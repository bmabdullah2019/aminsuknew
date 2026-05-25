<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        // Filters
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfMonth();

        // 1. Identify Cash / Equivalent Accounts (Cash on Hand + Bank)
        $cashAccounts = Account::whereIn('HeadCode', ['cash_on_hand', 'bank_operating_account'])->pluck('HeadId');

        // 2. Initial Opening Balance (Before the selected Period)
        $openingBalance = JournalEntryItem::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function ($q) use ($startDate) {
                $q->where('date', '<', $startDate);
            })->sum('debit')
            - JournalEntryItem::whereIn('account_id', $cashAccounts)
                ->whereHas('journalEntry', function ($q) use ($startDate) {
                    $q->where('date', '<', $startDate);
                })->sum('credit');

        // 3. Cash Inflows (Debits to Cash Accounts) grouped by opposite Account Types (e.g. Sales)
        $inflows = JournalEntryItem::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->where('debit', '>', 0)
            ->with(['journalEntry.items' => function ($q) use ($cashAccounts) {
                $q->whereNotIn('account_id', $cashAccounts)->with('account');
            }])
            ->get()
            ->map(function ($item) {
                $oppositeItem = $item->journalEntry->items->first();

                return [
                    'source' => $oppositeItem ? $oppositeItem->account->name : 'Unknown Inflow',
                    'amount' => $item->debit,
                    'date' => $item->journalEntry->date,
                ];
            })->groupBy('source')->map(function ($row) {
                return $row->sum('amount');
            });

        // 4. Cash Outflows (Credits to Cash Accounts) grouped by opposite Account Types (e.g. Expenses, Payables)
        $outflows = JournalEntryItem::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->where('credit', '>', 0)
            ->with(['journalEntry.items' => function ($q) use ($cashAccounts) {
                $q->whereNotIn('account_id', $cashAccounts)->with('account');
            }])
            ->get()
            ->map(function ($item) {
                $oppositeItem = $item->journalEntry->items->first();

                return [
                    'destination' => $oppositeItem ? $oppositeItem->account->name : 'Unknown Outflow',
                    'amount' => $item->credit,
                    'date' => $item->journalEntry->date,
                ];
            })->groupBy('destination')->map(function ($row) {
                return $row->sum('amount');
            });

        // 5. Total Net Change
        $totalInflows = $inflows->sum();
        $totalOutflows = $outflows->sum();
        $netCashFlow = $totalInflows - $totalOutflows;

        // 6. Ending Balance
        $endingBalance = $openingBalance + $netCashFlow;

        return view('backEnd.finance.cash_flow', compact(
            'startDate', 'endDate',
            'openingBalance', 'inflows', 'outflows',
            'totalInflows', 'totalOutflows',
            'netCashFlow', 'endingBalance'
        ));
    }
}
