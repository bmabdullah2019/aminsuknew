<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\OrderStateMachine;
use App\Exports\ArrayReportExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Courierapi;
use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\SmsGateway;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\FraudCheckerService;
use App\Services\LedgerService;
use App\Services\PhoneBlockService;
use App\Services\StockEngine;
use App\Services\WarehouseStockService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Cart;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Session;
use Throwable;
use Toastr;

class OrderController extends Controller
{
    private const MAX_REPORT_RANGE_DAYS = 730;

    private const ORDER_LIST_PAGE_SIZE = 10;

    private const PATHAO_CACHE_TTL_MINUTES = 30;

    private OrderStateMachine $orderStateMachine;

    private WarehouseStockService $warehouseStockService;

    private StockEngine $stockEngine;

    private LedgerService $ledgerService;

    public function __construct(OrderStateMachine $orderStateMachine, WarehouseStockService $warehouseStockService, StockEngine $stockEngine, LedgerService $ledgerService)
    {
        $this->orderStateMachine = $orderStateMachine;
        $this->warehouseStockService = $warehouseStockService;
        $this->stockEngine = $stockEngine;
        $this->ledgerService = $ledgerService;

        $this->middleware('permission:order-view', ['only' => ['index', 'invoice', 'process', 'order_print']]);
        $this->middleware('permission:order-status-change', ['only' => ['order_process', 'order_status', 'bulk_courier']]);
        $this->middleware('permission:order-assign', ['only' => ['order_assign']]);
        $this->middleware('permission:order-delete', ['only' => ['destroy', 'bulk_destroy']]);
    }

    public function index($slug, Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);
        $keyword = trim((string) ($validated['keyword'] ?? ''));

        $orderListQuery = Order::query()
            ->select([
                'id',
                'invoice_id',
                'amount',
                'updated_at',
                'order_status',
            ])
            ->with([
                'shipping:id,order_id,name,phone,address',
                'status:id,name',
                'orderdetails:id,order_id,product_id',
                'orderdetails.image:id,product_id,image',
            ])
            ->latest('id');

        if ($slug == 'all') {
            $order_status = (object) [
                'name' => 'All',
                'orders_count' => Order::count(),
            ];
            $show_data = clone $orderListQuery;
            if ($keyword !== '') {
                $show_data = $show_data->where(function ($query) use ($keyword) {
                    $query->where('invoice_id', 'LIKE', '%'.$keyword.'%')
                        ->orWhereHas('shipping', function ($subQuery) use ($keyword) {
                            $subQuery->where('phone', $keyword);
                        });
                });
            }
            $show_data = $show_data->paginate(self::ORDER_LIST_PAGE_SIZE)->appends($request->query());
        } else {
            $order_status = OrderStatus::where('slug', $slug)->withCount('orders')->firstOrFail();
            $show_data = (clone $orderListQuery)
                ->where('order_status', $order_status->id)
                ->paginate(self::ORDER_LIST_PAGE_SIZE)
                ->appends($request->query());
        }
        $users = User::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $steadfast = Courierapi::where(['status' => 1, 'type' => 'steadfast'])->first();
        $pathao_info = Courierapi::where(['status' => 1, 'type' => 'pathao'])->select('id', 'type', 'url', 'token', 'status')->first();

        // Fetch Pathao bootstrap data via short timeout and cache to avoid blocking list rendering.
        if ($pathao_info) {
            ['cities' => $pathaocities, 'stores' => $pathaostore] = $this->pathaoBootstrapData($pathao_info);
        } else {
            $pathaocities = [];
            $pathaostore = [];
        }

        $fraudService = app(FraudCheckerService::class);
        $show_data->getCollection()->transform(function ($order) use ($fraudService) {
            if ($order->shipping && $order->shipping->phone) {
                $riskData = $fraudService->getCachedFormattedRiskData((string) $order->shipping->phone);
                if (is_array($riskData)) {
                    $order->fraud_risk_class = (string) ($riskData['badge_class'] ?? 'secondary');
                    $order->fraud_risk_text = (string) ($riskData['formatted_text'] ?? 'N/A');

                    return $order;
                }

                $order->fraud_risk_class = 'secondary';
                $order->fraud_risk_text = 'Check';

                return $order;
            }

            $order->fraud_risk_class = 'secondary';
            $order->fraud_risk_text = 'No Phone';

            return $order;
        });

        return view('backEnd.order.index', compact('show_data', 'order_status', 'users', 'steadfast', 'pathaostore', 'pathaocities'));
    }

    private function pathaoBootstrapData(Courierapi $pathaoInfo): array
    {
        $cacheMinutes = now()->addMinutes(self::PATHAO_CACHE_TTL_MINUTES);
        $citiesCacheKey = 'pathao:cities:'.md5((string) $pathaoInfo->url);
        $storesCacheKey = 'pathao:stores:'.md5((string) $pathaoInfo->url.'|'.(string) $pathaoInfo->token);

        $cities = Cache::remember($citiesCacheKey, $cacheMinutes, function () use ($pathaoInfo) {
            return $this->fetchPathaoData($pathaoInfo->url.'/api/v1/countries/1/city-list');
        });

        $stores = Cache::remember($storesCacheKey, $cacheMinutes, function () use ($pathaoInfo) {
            return $this->fetchPathaoData(
                $pathaoInfo->url.'/api/v1/stores',
                [
                    'Authorization' => 'Bearer '.$pathaoInfo->token,
                    'Content-Type' => 'application/json',
                ]
            );
        });

        return [
            'cities' => is_array($cities) ? $cities : [],
            'stores' => is_array($stores) ? $stores : [],
        ];
    }

    private function fetchPathaoData(string $url, array $headers = []): array
    {
        try {
            $request = Http::connectTimeout(2)->timeout(3);
            if (! empty($headers)) {
                $request = $request->withHeaders($headers);
            }

            $response = $request->get($url);
            if (! $response->successful()) {
                Log::warning('Pathao bootstrap request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : [];
        } catch (Throwable $exception) {
            Log::warning('Pathao bootstrap request exception', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    public function pathaocity(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|integer|min:1',
        ]);

        $pathao_info = Courierapi::where(['status' => 1, 'type' => 'pathao'])->select('id', 'type', 'url', 'token', 'status')->first();
        if ($pathao_info) {
            $response = Http::get($pathao_info->url.'/api/v1/cities/'.$validated['city_id'].'/zone-list');
            $pathaozones = $response->json();

            return response()->json($pathaozones);
        } else {
            return response()->json([]);
        }
    }

    public function pathaozone(Request $request)
    {
        $validated = $request->validate([
            'zone_id' => 'required|integer|min:1',
        ]);

        $pathao_info = Courierapi::where(['status' => 1, 'type' => 'pathao'])->select('id', 'type', 'url', 'token', 'status')->first();
        if ($pathao_info) {
            $response = Http::get($pathao_info->url.'/api/v1/zones/'.$validated['zone_id'].'/area-list');
            $pathaoareas = $response->json();

            return response()->json($pathaoareas);
        } else {
            return response()->json([]);
        }
    }

    public function order_pathao(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|distinct|exists:orders,id',
            'pathaostore' => 'required|integer|min:1',
            'pathaocity' => 'required|integer|min:1',
            'pathaozone' => 'required|integer|min:1',
            'pathaoarea' => 'required|integer|min:1',
        ]);

        $pathaoInfo = Courierapi::where(['status' => 1, 'type' => 'pathao'])
            ->select('id', 'type', 'url', 'token', 'status')
            ->first();
        if (! $pathaoInfo) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Pathao courier is not configured.',
            ], 422);
        }

        $orders = Order::with('shipping')
            ->whereIn('id', $validated['order_ids'])
            ->get();

        $successOrders = [];
        $failedOrders = [];

        foreach ($orders as $order) {
            if (! $order->shipping) {
                $failedOrders[] = [
                    'order_id' => $order->id,
                    'message' => 'Shipping information is missing.',
                ];

                continue;
            }

            try {
                $response = Http::timeout(20)->withHeaders([
                    'Authorization' => 'Bearer '.$pathaoInfo->token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post($pathaoInfo->url.'/api/v1/orders', [
                    'store_id' => (int) $validated['pathaostore'],
                    'merchant_order_id' => $order->invoice_id,
                    'sender_name' => 'Test',
                    'sender_phone' => (string) $order->shipping->phone,
                    'recipient_name' => (string) $order->shipping->name,
                    'recipient_phone' => (string) $order->shipping->phone,
                    'recipient_address' => (string) $order->shipping->address,
                    'recipient_city' => (int) $validated['pathaocity'],
                    'recipient_zone' => (int) $validated['pathaozone'],
                    'recipient_area' => (int) $validated['pathaoarea'],
                    'delivery_type' => 48,
                    'item_type' => 2,
                    'special_instruction' => 'Special note- product must be check after delivery',
                    'item_quantity' => 1,
                    'item_weight' => 0.5,
                    'amount_to_collect' => round((float) $order->amount),
                    'item_description' => 'Special note- product must be check after delivery',
                ]);

                $payload = $response->json();
                $consignmentId = $payload['data']['consignment_id'] ?? null;
                if ($response->successful() && $consignmentId) {
                    $successOrders[] = [
                        'order_id' => $order->id,
                        'consignment_id' => $consignmentId,
                    ];

                    continue;
                }

                $failedOrders[] = [
                    'order_id' => $order->id,
                    'message' => $payload['message'] ?? 'Pathao order failed.',
                ];
            } catch (Throwable $e) {
                report($e);
                $failedOrders[] = [
                    'order_id' => $order->id,
                    'message' => 'Pathao request failed.',
                ];
            }
        }

        if (! empty($successOrders)) {
            Toastr::success('Pathao order request completed', 'Success');
        }

        return response()->json([
            'status' => ! empty($successOrders) ? 'success' : 'failed',
            'message' => ! empty($successOrders) ? 'Pathao order request completed.' : 'Pathao order request failed.',
            'success' => json_encode($successOrders),
            'failed' => json_encode($failedOrders),
        ]);
    }

    public function invoice($invoice_id)
    {
        $order = Order::where(['invoice_id' => $invoice_id])->with('orderdetails.image', 'payment', 'shipping', 'customer')->firstOrFail();

        return view('backEnd.order.invoice', compact('order'));
    }

    public function invoicePdf($invoice_id)
    {
        $order = Order::where(['invoice_id' => $invoice_id])
            ->with('orderdetails.image', 'payment', 'shipping', 'customer')
            ->firstOrFail();

        $generalsetting = GeneralSetting::where('status', 1)->first();
        $contact = Contact::where('status', 1)->first();

        $pdf = Pdf::loadView('backEnd.order.invoice_pdf', compact('order', 'generalsetting', 'contact'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('invoice-'.$order->invoice_id.'.pdf');
    }

    public function process($invoice_id)
    {
        $data = Order::where(['invoice_id' => $invoice_id])->select('id', 'invoice_id', 'order_status')->with('orderdetails')->firstOrFail();
        $shippingcharge = ShippingCharge::where('status', 1)->get();

        return view('backEnd.order.process', compact('data', 'shippingcharge'));
    }

    public function order_process(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:orders,id',
            'status' => 'required|exists:order_statuses,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:1000',
            'area' => 'required|exists:shipping_charges,id',
            'admin_note' => 'nullable|string',
        ]);

        $orderStatus = $this->resolveEffectiveOrderStatus((int) $validated['status']);
        $targetStatusId = (int) $orderStatus->id;
        $link = $orderStatus->slug;
        $isReturnedTarget = str_contains(strtolower((string) $orderStatus->slug), 'return')
            || str_contains(strtolower((string) $orderStatus->name), 'return')
            || (int) $orderStatus->id === 7;

        $order = Order::findOrFail($validated['id']);
        $previousStatus = (int) $order->order_status;
        $shippingfee = ShippingCharge::findOrFail($validated['area']);

        DB::transaction(function () use ($validated, $shippingfee, $targetStatusId, &$order, &$previousStatus) {
            $order = Order::query()->whereKey($validated['id'])->lockForUpdate()->firstOrFail();
            $previousStatus = (int) $order->order_status;
            $order->admin_note = $validated['admin_note'] ?? null;

            $shippingDifference = (float) $shippingfee->amount - (float) $order->shipping_charge;
            $order->shipping_charge = (float) $shippingfee->amount;
            $order->amount = max(0, (float) $order->amount + $shippingDifference);
            $order->save();

            $this->orderStateMachine->transition($order, $targetStatusId, [
                'actor_type' => 'admin',
                'actor_id' => Auth::id(),
                'source' => 'admin_order_process',
                'reason' => 'Manual status update',
            ]);

            $shippingUpdate = Shipping::firstOrNew(['order_id' => $order->id]);
            $shippingUpdate->customer_id = $order->customer_id;
            $shippingUpdate->name = $validated['name'];
            $shippingUpdate->phone = $validated['phone'];
            $shippingUpdate->address = $validated['address'];
            $shippingUpdate->area = $shippingfee->name;
            $shippingUpdate->save();
        });
        $order->refresh()->load('shipping');

        if ($this->isShippedOrderStatus($orderStatus) && $previousStatus !== $targetStatusId) {
            $courier_info = Courierapi::where(['status' => 1, 'type' => 'steadfast'])->first();
            if ($courier_info) {
                $consignmentData = [
                    'invoice' => $order->invoice_id,
                    'recipient_name' => $order->shipping ? $order->shipping->name : 'InboxHat',
                    'recipient_phone' => $order->shipping ? $order->shipping->phone : '01750578495',
                    'recipient_address' => $order->shipping ? $order->shipping->address : '01750578495',
                    'cod_amount' => $order->amount,
                ];
                $client = new Client;
                try {
                    $response = $client->post($courier_info->url, [
                        'json' => $consignmentData,
                        'headers' => [
                            'Api-Key' => $courier_info->api_key,
                            'Secret-Key' => $courier_info->secret_key,
                            'Accept' => 'application/json',
                        ],
                    ]);
                    $responseData = json_decode($response->getBody(), true);
                    if (isset($responseData['consignment'])) {
                        $order->steadfast_consignment_id = $responseData['consignment']['consignment_id'] ?? null;
                        $order->steadfast_tracking_code = $responseData['consignment']['tracking_code'] ?? null;
                        $order->steadfast_status = $responseData['consignment']['status'] ?? 'in_review';
                        $order->save();
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $successMessage = $isReturnedTarget
            ? 'Order marked as returned and synced to return list.'
            : 'Order status change successfully';
        Toastr::success('Success', $successMessage);

        return redirect('admin/order/'.$link);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:orders,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $orderId = (int) $validated['id'];
                $order = Order::with('orderdetails')->lockForUpdate()->findOrFail($orderId);

                // Release any reserved stock before deleting
                try {
                    $this->stockEngine->releaseForOrder($order);
                } catch (Throwable $e) {
                    Log::warning('Could not release stock during order delete', [
                        'order_id' => $orderId,
                        'message' => $e->getMessage(),
                    ]);
                }

                // Reverse any ledger journal entries for this order
                try {
                    $this->ledgerService->reverseJournalsByReference('order', $orderId, Auth::id());
                } catch (Throwable $e) {
                    Log::warning('Could not reverse ledger entries during order delete', [
                        'order_id' => $orderId,
                        'message' => $e->getMessage(),
                    ]);
                }

                OrderDetails::where('order_id', $orderId)->delete();
                Shipping::where('order_id', $orderId)->delete();
                Payment::where('order_id', $orderId)->delete();
                $order->delete();
            });
        } catch (Throwable $e) {
            report($e);
            Toastr::error('Failed to delete order.', 'Error');

            return redirect()->back();
        }

        Toastr::success('Success', 'Order delete success successfully');

        return redirect()->back();
    }

    public function order_assign(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|distinct|exists:orders,id',
        ]);

        try {
            $updatedRows = Order::whereIn('id', $validated['order_ids'])
                ->update(['user_id' => $validated['user_id'] ?? null]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to assign orders.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Assigned '.$updatedRows.' orders successfully.',
        ]);
    }

    public function order_status(Request $request)
    {
        $validated = $request->validate([
            'order_status' => 'required|integer|exists:order_statuses,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|distinct|exists:orders,id',
        ]);

        $sms_gateway = SmsGateway::where('status', 1)->first();
        $site_setting = GeneralSetting::where('status', 1)->first();
        $orderStatus = $this->resolveEffectiveOrderStatus((int) $validated['order_status']);
        $targetStatusId = (int) $orderStatus->id;
        $siteName = $site_setting->name ?? config('app.name', 'our store');
        $isReturnedTarget = str_contains(strtolower((string) $orderStatus->slug), 'return')
            || str_contains(strtolower((string) $orderStatus->name), 'return')
            || (int) $orderStatus->id === 7;

        // Update order statuses
        try {
            $orders = DB::transaction(function () use ($validated, $targetStatusId) {
                $orders = Order::whereIn('id', $validated['order_ids'])
                    ->lockForUpdate()
                    ->get();

                foreach ($orders as $order) {
                    $this->orderStateMachine->transition($order, $targetStatusId, [
                        'actor_type' => 'admin',
                        'actor_id' => Auth::id(),
                        'source' => 'admin_bulk_status',
                        'reason' => 'Bulk status update',
                    ]);
                }

                return $orders;
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to update order status: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
            ], 500);
        }

        $orders->loadMissing('customer:id,name,phone', 'shipping');

        // ─── Auto-send to Steadfast courier when status → Shipped (4) ─────
        if ($this->isShippedOrderStatus($orderStatus)) {
            $courier_info = Courierapi::where(['status' => 1, 'type' => 'steadfast'])->first();
            if ($courier_info) {
                $client = new Client;
                foreach ($orders as $order) {
                    // Skip orders that are already shipped or have a tracking code
                    if ($order->steadfast_tracking_code) {
                        continue;
                    }

                    $consignmentData = [
                        'invoice' => $order->invoice_id,
                        'recipient_name' => $order->shipping ? $order->shipping->name : 'N/A',
                        'recipient_phone' => $order->shipping ? $order->shipping->phone : '',
                        'recipient_address' => $order->shipping ? $order->shipping->address : 'Address not provided',
                        'cod_amount' => $order->amount,
                    ];

                    try {
                        $response = $client->post($courier_info->url, [
                            'json' => $consignmentData,
                            'headers' => [
                                'Api-Key' => $courier_info->api_key,
                                'Secret-Key' => $courier_info->secret_key,
                                'Accept' => 'application/json',
                            ],
                        ]);

                        $responseData = json_decode($response->getBody(), true);
                        if (isset($responseData['consignment'])) {
                            $order->steadfast_consignment_id = $responseData['consignment']['consignment_id'] ?? null;
                            $order->steadfast_tracking_code = $responseData['consignment']['tracking_code'] ?? null;
                            $order->steadfast_status = $responseData['consignment']['status'] ?? 'in_review';
                            $order->save();
                        }
                    } catch (Throwable $e) {
                        report($e);
                        Log::warning('Steadfast courier send failed during bulk status change', [
                            'order_id' => $order->id,
                            'invoice' => $order->invoice_id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        foreach ($orders as $order) {
            // Send SMS to the customer
            if (! $sms_gateway) {
                continue;
            }

            $customer_info = $order->customer;
            if (! $customer_info) {
                continue;
            }

            try {
                $url = $sms_gateway->url;
                $data = [
                    'api_key' => $sms_gateway->api_key,
                    'number' => $customer_info->phone,
                    'type' => 'text',
                    'senderid' => $sms_gateway->serderid,
                    'message' => "Dear {$customer_info->name},\r\n"
                              ."Your order (Order ID: {$order->invoice_id}) status has been updated to: "
                              ."{$orderStatus->name}.\r\n"
                              ."Thank you for using {$siteName}!",
                ];

                // cURL request to send SMS
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_exec($ch);
                curl_close($ch);
            } catch (Throwable $e) {
                report($e);
            }
        }

        // Stock management is enforced in OrderStateMachine transitions.
        // If stock mutation fails, the status update transaction is rolled back.
        $successMessage = $isReturnedTarget
            ? 'Order status updated successfully and return list synced for returned orders.'
            : 'Order status change successfully';

        return response()->json(['status' => 'success', 'message' => $successMessage]);
    }

    private function resolveEffectiveOrderStatus(int $requestedStatusId): OrderStatus
    {
        $requestedStatus = OrderStatus::query()
            ->select(['id', 'name', 'slug'])
            ->findOrFail($requestedStatusId);

        if (! $this->isConfirmedOrderStatus($requestedStatus)) {
            return $requestedStatus;
        }

        return $this->processingOrderStatus() ?? $requestedStatus;
    }

    private function isConfirmedOrderStatus(OrderStatus $status): bool
    {
        return strtolower((string) $status->slug) === 'confirmed'
            || strtolower((string) $status->name) === 'confirmed';
    }

    private function isShippedOrderStatus(OrderStatus $status): bool
    {
        return strtolower((string) $status->slug) === 'shipped'
            || strtolower((string) $status->name) === 'shipped';
    }

    private function processingOrderStatus(): ?OrderStatus
    {
        return OrderStatus::query()
            ->select(['id', 'name', 'slug'])
            ->where(function ($query) {
                $query->whereRaw('LOWER(slug) = ?', ['processing'])
                    ->orWhereRaw('LOWER(name) = ?', ['processing']);
            })
            ->orderBy('id')
            ->first();
    }

    public function bulk_destroy(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|distinct|exists:orders,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $orderIds = $validated['order_ids'];
                $orders = Order::with('orderdetails')->whereIn('id', $orderIds)->lockForUpdate()->get();

                // Release reserved stock for each order before deleting
                foreach ($orders as $order) {
                    try {
                        $this->stockEngine->releaseForOrder($order);
                    } catch (Throwable $e) {
                        Log::warning('Could not release stock during bulk order delete', [
                            'order_id' => $order->id,
                            'message' => $e->getMessage(),
                        ]);
                    }

                    // Reverse any ledger journal entries for this order
                    try {
                        $this->ledgerService->reverseJournalsByReference('order', (int) $order->id, Auth::id());
                    } catch (Throwable $e) {
                        Log::warning('Could not reverse ledger entries during bulk order delete', [
                            'order_id' => $order->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                OrderDetails::whereIn('order_id', $orderIds)->delete();
                Shipping::whereIn('order_id', $orderIds)->delete();
                Payment::whereIn('order_id', $orderIds)->delete();
                Order::whereIn('id', $orderIds)->delete();
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'failed',
                'message' => 'Order delete failed.',
            ], 500);
        }

        return response()->json(['status' => 'success', 'message' => 'Order delete successfully']);
    }

    public function order_print(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|distinct|exists:orders,id',
        ]);

        $orders = Order::whereIn('id', $validated['order_ids'])->with('orderdetails', 'payment', 'shipping', 'customer')->get();
        $view = view('backEnd.order.print', ['orders' => $orders])->render();

        return response()->json(['status' => 'success', 'view' => $view]);
    }
    // public function bulk_courier($slug, Request $request)
    // {
    //     $courier_info = Courierapi::where(['status' => 1, 'type' => $slug])->first();

    //     if ($courier_info) {
    //         $orders_ids = $request->order_ids;

    //         foreach ($orders_ids as $order_id) {
    //             $order = Order::find($order_id);

    //             $courier = $order->order_status;
    //             if ($request->status == 5 && $courier != 5) {
    //                 $consignmentData = [
    //                     'invoice' => $order->invoice_id,
    //                     'recipient_name' => $order->shipping ? $order->shipping->name : 'InboxHat',
    //                     'recipient_phone' => $order->shipping ? $order->shipping->phone : '01750578495',
    //                     'recipient_address' => $order->shipping ? $order->shipping->address : '01750578495',
    //                     'cod_amount' => $order->amount
    //                 ];
    //                 $client = new Client();
    //                 $response = $client->post('$courier_info->url', [
    //                     'json' => $consignmentData,
    //                     'headers' => [
    //                         'Api-Key' => '$courier_info->api_key',
    //                         'Secret-Key' => '$courier_info->secret_key',
    //                         'Accept' => 'application/json',
    //                     ],
    //                 ]);

    //                 $responseData = json_decode($response->getBody(), true);
    //                 if ($responseData['status'] == 200) {
    //                     $message = 'Your order place to courier successfully';
    //                     $status = 'success';
    //                     $order->order_status = 4;
    //                     $order->save();
    //                 } else {
    //                     $message = 'Your order place to courier failed';
    //                     $status = 'failed';
    //                 }
    //                 return response()->json(['status' => $status, 'message' => $message]);
    //             }

    //         }
    //     } else {
    //         return "stop";
    //     }
    // }
    public function bulk_courier($slug, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|integer',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|distinct|exists:orders,id',
        ]);

        if ((int) $validated['status'] !== 4) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid target status for courier handover.',
            ], 422);
        }

        $courier_info = Courierapi::where(['status' => 1, 'type' => $slug])->first();

        if ($courier_info) {
            $orders_ids = $validated['order_ids'];
            $orders = Order::query()
                ->whereIn('id', $orders_ids)
                ->with('shipping')
                ->get()
                ->keyBy('id');
            $successOrders = [];
            $failedOrders = [];

            foreach ($orders_ids as $order_id) {
                $order = $orders->get((int) $order_id);

                if ($order && (int) $order->order_status !== 4) {
                    $consignmentData = [
                        'invoice' => $order->invoice_id,
                        'recipient_name' => $order->shipping ? $order->shipping->name : 'InboxHat',
                        'recipient_phone' => $order->shipping ? $order->shipping->phone : '01750578495',
                        'recipient_address' => $order->shipping ? $order->shipping->address : 'Address not provided',
                        'cod_amount' => $order->amount,
                    ];

                    $client = new Client;
                    try {
                        $response = $client->post($courier_info->url, [
                            'json' => $consignmentData,
                            'headers' => [
                                'Api-Key' => $courier_info->api_key,
                                'Secret-Key' => $courier_info->secret_key,
                                'Accept' => 'application/json',
                            ],
                        ]);

                        $responseData = json_decode($response->getBody(), true);

                        if ($responseData['status'] == 200) {
                            $consignment = $responseData['consignment'] ?? [];
                            DB::transaction(function () use ($order, $consignment) {
                                $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();
                                if ($lockedOrder && (int) $lockedOrder->order_status !== 4) {
                                    $this->orderStateMachine->transition($lockedOrder, 4, [
                                        'actor_type' => 'admin',
                                        'actor_id' => auth()->id(),
                                        'source' => 'bulk_courier',
                                        'reason' => 'Courier handover accepted',
                                        'meta' => ['courier' => 'bulk'],
                                    ]);
                                }
                                // Save Steadfast tracking data
                                if ($lockedOrder) {
                                    $lockedOrder->steadfast_consignment_id = $consignment['consignment_id'] ?? null;
                                    $lockedOrder->steadfast_tracking_code = $consignment['tracking_code'] ?? null;
                                    $lockedOrder->steadfast_status = $consignment['status'] ?? 'in_review';
                                    $lockedOrder->save();
                                }
                            });
                            $successOrders[] = [
                                'order_id' => $order_id,
                                'message' => $responseData['message'] ?? 'Order placed successfully',
                            ];
                        } else {
                            $failedOrders[] = [
                                'order_id' => $order_id,
                                'message' => $responseData['message'] ?? 'Failed to place order',
                            ];
                        }
                    } catch (\Exception $e) {
                        report($e);
                        // Add to failed orders if there's an exception
                        $failedOrders[] = [
                            'order_id' => $order_id,
                            'message' => 'Courier request failed.',
                        ];
                    }
                }
            }

            // Return summary of success and failure
            return response()->json([
                'status' => 'success',
                'message' => 'Your order place to courier successfully',
                'success' => json_encode($successOrders),
                'failed' => json_encode($failedOrders),
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Courier information not found.',
            ]);
        }
    }

    public function stock_report(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'stock_status' => 'nullable|in:in_stock,low_stock,out_of_stock',
            'period' => 'nullable|in:daily,monthly,yearly,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'export' => 'nullable|in:xlsx',
        ]);
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        [$resolvedStartDate, $resolvedEndDate] = $this->resolvePeriodDateRange(
            (string) ($validated['period'] ?? 'custom'),
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );
        if (! empty($resolvedStartDate) && ! $request->filled('start_date')) {
            $request->merge(['start_date' => $resolvedStartDate]);
        }
        if (! empty($resolvedEndDate) && ! $request->filled('end_date')) {
            $request->merge(['end_date' => $resolvedEndDate]);
        }
        if (! empty($resolvedStartDate) && ! empty($resolvedEndDate)) {
            $this->guardReportDateRange($resolvedStartDate, $resolvedEndDate);
        }

        $products = Product::query()
            ->leftJoin('warehouse_stock as ws', 'products.id', '=', 'ws.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.new_price',
                'products.purchase_price',
                DB::raw('COALESCE(SUM(ws.available_quantity), 0) as stock')
            )
            ->where('products.status', 1)
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.new_price', 'products.purchase_price');

        if ($keyword !== '') {
            $products->where(function ($query) use ($keyword) {
                $query->where('products.name', 'LIKE', '%'.$keyword.'%')
                    ->orWhere('products.product_code', 'LIKE', '%'.$keyword.'%')
                    ->orWhere('products.sku', 'LIKE', '%'.$keyword.'%');
            });
        }

        if (! empty($validated['category_id'])) {
            $products->where('products.category_id', (int) $validated['category_id']);
        }

        if (! empty($validated['warehouse_id'])) {
            $products->where('ws.warehouse_id', (int) $validated['warehouse_id']);
        }

        if (! empty($resolvedStartDate) && ! empty($resolvedEndDate)) {
            $products->whereBetween('ws.updated_at', [
                $resolvedStartDate.' 00:00:00',
                $resolvedEndDate.' 23:59:59',
            ]);
        }

        $stockStatus = $validated['stock_status'] ?? null;
        if ($stockStatus === 'in_stock') {
            $products->havingRaw('COALESCE(SUM(ws.available_quantity), 0) > 5');
        } elseif ($stockStatus === 'low_stock') {
            $products->havingRaw('COALESCE(SUM(ws.available_quantity), 0) > 0 AND COALESCE(SUM(ws.available_quantity), 0) <= 5');
        } elseif ($stockStatus === 'out_of_stock') {
            $products->havingRaw('COALESCE(SUM(ws.available_quantity), 0) <= 0');
        }

        $summary = DB::query()
            ->fromSub(clone $products, 'stock_rows')
            ->selectRaw('COALESCE(SUM(purchase_price * stock), 0) as total_purchase')
            ->selectRaw('COALESCE(SUM(stock), 0) as total_stock')
            ->selectRaw('COALESCE(SUM(new_price * stock), 0) as total_price')
            ->selectRaw('COUNT(*) as total_products')
            ->first();

        $total_purchase = (float) ($summary->total_purchase ?? 0);
        $total_stock = (float) ($summary->total_stock ?? 0);
        $total_price = (float) ($summary->total_price ?? 0);
        $total_products = (int) ($summary->total_products ?? 0);

        if (($validated['export'] ?? null) === 'xlsx') {
            $rows = (clone $products)
                ->orderBy('products.name')
                ->get()
                ->map(function ($product) {
                    return [
                        (string) $product->name,
                        (string) ($product->sku ?? ''),
                        (float) ($product->purchase_price ?? 0),
                        (float) ($product->new_price ?? 0),
                        (float) ($product->stock ?? 0),
                        round((float) ($product->purchase_price ?? 0) * (float) ($product->stock ?? 0), 2),
                        round((float) ($product->new_price ?? 0) * (float) ($product->stock ?? 0), 2),
                    ];
                })
                ->values()
                ->all();

            return Excel::download(
                new ArrayReportExport(
                    [
                        'Product',
                        'SKU',
                        'Purchase Price',
                        'Sale Price',
                        'Stock Qty',
                        'Stock Purchase Value',
                        'Stock Sale Value',
                    ],
                    $rows
                ),
                'stock-report-'.now()->format('Ymd_His').'.xlsx'
            );
        }

        $products = $products
            ->orderBy('products.name')
            ->paginate(10)
            ->appends($request->query());

        $categories = Category::where('status', 1)->orderBy('name')->get();
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('backEnd.reports.stock', compact(
            'products',
            'categories',
            'warehouses',
            'total_purchase',
            'total_stock',
            'total_price',
            'total_products'
        ));
    }

    public function order_report(Request $request)
    {
        return redirect()->route('admin.reports-new.sales', $request->query());
    }

    public function order_create()
    {
        $cartinfo = Cart::instance('pos_shopping')->destroy();
        $products = Product::select('id', 'name', 'new_price', 'product_code')->where(['status' => 1])->get();
        $cartinfo = Cart::instance('pos_shopping')->content();
        $shippingcharge = ShippingCharge::where('status', 1)->get();

        return view('backEnd.order.create', compact('products', 'cartinfo', 'shippingcharge'));
    }

    public function customer_lookup(Request $request)
    {
        $phone = trim((string) $request->input('phone'));
        if ($phone === '') {
            return response()->json(['found' => false]);
        }

        $phoneBlock = app(PhoneBlockService::class)->getActiveBlockForPhone($phone);

        $customer = Customer::where('phone', $phone)
            ->select('id', 'name', 'phone', 'address')
            ->first();

        $shipping = Shipping::where('phone', $phone)
            ->latest('id')
            ->select('id', 'name', 'phone', 'address', 'area')
            ->first();

        $name = $shipping?->name ?? $customer?->name;
        $address = $shipping?->address ?? $customer?->address;
        $areaName = $shipping?->area;

        $areaId = null;
        if ($areaName) {
            $shippingCharge = ShippingCharge::where('name', $areaName)
                ->select('id', 'name')
                ->first();
            $areaId = $shippingCharge?->id;
        }

        return response()->json([
            'found' => (bool) ($customer || $shipping),
            'customer_id' => $customer?->id,
            'name' => $name,
            'address' => $address,
            'area_id' => $areaId,
            'area_name' => $areaName,
            'is_phone_blocked' => (bool) $phoneBlock,
            'phone_block_reason' => $phoneBlock?->reason,
            'phone_blocked_at' => optional($phoneBlock?->blocked_at)->toDateTimeString(),
        ]);
    }

    public function order_store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:1000',
            'area' => 'required|integer|exists:shipping_charges,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $phoneBlock = app(PhoneBlockService::class)->getActiveBlockForPhone($validated['phone']);
        if ($phoneBlock) {
            Toastr::error('This phone number is blocked for new orders due to repeated cancellations.', 'Blocked');

            return redirect()->back()->withInput();
        }

        if (Cart::instance('pos_shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');

            return redirect()->back();
        }

        try {
            DB::transaction(function () use ($validated) {
                $shippingfee = ShippingCharge::findOrFail((int) $validated['area']);
                $cartItems = Cart::instance('pos_shopping')->content();
                $pricing = $this->buildPosPricingPayload($cartItems, $shippingfee);

                $customer_id = $this->resolveOrCreateCustomer(
                    $validated['name'],
                    $validated['phone']
                );

                $order = new Order;
                $order->invoice_id = Order::generateInvoiceId();
                $order->amount = Money::toMajorInt((int) $pricing['final_minor']);
                $order->amount_minor = (int) $pricing['final_minor'];
                $order->discount = Money::toMajorInt((int) $pricing['discount_minor']);
                $order->discount_minor = (int) $pricing['discount_minor'];
                $order->shipping_charge = Money::toMajorInt((int) $pricing['shipping_minor']);
                $order->shipping_charge_minor = (int) $pricing['shipping_minor'];
                $order->currency = (string) $pricing['currency'];
                $order->order_public_token = (string) Str::uuid();
                $order->customer_id = $customer_id;
                $order->warehouse_id = (int) $pricing['order_warehouse_id'];
                $order->order_status = 1;
                $order->note = $validated['note'] ?? null;
                $order->save();

                // shipping data save
                $shipping = new Shipping;
                $shipping->order_id = $order->id;
                $shipping->customer_id = $customer_id;
                $shipping->name = $validated['name'];
                $shipping->phone = $validated['phone'];
                $shipping->address = $validated['address'];
                $shipping->area = $shippingfee->name;
                $shipping->save();

                // payment data save
                $payment = new Payment;
                $payment->order_id = $order->id;
                $payment->customer_id = $customer_id;
                $payment->payment_method = 'Cash On Delivery';
                $payment->gateway = 'cod';
                $payment->amount = Money::toMajorInt((int) $pricing['final_minor']);
                $payment->amount_minor = (int) $pricing['final_minor'];
                $payment->currency = (string) $pricing['currency'];
                $payment->payment_status = 'pending';
                $payment->save();

                foreach ($pricing['lines'] as $line) {
                    $order_details = new OrderDetails;
                    $order_details->order_id = $order->id;
                    $order_details->product_id = (int) $line['product_id'];
                    $order_details->warehouse_id = (int) $line['warehouse_id'];
                    $order_details->product_name = (string) $line['product_name'];
                    $order_details->purchase_price = Money::toMajorInt((int) $line['purchase_price_minor']);
                    $order_details->purchase_price_minor = (int) $line['purchase_price_minor'];
                    $order_details->product_discount = Money::toMajorInt((int) $line['discount_minor']);
                    $order_details->product_color = $line['product_color'];
                    $order_details->product_size = $line['product_size'];
                    $order_details->sale_price = Money::toMajorInt((int) $line['unit_price_minor']);
                    $order_details->sale_price_minor = (int) $line['unit_price_minor'];
                    $order_details->currency = (string) $pricing['currency'];
                    $order_details->qty = (int) $line['qty'];
                    $order_details->save();
                }

                $this->reservePosOrderStock((int) $order->id, $pricing['lines']);
                app(\App\Services\LegacyAccountingPostingService::class)->postSale($order->fresh());
            });
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Invalid order data.';
            Toastr::error((string) $message, 'Stock Error!');

            return redirect()->back()->withInput();
        } catch (Throwable $e) {
            report($e);
            Toastr::error('Failed to place order. Please verify data and try again.', 'Failed!');

            return redirect()->back()->withInput();
        }

        Cart::instance('pos_shopping')->destroy();
        Session::forget('pos_shipping');
        Session::forget('pos_discount');
        Session::forget('product_discount');
        Toastr::success('Thanks, Your order place successfully', 'Success!');

        return redirect('admin/order/pending');
    }

    public function cart_add(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:products,id',
        ]);

        // products.stock was dropped; keep selecting only real columns.
        $product = Product::select('id', 'name', 'new_price', 'old_price', 'purchase_price', 'slug')
            ->where(['id' => (int) $validated['id'], 'status' => 1])
            ->with('image')
            ->first();
        if (! $product) {
            return response()->json(['status' => 'failed', 'message' => 'Product not found.'], 404);
        }

        $qty = 1;
        $cartinfo = Cart::instance('pos_shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => $qty,
            'price' => $product->new_price,
            'options' => [
                'slug' => $product->slug,
                'image' => $product->image->image ?? '',
                'old_price' => $product->old_price,
                'purchase_price' => $product->purchase_price,
                'product_discount' => 0,
            ],
        ]);

        return response()->json(compact('cartinfo'));
    }

    public function cart_content()
    {
        $cartinfo = Cart::instance('pos_shopping')->content();

        return view('backEnd.order.cart_content', compact('cartinfo'));
    }

    public function cart_details()
    {
        $cartinfo = Cart::instance('pos_shopping')->content();
        $discount = 0;
        foreach ($cartinfo as $cart) {
            $discount += $cart->options->product_discount * $cart->qty;
        }
        Session::put('product_discount', $discount);

        return view('backEnd.order.cart_details', compact('cartinfo'));
    }

    public function cart_increment(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'qty' => 'required|numeric|min:1',
        ]);

        $qty = (float) $validated['qty'] + 1;
        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $validated['id'])->first();
        if (! $cart) {
            return response()->json(['status' => 'failed', 'message' => 'Cart item not found.'], 404);
        }

        $cartinfo = Cart::instance('pos_shopping')->update($validated['id'], [
            'qty' => $qty,
            'options' => [
                'slug' => $cart->options->slug,
                'image' => $cart->options->image,
                'old_price' => $cart->options->old_price,
                'purchase_price' => $cart->options->purchase_price,
                'product_discount' => $cart->options->product_discount,
                'product_size' => $cart->options->product_size,
                'product_color' => $cart->options->product_color,
            ],
        ]);

        return response()->json($cartinfo);
    }

    public function cart_decrement(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'qty' => 'required|numeric|min:1',
        ]);

        $qty = max(1, (float) $validated['qty'] - 1);
        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $validated['id'])->first();
        if (! $cart) {
            return response()->json(['status' => 'failed', 'message' => 'Cart item not found.'], 404);
        }

        $cartinfo = Cart::instance('pos_shopping')->update($validated['id'], [
            'qty' => $qty,
            'options' => [
                'slug' => $cart->options->slug,
                'image' => $cart->options->image,
                'old_price' => $cart->options->old_price,
                'purchase_price' => $cart->options->purchase_price,
                'product_discount' => $cart->options->product_discount,
                'product_size' => $cart->options->product_size,
                'product_color' => $cart->options->product_color,
            ],
        ]);

        return response()->json($cartinfo);
    }

    public function cart_remove(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
        ]);

        Cart::instance('pos_shopping')->remove($validated['id']);
        $cartinfo = Cart::instance('pos_shopping')->content();

        return response()->json($cartinfo);
    }

    public function product_discount(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'discount' => 'required|numeric|min:0|max:999999',
        ]);

        $discount = (float) $validated['discount'];
        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $validated['id'])->first();
        if (! $cart) {
            return response()->json(['status' => 'failed', 'message' => 'Cart item not found.'], 404);
        }

        $cartinfo = Cart::instance('pos_shopping')->update($validated['id'], [
            'options' => [
                'slug' => $cart->options->slug,
                'image' => $cart->options->image,
                'old_price' => $cart->options->old_price,
                'purchase_price' => $cart->options->purchase_price,
                'product_discount' => $discount,
                'product_size' => $cart->options->product_size,
                'product_color' => $cart->options->product_color,
            ],
        ]);

        return response()->json($cartinfo);
    }

    public function cart_update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'product_size' => 'nullable|string|max:100',
            'product_color' => 'nullable|string|max:100',
        ]);

        // Get the row ID of the cart item
        $rowId = $validated['id'];
        // Fetch the current cart item using the row ID
        $cartItem = Cart::instance('pos_shopping')->content()->where('rowId', $rowId)->first();
        if ($cartItem) {
            // Update the options for the cart item
            Cart::instance('pos_shopping')->update($rowId, [
                'options' => [
                    'product_size' => ($validated['product_size'] ?? null) ?: $cartItem->options->product_size, // Use new size or keep existing
                    'product_color' => ($validated['product_color'] ?? null) ?: $cartItem->options->product_color, // Use new color or keep existing
                    'slug' => $cartItem->options->slug,
                    'image' => $cartItem->options->image,
                    'old_price' => $cartItem->options->old_price,
                    'purchase_price' => $cartItem->options->purchase_price,
                    'product_discount' => $cartItem->options->product_discount,
                ],
            ]);
        }

        return response()->json($cartItem);
    }

    public function cart_shipping(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:shipping_charges,id',
        ]);
        $shipping = ShippingCharge::where(['status' => 1, 'id' => (int) $validated['id']])->firstOrFail()->amount;
        Session::put('pos_shipping', $shipping);

        return response()->json($shipping);
    }

    public function cart_clear(Request $request)
    {
        $cartinfo = Cart::instance('pos_shopping')->destroy();
        Session::forget('pos_shipping');
        Session::forget('pos_discount');
        Session::forget('product_discount');

        return redirect()->back();
    }

    public function order_edit($invoice_id)
    {
        $products = Product::select('id', 'name', 'new_price', 'product_code')->where(['status' => 1])->get();
        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $order = Order::where('invoice_id', $invoice_id)->firstOrFail();
        $cartinfo = Cart::instance('pos_shopping')->destroy();
        $shippinginfo = Shipping::where('order_id', $order->id)->first();
        Session::put('product_discount', $order->discount);
        Session::put('pos_shipping', $order->shipping_charge);
        $orderdetails = OrderDetails::where('order_id', $order->id)->with('image')->get();
        foreach ($orderdetails as $ordetails) {
            $cartinfo = Cart::instance('pos_shopping')->add([
                'id' => $ordetails->product_id,
                'name' => $ordetails->product_name,
                'qty' => $ordetails->qty,
                'price' => $ordetails->sale_price,
                'options' => [
                    'image' => optional($ordetails->image)->image,
                    'purchase_price' => $ordetails->purchase_price,
                    'product_discount' => $ordetails->product_discount,
                    'details_id' => $ordetails->id,
                    'product_size' => $ordetails->product_size,
                    'product_color' => $ordetails->product_color,
                ],
            ]);
        }
        $cartinfo = Cart::instance('pos_shopping')->content();

        return view('backEnd.order.edit', compact('products', 'cartinfo', 'shippingcharge', 'shippinginfo', 'order'));
    }

    public function order_update(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:1000',
            'area' => 'required|integer|exists:shipping_charges,id',
            'note' => 'nullable|string|max:1000',
        ]);

        if (Cart::instance('pos_shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');

            return redirect()->back();
        }

        try {
            DB::transaction(function () use ($validated) {
                $shippingfee = ShippingCharge::findOrFail((int) $validated['area']);
                $cartItems = Cart::instance('pos_shopping')->content();
                $pricing = $this->buildPosPricingPayload($cartItems, $shippingfee);

                $customer_id = $this->resolveOrCreateCustomer(
                    $validated['name'],
                    $validated['phone']
                );

                // order data save
                $order = Order::where('id', (int) $validated['order_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $existingOrderDetails = OrderDetails::where('order_id', $order->id)
                    ->lockForUpdate()
                    ->get();
                $this->releasePosOrderStockReservations((int) $order->id, $existingOrderDetails);

                $order->amount = Money::toMajorInt((int) $pricing['final_minor']);
                $order->amount_minor = (int) $pricing['final_minor'];
                $order->discount = Money::toMajorInt((int) $pricing['discount_minor']);
                $order->discount_minor = (int) $pricing['discount_minor'];
                $order->shipping_charge = Money::toMajorInt((int) $pricing['shipping_minor']);
                $order->shipping_charge_minor = (int) $pricing['shipping_minor'];
                $order->currency = (string) $pricing['currency'];
                $order->customer_id = $customer_id;
                $order->warehouse_id = (int) $pricing['order_warehouse_id'];
                $order->note = $validated['note'] ?? null;
                $order->save();

                // shipping data save
                $shipping = Shipping::where('order_id', (int) $validated['order_id'])->first();
                if (! $shipping) {
                    $shipping = new Shipping;
                }
                $shipping->order_id = $order->id;
                $shipping->customer_id = $customer_id;
                $shipping->name = $validated['name'];
                $shipping->phone = $validated['phone'];
                $shipping->address = $validated['address'];
                $shipping->area = $shippingfee->name;
                $shipping->save();

                // payment data save
                $payment = Payment::where('order_id', (int) $validated['order_id'])->first();
                if (! $payment) {
                    $payment = new Payment;
                }
                $payment->order_id = $order->id;
                $payment->customer_id = $customer_id;
                $payment->payment_method = 'Cash On Delivery';
                $payment->gateway = 'cod';
                $payment->amount = Money::toMajorInt((int) $pricing['final_minor']);
                $payment->amount_minor = (int) $pricing['final_minor'];
                $payment->currency = (string) $pricing['currency'];
                $payment->payment_status = 'pending';
                $payment->save();

                OrderDetails::where('order_id', $order->id)->delete();

                foreach ($pricing['lines'] as $line) {
                    $order_details = new OrderDetails;
                    $order_details->order_id = $order->id;
                    $order_details->product_id = (int) $line['product_id'];
                    $order_details->warehouse_id = (int) $line['warehouse_id'];
                    $order_details->product_name = (string) $line['product_name'];
                    $order_details->purchase_price = Money::toMajorInt((int) $line['purchase_price_minor']);
                    $order_details->purchase_price_minor = (int) $line['purchase_price_minor'];
                    $order_details->product_discount = Money::toMajorInt((int) $line['discount_minor']);
                    $order_details->product_color = $line['product_color'];
                    $order_details->product_size = $line['product_size'];
                    $order_details->sale_price = Money::toMajorInt((int) $line['unit_price_minor']);
                    $order_details->sale_price_minor = (int) $line['unit_price_minor'];
                    $order_details->currency = (string) $pricing['currency'];
                    $order_details->qty = (int) $line['qty'];
                    $order_details->save();
                }

                $this->reservePosOrderStock((int) $order->id, $pricing['lines']);
            });
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Invalid order update data.';
            Toastr::error((string) $message, 'Stock Error!');

            return redirect()->back()->withInput();
        } catch (Throwable $e) {
            report($e);
            Toastr::error('Failed to update order. Please verify data and try again.', 'Failed!');

            return redirect()->back()->withInput();
        }

        Cart::instance('pos_shopping')->destroy();
        Session::forget('pos_shipping');
        Session::forget('pos_discount');
        Session::forget('product_discount');
        Toastr::success('Thanks, Your order place successfully', 'Success!');

        $order = Order::query()->findOrFail((int) $validated['order_id']);
        $currentStatus = OrderStatus::query()
            ->select(['id', 'slug'])
            ->find((int) $order->order_status);

        $link = $currentStatus?->slug ?? 'pending';

        return redirect('admin/order/'.$link);
    }

    protected function resolvePeriodDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        if (! empty($startDate) || ! empty($endDate)) {
            return [$startDate, $endDate];
        }

        return match (strtolower($period)) {
            'daily' => [now()->toDateString(), now()->toDateString()],
            'monthly' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'yearly' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default => [null, null],
        };
    }

    protected function guardReportDateRange(string $startDate, string $endDate): void
    {
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        if ($days > self::MAX_REPORT_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => 'Date range is too large. Maximum allowed range is '.self::MAX_REPORT_RANGE_DAYS.' days.',
            ]);
        }
    }

    protected function resolveOrCreateCustomer(string $name, string $phone): int
    {
        $existingCustomer = Customer::where('phone', $phone)
            ->select('id')
            ->first();
        if ($existingCustomer) {
            return (int) $existingCustomer->id;
        }

        $password = random_int(111111, 999999);
        $store = new Customer;
        $store->name = $name;
        $store->slug = $name;
        $store->phone = $phone;
        $store->password = bcrypt($password);
        $store->verify = 1;
        $store->status = 'active';
        $store->save();

        return (int) $store->id;
    }

    /**
     * @param  \Illuminate\Support\Collection<int,mixed>  $cartItems
     * @return array{
     *   currency:string,
     *   subtotal_minor:int,
     *   shipping_minor:int,
     *   discount_minor:int,
     *   final_minor:int,
     *   order_warehouse_id:int,
     *   lines:array<int,array{
     *     product_id:int,
     *     product_name:string,
     *     warehouse_id:int,
     *     qty:int,
     *     unit_price_minor:int,
     *     purchase_price_minor:int,
     *     discount_minor:int,
     *     product_color:mixed,
     *     product_size:mixed
     *   }>
     * }
     */
    protected function buildPosPricingPayload($cartItems, ShippingCharge $shippingfee): array
    {
        $lines = [];
        $subtotalMinor = 0;
        $firstWarehouseId = null;

        foreach ($cartItems as $cart) {
            $productId = (int) $cart->id;
            $qty = max(1, (int) $cart->qty);
            $unitMinor = Money::fromMajor((float) $cart->price);
            $purchaseMinor = Money::fromMajor((float) ($cart->options->purchase_price ?? 0));
            $perUnitDiscountMinor = Money::fromMajor((float) ($cart->options->product_discount ?? 0));

            if ($unitMinor < 0) {
                throw ValidationException::withMessages([
                    'cart' => "Invalid unit price for product #{$productId}.",
                ]);
            }

            $warehouseId = $this->resolvePosWarehouseId(
                $productId,
                $qty,
                isset($cart->options->warehouse_id) ? (int) $cart->options->warehouse_id : null
            );

            if ($firstWarehouseId === null) {
                $firstWarehouseId = $warehouseId;
            }

            $subtotalMinor += $unitMinor * $qty;

            $lines[] = [
                'product_id' => $productId,
                'product_name' => (string) $cart->name,
                'warehouse_id' => $warehouseId,
                'qty' => $qty,
                'unit_price_minor' => $unitMinor,
                'purchase_price_minor' => $purchaseMinor,
                'discount_minor' => max(0, $perUnitDiscountMinor * $qty),
                'product_color' => $cart->options->product_color ?? null,
                'product_size' => $cart->options->product_size ?? null,
            ];
        }

        $sessionDiscountMinor = Money::fromMajor((float) Session::get('pos_discount', 0));
        $productDiscountMinor = Money::fromMajor((float) Session::get('product_discount', 0));
        $lineDiscountMinor = array_sum(array_column($lines, 'discount_minor'));
        $discountMinor = max($lineDiscountMinor, $sessionDiscountMinor + $productDiscountMinor);

        $shippingMinor = (int) ($shippingfee->amount_minor ?? 0);
        if ($shippingMinor <= 0) {
            $shippingMinor = Money::fromMajor((float) $shippingfee->amount);
        }

        return [
            'currency' => 'BDT',
            'subtotal_minor' => $subtotalMinor,
            'shipping_minor' => $shippingMinor,
            'discount_minor' => $discountMinor,
            'final_minor' => Money::clampNonNegative($subtotalMinor + $shippingMinor - $discountMinor),
            'order_warehouse_id' => (int) ($firstWarehouseId ?? 0),
            'lines' => $lines,
        ];
    }

    protected function resolvePosWarehouseId(int $productId, int $qty, ?int $preferredWarehouseId = null): int
    {
        if ($preferredWarehouseId !== null && $preferredWarehouseId > 0) {
            $preferredStock = WarehouseStock::query()
                ->where('warehouse_id', $preferredWarehouseId)
                ->where('product_id', $productId)
                ->selectRaw('CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END AS sellable_stock')
                ->value('sellable_stock');

            if ((float) ($preferredStock ?? 0) >= $qty) {
                return $preferredWarehouseId;
            }
        }

        $candidate = WarehouseStock::query()
            ->where('product_id', $productId)
            ->whereRaw('(physical_quantity - reserved_quantity) >= ?', [$qty])
            ->select('warehouse_id')
            ->orderByRaw('(physical_quantity - reserved_quantity) DESC')
            ->first();

        if ($candidate) {
            return (int) $candidate->warehouse_id;
        }

        throw ValidationException::withMessages([
            'stock' => "Insufficient stock for product #{$productId}.",
        ]);
    }

    /**
     * @param  array<int,array{product_id:int,warehouse_id:int,qty:int}>  $lines
     */
    protected function reservePosOrderStock(int $orderId, array $lines): void
    {
        foreach ($lines as $line) {
            $this->warehouseStockService->reserveStock(
                (int) $line['warehouse_id'],
                (int) $line['product_id'],
                (float) $line['qty'],
                $orderId,
                'order',
                "Reserved stock for POS order #{$orderId}"
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int,\App\Models\OrderDetails>  $orderDetails
     */
    protected function releasePosOrderStockReservations(int $orderId, $orderDetails): void
    {
        foreach ($orderDetails as $detail) {
            $warehouseId = (int) ($detail->warehouse_id ?? 0);
            if ($warehouseId <= 0) {
                continue;
            }

            try {
                $this->warehouseStockService->releaseReservedStock(
                    $warehouseId,
                    (int) $detail->product_id,
                    (float) $detail->qty,
                    $orderId,
                    'order',
                    "Released reservation before POS update for order #{$orderId}"
                );
            } catch (Throwable $e) {
                Log::warning('Unable to release existing POS order reservation before update', [
                    'order_id' => $orderId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => (int) $detail->product_id,
                    'qty' => (float) $detail->qty,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
