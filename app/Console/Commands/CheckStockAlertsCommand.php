<?php

namespace App\Console\Commands;

use App\Jobs\CheckStockAlertsJob;
use Illuminate\Console\Command;

class CheckStockAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-alerts {--immediate : Run immediately instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check stock levels and create alerts for low stock and out of stock items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting stock alerts check...');

        if ($this->option('immediate')) {
            // Run immediately for testing
            $job = new CheckStockAlertsJob;
            $job->handle(app(\App\Services\StockService::class));
            $this->info('Stock alerts check completed immediately.');
        } else {
            // Queue the job
            CheckStockAlertsJob::dispatch();
            $this->info('Stock alerts check job has been queued.');
        }

        return Command::SUCCESS;
    }
}
