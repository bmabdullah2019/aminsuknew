<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProfitLossController extends Controller
{
    public function dashboard(Request $request)
    {
        // Accept Dynamic Date Range Filtering
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfMonth();

        // Query Accounts by Type and Map their Balances
        $revenueAccounts = Account::where('type', 'revenue')->get();
        $cogsAccounts = Account::where('type', 'cost_of_goods')->get();
        $expenseAccounts = Account::where('type', 'expense')->get();

        $fetchBalances = function ($accounts) use ($startDate, $endDate) {
            $data = [];
            foreach ($accounts as $account) {
                // Revenue/Equity increases with Credit, Expenses/Assets increase with Debit
                $isCreditNormal = in_array($account->type, ['revenue', 'liability', 'equity']);

                $debits = JournalEntryItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate]);
                    })->sum('debit');

                $credits = JournalEntryItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate]);
                    })->sum('credit');

                $balance = $isCreditNormal ? ($credits - $debits) : ($debits - $credits);

                if ($balance > 0) {
                    $data[] = [
                        'name' => $account->name,
                        'code' => $account->code,
                        'balance' => $balance,
                    ];
                }
            }

            return collect($data);
        };

        $revenue = $fetchBalances($revenueAccounts);
        $cogs = $fetchBalances($cogsAccounts);
        $expenses = $fetchBalances($expenseAccounts);

        $totalRevenue = $revenue->sum('balance');
        $totalCogs = $cogs->sum('balance');
        $totalExpenses = $expenses->sum('balance');

        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalExpenses;

        return view('backEnd.finance.profit_loss', compact(
            'revenue', 'cogs', 'expenses',
            'totalRevenue', 'totalCogs', 'totalExpenses',
            'grossProfit', 'netProfit',
            'startDate', 'endDate'
        ));
    }
}
