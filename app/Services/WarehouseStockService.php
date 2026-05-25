<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WarehouseStockService
{
    /**
     * Get available stock for a product in a warehouse
     */
    public function getAvailableStock(int $productId, int $warehouseId): float
    {
        $stock = WarehouseStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNull('product_variant_id')
            ->first();

        return $stock ? $stock->available_quantity : 0;
    }

    /**
     * Check if product has enough stock
     */
    public function hasEnoughStock(int $productId, float $quantity, int $warehouseId): bool
    {
        $available = $this->getAvailableStock($productId, $warehouseId);

        return $available >= $quantity;
    }

    /**
     * Increase stock (Stock In)
     */
    public function increaseStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        string $referenceType,
        int $referenceId,
        float $unitCost = 0,
        string $notes = ''
    ): WarehouseStock {
        $this->ensurePositiveQuantity($quantity, 'Stock-in quantity');

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $referenceType,
            $referenceId,
            $unitCost,
            $notes
        ) {
            $branchId = $this->resolveBranchId($warehouseId);

            // Get or create warehouse stock with row lock to prevent race conditions
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = WarehouseStock::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'sku' => $this->generateSKU($productId),
                    'physical_quantity' => 0,
                    'reserved_quantity' => 0,
                    'reorder_point' => 5,
                    'reorder_quantity' => 0,
                    'average_cost' => $unitCost,
                ]);
            }

            if (empty($stock->branch_id)) {
                $stock->branch_id = $branchId;
                $stock->save();
            }

            // Calculate proper weighted average cost (WAC)
            $oldQuantity = (float) $stock->physical_quantity;
            $oldCost = (float) $stock->average_cost;
            $newQuantity = $oldQuantity + $quantity;
            $newAvailable = $newQuantity - $stock->reserved_quantity;

            if ($unitCost > 0 && $newQuantity > 0) {
                $newAverageCost = (($oldQuantity * $oldCost) + ($quantity * $unitCost)) / $newQuantity;
            } else {
                $newAverageCost = $oldCost;
            }

            $stock->update([
                'physical_quantity' => $newQuantity,
                'available_quantity' => $newAvailable,
                'average_cost' => round($newAverageCost, 2),
                'last_stock_in_date' => now(),
            ]);

            // Refresh to get computed attributes
            $stock->refresh();
            $normalizedReferenceType = $this->normalizeReferenceType($referenceType);
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $this->getMovementType($normalizedReferenceType, 'in'),
                'reference_type' => $normalizedReferenceType,
                'reference_id' => $referenceId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'balance_after' => $newQuantity,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            // Clear cache
            $this->clearStockCache($warehouseId, $productId);

            Log::info("Stock increased for product {$productId}", [
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'new_quantity' => $newQuantity,
                'reference_type' => $normalizedReferenceType,
            ]);

            return $stock;
        });
    }

    /**
     * Decrease stock (Stock Out)
     */
    public function decreaseStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        string $referenceType,
        int $referenceId,
        string $notes = ''
    ): WarehouseStock {
        $this->ensurePositiveQuantity($quantity, 'Stock-out quantity');

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $referenceType,
            $referenceId,
            $notes
        ) {
            $branchId = $this->resolveBranchId($warehouseId);

            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw new \Exception("Stock record not found for product {$productId} in warehouse {$warehouseId}");
            }

            if ($stock->available_quantity < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$stock->available_quantity}, Required: {$quantity}");
            }

            // Update physical quantity
            $newQuantity = $stock->physical_quantity - $quantity;
            $newAvailable = $newQuantity - $stock->reserved_quantity;
            $stock->update([
                'physical_quantity' => $newQuantity,
                'available_quantity' => $newAvailable,
                'last_stock_out_date' => now(),
            ]);

            // Refresh to get computed attributes
            $stock->refresh();
            $normalizedReferenceType = $this->normalizeReferenceType($referenceType);
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $this->getMovementType($normalizedReferenceType, 'out'),
                'reference_type' => $normalizedReferenceType,
                'reference_id' => $referenceId,
                'quantity' => -$quantity,
                'unit_cost' => $stock->average_cost,
                'balance_after' => $newQuantity,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            // Clear cache
            $this->clearStockCache($warehouseId, $productId);

            Log::info("Stock decreased for product {$productId}", [
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'new_quantity' => $newQuantity,
                'reference_type' => $normalizedReferenceType,
            ]);

            return $stock;
        });
    }

    /**
     * Reserve stock (for pending orders)
     */
    public function reserveStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $referenceId = null,
        string $referenceType = 'order',
        string $notes = ''
    ): WarehouseStock {
        $this->ensurePositiveQuantity($quantity, 'Reserve quantity');

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $referenceId,
            $referenceType,
            $notes
        ) {
            $branchId = $this->resolveBranchId($warehouseId);

            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->available_quantity < $quantity) {
                throw new \Exception('Cannot reserve stock. Insufficient available quantity.');
            }

            $newReserved = $stock->reserved_quantity + $quantity;
            $newAvailable = $stock->physical_quantity - $newReserved;
            $stock->update([
                'reserved_quantity' => $newReserved,
                'available_quantity' => $newAvailable,
            ]);

            // Refresh to get computed attributes
            $stock->refresh();

            $normalizedReferenceType = $this->normalizeReferenceType($referenceType);
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => 'reservation',
                'reference_type' => $normalizedReferenceType,
                'reference_id' => $referenceId,
                'quantity' => -$quantity,
                'unit_cost' => $stock->average_cost,
                'balance_after' => $newAvailable,
                'notes' => $notes !== '' ? $notes : ($referenceId ? "Reserved stock for {$normalizedReferenceType} #{$referenceId}" : 'Reserved stock'),
                'created_by' => auth()->id(),
            ]);

            $this->clearStockCache($warehouseId, $productId);

            return $stock;
        });
    }

    /**
     * Release reserved stock
     */
    public function releaseReservedStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $referenceId = null,
        string $referenceType = 'order',
        string $notes = ''
    ): WarehouseStock {
        $this->ensurePositiveQuantity($quantity, 'Release quantity');

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $referenceId,
            $referenceType,
            $notes
        ) {
            $branchId = $this->resolveBranchId($warehouseId);

            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->reserved_quantity < $quantity) {
                throw new \Exception('Cannot release more stock than reserved.');
            }

            $newReserved = max(0, $stock->reserved_quantity - $quantity);
            $newAvailable = $stock->physical_quantity - $newReserved;
            $stock->update([
                'reserved_quantity' => $newReserved,
                'available_quantity' => $newAvailable,
            ]);

            // Refresh to get computed attributes
            $stock->refresh();

            $normalizedReferenceType = $this->normalizeReferenceType($referenceType);
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => 'release',
                'reference_type' => $normalizedReferenceType,
                'reference_id' => $referenceId,
                'quantity' => $quantity,
                'unit_cost' => $stock->average_cost,
                'balance_after' => $newAvailable,
                'notes' => $notes !== '' ? $notes : ($referenceId ? "Released reserved stock for {$normalizedReferenceType} #{$referenceId}" : 'Released reserved stock'),
                'created_by' => auth()->id(),
            ]);

            $this->clearStockCache($warehouseId, $productId);

            return $stock;
        });
    }

    /**
     * Commit reserved stock to sold stock when shipment is confirmed.
     */
    public function commitReservedStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $referenceId = null,
        string $referenceType = 'order',
        string $notes = ''
    ): WarehouseStock {
        $this->ensurePositiveQuantity($quantity, 'Commit quantity');

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $referenceId,
            $referenceType,
            $notes
        ) {
            $branchId = $this->resolveBranchId($warehouseId);

            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw new \Exception("Stock record not found for product {$productId} in warehouse {$warehouseId}");
            }

            if ($stock->reserved_quantity < $quantity) {
                throw new \Exception("Cannot commit more stock than reserved. Reserved: {$stock->reserved_quantity}, Requested: {$quantity}");
            }

            if ($stock->physical_quantity < $quantity) {
                throw new \Exception("Cannot commit more stock than physical quantity. Physical: {$stock->physical_quantity}, Requested: {$quantity}");
            }

            $newPhysical = $stock->physical_quantity - $quantity;
            $newReserved = $stock->reserved_quantity - $quantity;
            $newAvailable = $newPhysical - $newReserved;
            $stock->update([
                'physical_quantity' => $newPhysical,
                'reserved_quantity' => $newReserved,
                'available_quantity' => $newAvailable,
                'last_stock_out_date' => now(),
            ]);

            $stock->refresh();

            $normalizedReferenceType = $this->normalizeReferenceType($referenceType);
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => 'sale',
                'reference_type' => $normalizedReferenceType,
                'reference_id' => $referenceId,
                'quantity' => -$quantity,
                'unit_cost' => $stock->average_cost,
                'balance_after' => $newPhysical,
                'notes' => $notes !== '' ? $notes : ($referenceId ? "Committed reserved stock for {$normalizedReferenceType} #{$referenceId}" : 'Committed reserved stock'),
                'created_by' => auth()->id(),
            ]);

            $this->clearStockCache($warehouseId, $productId);

            return $stock;
        });
    }

    /**     * Adjust stock (increase or decrease based on adjustment amount)
     */
    public function adjustStock(
        int $warehouseId,
        int $productId,
        float $adjustment,
        string $reason = '',
        int $referenceId = 0
    ): WarehouseStock {
        if ($adjustment == 0.0) {
            throw new \InvalidArgumentException('Adjustment quantity must be non-zero.');
        }

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $adjustment,
            $reason,
            $referenceId
        ) {
            $branchId = $this->resolveBranchId($warehouseId);

            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw new \Exception("Stock record not found for product {$productId} in warehouse {$warehouseId}");
            }

            $oldQuantity = $stock->physical_quantity;
            $newQuantity = $oldQuantity + $adjustment;

            // Validate new quantity isn't negative
            if ($newQuantity < 0) {
                throw new \Exception("Cannot reduce stock below 0. Current: {$oldQuantity}, Adjustment: {$adjustment}");
            }
            if ($newQuantity < $stock->reserved_quantity) {
                throw new \Exception("Cannot reduce stock below reserved quantity ({$stock->reserved_quantity}).");
            }

            // Update physical quantity
            $newAvailable = $newQuantity - $stock->reserved_quantity;
            $stock->update([
                'physical_quantity' => $newQuantity,
                'available_quantity' => $newAvailable,
            ]);

            // Refresh to get computed attributes
            $stock->refresh();

            // Determine movement type based on direction
            $movementType = $adjustment > 0 ? 'adjustment_in' : 'adjustment_out';

            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $movementType,
                'reference_type' => 'stock_adjustment',
                'reference_id' => $referenceId,
                'quantity' => $adjustment,
                'unit_cost' => $stock->average_cost,
                'balance_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => auth()->id(),
            ]);

            // Clear cache
            $this->clearStockCache($warehouseId, $productId);

            Log::info("Stock adjusted for product {$productId}", [
                'warehouse_id' => $warehouseId,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'adjustment' => $adjustment,
                'reason' => $reason,
            ]);

            return $stock;
        });
    }

    /**     * Get total stock across all warehouses for a product
     */
    public function getTotalStockForProduct(int $productId): float
    {
        return WarehouseStock::where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->sum('physical_quantity');
    }

    /**
     * Get movement type based on reference type and direction
     */
    private function getMovementType(string $referenceType, string $direction): string
    {
        $typeMap = [
            'grn' => 'grn',
            'order' => 'sale',
            'warehouse_transfer' => $direction === 'in' ? 'transfer_in' : 'transfer_out',
            'stock_adjustment' => $direction === 'in' ? 'adjustment_in' : 'adjustment_out',
            'stock_loss' => 'loss',
            'product_creation' => 'initial_stock',
            'product_sync' => 'initial_stock',
        ];

        return $typeMap[$referenceType] ?? ($direction === 'in' ? 'adjustment_in' : 'adjustment_out');
    }

    private function normalizeReferenceType(string $referenceType): string
    {
        $referenceMap = [
            'transfer' => 'warehouse_transfer',
            'adjustment' => 'stock_adjustment',
        ];

        return $referenceMap[$referenceType] ?? $referenceType;
    }

    /**
     * Generate SKU if not exists
     */
    private function generateSKU(int $productId): string
    {
        $product = Product::find($productId);

        return $product?->sku ?? $product?->product_code ?? ('SKU-'.$productId);
    }

    /**
     * Clear stock cache
     */
    private function clearStockCache(int $warehouseId, int $productId): void
    {
        Cache::forget("stock_summary_{$warehouseId}_{$productId}");
        Cache::forget("stock_balance_{$warehouseId}_{$productId}");
        Cache::forget("product_stock_{$productId}");
        Cache::forget("warehouse_total_value_{$warehouseId}");
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

    private function ensurePositiveQuantity(float $quantity, string $label): void
    {
        if (! is_finite($quantity) || $quantity <= 0) {
            throw new \InvalidArgumentException($label.' must be greater than zero.');
        }
    }
}
