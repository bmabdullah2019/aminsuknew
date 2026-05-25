<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportsHubCustomerLedgerFilterRequest;
use App\Http\Requests\ReportsHubDailyFilterRequest;
use App\Http\Requests\ReportsHubDamageReportFilterRequest;
use App\Http\Requests\ReportsHubMoneyReceiptFilterRequest;
use App\Http\Requests\ReportsHubMonthRangeFilterRequest;
use App\Http\Requests\ReportsHubReturnStatementFilterRequest;
use App\Http\Requests\ReportsHubStatementFilterRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProfitLossEntry;
use App\Models\ReturnOrder;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use App\Models\SupplierPurchaseReturn;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReportsHubController extends Controller
{
    private const MAX_REPORT_RANGE_DAYS = 730;

    public function inventorySummary(): RedirectResponse
    {
        return redirect()->route('admin.reports-new.stock');
    }

    public function daily(ReportsHubDailyFilterRequest $request): View
    {
        [$date, $branchId] = $this->resolveDailyInputs($request->filters());
        $data = $this->buildDailyData($date, $branchId);

        return view('backEnd.reports.daily_report', $data);
    }

    public function dailyPrint(ReportsHubDailyFilterRequest $request): View
    {
        [$date, $branchId] = $this->resolveDailyInputs($request->filters());
        $data = $this->buildDailyData($date, $branchId);

        return view('backEnd.reports.print.daily_report', $data);
    }

    public function monthWiseSalesComparative(ReportsHubMonthRangeFilterRequest $request): View
    {
        [$fromMonth, $toMonth, $branchId] = $this->resolveMonthRangeInputs($request->filters());
        $data = $this->buildMonthWiseSalesComparativeData($fromMonth, $toMonth, $branchId);

        return view('backEnd.reports.month_wise_sales_comparative', $data);
    }

    public function monthWiseSalesComparativePrint(ReportsHubMonthRangeFilterRequest $request): View
    {
        [$fromMonth, $toMonth, $branchId] = $this->resolveMonthRangeInputs($request->filters());
        $data = $this->buildMonthWiseSalesComparativeData($fromMonth, $toMonth, $branchId);

        return view('backEnd.reports.print.month_wise_sales_comparative', $data);
    }

    public function purchaseReturnStatement(ReportsHubStatementFilterRequest $request): View
    {
        [$filters, $branches, $suppliers] = $this->resolveStatementFilters($request->filters());

        $query = SupplierPurchaseReturn::query()
            ->with(['supplier:id,name,supplier_code', 'branch:id,name,code', 'creator:id,name'])
            ->latest('return_date')
            ->latest('id');

        $this->applyCommonStatementFilters($query, $filters, dateColumn: 'return_date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $rows = $query->paginate(50)->appends($request->query());

        return view('backEnd.reports.purchase_return_statement', compact('rows', 'filters', 'branches', 'suppliers'));
    }

    public function purchaseReturnStatementPrint(ReportsHubStatementFilterRequest $request): View
    {
        [$filters, $branches, $suppliers] = $this->resolveStatementFilters($request->filters());

        $query = SupplierPurchaseReturn::query()
            ->with(['supplier:id,name,supplier_code', 'branch:id,name,code', 'creator:id,name'])
            ->latest('return_date')
            ->latest('id');

        $this->applyCommonStatementFilters($query, $filters, dateColumn: 'return_date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $rows = $query->get();

        return view('backEnd.reports.print.purchase_return_statement', compact('rows', 'filters', 'branches', 'suppliers'));
    }

    public function supplierLedger(ReportsHubStatementFilterRequest $request): View
    {
        [$filters, $branches, $suppliers] = $this->resolveStatementFilters($request->filters());
        [$rows, $totals] = $this->buildSupplierLedgerSummary($filters);
        $detail = $this->buildSupplierLedgerDetail($filters);

        return view('backEnd.reports.supplier_ledger', compact('rows', 'totals', 'filters', 'branches', 'suppliers', 'detail'));
    }

    public function supplierLedgerPrint(ReportsHubStatementFilterRequest $request): View
    {
        [$filters, $branches, $suppliers] = $this->resolveStatementFilters($request->filters());
        [$rows, $totals] = $this->buildSupplierLedgerSummary($filters);
        $detail = $this->buildSupplierLedgerDetail($filters);

        return view('backEnd.reports.print.supplier_ledger', compact('rows', 'totals', 'filters', 'branches', 'suppliers', 'detail'));
    }

    private function buildSupplierLedgerSummary(array $filters): array
    {
        $startDate = (string) ($filters['start_date'] ?? now()->startOfMonth()->toDateString());
        $endDate = (string) ($filters['end_date'] ?? now()->endOfMonth()->toDateString());

        $supplierQuery = Supplier::query()
            ->select(['id', 'name', 'supplier_code'])
            ->orderBy('name');
        if (! empty($filters['supplier_id'])) {
            $supplierQuery->where('id', (int) $filters['supplier_id']);
        }
        $supplierRows = $supplierQuery->get();
        if ($supplierRows->isEmpty()) {
            return [collect(), [
                'opening' => 0.0,
                'purchase' => 0.0,
                'payment' => 0.0,
                'adjustment' => 0.0,
                'return' => 0.0,
                'balance' => 0.0,
            ]];
        }

        $supplierIds = $supplierRows->pluck('id')->all();

        $openingQuery = SupplierLedger::query()
            ->selectRaw('supplier_id, COALESCE(SUM(debit - credit), 0) as opening')
            ->whereIn('supplier_id', $supplierIds)
            ->whereDate('transaction_date', '<', $startDate);
        if (! empty($filters['branch_id']) && Schema::hasColumn('supplier_ledgers', 'branch_id')) {
            $openingQuery->where('branch_id', (int) $filters['branch_id']);
        }
        $openings = $openingQuery->groupBy('supplier_id')->get()->keyBy('supplier_id');

        $periodQuery = SupplierLedger::query()
            ->selectRaw('supplier_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'purchase' THEN debit ELSE 0 END), 0) as purchase_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'payment' THEN credit ELSE 0 END), 0) as payment_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'adjustment' THEN (debit - credit) ELSE 0 END), 0) as adjustment_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'purchase_return' THEN credit ELSE 0 END), 0) as return_total")
            ->whereIn('supplier_id', $supplierIds)
            ->whereBetween('transaction_date', [$startDate, $endDate]);
        if (! empty($filters['branch_id']) && Schema::hasColumn('supplier_ledgers', 'branch_id')) {
            $periodQuery->where('branch_id', (int) $filters['branch_id']);
        }
        if (! empty($filters['transaction_type'])) {
            $periodQuery->where('transaction_type', $filters['transaction_type']);
        }
        $periods = $periodQuery->groupBy('supplier_id')->get()->keyBy('supplier_id');

        $rows = $supplierRows->map(function (Supplier $supplier) use ($openings, $periods) {
            $opening = round((float) ($openings->get($supplier->id)->opening ?? 0), 2);
            $purchase = round((float) ($periods->get($supplier->id)->purchase_total ?? 0), 2);
            $payment = round((float) ($periods->get($supplier->id)->payment_total ?? 0), 2);
            $adjustment = round((float) ($periods->get($supplier->id)->adjustment_total ?? 0), 2);
            $return = round((float) ($periods->get($supplier->id)->return_total ?? 0), 2);
            $balance = round($opening + $purchase + $adjustment - $payment - $return, 2);

            return [
                'supplier_id' => $supplier->id,
                'code' => (string) ($supplier->supplier_code ?? ''),
                'name' => (string) $supplier->name,
                'opening' => $opening,
                'purchase' => $purchase,
                'payment' => $payment,
                'adjustment' => $adjustment,
                'return' => $return,
                'balance' => $balance,
            ];
        })->values();

        $totals = [
            'opening' => round((float) $rows->sum('opening'), 2),
            'purchase' => round((float) $rows->sum('purchase'), 2),
            'payment' => round((float) $rows->sum('payment'), 2),
            'adjustment' => round((float) $rows->sum('adjustment'), 2),
            'return' => round((float) $rows->sum('return'), 2),
            'balance' => round((float) $rows->sum('balance'), 2),
        ];

        return [$rows, $totals];
    }

    private function buildSupplierLedgerDetail(array $filters): array
    {
        $supplierId = (int) ($filters['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            return [
                'supplier' => null,
                'opening' => 0.0,
                'closing' => 0.0,
                'lines' => collect(),
            ];
        }

        $startDate = (string) ($filters['start_date'] ?? now()->startOfMonth()->toDateString());
        $endDate = (string) ($filters['end_date'] ?? now()->endOfMonth()->toDateString());

        $openingQuery = SupplierLedger::query()
            ->where('supplier_id', $supplierId)
            ->whereDate('transaction_date', '<', $startDate);
        if (! empty($filters['branch_id']) && Schema::hasColumn('supplier_ledgers', 'branch_id')) {
            $openingQuery->where('branch_id', (int) $filters['branch_id']);
        }
        $opening = round((float) ($openingQuery->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance') ?? 0), 2);

        $lineQuery = SupplierLedger::query()
            ->with(['creator:id,name'])
            ->where('supplier_id', $supplierId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->orderBy('id');
        if (! empty($filters['branch_id']) && Schema::hasColumn('supplier_ledgers', 'branch_id')) {
            $lineQuery->where('branch_id', (int) $filters['branch_id']);
        }
        if (! empty($filters['transaction_type'])) {
            $lineQuery->where('transaction_type', (string) $filters['transaction_type']);
        }

        $running = $opening;
        $lines = $lineQuery->get()->map(function (SupplierLedger $line) use (&$running) {
            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);
            $running = round($running + $debit - $credit, 2);

            return [
                'date' => optional($line->transaction_date)->format('Y-m-d') ?: '',
                'transaction_type' => (string) ($line->transaction_type ?? ''),
                'reference' => (string) ($line->reference_number ?: (($line->reference_type ?? '').'#'.($line->reference_id ?? ''))),
                'description' => (string) ($line->description ?? ''),
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $running,
                'creator' => (string) ($line->creator->name ?? ''),
            ];
        })->values();

        return [
            'supplier' => Supplier::query()->find($supplierId),
            'opening' => $opening,
            'closing' => round((float) ($lines->last()['balance'] ?? $opening), 2),
            'lines' => $lines,
        ];
    }

    public function billPaymentStatement(ReportsHubStatementFilterRequest $request): View
    {
        [$filters, $branches, $suppliers] = $this->resolveStatementFilters($request->filters());

        $query = SupplierPayment::query()
            ->with(['supplier:id,name,supplier_code', 'branch:id,name,code', 'creator:id,name'])
            ->latest('payment_date')
            ->latest('id');

        $this->applyCommonStatementFilters($query, $filters, dateColumn: 'payment_date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $rows = $query->paginate(50)->appends($request->query());

        return view('backEnd.reports.bill_payment_statement', compact('rows', 'filters', 'branches', 'suppliers'));
    }

    public function billPaymentStatementPrint(ReportsHubStatementFilterRequest $request): View
    {
        [$filters, $branches, $suppliers] = $this->resolveStatementFilters($request->filters());

        $query = SupplierPayment::query()
            ->with(['supplier:id,name,supplier_code', 'branch:id,name,code', 'creator:id,name'])
            ->orderBy('payment_date')
            ->orderBy('id');

        $this->applyCommonStatementFilters($query, $filters, dateColumn: 'payment_date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $rows = $query->get();

        return view('backEnd.reports.print.bill_payment_statement', compact('rows', 'filters', 'branches', 'suppliers'));
    }

    public function salesReturnStatement(ReportsHubReturnStatementFilterRequest $request): View
    {
        [$filters, $branches] = $this->resolveReturnFilters($request->filters());

        $query = ReturnOrder::query()
            ->with(['customer:id,name,phone', 'order:id,invoice_id,branch_id', 'returnReason:id,reason_name', 'creator:id,name'])
            ->latest('created_at');

        $this->applyReturnStatementFilters($query, $filters);
        $rows = $query->paginate(50)->appends($request->query());

        return view('backEnd.reports.sales_return_statement', compact('rows', 'filters', 'branches'));
    }

    public function salesReturnStatementPrint(ReportsHubReturnStatementFilterRequest $request): View
    {
        [$filters, $branches] = $this->resolveReturnFilters($request->filters());

        $query = ReturnOrder::query()
            ->with(['customer:id,name,phone', 'order:id,invoice_id,branch_id', 'returnReason:id,reason_name', 'creator:id,name'])
            ->orderBy('created_at')
            ->orderBy('id');

        $this->applyReturnStatementFilters($query, $filters);
        $rows = $query->get();

        return view('backEnd.reports.print.sales_return_statement', compact('rows', 'filters', 'branches'));
    }

    public function damageReport(ReportsHubDamageReportFilterRequest $request): View
    {
        return view('backEnd.reports.damage_report', $this->buildDamageReportData($request->filters(), forPrint: false));
    }

    public function damageReportPrint(ReportsHubDamageReportFilterRequest $request): View
    {
        return view('backEnd.reports.print.damage_report', $this->buildDamageReportData($request->filters(), forPrint: true));
    }

    public function customerLedger(ReportsHubCustomerLedgerFilterRequest $request): View
    {
        $data = $this->buildCustomerLedgerData($request->filters(), forPrint: false);

        return view('backEnd.reports.customer_ledger', $data);
    }

    public function customerLedgerPrint(ReportsHubCustomerLedgerFilterRequest $request): View
    {
        $data = $this->buildCustomerLedgerData($request->filters(), forPrint: true);

        return view('backEnd.reports.print.customer_ledger', $data);
    }

    public function moneyReceipt(ReportsHubMoneyReceiptFilterRequest $request): View
    {
        return view('backEnd.reports.money_receipt', $this->buildMoneyReceiptData($request->filters()));
    }

    public function moneyReceiptPrint(Payment $payment): View
    {
        $payment->load(['customer', 'order', 'branch']);

        return view('backEnd.reports.print.money_receipt', compact('payment'));
    }

    private function resolveDailyInputs(array $filters): array
    {
        $date = Carbon::parse($filters['date'] ?? now()->toDateString())->toDateString();
        $branchId = ! empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;

        return [$date, $branchId];
    }

    private function buildDailyData(string $date, ?int $branchId): array
    {
        $branches = $this->branchOptions();
        $queryParams = $this->queryParams([
            'date' => $date,
            'branch_id' => $branchId,
        ]);

        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = Carbon::parse($date)->endOfDay();

        $ordersQuery = Order::query()->whereBetween('created_at', [$dayStart, $dayEnd]);
        if ($branchId && Schema::hasColumn('orders', 'branch_id')) {
            $ordersQuery->where('branch_id', $branchId);
        }
        $salesTotal = (float) $ordersQuery
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as total')
            ->value('total');
        $salesCount = (int) $ordersQuery->count();

        $paymentsQuery = Payment::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$dayStart, $dayEnd]);
        if ($branchId && Schema::hasColumn('payments', 'branch_id')) {
            $paymentsQuery->where('branch_id', $branchId);
        }
        $customerReceipts = (float) $paymentsQuery
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as total')
            ->value('total');
        $customerReceiptsCount = (int) $paymentsQuery->count();

        $supplierPayments = 0.0;
        $supplierPaymentsCount = 0;
        if (Schema::hasTable('supplier_payments')) {
            $spQuery = SupplierPayment::query()
                ->whereIn('status', ['completed'])
                ->whereBetween('payment_date', [$date, $date]);
            if ($branchId && Schema::hasColumn('supplier_payments', 'branch_id')) {
                $spQuery->where('branch_id', $branchId);
            }
            $supplierPayments = (float) $spQuery->sum('amount');
            $supplierPaymentsCount = (int) $spQuery->count();
        }

        $expensesTotal = 0.0;
        $expensesCount = 0;
        if (Schema::hasTable('expenses')) {
            $exQuery = Expense::query()->whereBetween('expense_date', [$date, $date]);
            if ($branchId && Schema::hasColumn('expenses', 'branch_id')) {
                $exQuery->where('branch_id', $branchId);
            }
            $expensesTotal = (float) $exQuery->sum('total_amount');
            $expensesCount = (int) $exQuery->count();
        }

        return compact(
            'date',
            'branchId',
            'branches',
            'salesTotal',
            'salesCount',
            'customerReceipts',
            'customerReceiptsCount',
            'supplierPayments',
            'supplierPaymentsCount',
            'expensesTotal',
            'expensesCount',
            'queryParams'
        );
    }

    private function resolveMonthRangeInputs(array $filters): array
    {
        $toMonth = Carbon::parse(($filters['to_month'] ?? now()->format('Y-m')).'-01')->startOfMonth();
        $fromMonth = Carbon::parse(($filters['from_month'] ?? now()->subMonths(5)->format('Y-m')).'-01')->startOfMonth();

        $branchId = ! empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;

        $this->guardDateRangeWindow($fromMonth->toDateString(), $toMonth->copy()->endOfMonth()->toDateString());

        return [$fromMonth, $toMonth, $branchId];
    }

    private function buildMonthWiseSalesComparativeData(Carbon $fromMonth, Carbon $toMonth, ?int $branchId): array
    {
        $branches = $this->branchOptions();
        $months = $this->monthSequence($fromMonth, $toMonth);

        $orderRows = Order::query()
            ->when($branchId && Schema::hasColumn('orders', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$fromMonth->copy()->startOfMonth(), $toMonth->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as gross_sales')
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $paymentRows = Payment::query()
            ->where('payment_status', 'paid')
            ->when($branchId && Schema::hasColumn('payments', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$fromMonth->copy()->startOfMonth(), $toMonth->copy()->endOfMonth()])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym')
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as paid_amount')
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $refundRows = collect();
        if (Schema::hasTable('return_orders')) {
            $refundRows = ReturnOrder::query()
                ->when($branchId && Schema::hasColumn('return_orders', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
                ->whereBetween('created_at', [$fromMonth->copy()->startOfMonth(), $toMonth->copy()->endOfMonth()])
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym')
                ->selectRaw('COALESCE(SUM(refund_amount), 0) as refund_amount')
                ->groupBy('ym')
                ->get()
                ->keyBy('ym');
        }

        $rows = $months->map(function (string $ym) use ($orderRows, $paymentRows, $refundRows) {
            $orders = $orderRows->get($ym);
            $payments = $paymentRows->get($ym);
            $refund = $refundRows->get($ym);

            return [
                'ym' => $ym,
                'order_count' => (int) ($orders->order_count ?? 0),
                'gross_sales' => (float) ($orders->gross_sales ?? 0),
                'paid_amount' => (float) ($payments->paid_amount ?? 0),
                'refund_amount' => (float) ($refund->refund_amount ?? 0),
            ];
        })->values();

        $summary = [
            'order_count' => (int) $rows->sum('order_count'),
            'gross_sales' => (float) $rows->sum('gross_sales'),
            'paid_amount' => (float) $rows->sum('paid_amount'),
            'refund_amount' => (float) $rows->sum('refund_amount'),
        ];

        return [
            'fromMonth' => $fromMonth,
            'toMonth' => $toMonth,
            'branchId' => $branchId,
            'branches' => $branches,
            'rows' => $rows,
            'summary' => $summary,
            'queryParams' => $this->queryParams([
                'from_month' => $fromMonth->format('Y-m'),
                'to_month' => $toMonth->format('Y-m'),
                'branch_id' => $branchId,
            ]),
        ];
    }

    private function branchOptions(): Collection
    {
        if (! Schema::hasTable('branches')) {
            return collect();
        }

        return Branch::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function monthSequence(Carbon $fromMonth, Carbon $toMonth): Collection
    {
        $cursor = $fromMonth->copy();
        $end = $toMonth->copy();
        $items = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $items[] = $cursor->format('Y-m');
            $cursor->addMonthNoOverflow();
        }

        return collect($items);
    }

    private function resolveStatementFilters(array $filters): array
    {
        $this->guardDateRangeWindow((string) $filters['start_date'], (string) $filters['end_date']);

        $branches = $this->branchOptions();
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'supplier_code']);

        return [$filters, $branches, $suppliers];
    }

    private function applyCommonStatementFilters(Builder $query, array $filters, string $dateColumn): void
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        if ($startDate && $endDate) {
            $query->whereBetween($dateColumn, [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (! empty($filters['branch_id']) && Schema::hasColumn($query->getModel()->getTable(), 'branch_id')) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }
    }

    private function resolveReturnFilters(array $filters): array
    {
        $this->guardDateRangeWindow((string) $filters['start_date'], (string) $filters['end_date']);

        $branches = $this->branchOptions();

        return [$filters, $branches];
    }

    private function applyReturnStatementFilters(Builder $query, array $filters): void
    {
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();
        $query->whereBetween('created_at', [$start, $end]);

        if (! empty($filters['branch_id']) && Schema::hasColumn('return_orders', 'branch_id')) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('return_status', $filters['status']);
        }
        if (! empty($filters['return_source'])) {
            $query->where('return_source', $filters['return_source']);
        }
    }

    private function guardDateRangeWindow(string $startDate, string $endDate): void
    {
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        if ($days > self::MAX_REPORT_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => 'Date range is too large. Maximum allowed range is '.self::MAX_REPORT_RANGE_DAYS.' days.',
            ]);
        }
    }

    private function supplierRunningBalance(array $filters, bool $withLines = false, ?Collection $lines = null): array
    {
        $supplierId = ! empty($filters['supplier_id']) ? (int) $filters['supplier_id'] : 0;
        if ($supplierId <= 0) {
            return [
                'supplier' => null,
                'opening' => null,
                'closing' => null,
            ];
        }

        $startDate = (string) ($filters['start_date'] ?? now()->startOfMonth()->toDateString());
        $endDate = (string) ($filters['end_date'] ?? now()->endOfMonth()->toDateString());

        $openingQuery = SupplierLedger::query()
            ->where('supplier_id', $supplierId)
            ->whereDate('transaction_date', '<', $startDate);
        if (! empty($filters['branch_id']) && Schema::hasColumn('supplier_ledgers', 'branch_id')) {
            $openingQuery->where('branch_id', (int) $filters['branch_id']);
        }
        $opening = (float) $openingQuery->selectRaw('COALESCE(SUM(debit - credit), 0) as bal')->value('bal');

        $closingQuery = SupplierLedger::query()
            ->where('supplier_id', $supplierId)
            ->whereDate('transaction_date', '<=', $endDate);
        if (! empty($filters['branch_id']) && Schema::hasColumn('supplier_ledgers', 'branch_id')) {
            $closingQuery->where('branch_id', (int) $filters['branch_id']);
        }
        $closing = (float) $closingQuery->selectRaw('COALESCE(SUM(debit - credit), 0) as bal')->value('bal');

        $supplier = Supplier::query()->find($supplierId);

        return compact('supplier', 'opening', 'closing');
    }

    private function buildCustomerLedgerData(array $filters, bool $forPrint): array
    {
        $startDate = (string) ($filters['start_date'] ?? now()->startOfMonth()->toDateString());
        $endDate = (string) ($filters['end_date'] ?? now()->endOfMonth()->toDateString());
        $this->guardDateRangeWindow($startDate, $endDate);

        $branches = $this->branchOptions();
        $customers = Customer::query()->orderBy('name')->limit(1000)->get(['id', 'name', 'phone']);

        $customerId = (int) ($filters['customer_id'] ?? 0);
        $customer = $customerId > 0 ? Customer::query()->find($customerId) : null;

        $opening = null;
        $lines = collect();
        $closing = null;

        if ($customerId > 0) {
            $opening = $this->customerOpeningBalance($customerId, $startDate, $filters['branch_id'] ?? null);
            $lines = $this->customerLedgerLines($customerId, $startDate, $endDate, $filters['branch_id'] ?? null);
            $closing = $opening + $lines->sum(fn ($l) => (float) $l['debit'] - (float) $l['credit']);
        }

        return compact('filters', 'branches', 'customers', 'customer', 'opening', 'lines', 'closing');
    }

    private function buildDamageReportData(array $filters, bool $forPrint): array
    {
        $startDate = (string) ($filters['start_date'] ?? now()->startOfMonth()->toDateString());
        $endDate = (string) ($filters['end_date'] ?? now()->endOfMonth()->toDateString());
        $this->guardDateRangeWindow($startDate, $endDate);

        $query = ProfitLossEntry::query()
            ->with(['product:id,name', 'warehouse:id,name'])
            ->where('status', 'approved')
            ->whereIn('entry_type', ['damage'])
            ->whereBetween('entry_date', [$startDate, $endDate]);

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        $queryParams = $this->queryParams($filters);
        $rows = $forPrint
            ? $query->orderBy('entry_date')->orderBy('id')->get()
            : $query->latest('entry_date')->latest('id')->paginate(50)->appends($queryParams);

        return compact('rows', 'filters', 'queryParams');
    }

    private function buildMoneyReceiptData(array $filters): array
    {
        $startDate = (string) ($filters['start_date'] ?? now()->startOfMonth()->toDateString());
        $endDate = (string) ($filters['end_date'] ?? now()->endOfMonth()->toDateString());
        $this->guardDateRangeWindow($startDate, $endDate);

        $query = Payment::query()
            ->with(['customer:id,name,phone', 'order:id,invoice_id,branch_id', 'branch:id,name,code'])
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->latest('created_at')
            ->latest('id');

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }
        if (! empty($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }
        if (! empty($filters['trx_id'])) {
            $query->where('trx_id', 'like', '%'.$filters['trx_id'].'%');
        }

        $queryParams = $this->queryParams($filters);
        $rows = $query->paginate(50)->appends($queryParams);
        $customers = Customer::query()->orderBy('name')->limit(500)->get(['id', 'name', 'phone']);

        return compact('rows', 'filters', 'customers', 'queryParams');
    }

    private function queryParams(array $filters): array
    {
        return array_filter($filters, static fn ($value) => ! ($value === null || $value === ''));
    }

    private function customerOpeningBalance(int $customerId, string $startDate, ?int $branchId): float
    {
        $ordersBefore = Order::query()
            ->where('customer_id', $customerId)
            ->whereDate('created_at', '<', $startDate)
            ->when($branchId && Schema::hasColumn('orders', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as total')
            ->value('total');

        $paidBefore = Payment::query()
            ->where('customer_id', $customerId)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', '<', $startDate)
            ->when($branchId && Schema::hasColumn('payments', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as total')
            ->value('total');

        $refundBefore = 0.0;
        if (Schema::hasTable('return_orders')) {
            $refundBefore = (float) ReturnOrder::query()
                ->where('customer_id', $customerId)
                ->whereDate('created_at', '<', $startDate)
                ->when($branchId && Schema::hasColumn('return_orders', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
                ->sum('refund_amount');
        }

        return round((float) $ordersBefore - (float) $paidBefore - (float) $refundBefore, 2);
    }

    private function customerLedgerLines(int $customerId, string $startDate, string $endDate, ?int $branchId): Collection
    {
        $orders = Order::query()
            ->where('customer_id', $customerId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->when($branchId && Schema::hasColumn('orders', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
            ->get(['id', 'invoice_id', 'created_at', 'amount', 'amount_minor', 'branch_id'])
            ->map(function (Order $o) {
                $amount = (int) ($o->amount_minor ?? 0) > 0 ? round(((int) $o->amount_minor) / 100, 2) : round((float) $o->amount, 2);

                return [
                    'date' => optional($o->created_at)->toDateTimeString(),
                    'ref' => (string) ($o->invoice_id ?? ('ORDER-'.$o->id)),
                    'type' => 'invoice',
                    'description' => 'Sales Invoice',
                    'debit' => $amount,
                    'credit' => 0.0,
                ];
            });

        $payments = Payment::query()
            ->where('customer_id', $customerId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->when($branchId && Schema::hasColumn('payments', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
            ->get(['id', 'created_at', 'amount', 'amount_minor', 'trx_id', 'payment_method'])
            ->map(function (Payment $p) {
                $amount = (int) ($p->amount_minor ?? 0) > 0 ? round(((int) $p->amount_minor) / 100, 2) : round((float) $p->amount, 2);
                $method = trim((string) ($p->payment_method ?? ''));

                return [
                    'date' => optional($p->created_at)->toDateTimeString(),
                    'ref' => 'MR-'.$p->id,
                    'type' => 'receipt',
                    'description' => trim('Money Receipt'.($method !== '' ? " ({$method})" : '')),
                    'debit' => 0.0,
                    'credit' => $amount,
                ];
            });

        $refunds = collect();
        if (Schema::hasTable('return_orders')) {
            $refunds = ReturnOrder::query()
                ->where('customer_id', $customerId)
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ])
                ->when($branchId && Schema::hasColumn('return_orders', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
                ->get(['id', 'return_number', 'created_at', 'refund_amount'])
                ->map(function (ReturnOrder $r) {
                    $amount = round((float) ($r->refund_amount ?? 0), 2);

                    return [
                        'date' => optional($r->created_at)->toDateTimeString(),
                        'ref' => (string) ($r->return_number ?? ('RETURN-'.$r->id)),
                        'type' => 'refund',
                        'description' => 'Sales Return / Refund',
                        'debit' => 0.0,
                        'credit' => $amount,
                    ];
                });
        }

        return $orders
            ->concat($payments)
            ->concat($refunds)
            ->sortBy('date')
            ->values();
    }
}
