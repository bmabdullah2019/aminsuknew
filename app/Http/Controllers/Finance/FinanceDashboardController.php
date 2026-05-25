<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Accounts\AccountSetting;
use App\Models\Expense;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // 1. Total P&L (Last 30 Days)
        // Revenue minus Expenses
        $settings = AccountSetting::current();

        $revenueAccounts = Account::where('AccType', $settings->Income)->pluck('HeadId');
        $expenseAccounts = Account::where('AccType', $settings->Expense)->pluck('HeadId');

        $revenue = JournalEntryItem::whereIn('account_id', $revenueAccounts)
            ->whereHas('journalEntry', function ($q) use ($thirtyDaysAgo) {
                $q->where('date', '>=', $thirtyDaysAgo);
            })
            ->sum('credit') - JournalEntryItem::whereIn('account_id', $revenueAccounts)
            ->whereHas('journalEntry', function ($q) use ($thirtyDaysAgo) {
                $q->where('date', '>=', $thirtyDaysAgo);
            })
            ->sum('debit');

        $expenses = JournalEntryItem::whereIn('account_id', $expenseAccounts)
            ->whereHas('journalEntry', function ($q) use ($thirtyDaysAgo) {
                $q->where('date', '>=', $thirtyDaysAgo);
            })
            ->sum('debit') - JournalEntryItem::whereIn('account_id', $expenseAccounts)
            ->whereHas('journalEntry', function ($q) use ($thirtyDaysAgo) {
                $q->where('date', '>=', $thirtyDaysAgo);
            })
            ->sum('credit');

        $netProfit = $revenue - $expenses;

        // 2. Cash on Hand (Current Balance)
        $assetAccounts = Account::where('AccType', $settings->Asset)->pluck('HeadId');
        $cashOnHand = JournalEntryItem::whereIn('account_id', $assetAccounts)
            ->sum('debit') - JournalEntryItem::whereIn('account_id', $assetAccounts)
            ->sum('credit');

        // 3. Expense Breakdown (Last 30 Days)
        $expenseBreakdown = Expense::with('category')
            ->where('expense_date', '>=', $thirtyDaysAgo)
            ->where('status', 'paid')
            ->select('category_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('category_id')
            ->get();

        // 4. Recent Transactions (Last 5 Journal Entries)
        $recentTransactions = \App\Models\JournalEntry::with('items.account')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('backEnd.finance.dashboard', compact(
            'revenue',
            'expenses',
            'netProfit',
            'cashOnHand',
            'expenseBreakdown',
            'recentTransactions'
        ));
    }
}
