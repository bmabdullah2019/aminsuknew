<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProfitLossEntry;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\ProfitLossService;
use App\Services\WarehouseStockService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProfitLossController extends Controller
{
    protected ProfitLossService $profitLossService;

    protected WarehouseStockService $warehouseStockService;

    private const MAX_REPORT_RANGE_DAYS = 730;

    public function __construct(ProfitLossService $profitLossService, WarehouseStockService $warehouseStockService)
    {
        $this->middleware('permission:profit-loss-dashboard', ['only' => ['dashboard']]);
        $this->middleware('permission:profit-loss-reports', ['only' => ['reports']]);
        $this->middleware('permission:profit-loss-trends', ['only' => ['trends']]);
        $this->middleware('permission:profit-loss-product-wise', ['only' => ['productWise']]);
        $this->middleware('permission:profit-loss-warehouse-wise', ['only' => ['warehouseWise']]);
        $this->middleware('permission:profit-loss-inventory-valuation', ['only' => ['inventoryValuation']]);
        $this->middleware('permission:profit-loss-costing-comparison', ['only' => ['costingComparison']]);
        $this->middleware('permission:profit-loss-export', ['only' => ['export']]);
        $this->middleware('permission:profit-loss-losses-list', ['only' => ['losses', 'showLoss']]);
        $this->middleware('permission:profit-loss-losses-create', ['only' => ['createLoss', 'storeLoss']]);
        $this->middleware('permission:profit-loss-losses-approve', ['only' => ['approveLoss']]);
        $this->middleware('permission:profit-loss-losses-reject', ['only' => ['rejectLoss']]);

        $this->profitLossService = $profitLossService;
        $this->warehouseStockService = $warehouseStockService;
    }

    /**
     * Display profit/loss dashboard
     */
    public function dashboard(Request $request)
    {
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $currentMonthReport = $this->profitLossService->generateProfitLossReport(
            'monthly',
            $startOfMonth,
            $endOfMonth
        );

        // Get previous month data for comparison
        $previousMonth = now()->subMonth();
        $prevStartOfMonth = $previousMonth->copy()->startOfMonth()->toDateString();
        $prevEndOfMonth = $previousMonth->copy()->endOfMonth()->toDateString();

        $previousMonthReport = $this->profitLossService->generateProfitLossReport(
            'monthly',
            $prevStartOfMonth,
            $prevEndOfMonth
        );

        // Get profit trends
        $profitTrends = $this->profitLossService->getProfitTrends(6);

        // Get top profitable products
        $productWiseProfit = $this->profitLossService->generateProductWiseProfit(
            $startOfMonth,
            $endOfMonth
        )->take(5);

        // Get warehouse performance
        $warehouseWiseProfit = $this->profitLossService->generateWarehouseWiseProfit(
            $startOfMonth,
            $endOfMonth
        );

        return view('backEnd.profit-loss.dashboard', compact(
            'currentMonthReport',
            'previousMonthReport',
            'profitTrends',
            'productWiseProfit',
            'warehouseWiseProfit'
        ));
    }

    /**
     * Generate profit/loss reports
     */
    public function reports(Request $request)
    {
        $reportType = $request->input('report_type', 'monthly');
        $defaultDate = now()->toDateString();

        $request->merge([
            'report_type' => $reportType,
            'start_date' => $request->input('start_date', $defaultDate),
            'end_date' => $request->input('end_date', $defaultDate),
            'year' => $request->input('year', now()->year),
            'costing_method' => $request->input('costing_method', 'fifo'),
        ]);

        $request->validate([
            'report_type' => 'required|in:daily,monthly,yearly,custom',
            'start_date' => 'nullable|required_if:report_type,custom,daily,monthly|date',
            'end_date' => 'nullable|required_if:report_type,custom|date|after_or_equal:start_date',
            'year' => 'nullable|required_if:report_type,yearly|integer|min:2000|max:2100',
            'costing_method' => 'required|in:fifo,weighted_average',
            'product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        [$startDate, $endDate] = $this->resolveReportDates(
            $request->report_type,
            $request->start_date,
            $request->end_date,
            (int) $request->year
        );
        $this->guardDateRangeWindow($startDate, $endDate);

        $filters = array_filter([
            'costing_method' => $request->costing_method,
            'product_id' => $request->product_id,
            'warehouse_id' => $request->warehouse_id,
        ]);

        $report = $this->profitLossService->generateProfitLossReport(
            $request->report_type,
            $startDate,
            $endDate,
            $filters
        );

        $products = $this->getActiveProductsForFilters();
        $warehouses = $this->getActiveWarehousesForFilters();

        return view('backEnd.profit-loss.reports', compact(
            'report',
            'startDate',
            'endDate',
            'products',
            'warehouses'
        ));
    }

    /**
     * Show profit/loss trends
     */
    public function trends(Request $request)
    {
        $request->validate([
            'months' => 'nullable|integer|min:1|max:36',
        ]);

        $months = (int) $request->get('months', 12);
        $trends = $this->profitLossService->getProfitTrends($months);

        return view('backEnd.profit-loss.trends', compact('trends', 'months'));
    }

    /**
     * Product-wise profit analysis
     */
    public function productWise(Request $request)
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->endOfMonth()->toDateString()),
            'costing_method' => $request->input('costing_method', 'fifo'),
            'limit' => $request->input('limit', 50),
        ]);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'costing_method' => 'required|in:fifo,weighted_average',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $limit = $request->get('limit', 50);
        $this->guardDateRangeWindow($startDate, $endDate);

        $filters = ['costing_method' => $request->costing_method];

        $productWiseProfit = $this->profitLossService->generateProductWiseProfit(
            $startDate,
            $endDate,
            $filters
        )->take($limit);

        return view('backEnd.profit-loss.product-wise', compact(
            'productWiseProfit',
            'startDate',
            'endDate',
            'limit'
        ));
    }

    /**
     * Warehouse-wise profit analysis
     */
    public function warehouseWise(Request $request)
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->endOfMonth()->toDateString()),
            'costing_method' => $request->input('costing_method', 'fifo'),
        ]);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'costing_method' => 'required|in:fifo,weighted_average',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $this->guardDateRangeWindow($startDate, $endDate);

        $filters = ['costing_method' => $request->costing_method];

        $warehouseWiseProfit = $this->profitLossService->generateWarehouseWiseProfit(
            $startDate,
            $endDate,
            $filters
        );

        return view('backEnd.profit-loss.warehouse-wise', compact(
            'warehouseWiseProfit',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Loss entries management
     */
    public function losses(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'entry_type' => 'nullable|in:damage,expired,stolen,theft,other',
            'status' => 'nullable|in:pending,approved,rejected',
            'product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);
        $filters['entry_type'] = $this->normalizeLossEntryType($filters['entry_type'] ?? null);

        $query = ProfitLossEntry::query()->with(['product', 'warehouse', 'reporter', 'approver']);
        $this->applyLossFilters($query, $filters);

        $losses = $query->latest('entry_date')->paginate(25)->appends($request->query());

        $products = $this->getActiveProductsForFilters();
        $warehouses = $this->getActiveWarehousesForFilters();

        // Summary statistics for the current filtered scope
        $summaryQuery = ProfitLossEntry::query();
        $this->applyLossFilters($summaryQuery, $filters);

        $totalLossValue = (clone $summaryQuery)->approved()->sum('total_loss_amount');
        $pendingLosses = (clone $summaryQuery)->pending()->count();
        $approvedLosses = (clone $summaryQuery)->approved()->count();

        return view('backEnd.profit-loss.losses', compact(
            'losses',
            'products',
            'warehouses',
            'totalLossValue',
            'pendingLosses',
            'approvedLosses'
        ));
    }

    /**
     * Create loss entry
     */
    public function createLoss()
    {
        $products = $this->getActiveProductsForFilters();
        $warehouses = $this->getActiveWarehousesForFilters();

        return view('backEnd.profit-loss.create-loss', compact('products', 'warehouses'));
    }

    /**
     * Store loss entry
     */
    public function storeLoss(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date|before_or_equal:today',
            'entry_type' => 'required|in:damage,expired,stolen,theft,other',
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
            'reason_details' => 'nullable|string|max:1000',
            'evidence_attachments' => 'nullable|array',
            'evidence_attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);
        $validated['entry_type'] = $this->normalizeLossEntryType($validated['entry_type']);
        $actor = $this->requireAuthenticatedUser();

        try {
            DB::transaction(function () use ($validated, $request, $actor) {
                $stock = WarehouseStock::query()
                    ->where('product_id', (int) $validated['product_id'])
                    ->where('warehouse_id', (int) $validated['warehouse_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $stock || (float) $stock->available_quantity < (float) $validated['quantity']) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Insufficient stock for the selected product and warehouse.',
                    ]);
                }

                $totalLossAmount = (float) $validated['quantity'] * (float) $validated['unit_cost'];

                $lossEntry = ProfitLossEntry::create([
                    'entry_date' => $validated['entry_date'],
                    'entry_type' => $validated['entry_type'],
                    'product_id' => (int) $validated['product_id'],
                    'warehouse_id' => (int) $validated['warehouse_id'],
                    'quantity' => (float) $validated['quantity'],
                    'unit_cost' => (float) $validated['unit_cost'],
                    'total_loss_amount' => $totalLossAmount,
                    'description' => $validated['description'],
                    'reason_details' => $validated['reason_details'] ?? null,
                    'reported_by' => $actor->id,
                ]);

                // Handle file uploads if any
                if ($request->hasFile('evidence_attachments')) {
                    $attachments = [];
                    foreach ($request->file('evidence_attachments') as $file) {
                        $filename = $file->store('profit-loss-evidence', 'public');
                        $attachments[] = $filename;
                    }
                    $lossEntry->update(['evidence_attachments' => $attachments]);
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create loss entry. Please try again.');
        }

        return redirect()->route('admin.profit-loss.losses')
            ->with('success', 'Loss entry created successfully and is pending approval.');
    }

    /**
     * Show loss entry details
     */
    public function showLoss(ProfitLossEntry $loss)
    {
        $loss->load(['product', 'warehouse', 'reporter', 'approver']);

        return view('backEnd.profit-loss.show-loss', compact('loss'));
    }

    /**
     * Approve loss entry
     */
    public function approveLoss(ProfitLossEntry $loss)
    {
        $actor = $this->requireAuthenticatedUser();
        try {
            DB::transaction(function () use ($loss, $actor) {
                $lossEntry = ProfitLossEntry::query()
                    ->whereKey($loss->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lossEntry->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'loss' => 'Only pending loss entries can be approved.',
                    ]);
                }

                $this->warehouseStockService->decreaseStock(
                    warehouseId: (int) $lossEntry->warehouse_id,
                    productId: (int) $lossEntry->product_id,
                    quantity: (float) $lossEntry->quantity,
                    referenceType: 'stock_loss',
                    referenceId: (int) $lossEntry->id,
                    notes: 'Approved stock loss entry '.$lossEntry->entry_number
                );

                $lossEntry->approve($actor);

                $this->profitLossService->updateInventoryValuation(
                    (int) $lossEntry->product_id,
                    (int) $lossEntry->warehouse_id,
                    now()->toDateString()
                );
            });
        } catch (ValidationException $e) {
            $errorMessage = collect($e->errors())->flatten()->first() ?? 'Loss approval failed.';

            return redirect()->back()->with('error', $errorMessage);
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'Failed to approve loss entry.');
        }

        return redirect()->back()->with('success', 'Loss entry approved successfully.');
    }

    /**
     * Reject loss entry
     */
    public function rejectLoss(Request $request, ProfitLossEntry $loss)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $actor = $this->requireAuthenticatedUser();
        try {
            DB::transaction(function () use ($loss, $actor, $validated) {
                $lossEntry = ProfitLossEntry::query()
                    ->whereKey($loss->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lossEntry->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'loss' => 'Only pending loss entries can be rejected.',
                    ]);
                }

                $lossEntry->reject($actor, $validated['reason']);
            });
        } catch (ValidationException $e) {
            $errorMessage = collect($e->errors())->flatten()->first() ?? 'Loss rejection failed.';

            return redirect()->back()->with('error', $errorMessage);
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'Failed to reject loss entry.');
        }

        return redirect()->back()->with('success', 'Loss entry rejected successfully.');
    }

    /**
     * Inventory valuation reports
     */
    public function inventoryValuation(Request $request)
    {
        $request->merge([
            'valuation_date' => $request->input('valuation_date', now()->toDateString()),
            'costing_method' => $request->input('costing_method', 'fifo'),
        ]);

        $request->validate([
            'valuation_date' => 'required|date',
            'costing_method' => 'required|in:fifo,weighted_average',
            'product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $valuationDate = $request->valuation_date;
        $costingMethod = $request->costing_method;

        $valuationReport = $this->profitLossService->generateInventoryValuationReport(
            $valuationDate,
            $costingMethod,
            array_filter([
                'product_id' => $request->input('product_id'),
                'warehouse_id' => $request->input('warehouse_id'),
            ])
        );

        $products = $this->getActiveProductsForFilters();
        $warehouses = $this->getActiveWarehousesForFilters();
        $valuationSummary = $valuationReport['summary'];
        $valuationItems = $valuationReport['items'];
        $warehouseValuation = $valuationReport['warehouse_summary'];

        return view('backEnd.profit-loss.inventory-valuation', compact(
            'valuationDate',
            'costingMethod',
            'products',
            'warehouses',
            'valuationSummary',
            'valuationItems',
            'warehouseValuation'
        ));
    }

    /**
     * Costing method comparison
     */
    public function costingComparison(Request $request)
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->endOfMonth()->toDateString()),
        ]);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $this->guardDateRangeWindow($startDate, $endDate);

        // Generate reports with both costing methods
        $fifoReport = $this->profitLossService->generateProfitLossReport(
            'custom',
            $startDate,
            $endDate,
            ['costing_method' => 'fifo']
        );

        $wacReport = $this->profitLossService->generateProfitLossReport(
            'custom',
            $startDate,
            $endDate,
            ['costing_method' => 'weighted_average']
        );

        return view('backEnd.profit-loss.costing-comparison', compact(
            'fifoReport',
            'wacReport',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export profit/loss report
     */
    public function export(Request $request)
    {
        $request->merge([
            'report_type' => $request->input('report_type', 'monthly'),
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->endOfMonth()->toDateString()),
            'year' => $request->input('year', now()->year),
            'format' => $request->input('format', 'xlsx'),
            'costing_method' => $request->input('costing_method', 'fifo'),
        ]);

        $request->validate([
            'report_type' => 'required|in:daily,monthly,yearly,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'year' => 'nullable|integer|min:2000|max:2100',
            'format' => 'required|in:csv,xlsx',
            'costing_method' => 'required|in:fifo,weighted_average',
            'product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        [$startDate, $endDate] = $this->resolveReportDates(
            $request->report_type,
            $request->start_date,
            $request->end_date,
            (int) $request->year
        );
        $this->guardDateRangeWindow($startDate, $endDate);

        // Generate report
        $filters = array_filter([
            'costing_method' => $request->costing_method,
            'product_id' => $request->product_id,
            'warehouse_id' => $request->warehouse_id,
        ]);

        $report = $this->profitLossService->generateProfitLossReport(
            $request->report_type,
            $startDate,
            $endDate,
            $filters
        );

        $filename = 'profit-loss-report-'.$startDate.'-to-'.$endDate;

        if ($request->format === 'xlsx') {
            return Excel::download(
                new ArrayReportExport(
                    [
                        'Report Type',
                        'Period Start',
                        'Period End',
                        'Sales Revenue',
                        'Cost of Goods Sold',
                        'Gross Profit',
                        'Operating Expenses',
                        'Net Profit',
                        'Inventory Losses',
                        'Costing Method',
                        'Product ID',
                        'Warehouse ID',
                    ],
                    [[
                        (string) $report->report_type,
                        (string) $report->period_start,
                        (string) $report->period_end,
                        (float) $report->sales_revenue,
                        (float) $report->cost_of_goods_sold,
                        (float) $report->gross_profit,
                        (float) $report->operating_expenses,
                        (float) $report->net_profit,
                        (float) $report->inventory_losses,
                        (string) $report->costing_method,
                        $report->product_id ? (int) $report->product_id : '',
                        $report->warehouse_id ? (int) $report->warehouse_id : '',
                    ]]
                ),
                $filename.'.xlsx'
            );
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($report) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Report Type', 'Period Start', 'Period End', 'Sales Revenue',
                'Cost of Goods Sold', 'Gross Profit', 'Operating Expenses',
                'Net Profit', 'Inventory Losses', 'Costing Method',
            ]);

            // CSV data
            fputcsv($file, [
                $report->report_type,
                $report->period_start,
                $report->period_end,
                $report->sales_revenue,
                $report->cost_of_goods_sold,
                $report->gross_profit,
                $report->operating_expenses,
                $report->net_profit,
                $report->inventory_losses,
                $report->costing_method,
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function applyLossFilters(Builder $query, array $filters): void
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($startDate && $endDate) {
            $query->whereBetween('entry_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('entry_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('entry_date', '<=', $endDate);
        }

        if (! empty($filters['entry_type'])) {
            $entryType = $this->normalizeLossEntryType($filters['entry_type']);
            if ($entryType === 'theft') {
                $query->whereIn('entry_type', ['theft', 'stolen']);
            } else {
                $query->where('entry_type', $entryType);
            }
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }
    }

    protected function resolveReportDates(string $reportType, ?string $startDate, ?string $endDate, ?int $year = null): array
    {
        $reportType = strtolower($reportType);

        switch ($reportType) {
            case 'daily':
                $start = Carbon::parse($startDate ?? now()->toDateString())->toDateString();

                return [$start, $start];

            case 'monthly':
                $monthlyDate = Carbon::parse($startDate ?? now()->toDateString());

                return [
                    $monthlyDate->copy()->startOfMonth()->toDateString(),
                    $monthlyDate->copy()->endOfMonth()->toDateString(),
                ];

            case 'yearly':
                $yearValue = $year ?? now()->year;

                return [
                    Carbon::create($yearValue, 1, 1)->toDateString(),
                    Carbon::create($yearValue, 12, 31)->toDateString(),
                ];

            default:
                return [
                    Carbon::parse($startDate ?? now()->toDateString())->toDateString(),
                    Carbon::parse($endDate ?? now()->toDateString())->toDateString(),
                ];
        }
    }

    protected function normalizeLossEntryType(?string $entryType): ?string
    {
        if ($entryType === null || $entryType === '') {
            return null;
        }

        return $entryType === 'stolen' ? 'theft' : $entryType;
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
            abort(403, 'Authenticated user is required.');
        }

        return $user;
    }

    protected function getActiveProductsForFilters()
    {
        return Product::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'product_code', 'sku']);
    }

    protected function getActiveWarehousesForFilters()
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city']);
    }
}
