<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Services\StockMovementService;
use App\Services\StockService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransferController extends Controller
{
    protected $stockService;

    protected $movementService;

    public function __construct(StockService $stockService, StockMovementService $movementService)
    {
        $this->stockService = $stockService;
        $this->movementService = $movementService;

        $this->middleware('permission:transfer-list', ['only' => ['index']]);
        $this->middleware('permission:transfer-create', ['only' => ['create', 'store', 'warehouseProducts']]);
        $this->middleware('permission:transfer-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:transfer-delete', ['only' => ['destroy']]);
        $this->middleware('permission:transfer-approve', ['only' => ['approve', 'reject']]);
        $this->middleware('permission:transfer-dispatch', ['only' => ['dispatch']]);
        $this->middleware('permission:transfer-receive', ['only' => ['receive']]);
        $this->middleware('permission:transfer-view', ['only' => ['show']]);
    }

    public function index(Request $request)
    {
        $query = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'requester']);

        if ($request->status) {
            $query->byStatus($request->status);
        }

        if ($request->from_warehouse_id) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->to_warehouse_id) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }

        if ($request->search) {
            $query->where('transfer_number', 'like', "%{$request->search}%");
        }

        $transfers = $query->latest()->paginate(20);
        $warehouses = Warehouse::active()->get();

        return view('backEnd.transfer.index', compact('transfers', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('backEnd.transfer.create', compact('warehouses', 'products'));
    }

    public function warehouseProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $warehouseId = (int) $validator->validated()['warehouse_id'];

        $stocks = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('available_quantity', '>', 0)
            ->whereHas('product', function ($query) {
                $query->where('status', 1);
            })
            ->with('product:id,name,sku')
            ->orderBy('product_id')
            ->get(['warehouse_id', 'product_id', 'available_quantity']);

        return response()->json([
            'success' => true,
            'products' => $stocks->map(fn ($stock) => [
                'product_id' => (int) $stock->product_id,
                'product_name' => (string) ($stock->product?->name ?? ''),
                'sku' => (string) ($stock->product?->sku ?? ''),
                'available_quantity' => (float) ($stock->available_quantity ?? 0),
            ])->values(),
        ]);
    }

    public function store(TransferRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Check stock availability using StockService
            foreach ($validated['items'] as $item) {
                if (! $this->stockService->checkAvailableStock($validated['from_warehouse_id'], $item['product_id'], $item['quantity'])) {
                    $product = Product::find($item['product_id']);
                    throw new \Exception("Insufficient stock for {$product->name}");
                }
            }

            $transfer = WarehouseTransfer::create([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'transfer_date' => $validated['transfer_date'],
                'reason' => $validated['reason'],
                'estimated_arrival' => $validated['estimated_arrival'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $stock = WarehouseStock::where('warehouse_id', $validated['from_warehouse_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();

                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'sku' => $product->sku,
                    'quantity_requested' => $item['quantity'],
                    'unit_cost' => $stock->unit_cost,
                ]);
            }

            DB::commit();
            Toastr::success('Transfer request created successfully', 'Success');

            return redirect()->route('admin.transfer.show', $transfer->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to create transfer: '.$e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        $transfer = WarehouseTransfer::with([
            'fromWarehouse',
            'toWarehouse',
            'items.product',
            'requester',
            'approver',
            'dispatcher',
            'receiver',
        ])->findOrFail($id);

        return view('backEnd.transfer.show', compact('transfer'));
    }

    public function edit($id)
    {
        $transfer = WarehouseTransfer::with('items')->findOrFail($id);

        if (! in_array($transfer->status, ['draft', 'pending'])) {
            Toastr::error('Only draft or pending transfers can be edited', 'Error');

            return redirect()->route('admin.transfer.show', $id);
        }

        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('backEnd.transfer.edit', compact('transfer', 'warehouses', 'products'));
    }

    public function update(Request $request, $id)
    {
        $transfer = WarehouseTransfer::findOrFail($id);

        if (! in_array($transfer->status, ['draft', 'pending'])) {
            Toastr::error('Only draft or pending transfers can be updated', 'Error');

            return redirect()->back();
        }

        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'transfer_date' => 'required|date|before_or_equal:today',
            'reason' => 'required|min:10|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $transfer->items()->delete();

            $transfer->update([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'transfer_date' => $validated['transfer_date'],
                'reason' => $validated['reason'],
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $stock = WarehouseStock::where('warehouse_id', $validated['from_warehouse_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();

                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'sku' => $product->sku,
                    'quantity_requested' => $item['quantity'],
                    'unit_cost' => $stock->unit_cost ?? 0,
                ]);
            }

            DB::commit();
            Toastr::success('Transfer updated successfully', 'Success');

            return redirect()->route('admin.transfer.show', $transfer->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to update transfer: '.$e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        }
    }

    public function approve($id)
    {
        $transfer = WarehouseTransfer::with('items')->findOrFail($id);

        if ($transfer->status !== 'pending') {
            Toastr::error('Only pending transfers can be approved', 'Error');

            return redirect()->back();
        }

        $transfer->approve(auth()->id());

        Toastr::success('Transfer approved successfully', 'Success');

        return redirect()->route('admin.transfer.show', $transfer->id);
    }

    public function reject($id)
    {
        $transfer = WarehouseTransfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            Toastr::error('Only pending transfers can be rejected', 'Error');

            return redirect()->back();
        }

        $transfer->reject(auth()->id());

        Toastr::success('Transfer rejected', 'Success');

        return redirect()->route('admin.transfer.index');
    }

    public function dispatch($id)
    {
        $transfer = WarehouseTransfer::with('items')->findOrFail($id);

        if ($transfer->status !== 'approved') {
            Toastr::error('Only approved transfers can be dispatched', 'Error');

            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            // Reduce stock at source warehouse using StockMovementService
            foreach ($transfer->items as $item) {
                // Update dispatched quantity
                $item->update(['quantity_dispatched' => $item->quantity_requested]);

                // Record stock movement (out from source)
                $this->movementService->recordStockOut([
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity_requested,
                    'type' => 'transfer_out',
                    'reference_type' => 'warehouse_transfer',
                    'reference_id' => $transfer->id,
                    'unit_cost' => $item->unit_cost,
                    'notes' => "Transfer to {$transfer->toWarehouse->name}",
                ]);
            }

            $transfer->dispatch(auth()->id());

            DB::commit();
            Toastr::success('Transfer dispatched successfully', 'Success');

            return redirect()->route('admin.transfer.show', $transfer->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to dispatch transfer: '.$e->getMessage(), 'Error');

            return redirect()->back();
        }
    }

    public function receive($id)
    {
        $transfer = WarehouseTransfer::with('items')->findOrFail($id);

        if ($transfer->status !== 'dispatched') {
            Toastr::error('Only dispatched transfers can be received', 'Error');

            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            // Increase stock at destination warehouse using StockMovementService
            foreach ($transfer->items as $item) {
                // Update received quantity
                $item->update(['quantity_received' => $item->quantity_dispatched]);

                // Record stock movement (in to destination)
                $this->movementService->recordStockIn([
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity_dispatched,
                    'type' => 'transfer_in',
                    'reference_type' => 'warehouse_transfer',
                    'reference_id' => $transfer->id,
                    'unit_cost' => $item->unit_cost,
                    'notes' => "Transfer from {$transfer->fromWarehouse->name}",
                ]);
            }

            $transfer->receive(auth()->id());
            $transfer->complete();

            DB::commit();
            Toastr::success('Transfer received and completed successfully', 'Success');

            return redirect()->route('admin.transfer.show', $transfer->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to receive transfer: '.$e->getMessage(), 'Error');

            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $transfer = WarehouseTransfer::findOrFail($id);

        if (! in_array($transfer->status, ['draft', 'pending'])) {
            Toastr::error('Only draft or pending transfers can be deleted', 'Error');

            return redirect()->back();
        }

        $transfer->delete();
        Toastr::success('Transfer deleted successfully', 'Success');

        return redirect()->route('admin.transfer.index');
    }

    public function complete($id)
    {
        $transfer = WarehouseTransfer::findOrFail($id);

        if ($transfer->status !== 'dispatched') {
            Toastr::error('Only dispatched transfers can be completed', 'Error');

            return redirect()->back();
        }

        $transfer->complete();
        Toastr::success('Transfer completed successfully', 'Success');

        return redirect()->route('admin.transfer.show', $transfer->id);
    }

    public function cancel($id)
    {
        $transfer = WarehouseTransfer::findOrFail($id);

        if (! in_array($transfer->status, ['pending', 'approved'])) {
            Toastr::error('Only pending or approved transfers can be cancelled', 'Error');

            return redirect()->back();
        }

        $transfer->cancel();
        Toastr::success('Transfer cancelled successfully', 'Success');

        return redirect()->route('admin.transfer.show', $transfer->id);
    }
}
