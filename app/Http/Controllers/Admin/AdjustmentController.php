<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustmentRequest;
use App\Http\Requests\LossRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockLoss;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockMovementService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdjustmentController extends Controller
{
    protected $movementService;

    public function __construct(StockMovementService $movementService)
    {
        $this->movementService = $movementService;

        // Adjustment permissions
        $this->middleware('permission:adjustment-list', ['only' => ['index']]);
        $this->middleware('permission:adjustment-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:adjustment-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:adjustment-delete', ['only' => ['destroy']]);
        $this->middleware('permission:adjustment-approve', ['only' => ['approve']]);
        $this->middleware('permission:adjustment-view', ['only' => ['show']]);

        // Loss permissions
        $this->middleware('permission:loss-list', ['only' => ['lossIndex']]);
        $this->middleware('permission:loss-create', ['only' => ['lossCreate', 'lossStore']]);
        $this->middleware('permission:loss-edit', ['only' => ['lossEdit', 'lossUpdate']]);
        $this->middleware('permission:loss-delete', ['only' => ['lossDestroy']]);
        $this->middleware('permission:loss-approve', ['only' => ['lossApprove']]);
        $this->middleware('permission:loss-view', ['only' => ['lossShow']]);
    }

    // ==================== ADJUSTMENT METHODS ====================

    public function index(Request $request)
    {
        $query = StockAdjustment::with('warehouse')->withCount('items');

        $status = (string) $request->input('status', '');
        $allowedStatuses = ['draft', 'pending', 'approved', 'rejected'];
        if (in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        $warehouseId = (int) $request->input('warehouse_id', 0);
        if ($warehouseId > 0) {
            $query->where('warehouse_id', $warehouseId);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where('adjustment_number', 'like', '%'.$search.'%');
        }

        $adjustments = $query->latest()->paginate(20);
        $warehouses = Warehouse::active()->get();

        return view('backEnd.adjustment.index', compact('adjustments', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('backEnd.adjustment.create', compact('warehouses', 'products'));
    }

    public function store(AdjustmentRequest $request)
    {
        $validated = $request->validated();

        try {
            $adjustment = DB::transaction(function () use ($validated) {
                $adjustment = StockAdjustment::create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'adjustment_date' => $validated['adjustment_date'],
                    'adjustment_type' => $validated['adjustment_type'],
                    'reason' => $validated['reason'],
                    'reason_details' => $validated['reason_details'] ?? null,
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);

                $totalValueImpact = $this->syncAdjustmentItems($adjustment, $validated);
                $adjustment->update(['total_value_impact' => $totalValueImpact]);

                return $adjustment;
            });

            Toastr::success('Stock adjustment created successfully', 'Success');

            return redirect()->route('admin.adjustment.show', $adjustment->id);
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            Log::error('Failed to create stock adjustment', [
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('Failed to create adjustment. Please try again.', 'Error');

            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        $adjustment = StockAdjustment::with(['warehouse', 'items.product', 'approver'])->findOrFail($id);

        return view('backEnd.adjustment.show', compact('adjustment'));
    }

    public function edit($id)
    {
        $adjustment = StockAdjustment::with('items')->findOrFail($id);

        if ($adjustment->status !== 'pending') {
            Toastr::error('Only pending adjustments can be edited', 'Error');

            return redirect()->route('admin.adjustment.show', $id);
        }

        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('backEnd.adjustment.edit', compact('adjustment', 'warehouses', 'products'));
    }

    public function update(AdjustmentRequest $request, $id)
    {
        $validated = $request->validated();

        try {
            $adjustment = DB::transaction(function () use ($validated, $id) {
                $adjustment = StockAdjustment::lockForUpdate()->findOrFail($id);

                if ($adjustment->status !== 'pending') {
                    throw new \DomainException('Only pending adjustments can be updated');
                }

                $adjustment->update([
                    'warehouse_id' => $validated['warehouse_id'],
                    'adjustment_date' => $validated['adjustment_date'],
                    'adjustment_type' => $validated['adjustment_type'],
                    'reason' => $validated['reason'],
                    'reason_details' => $validated['reason_details'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $totalValueImpact = $this->syncAdjustmentItems($adjustment, $validated);
                $adjustment->update(['total_value_impact' => $totalValueImpact]);

                return $adjustment;
            });

            Toastr::success('Stock adjustment updated successfully', 'Success');

            return redirect()->route('admin.adjustment.show', $adjustment->id);
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            Log::error('Failed to update stock adjustment', [
                'adjustment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('Failed to update adjustment. Please try again.', 'Error');

            return redirect()->back()->withInput();
        }
    }

    public function approve($id)
    {
        try {
            $adjustment = DB::transaction(function () use ($id) {
                $adjustment = StockAdjustment::with('items')->lockForUpdate()->findOrFail($id);

                if ($adjustment->status !== 'pending') {
                    throw new \DomainException('Only pending adjustments can be approved');
                }

                $adjustment->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);

                foreach ($adjustment->items as $item) {
                    $quantityDifference = abs((float) $item->adjusted_quantity - (float) $item->system_quantity);
                    if ($quantityDifference <= 0) {
                        continue;
                    }

                    $this->movementService->recordAdjustment([
                        'warehouse_id' => $adjustment->warehouse_id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $quantityDifference,
                        'adjustment_type' => $adjustment->adjustment_type,
                        'adjustment_id' => $adjustment->id,
                        'unit_cost' => $item->unit_cost,
                        'notes' => $item->notes ?? "Stock adjustment: {$adjustment->reason}",
                    ]);
                }

                return $adjustment;
            });

            Toastr::success('Stock adjustment approved and stock updated successfully', 'Success');

            return redirect()->route('admin.adjustment.show', $adjustment->id);
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage(), 'Error');

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Failed to approve stock adjustment', [
                'adjustment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('Failed to approve adjustment. Please try again.', 'Error');

            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $adjustment = StockAdjustment::lockForUpdate()->findOrFail($id);

                if ($adjustment->status !== 'pending') {
                    throw new \DomainException('Only pending adjustments can be deleted');
                }

                $adjustment->delete();
            });

            Toastr::success('Stock adjustment deleted successfully', 'Success');

            return redirect()->route('admin.adjustment.index');
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage(), 'Error');

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Failed to delete stock adjustment', [
                'adjustment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('Failed to delete adjustment. Please try again.', 'Error');

            return redirect()->back();
        }
    }

    // ==================== LOSS METHODS ====================

    public function lossIndex(Request $request)
    {
        $query = StockLoss::with('warehouse')->withCount('items');

        $status = (string) $request->input('status', '');
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $warehouseId = (int) $request->input('warehouse_id', 0);
        if ($warehouseId > 0) {
            $query->where('warehouse_id', $warehouseId);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where('loss_number', 'like', '%'.$search.'%');
        }

        $losses = $query->latest()->paginate(20);
        $warehouses = Warehouse::active()->get();

        return view('backEnd.loss.index', compact('losses', 'warehouses'));
    }

    public function lossCreate()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('backEnd.loss.create', compact('warehouses', 'products'));
    }

    public function lossStore(LossRequest $request)
    {
        $validated = $request->validated();

        try {
            $loss = DB::transaction(function () use ($validated) {
                $firstProductId = (int) $validated['items'][0]['product_id'];

                // The header still carries legacy single-product fields for backward compatibility.
                $loss = StockLoss::create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'product_id' => $firstProductId,
                    'loss_date' => $validated['loss_date'],
                    'loss_type' => $validated['loss_type'],
                    'quantity' => 0,
                    'unit_cost' => 0,
                    'reason_details' => $validated['notes'] ?? '',
                    'status' => 'pending',
                ]);

                $summary = $this->syncLossItems($loss, $validated);
                $avgUnitCost = $summary['total_qty'] > 0 ? ($summary['total_value'] / $summary['total_qty']) : 0;

                $loss->update([
                    'quantity' => $summary['total_qty'],
                    'unit_cost' => $avgUnitCost,
                    'product_id' => $summary['first_product_id'],
                ]);

                return $loss;
            });

            Toastr::success('Stock loss recorded successfully', 'Success');

            return redirect()->route('admin.loss.show', $loss->id);
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            Log::error('Failed to record stock loss', [
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('Failed to record loss. Please try again.', 'Error');

            return redirect()->back()->withInput();
        }
    }

    public function lossShow($id)
    {
        $loss = StockLoss::with(['warehouse', 'items.product', 'approver'])->findOrFail($id);

        return view('backEnd.loss.show', compact('loss'));
    }

    public function lossEdit($id)
    {
        $loss = StockLoss::with(['items.product'])->findOrFail($id);

        if ($loss->status !== 'pending') {
            Toastr::error('Only pending losses can be edited', 'Error');

            return redirect()->route('admin.loss.show', $id);
        }

        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('backEnd.loss.edit', compact('loss', 'warehouses', 'products'));
    }

    public function lossUpdate(LossRequest $request, $id)
    {
        $validated = $request->validated();

        try {
            $loss = DB::transaction(function () use ($validated, $id) {
                $loss = StockLoss::lockForUpdate()->findOrFail($id);

                if ($loss->status !== 'pending') {
                    throw new \DomainException('Only pending losses can be updated');
                }

                $loss->update([
                    'warehouse_id' => $validated['warehouse_id'],
                    'loss_date' => $validated['loss_date'],
                    'loss_type' => $validated['loss_type'],
                    'reason_details' => $validated['notes'] ?? '',
                ]);

                $summary = $this->syncLossItems($loss, $validated);
                $avgUnitCost = $summary['total_qty'] > 0 ? ($summary['total_value'] / $summary['total_qty']) : 0;

                $loss->update([
                    'quantity' => $summary['total_qty'],
                    'unit_cost' => $avgUnitCost,
                    'product_id' => $summary['first_product_id'],
                ]);

                return $loss;
            });

            Toastr::success('Stock loss updated successfully', 'Success');

            return redirect()->route('admin.loss.show', $loss->id);
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            Log::error('Failed to update stock loss', [
                'loss_id' => $id,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('Failed to update loss. Please try again.', 'Error');

            return redirect()->back()->withInput();
        }
    }

    public function lossApprove($id)
    {
        try {
            $loss = DB::transaction(function () use ($id) {
                $loss = StockLoss::with('items')->lockForUpdate()->findOrFail($id);

                if ($loss->status !== 'pending') {
                    throw new \DomainException('Only pending losses can be approved');
                }

                $loss->update([
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'status' => 'approved',
                ]);

                foreach ($loss->items as $item) {
                    $this->movementService->recordStockOut([
                        'warehouse_id' => $loss->warehouse_id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'type' => 'loss',
                        'reference_type' => 'stock_loss',
                        'reference_id' => $loss->id,
                        'unit_cost' => $item->unit_cost,
                        'notes' => $item->notes ?? "Stock loss: {$loss->loss_type}",
                    ]);
                }

                return $loss;
            });

            Toastr::success('Stock loss approved and stock updated successfully', 'Success');

            return redirect()->route('admin.loss.show', $loss->id);
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage(), 'Error');

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Failed to approve stock loss', [
                'loss_id' => $id,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('Failed to approve loss. Please try again.', 'Error');

            return redirect()->back();
        }
    }

    public function lossDestroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $loss = StockLoss::lockForUpdate()->findOrFail($id);

                if ($loss->status !== 'pending') {
                    throw new \DomainException('Only pending losses can be deleted');
                }

                $loss->delete();
            });

            Toastr::success('Stock loss deleted successfully', 'Success');

            return redirect()->route('admin.loss.index');
        } catch (\DomainException $e) {
            Toastr::error($e->getMessage(), 'Error');

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Failed to delete stock loss', [
                'loss_id' => $id,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('Failed to delete loss. Please try again.', 'Error');

            return redirect()->back();
        }
    }

    private function syncAdjustmentItems(StockAdjustment $adjustment, array $validated): float
    {
        $adjustment->items()->delete();
        $totalValueImpact = 0;

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            if (! $product) {
                throw new \DomainException('One or more selected products are invalid.');
            }

            // Get first variant if not specified
            $variantId = $item['product_variant_id'] ?? null;
            if (! $variantId) {
                $variant = \App\Models\ProductVariant::where('product_id', $item['product_id'])->orderBy('id')->first();
                $variantId = $variant?->id;
            }

            $stock = WarehouseStock::where('warehouse_id', $validated['warehouse_id'])
                ->where('product_id', $item['product_id'])
                ->where('product_variant_id', $variantId)
                ->first();

            $systemQuantity = (float) ($stock->physical_quantity ?? 0);
            $adjustmentQuantity = (float) $item['quantity'];

            if ($validated['adjustment_type'] === 'decrease' && $adjustmentQuantity > $systemQuantity) {
                throw new \DomainException("Adjustment quantity for {$product->name} exceeds current stock ({$systemQuantity}).");
            }

            $adjustedQuantity = $validated['adjustment_type'] === 'increase'
                ? $systemQuantity + $adjustmentQuantity
                : $systemQuantity - $adjustmentQuantity;

            $unitCost = array_key_exists('unit_cost', $item) && $item['unit_cost'] !== null
                ? (float) $item['unit_cost']
                : (float) ($stock->average_cost ?? 0);

            $adjustment->items()->create([
                'product_id' => $item['product_id'],
                'product_variant_id' => $variantId,
                'sku' => $product->sku ?? '',
                'system_quantity' => $systemQuantity,
                'adjusted_quantity' => $adjustedQuantity,
                'unit_cost' => $unitCost,
                'notes' => $item['notes'] ?? null,
            ]);

            $totalValueImpact += abs($adjustedQuantity - $systemQuantity) * $unitCost;
        }

        return $totalValueImpact;
    }

    private function syncLossItems(StockLoss $loss, array $validated): array
    {
        $loss->items()->delete();
        $totalValue = 0;
        $totalQty = 0;
        $firstProductId = (int) $validated['items'][0]['product_id'];

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            if (! $product) {
                throw new \DomainException('One or more selected products are invalid.');
            }

            $stock = WarehouseStock::where('warehouse_id', $validated['warehouse_id'])
                ->where('product_id', $item['product_id'])
                ->first();

            $unitCost = (float) ($stock->average_cost ?? 0);
            $itemQuantity = (float) $item['quantity'];
            $itemValue = $itemQuantity * $unitCost;

            $totalValue += $itemValue;
            $totalQty += $itemQuantity;

            $loss->items()->create([
                'product_id' => $item['product_id'],
                'sku' => $product->sku ?? '',
                'quantity' => $itemQuantity,
                'unit_cost' => $unitCost,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        return [
            'total_qty' => $totalQty,
            'total_value' => $totalValue,
            'first_product_id' => $firstProductId,
        ];
    }
}
