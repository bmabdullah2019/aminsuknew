<?php

namespace App\Services;

use App\Models\Accounts\AccountHead;
use App\Models\Accounts\AccountSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountsService
{
    private ?bool $hasApprovalStatusColumn = null;

    /**
     * Get account settings (cached per request).
     */
    public function settings(): ?AccountSetting
    {
        return \Illuminate\Support\Facades\Cache::remember('accounts_settings', 60 * 60, function () {
            return AccountSetting::current();
        });
    }

    // ── Balance Calculations ──

    /**
     * Get opening balance for a head (from accounts_opening + prior transactions).
     */
    public function getOpeningBalance(int $headId, ?int $subId, string $beforeDate, ?AccountSetting $settings = null): float
    {
        $settings = $settings ?: $this->settings();
        $head = AccountHead::find($headId);
        if (! $head || ! $settings) {
            return 0;
        }

        // From accounts_opening
        $opening = DB::table('accounts_opening')
            ->where('TranHead', $headId)
            ->where('Validity', 1)
            ->when($subId, fn ($q) => $q->where('SubId', $subId))
            ->selectRaw('COALESCE(SUM(Debit), 0) as totalDebit, COALESCE(SUM(Credit), 0) as totalCredit')
            ->first();

        // From transactions before the date
        $trans = DB::table('accounts_transaction_details as td')
            ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
            ->where('td.TranHead', $headId)
            ->where('td.Validity', 1)
            ->where('t.Validity', 1)
            ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
            ->where('t.TranDate', '<', $beforeDate)
            ->when($subId, fn ($q) => $q->where('td.SubId', $subId))
            ->selectRaw('COALESCE(SUM(td.Debit), 0) as totalDebit, COALESCE(SUM(td.Credit), 0) as totalCredit')
            ->first();

        $totalDebit = ($opening->totalDebit ?? 0) + ($trans->totalDebit ?? 0);
        $totalCredit = ($opening->totalCredit ?? 0) + ($trans->totalCredit ?? 0);

        if ($head->AccType == $settings->Asset || $head->AccType == $settings->Expense) {
            return $totalDebit - $totalCredit;
        }

        return $totalCredit - $totalDebit;
    }

    /**
     * Get transaction list for ledger report.
     */
    public function getLedgerTransactions(int $headId, ?int $subId, string $fromDate, string $toDate): \Illuminate\Support\Collection
    {
        return DB::table('accounts_transaction_details as td')
            ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
            ->leftJoin('accounts_head as h', 'h.HeadId', '=', 'td.TranParticular')
            ->leftJoin('accounts_subsidiary as s', 's.SubId', '=', 'td.SubId')
            ->where('td.TranHead', $headId)
            ->where('td.Validity', 1)
            ->where('t.Validity', 1)
            ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
            ->whereBetween('t.TranDate', [$fromDate, $toDate])
            ->when($subId, fn ($q) => $q->where('td.SubId', $subId))
            ->select([
                't.TranDate', 't.TranNo', 't.TranId',
                'td.Debit', 'td.Credit', 'td.Narration',
                'h.HeadCode as ParticularCode', 'h.HeadName as ParticularName',
                's.SubName',
            ])
            ->orderBy('t.TranDate')
            ->orderBy('t.TranId')
            ->get();
    }

    /**
     * Get trial balance data.
     */
    public function getTrialBalance(string $upToDate): \Illuminate\Support\Collection
    {
        $settings = $this->settings();
        if (! $settings) {
            return collect();
        }

        // Get all heads from tree
        $heads = DB::table('accounts_tree')
            ->where('Validity', 1)
            ->where('HasChild', 0)
            ->orderBy('Serial')
            ->get();

        return $heads->map(function ($head) use ($upToDate) {
            $opening = DB::table('accounts_opening')
                ->where('TranHead', $head->HeadId)
                ->where('Validity', 1)
                ->selectRaw('COALESCE(SUM(Debit), 0) as Debit, COALESCE(SUM(Credit), 0) as Credit')
                ->first();

            $trans = DB::table('accounts_transaction_details as td')
                ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
                ->where('td.TranHead', $head->HeadId)
                ->where('td.Validity', 1)
                ->where('t.Validity', 1)
                ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
                ->where('t.TranDate', '<=', $upToDate)
                ->selectRaw('COALESCE(SUM(td.Debit), 0) as Debit, COALESCE(SUM(td.Credit), 0) as Credit')
                ->first();

            return (object) [
                'HeadId' => $head->HeadId,
                'HeadCode' => $head->HeadCode,
                'HeadName' => $head->HeadName,
                'AccType' => $head->AccType,
                'ParentHead' => $head->ParentHead,
                'Debit' => ($opening->Debit ?? 0) + ($trans->Debit ?? 0),
                'Credit' => ($opening->Credit ?? 0) + ($trans->Credit ?? 0),
            ];
        })->filter(fn ($h) => $h->Debit > 0 || $h->Credit > 0);
    }

    /**
     * Get balance sheet data.
     */
    public function getBalanceSheet(string $upToDate): array
    {
        $settings = $this->settings();
        if (! $settings) {
            return ['assets' => [], 'liabilities' => [], 'equity' => [], 'retainedEarnings' => 0];
        }

        $getGroupBalance = function (int $accType) use ($upToDate, $settings) {
            $heads = DB::table('accounts_tree')
                ->where('AccType', $accType)
                ->where('Validity', 1)
                ->where('HasChild', 0)
                ->orderBy('Serial')
                ->get();

            return $heads->map(function ($head) use ($upToDate, $settings) {
                $opening = DB::table('accounts_opening')
                    ->where('TranHead', $head->HeadId)->where('Validity', 1)
                    ->selectRaw('COALESCE(SUM(Debit), 0) as D, COALESCE(SUM(Credit), 0) as C')
                    ->first();
                $trans = DB::table('accounts_transaction_details as td')
                    ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
                    ->where('td.TranHead', $head->HeadId)->where('td.Validity', 1)->where('t.Validity', 1)
                    ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
                    ->where('t.TranDate', '<=', $upToDate)
                    ->selectRaw('COALESCE(SUM(td.Debit), 0) as D, COALESCE(SUM(td.Credit), 0) as C')
                    ->first();

                $d = ($opening->D ?? 0) + ($trans->D ?? 0);
                $c = ($opening->C ?? 0) + ($trans->C ?? 0);

                if ($head->AccType == $settings->Asset || $head->AccType == $settings->Expense) {
                    $balance = $d - $c;
                } else {
                    $balance = $c - $d;
                }

                return (object) [
                    'HeadId' => $head->HeadId, 'HeadCode' => $head->HeadCode,
                    'HeadName' => $head->HeadName, 'ParentHead' => $head->ParentHead,
                    'Balance' => $balance,
                ];
            })->filter(fn ($h) => abs($h->Balance) > 0.01);
        };

        // Calculate retained earnings (income - expense)
        $incomeHeads = $getGroupBalance($settings->Income);
        $expenseHeads = $getGroupBalance($settings->Expense);
        $totalIncome = $incomeHeads->sum('Balance');
        $totalExpense = $expenseHeads->sum('Balance');

        return [
            'assets' => $getGroupBalance($settings->Asset),
            'liabilities' => $getGroupBalance($settings->Liability),
            'equity' => $getGroupBalance($settings->Equity),
            'retainedEarnings' => $totalIncome - $totalExpense,
        ];
    }

    /**
     * Get income statement data.
     */
    public function getIncomeStatement(string $fromDate, string $toDate): array
    {
        $settings = $this->settings();
        if (! $settings) {
            return ['income' => [], 'expense' => [], 'netProfit' => 0];
        }

        $getItems = function (int $accType, bool $isExpense) use ($fromDate, $toDate) {
            $heads = DB::table('accounts_tree')
                ->where('AccType', $accType)->where('Validity', 1)->where('HasChild', 0)
                ->orderBy('Serial')->get();

            return $heads->map(function ($head) use ($fromDate, $toDate, $isExpense) {
                $opening = DB::table('accounts_opening')
                    ->where('TranHead', $head->HeadId)->where('Validity', 1)
                    ->selectRaw('COALESCE(SUM(Debit), 0) as D, COALESCE(SUM(Credit), 0) as C')
                    ->first();
                $trans = DB::table('accounts_transaction_details as td')
                    ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
                    ->where('td.TranHead', $head->HeadId)->where('td.Validity', 1)->where('t.Validity', 1)
                    ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
                    ->whereBetween('t.TranDate', [$fromDate, $toDate])
                    ->selectRaw('COALESCE(SUM(td.Debit), 0) as D, COALESCE(SUM(td.Credit), 0) as C')
                    ->first();

                $d = ($opening->D ?? 0) + ($trans->D ?? 0);
                $c = ($opening->C ?? 0) + ($trans->C ?? 0);
                $balance = $isExpense ? ($d - $c) : ($c - $d);

                return (object) [
                    'HeadId' => $head->HeadId, 'HeadCode' => $head->HeadCode,
                    'HeadName' => $head->HeadName, 'Balance' => $balance,
                ];
            })->filter(fn ($h) => abs($h->Balance) > 0.01);
        };

        $income = $getItems($settings->Income, false);
        $expense = $getItems($settings->Expense, true);

        return [
            'income' => $income,
            'expense' => $expense,
            'netProfit' => $income->sum('Balance') - $expense->sum('Balance'),
        ];
    }

    /**
     * Get cash flow data.
     */
    public function getCashFlow(array $headIds, string $fromDate, string $toDate): array
    {
        $settings = $this->settings();
        if (! $settings) {
            return ['openingBalance' => 0, 'transactions' => collect(), 'closingBalance' => 0];
        }

        // Opening balance for selected heads before fromDate
        $openingBalance = 0;
        foreach ($headIds as $hid) {
            $openingBalance += $this->getOpeningBalance($hid, null, $fromDate, $settings);
        }

        // Transactions within range grouped by TranParticular
        $transactions = DB::table('accounts_transaction_details as td')
            ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
            ->leftJoin('accounts_head as h', 'h.HeadId', '=', 'td.TranParticular')
            ->whereIn('td.TranHead', $headIds)
            ->where('td.Validity', 1)->where('t.Validity', 1)
            ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
            ->whereBetween('t.TranDate', [$fromDate, $toDate])
            ->groupBy('td.TranParticular', 'h.HeadCode', 'h.HeadName')
            ->select([
                'td.TranParticular',
                'h.HeadCode', 'h.HeadName',
                DB::raw('COALESCE(SUM(td.Debit), 0) as Debit'),
                DB::raw('COALESCE(SUM(td.Credit), 0) as Credit'),
            ])
            ->get();

        $netMovement = $transactions->sum('Debit') - $transactions->sum('Credit');

        return [
            'openingBalance' => $openingBalance,
            'transactions' => $transactions,
            'closingBalance' => $openingBalance + $netMovement,
        ];
    }

    public function getReconciliationSummary(?string $asOfDate = null): array
    {
        $settings = $this->settings();
        $asOfDate = $asOfDate ?: now()->toDateString();

        if (! $settings) {
            return [];
        }

        $rows = [
            [
                'label' => 'Customer subledger vs Accounts Receivable GL',
                'source' => $this->customerReceivableSourceBalance($asOfDate),
                'gl' => $this->headBalance((int) ($settings->Receivable ?? 0), $asOfDate),
            ],
            [
                'label' => 'Supplier subledger vs Accounts Payable GL',
                'source' => $this->supplierPayableSourceBalance($asOfDate),
                'gl' => $this->headBalance((int) ($settings->Payable ?? 0), $asOfDate),
            ],
            [
                'label' => 'Inventory stock value vs Inventory GL',
                'source' => $this->inventorySourceValue(),
                'gl' => $this->headBalance((int) ($settings->Inventory ?? 0), $asOfDate),
            ],
            [
                'label' => 'Undeposited fund vs unencashed cheques',
                'source' => $this->undepositedSourceValue($asOfDate),
                'gl' => $this->headBalance((int) ($settings->UndepositedFund ?? 0), $asOfDate),
            ],
        ];

        return array_map(function (array $row) {
            $row['difference'] = round((float) $row['source'] - (float) $row['gl'], 2);
            $row['status'] = abs($row['difference']) <= 0.01 ? 'ok' : 'mismatch';

            return $row;
        }, $rows);
    }

    private function headBalance(int $headId, string $asOfDate): float
    {
        if ($headId <= 0) {
            return 0.0;
        }

        $head = AccountHead::query()->find($headId);
        $settings = $this->settings();
        if (! $head || ! $settings) {
            return 0.0;
        }

        $opening = DB::table('accounts_opening')
            ->where('TranHead', $headId)
            ->where('Validity', 1)
            ->selectRaw('COALESCE(SUM(Debit), 0) as Debit, COALESCE(SUM(Credit), 0) as Credit')
            ->first();

        $transactions = DB::table('accounts_transaction_details as td')
            ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
            ->where('td.TranHead', $headId)
            ->where('td.Validity', 1)
            ->where('t.Validity', 1)
            ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
            ->where('t.TranDate', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(td.Debit), 0) as Debit, COALESCE(SUM(td.Credit), 0) as Credit')
            ->first();

        $debit = (float) ($opening->Debit ?? 0) + (float) ($transactions->Debit ?? 0);
        $credit = (float) ($opening->Credit ?? 0) + (float) ($transactions->Credit ?? 0);

        if ((int) $head->AccType === (int) $settings->Asset || (int) $head->AccType === (int) $settings->Expense) {
            return round($debit - $credit, 2);
        }

        return round($credit - $debit, 2);
    }

    private function customerReceivableSourceBalance(string $asOfDate): float
    {
        if (! Schema::hasTable('orders')) {
            return 0.0;
        }

        $orders = DB::table('orders')
            ->whereDate('created_at', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as total')
            ->value('total');

        $payments = 0.0;
        if (Schema::hasTable('payments')) {
            $payments = (float) DB::table('payments')
                ->where('payment_status', 'paid')
                ->whereDate('created_at', '<=', $asOfDate)
                ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as total')
                ->value('total');
        }

        $returns = 0.0;
        if (Schema::hasTable('return_orders')) {
            $returns = (float) DB::table('return_orders')
                ->where('return_status', 'completed')
                ->whereDate('updated_at', '<=', $asOfDate)
                ->sum('total_return_value');
        }

        return round((float) $orders - $payments - $returns, 2);
    }

    private function supplierPayableSourceBalance(string $asOfDate): float
    {
        if (! Schema::hasTable('supplier_ledgers')) {
            return 0.0;
        }

        return round((float) DB::table('supplier_ledgers')
            ->whereDate('created_at', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance'), 2);
    }

    private function inventorySourceValue(): float
    {
        if (! Schema::hasTable('warehouse_stocks')) {
            return 0.0;
        }

        return round((float) DB::table('warehouse_stocks')
            ->selectRaw('COALESCE(SUM(physical_quantity * average_cost), 0) as value')
            ->value('value'), 2);
    }

    private function undepositedSourceValue(string $asOfDate): float
    {
        if (! Schema::hasTable('payments')) {
            return 0.0;
        }

        return round((float) DB::table('payments')
            ->whereIn('payment_method', ['cheque', 'check'])
            ->where('payment_status', 'paid')
            ->whereDate('created_at', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(amount_minor, 0) / 100, amount, 0)), 0) as total')
            ->value('total'), 2);
    }

    /**
     * Get voucher statement data.
     */
    public function getVoucherStatement(string $fromDate, string $toDate): \Illuminate\Support\Collection
    {
        return DB::table('accounts_transaction as t')
            ->where('t.Validity', 1)
            ->where('t.ModuleName', 'accounts_transaction')
            ->whereBetween('t.TranDate', [$fromDate, $toDate])
            ->select(['t.TranId', 't.TranDate', 't.TranNo', 't.TranAmount', 't.Remarks'])
            ->orderBy('t.TranDate')
            ->orderBy('t.TranId')
            ->get();
    }

    /**
     * Get voucher details for display/edit, preserving line-level metadata.
     */
    public function getVoucherDetails(int $tranId): \Illuminate\Support\Collection
    {
        return DB::table('accounts_transaction_details as td')
            ->leftJoin('accounts_head as h', 'h.HeadId', '=', 'td.TranHead')
            ->leftJoin('accounts_subsidiary as s', 's.SubId', '=', 'td.SubId')
            ->where('td.TranId', $tranId)
            ->where('td.Validity', 1)
            ->groupBy(
                'td.TranHead',
                'td.SubId',
                'td.Narration',
                'td.BankName',
                'td.BranchName',
                'td.ChequeNo',
                'td.ChequeDate',
                'h.HeadCode',
                'h.HeadName',
                's.SubCode',
                's.SubName'
            )
            ->select([
                'td.TranHead', 'h.HeadCode', 'h.HeadName',
                'td.SubId', 's.SubCode', 's.SubName',
                'td.Narration', 'td.BankName', 'td.BranchName', 'td.ChequeNo', 'td.ChequeDate',
                DB::raw('SUM(td.Debit) as Debit'),
                DB::raw('SUM(td.Credit) as Credit'),
            ])
            ->get();
    }

    /**
     * Get top sheet data for a given parent head.
     */
    public function getTopSheet(int $parentHeadId, string $fromDate, string $toDate): \Illuminate\Support\Collection
    {
        $settings = $this->settings();
        $parent = AccountHead::find($parentHeadId);
        if (! $parent || ! $settings) {
            return collect();
        }

        $directChildren = AccountHead::valid()
            ->where('ParentId', $parentHeadId)
            ->orderBy('HeadId')
            ->get();

        return $directChildren->map(function ($child) use ($fromDate, $toDate, $settings) {
            $headObj = new AccountHead;
            $headObj->HeadId = $child->HeadId;
            $leafIds = $headObj->getAllLeafIds();

            if (empty($leafIds)) {
                return null;
            }

            $opening = DB::table('accounts_opening')
                ->whereIn('TranHead', $leafIds)->where('Validity', 1)
                ->selectRaw('COALESCE(SUM(Debit), 0) as D, COALESCE(SUM(Credit), 0) as C')
                ->first();

            $priorTrans = DB::table('accounts_transaction_details as td')
                ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
                ->whereIn('td.TranHead', $leafIds)->where('td.Validity', 1)->where('t.Validity', 1)
                ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
                ->where('t.TranDate', '<', $fromDate)
                ->selectRaw('COALESCE(SUM(td.Debit), 0) as D, COALESCE(SUM(td.Credit), 0) as C')
                ->first();

            $rangeTrans = DB::table('accounts_transaction_details as td')
                ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
                ->whereIn('td.TranHead', $leafIds)->where('td.Validity', 1)->where('t.Validity', 1)
                ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
                ->whereBetween('t.TranDate', [$fromDate, $toDate])
                ->selectRaw('COALESCE(SUM(td.Debit), 0) as Debit, COALESCE(SUM(td.Credit), 0) as Credit')
                ->first();

            $openingBal = ($opening->D ?? 0) + ($priorTrans->D ?? 0) - ($opening->C ?? 0) - ($priorTrans->C ?? 0);
            if (! in_array($child->AccType, [$settings->Asset, $settings->Expense])) {
                $openingBal = -$openingBal;
            }

            return (object) [
                'HeadId' => $child->HeadId,
                'HeadCode' => $child->HeadCode,
                'HeadName' => $child->HeadName,
                'Opening' => $openingBal,
                'Debit' => $rangeTrans->Debit ?? 0,
                'Credit' => $rangeTrans->Credit ?? 0,
            ];
        })->filter();
    }

    /**
     * Close fiscal year and carry balances forward.
     */
    public function closeFiscalYear(int $fiscalYearId, string $user): void
    {
        $fiscalYear = \App\Models\Accounts\AccountClosing::findOrFail($fiscalYearId);

        if ($fiscalYear->IsClosed) {
            throw new \Exception('Fiscal year is already closed.');
        }

        DB::transaction(function () use ($fiscalYear, $user) {
            $upToDate = $fiscalYear->ClosingDate;
            $settings = $this->settings();

            $nextFiscalYear = \App\Models\Accounts\AccountClosing::create([
                'OpeningDate' => \Carbon\Carbon::parse($upToDate)->addDay()->toDateString(),
                'ClosingDate' => \Carbon\Carbon::parse($upToDate)->addYear()->toDateString(),
                'Remarks' => 'Auto-generated after closing FY '.$fiscalYear->FiscalYearId,
                'CreatedBy' => $user,
                'CreatedAt' => now(),
            ]);

            $headsToCarryForward = DB::table('accounts_tree')
                ->whereIn('AccType', [$settings->Asset, $settings->Liability, $settings->Equity])
                ->where('Validity', 1)->where('HasChild', 0)->get();

            $openingData = [];
            foreach ($headsToCarryForward as $head) {
                // Collect subIds that have transactions for this head
                $subsidiaries = DB::table('accounts_transaction_details as td')
                    ->join('accounts_transaction as t', 't.TranId', '=', 'td.TranId')
                    ->where('td.TranHead', $head->HeadId)
                    ->where('td.Validity', 1)->where('t.Validity', 1)
                    ->when($this->transactionsHaveApprovalStatus(), fn ($q) => $q->where('t.ApprovalStatus', 'approved'))
                    ->where('t.TranDate', '<=', $upToDate)
                    ->select('td.SubId')->groupBy('td.SubId')->pluck('SubId');

                if (! $subsidiaries->contains(null)) {
                    $subsidiaries->push(null);
                }

                foreach ($subsidiaries as $subId) {
                    $balance = $this->getOpeningBalance($head->HeadId, $subId, \Carbon\Carbon::parse($upToDate)->addDay()->toDateString(), $settings);
                    if (abs($balance) > 0.01) {
                        $isAsset = $head->AccType == $settings->Asset;
                        $openingData[] = [
                            'OpeningDate' => $nextFiscalYear->OpeningDate,
                            'FiscalYearId' => $nextFiscalYear->FiscalYearId,
                            'TranHead' => $head->HeadId,
                            'SubId' => $subId,
                            'Debit' => ($isAsset && $balance > 0) || (! $isAsset && $balance < 0) ? abs($balance) : 0,
                            'Credit' => ($isAsset && $balance < 0) || (! $isAsset && $balance > 0) ? abs($balance) : 0,
                            'ModuleName' => 'system_closing',
                            'CreatedBy' => $user,
                            'CreatedAt' => now(),
                        ];
                    }
                }
            }

            $incomeStatement = $this->getIncomeStatement($fiscalYear->OpeningDate, $upToDate);
            $netProfit = $incomeStatement['netProfit'];

            if (abs($netProfit) > 0.01 && $settings->OwnerEquity) {
                $openingData[] = [
                    'OpeningDate' => $nextFiscalYear->OpeningDate,
                    'FiscalYearId' => $nextFiscalYear->FiscalYearId,
                    'TranHead' => $settings->OwnerEquity,
                    'Debit' => $netProfit < 0 ? abs($netProfit) : 0,
                    'Credit' => $netProfit > 0 ? abs($netProfit) : 0,
                    'ModuleName' => 'system_closing_retained_earnings',
                    'CreatedBy' => $user,
                    'CreatedAt' => now(),
                ];
            }

            if (! empty($openingData)) {
                $chunks = array_chunk($openingData, 500);
                foreach ($chunks as $chunk) {
                    DB::table('accounts_opening')->insert($chunk);
                }
            }

            $fiscalYear->update(['IsClosed' => 1]);
        });
    }

    private function transactionsHaveApprovalStatus(): bool
    {
        if ($this->hasApprovalStatusColumn !== null) {
            return $this->hasApprovalStatusColumn;
        }

        $this->hasApprovalStatusColumn = Schema::hasColumn('accounts_transaction', 'ApprovalStatus');

        return $this->hasApprovalStatusColumn;
    }
}
