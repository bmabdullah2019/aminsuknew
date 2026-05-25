<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockMovementService
{
    /**
     * Record stock in movement (GRN, Transfer In, Adjustment In)
     */
    public function recordStockIn(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $sku = $data['sku'] ?? Product::whereKey($data['product_id'])->value('sku') ?? ('SKU-'.$data['product_id']);
            $branchId = $data['branch_id'] ?? $this->resolveBranchId((int) $data['warehouse_id']);
            $preferredVariant = null;
            if (isset($data['product_variant_id']) && $data['product_variant_id'] !== '' && $data['product_variant_id'] !== null) {
                $preferredVariant = (int) $data['product_variant_id'];
                if ($preferredVariant <= 0) {
                    $preferredVariant = null;
                }
            }

            $variantId = $this->resolveProductVariantId(
                (int) $data['product_id'],
                (int) $data['warehouse_id'],
                $preferredVariant
            );

            $lookupKeys = [
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
            ];
            if (Schema::hasColumn((new WarehouseStock)->getTable(), 'product_variant_id')) {
                $lookupKeys['product_variant_id'] = $variantId;
            }

            // Get or create stock record (variant-aware so simple vs variable products don't collide)
            $stock = WarehouseStock::firstOrCreate(
                $lookupKeys,
                [
                    'branch_id' => $branchId,
                    'sku' => $sku,
                    'physical_quantity' => 0,
                    'reserved_quantity' => 0,
                    'available_quantity' => 0,
                    'reorder_point' => $data['reorder_point'] ?? 0,
                    'reorder_quantity' => $data['reorder_quantity'] ?? 0,
                    'average_cost' => $data['unit_cost'] ?? 0,
                    'total_value' => 0,
                ]
            );

            if (empty($stock->branch_id)) {
                $stock->branch_id = $branchId;
            }

            if (Schema::hasColumn($stock->getTable(), 'product_variant_id') && $variantId !== null && empty($stock->product_variant_id)) {
                $stock->product_variant_id = $variantId;
            }

            if (! empty($sku) && $stock->sku !== $sku) {
                $stock->sku = $sku;
            }

            if (! empty($data['expiry_date'])) {
                $incomingExpiry = Carbon::parse($data['expiry_date'])->startOfDay();
                $currentExpiry = $stock->expiry_date ? Carbon::parse($stock->expiry_date)->startOfDay() : null;

                // Keep the nearest expiry date on aggregated stock rows.
                if (! $currentExpiry || $incomingExpiry->lt($currentExpiry)) {
                    $stock->expiry_date = $incomingExpiry->toDateString();
                }
            }

            if ($stock->isDirty(['branch_id', 'product_variant_id', 'sku', 'expiry_date'])) {
                $stock->save();
            }

            // Update stock quantities
            $quantity = abs($data['quantity']);
            $stock->physical_quantity += $quantity;
            $stock->available_quantity += $quantity;
            $stock->last_stock_in_date = now();

            // Update average cost if provided
            if (isset($data['unit_cost'])) {
                $this->updateAverageCost($stock, $quantity, $data['unit_cost']);
            } else {
                $stock->total_value = $stock->physical_quantity * ((float) $stock->average_cost);
            }

            $stock->save();

            // Create movement record
            $movement = StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $variantId ?? $stock->product_variant_id,
                'type' => $data['type'] ?? 'adjustment_in',
                'reference_type' => $data['reference_type'] ?? 'stock_adjustment',
                'reference_id' => $data['reference_id'] ?? null,
                'quantity' => $quantity,
                'unit_cost' => $data['unit_cost'] ?? null,
                'balance_after' => $stock->available_quantity,
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return $movement;
        });
    }

    /**
     * Record stock out movement (Sale, Transfer Out, Adjustment Out, Loss)
     */
    public function recordStockOut(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $branchId = $data['branch_id'] ?? $this->resolveBranchId((int) $data['warehouse_id']);

            $stock = WarehouseStock::where('warehouse_id', $data['warehouse_id'])
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $variantId = $stock->product_variant_id
                ?: $this->resolveProductVariantId(
                    (int) $data['product_id'],
                    (int) $data['warehouse_id'],
                    isset($data['product_variant_id']) ? (int) $data['product_variant_id'] : null
                );

            $quantity = abs($data['quantity']);

            // Check available stock
            if ($stock->available_quantity < $quantity && ! ($data['allow_negative'] ?? false)) {
                throw new \Exception("Insufficient stock. Available: {$stock->available_quantity}, Required: {$quantity}");
            }

            // Update stock quantities
            $stock->physical_quantity -= $quantity;
            $stock->available_quantity -= $quantity;
            $stock->last_stock_out_date = now();
            $stock->total_value = $stock->physical_quantity * ((float) $stock->average_cost);
            $stock->save();

            // Create movement record
            $movement = StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $variantId,
                'type' => $data['type'] ?? 'adjustment_out',
                'reference_type' => $data['reference_type'] ?? 'stock_adjustment',
                'reference_id' => $data['reference_id'] ?? null,
                'quantity' => -$quantity,
                'unit_cost' => $data['unit_cost'] ?? $stock->average_cost,
                'balance_after' => $stock->available_quantity,
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return $movement;
        });
    }

    /**
     * Record transfer movement (from one warehouse to another)
     */
    public function recordTransfer(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Record stock out from source warehouse
            $outMovement = $this->recordStockOut([
                'warehouse_id' => $data['from_warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'quantity' => $data['quantity'],
                'type' => 'transfer_out',
                'reference_type' => 'warehouse_transfer',
                'reference_id' => $data['transfer_id'],
                'notes' => "Transfer to warehouse #{$data['to_warehouse_id']}",
            ]);

            // Record stock in to destination warehouse
            $inMovement = $this->recordStockIn([
                'warehouse_id' => $data['to_warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'quantity' => $data['quantity'],
                'type' => 'transfer_in',
                'reference_type' => 'warehouse_transfer',
                'reference_id' => $data['transfer_id'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'notes' => "Transfer from warehouse #{$data['from_warehouse_id']}",
            ]);

            return [
                'out_movement' => $outMovement,
                'in_movement' => $inMovement,
            ];
        });
    }

    /**
     * Record adjustment movement
     */
    public function recordAdjustment(array $data): StockMovement
    {
        $quantity = $data['quantity'];
        $adjustmentType = $data['adjustment_type']; // 'increase' or 'decrease'

        if ($adjustmentType === 'increase') {
            return $this->recordStockIn([
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'quantity' => abs($quantity),
                'type' => 'adjustment_in',
                'reference_type' => 'stock_adjustment',
                'reference_id' => $data['adjustment_id'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'notes' => $data['notes'] ?? 'Stock adjustment - increase',
            ]);
        } else {
            return $this->recordStockOut([
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'quantity' => abs($quantity),
                'type' => 'adjustment_out',
                'reference_type' => 'stock_adjustment',
                'reference_id' => $data['adjustment_id'],
                'allow_negative' => $data['allow_negative'] ?? false,
                'notes' => $data['notes'] ?? 'Stock adjustment - decrease',
            ]);
        }
    }

    /**
     * Get movement history with filters
     */
    public function getMovementHistory(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = StockMovement::with(['warehouse', 'product', 'creator']);

        // Filter by warehouse
        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        // Filter by product
        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // Filter by type
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by date range
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Filter by reference
        if (isset($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        if (isset($filters['reference_id'])) {
            $query->where('reference_id', $filters['reference_id']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Update average cost using weighted average method
     */
    private function updateAverageCost(WarehouseStock $stock, float $newQuantity, float $newUnitCost): void
    {
        $existingQuantity = $stock->physical_quantity - $newQuantity;
        $existingCost = $stock->average_cost ?? 0;

        if ($existingQuantity <= 0) {
            // First entry or complete replacement
            $stock->average_cost = $newUnitCost;
        } else {
            // Weighted average
            $totalCost = ($existingQuantity * $existingCost) + ($newQuantity * $newUnitCost);
            $totalQuantity = $existingQuantity + $newQuantity;
            $stock->average_cost = $totalCost / $totalQuantity;
        }

        // Update total value
        $stock->total_value = $stock->physical_quantity * $stock->average_cost;
    }

    private function resolveBranchId(int $warehouseId): int
    {
        $branchId = (int) (Warehouse::query()
            ->whereKey($warehouseId)
            ->value('branch_id') ?? 0);

        if ($branchId > 0) {
            return $branchId;
        }

        if (! Schema::hasTable('branches')) {
            return 1;
        }

        return (int) (DB::table('branches')->where('code', 'MAIN')->value('id')
            ?? DB::table('branches')->value('id')
            ?? 1);
    }

    /**
     * Resolve variant for warehouse stock rows. Returns null for simple products with no variants.
     * Never auto-creates ProductVariant rows here (that could violate DB constraints and broke GRN approval).
     */
    private function resolveProductVariantId(int $productId, int $warehouseId, ?int $preferredVariantId = null): ?int
    {
        if ($preferredVariantId && $preferredVariantId > 0) {
            $preferredVariant = ProductVariant::query()
                ->whereKey($preferredVariantId)
                ->where('product_id', $productId)
                ->value('id');

            if ($preferredVariant) {
                return (int) $preferredVariant;
            }
        }

        $existingVariantId = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereNotNull('product_variant_id')
            ->value('product_variant_id');

        if ($existingVariantId) {
            return (int) $existingVariantId;
        }

        $firstVariantId = ProductVariant::query()
            ->where('product_id', $productId)
            ->orderBy('id')
            ->value('id');

        if ($firstVariantId) {
            return (int) $firstVariantId;
        }

        return null;
    }
}
