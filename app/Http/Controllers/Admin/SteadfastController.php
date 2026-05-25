<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courierapi;
use App\Models\Order;
use App\Services\SteadfastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Toastr;

class SteadfastController extends Controller
{
    protected SteadfastService $steadfast;

    public function __construct(SteadfastService $steadfast)
    {
        $this->steadfast = $steadfast;
        $this->middleware('permission:payment-config-update');
    }

    /**
     * Dashboard: balance + recent orders with tracking data + quick status checker.
     */
    public function dashboard()
    {
        $configured = $this->steadfast->isConfigured();
        $balance = null;

        if ($configured) {
            try {
                $balanceResponse = $this->steadfast->getBalance();
                $balance = $balanceResponse['current_balance'] ?? null;
            } catch (Throwable $e) {
                Log::warning('Steadfast balance fetch failed', ['message' => $e->getMessage()]);
            }
        }

        $recentOrders = Order::query()
            ->whereNotNull('steadfast_tracking_code')
            ->with('shipping')
            ->latest()
            ->take(20)
            ->get();

        return view('backEnd.steadfast.dashboard', compact('configured', 'balance', 'recentOrders'));
    }

    /**
     * Check delivery status by consignment ID / invoice / tracking code.
     */
    public function checkStatus(Request $request)
    {
        $configured = $this->steadfast->isConfigured();
        $result = null;
        $searchType = $request->input('search_type');
        $searchValue = trim((string) $request->input('search_value'));

        if ($request->isMethod('post') && $configured && $searchValue !== '') {
            try {
                $result = match ($searchType) {
                    'consignment_id' => $this->steadfast->statusByConsignmentId((int) $searchValue),
                    'invoice' => $this->steadfast->statusByInvoice($searchValue),
                    'tracking_code' => $this->steadfast->statusByTrackingCode($searchValue),
                    default => ['status' => 422, 'message' => 'Invalid search type'],
                };
            } catch (Throwable $e) {
                $result = ['status' => 500, 'error' => $e->getMessage()];
            }
        }

        return view('backEnd.steadfast.dashboard', [
            'configured' => $configured,
            'balance' => null,
            'recentOrders' => collect(),
            'result' => $result,
            'searchType' => $searchType,
            'searchValue' => $searchValue,
        ]);
    }

    /**
     * List all return requests & create new ones.
     */
    public function returnRequests(Request $request)
    {
        $configured = $this->steadfast->isConfigured();
        $returnRequests = [];
        $createResult = null;

        if ($request->isMethod('post') && $configured) {
            $request->validate([
                'identifier_type' => 'required|in:consignment_id,invoice,tracking_code',
                'identifier_value' => 'required|string|max:255',
                'reason' => 'nullable|string|max:500',
            ]);

            $data = [
                $request->input('identifier_type') => $request->input('identifier_value'),
            ];
            if ($request->filled('reason')) {
                $data['reason'] = $request->input('reason');
            }

            try {
                $createResult = $this->steadfast->createReturnRequest($data);
                if (isset($createResult['id'])) {
                    Toastr::success('Success', 'Return request created successfully.');
                }
            } catch (Throwable $e) {
                $createResult = ['error' => $e->getMessage()];
            }
        }

        if ($configured) {
            try {
                $response = $this->steadfast->getReturnRequests();
                $returnRequests = $response['data'] ?? $response;
                if (! is_array($returnRequests)) {
                    $returnRequests = [];
                }
            } catch (Throwable $e) {
                Log::warning('Steadfast return requests fetch failed', ['message' => $e->getMessage()]);
            }
        }

        return view('backEnd.steadfast.return_requests', compact('configured', 'returnRequests', 'createResult'));
    }

    /**
     * List all payments from Steadfast.
     */
    public function payments()
    {
        $configured = $this->steadfast->isConfigured();
        $payments = [];

        if ($configured) {
            try {
                $response = $this->steadfast->getPayments();
                $payments = $response['data'] ?? $response;
                if (! is_array($payments)) {
                    $payments = [];
                }
            } catch (Throwable $e) {
                Log::warning('Steadfast payments fetch failed', ['message' => $e->getMessage()]);
            }
        }

        return view('backEnd.steadfast.payments', compact('configured', 'payments'));
    }

    /**
     * View single payment with its consignments.
     */
    public function payment(int $id)
    {
        $configured = $this->steadfast->isConfigured();
        $payment = [];

        if ($configured) {
            try {
                $payment = $this->steadfast->getPayment($id);
            } catch (Throwable $e) {
                Log::warning('Steadfast single payment fetch failed', ['id' => $id, 'message' => $e->getMessage()]);
            }
        }

        return view('backEnd.steadfast.payment_detail', compact('configured', 'payment'));
    }

    /**
     * List police stations.
     */
    public function policeStations()
    {
        $configured = $this->steadfast->isConfigured();
        $stations = [];

        if ($configured) {
            try {
                $response = $this->steadfast->getPoliceStations();
                $stations = $response['data'] ?? $response;
                if (! is_array($stations)) {
                    $stations = [];
                }
            } catch (Throwable $e) {
                Log::warning('Steadfast police stations fetch failed', ['message' => $e->getMessage()]);
            }
        }

        return view('backEnd.steadfast.police_stations', compact('configured', 'stations'));
    }

    /**
     * AJAX: Sync a single order's Steadfast delivery status.
     */
    public function syncOrderStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->input('order_id'));

        if (! $this->steadfast->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Steadfast API not configured.']);
        }

        $result = null;

        // Try by consignment ID first, then invoice, then tracking code
        if ($order->steadfast_consignment_id) {
            $result = $this->steadfast->statusByConsignmentId((int) $order->steadfast_consignment_id);
        } elseif ($order->invoice_id) {
            $result = $this->steadfast->statusByInvoice($order->invoice_id);
        } elseif ($order->steadfast_tracking_code) {
            $result = $this->steadfast->statusByTrackingCode($order->steadfast_tracking_code);
        }

        if ($result && isset($result['delivery_status'])) {
            $order->steadfast_status = $result['delivery_status'];
            $order->save();

            return response()->json([
                'success' => true,
                'delivery_status' => $result['delivery_status'],
                'message' => 'Status synced: ' . $result['delivery_status'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Could not retrieve delivery status.',
            'raw' => $result,
        ]);
    }
}
