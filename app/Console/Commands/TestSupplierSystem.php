<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use Illuminate\Console\Command;

class TestSupplierSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:supplier-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the supplier management system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Supplier Management System...');

        // Test 1: Check if suppliers can be loaded
        try {
            $suppliers = Supplier::with(['ledger' => function ($q) {
                $q->latest('transaction_date')->limit(1);
            }])->get();

            $this->info("✅ Found {$suppliers->count()} suppliers");

            if ($suppliers->count() > 0) {
                $supplier = $suppliers->first();
                $this->info("✅ Sample supplier: {$supplier->name}");
                $this->info("✅ Current balance: ৳{$supplier->current_balance}");

                // Test aging summary
                $aging = $supplier->getAgingSummary();
                $this->info('✅ Aging summary calculated successfully');
            }

        } catch (\Exception $e) {
            $this->error("❌ Supplier loading failed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        // Test 2: Check supplier service
        try {
            $service = app(\App\Services\SupplierService::class);
            $agingReport = $service->getSupplierAgingReport();
            $this->info('✅ Aging report generated successfully');

            $performanceMetrics = $service->getSupplierPerformanceMetrics();
            $this->info('✅ Performance metrics calculated successfully');

        } catch (\Exception $e) {
            $this->error("❌ Supplier service failed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        $this->info('🎉 All supplier system tests passed!');

        return Command::SUCCESS;
    }
}
