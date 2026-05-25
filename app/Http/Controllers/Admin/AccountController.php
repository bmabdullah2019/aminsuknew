<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\AccountHeadRequest;
use App\Http\Requests\Accounts\SupplierPayablesReportRequest;
use App\Models\Accounts\AccountHead;
use App\Models\Accounts\AccountSubsidiaryHead;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\BranchAccountingService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:supplier-report-dues', ['only' => ['supplierPayablesReport']]);
        $this->middleware('permission:order-view', ['only' => ['customerReceivablesReport']]);
    }

    // ── Chart of Accounts: Tree View ──

    public function index(): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $heads = AccountHead::valid()
            ->get([
                'HeadId',
                'ParentId',
                'AccType',
                'HeadCode',
                'HeadName',
                'Label',
                'HasChild',
                'ParentHead',
                'Description',
            ]);

        $tree = $this->buildNestedHeads($heads);
        $headOptions = $heads
            ->sortBy('HeadCode', SORT_NATURAL)
            ->values()
            ->map(function (AccountHead $head) {
                $breadcrumb = trim((string) $head->ParentHead, ' /');
                $path = $breadcrumb !== ''
                    ? $breadcrumb.' / '.$head->HeadName
                    : $head->HeadName;

                return [
                    'id' => $head->HeadId,
                    'code' => $head->HeadCode,
                    'name' => $head->HeadName,
                    'path' => $path,
                    'label' => $head->HeadCode.' - '.$path,
                ];
            });
        $summary = [
            'totalHeads' => $heads->count(),
            'rootHeads' => $heads->where('ParentId', 0)->count(),
            'leafHeads' => $heads->where('HasChild', false)->count(),
        ];

        return view('backEnd.accounts.head.index', compact('tree', 'summary', 'headOptions'));
    }

    // ── AJAX: Expand tree node ──

    public function getTree(Request $request): JsonResponse
    {
        $parentId = (int) $request->input('parentId', 0);
        $children = AccountHead::valid()
            ->where('ParentId', $parentId)
            ->orderBy('HeadId')
            ->get(['HeadId', 'ParentId', 'AccType', 'HeadCode', 'HeadName', 'Label', 'HasChild', 'Description', 'ParentHead']);

        return response()->json($children);
    }

    // ── AJAX: Get new code for adding child account ──

    public function getNewCode(int $parentId): JsonResponse
    {
        $code = AccountHead::getNewCode($parentId);
        $parent = AccountHead::find($parentId);

        return response()->json([
            'code' => $code,
            'accType' => $parent->AccType ?? 0,
            'label' => AccountHead::nextLabelForParent($parentId),
            'breadcrumb' => AccountHead::buildBreadcrumb($parentId),
        ]);
    }

    // ── AJAX: Autocomplete search ──

    public function getHeadList(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('Keyword', ''));
        if (strlen($keyword) < 2) {
            return response()->json([]);
        }

        $heads = AccountHead::valid()
            ->where(function ($q) use ($keyword) {
                $q->where('HeadName', 'like', '%'.$keyword.'%')
                    ->orWhere('HeadCode', 'like', '%'.$keyword.'%');
            })
            ->orderBy('HeadCode')
            ->limit(20)
            ->get(['HeadId', 'HeadCode', 'HeadName', 'AccType']);

        return response()->json($heads);
    }

    // ── AJAX: Get subsidiaries for a head ──

    public function getSubsidiaryList(Request $request): JsonResponse
    {
        $headId = (int) $request->input('HeadId', 0);

        $subsidiaries = AccountSubsidiaryHead::valid()
            ->where('HeadId', $headId)
            ->with(['subsidiary' => fn ($q) => $q->where('Validity', 1)->where('Status', 'A')])
            ->get()
            ->pluck('subsidiary')
            ->filter()
            ->values();

        return response()->json($subsidiaries);
    }

    // ── Save account head (create/update) ──

    public function store(AccountHeadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['ParentId'] = (int) $request->input('ParentId', 0);

        $user = auth()->user()->name ?? 'system';
        $now = now()->toDateTimeString();
        $savedHead = null;

        DB::transaction(function () use ($validated, $user, $now, &$savedHead) {
            $headId = $validated['HeadId'] ?? null;

            if ($headId) {
                // Update existing
                $head = AccountHead::findOrFail($headId);
                $head->update([
                    'HeadCode' => $validated['HeadCode'],
                    'HeadName' => $validated['HeadName'],
                    'Description' => $validated['Description'] ?? '',
                    'UpdatedBy' => $user,
                    'UpdatedAt' => $now,
                ]);

                $savedHead = $head->fresh();
            } else {
                // Create new
                $parentId = (int) $validated['ParentId'];
                $parent = $parentId > 0
                    ? AccountHead::valid()->findOrFail($parentId)
                    : null;
                $breadcrumb = AccountHead::buildBreadcrumb($parentId);
                $headCode = AccountHead::getNewCode($parentId);
                $label = AccountHead::nextLabelForParent($parentId);
                $accType = $parent ? (int) $parent->AccType : (int) $validated['AccType'];

                $savedHead = AccountHead::create([
                    'ParentId' => $parentId,
                    'AccType' => $accType,
                    'HeadCode' => $headCode,
                    'HeadName' => $validated['HeadName'],
                    'Label' => $label,
                    'HasChild' => 0,
                    'ParentHead' => $breadcrumb,
                    'Description' => $validated['Description'] ?? '',
                    'CreatedBy' => $user,
                    'CreatedAt' => $now,
                    'Validity' => 1,
                ]);

                // Mark parent as HasChild
                if ($parentId > 0) {
                    AccountHead::where('HeadId', $parentId)
                        ->update(['HasChild' => 1]);
                }
            }
        });

        // Rebuild the tree after the transaction commits. The rebuild uses
        // TRUNCATE internally, which would otherwise break the active transaction.
        event(new \App\Events\Accounts\AccountHeadUpdated);

        $savedHead = $savedHead?->fresh();

        return response()->json([
            'hasError' => 0,
            'message' => 'Account head saved successfully.',
            'head' => $savedHead ? [
                'headId' => (int) $savedHead->HeadId,
                'parentId' => (int) $savedHead->ParentId,
                'accType' => (int) $savedHead->AccType,
                'headCode' => (string) $savedHead->HeadCode,
                'headName' => (string) $savedHead->HeadName,
                'label' => (int) $savedHead->Label,
                'description' => (string) ($savedHead->Description ?? ''),
                'parentName' => trim((string) $savedHead->ParentHead, ' /'),
                'hasChild' => (bool) $savedHead->HasChild,
            ] : null,
        ]);
    }

    // ── Delete account head ──

    public function destroy(Request $request): JsonResponse
    {
        $headId = (int) $request->input('HeadId', 0);
        $head = AccountHead::find($headId);

        if (! $head) {
            return response()->json(['hasError' => 1, 'message' => 'Account not found.']);
        }

        // Check if has children
        if (AccountHead::valid()->where('ParentId', $headId)->exists()) {
            return response()->json(['hasError' => 1, 'message' => 'Cannot delete: this account has children.']);
        }

        // Check if used in transactions
        if (DB::table('accounts_transaction_details')->where('TranHead', $headId)->where('Validity', 1)->exists()) {
            return response()->json(['hasError' => 1, 'message' => 'Cannot delete: this account has transactions.']);
        }

        $user = auth()->user()->name ?? 'system';
        $head->update([
            'Validity' => 0,
            'DeletedBy' => $user,
            'DeletedAt' => now()->toDateTimeString(),
        ]);

        // Check if parent still has other children
        $siblings = AccountHead::valid()->where('ParentId', $head->ParentId)->count();
        if ($siblings === 0 && $head->ParentId > 0) {
            AccountHead::where('HeadId', $head->ParentId)->update(['HasChild' => 0]);
        }

        event(new \App\Events\Accounts\AccountHeadUpdated);

        return response()->json(['hasError' => 0, 'message' => 'Account head deleted.']);
    }

    // ── Supplier Payables Report (preserved from old controller) ──

    public function supplierPayablesReport(
        SupplierPayablesReportRequest $request,
        BranchAccountingService $branchAccountingService
    ): View|RedirectResponse {
        if (! $this->reportReady()) {
            return $this->missingReportRedirect();
        }

        $branchId = $request->branchId();
        $supplierId = $request->supplierId();
        $onlyDue = $request->onlyDue();
        $search = $request->searchTerm();
        $sort = $request->sortOption();

        $query = $branchAccountingService->supplierPayablesQuery($branchId);

        if ($supplierId) {
            $query->where('supplier_ledgers.supplier_id', $supplierId);
        }
        if ($onlyDue) {
            $query->havingRaw('COALESCE(SUM(supplier_ledgers.debit - supplier_ledgers.credit), 0) > 0.01');
        }
        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('suppliers.name', 'like', '%'.$search.'%')
                    ->orWhere('suppliers.supplier_code', 'like', '%'.$search.'%');
            });
        }

        match ($sort) {
            'due_asc' => $query->orderBy('due_amount'),
            'purchase_desc' => $query->orderByDesc('purchase_total'),
            'purchase_asc' => $query->orderBy('purchase_total'),
            'paid_desc' => $query->orderByDesc('paid_amount'),
            'paid_asc' => $query->orderBy('paid_amount'),
            'supplier_asc' => $query->orderBy('supplier_name'),
            'supplier_desc' => $query->orderByDesc('supplier_name'),
            default => $query->orderByDesc('due_amount'),
        };

        $summaryRow = \Illuminate\Support\Facades\DB::query()
            ->fromSub(clone $query, 'supplier_due')
            ->selectRaw('COALESCE(SUM(purchase_total), 0) AS purchase_total')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) AS paid_amount')
            ->selectRaw('COALESCE(SUM(due_amount), 0) AS due_amount')
            ->selectRaw('COUNT(DISTINCT supplier_id) AS supplier_count')
            ->first();

        $summary = [
            'purchase_total' => round((float) ($summaryRow->purchase_total ?? 0), 2),
            'paid_amount' => round((float) ($summaryRow->paid_amount ?? 0), 2),
            'due_amount' => round((float) ($summaryRow->due_amount ?? 0), 2),
            'supplier_count' => (int) ($summaryRow->supplier_count ?? 0),
        ];

        if ($request->shouldExportXlsx()) {
            $exportRows = (clone $query)->get()->map(function ($row) {
                return [
                    (string) ($row->branch_code ?? ''),
                    (string) ($row->branch_name ?? ''),
                    (string) ($row->supplier_code ?? ''),
                    (string) ($row->supplier_name ?? ''),
                    (float) ($row->purchase_total ?? 0),
                    (float) ($row->paid_amount ?? 0),
                    (float) ($row->due_amount ?? 0),
                ];
            })->values()->all();

            return Excel::download(
                new ArrayReportExport(
                    ['Branch Code', 'Branch Name', 'Supplier Code', 'Supplier Name', 'Purchase Total', 'Paid Amount', 'Due Amount'],
                    $exportRows
                ),
                'supplier-due-report-'.now()->format('Ymd_His').'.xlsx'
            );
        }

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'supplier_code']);
        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);
        $rows = $query->paginate(30)->appends($request->query());

        return view('backEnd.account.supplier-payables', compact('rows', 'summary', 'suppliers', 'branches'));
    }

    // ── Customer Receivables Report (preserved from old controller) ──

    public function customerReceivablesReport(
        Request $request,
        BranchAccountingService $branchAccountingService
    ): View|RedirectResponse {
        if (! $this->reportReady()) {
            return $this->missingReportRedirect();
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'only_due' => 'nullable|in:0,1',
            'export' => 'nullable|in:xlsx',
        ]);

        $branchId = ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        $customerId = ! empty($validated['customer_id']) ? (int) $validated['customer_id'] : null;
        $onlyDue = ! array_key_exists('only_due', $validated) || $validated['only_due'] === '1';

        $query = $branchAccountingService->customerReceivablesQuery($branchId);

        if ($customerId) {
            $query->where('orders.customer_id', $customerId);
        }
        if ($onlyDue) {
            $query->whereRaw(
                Schema::hasTable('return_orders')
                    ? '(COALESCE(NULLIF(orders.amount_minor, 0) / 100, orders.amount, 0) - COALESCE(paid.paid_amount, 0) - COALESCE(refund.refund_amount, 0)) > 0.01'
                    : '(COALESCE(NULLIF(orders.amount_minor, 0) / 100, orders.amount, 0) - COALESCE(paid.paid_amount, 0)) > 0.01'
            );
        }
        $query->orderByDesc('orders.id');

        $summaryRow = \Illuminate\Support\Facades\DB::query()
            ->fromSub(clone $query, 'customer_due')
            ->selectRaw('COALESCE(SUM(order_total), 0) AS order_total')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) AS paid_amount')
            ->selectRaw('COALESCE(SUM(refund_amount), 0) AS refund_amount')
            ->selectRaw('COALESCE(SUM(due_amount), 0) AS due_amount')
            ->selectRaw('COUNT(DISTINCT customer_id) AS customer_count')
            ->selectRaw('COUNT(*) AS order_count')
            ->first();

        $summary = [
            'order_total' => round((float) ($summaryRow->order_total ?? 0), 2),
            'paid_amount' => round((float) ($summaryRow->paid_amount ?? 0), 2),
            'refund_amount' => round((float) ($summaryRow->refund_amount ?? 0), 2),
            'due_amount' => round((float) ($summaryRow->due_amount ?? 0), 2),
            'customer_count' => (int) ($summaryRow->customer_count ?? 0),
            'order_count' => (int) ($summaryRow->order_count ?? 0),
        ];

        if (($validated['export'] ?? null) === 'xlsx') {
            $exportRows = (clone $query)->get()->map(function ($row) {
                return [
                    (string) ($row->branch_code ?? ''), (string) ($row->branch_name ?? ''),
                    (string) ($row->invoice_id ?? ('#'.$row->order_id)),
                    (string) ($row->customer_name ?? ''), (string) ($row->customer_phone ?? ''),
                    (float) ($row->order_total ?? 0), (float) ($row->paid_amount ?? 0),
                    (float) ($row->refund_amount ?? 0), (float) ($row->due_amount ?? 0),
                ];
            })->values()->all();

            return Excel::download(
                new ArrayReportExport(
                    ['Branch Code', 'Branch Name', 'Order', 'Customer', 'Phone', 'Order Total', 'Paid Amount', 'Refund Amount', 'Due Amount'],
                    $exportRows
                ),
                'customer-due-report-'.now()->format('Ymd_His').'.xlsx'
            );
        }

        $customers = Customer::query()->orderBy('name')->get(['id', 'name', 'phone']);
        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);
        $rows = $query->paginate(30)->appends($request->query());

        return view('backEnd.account.customer-receivables', compact('rows', 'summary', 'customers', 'branches'));
    }

    private function ready(): bool
    {
        return Schema::hasTable('accounts_head');
    }

    private function buildNestedHeads(Collection $heads, int $parentId = 0): Collection
    {
        return $heads
            ->where('ParentId', $parentId)
            ->sortBy(fn (AccountHead $head) => (string) $head->HeadCode, SORT_NATURAL)
            ->values()
            ->map(function (AccountHead $head) use ($heads) {
                $head->setRelation('children', $this->buildNestedHeads($heads, (int) $head->HeadId));

                return $head;
            });
    }

    private function reportReady(): bool
    {
        return Schema::hasTable('branches')
            && Schema::hasTable('suppliers')
            && Schema::hasTable('supplier_ledgers')
            && Schema::hasTable('orders')
            && Schema::hasTable('payments');
    }

    private function missingTableRedirect(): RedirectResponse
    {
        Toastr::error('Accounts module is not ready. Run migrations first.');

        return redirect()->route('admin.dashboard');
    }

    private function missingReportRedirect(): RedirectResponse
    {
        Toastr::error('Accounts reporting module is not ready. Run migrations first.');

        return redirect()->route('admin.dashboard');
    }
}
