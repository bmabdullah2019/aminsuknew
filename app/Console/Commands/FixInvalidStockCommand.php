<?php

namespace App\Console\Commands;

use App\Models\WarehouseStock;
use Illuminate\Console\Command;

class FixInvalidStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:fix-invalid {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix invalid stock records where reserved_quantity >= physical_quantity';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Find invalid stock records
        $invalidStocks = WarehouseStock::whereRaw('reserved_quantity >= physical_quantity')
            ->with(['product', 'warehouse'])
            ->get();

        if ($invalidStocks->isEmpty()) {
            $this->info('No invalid stock records found.');

            return 0;
        }

        $this->warn("Found {$invalidStocks->count()} invalid stock records:");

        $bar = $this->output->createProgressBar($invalidStocks->count());
        $bar->start();

        $fixed = 0;
        foreach ($invalidStocks as $stock) {
            $this->newLine();
            $this->info('Product: '.($stock->product ? $stock->product->name : 'Unknown')." (ID: {$stock->product_id})");
            $this->info('Warehouse: '.($stock->warehouse ? $stock->warehouse->name : 'Unknown')." (ID: {$stock->warehouse_id})");
            $this->info("Current: Physical={$stock->physical_quantity}, Reserved={$stock->reserved_quantity}, Available={$stock->available_quantity}");

            if (! $dryRun) {
                // Reset reserved_quantity to 0
                $stock->reserved_quantity = 0;
                $stock->available_quantity = $stock->physical_quantity;
                $stock->save();

                $this->info("Fixed: Reserved reset to 0, Available now={$stock->available_quantity}");
                $fixed++;
            } else {
                $this->info("Would fix: Reserved would be reset to 0, Available would be={$stock->physical_quantity}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (! $dryRun) {
            $this->info("Successfully fixed {$fixed} stock records.");
        } else {
            $this->info("Dry run completed. Would fix {$invalidStocks->count()} stock records.");
        }

        return 0;
    }
}
