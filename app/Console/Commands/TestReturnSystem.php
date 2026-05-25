<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestReturnSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:return-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the return management system functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Return Management System...');

        try {
            $reasonCount = \App\Models\ReturnReason::count();
            $this->info("Return reasons available: {$reasonCount}");

            $service = app(\App\Services\ReturnService::class);
            $stats = $service->getReturnStatistics();
            $this->info("Total returns: {$stats['total_returns']}");
            $this->info('Total return value (BDT): '.number_format((float) $stats['total_return_value'], 2));

            $this->info('Return system components loaded successfully.');
            $this->info('PASS: Return Management System test completed successfully.');
        } catch (\Exception $e) {
            $this->error('FAIL: '.$e->getMessage());
            $this->error('File: '.$e->getFile().':'.$e->getLine());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
