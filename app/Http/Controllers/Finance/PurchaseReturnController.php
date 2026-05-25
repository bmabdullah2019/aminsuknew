<?php

namespace App\Http\Controllers\Finance;

use App\Http\Requests\ApprovePurchaseReturnRequest;
use App\Http\Requests\PurchaseReturnIndexRequest;
use App\Http\Requests\StorePurchaseReturnRequest;
use App\Http\Requests\UpdatePurchaseReturnRequest;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:purchase-return-list', ['only' => ['index']]);
        $this->middleware('permission:purchase-return-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:purchase-return-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:purchase-return-delete', ['only' => ['destroy']]);
        $this->middleware('permission:purchase-return-approve', ['only' => ['approve']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(PurchaseReturnIndexRequest $request): View
    {
        $filters = $request->filters();
        $query = PurchaseReturn::with(['supplier', 'branch', 'creator']);

        if ($status = $filters['status']) {
            $query->where('status', $status);
        }

        if ($supplierId = $filters['supplier_id']) {
            $query->where('supplier_id', $supplierId);
        }

        if ($branchId = $filters['branch_id']) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $filters['search']) {
            $query->where('return_number', 'like', "%{$search}%");
        }

        $purchaseReturns = $query->latest()->paginate(20)->appends($request->query());
        $suppliers = Supplier::active()->get();
        $branches = Branch::active()->get();

        return view('backEnd.finance.purchase-returns.index', compact('purchaseReturns', 'suppliers', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $suppliers = Supplier::active()->get();
        $branches = Branch::active()->get();
        $products = Product::where('status', 1)->get();
        $purchaseOrders = PurchaseOrder::where('status', 'pending')->get();

        return view('backEnd.finance.purchase-returns.create', compact('suppliers', 'branches', 'products', 'purchaseOrders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseReturnRequest $request): RedirectResponse
    {
        $validated = $request->purchaseReturnData();

        try {
            $purchaseReturn = DB::transaction(function () use ($validated) {
                $purchaseReturn = PurchaseReturn::create([
                    'return_number' => $this->generateReturnNumber(),
                    'branch_id' => $validated['branch_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                    'return_date' => $validated['return_date'],
                    'return_reason' => $validated['return_reason'],
                    'description' => $validated['description'] ?? null,
                    'total_return_amount' => $validated['total_return_amount'],
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

                $this->createLineItems($purchaseReturn, $validated['items']);

                return $purchaseReturn;
            });

            Toastr::success('Purchase return created successfully', 'Success');

            return redirect()->route('admin.purchase-returns.show', $purchaseReturn->id);
        } catch (\Exception $e) {
            Toastr::error('Failed to create purchase return: '.$e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseReturn $purchaseReturn): View
    {
        $purchaseReturn->load(['supplier', 'branch', 'items.product', 'items.variant', 'creator', 'approver']);

        return view('backEnd.finance.purchase-returns.show', compact('purchaseReturn'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseReturn $purchaseReturn): View|RedirectResponse
    {
        if (! $purchaseReturn->canEdit()) {
            Toastr::error('Only pending returns can be edited', 'Error');

            return redirect()->route('admin.purchase-returns.show', $purchaseReturn->id);
        }

        $suppliers = Supplier::active()->get();
        $branches = Branch::active()->get();
        $products = Product::where('status', 1)->get();

        return view('backEnd.finance.purchase-returns.edit', compact('purchaseReturn', 'suppliers', 'branches', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseReturnRequest $request, PurchaseReturn $purchaseReturn): JsonResponse|RedirectResponse
    {
        if (! $purchaseReturn->canEdit()) {
            return response()->json(['error' => 'This return cannot be edited'], 422);
        }

        $validated = $request->purchaseReturnData();

        try {
            DB::transaction(function () use ($purchaseReturn, $validated) {
                $purchaseReturn->update([
                    'branch_id' => $validated['branch_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                    'return_date' => $validated['return_date'],
                    'return_reason' => $validated['return_reason'],
                    'description' => $validated['description'] ?? null,
                    'total_return_amount' => $validated['total_return_amount'],
                ]);

                $purchaseReturn->items()->delete();
                $this->createLineItems($purchaseReturn, $validated['items']);
            });

            Toastr::success('Purchase return updated successfully', 'Success');

            return redirect()->route('admin.purchase-returns.show', $purchaseReturn->id);
        } catch (\Exception $e) {
            Toastr::error('Failed to update purchase return: '.$e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseReturn $purchaseReturn): JsonResponse|RedirectResponse
    {
        if (! $purchaseReturn->canEdit()) {
            return response()->json(['error' => 'Only pending returns can be deleted'], 422);
        }

        try {
            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();

            Toastr::success('Purchase return deleted successfully', 'Success');

            return redirect()->route('admin.purchase-returns.index');
        } catch (\Exception $e) {
            Toastr::error('Failed to delete purchase return', 'Error');

            return redirect()->back();
        }
    }

    /**
     * Approve a purchase return
     */
    public function approve(ApprovePurchaseReturnRequest $request, PurchaseReturn $purchaseReturn): JsonResponse|RedirectResponse
    {
        if (! $purchaseReturn->canApprove()) {
            return response()->json(['error' => 'This return cannot be approved'], 422);
        }

        try {
            DB::transaction(function () use ($purchaseReturn) {
                $purchaseReturn->approve(auth()->id());

                // Trigger event for inventory update
                event(new \App\Events\PurchaseReturnApproved($purchaseReturn));
            });

            Toastr::success('Purchase return approved successfully', 'Success');

            return redirect()->route('admin.purchase-returns.show', $purchaseReturn->id);
        } catch (\Exception $e) {
            Toastr::error('Failed to approve purchase return: '.$e->getMessage(), 'Error');

            return redirect()->back();
        }
    }

    /**
     * Generate unique return number
     */
    private function generateReturnNumber(): string
    {
        $year = now()->year;
        $lastReturn = PurchaseReturn::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastReturn ? intval(substr($lastReturn->return_number, -5)) + 1 : 1;

        return 'PRET-'.$year.'-'.str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    private function createLineItems(PurchaseReturn $purchaseReturn, array $items): void
    {
        foreach ($items as $item) {
            $purchaseReturn->items()->create([
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_amount' => $item['line_amount'],
            ]);
        }
    }
}
