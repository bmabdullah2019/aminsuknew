<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // delete old incomplete partials older than 90 days
        $schedule->call(function () {
            \App\Models\PartialOrder::where('status', 'incomplete')
                ->where('updated_at', '<', now()->subDays(90))
                ->delete();
        })->daily();

        // Warehouse Module Scheduled Tasks

        // Generate stock alerts daily at 2 AM
        $schedule->command('warehouse:generate-alerts')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Detect dead stock weekly on Monday at 3 AM
        $schedule->command('warehouse:detect-dead-stock --days=90')
            ->weeklyOn(1, '03:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Update stock valuation daily at 4 AM
        $schedule->command('warehouse:update-valuation')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Purchase receipt payable reconciliation (dry-run) every night.
        $schedule->command('purchase-orders:reconcile-receipts --strict')
            ->dailyAt('01:15')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/purchase_receipt_reconcile.log'))
            ->runInBackground();

        // Purchase receipt payable reconciliation with fixes every Sunday.
        $schedule->command('purchase-orders:reconcile-receipts --apply')
            ->weeklyOn(0, '01:35')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/purchase_receipt_reconcile.log'))
            ->runInBackground();

        // Expense procurement link reconciliation (dry-run) every night.
        $schedule->command('expenses:reconcile-procurement-links --strict')
            ->dailyAt('01:50')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/expense_procurement_reconcile.log'))
            ->runInBackground();

        // Expense procurement link reconciliation with safe fixes every Sunday.
        $schedule->command('expenses:reconcile-procurement-links --apply --strict')
            ->weeklyOn(0, '02:05')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/expense_procurement_reconcile.log'))
            ->runInBackground();

        // Aggregate reconciliation health summary for monitoring/alerting pipelines.
        $schedule->command('reconciliation:health-check --json --max_issues=100')
            ->dailyAt('02:20')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/reconciliation_health.log'))
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
