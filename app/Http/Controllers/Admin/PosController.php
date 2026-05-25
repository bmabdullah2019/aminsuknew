<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\Shipping;
use App\Models\Warehouse;
use App\Services\StockService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Toastr;

class PosController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Show the POS page.
     */
    public function index()
    {
        $warehouses = Warehouse::where('is_active', 1)->orderBy('name')->get(['id', 'name']);
        $defaultWarehouse = $warehouses->first();
        $shippingCharges = \App\Models\ShippingCharge::where('status', 1)->get();

        return view('backEnd.pos.index', compact('warehouses', 'defaultWarehouse', 'shippingCharges'));
    }

    /**
     * AJAX: Get paginated products for the product grid.
     */
    public function getProducts(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $query = Product::query()
            ->where('status', 1)
            ->select('id', 'name', 'slug', 'new_price', 'purchase_price', 'sku', 'product_code', 'has_variant', 'thumbnail');

        if ($search !== '' && mb_strlen($search) >= 2) {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like, $search) {
                $q->where('name', 'like', $like)
                  ->orWhere('sku', 'like', $like)
                  ->orWhere('product_code', 'like', $like);
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        $products = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        $items = $products->getCollection()->map(function ($product) {
            $image = Productimage::where('product_id', $product->id)
                ->orderBy('sort_order')->orderBy('id')
                ->value('image');

            $imageUrl = null;
            if ($product->thumbnail) {
                $imageUrl = asset('public/storage/' . $product->thumbnail);
            } elseif ($image) {
                $imageUrl = asset($image);
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->new_price,
                'purchase_price' => (float) $product->purchase_price,
                'sku' => $product->sku,
                'product_code' => $product->product_code,
                'has_variant' => (bool) $product->has_variant,
                'stock' => $product->available_stock,
                'image' => $imageUrl,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }

    /**
     * AJAX: Search customers by phone.
     */
    public function searchCustomers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $customers = Customer::where('phone', 'like', "%{$q}%")
            ->orWhere('name', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id', 'name', 'phone', 'address', 'area']);

        return response()->json(['success' => true, 'data' => $customers]);
    }

    /**
     * AJAX: Complete a POS sale.
     */
    public function completeSale(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'delivery_area' => 'nullable|string|max:255',
            'shipping_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'warehouse_id' => 'nullable|integer',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $items = $request->input('items', []);
        $shippingFee = (float) $request->input('shipping_fee', 0);
        $orderDiscount = (float) $request->input('discount', 0);
        $paymentMethod = $request->input('payment_method', 'Cash');
        $warehouseId = $request->input('warehouse_id') ?: Warehouse::where('is_active', 1)->value('id');

        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $lineDiscount = (float) ($item['discount'] ?? 0);
            $lineTotal = ((float) $item['price'] * (int) $item['qty']) - $lineDiscount;
            $subtotal += max(0, $lineTotal);
        }
        $grandTotal = max(0, $subtotal + $shippingFee - $orderDiscount);

        try {
            DB::beginTransaction();

            // Create or find customer
            $customerId = null;
            $customerName = $request->input('customer_name', 'Walk-in Customer');
            $customerPhone = $request->input('customer_phone');

            if ($customerPhone) {
                $customer = Customer::where('phone', $customerPhone)->first();
                if ($customer) {
                    $customerId = $customer->id;
                } else {
                    $customer = new Customer();
                    $customer->id = Customer::max('id') + 1;
                    $customer->name = $customerName ?: 'Walk-in Customer';
                    $customer->phone = $customerPhone;
                    $customer->slug = Str::slug($customerName ?: 'walk-in') . '-' . time();
                    $customer->address = $request->input('customer_address', '');
                    $customer->status = 1;
                    $customer->password = bcrypt(Str::random(12));
                    $customer->save();
                    $customerId = $customer->id;
                }
            }

            $deliveredStatusId = \App\Models\OrderStatus::where('name', 'like', '%deliver%')->value('id') ?? 5;

            // Create Order
            $invoiceId = Order::generateInvoiceId();
            $order = Order::create([
                'invoice_id' => $invoiceId,
                'amount' => $grandTotal,
                'amount_minor' => (int) round($grandTotal * 100),
                'discount' => $orderDiscount,
                'discount_minor' => (int) round($orderDiscount * 100),
                'shipping_charge' => $shippingFee,
                'shipping_charge_minor' => (int) round($shippingFee * 100),
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'order_status' => $deliveredStatusId,
                'currency' => 'BDT',
                'user_id' => auth()->id(),
                'note' => 'POS Sale',
            ]);

            // Create order details
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if (! $product) continue;

                $lineDiscount = (float) ($item['discount'] ?? 0);

                OrderDetails::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'warehouse_id' => $warehouseId,
                    'product_name' => $product->name,
                    'purchase_price' => $product->purchase_price,
                    'purchase_price_minor' => (int) round(($product->purchase_price ?? 0) * 100),
                    'sale_price' => (float) $item['price'],
                    'sale_price_minor' => (int) round((float) $item['price'] * 100),
                    'qty' => (int) $item['qty'],
                    'product_discount' => $lineDiscount,
                    'currency' => 'BDT',
                ]);

                // Deduct stock
                if ($warehouseId) {
                    $this->stockService->adjustStock(
                        $product->id,
                        $item['variant_id'] ?? null,
                        -1 * (float) $item['qty']
                    );
                }
            }

            // Create shipping record
            $shipping = new Shipping();
            $shipping->order_id = $order->id;
            $shipping->customer_id = $customerId;
            $shipping->name = $customerName ?: 'Walk-in Customer';
            $shipping->phone = $customerPhone ?? '';
            $shipping->address = $request->input('customer_address', '');
            $shipping->area = $request->input('delivery_area', '');
            $shipping->save();

            // Create payment
            Payment::create([
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'amount' => $grandTotal,
                'amount_minor' => (int) round($grandTotal * 100),
                'currency' => 'BDT',
                'trx_id' => 'POS-' . $order->id . '-' . time(),
                'payment_method' => $paymentMethod,
                'gateway' => 'pos',
                'payment_status' => 'paid',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully!',
                'invoice_id' => $invoiceId,
                'order_id' => $order->id,
                'grand_total' => $grandTotal,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete sale: ' . $e->getMessage(),
            ], 500);
        }
    }
}
