<?php

namespace App\Console\Commands;

use App\Models\WarehouseStock;
use App\Services\StockService;
use Illuminate\Console\Command;

class UpdateStockValuation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warehouse:update-valuation {--warehouse= : Specific warehouse ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update stock valuation (average cost and total value) for all warehouses';

    /**
     * Execute the console command.
     */
    public function handle(StockService $stockService)
    {
        $warehouseId = $this->option('warehouse');
        $this->info('Updating stock valuation...');

        try {
            $query = WarehouseStock::with(['warehouse', 'product']);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
                $this->info("Updating for warehouse ID: {$warehouseId}");
            }

            $stocks = $query->get();
            $updated = 0;
            $bar = $this->output->createProgressBar($stocks->count());
            $bar->start();

            foreach ($stocks as $stock) {
                // Calculate weighted average cost
                $averageCost = $stockService->calculateWeightedAverageCost(
                    $stock->warehouse_id,
                    $stock->product_id
                );

                // Update stock record
                $stock->update([
                    'average_cost' => $averageCost,
                    'total_value' => $stock->physical_quantity * $averageCost,
                ]);

                $updated++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Stock valuation updated successfully!');
            $this->line("  - Records updated: {$updated}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to update stock valuation: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
