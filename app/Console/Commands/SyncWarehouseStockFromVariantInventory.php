<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWarehouseStockFromVariantInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-warehouse-stock-from-variants {--dry-run : Preview changes without writing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync warehouse_stock product totals from variant-level inventories';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $variantProductIds = DB::table('product_variants')
            ->distinct()
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if (empty($variantProductIds)) {
            $this->info('No variant products found. Nothing to sync.');

            return self::SUCCESS;
        }

        $aggregates = DB::table('inventories')
            ->join('product_variants', 'inventories.product_variant_id', '=', 'product_variants.id')
            ->whereIn('product_variants.product_id', $variantProductIds)
            ->groupBy('inventories.warehouse_id', 'product_variants.product_id')
            ->selectRaw('inventories.warehouse_id, product_variants.product_id')
            ->selectRaw('COALESCE(SUM(inventories.quantity_available), 0) as physical_quantity')
            ->selectRaw('COALESCE(SUM(inventories.quantity_reserved), 0) as reserved_quantity')
            ->selectRaw('COALESCE(SUM(inventories.total_value), 0) as total_value')
            ->get();

        $productMap = Product::query()
            ->whereIn('id', $variantProductIds)
            ->get(['id', 'sku', 'product_code', 'purchase_price'])
            ->keyBy('id');

        $existingRows = WarehouseStock::query()
            ->whereIn('product_id', $variantProductIds)
            ->whereNull('product_variant_id')
            ->get(['id', 'warehouse_id', 'product_id'])
            ->keyBy(fn ($row) => $row->warehouse_id.':'.$row->product_id);

        $aggregateMap = [];
        foreach ($aggregates as $row) {
            $warehouseId = (int) $row->warehouse_id;
            $productId = (int) $row->product_id;
            $key = $warehouseId.':'.$productId;

            $physical = (float) $row->physical_quantity;
            $reserved = (float) $row->reserved_quantity;
            $available = max(0, $physical - $reserved);
            $totalValue = (float) $row->total_value;

            $product = $productMap->get($productId);
            $sku = $product?->sku ?: ($product?->product_code ?: ('SKU-'.$productId));
            $averageCost = $physical > 0
                ? round($totalValue / $physical, 4)
                : (float) ($product?->purchase_price ?? 0);

            $aggregateMap[$key] = [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'sku' => $sku,
                'physical_quantity' => $physical,
                'reserved_quantity' => $reserved,
                'available_quantity' => $available,
                'average_cost' => $averageCost,
                'total_value' => $totalValue,
            ];
        }

        $synced = 0;
        $zeroed = 0;

        if (! $dryRun) {
            DB::transaction(function () use ($aggregateMap, &$synced, $existingRows, &$zeroed, $productMap) {
                foreach ($aggregateMap as $key => $data) {
                    WarehouseStock::updateOrCreate(
                        [
                            'warehouse_id' => $data['warehouse_id'],
                            'product_id' => $data['product_id'],
                            'product_variant_id' => null,
                        ],
                        $data
                    );
                    $synced++;
                }

                foreach ($existingRows as $key => $row) {
                    if (isset($aggregateMap[$key])) {
                        continue;
                    }

                    $product = $productMap->get((int) $row->product_id);
                    $sku = $product?->sku ?: ($product?->product_code ?: ('SKU-'.$row->product_id));

                    WarehouseStock::whereKey($row->id)->update([
                        'sku' => $sku,
                        'physical_quantity' => 0,
                        'reserved_quantity' => 0,
                        'available_quantity' => 0,
                        'average_cost' => (float) ($product?->purchase_price ?? 0),
                        'total_value' => 0,
                    ]);
                    $zeroed++;
                }
            });
        } else {
            $synced = count($aggregateMap);
            $zeroed = $existingRows
                ->keys()
                ->filter(fn ($key) => ! isset($aggregateMap[$key]))
                ->count();
        }

        $mode = $dryRun ? 'DRY RUN' : 'DONE';
        $this->info("{$mode}: Synced {$synced} warehouse_stock rows from variant inventories.");
        $this->info("{$mode}: Zeroed {$zeroed} stale product-level warehouse_stock rows.");

        return self::SUCCESS;
    }
}
