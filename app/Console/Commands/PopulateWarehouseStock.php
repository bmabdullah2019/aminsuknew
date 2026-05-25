<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;

class PopulateWarehouseStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warehouse:populate-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate warehouse stock records for products that do not have them';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::all();

        $this->info('Found '.$warehouses->count().' active warehouses and '.$products->count().' products');

        $created = 0;

        foreach ($warehouses as $warehouse) {
            foreach ($products as $product) {
                $exists = WarehouseStock::where('warehouse_id', $warehouse->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if (! $exists) {
                    WarehouseStock::create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'sku' => $product->sku ?? $product->product_code,
                        'physical_quantity' => 0,
                        'reserved_quantity' => 0,
                        'available_quantity' => 0,
                        'reorder_point' => 0,
                        'reorder_quantity' => 0,
                        'average_cost' => 0,
                        'total_value' => 0,
                    ]);

                    $created++;
                    $this->line("Created stock for product {$product->id} in warehouse {$warehouse->id}");
                }
            }
        }

        $this->info("Created {$created} warehouse stock records");

        return Command::SUCCESS;
    }
}
