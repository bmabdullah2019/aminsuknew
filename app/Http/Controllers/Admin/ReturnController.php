<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnLog;
use App\Models\ReturnOrder;
use App\Models\ReturnReason;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReturnService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReturnController extends Controller
{
    private const MAX_REPORT_RANGE_DAYS = 730;

    public function __construct(protected ReturnService $returnService)
    {
        $this->middleware('permission:return-management-view', ['only' => ['dashboard', 'index', 'show']]);
        $this->middleware('permission:return-management-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:return-order-search', ['only' => ['searchOrders']]);
        $this->middleware('permission:return-management-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:return-management-approve', ['only' => ['approve', 'reject']]);
        $this->middleware('permission:return-management-bulk-approve', ['only' => ['bulkAction']]);
        $this->middleware('permission:return-management-process', ['only' => ['process']]);
        $this->middleware('permission:return-management-complete', ['only' => ['complete']]);
        $this->middleware('permission:return-management-cancel', ['only' => ['cancel']]);
        $this->middleware('permission:return-analytics-view', ['only' => ['analytics']]);
        $this->middleware('permission:return-management-export', ['only' => ['export']]);
    }

    /**
     * Display returns dashboard
     */
    public function dashboard(Request $request)
    {
        $dateFilters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $dateFilters['start_date'] ?? now()->subDays(30)->toDateString();
        $endDate = $dateFilters['end_date'] ?? now()->toDateString();
        $this->guardDateRangeWindow($startDate, $endDate);

        $rangeStart = Carbon::parse($startDate)->startOfDay();
        $rangeEnd = Carbon::parse($endDate)->endOfDay();

        // Get return statistics
        $stats = $this->returnService->getReturnStatistics([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        // Recent returns
        $recentReturns = ReturnOrder::with(['customer', 'returnReason'])
            ->latest()
            ->take(10)
            ->get();

        // Pending approvals
        $pendingReturns = ReturnOrder::pending()
            ->with(['customer', 'returnReason'])
            ->latest()
            ->take(10)
            ->get();

        // Returns by status
        $returnsByStatus = ReturnOrder::select('return_status', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->groupBy('return_status')
            ->get()
            ->pluck('count', 'return_status');

        // Returns by reason
        $returnsByReason = ReturnOrder::with('returnReason')
            ->select('return_reason_id', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->groupBy('return_reason_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $reasonName = optional($item->returnReason)->reason_name ?? 'Unknown';

                return [$reasonName => $item->count];
            });

        return view('backEnd.returns.dashboard', compact(
            'stats',
            'recentReturns',
            'pendingReturns',
            'returnsByStatus',
            'returnsByReason',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display all returns with filtering
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,pending,approved,processing,completed,cancelled,rejected',
            'return_source' => 'nullable|in:customer,warehouse,supplier,qc',
            'return_reason_id' => [
                'nullable',
                Rule::exists('return_reasons', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $this->guardDateRangeWindow($filters['start_date'], $filters['end_date']);
        }

        $query = ReturnOrder::with(['customer', 'returnReason', 'order', 'creator']);
        $this->applyReturnFilters($query, $filters);

        $returns = $query->latest()->paginate(25)->appends($request->query());

        $returnReasons = ReturnReason::active()->orderBy('reason_category')->get();

        return view('backEnd.returns.index', compact('returns', 'returnReasons'));
    }

    /**
     * Show return creation form
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|integer|exists:orders,id',
        ]);

        $order = null;
        $orderDetails = collect();

        if (! empty($validated['order_id'])) {
            $order = Order::with(['customer', 'orderdetails.product'])->find($validated['order_id']);

            if ($order) {
                try {
                    $this->returnService->validateOrderForReturn($order);
                    $orderDetails = $order->orderdetails->filter(function ($detail) {
                        return $this->isOrderDetailReturnable($detail);
                    });
                } catch (\InvalidArgumentException $e) {
                    return redirect()->route('admin.returns.index')->with('error', $e->getMessage());
                }
            }
        }

        $returnReasons = ReturnReason::active()->orderBySort()->get();
        $warehouses = Warehouse::active()->get();

        return view('backEnd.returns.create', compact('order', 'orderDetails', 'returnReasons', 'warehouses'));
    }

    /**
     * Search for orders eligible for return
     */
    public function searchOrders(Request $request)
    {
        $validated = $request->validate([
            'search' => 'required|string|min:3|max:255',
        ]);

        $orders = Order::with(['customer', 'orderdetails'])
            ->where('order_status', 5) // Delivered orders
            ->where(function ($query) use ($validated) {
                $query->where('invoice_id', 'like', "%{$validated['search']}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($validated) {
                        $customerQuery->where('name', 'like', "%{$validated['search']}%")
                            ->orWhere('phone', 'like', "%{$validated['search']}%")
                            ->orWhere('email', 'like', "%{$validated['search']}%");
                    });
            })
            ->whereHas('orderdetails', function ($detailsQuery) {
                $detailsQuery->whereColumn('qty', '>', 'returned_quantity')
                    ->where(function ($eligibilityQuery) {
                        $eligibilityQuery->whereNull('return_eligible')
                            ->orWhere('return_eligible', true);
                    })
                    ->where(function ($deadlineQuery) {
                        $deadlineQuery->whereNull('return_deadline')
                            ->orWhereDate('return_deadline', '>=', now()->toDateString());
                    });
            })
            ->whereDoesntHave('returnOrders', function ($returnQuery) {
                $returnQuery->where('return_type', 'full')
                    ->whereNotIn('return_status', ['rejected', 'cancelled']);
            })
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'orders' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                    'customer_name' => optional($order->customer)->name,
                    'customer_phone' => optional($order->customer)->phone,
                    'order_date_formatted' => $order->created_at->format('M d, Y'),
                    'grand_total_formatted' => number_format((float) $order->amount, 2),
                    'total_items' => $order->orderdetails->count(),
                    'returnable_items' => $order->orderdetails->filter(function ($detail) {
                        return $this->isOrderDetailReturnable($detail);
                    })->count(),
                ];
            }),
        ]);
    }

    /**
     * Store a new return order
     */
    public function store(Request $request)
    {
        $request->merge([
            'return_items' => $this->normalizeReturnItems($request),
        ]);

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'return_type' => 'required|in:full,partial',
            'return_reason_id' => [
                'required',
                Rule::exists('return_reasons', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'return_source' => 'required|in:customer,warehouse,supplier,qc',
            'refund_method' => 'nullable|in:cash,bank,credit,voucher,none',
            'restock_flag' => 'boolean',
            'damage_flag' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'return_items' => 'required|array|min:1',
            'return_items.*.order_detail_id' => 'required|distinct|exists:order_details,id',
            'return_items.*.return_quantity' => 'required|numeric|min:0.01',
            'return_items.*.warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'return_items.*.return_condition' => 'required|in:new,opened,damaged,defective,expired',
            'return_items.*.notes' => 'nullable|string|max:500',
        ]);

        $validated['restock_flag'] = $request->boolean('restock_flag', true);
        $validated['damage_flag'] = $request->boolean('damage_flag', false);

        try {
            $returnOrder = $this->returnService->createReturnOrder($validated, $this->requireAuthenticatedUser());

            return redirect()->route('admin.returns.show', $returnOrder)
                ->with('success', 'Return order created successfully');

        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create return. Please verify input and try again.');
        }
    }

    /**
     * Display return order details
     */
    public function show(ReturnOrder $return)
    {
        $return->load([
            'customer',
            'order.orderdetails.product',
            'returnReason',
            'returnItems.product',
            'returnItems.warehouse',
            'returnItems.replacementOrder',
            'creator',
            'approver',
            'processor',
            'returnLogs.performer',
        ]);

        // Get audit timeline
        $timeline = ReturnLog::getActionTimeline($return->id);

        return view('backEnd.returns.show', compact('return', 'timeline'));
    }

    /**
     * Show return editing form
     */
    public function edit(ReturnOrder $return)
    {
        if (! in_array($return->return_status, ['draft', 'pending'])) {
            return redirect()->route('admin.returns.show', $return)
                ->with('error', 'Cannot edit return in current status');
        }

        $return->load(['order.orderdetails.product', 'returnItems']);
        $returnReasons = ReturnReason::active()->orderBySort()->get();
        $warehouses = Warehouse::active()->get();

        return view('backEnd.returns.edit', compact('return', 'returnReasons', 'warehouses'));
    }

    /**
     * Update return order
     */
    public function update(Request $request, ReturnOrder $return)
    {
        if (! in_array($return->return_status, ['draft', 'pending'])) {
            return redirect()->route('admin.returns.show', $return)
                ->with('error', 'Cannot update return in current status');
        }

        $validated = $request->validate([
            'return_reason_id' => [
                'required',
                Rule::exists('return_reasons', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'restock_flag' => 'boolean',
            'damage_flag' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['restock_flag'] = $request->boolean('restock_flag');
        $validated['damage_flag'] = $request->boolean('damage_flag');

        try {
            $this->returnService->updateReturnOrder($return, $validated);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update return. Please verify input and try again.');
        }

        return redirect()->route('admin.returns.show', $return)
            ->with('success', 'Return updated successfully');
    }

    /**
     * Approve return order
     */
    public function approve(Request $request, ReturnOrder $return)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->returnService->approveReturn($return, $this->requireAuthenticatedUser(), $request->notes);

            return redirect()->route('admin.returns.show', $return)
                ->with('success', 'Return approved successfully');

        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'Unable to approve this return.');
        }
    }

    /**
     * Reject return order
     */
    public function reject(Request $request, ReturnOrder $return)
    {
        $request->validate([
            'notes' => 'required|string|min:5|max:500',
        ]);

        try {
            $this->returnService->rejectReturn($return, $this->requireAuthenticatedUser(), $request->notes);

            return redirect()->route('admin.returns.show', $return)
                ->with('success', 'Return rejected successfully');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'Unable to reject this return.');
        }
    }

    /**
     * Process approved return
     */
    public function process(Request $request, ReturnOrder $return)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'refund_method' => 'nullable|in:cash,bank,credit,voucher,none',
        ]);

        try {
            $processingData = [
                'notes' => $request->notes,
                'refund_method' => $request->refund_method,
            ];

            $this->returnService->processReturn($return, $this->requireAuthenticatedUser(), $processingData);

            return redirect()->route('admin.returns.show', $return)
                ->with('success', 'Return processed successfully');

        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'Unable to process this return.');
        }
    }

    /**
     * Complete return order
     */
    public function complete(Request $request, ReturnOrder $return)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'refund_method' => 'nullable|in:cash,bank,credit,voucher,none',
        ]);

        try {
            $this->returnService->completeReturn($return, $this->requireAuthenticatedUser(), [
                'notes' => $request->notes,
                'refund_method' => $request->refund_method,
            ]);

            return redirect()->route('admin.returns.show', $return)
                ->with('success', 'Return completed successfully');

        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'Unable to complete this return.');
        }
    }

    /**
     * Cancel return order
     */
    public function cancel(Request $request, ReturnOrder $return)
    {
        $request->validate([
            'notes' => 'required|string|min:5|max:500',
        ]);

        try {
            $this->returnService->cancelReturn($return, $this->requireAuthenticatedUser(), $request->notes);

            return redirect()->route('admin.returns.show', $return)
                ->with('success', 'Return cancelled successfully');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'Unable to cancel this return.');
        }
    }

    /**
     * Display return analytics
     */
    public function analytics(Request $request)
    {
        $dateFilters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'start_date' => $dateFilters['start_date'] ?? now()->subDays(30)->toDateString(),
            'end_date' => $dateFilters['end_date'] ?? now()->toDateString(),
        ];
        $this->guardDateRangeWindow($filters['start_date'], $filters['end_date']);

        $rangeStart = Carbon::parse($filters['start_date'])->startOfDay();
        $rangeEnd = Carbon::parse($filters['end_date'])->endOfDay();

        $stats = $this->returnService->getReturnStatistics($filters);
        $productAnalysis = $this->returnService->getProductReturnAnalysis($filters);

        // Reason-wise analysis
        $reasonAnalysis = ReturnOrder::with('returnReason')
            ->select('return_reason_id', DB::raw('count(*) as count'), DB::raw('sum(total_return_value) as value'))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->groupBy('return_reason_id')
            ->get()
            ->map(function ($item) {
                $reason = $item->returnReason;

                return [
                    'reason' => $reason->reason_name ?? 'Unknown',
                    'count' => $item->count,
                    'value' => $item->value,
                    'category' => $reason->reason_category ?? 'unknown',
                ];
            });

        // Monthly trends
        $monthlyTrends = ReturnOrder::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('count(*) as count'),
            DB::raw('sum(total_return_value) as value')
        )
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('backEnd.returns.analytics', compact(
            'stats',
            'productAnalysis',
            'reasonAnalysis',
            'monthlyTrends',
            'filters'
        ));
    }

    /**
     * Export returns data
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'nullable|in:csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:draft,pending,approved,processing,completed,cancelled,rejected',
            'return_source' => 'nullable|in:customer,warehouse,supplier,qc',
            'return_reason_id' => [
                'nullable',
                Rule::exists('return_reasons', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
        ]);

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $this->guardDateRangeWindow($validated['start_date'], $validated['end_date']);
        }

        $query = ReturnOrder::query()
            ->with(['customer', 'returnReason', 'order'])
            ->orderBy('created_at');
        $this->applyReturnFilters($query, $validated);

        $filename = 'returns-export-'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Return Number',
                'Order Invoice',
                'Customer',
                'Return Status',
                'Return Reason',
                'Total Value',
                'Refund Amount',
                'Created Date',
            ]);

            // CSV data
            foreach ($query->cursor() as $return) {
                fputcsv($file, [
                    $return->return_number,
                    optional($return->order)->invoice_id ?? '',
                    optional($return->customer)->name ?? 'N/A',
                    $return->return_status,
                    optional($return->returnReason)->reason_name ?? 'Unknown',
                    $return->total_return_value,
                    $return->refund_amount,
                    $return->created_at->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk operations for returns
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,process,complete',
            'return_ids' => 'required|array|min:1',
            'return_ids.*' => 'distinct|exists:return_orders,id',
            'notes' => 'required_if:action,reject|nullable|string|max:500',
        ]);

        $returns = ReturnOrder::whereIn('id', $request->return_ids)->get();
        $successCount = 0;
        $errors = [];
        $actor = $this->requireAuthenticatedUser();

        foreach ($returns as $return) {
            try {
                switch ($request->action) {
                    case 'approve':
                        $this->returnService->approveReturn($return, $actor, $request->notes);
                        break;
                    case 'reject':
                        $this->returnService->rejectReturn($return, $actor, $request->notes);
                        break;
                    case 'process':
                        $this->returnService->processReturn($return, $actor, ['notes' => $request->notes]);
                        break;
                    case 'complete':
                        $this->returnService->completeReturn($return, $actor, ['notes' => $request->notes]);
                        break;
                }
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Return {$return->return_number}: {$e->getMessage()}";
            }
        }

        if ($successCount === 0 && ! empty($errors)) {
            $errorPreview = implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorPreview .= '; and '.(count($errors) - 5).' more errors';
            }

            return redirect()->back()->with('error', 'No returns were processed. '.$errorPreview);
        }

        $message = "Successfully processed {$successCount} returns";
        if (! empty($errors)) {
            $message .= '. Failed: '.count($errors);
        }

        return redirect()->back()->with('success', $message);
    }

    protected function applyReturnFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('return_number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('order', function ($orderQuery) use ($search) {
                        $orderQuery->where('invoice_id', 'like', '%'.$search.'%');
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('return_status', $filters['status']);
        }

        if (! empty($filters['return_source'])) {
            $query->where('return_source', $filters['return_source']);
        }

        if (! empty($filters['return_reason_id'])) {
            $query->where('return_reason_id', (int) $filters['return_reason_id']);
        }

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        } elseif ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
    }

    protected function isOrderDetailReturnable($detail): bool
    {
        $eligible = ! isset($detail->return_eligible) || (bool) $detail->return_eligible;
        $availableQty = (float) ($detail->qty ?? 0) - (float) ($detail->returned_quantity ?? 0);
        if (! $eligible || $availableQty <= 0) {
            return false;
        }

        if (empty($detail->return_deadline)) {
            return true;
        }

        try {
            return now()->lessThanOrEqualTo(Carbon::parse($detail->return_deadline)->endOfDay());
        } catch (\Throwable) {
            return false;
        }
    }

    protected function guardDateRangeWindow(string $startDate, string $endDate): void
    {
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        if ($days > self::MAX_REPORT_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => 'Date range is too large. Maximum allowed range is '.self::MAX_REPORT_RANGE_DAYS.' days.',
            ]);
        }
    }

    protected function requireAuthenticatedUser(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403, 'Authenticated user is required');
        }

        return $user;
    }

    protected function normalizeReturnItems(Request $request): array
    {
        $items = $request->input('return_items');
        if (is_array($items) && count($items) > 0) {
            return array_values($items);
        }

        $legacyItems = $request->input('items', []);
        if (! is_array($legacyItems)) {
            return [];
        }

        $normalizedItems = [];
        foreach ($legacyItems as $orderDetailId => $item) {
            if (! is_array($item)) {
                continue;
            }

            $selected = filter_var($item['selected'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (! $selected) {
                continue;
            }

            $normalizedItems[] = [
                'order_detail_id' => $item['order_detail_id'] ?? $orderDetailId,
                'return_quantity' => $item['return_quantity'] ?? null,
                'warehouse_id' => $item['warehouse_id'] ?? null,
                'return_condition' => $item['return_condition'] ?? 'new',
                'notes' => $item['notes'] ?? null,
            ];
        }

        return $normalizedItems;
    }
}
