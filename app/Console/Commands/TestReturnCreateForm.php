<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestReturnCreateForm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:return-create-form';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the return create form functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Return Create Form Components...');

        try {
            $returnReasonsCount = \App\Models\ReturnReason::active()->count();
            $this->info("PASS: Active return reasons: {$returnReasonsCount}");

            $warehousesCount = \App\Models\Warehouse::active()->count();
            $this->info("PASS: Active warehouses: {$warehousesCount}");

            $deliveredOrdersCount = \App\Models\Order::where('order_status', 5)->count();
            $this->info("PASS: Delivered orders available: {$deliveredOrdersCount}");

            $viewPath = resource_path('views/backEnd/returns/create.blade.php');
            if (file_exists($viewPath)) {
                $this->info('PASS: Create form view file exists');
            } else {
                throw new \Exception('Create form view file not found');
            }

            $createRouteExists = app('router')->getRoutes()->hasNamedRoute('admin.returns.create');
            if ($createRouteExists) {
                $this->info('PASS: Create route is registered');
            } else {
                throw new \Exception('Create route not found');
            }

            $this->info('PASS: Return Create Form components test completed successfully.');
            $this->line('Manual UI check: /admin/returns/create');
        } catch (\Exception $e) {
            $this->error('FAIL: '.$e->getMessage());
            $this->error('File: '.$e->getFile().':'.$e->getLine());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
