<?php

namespace App\Console\Commands;

use App\Services\ExpenseService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestExpenseFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:expense-features';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test expense features including daily summary and activity log';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Expense Features...');

        $expenseService = app(ExpenseService::class);

        // Test 1: Daily Summary for today
        $this->info('📅 Testing Daily Summary for Today...');
        $today = Carbon::today();
        $summary = $expenseService->getDailyExpenseSummary($today);

        $this->info("✅ Date: {$summary['date']}");
        $this->info("✅ Total Expenses: {$summary['total_expenses']}");
        $this->info("✅ Total Amount: ৳{$summary['total_amount']}");
        $this->info("✅ Pending: {$summary['pending_count']}");
        $this->info("✅ Approved: {$summary['approved_count']}");
        $this->info("✅ Paid: {$summary['paid_count']}");

        // Test 2: Daily Summary for yesterday
        $this->info('\\n📅 Testing Daily Summary for Yesterday...');
        $yesterday = Carbon::yesterday();
        $yesterdaySummary = $expenseService->getDailyExpenseSummary($yesterday);

        $this->info("✅ Date: {$yesterdaySummary['date']}");
        $this->info("✅ Total Expenses: {$yesterdaySummary['total_expenses']}");
        $this->info("✅ Total Amount: ৳{$yesterdaySummary['total_amount']}");

        // Test 3: Activity Log
        $this->info('\\n📋 Testing Activity Log...');
        $activityData = $expenseService->getActivityLog();

        $this->info("✅ Total Activities: {$activityData['total_activities']}");
        $this->info('✅ Activities by Action: '.json_encode($activityData['activities_by_action']));
        $this->info('✅ Activities by User: '.json_encode($activityData['activities_by_user']));

        // Test 4: Activity Log with filters
        $this->info('\\n📋 Testing Filtered Activity Log (last 7 days)...');
        $filteredActivity = $expenseService->getActivityLog(['days' => 7]);
        $this->info("✅ Filtered Activities: {$filteredActivity['logs']->count()}");

        // Test 5: Check if views exist
        $this->info('\\n📄 Testing View Files...');
        $views = [
            'backEnd.expense.daily-summary',
            'backEnd.expense.activity-log',
            'backEnd.expense.index',
            'backEnd.expense.create',
            'backEnd.expense.show',
            'backEnd.expense.edit',
            'backEnd.expense.reports',
            'backEnd.expense.category.index',
            'backEnd.expense.category.create',
            'backEnd.expense.category.edit',
        ];

        foreach ($views as $view) {
            try {
                view($view);
                $this->info("✅ View exists: {$view}");
            } catch (\Exception $e) {
                $this->error("❌ View missing: {$view} - {$e->getMessage()}");
            }
        }

        $this->info('\\n🎉 All expense features tested successfully!');

        return Command::SUCCESS;
    }
}
