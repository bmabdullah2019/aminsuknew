<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetSupplierOpeningBalanceRequest;
use App\Http\Requests\SupplierAdjustmentsOverviewFilterRequest;
use App\Http\Requests\SupplierLedgerFilterRequest;
use App\Http\Requests\SupplierPaymentsFilterRequest;
use App\Http\Requests\SupplierPaymentsOverviewFilterRequest;
use App\Http\Requests\SupplierPurchaseReturnActionRequest;
use App\Http\Requests\SupplierPurchaseReturnsFilterRequest;
use App\Http\Requests\SupplierPurchaseReturnsOverviewFilterRequest;
use App\Http\Requests\StoreSupplierOverviewPurchaseReturnRequest;
use App\Http\Requests\StoreSupplierPurchaseReturnRequest;
use App\Http\Requests\SupplierReportsQueryRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\StoreSupplierAdjustmentRequest;
use App\Http\Requests\StoreSupplierOverviewPaymentRequest;
use App\Http\Requests\StoreSupplierPaymentRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Accounts\AccountHead;
use App\Models\Branch;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PaymentHeadMapping;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use App\Models\SupplierPurchaseReturn;
use App\Models\Warehouse;
use App\Services\SupplierService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    private const MAX_REPORT_RANGE_DAYS = 730;

    public function __construct(protected SupplierService $supplierService)
    {
        $this->middleware('permission:supplier-list', ['only' => ['index']]);
        $this->middleware('permission:supplier-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:supplier-view', ['only' => ['show']]);
        $this->middleware('permission:supplier-edit', ['only' => ['edit', 'update', 'data']]);
        $this->middleware('permission:supplier-delete', ['only' => ['destroy']]);
        $this->middleware('permission:supplier-opening-balance', ['only' => ['setOpeningBalance']]);
        $this->middleware('permission:supplier-ledger|supplier-report-ledger', ['only' => ['ledger']]);
        $this->middleware('permission:supplier-payment-list', ['only' => ['payments', 'paymentsOverview']]);
        $this->middleware('permission:supplier-payment-create', ['only' => ['createPayment', 'storePayment', 'storeOverviewPayment']]);
        $this->middleware('permission:supplier-return-list', ['only' => ['purchaseReturns', 'purchaseReturnsOverview']]);
        $this->middleware('permission:supplier-return-create', ['only' => ['createPurchaseReturn', 'storePurchaseReturn', 'purchaseReturnFormData', 'storeOverviewPurchaseReturn']]);
        $this->middleware('permission:supplier-return-approve', ['only' => ['approvePurchaseReturn', 'completePurchaseReturn']]);
        $this->middleware('permission:supplier-report-aging|supplier-report-dues|supplier-report-performance', ['only' => ['reports']]);
    }

    /**
     * Display a listing of suppliers
     */
    public function index(Request $request)
    {
        $query = Supplier::query()
            ->withComputedBalance()
            ->with('latestOpeningBalance');

        $status = (string) $request->input('status', '');
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('supplier_code', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($request->boolean('with_dues')) {
            $query->withDues();
        }

        if ($request->boolean('over_credit_limit')) {
            $query->overCreditLimit();
        }

        $suppliers = $query->paginate(50)->appends($request->query());
        $nextSupplierCode = $this->nextSupplierCode();

        return view('backEnd.supplier.index', compact('suppliers', 'nextSupplierCode'));
    }

    /**
     * Show the form for creating a new supplier
     */
    public function create()
    {
        return view('backEnd.supplier.create');
    }

    /**
     * Store a newly created supplier
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? 'active';

        $supplier = Supplier::create($validated);

        if (! empty($validated['opening_date']) && (float) ($validated['opening_balance'] ?? 0) > 0) {
            $this->supplierService->setOpeningBalance($supplier, [
                'opening_date' => $validated['opening_date'],
                'opening_balance' => (float) $validated['opening_balance'],
                'balance_type' => 'debit',
                'description' => 'Opening Balance',
                'created_by' => $this->requireAuthenticatedUserId(),
            ]);
            $supplier->load('latestOpeningBalance');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Supplier created successfully.',
                'supplier' => $this->serializeSupplier($supplier->fresh()->load('latestOpeningBalance')),
            ]);
        }

        Toastr::success('Supplier created successfully');

        return redirect()->route('admin.supplier.show', $supplier->id);
    }

    /**
     * Display the specified supplier
     */
    public function show(Supplier $supplier)
    {
        $supplier->load([
            'openingBalances',
            'ledger' => function ($q) {
                $q->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->limit(10);
            },
            'payments' => function ($q) {
                $q->latest('payment_date')->limit(5);
            },
            'purchaseReturns' => function ($q) {
                $q->latest('return_date')->limit(5);
            },
        ]);

        $agingSummary = $supplier->getAgingSummary();
        $ledgerSummary = $this->supplierService->getSupplierLedgerSummary($supplier);

        return view('backEnd.supplier.show', compact('supplier', 'agingSummary', 'ledgerSummary'));
    }

    /**
     * Show the form for editing the specified supplier
     */
    public function edit(Supplier $supplier)
    {
        return view('backEnd.supplier.edit', compact('supplier'));
    }

    public function data(Supplier $supplier)
    {
        $supplier->load('latestOpeningBalance');

        return response()->json([
            'supplier' => $this->serializeSupplier($supplier),
        ]);
    }

    /**
     * Update the specified supplier
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? $supplier->status;

        $supplier->update($validated);

        if (! empty($validated['opening_date']) && (float) ($validated['opening_balance'] ?? 0) > 0) {
            $this->supplierService->setOpeningBalance($supplier, [
                'opening_date' => $validated['opening_date'],
                'opening_balance' => (float) $validated['opening_balance'],
                'balance_type' => 'debit',
                'description' => 'Opening Balance',
                'created_by' => $this->requireAuthenticatedUserId(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Supplier updated successfully.',
                'supplier' => $this->serializeSupplier($supplier->fresh()->load('latestOpeningBalance')),
            ]);
        }

        Toastr::success('Supplier updated successfully');

        return redirect()->route('admin.supplier.show', $supplier->id);
    }

    /**
     * Remove the specified supplier
     */
    public function destroy(Supplier $supplier)
    {
        try {
            DB::transaction(function () use ($supplier) {
                $lockedSupplier = Supplier::query()
                    ->whereKey($supplier->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $hasTransactions = $lockedSupplier->ledger()->exists()
                    || $lockedSupplier->payments()->exists()
                    || $lockedSupplier->purchaseReturns()->exists()
                    || $lockedSupplier->openingBalances()->exists();

                if ($hasTransactions) {
                    throw new \DomainException('Cannot delete supplier with financial transaction history.');
                }

                $lockedSupplier->delete();
            });
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage());

            return redirect()->back();
        } catch (\Throwable $e) {
            report($e);
            Toastr::error('Failed to delete supplier. Please try again.');

            return redirect()->back();
        }

        Toastr::success('Supplier deleted successfully');

        return redirect()->route('admin.supplier.index');
    }

    /**
     * Set opening balance for supplier
     */
    public function setOpeningBalance(SetSupplierOpeningBalanceRequest $request, Supplier $supplier)
    {
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'set opening balance')) {
            return $redirect;
        }

        $validated = $request->validated();

        $validated['created_by'] = $this->requireAuthenticatedUserId();

        try {
            $this->supplierService->setOpeningBalance($supplier, $validated);
        } catch (\Throwable $e) {
            report($e);
            Toastr::error('Failed to set opening balance. Please try again.');

            return redirect()->back()->withInput();
        }

        Toastr::success('Opening balance set successfully');

        return redirect()->route('admin.supplier.show', $supplier->id);
    }

    /**
     * Display supplier ledger
     */
    public function ledger(SupplierLedgerFilterRequest $request, Supplier $supplier)
    {
        $filters = $request->validated();

        $query = $supplier->ledger()->with(['creator']);
        $this->applyDateWindowFilter(
            $query,
            'transaction_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        if (! empty($filters['transaction_type'])) {
            $query->byType($filters['transaction_type']);
        }

        $ledger = $query->paginate(50)->appends($request->query());

        return view('backEnd.supplier.ledger', compact('supplier', 'ledger'));
    }

    /**
     * Display supplier payments
     */
    public function payments(SupplierPaymentsFilterRequest $request, Supplier $supplier)
    {
        $filters = $request->validated();

        $query = $supplier->payments()->with(['creator']);
        $this->applyDateWindowFilter(
            $query,
            'payment_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        if (! empty($filters['payment_method'])) {
            $query->byMethod($filters['payment_method']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $payments = $query->paginate(50)->appends($request->query());

        $summaryQuery = $supplier->payments()->reorder();
        $this->applyDateWindowFilter(
            $summaryQuery,
            'payment_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );
        if (! empty($filters['payment_method'])) {
            $summaryQuery->where('payment_method', $filters['payment_method']);
        }
        if (! empty($filters['status'])) {
            $summaryQuery->where('status', $filters['status']);
        }

        $summaryRaw = $summaryQuery
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count
            ")
            ->first();

        $paymentSummary = [
            'total_paid' => (float) ($summaryRaw->total_paid ?? 0),
            'pending_count' => (int) ($summaryRaw->pending_count ?? 0),
            'cancelled_count' => (int) ($summaryRaw->cancelled_count ?? 0),
        ];

        return view('backEnd.supplier.payments', compact('supplier', 'payments', 'paymentSummary'));
    }

    /**
     * Display all supplier payments (global overview).
     */
    public function paymentsOverview(SupplierPaymentsOverviewFilterRequest $request)
    {
        $supportsBranchFilter = Schema::hasColumn('supplier_payments', 'branch_id');

        $filters = $request->validated();

        $query = SupplierPayment::query()
            ->with([
                'supplier:id,name,supplier_code',
                'branch:id,name,code',
                'accountHead:HeadId,HeadCode,HeadName',
                'creator:id,name',
            ])
            ->latest('payment_date')
            ->latest('id');

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if ($supportsBranchFilter && ! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['account_head_id'])) {
            $query->where('account_head_id', (int) $filters['account_head_id']);
        }

        $this->applyDateWindowFilter(
            $query,
            'payment_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $payments = $query->paginate(50)->appends($request->query());

        $summaryQuery = SupplierPayment::query();
        if (! empty($filters['supplier_id'])) {
            $summaryQuery->where('supplier_id', (int) $filters['supplier_id']);
        }
        if ($supportsBranchFilter && ! empty($filters['branch_id'])) {
            $summaryQuery->where('branch_id', (int) $filters['branch_id']);
        }
        if (! empty($filters['account_head_id'])) {
            $summaryQuery->where('account_head_id', (int) $filters['account_head_id']);
        }
        $this->applyDateWindowFilter(
            $summaryQuery,
            'payment_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );
        if (! empty($filters['payment_method'])) {
            $summaryQuery->where('payment_method', $filters['payment_method']);
        }
        if (! empty($filters['status'])) {
            $summaryQuery->where('status', $filters['status']);
        }

        $summaryRaw = $summaryQuery
            ->selectRaw("
                COUNT(*) as total_rows,
                COALESCE(SUM(amount), 0) as gross_amount,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_amount,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount
            ")
            ->first();

        $summary = [
            'total_rows' => (int) ($summaryRaw->total_rows ?? 0),
            'gross_amount' => (float) ($summaryRaw->gross_amount ?? 0),
            'completed_amount' => (float) ($summaryRaw->completed_amount ?? 0),
            'pending_amount' => (float) ($summaryRaw->pending_amount ?? 0),
        ];

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'supplier_code']);
        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);
        $accountHeads = AccountHead::query()
            ->valid()
            ->leaves()
            ->orderBy('HeadCode')
            ->get(['HeadId', 'HeadCode', 'HeadName']);
        // Fetch mappings across all branches
        $paymentHeadMapsByBranch = [
            'global' => $this->supplierPaymentHeadMap(null)
        ];
        foreach ($branches as $b) {
            $paymentHeadMapsByBranch[$b->id] = $this->supplierPaymentHeadMap($b->id);
        }

        return view('backEnd.supplier.payment-overview', compact('payments', 'summary', 'suppliers', 'branches', 'accountHeads', 'paymentHeadMapsByBranch'));
    }

    public function storeOverviewPayment(StoreSupplierOverviewPaymentRequest $request)
    {
        $validated = $request->validated();

        $supplier = Supplier::query()->findOrFail((int) $validated['supplier_id']);
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'record payments')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Supplier is inactive.'], 422);
            }

            return $redirect;
        }

        $paymentMethod = ($validated['payment_method'] ?? null) === 'other'
            ? 'online'
            : (string) ($validated['payment_method'] ?? '');
        $resolvedAccountHeadId = $this->resolveSupplierPaymentAccountHeadId(
            isset($validated['account_head_id']) ? (int) $validated['account_head_id'] : null,
            $paymentMethod,
            isset($validated['branch_id']) ? (int) $validated['branch_id'] : null
        );
        if (! $resolvedAccountHeadId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accounts Head is required (or configure payment-head mapping).'], 422);
            }

            return redirect()->back()->withInput()->withErrors([
                'account_head_id' => 'Accounts Head is required. You can set automatic mapping from Payment Head Mapping settings.',
            ]);
        }

        $payload = array_merge($validated, [
            'branch_id' => ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            'account_head_id' => $resolvedAccountHeadId,
            'payment_method' => $paymentMethod,
            'status' => $validated['status'] ?? 'completed',
            'created_by' => $this->requireAuthenticatedUserId(),
        ]);

        try {
            $payment = $this->supplierService->recordPayment($supplier, $payload);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Failed to record supplier payment. Please try again.'], 500);
            }

            Toastr::error('Failed to record supplier payment. Please try again.');

            return redirect()->back()->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Payment recorded successfully.',
                'payment_number' => $payment->payment_number,
            ]);
        }

        Toastr::success('Payment recorded successfully');

        return redirect()->route('admin.supplier.payments.overview');
    }

    /**
     * Show payment creation form
     */
    public function createPayment(Request $request, Supplier $supplier)
    {
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'record payments')) {
            return $redirect;
        }

        $branches = collect();
        if (Schema::hasTable('branches')) {
            $branches = Branch::query()
                ->orderByDesc('status')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'status']);
        }

        $accountHeads = AccountHead::query()
            ->valid()
            ->leaves()
            ->orderBy('HeadCode')
            ->get(['HeadId', 'HeadCode', 'HeadName']);

        $branchDueMap = $supplier->ledger()
            ->reorder()
            ->selectRaw('COALESCE(branch_id, 0) as branch_id')
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as due_amount')
            ->groupBy('branch_id')
            ->get()
            ->mapWithKeys(function ($row) {
                $branchId = (string) ((int) ($row->branch_id ?? 0));
                $dueAmount = round(max(0, (float) ($row->due_amount ?? 0)), 2);

                return [$branchId => $dueAmount];
            });

        $availableBranchIds = $branches
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $dueBranchIds = $branchDueMap
            ->filter(function ($amount, $branchId) use ($availableBranchIds) {
                $id = (int) $branchId;
                if ($id <= 0 || (float) $amount <= 0.01) {
                    return false;
                }

                if (empty($availableBranchIds)) {
                    return true;
                }

                return in_array($id, $availableBranchIds, true);
            })
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();

        $requestedBranchId = (int) $request->query('branch_id', 0);
        $selectedBranchId = 0;

        if (
            $requestedBranchId > 0
            && (empty($availableBranchIds) || in_array($requestedBranchId, $availableBranchIds, true))
        ) {
            $selectedBranchId = $requestedBranchId;
        } elseif ($dueBranchIds->isNotEmpty()) {
            $selectedBranchId = (int) $dueBranchIds->first();
        } elseif ($branches->isNotEmpty()) {
            $selectedBranchId = (int) $branches->first()->id;
        }

        $prefillAmount = round((float) $request->query('amount', 0), 2);
        if ($prefillAmount <= 0 && $selectedBranchId > 0) {
            $prefillAmount = round((float) ($branchDueMap->get((string) $selectedBranchId, 0) ?? 0), 2);
        }
        if ($selectedBranchId > 0) {
            $branchDue = round((float) ($branchDueMap->get((string) $selectedBranchId, 0) ?? 0), 2);
            if ($branchDue > 0) {
                $prefillAmount = min($prefillAmount, $branchDue);
            } else {
                $prefillAmount = 0;
            }
        }

        // Fetch mappings across all branches
        $paymentHeadMapsByBranch = [
            'global' => $this->supplierPaymentHeadMap(null)
        ];
        foreach ($branches as $b) {
            $paymentHeadMapsByBranch[$b->id] = $this->supplierPaymentHeadMap($b->id);
        }

        return view('backEnd.supplier.create-payment', compact(
            'supplier',
            'branches',
            'accountHeads',
            'branchDueMap',
            'selectedBranchId',
            'prefillAmount',
            'paymentHeadMapsByBranch'
        ));
    }

    /**
     * Store new payment
     */
    public function storePayment(StoreSupplierPaymentRequest $request, Supplier $supplier)
    {
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'record payments')) {
            return $redirect;
        }

        $validated = $request->validated();

        // Backward compatibility with existing form field names.
        $paymentMethod = ($validated['payment_method'] ?? null) === 'other'
            ? 'online'
            : $validated['payment_method'];
        $resolvedAccountHeadId = $this->resolveSupplierPaymentAccountHeadId(
            isset($validated['account_head_id']) ? (int) $validated['account_head_id'] : null,
            (string) $paymentMethod
        );
        if (! $resolvedAccountHeadId) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'account_head_id' => 'Accounts Head is required. You can set automatic mapping from Payment Head Mapping settings.',
                ]);
        }

        $payload = array_merge($validated, [
            'branch_id' => ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            'account_head_id' => $resolvedAccountHeadId,
            'payment_method' => $paymentMethod,
            'reference_number' => $validated['reference_number'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'status' => $validated['status'] ?? 'completed',
            'created_by' => $this->requireAuthenticatedUserId(),
        ]);

        try {
            $this->supplierService->recordPayment($supplier, $payload);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['amount' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);
            Toastr::error('Failed to record supplier payment. Please try again.');

            return redirect()->back()->withInput();
        }

        Toastr::success('Payment recorded successfully');

        return redirect()->route('admin.supplier.payments', $supplier->id);
    }

    /**
     * Display supplier purchase returns
     */
    public function purchaseReturns(SupplierPurchaseReturnsFilterRequest $request, Supplier $supplier)
    {
        $supportsReturnItems = Schema::hasTable('supplier_purchase_return_items');

        $filters = $request->validated();

        $with = ['creator', 'branch'];
        if ($supportsReturnItems) {
            $with[] = 'items';
        }

        $query = $supplier->purchaseReturns()->with($with);
        $this->applyDateWindowFilter(
            $query,
            'return_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['return_reason'])) {
            $query->byReason($this->normalizePurchaseReturnReason((string) $filters['return_reason']));
        }

        $returns = $query->paginate(50)->appends($request->query());

        $summaryQuery = $supplier->purchaseReturns()->reorder();
        $this->applyDateWindowFilter(
            $summaryQuery,
            'return_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );
        if (! empty($filters['status'])) {
            $summaryQuery->where('status', $filters['status']);
        }
        if (! empty($filters['return_reason'])) {
            $summaryQuery->where('return_reason', $this->normalizePurchaseReturnReason((string) $filters['return_reason']));
        }

        $summaryRaw = $summaryQuery
            ->selectRaw("
                COUNT(*) as total_returns,
                COALESCE(SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END), 0) as draft_count,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) as approved_count,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
                COALESCE(SUM(CASE WHEN status IN ('approved', 'completed') THEN total_amount ELSE 0 END), 0) as total_value
            ")
            ->first();

        $returnSummary = [
            'total_returns' => (int) ($summaryRaw->total_returns ?? 0),
            'draft_count' => (int) ($summaryRaw->draft_count ?? 0),
            'approved_count' => (int) ($summaryRaw->approved_count ?? 0),
            'completed_count' => (int) ($summaryRaw->completed_count ?? 0),
            'total_value' => (float) ($summaryRaw->total_value ?? 0),
        ];

        return view('backEnd.supplier.purchase-returns', compact('supplier', 'returns', 'returnSummary', 'supportsReturnItems'));
    }

    /**
     * Display all purchase returns (global overview).
     */
    public function purchaseReturnsOverview(SupplierPurchaseReturnsOverviewFilterRequest $request)
    {
        $supportsReturnItems = Schema::hasTable('supplier_purchase_return_items');
        $supportsBranchFilter = Schema::hasColumn('supplier_purchase_returns', 'branch_id');

        $filters = $request->validated();

        $with = [
            'supplier:id,name,supplier_code',
            'branch:id,name,code',
            'creator:id,name',
        ];
        if ($supportsReturnItems) {
            $with[] = 'items';
        }

        $query = SupplierPurchaseReturn::query()
            ->with($with)
            ->latest('return_date')
            ->latest('id');

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if ($supportsBranchFilter && ! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        $this->applyDateWindowFilter(
            $query,
            'return_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['return_reason'])) {
            $query->where('return_reason', $this->normalizePurchaseReturnReason((string) $filters['return_reason']));
        }

        $returns = $query->paginate(50)->appends($request->query());

        $summaryQuery = SupplierPurchaseReturn::query();
        if (! empty($filters['supplier_id'])) {
            $summaryQuery->where('supplier_id', (int) $filters['supplier_id']);
        }
        if ($supportsBranchFilter && ! empty($filters['branch_id'])) {
            $summaryQuery->where('branch_id', (int) $filters['branch_id']);
        }
        $this->applyDateWindowFilter(
            $summaryQuery,
            'return_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );
        if (! empty($filters['status'])) {
            $summaryQuery->where('status', $filters['status']);
        }
        if (! empty($filters['return_reason'])) {
            $summaryQuery->where('return_reason', $this->normalizePurchaseReturnReason((string) $filters['return_reason']));
        }

        $summaryRaw = $summaryQuery
            ->selectRaw("
                COUNT(*) as total_rows,
                COALESCE(SUM(total_amount), 0) as gross_value,
                COALESCE(SUM(CASE WHEN status IN ('approved', 'completed') THEN total_amount ELSE 0 END), 0) as effective_value
            ")
            ->first();

        $summary = [
            'total_rows' => (int) ($summaryRaw->total_rows ?? 0),
            'gross_value' => (float) ($summaryRaw->gross_value ?? 0),
            'effective_value' => (float) ($summaryRaw->effective_value ?? 0),
        ];

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'supplier_code']);
        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('backEnd.supplier.purchase-return-overview', compact(
            'returns',
            'summary',
            'suppliers',
            'branches',
            'supportsReturnItems'
        ));
    }

    public function adjustmentsOverview(SupplierAdjustmentsOverviewFilterRequest $request)
    {
        $filters = $request->validated();

        $query = SupplierLedger::query()
            ->with([
                'supplier:id,name,supplier_code',
                'branch:id,name,code',
                'creator:id,name',
            ])
            ->where('transaction_type', 'adjustment')
            ->latest('transaction_date')
            ->latest('id');

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (Schema::hasColumn('supplier_ledgers', 'branch_id') && ! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        $this->applyDateWindowFilter(
            $query,
            'transaction_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $adjustments = $query->paginate(50)->appends($request->query());

        $summaryQuery = SupplierLedger::query()->where('transaction_type', 'adjustment');
        if (! empty($filters['supplier_id'])) {
            $summaryQuery->where('supplier_id', (int) $filters['supplier_id']);
        }
        if (Schema::hasColumn('supplier_ledgers', 'branch_id') && ! empty($filters['branch_id'])) {
            $summaryQuery->where('branch_id', (int) $filters['branch_id']);
        }
        $this->applyDateWindowFilter(
            $summaryQuery,
            'transaction_date',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $summaryRaw = $summaryQuery
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $summary = [
            'total_rows' => (int) ($summaryRaw->total_rows ?? 0),
            'total_debit' => (float) ($summaryRaw->total_debit ?? 0),
            'total_credit' => (float) ($summaryRaw->total_credit ?? 0),
        ];

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'supplier_code']);
        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('backEnd.supplier.adjustments-overview', compact('adjustments', 'summary', 'suppliers', 'branches'));
    }

    public function storeAdjustment(StoreSupplierAdjustmentRequest $request)
    {
        $validated = $request->validated();

        $supplier = Supplier::query()->findOrFail((int) $validated['supplier_id']);
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'record adjustments')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Supplier is inactive.'], 422);
            }

            return $redirect;
        }

        $direction = $validated['direction'] ?? 'credit';
        $amount = round((float) $validated['amount'], 2);
        $referenceNumber = $this->nextAdjustmentNumber();

        $supplier->addLedgerEntry(
            'adjustment',
            $direction === 'debit' ? $amount : 0,
            $direction === 'credit' ? $amount : 0,
            [
                'branch_id' => ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null,
                'transaction_date' => $validated['adjustment_date'],
                'reference_type' => 'supplier_adjustment',
                'reference_number' => $referenceNumber,
                'description' => trim($validated['reason'].(! empty($validated['notes']) ? ' | '.$validated['notes'] : '')),
                'created_by' => $this->requireAuthenticatedUserId(),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Supplier adjustment recorded successfully.',
                'reference_number' => $referenceNumber,
            ]);
        }

        Toastr::success('Supplier adjustment recorded successfully');

        return redirect()->route('admin.supplier.adjustments.index');
    }

    /**
     * Create purchase return
     */
    public function createPurchaseReturn(Supplier $supplier)
    {
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'create purchase returns')) {
            return $redirect;
        }

        return redirect()->route('admin.supplier.purchase-returns.overview', [
            'supplier_modal' => $supplier->id,
        ]);
    }

    public function purchaseReturnFormData(Supplier $supplier): JsonResponse
    {
        if ($supplier->status !== 'active') {
            return response()->json([
                'message' => 'Supplier is inactive.',
            ], 422);
        }

        return response()->json(
            $this->serializePurchaseReturnFormData($supplier)
        );
    }

    public function storeOverviewPurchaseReturn(StoreSupplierOverviewPurchaseReturnRequest $request)
    {
        $supplier = $request->supplier();
        abort_unless($supplier !== null, 404);
        if ($supplier->status !== 'active') {
            return response()->json([
                'message' => 'Supplier is inactive.',
            ], 422);
        }

        $validated = $request->purchaseReturnData();

        try {
            $purchaseReturn = $this->persistPurchaseReturn($supplier, $validated);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to record purchase return. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'Purchase return recorded successfully.',
            'id' => $purchaseReturn->id,
            'return_number' => $purchaseReturn->return_number,
        ]);
    }

    /**
     * Store purchase return
     */
    public function storePurchaseReturn(StoreSupplierPurchaseReturnRequest $request, Supplier $supplier)
    {
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'create purchase returns')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Supplier is inactive.'], 422);
            }

            return $redirect;
        }

        $validated = $request->purchaseReturnData();

        try {
            $purchaseReturn = $this->persistPurchaseReturn($supplier, $validated);
        } catch (\Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to record purchase return. Please try again.',
                ], 500);
            }

            Toastr::error('Failed to record purchase return. Please try again.');

            return redirect()->back()->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Purchase return recorded successfully.',
                'id' => $purchaseReturn->id,
                'return_number' => $purchaseReturn->return_number,
            ]);
        }

        Toastr::success('Purchase return recorded successfully');

        return redirect()->route('admin.supplier.purchase-returns', $supplier->id);
    }

    /**
     * Approve supplier purchase return.
     */
    public function approvePurchaseReturn(SupplierPurchaseReturnActionRequest $request, Supplier $supplier, SupplierPurchaseReturn $purchaseReturn)
    {
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'approve purchase returns')) {
            return $redirect;
        }

        $this->ensurePurchaseReturnBelongsToSupplier($supplier, $purchaseReturn);

        $validated = $request->validated();

        try {
            $this->supplierService->approvePurchaseReturn(
                $purchaseReturn,
                $this->requireAuthenticatedUserId(),
                $validated['notes'] ?? null
            );
        } catch (\Throwable $e) {
            report($e);
            Toastr::error('Failed to approve purchase return. Please try again.');

            return redirect()->back();
        }

        Toastr::success('Purchase return approved successfully');

        return redirect()->route('admin.supplier.purchase-returns', $supplier->id);
    }

    /**
     * Complete supplier purchase return.
     */
    public function completePurchaseReturn(SupplierPurchaseReturnActionRequest $request, Supplier $supplier, SupplierPurchaseReturn $purchaseReturn)
    {
        if ($redirect = $this->ensureSupplierIsActive($supplier, 'complete purchase returns')) {
            return $redirect;
        }

        $this->ensurePurchaseReturnBelongsToSupplier($supplier, $purchaseReturn);

        $validated = $request->validated();

        try {
            $this->supplierService->completePurchaseReturn(
                $purchaseReturn,
                $this->requireAuthenticatedUserId(),
                $validated['notes'] ?? null
            );
        } catch (\Throwable $e) {
            report($e);
            Toastr::error('Failed to complete purchase return. Please try again.');

            return redirect()->back();
        }

        Toastr::success('Purchase return completed successfully');

        return redirect()->route('admin.supplier.purchase-returns', $supplier->id);
    }

    /**
     * Supplier reports
     */
    public function reports(SupplierReportsQueryRequest $request)
    {
        $reportQuery = $request->queryData();
        if ($reportQuery->shouldRedirectToCanonicalRoute()) {
            return redirect()->route('admin.supplier.reports', $reportQuery->canonicalQuery());
        }
        $reportType = $reportQuery->type;
        $this->ensureReportPermission($reportType);

        $data = [];
        $suppliers = null;
        $agingRows = collect();
        $performanceSuppliers = collect();

        switch ($reportType) {
            case 'aging':
                $data = $this->supplierService->getSupplierAgingReport();
                $agingRows = $this->buildAgingRows();
                break;

            case 'performance':
                $data = $this->supplierService->getSupplierPerformanceMetrics();
                $performanceSuppliers = $this->buildPerformanceSuppliers();
                break;

            case 'dues':
                try {
                    $suppliers = Supplier::active()
                        ->withDues()
                        ->withComputedBalance()
                        ->withMax([
                            'payments as last_completed_payment_date' => function ($query) {
                                $query->where('status', 'completed');
                            },
                        ], 'payment_date')
                        ->paginate(50)
                        ->appends($request->query());
                } catch (\Exception $e) {
                    report($e);

                    // Fallback: get all suppliers and filter in PHP
                    $filteredSuppliers = Supplier::active()
                        ->withComputedBalance()
                        ->withMax([
                            'payments as last_completed_payment_date' => function ($query) {
                                $query->where('status', 'completed');
                            },
                        ], 'payment_date')
                        ->get()
                        ->filter(function ($supplier) {
                            return $supplier->total_dues > 0;
                        })->values();

                    $perPage = 50;
                    $currentPage = LengthAwarePaginator::resolveCurrentPage();

                    $suppliers = new LengthAwarePaginator(
                        $filteredSuppliers->forPage($currentPage, $perPage),
                        $filteredSuppliers->count(),
                        $perPage,
                        $currentPage,
                        [
                            'path' => request()->url(),
                            'query' => request()->query(),
                        ]
                    );
                }
                break;
        }

        if ($reportQuery->shouldExport()) {
            if ($reportQuery->exportsAsXlsx()) {
                return $this->exportReportsExcel($reportType, $data);
            }

            return $this->exportReportsCsv($reportType, $data);
        }

        return view('backEnd.supplier.reports', compact(
            'reportType',
            'data',
            'suppliers',
            'agingRows',
            'performanceSuppliers',
            'reportQuery'
        ));
    }

    private function requireAuthenticatedUserId(): int
    {
        $userId = auth()->id();
        if (! $userId) {
            abort(403, 'Authenticated user is required to perform this action.');
        }

        return (int) $userId;
    }

    private function getPurchaseReturnFormResources(Supplier $supplier): array
    {
        $recentReturns = $supplier->purchaseReturns()
            ->with('creator')
            ->latest('return_date')
            ->take(5)
            ->get();

        $purchaseOrders = collect();
        $returnableItems = collect();
        $warehouses = collect();

        if (Schema::hasTable('warehouses')) {
            $warehouses = Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'branch_id']);
        }

        if (Schema::hasTable('purchase_orders')) {
            $purchaseOrders = PurchaseOrder::query()
                ->where('supplier_id', $supplier->id)
                ->whereNotIn('status', ['draft', 'pending', 'cancelled'])
                ->orderByDesc('ordered_at')
                ->orderByDesc('id')
                ->limit(100)
                ->get(['id', 'po_number', 'status', 'total_cost', 'ordered_at', 'warehouse_id', 'branch_id']);
        }

        if (Schema::hasTable('purchase_items') && Schema::hasTable('purchase_orders')) {
            $returnableItems = PurchaseItem::query()
                ->with([
                    'productVariant.product:id,name',
                    'purchaseOrder:id,po_number,warehouse_id,supplier_id',
                ])
                ->where('quantity_received', '>', 0)
                ->whereHas('purchaseOrder', function ($query) use ($supplier) {
                    $query->where('supplier_id', $supplier->id)
                        ->whereNotIn('status', ['draft', 'pending', 'cancelled']);
                })
                ->orderByDesc('id')
                ->limit(500)
                ->get([
                    'id',
                    'purchase_order_id',
                    'product_variant_id',
                    'quantity_ordered',
                    'quantity_received',
                    'unit_cost',
                ]);
        }

        return compact('recentReturns', 'purchaseOrders', 'returnableItems', 'warehouses');
    }

    private function serializePurchaseReturnFormData(Supplier $supplier): array
    {
        $resources = $this->getPurchaseReturnFormResources($supplier);
        $recentReturns = $resources['recentReturns'];
        $purchaseOrders = $resources['purchaseOrders'];
        $returnableItems = $resources['returnableItems'];
        $warehouses = $resources['warehouses'];

        return [
            'supplier' => [
                'id' => (int) $supplier->id,
                'name' => (string) $supplier->name,
                'supplier_code' => (string) ($supplier->supplier_code ?? ''),
                'current_balance' => (float) ($supplier->current_balance ?? 0),
            ],
            'purchase_orders' => $purchaseOrders->map(function ($purchaseOrder) {
                return [
                    'id' => (int) $purchaseOrder->id,
                    'po_number' => (string) $purchaseOrder->po_number,
                    'status' => (string) $purchaseOrder->status,
                    'total_cost' => (float) ($purchaseOrder->total_cost ?? 0),
                    'ordered_at' => optional($purchaseOrder->ordered_at)->format('Y-m-d'),
                    'warehouse_id' => $purchaseOrder->warehouse_id ? (int) $purchaseOrder->warehouse_id : null,
                    'branch_id' => $purchaseOrder->branch_id ? (int) $purchaseOrder->branch_id : null,
                    'label' => trim(sprintf(
                        '%s | %s | BDT %s',
                        (string) $purchaseOrder->po_number,
                        ucfirst((string) $purchaseOrder->status),
                        number_format((float) ($purchaseOrder->total_cost ?? 0), 2, '.', '')
                    )),
                ];
            })->values(),
            'returnable_items' => $returnableItems->map(function ($returnableItem) {
                $po = $returnableItem->purchaseOrder;
                $variant = $returnableItem->productVariant;
                $productName = $variant?->product?->name ?? 'Product';
                $variantLabel = $variant?->name ?? ('Variant #'.$returnableItem->product_variant_id);
                $receivedQty = (float) ($returnableItem->quantity_received ?? 0);

                return [
                    'id' => (int) $returnableItem->id,
                    'purchase_order_id' => $po?->id ? (int) $po->id : null,
                    'warehouse_id' => $po?->warehouse_id ? (int) $po->warehouse_id : null,
                    'quantity_received' => $receivedQty,
                    'unit_cost' => (float) ($returnableItem->unit_cost ?? 0),
                    'label' => sprintf(
                        '%s | %s (%s) | Recv: %s',
                        (string) ($po?->po_number ?? 'PO'),
                        $productName,
                        $variantLabel,
                        number_format($receivedQty, 2, '.', '')
                    ),
                ];
            })->values(),
            'warehouses' => $warehouses->map(function ($warehouse) {
                return [
                    'id' => (int) $warehouse->id,
                    'code' => (string) ($warehouse->code ?? ''),
                    'name' => (string) ($warehouse->name ?? ''),
                    'branch_id' => $warehouse->branch_id ? (int) $warehouse->branch_id : null,
                    'label' => trim((string) ($warehouse->code ? $warehouse->code.' - ' : '').$warehouse->name),
                ];
            })->values(),
            'recent_returns' => $recentReturns->map(function ($return) {
                return [
                    'return_number' => (string) $return->return_number,
                    'return_date' => optional($return->return_date)->format('d M Y'),
                    'total_amount' => (float) ($return->total_amount ?? 0),
                    'status' => (string) $return->status,
                ];
            })->values(),
        ];
    }

    private function persistPurchaseReturn(Supplier $supplier, array $validated): SupplierPurchaseReturn
    {
        $reason = (string) ($validated['return_reason'] ?? $validated['reason'] ?? 'other');
        $normalizedReason = $this->normalizePurchaseReturnReason($reason);
        $requestedStatus = (string) ($validated['status'] ?? 'draft');
        if (in_array($requestedStatus, ['approved', 'completed'], true)
            && ! auth()->user()?->can('supplier-return-approve')) {
            $requestedStatus = 'draft';
        }

        $payload = array_merge($validated, [
            'return_reason' => $normalizedReason ?? 'other',
            'status' => $requestedStatus,
            'created_by' => $this->requireAuthenticatedUserId(),
        ]);
        unset($payload['reason']);

        return $this->supplierService->recordPurchaseReturn($supplier, $payload);
    }

    private function normalizePurchaseReturnReason(string $reason): string
    {
        return match ($reason) {
            'damaged_goods' => 'damaged',
            'over_supplied' => 'over_supply',
            'expired' => 'other',
            default => $reason,
        };
    }

    private function ensurePurchaseReturnBelongsToSupplier(Supplier $supplier, SupplierPurchaseReturn $purchaseReturn): void
    {
        if ((int) $purchaseReturn->supplier_id !== (int) $supplier->id) {
            abort(404);
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

    private function applyDateWindowFilter(
        Builder|Relation $query,
        string $column,
        ?string $startDate,
        ?string $endDate
    ): void {
        if ($startDate && $endDate) {
            $this->guardDateRangeWindow($startDate, $endDate);
            $query->whereBetween($column, [$startDate, $endDate]);

            return;
        }

        if ($startDate) {
            $query->whereDate($column, '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate($column, '<=', $endDate);
        }
    }

    private function ensureSupplierIsActive(Supplier $supplier, string $action)
    {
        if ($supplier->status === 'active') {
            return null;
        }

        Toastr::error("Supplier is inactive. Please activate the supplier before attempting to {$action}.");

        return redirect()->route('admin.supplier.show', $supplier->id);
    }

    private function ensureReportPermission(string $reportType): void
    {
        $permission = match ($reportType) {
            'aging' => 'supplier-report-aging',
            'dues' => 'supplier-report-dues',
            'performance' => 'supplier-report-performance',
            default => 'supplier-report-aging',
        };

        abort_unless(auth()->user()?->can($permission), 403);
    }

    private function serializeSupplier(Supplier $supplier): array
    {
        $openingBalance = $supplier->latestOpeningBalance;

        return [
            'id' => (int) $supplier->id,
            'supplier_code' => (string) $supplier->supplier_code,
            'name' => (string) $supplier->name,
            'email' => (string) ($supplier->email ?? ''),
            'phone' => (string) ($supplier->phone ?? ''),
            'mobile' => (string) ($supplier->mobile ?? ''),
            'address' => (string) ($supplier->address ?? ''),
            'contact_person' => (string) ($supplier->contact_person ?? ''),
            'notes' => (string) ($supplier->notes ?? ''),
            'status' => (string) ($supplier->status ?? 'active'),
            'opening_date' => $openingBalance?->opening_date ? $openingBalance->opening_date->format('Y-m-d') : '',
            'opening_balance' => $openingBalance ? (float) $openingBalance->opening_balance : 0,
        ];
    }

    private function nextSupplierCode(): string
    {
        $max = Supplier::query()
            ->pluck('supplier_code')
            ->map(function ($code) {
                if (preg_match('/(\d+)$/', (string) $code, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max();

        return 'SU-'.str_pad((string) (($max ?: 0) + 1), 5, '0', STR_PAD_LEFT);
    }

    private function nextAdjustmentNumber(): string
    {
        return 'ADJ-'.now()->format('Ymd-His');
    }

    private function buildAgingRows(): Collection
    {
        $suppliers = Supplier::active()
            ->with([
                'ledger' => function ($query) {
                    $query->select('id', 'supplier_id', 'transaction_date', 'created_at', 'debit', 'credit')
                        ->orderBy('transaction_date')
                        ->orderBy('created_at')
                        ->orderBy('id');
                },
            ])
            ->orderBy('name')
            ->get();

        return $suppliers
            ->map(function (Supplier $supplier) {
                return [
                    'supplier' => $supplier,
                    'aging' => $supplier->getAgingSummary(),
                ];
            })
            ->filter(function (array $row) {
                return (float) ($row['aging']['total'] ?? 0) > 0;
            })
            ->values();
    }

    private function buildPerformanceSuppliers(): Collection
    {
        return Supplier::active()
            ->withComputedBalance()
            ->withSum([
                'ledger as total_purchases_amount' => function ($query) {
                    $query->where('transaction_type', 'purchase');
                },
            ], 'debit')
            ->withSum([
                'purchaseReturns as approved_returns_amount' => function ($query) {
                    $query->whereIn('status', ['approved', 'completed']);
                },
            ], 'total_amount')
            ->orderBy('name')
            ->get();
    }

    private function exportReportsCsv(string $reportType, array $data)
    {
        $filename = 'supplier-'.$reportType.'-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($reportType, $data) {
            $handle = fopen('php://output', 'w');

            switch ($reportType) {
                case 'aging':
                    fputcsv($handle, ['Bucket', 'Amount']);
                    fputcsv($handle, ['Current', $data['current'] ?? 0]);
                    fputcsv($handle, ['Overdue 1-30', $data['overdue_1_30'] ?? 0]);
                    fputcsv($handle, ['Overdue 31-60', $data['overdue_31_60'] ?? 0]);
                    fputcsv($handle, ['Overdue 61-90', $data['overdue_61_90'] ?? 0]);
                    fputcsv($handle, ['Overdue 90+', $data['overdue_90_plus'] ?? 0]);
                    fputcsv($handle, []);
                    fputcsv($handle, ['Supplier Code', 'Supplier Name', 'Current', 'Overdue 1-30', 'Overdue 31-60', 'Overdue 61-90', 'Overdue 90+', 'Total']);

                    Supplier::active()->withBalance()->chunkById(200, function ($chunk) use ($handle) {
                        foreach ($chunk as $supplier) {
                            $aging = $supplier->getAgingSummary();
                            if (($aging['total'] ?? 0) <= 0) {
                                continue;
                            }

                            fputcsv($handle, [
                                $supplier->supplier_code,
                                $supplier->name,
                                $aging['current'] ?? 0,
                                $aging['overdue_1_30'] ?? 0,
                                $aging['overdue_31_60'] ?? 0,
                                $aging['overdue_61_90'] ?? 0,
                                $aging['overdue_90_plus'] ?? 0,
                                $aging['total'] ?? 0,
                            ]);
                        }
                    });
                    break;

                case 'dues':
                    fputcsv($handle, ['Supplier Code', 'Supplier Name', 'Email', 'Phone', 'Outstanding Amount', 'Payment Terms (Days)', 'Payment Status']);

                    Supplier::active()->withDues()->withBalance()->chunkById(200, function ($chunk) use ($handle) {
                        foreach ($chunk as $supplier) {
                            fputcsv($handle, [
                                $supplier->supplier_code,
                                $supplier->name,
                                $supplier->email,
                                $supplier->phone,
                                $supplier->total_dues,
                                $supplier->payment_terms_days,
                                $supplier->payment_status,
                            ]);
                        }
                    });
                    break;

                case 'performance':
                    fputcsv($handle, ['Metric', 'Value']);
                    fputcsv($handle, ['Total Suppliers', $data['total_suppliers'] ?? 0]);
                    fputcsv($handle, ['Suppliers With Dues', $data['suppliers_with_dues'] ?? 0]);
                    fputcsv($handle, ['Suppliers Over Credit Limit', $data['suppliers_over_credit_limit'] ?? 0]);
                    fputcsv($handle, ['Average Performance Score', $data['average_performance_score'] ?? 0]);
                    fputcsv($handle, []);
                    fputcsv($handle, ['Supplier Code', 'Supplier Name', 'Performance Score', 'Current Balance', 'Payment Status', 'Total Dues']);

                    Supplier::active()->withBalance()->chunkById(200, function ($chunk) use ($handle) {
                        foreach ($chunk as $supplier) {
                            fputcsv($handle, [
                                $supplier->supplier_code,
                                $supplier->name,
                                $supplier->performance_score,
                                $supplier->current_balance,
                                $supplier->payment_status,
                                $supplier->total_dues,
                            ]);
                        }
                    });
                    break;
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function exportReportsExcel(string $reportType, array $data)
    {
        $filename = 'supplier-'.$reportType.'-'.now()->format('Ymd_His').'.xlsx';

        [$headings, $rows] = match ($reportType) {
            'aging' => [
                ['Supplier Code', 'Supplier Name', 'Current', 'Overdue 1-30', 'Overdue 31-60', 'Overdue 61-90', 'Overdue 90+', 'Total'],
                $this->buildAgingRows()->map(function (array $row) {
                    /** @var \App\Models\Supplier $supplier */
                    $supplier = $row['supplier'];
                    $aging = $row['aging'];

                    return [
                        (string) ($supplier->supplier_code ?? ''),
                        (string) ($supplier->name ?? ''),
                        (float) ($aging['current'] ?? 0),
                        (float) ($aging['overdue_1_30'] ?? 0),
                        (float) ($aging['overdue_31_60'] ?? 0),
                        (float) ($aging['overdue_61_90'] ?? 0),
                        (float) ($aging['overdue_90_plus'] ?? 0),
                        (float) ($aging['total'] ?? 0),
                    ];
                })->values()->all(),
            ],
            'dues' => [
                ['Supplier Code', 'Supplier Name', 'Email', 'Phone', 'Outstanding Amount', 'Payment Terms (Days)', 'Payment Status'],
                Supplier::active()
                    ->withDues()
                    ->withComputedBalance()
                    ->orderBy('name')
                    ->get()
                    ->map(function (Supplier $supplier) {
                        return [
                            (string) ($supplier->supplier_code ?? ''),
                            (string) ($supplier->name ?? ''),
                            (string) ($supplier->email ?? ''),
                            (string) ($supplier->phone ?? ''),
                            (float) ($supplier->total_dues ?? 0),
                            (int) ($supplier->payment_terms_days ?? 0),
                            (string) ($supplier->payment_status ?? ''),
                        ];
                    })
                    ->values()
                    ->all(),
            ],
            'performance' => [
                ['Supplier Code', 'Supplier Name', 'Performance Score', 'Current Balance', 'Payment Status', 'Total Dues'],
                Supplier::active()
                    ->withBalance()
                    ->orderBy('name')
                    ->get()
                    ->map(function (Supplier $supplier) {
                        return [
                            (string) ($supplier->supplier_code ?? ''),
                            (string) ($supplier->name ?? ''),
                            (float) ($supplier->performance_score ?? 0),
                            (float) ($supplier->current_balance ?? 0),
                            (string) ($supplier->payment_status ?? ''),
                            (float) ($supplier->total_dues ?? 0),
                        ];
                    })
                    ->values()
                    ->all(),
            ],
            default => [[], []],
        };

        return Excel::download(new ArrayReportExport($headings, $rows), $filename);
    }

    private function supplierPaymentHeadMap(?int $branchId = null): array
    {
        $query = PaymentHeadMapping::query()
            ->forContext(PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT)
            ->active();

        // First find mappings specifically for this branch, or fallback to global. 
        // We will fetch both and prioritize branch over global in memory.
        $mappings = $query->where(function($q) use ($branchId) {
            $q->whereNull('branch_id');
            if ($branchId) {
                $q->orWhere('branch_id', $branchId);
            }
        })->get();

        $resolvedMap = [];
        foreach (array_keys(PaymentHeadMapping::methodOptions()) as $method) {
            $branchMap = $mappings->first(fn($m) => $m->payment_method === $method && $m->branch_id == $branchId);
            $globalMap = $mappings->first(fn($m) => $m->payment_method === $method && is_null($m->branch_id));
            
            $targetMap = $branchMap ?? $globalMap;
            if ($targetMap) {
                $resolvedMap[$method] = [
                    'head_id' => (int) $targetMap->account_head_id,
                    'is_locked' => (bool) $targetMap->is_locked,
                ];
            }
        }

        return $resolvedMap;
    }

    private function resolveSupplierPaymentAccountHeadId(?int $providedHeadId, string $paymentMethod, ?int $branchId = null): ?int
    {
        $mapDetail = $this->supplierPaymentHeadMap($branchId)[$paymentMethod] ?? null;

        if ($mapDetail && current($mapDetail)) {
            // If it's locked, we must ignore the provided HeadId and force the mapped one.
            if ($mapDetail['is_locked']) {
                return $mapDetail['head_id'];
            }
        }

        // If not locked and user provided one, use user's choice
        if (! empty($providedHeadId) && $providedHeadId > 0) {
            return (int) $providedHeadId;
        }

        // Otherwise fallback to mapped (if available)
        return $mapDetail ? $mapDetail['head_id'] : null;
    }
}
