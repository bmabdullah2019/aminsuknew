<?php

namespace App\Console\Commands;

use App\Services\StockAlertService;
use Illuminate\Console\Command;

class GenerateStockAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warehouse:generate-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate stock alerts (low stock, out of stock, expiring stock, dead stock)';

    /**
     * Execute the console command.
     */
    public function handle(StockAlertService $alertService)
    {
        $this->info('Starting stock alert generation...');

        try {
            $results = $alertService->generateAlerts();

            $this->info('Stock alerts generated successfully:');
            $this->line("  - Low Stock: {$results['low_stock']} alerts");
            $this->line("  - Out of Stock: {$results['out_of_stock']} alerts");
            $this->line("  - Expiring Stock: {$results['expiring_stock']} alerts");
            $this->line("  - Dead Stock: {$results['dead_stock']} alerts");

            $total = array_sum($results);
            $this->info("Total alerts generated: {$total}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate alerts: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
