<?php

namespace App\Console\Commands;

use App\Services\ProfitLossService;
use Illuminate\Console\Command;

class TestProfitLossSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:profit-loss-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the profit loss system functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Profit & Loss System...');

        $service = app(ProfitLossService::class);

        try {
            // Test basic sales revenue calculation
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();

            $this->info("Testing sales revenue calculation for period: {$startDate} to {$endDate}");

            $revenue = $service->calculateSalesRevenue($startDate, $endDate);
            $this->info('Sales Revenue: ৳'.number_format($revenue, 2));

            // Test COGS calculation
            $cogs = $service->calculateCOGS($startDate, $endDate, [], 'fifo');
            $this->info('COGS: ৳'.number_format($cogs['total'], 2));
            $this->info('Units Sold: '.$cogs['units_sold']);

            // Test report generation
            $this->info('Generating profit/loss report...');
            $report = $service->generateProfitLossReport('monthly', $startDate, $endDate);
            $this->info('Report generated successfully!');
            $this->info('Gross Profit: ৳'.number_format($report->gross_profit, 2));
            $this->info('Net Profit: ৳'.number_format($report->net_profit, 2));

            $this->info('✅ Profit & Loss System test completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());
            $this->error('File: '.$e->getFile().':'.$e->getLine());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
