<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ExpenseService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExpenseController extends Controller
{
    private const MAX_REPORT_RANGE_DAYS = 730;

    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->middleware('permission:expense-list', ['only' => ['index']]);
        $this->middleware('permission:expense-view', ['only' => ['show']]);
        $this->middleware('permission:expense-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:expense-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:expense-delete', ['only' => ['destroy']]);
        $this->middleware('permission:expense-approve', ['only' => ['approve']]);
        $this->middleware('permission:expense-reject', ['only' => ['reject']]);
        $this->middleware('permission:expense-mark-paid', ['only' => ['markAsPaid']]);
        $this->middleware('permission:expense-reports', ['only' => ['reports']]);
        $this->middleware('permission:expense-daily-summary', ['only' => ['dailySummary']]);
        $this->middleware('permission:expense-activity-log', ['only' => ['activityLog']]);
        $this->middleware('permission:expense-bulk-approve', ['only' => ['bulkApprove']]);
        $this->middleware('permission:expense-export', ['only' => ['export']]);

        $this->expenseService = $expenseService;
    }

    /**
     * Display a listing of expenses
     */
    public function index(Request $request)
    {
        $indexRules = [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'category_id' => 'nullable|exists:expense_categories,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'nullable|in:pending,approved,rejected,paid',
            'payment_method' => 'nullable|in:cash,bank_transfer,cheque,card,other',
            'search' => 'nullable|string|max:255',
        ];

        if ($this->supportsSupplierLink()) {
            $indexRules['supplier_id'] = 'nullable|exists:suppliers,id';
        }
        if ($this->supportsPurchaseOrderLink()) {
            $indexRules['purchase_order_id'] = 'nullable|exists:purchase_orders,id';
        }
        if ($this->supportsGrnLink()) {
            $indexRules['grn_id'] = 'nullable|exists:grns,id';
        }

        $filters = $request->validate($indexRules);

        $baseQuery = Expense::query();
        $this->applyExpenseFilters($baseQuery, $filters);

        $expenses = (clone $baseQuery)
            ->with(['category', 'creator', 'approver', 'allocations.warehouse'])
            ->latest('expense_date')
            ->paginate(25)
            ->appends($request->query());

        $summary = [
            'total_expenses' => (clone $baseQuery)->count(),
            'pending_count' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved_count' => (clone $baseQuery)->where('status', 'approved')->count(),
            'paid_count' => (clone $baseQuery)->where('status', 'paid')->count(),
            'rejected_count' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'paid_total' => (clone $baseQuery)->where('status', 'paid')->sum('total_amount'),
        ];

        $categories = ExpenseCategory::active()->ordered()->get();
        $warehouses = Warehouse::active()->get();
        $suppliers = $this->getSupplierOptions();
        $purchaseOrders = $this->getPurchaseOrderOptions();
        $grns = $this->getGrnOptions();

        return view('backEnd.expense.index', compact(
            'expenses',
            'categories',
            'warehouses',
            'summary',
            'suppliers',
            'purchaseOrders',
            'grns'
        ));
    }

    /**
     * Show the form for creating a new expense
     */
    public function create()
    {
        $categories = ExpenseCategory::active()->ordered()->get();
        $warehouses = Warehouse::active()->get();
        $suppliers = $this->getSupplierOptions();
        $purchaseOrders = $this->getPurchaseOrderOptions();
        $grns = $this->getGrnOptions();

        return view('backEnd.expense.create', compact('categories', 'warehouses', 'suppliers', 'purchaseOrders', 'grns'));
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        $validated = $this->validateExpensePayload($request);
        $actor = $this->requireAuthenticatedUser();

        if (! empty($validated['warehouse_allocations']) && ! $actor->can('expense-allocate')) {
            Toastr::error('You do not have permission to allocate expenses to warehouses.');

            return redirect()->back()->withInput();
        }
        $expensePayload = $this->normalizeExpensePayload($validated);
        $procurementLinks = $this->resolveProcurementLinks($validated);

        try {
            DB::transaction(function () use ($validated, $expensePayload, $procurementLinks, $actor) {
                $expense = Expense::create([
                    'expense_date' => $expensePayload['expense_date'],
                    'category_id' => (int) $expensePayload['category_id'],
                    'total_amount' => (float) $expensePayload['total_amount'],
                    'payment_method' => $expensePayload['payment_method'],
                    'bank_name' => $expensePayload['bank_name'],
                    'cheque_number' => $expensePayload['cheque_number'],
                    'card_number' => $expensePayload['card_number'],
                    'description' => $expensePayload['description'],
                    'notes' => $expensePayload['notes'],
                    'supplier_id' => $procurementLinks['supplier_id'] ?? null,
                    'purchase_order_id' => $procurementLinks['purchase_order_id'] ?? null,
                    'grn_id' => $procurementLinks['grn_id'] ?? null,
                    'created_by' => $actor->id,
                ]);

                if (! empty($validated['warehouse_allocations'])) {
                    $allocations = collect($validated['warehouse_allocations'])
                        ->map(function (array $allocation) {
                            return [
                                'warehouse_id' => (int) $allocation['warehouse_id'],
                                'amount' => (float) $allocation['amount'],
                                'notes' => $allocation['notes'] ?? null,
                            ];
                        })
                        ->values()
                        ->all();

                    $validationErrors = $this->expenseService->validateExpenseAllocation($expense, $allocations);
                    if (! empty($validationErrors)) {
                        throw ValidationException::withMessages([
                            'warehouse_allocations' => implode(' ', $validationErrors),
                        ]);
                    }

                    $expense->allocateToWarehouses($allocations, $actor);
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            Toastr::error('Failed to create expense. Please try again.');

            return redirect()->back()->withInput();
        }

        Toastr::success('Expense created successfully and is pending approval.');

        return redirect()->route('admin.expense.index');
    }

    /**
     * Display the specified expense
     */
    public function show(Expense $expense)
    {
        $expense->load(['category', 'creator', 'approver', 'allocations.warehouse', 'logs.user']);

        return view('backEnd.expense.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified expense
     */
    public function edit(Expense $expense)
    {
        // Only allow editing if status is pending
        if ($expense->status !== 'pending') {
            Toastr::error('Only pending expenses can be edited.');

            return redirect()->route('admin.expense.show', $expense);
        }

        $categories = ExpenseCategory::active()->ordered()->get();
        $warehouses = Warehouse::active()->get();
        $suppliers = $this->getSupplierOptions();
        $purchaseOrders = $this->getPurchaseOrderOptions();
        $grns = $this->getGrnOptions();

        return view('backEnd.expense.edit', compact('expense', 'categories', 'warehouses', 'suppliers', 'purchaseOrders', 'grns'));
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, Expense $expense)
    {
        // Only allow editing if status is pending
        if ($expense->status !== 'pending') {
            Toastr::error('Only pending expenses can be updated.');

            return redirect()->route('admin.expense.show', $expense);
        }

        $validated = $this->validateExpensePayload($request);
        $actor = $this->requireAuthenticatedUser();

        if (! empty($validated['warehouse_allocations']) && ! $actor->can('expense-allocate')) {
            Toastr::error('You do not have permission to allocate expenses to warehouses.');

            return redirect()->back()->withInput();
        }
        $expensePayload = $this->normalizeExpensePayload($validated);
        $procurementLinks = $this->resolveProcurementLinks($validated);

        try {
            DB::transaction(function () use ($validated, $expensePayload, $procurementLinks, $expense, $actor) {
                $lockedExpense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
                if ($lockedExpense->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'expense' => 'Only pending expenses can be updated.',
                    ]);
                }

                $lockedExpense->update([
                    'expense_date' => $expensePayload['expense_date'],
                    'category_id' => (int) $expensePayload['category_id'],
                    'total_amount' => (float) $expensePayload['total_amount'],
                    'payment_method' => $expensePayload['payment_method'],
                    'bank_name' => $expensePayload['bank_name'],
                    'cheque_number' => $expensePayload['cheque_number'],
                    'card_number' => $expensePayload['card_number'],
                    'description' => $expensePayload['description'],
                    'notes' => $expensePayload['notes'],
                    'supplier_id' => $procurementLinks['supplier_id'] ?? null,
                    'purchase_order_id' => $procurementLinks['purchase_order_id'] ?? null,
                    'grn_id' => $procurementLinks['grn_id'] ?? null,
                ]);

                if ($actor->can('expense-allocate')) {
                    if (! empty($validated['warehouse_allocations'])) {
                        $allocations = collect($validated['warehouse_allocations'])
                            ->map(function (array $allocation) {
                                return [
                                    'warehouse_id' => (int) $allocation['warehouse_id'],
                                    'amount' => (float) $allocation['amount'],
                                    'notes' => $allocation['notes'] ?? null,
                                ];
                            })
                            ->values()
                            ->all();

                        $validationErrors = $this->expenseService->validateExpenseAllocation($lockedExpense, $allocations);
                        if (! empty($validationErrors)) {
                            throw ValidationException::withMessages([
                                'warehouse_allocations' => implode(' ', $validationErrors),
                            ]);
                        }

                        $lockedExpense->allocateToWarehouses($allocations, $actor);
                    } else {
                        $lockedExpense->allocations()->delete();
                    }
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            Toastr::error('Failed to update expense. Please try again.');

            return redirect()->back()->withInput();
        }

        Toastr::success('Expense updated successfully.');

        return redirect()->route('admin.expense.show', $expense);
    }

    /**
     * Remove the specified expense
     */
    public function destroy(Expense $expense)
    {
        // Only allow deletion if status is pending
        if ($expense->status !== 'pending') {
            Toastr::error('Only pending expenses can be deleted.');

            return redirect()->route('admin.expense.show', $expense);
        }

        try {
            DB::transaction(function () use ($expense) {
                $lockedExpense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
                if ($lockedExpense->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'expense' => 'Only pending expenses can be deleted.',
                    ]);
                }

                $lockedExpense->delete();
            });
        } catch (ValidationException $e) {
            $errorMessage = collect($e->errors())->flatten()->first() ?? 'Expense deletion failed.';
            Toastr::error($errorMessage);

            return redirect()->route('admin.expense.show', $expense);
        } catch (Throwable $e) {
            report($e);
            Toastr::error('Failed to delete expense. Please try again.');

            return redirect()->route('admin.expense.show', $expense);
        }

        Toastr::success('Expense deleted successfully.');

        return redirect()->route('admin.expense.index');
    }

    /**
     * Approve an expense
     */
    public function approve(Expense $expense)
    {
        try {
            DB::transaction(function () use ($expense) {
                $lockedExpense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
                if ($lockedExpense->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'expense' => 'Only pending expenses can be approved.',
                    ]);
                }

                $lockedExpense->approve($this->requireAuthenticatedUser());
            });
        } catch (ValidationException $e) {
            $errorMessage = collect($e->errors())->flatten()->first() ?? 'Expense approval failed.';

            return $this->respondError($errorMessage, 422);
        } catch (Throwable $e) {
            report($e);

            return $this->respondError('Failed to approve expense.', 500);
        }

        return $this->respondSuccess('Expense approved successfully.');
    }

    /**
     * Reject an expense
     */
    public function reject(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        try {
            DB::transaction(function () use ($expense, $validated) {
                $lockedExpense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
                if ($lockedExpense->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'expense' => 'Only pending expenses can be rejected.',
                    ]);
                }

                $lockedExpense->reject($this->requireAuthenticatedUser(), $validated['reason']);
            });
        } catch (ValidationException $e) {
            $errorMessage = collect($e->errors())->flatten()->first() ?? 'Expense rejection failed.';

            return $this->respondError($errorMessage, 422);
        } catch (Throwable $e) {
            report($e);

            return $this->respondError('Failed to reject expense.', 500);
        }

        return $this->respondSuccess('Expense rejected successfully.');
    }

    /**
     * Mark expense as paid
     */
    public function markAsPaid(Expense $expense)
    {
        try {
            DB::transaction(function () use ($expense) {
                $lockedExpense = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();
                if ($lockedExpense->status !== 'approved') {
                    throw ValidationException::withMessages([
                        'expense' => 'Only approved expenses can be marked as paid.',
                    ]);
                }

                $lockedExpense->markAsPaid($this->requireAuthenticatedUser());
            });
        } catch (ValidationException $e) {
            $errorMessage = collect($e->errors())->flatten()->first() ?? 'Expense payment update failed.';

            return $this->respondError($errorMessage, 422);
        } catch (Throwable $e) {
            report($e);

            return $this->respondError('Failed to mark expense as paid.', 500);
        }

        return $this->respondSuccess('Expense marked as paid successfully.');
    }

    /**
     * Show expense reports
     */
    public function reports(Request $request)
    {
        $reportRules = [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'category_id' => 'nullable|exists:expense_categories,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'nullable|in:pending,approved,rejected,paid',
            'payment_method' => 'nullable|in:cash,bank_transfer,cheque,card,other',
        ];
        if ($this->supportsSupplierLink()) {
            $reportRules['supplier_id'] = 'nullable|exists:suppliers,id';
        }
        if ($this->supportsPurchaseOrderLink()) {
            $reportRules['purchase_order_id'] = 'nullable|exists:purchase_orders,id';
        }
        if ($this->supportsGrnLink()) {
            $reportRules['grn_id'] = 'nullable|exists:grns,id';
        }

        $validated = $request->validate($reportRules);

        $filters = array_filter($validated, function ($value) {
            return $value !== null && $value !== '';
        });

        // Set default date range if not provided
        if (! $request->filled('start_date')) {
            $filters['start_date'] = now()->startOfMonth()->format('Y-m-d');
        }
        if (! $request->filled('end_date')) {
            $filters['end_date'] = now()->endOfMonth()->format('Y-m-d');
        }
        $this->guardDateRangeWindow($filters['start_date'], $filters['end_date']);

        $reportData = $this->expenseService->getExpenseSummaryReport($filters);
        $categories = ExpenseCategory::active()->ordered()->get();
        $warehouses = Warehouse::active()->get();
        $suppliers = $this->getSupplierOptions();
        $purchaseOrders = $this->getPurchaseOrderOptions();
        $grns = $this->getGrnOptions();

        return view('backEnd.expense.reports', compact(
            'reportData',
            'categories',
            'warehouses',
            'filters',
            'suppliers',
            'purchaseOrders',
            'grns'
        ));
    }

    /**
     * Show activity log
     */
    public function activityLog(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'action' => 'nullable|in:created,updated,approved,rejected,paid,allocated',
            'days' => 'nullable|in:7,30,90,all',
        ]);

        $filters = array_filter($validated, function ($value) {
            return $value !== null && $value !== '';
        });
        $activityData = $this->expenseService->getActivityLog($filters);

        $users = \App\Models\User::whereHas('expenseLogs')->get();

        return view('backEnd.expense.activity-log', compact('activityData', 'users', 'filters'));
    }

    /**
     * Get daily expense summary
     */
    public function dailySummary(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = isset($validated['date']) ? Carbon::parse($validated['date']) : now();
        $summary = $this->expenseService->getDailyExpenseSummary($date);

        return view('backEnd.expense.daily-summary', compact('summary', 'date'));
    }

    /**
     * Bulk approve expenses
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'expense_ids' => 'required|array|min:1',
            'expense_ids.*' => 'required|integer|distinct|exists:expenses,id',
        ]);
        $actor = $this->requireAuthenticatedUser();

        try {
            $approvedCount = DB::transaction(function () use ($validated, $actor) {
                $count = 0;
                $expenses = Expense::query()
                    ->whereIn('id', $validated['expense_ids'])
                    ->lockForUpdate()
                    ->get();

                foreach ($expenses as $expense) {
                    if ($expense->status === 'pending') {
                        $expense->approve($actor);
                        $count++;
                    }
                }

                return $count;
            });
        } catch (Throwable $e) {
            report($e);

            return $this->respondError('Bulk approval failed.', 500);
        }

        return $this->respondSuccess("{$approvedCount} expenses approved successfully.");
    }

    /**
     * Export expenses to CSV
     */
    public function export(Request $request)
    {
        $exportRules = [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'category_id' => 'nullable|exists:expense_categories,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'nullable|in:pending,approved,rejected,paid',
            'payment_method' => 'nullable|in:cash,bank_transfer,cheque,card,other',
            'search' => 'nullable|string|max:255',
        ];
        if ($this->supportsSupplierLink()) {
            $exportRules['supplier_id'] = 'nullable|exists:suppliers,id';
        }
        if ($this->supportsPurchaseOrderLink()) {
            $exportRules['purchase_order_id'] = 'nullable|exists:purchase_orders,id';
        }
        if ($this->supportsGrnLink()) {
            $exportRules['grn_id'] = 'nullable|exists:grns,id';
        }

        $filters = $request->validate($exportRules);

        $query = Expense::query()->with(['category', 'creator', 'approver']);
        $this->applyExpenseFilters($query, $filters);
        $query->orderBy('expense_date')->orderBy('id');

        $filename = 'expenses_'.now()->format('Y-m-d_H-i-s').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Expense Number',
                'Date',
                'Category',
                'Amount',
                'Payment Method',
                'Status',
                'Created By',
                'Approved By',
                'Description',
            ]);

            // CSV data
            foreach ($query->cursor() as $expense) {
                fputcsv($file, [
                    $expense->expense_number,
                    $expense->expense_date->format('Y-m-d'),
                    optional($expense->category)->name ?? 'N/A',
                    $expense->total_amount,
                    ucfirst(str_replace('_', ' ', $expense->payment_method)),
                    ucfirst($expense->status),
                    optional($expense->creator)->name ?? 'N/A',
                    $expense->approver?->name ?? 'N/A',
                    $expense->description,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function applyExpenseFilters(Builder $query, array $filters): void
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($startDate && $endDate) {
            $this->guardDateRangeWindow($startDate, $endDate);
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('expense_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('expense_date', '<=', $endDate);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->whereHas('allocations', function ($allocationQuery) use ($filters) {
                $allocationQuery->where('warehouse_id', (int) $filters['warehouse_id']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if ($this->supportsSupplierLink() && ! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if ($this->supportsPurchaseOrderLink() && ! empty($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', (int) $filters['purchase_order_id']);
        }

        if ($this->supportsGrnLink() && ! empty($filters['grn_id'])) {
            $query->where('grn_id', (int) $filters['grn_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('expense_number', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }
    }

    protected function validateExpensePayload(Request $request): array
    {
        $rules = [
            'expense_date' => 'required|date|before_or_equal:today',
            'category_id' => [
                'required',
                Rule::exists('expense_categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'total_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,card,other',
            'bank_name' => 'nullable|required_if:payment_method,bank_transfer,cheque|string|max:255',
            'cheque_number' => 'nullable|required_if:payment_method,cheque|string|max:255',
            'card_number' => 'nullable|required_if:payment_method,card|digits:4',
            'description' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'warehouse_allocations' => 'nullable|array',
            'warehouse_allocations.*.warehouse_id' => [
                'required_with:warehouse_allocations',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'warehouse_allocations.*.amount' => 'required_with:warehouse_allocations|numeric|min:0.01',
            'warehouse_allocations.*.notes' => 'nullable|string|max:255',
        ];

        if ($this->supportsSupplierLink()) {
            $rules['supplier_id'] = 'nullable|integer|exists:suppliers,id';
        }
        if ($this->supportsPurchaseOrderLink()) {
            $rules['purchase_order_id'] = 'nullable|integer|exists:purchase_orders,id';
        }
        if ($this->supportsGrnLink()) {
            $rules['grn_id'] = 'nullable|integer|exists:grns,id';
        }

        return $request->validate($rules);
    }

    protected function resolveProcurementLinks(array $validated): array
    {
        $supplierId = $this->supportsSupplierLink() ? (int) ($validated['supplier_id'] ?? 0) : 0;
        $purchaseOrderId = $this->supportsPurchaseOrderLink() ? (int) ($validated['purchase_order_id'] ?? 0) : 0;
        $grnId = $this->supportsGrnLink() ? (int) ($validated['grn_id'] ?? 0) : 0;

        if ($purchaseOrderId > 0) {
            $purchaseOrder = PurchaseOrder::query()
                ->select(['id', 'supplier_id'])
                ->find($purchaseOrderId);

            if (! $purchaseOrder) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Selected purchase order was not found.',
                ]);
            }

            $purchaseSupplierId = (int) ($purchaseOrder->supplier_id ?? 0);
            if ($supplierId > 0 && $purchaseSupplierId > 0 && $supplierId !== $purchaseSupplierId) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Selected purchase order does not belong to the selected supplier.',
                ]);
            }
            if ($supplierId <= 0 && $purchaseSupplierId > 0) {
                $supplierId = $purchaseSupplierId;
            }
        }

        if ($grnId > 0) {
            $grn = Grn::query()
                ->select(['id', 'supplier_id'])
                ->find($grnId);

            if (! $grn) {
                throw ValidationException::withMessages([
                    'grn_id' => 'Selected GRN was not found.',
                ]);
            }

            $grnSupplierId = (int) ($grn->supplier_id ?? 0);
            if ($supplierId > 0 && $grnSupplierId > 0 && $supplierId !== $grnSupplierId) {
                throw ValidationException::withMessages([
                    'grn_id' => 'Selected GRN does not belong to the selected supplier.',
                ]);
            }
            if ($supplierId <= 0 && $grnSupplierId > 0) {
                $supplierId = $grnSupplierId;
            }
        }

        $links = [];
        if ($this->supportsSupplierLink()) {
            $links['supplier_id'] = $supplierId > 0 ? $supplierId : null;
        }
        if ($this->supportsPurchaseOrderLink()) {
            $links['purchase_order_id'] = $purchaseOrderId > 0 ? $purchaseOrderId : null;
        }
        if ($this->supportsGrnLink()) {
            $links['grn_id'] = $grnId > 0 ? $grnId : null;
        }

        return $links;
    }

    protected function getSupplierOptions(): Collection
    {
        if (! $this->supportsSupplierLink()) {
            return collect();
        }

        return Supplier::query()
            ->select(['id', 'supplier_code', 'name', 'status'])
            ->orderBy('name')
            ->limit(500)
            ->get();
    }

    protected function getPurchaseOrderOptions(): Collection
    {
        if (! $this->supportsPurchaseOrderLink()) {
            return collect();
        }

        return PurchaseOrder::query()
            ->select(['id', 'po_number', 'supplier_id', 'status'])
            ->orderByDesc('id')
            ->limit(500)
            ->get();
    }

    protected function getGrnOptions(): Collection
    {
        if (! $this->supportsGrnLink()) {
            return collect();
        }

        return Grn::query()
            ->select(['id', 'grn_number', 'supplier_id', 'status'])
            ->orderByDesc('id')
            ->limit(500)
            ->get();
    }

    protected function supportsSupplierLink(): bool
    {
        return $this->supportsExpenseColumn('supplier_id') && Schema::hasTable('suppliers');
    }

    protected function supportsPurchaseOrderLink(): bool
    {
        return $this->supportsExpenseColumn('purchase_order_id') && Schema::hasTable('purchase_orders');
    }

    protected function supportsGrnLink(): bool
    {
        return $this->supportsExpenseColumn('grn_id') && Schema::hasTable('grns');
    }

    protected function supportsExpenseColumn(string $column): bool
    {
        static $cache = [];
        if (! array_key_exists($column, $cache)) {
            $cache[$column] = Schema::hasTable('expenses') && Schema::hasColumn('expenses', $column);
        }

        return (bool) $cache[$column];
    }

    protected function normalizeExpensePayload(array $validated): array
    {
        $paymentMethod = (string) $validated['payment_method'];
        $usesBankInfo = in_array($paymentMethod, ['bank_transfer', 'cheque'], true);

        return [
            'expense_date' => $validated['expense_date'],
            'category_id' => (int) $validated['category_id'],
            'total_amount' => (float) $validated['total_amount'],
            'payment_method' => $paymentMethod,
            'bank_name' => $usesBankInfo ? ($validated['bank_name'] ?? null) : null,
            'cheque_number' => $paymentMethod === 'cheque' ? ($validated['cheque_number'] ?? null) : null,
            'card_number' => $paymentMethod === 'card' ? ($validated['card_number'] ?? null) : null,
            'description' => $validated['description'],
            'notes' => $validated['notes'] ?? null,
        ];
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

    protected function shouldReturnJson(): bool
    {
        $request = request();

        return $request->expectsJson() || $request->wantsJson() || $request->ajax();
    }

    protected function respondSuccess(string $message)
    {
        if ($this->shouldReturnJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        Toastr::success($message);

        return redirect()->back();
    }

    protected function respondError(string $message, int $status = 400)
    {
        if ($this->shouldReturnJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        Toastr::error($message);

        return redirect()->back();
    }
}
