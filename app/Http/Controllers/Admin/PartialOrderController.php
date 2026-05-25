<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockReservationException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PartialOrder;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipping;
use App\Models\Warehouse;
use App\Services\PhoneBlockService;
use App\Services\StockEngine;
use App\Services\VariantStockService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PartialOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|partial-order-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|partial-order-view', ['only' => ['show']]);
        $this->middleware('role_or_permission:Admin|partial-order-convert', ['only' => ['convert']]);
        $this->middleware('role_or_permission:Admin|partial-order-delete', ['only' => ['delete', 'bulkDelete']]);
    }

    public function index(Request $request)
    {
        $items = PartialOrder::where('status', 'incomplete')->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->paginate(40);

        return view('admin.partial_orders.index', compact('items'));
    }

    public function show($id)
    {
        $item = PartialOrder::findOrFail($id);

        return view('admin.partial_orders.show', compact('item'));
    }

    public function convert(Request $request, $id)
    {
        $partial = PartialOrder::findOrFail($id);

        $phoneBlock = app(PhoneBlockService::class)->getActiveBlockForPhone((string) ($partial->phone ?? ''));
        if ($phoneBlock) {
            return redirect()->route('admin.partial-orders.index')
                ->with('error', 'Cannot convert partial order: phone number is blocked for new orders.');
        }

        // Validate that partial order has products
        if (! $partial->products || empty($partial->products)) {
            return redirect()->route('admin.partial-orders.index')->with('error', 'Partial order has no products to convert.');
        }

        // Get default warehouse
        $warehouse = Warehouse::main()->active()->first() ?? Warehouse::active()->first();
        if (! $warehouse) {
            return redirect()->route('admin.partial-orders.index')->with('error', 'No active warehouse found.');
        }

        // Create or find customer
        $customer = Customer::where('phone', $partial->phone)->first();
        if (! $customer) {
            $customer = Customer::create([
                'name' => $partial->name,
                'phone' => $partial->phone,
                'email' => null,
                'password' => bcrypt('123456'), // Default password
                'verify' => 1,
                'status' => 'active',
            ]);
        }

        // Calculate totals
        $subtotal = 0;
        $shippingCharge = 0; // Default shipping charge for admin conversions

        foreach ($partial->products as $productData) {
            $product = Product::find($productData['id']);
            if ($product) {
                $subtotal += $product->new_price * $productData['qty'];
            }
        }

        $totalAmount = $subtotal + $shippingCharge;

        try {
            $order = DB::transaction(function () use ($customer, $partial, $warehouse, $shippingCharge, $totalAmount) {
                // Create Order
                $order = Order::create([
                    'invoice_id' => Order::generateInvoiceId(),
                    'amount' => $totalAmount,
                    'discount' => 0,
                    'shipping_charge' => $shippingCharge,
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'order_status' => 1, // Pending
                    'note' => 'Converted from partial order #'.$partial->id,
                ]);

                // Create Shipping
                Shipping::create([
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'name' => $partial->name,
                    'phone' => $partial->phone,
                    'address' => $partial->address ?? '',
                    'area' => 'Admin Conversion',
                ]);

                // Create Payment
                Payment::create([
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'payment_method' => 'cash', // Default for admin conversions
                    'amount' => $totalAmount,
                    'payment_status' => 'pending',
                ]);

                // Create Order Details and Reserve Stock
                $stockEngine = app(StockEngine::class);
                $variantStockService = app(VariantStockService::class);

                foreach ($partial->products as $productData) {
                    $product = Product::find($productData['id']);
                    if (! $product) {
                        throw new \Exception("Product ID {$productData['id']} not found");
                    }

                    // Check if product has variants
                    $variant = ProductVariant::where('product_id', $product->id)->first();

                    // Create Order Detail
                    $orderDetail = OrderDetails::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant ? $variant->id : null,
                        'warehouse_id' => $warehouse->id,
                        'product_name' => $product->name,
                        'purchase_price' => $product->purchase_price ?? 0,
                        'product_color' => null,
                        'product_size' => null,
                        'sale_price' => $product->new_price,
                        'qty' => $productData['qty'],
                    ]);

                    // Reserve stock
                    if ($variant) {
                        $variantStockService->reserveStock($warehouse->id, $variant->id, $productData['qty'], $order->id);
                    } else {
                        $stockEngine->reserve(
                            warehouseId: (int) $warehouse->id,
                            productId: (int) $product->id,
                            quantity: (float) $productData['qty'],
                            referenceId: (int) $order->id,
                            referenceType: 'order',
                            variantId: null,
                            notes: "Reserved stock for converted partial order #{$order->id}"
                        );
                    }
                }

                return $order;
            });

            // Mark partial order as completed
            $partial->update(['status' => 'completed']);

            return redirect()->route('admin.partial-orders.index')->with('success', 'Partial order successfully converted to order #'.$order->invoice_id);

        } catch (StockReservationException $e) {
            Log::error('Partial order conversion stock reservation failed', [
                'partial_order_id' => $partial->id,
                'warehouse_id' => $warehouse->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.partial-orders.index')->with('error', 'Failed to convert partial order: Insufficient stock for one or more products.');

        } catch (\Exception $e) {
            Log::error('Partial order conversion failed', [
                'partial_order_id' => $partial->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.partial-orders.index')->with('error', 'Failed to convert partial order: '.$e->getMessage());
        }
    }

    public function delete($id)
    {
        $partial = PartialOrder::findOrFail($id);
        $partial->delete();

        return redirect()->route('admin.partial-orders.index')->with('success', 'Partial order deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! empty($ids)) {
            PartialOrder::whereIn('id', $ids)->delete();

            return response()->json(['success' => true, 'message' => 'Selected partial orders deleted successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'No orders selected.']);
    }
}
