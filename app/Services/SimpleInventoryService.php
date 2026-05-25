<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * SIMPLE INVENTORY SERVICE
 * Handles all stock operations with clean, easy-to-understand methods
 */
class SimpleInventoryService
{
    /**
     * Check available stock for a product
     */
    public function checkStock(int $productId, ?int $warehouseId = null): float
    {
        if ($warehouseId) {
            $stock = WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            return $stock?->available_quantity ?? 0;
        }

        // Get total across all warehouses
        return WarehouseStock::where('product_id', $productId)
            ->sum('available_quantity');
    }

    /**
     * Add stock (Goods Receipt)
     */
    public function addStock(int $productId, int $warehouseId, float $quantity, float $unitCost = 0, string $notes = ''): bool
    {
        try {
            $warehouse = Warehouse::findOrFail($warehouseId);
            $branchId = (int) ($warehouse->branch_id ?? $this->defaultBranchId());

            // Get or create warehouse stock
            $stock = WarehouseStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['branch_id' => $branchId, 'physical_quantity' => 0, 'available_quantity' => 0]
            );

            // Update quantities
            $stock->update([
                'physical_quantity' => $stock->physical_quantity + $quantity,
                'available_quantity' => $stock->available_quantity + $quantity,
            ]);

            // Log movement
            $this->logMovement(
                warehouseId: $warehouseId,
                productId: $productId,
                type: 'grn',
                referenceType: 'grn',
                quantity: $quantity,
                unitCost: $unitCost,
                notes: $notes
            );

            $this->clearCache($productId, $warehouseId);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to add stock: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove stock (Sale, Loss, etc)
     */
    public function removeStock(int $productId, int $warehouseId, float $quantity, string $reason = 'sale', string $notes = ''): bool
    {
        try {
            $stock = WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->firstOrFail();

            if ($stock->available_quantity < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$stock->available_quantity}, Required: {$quantity}");
            }

            $stock->update([
                'physical_quantity' => $stock->physical_quantity - $quantity,
                'available_quantity' => $stock->available_quantity - $quantity,
            ]);

            // Log movement
            $this->logMovement(
                warehouseId: $warehouseId,
                productId: $productId,
                type: $reason === 'loss' ? 'loss' : 'sale',
                referenceType: $reason,
                quantity: $quantity,
                notes: $notes
            );

            $this->clearCache($productId, $warehouseId);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to remove stock: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Adjust stock (Fix counting errors)
     */
    public function adjustStock(int $productId, int $warehouseId, float $quantity, string $reason = '', string $notes = ''): bool
    {
        try {
            $stock = WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->firstOrFail();

            // Quantity can be positive or negative
            $newPhysical = $stock->physical_quantity + $quantity;
            $newAvailable = $stock->available_quantity + $quantity;

            if ($newPhysical < 0 || $newAvailable < 0) {
                throw new \Exception('Adjustment would result in negative stock');
            }

            $stock->update([
                'physical_quantity' => $newPhysical,
                'available_quantity' => $newAvailable,
            ]);

            // Log movement
            $type = $quantity > 0 ? 'adjustment_in' : 'adjustment_out';
            $this->logMovement(
                warehouseId: $warehouseId,
                productId: $productId,
                type: $type,
                referenceType: 'adjustment',
                quantity: abs($quantity),
                notes: $reason.' - '.$notes
            );

            $this->clearCache($productId, $warehouseId);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to adjust stock: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock(int $productId, int $fromWarehouseId, int $toWarehouseId, float $quantity, string $notes = ''): bool
    {
        try {
            $fromStock = WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $fromWarehouseId)
                ->firstOrFail();

            if ($fromStock->available_quantity < $quantity) {
                throw new \Exception('Insufficient stock in source warehouse');
            }

            // Remove from source
            $fromStock->update([
                'physical_quantity' => $fromStock->physical_quantity - $quantity,
                'available_quantity' => $fromStock->available_quantity - $quantity,
            ]);

            // Add to destination
            $toStock = WarehouseStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $toWarehouseId],
                ['branch_id' => $this->resolveBranchId($toWarehouseId), 'physical_quantity' => 0, 'available_quantity' => 0]
            );

            $toStock->update([
                'physical_quantity' => $toStock->physical_quantity + $quantity,
                'available_quantity' => $toStock->available_quantity + $quantity,
            ]);

            // Log both movements
            $fromWarehouse = Warehouse::find($fromWarehouseId);
            $toWarehouse = Warehouse::find($toWarehouseId);

            $this->logMovement(
                warehouseId: $fromWarehouseId,
                productId: $productId,
                type: 'transfer_out',
                referenceType: 'transfer',
                quantity: $quantity,
                notes: "Transfer to {$toWarehouse->name}"
            );

            $this->logMovement(
                warehouseId: $toWarehouseId,
                productId: $productId,
                type: 'transfer_in',
                referenceType: 'transfer',
                quantity: $quantity,
                notes: "Transfer from {$fromWarehouse->name}"
            );

            $this->clearCache($productId, $fromWarehouseId);
            $this->clearCache($productId, $toWarehouseId);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to transfer stock: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Log stock movement
     */
    private function logMovement(int $warehouseId, int $productId, string $type, string $referenceType, float $quantity, float $unitCost = 0, string $notes = ''): void
    {
        try {
            $stock = WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();
            $branchId = $this->resolveBranchId($warehouseId);

            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $type,
                'reference_type' => $referenceType,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'balance_after' => $stock?->available_quantity ?? 0,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log movement: '.$e->getMessage());
        }
    }

    private function resolveBranchId(int $warehouseId): int
    {
        $branchId = (int) (Warehouse::query()
            ->whereKey($warehouseId)
            ->value('branch_id') ?? 0);

        if ($branchId > 0) {
            return $branchId;
        }

        return $this->defaultBranchId();
    }

    private function defaultBranchId(): int
    {
        if (! Schema::hasTable('branches')) {
            return 1;
        }

        return (int) (DB::table('branches')->where('code', 'MAIN')->value('id')
            ?? DB::table('branches')->value('id')
            ?? 1);
    }

    /**
     * Clear cache for inventory
     */
    private function clearCache(int $productId, int $warehouseId): void
    {
        Cache::forget("product_stock_{$productId}_{$warehouseId}");
        Cache::forget("product_stock_{$productId}");
        Cache::forget("warehouse_stock_{$warehouseId}");
    }

    /**
     * Get detailed stock information
     */
    public function getStockDetails(int $productId, ?int $warehouseId = null): array
    {
        if ($warehouseId) {
            $stock = WarehouseStock::with(['product', 'warehouse'])
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (! $stock) {
                return ['available' => 0, 'physical' => 0, 'reserved' => 0];
            }

            return [
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,
                'physical_quantity' => $stock->physical_quantity,
                'available_quantity' => $stock->available_quantity,
                'reserved_quantity' => $stock->reserved_quantity,
                'reorder_point' => $stock->reorder_point,
                'is_low_stock' => $stock->available_quantity <= $stock->reorder_point,
            ];
        }

        // Get across all warehouses
        $stocks = WarehouseStock::with(['warehouse'])
            ->where('product_id', $productId)
            ->get();

        return [
            'total_physical' => $stocks->sum('physical_quantity'),
            'total_available' => $stocks->sum('available_quantity'),
            'warehouses' => $stocks->map(fn ($s) => [
                'warehouse_name' => $s->warehouse->name,
                'available' => $s->available_quantity,
                'physical' => $s->physical_quantity,
            ])->toArray(),
        ];
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(): array
    {
        return WarehouseStock::with(['product', 'warehouse'])
            ->whereRaw('available_quantity <= reorder_point')
            ->where('available_quantity', '>', 0)
            ->get()
            ->map(fn ($s) => [
                'product_name' => $s->product->name,
                'sku' => $s->product->sku,
                'warehouse' => $s->warehouse->name,
                'available' => $s->available_quantity,
                'reorder_point' => $s->reorder_point,
            ])
            ->toArray();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts(): array
    {
        return WarehouseStock::with(['product', 'warehouse'])
            ->where('available_quantity', 0)
            ->get()
            ->map(fn ($s) => [
                'product_name' => $s->product->name,
                'sku' => $s->product->sku,
                'warehouse' => $s->warehouse->name,
            ])
            ->toArray();
    }
}
