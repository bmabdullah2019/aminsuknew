<?php

namespace App\Console\Commands;

use App\Models\WarehouseStock;
use App\Services\StockAlertService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DetectDeadStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warehouse:detect-dead-stock {--days=90 : Number of days threshold for dead stock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and generate alerts for dead stock (stock not sold in specified days)';

    /**
     * Execute the console command.
     */
    public function handle(StockAlertService $alertService)
    {
        $days = (int) $this->option('days');
        $this->info("Detecting dead stock (threshold: {$days} days)...");

        try {
            $count = $alertService->checkDeadStock($days);

            $this->info('Dead stock detection completed.');
            $this->line("  - Dead stock alerts generated: {$count}");

            // Also show summary
            $deadStockCount = WarehouseStock::where('physical_quantity', '>', 0)
                ->whereDoesntHave('movements', function ($query) use ($days) {
                    $query->where('created_at', '>=', Carbon::now()->subDays($days))
                        ->where('quantity', '<', 0);
                })
                ->count();

            $this->line("  - Total dead stock items: {$deadStockCount}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to detect dead stock: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
