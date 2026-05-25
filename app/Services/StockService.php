<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockService
{
    // 🔒 NEW STOCK SYSTEM - Simple & Clean Architecture
    // Single source of truth with available_qty, reserved_qty, sold_qty

    /**
     * Get stock record for a product or product variant
     */
    public function getStock(int $productId, ?int $variantId = null): ?Stock
    {
        return $this->stockQuery($productId, $variantId)->first();
    }

    /**
     * Create stock record for product (called when product is created)
     */
    public function createProductStock(Product $product, float $initialQty = 0): Stock
    {
        $attributes = [
            'product_id' => $product->id,
            'variant_id' => null,
            'available_qty' => $initialQty,
            'reserved_qty' => 0,
            'sold_qty' => 0,
        ];

        $attributes['id'] = Stock::max('id') + 1;

        if ($this->stockHasBranchColumn()) {
            $attributes['branch_id'] = $this->defaultBranchId();
        }

        return Stock::create($attributes);
    }

    /**
     * Create stock record for variant (called when variant is created)
     */
    public function createVariantStock(ProductVariant $variant, float $initialQty = 0): Stock
    {
        $attributes = [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'available_qty' => $initialQty,
            'reserved_qty' => 0,
            'sold_qty' => 0,
        ];

        $attributes['id'] = Stock::max('id') + 1;

        if ($this->stockHasBranchColumn()) {
            $attributes['branch_id'] = $this->defaultBranchId();
        }

        return Stock::create($attributes);
    }

    /**
     * Reserve stock for cart/order
     */
    public function reserveStock(int $productId, ?int $variantId, float $quantity): bool
    {
        return DB::transaction(function () use ($productId, $variantId, $quantity) {
            $stock = $this->getStockForUpdate($productId, $variantId);

            if (! $stock || ! $stock->reserveStock($quantity)) {
                return false;
            }

            $this->clearStockCache($productId, $variantId);

            return true;
        });
    }

    /**
     * Release reserved stock (cart expire, order cancel)
     */
    public function releaseReservedStock(int $productId, ?int $variantId, float $quantity): bool
    {
        return DB::transaction(function () use ($productId, $variantId, $quantity) {
            $stock = $this->getStockForUpdate($productId, $variantId);

            if (! $stock || ! $stock->releaseReservedStock($quantity)) {
                return false;
            }

            $this->clearStockCache($productId, $variantId);

            return true;
        });
    }

    /**
     * Convert reserved to sold (payment confirmed)
     */
    public function convertReservedToSold(int $productId, ?int $variantId, float $quantity): bool
    {
        return DB::transaction(function () use ($productId, $variantId, $quantity) {
            $stock = $this->getStockForUpdate($productId, $variantId);

            if (! $stock || ! $stock->convertReservedToSold($quantity)) {
                return false;
            }

            $this->clearStockCache($productId, $variantId);

            return true;
        });
    }

    /**
     * Process return
     */
    public function processReturn(int $productId, ?int $variantId, float $quantity, bool $resellable = true): bool
    {
        return DB::transaction(function () use ($productId, $variantId, $quantity, $resellable) {
            $stock = $this->getStockForUpdate($productId, $variantId);

            if (! $stock || ! $stock->processReturn($quantity, $resellable)) {
                return false;
            }

            $this->clearStockCache($productId, $variantId);

            return true;
        });
    }

    /**
     * Manual stock adjustment
     */
    public function adjustStock(int $productId, ?int $variantId, float $quantity): bool
    {
        return DB::transaction(function () use ($productId, $variantId, $quantity) {
            $stock = $this->getStockForUpdate($productId, $variantId);

            if (! $stock) {
                // Create stock record if it doesn't exist
                if ($variantId) {
                    $variant = ProductVariant::find($variantId);
                    if ($variant) {
                        $stock = $this->createVariantStock($variant, 0);
                    }
                } else {
                    $product = Product::find($productId);
                    if ($product) {
                        $stock = $this->createProductStock($product, 0);
                    }
                }
            }

            if (! $stock || ! $stock->adjustStock($quantity)) {
                return false;
            }

            $this->clearStockCache($productId, $variantId);

            return true;
        });
    }

    /**
     * Check if stock is available for purchase
     */
    public function isStockAvailable(int $productId, ?int $variantId, float $quantity): bool
    {
        $stock = $this->getStock($productId, $variantId);

        return $stock && $stock->available_qty >= $quantity;
    }

    /**
     * Backward-compatible check for warehouse-level stock usage.
     */
    public function checkAvailableStock(int $warehouseId, int $productId, float $quantity): bool
    {
        $available = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('available_quantity');

        return (float) ($available ?? 0) >= $quantity;
    }

    /**
     * Get stock summary for display
     */
    public function getStockSummary(int $productId, ?int $variantId): array
    {
        $cacheKey = "stock_summary_{$productId}_{$variantId}";

        return Cache::remember($cacheKey, 300, function () use ($productId, $variantId) {
            $stock = $this->getStock($productId, $variantId);

            if (! $stock) {
                return [
                    'available_qty' => 0,
                    'reserved_qty' => 0,
                    'sold_qty' => 0,
                    'total_qty' => 0,
                    'on_hold_qty' => 0,
                    'status' => 'not_found',
                ];
            }

            return [
                'available_qty' => $stock->available_qty,
                'reserved_qty' => $stock->reserved_qty,
                'sold_qty' => $stock->sold_qty,
                'total_qty' => $stock->total_qty,
                'on_hold_qty' => $stock->on_hold_qty,
                'status' => $stock->stock_status,
                'status_color' => $stock->stock_status_color,
                'last_updated' => $stock->updated_at,
            ];
        });
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems(): \Illuminate\Database\Eloquent\Collection
    {
        return Stock::lowStock()->with(['product', 'variant'])->get();
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems(): \Illuminate\Database\Eloquent\Collection
    {
        return Stock::outOfStock()->with(['product', 'variant'])->get();
    }

    /**
     * Backward-compatible stock alerts payload for legacy jobs/commands.
     *
     * @return array{low_stock:\Illuminate\Database\Eloquent\Collection<int, WarehouseStock>, out_of_stock:\Illuminate\Database\Eloquent\Collection<int, WarehouseStock>}
     */
    public function getStockAlerts(): array
    {
        $lowStock = WarehouseStock::query()
            ->with(['warehouse', 'product'])
            ->where('reorder_point', '>', 0)
            ->whereRaw('(physical_quantity - reserved_quantity) > 0')
            ->whereRaw('(physical_quantity - reserved_quantity) <= reorder_point')
            ->get();

        $outOfStock = WarehouseStock::query()
            ->with(['warehouse', 'product'])
            ->whereRaw('(physical_quantity - reserved_quantity) <= 0')
            ->get();

        return [
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
        ];
    }

    /**
     * Calculate weighted average unit cost using incoming stock movements.
     * Falls back to current warehouse stock average cost or product purchase price.
     */
    public function calculateWeightedAverageCost(int $warehouseId, int $productId): float
    {
        $incoming = StockMovement::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->whereNotNull('unit_cost')
            ->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_qty')
            ->first();

        $totalQty = (float) ($incoming->total_qty ?? 0);
        if ($totalQty > 0) {
            return round(((float) ($incoming->total_cost ?? 0)) / $totalQty, 2);
        }

        $existingAverage = (float) (WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('average_cost') ?? 0);

        if ($existingAverage > 0) {
            return round($existingAverage, 2);
        }

        $purchasePrice = (float) (Product::query()->whereKey($productId)->value('purchase_price') ?? 0);

        return round(max(0, $purchasePrice), 2);
    }

    /**
     * Clear stock cache
     */
    private function clearStockCache(int $productId, ?int $variantId): void
    {
        Cache::forget("stock_summary_{$productId}_{$variantId}");
    }

    private function getStockForUpdate(int $productId, ?int $variantId = null): ?Stock
    {
        return $this->stockQuery($productId, $variantId)
            ->lockForUpdate()
            ->first();
    }

    private function stockQuery(int $productId, ?int $variantId = null)
    {
        $query = Stock::query()->where('product_id', $productId);

        if ($variantId === null) {
            return $query->whereNull('variant_id');
        }

        return $query->where('variant_id', $variantId);
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

    private function stockHasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $hasBranchColumn = Schema::hasColumn('stocks', 'branch_id');
        }

        return $hasBranchColumn;
    }
}
