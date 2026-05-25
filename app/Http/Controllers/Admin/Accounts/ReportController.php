<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\AccountHead;
use App\Services\AccountsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    private AccountsService $service;

    public function __construct(AccountsService $service)
    {
        $this->service = $service;
    }

    // ── Reports Hub ──
    public function index(): View
    {
        return view('backEnd.accounts.report.index');
    }

    // ── Ledger ──
    public function ledger(): View
    {
        $heads = AccountHead::valid()
            ->leaves()
            ->orderBy('HeadCode')
            ->get(['HeadId', 'HeadCode', 'HeadName']);

        return view('backEnd.accounts.report.ledger', compact('heads'));
    }

    public function ledgerReport(Request $request): View
    {
        $request->validate([
            'HeadId' => 'required|integer|exists:accounts_head,HeadId',
            'SubId' => 'nullable|integer',
            'FromDate' => 'required|date',
            'ToDate' => 'required|date|after_or_equal:FromDate',
        ]);

        $headId = (int) $request->HeadId;
        $subId = $request->filled('SubId') ? (int) $request->SubId : null;
        $fromDate = $request->FromDate;
        $toDate = $request->ToDate;

        $head = AccountHead::find($headId);
        $settings = $this->service->settings();
        $opening = $this->service->getOpeningBalance($headId, $subId, $fromDate, $settings);
        $transactions = $this->service->getLedgerTransactions($headId, $subId, $fromDate, $toDate);

        // Calculate running balance
        $balance = $opening;
        $transactions = $transactions->map(function ($line) use (&$balance, $head, $settings) {
            if ($head->AccType == $settings->Asset || $head->AccType == $settings->Expense) {
                $balance += $line->Debit - $line->Credit;
            } else {
                $balance += $line->Credit - $line->Debit;
            }
            $line->RunningBalance = $balance;

            return $line;
        });

        return view('backEnd.accounts.report.ledger-result', compact(
            'head', 'opening', 'transactions', 'fromDate', 'toDate', 'subId'
        ));
    }

    // ── Trial Balance ──
    public function trialBalance(): View
    {
        return view('backEnd.accounts.report.trial-balance');
    }

    public function trialBalanceReport(Request $request): View
    {
        $request->validate(['AsOfDate' => 'required|date']);
        $asOfDate = $request->AsOfDate;
        $data = $this->service->getTrialBalance($asOfDate);
        $totalDebit = $data->sum('Debit');
        $totalCredit = $data->sum('Credit');

        return view('backEnd.accounts.report.trial-balance-result', compact('data', 'asOfDate', 'totalDebit', 'totalCredit'));
    }

    // ── Balance Sheet ──
    public function balanceSheet(): View
    {
        return view('backEnd.accounts.report.balance-sheet');
    }

    public function balanceSheetReport(Request $request): View
    {
        $request->validate(['AsOfDate' => 'required|date']);
        $asOfDate = $request->AsOfDate;
        $data = $this->service->getBalanceSheet($asOfDate);
        $settings = $this->service->settings();

        return view('backEnd.accounts.report.balance-sheet-result', compact('data', 'asOfDate') + [
            'assetLabel' => $this->getHeadName((int) ($settings->Asset ?? 0), 'Assets'),
            'liabilityLabel' => $this->getHeadName((int) ($settings->Liability ?? 0), 'Liabilities'),
            'equityLabel' => $this->getHeadName((int) ($settings->Equity ?? 0), 'Equity'),
        ]);
    }

    // ── Income Statement ──
    public function incomeStatement(): View
    {
        return view('backEnd.accounts.report.income-statement');
    }

    public function incomeStatementReport(Request $request): View
    {
        $request->validate([
            'FromDate' => 'required|date',
            'ToDate' => 'required|date|after_or_equal:FromDate',
        ]);
        $data = $this->service->getIncomeStatement($request->FromDate, $request->ToDate);
        $settings = $this->service->settings();

        return view('backEnd.accounts.report.income-statement-result', [
            'data' => $data,
            'fromDate' => $request->FromDate,
            'toDate' => $request->ToDate,
            'incomeLabel' => $this->getHeadName((int) ($settings->Income ?? 0), 'Income'),
            'expenseLabel' => $this->getHeadName((int) ($settings->Expense ?? 0), 'Expense'),
        ]);
    }

    // ── Cash Flow ──
    public function cashFlow(): View
    {
        $settings = $this->service->settings();
        $cashHeads = [];
        $cashLabel = 'Cash';
        $bankLabel = 'Bank';
        if ($settings) {
            $cashLabel = $this->getHeadName((int) $settings->Cash, $cashLabel);
            $bankLabel = $this->getHeadName((int) $settings->Bank, $bankLabel);
            $cashHeads = AccountHead::valid()
                ->where(function ($q) use ($settings) {
                    $q->where('ParentId', $settings->Cash)
                        ->orWhere('ParentId', $settings->Bank)
                        ->orWhere('HeadId', $settings->Cash)
                        ->orWhere('HeadId', $settings->Bank);
                })
                ->orderBy('HeadCode')
                ->get();
        }

        return view('backEnd.accounts.report.cash-flow', compact('cashHeads', 'cashLabel', 'bankLabel'));
    }

    public function cashFlowReport(Request $request): View
    {
        $request->validate([
            'HeadIds' => 'required|string',
            'FromDate' => 'required|date',
            'ToDate' => 'required|date|after_or_equal:FromDate',
        ]);

        $headIds = array_map('intval', explode(',', $request->HeadIds));
        $data = $this->service->getCashFlow($headIds, $request->FromDate, $request->ToDate);
        $selectedHeads = AccountHead::valid()
            ->whereIn('HeadId', $headIds)
            ->orderBy('HeadCode')
            ->get(['HeadId', 'HeadCode', 'HeadName']);

        return view('backEnd.accounts.report.cash-flow-result', [
            'data' => $data,
            'fromDate' => $request->FromDate,
            'toDate' => $request->ToDate,
            'selectedHeads' => $selectedHeads,
        ]);
    }

    // ── Account Group Summary (legacy: Top Sheet) ──
    public function topSheet(): View
    {
        $roots = AccountHead::valid()->roots()->orderBy('HeadId')->get();

        return view('backEnd.accounts.report.top-sheet', compact('roots'));
    }

    public function topSheetReport(Request $request): View
    {
        $request->validate([
            'HeadId' => 'required|integer|exists:accounts_head,HeadId',
            'FromDate' => 'required|date',
            'ToDate' => 'required|date|after_or_equal:FromDate',
        ]);

        $head = AccountHead::find($request->HeadId);
        $data = $this->service->getTopSheet((int) $request->HeadId, $request->FromDate, $request->ToDate);

        return view('backEnd.accounts.report.top-sheet-result', compact('data', 'head') + [
            'fromDate' => $request->FromDate,
            'toDate' => $request->ToDate,
        ]);
    }

    // ── Voucher Statement ──
    public function voucherStatement(): View
    {
        return view('backEnd.accounts.report.voucher-statement');
    }

    public function voucherStatementReport(Request $request): View
    {
        $request->validate([
            'FromDate' => 'required|date',
            'ToDate' => 'required|date|after_or_equal:FromDate',
        ]);

        $data = $this->service->getVoucherStatement($request->FromDate, $request->ToDate);

        return view('backEnd.accounts.report.voucher-statement-result', compact('data') + [
            'fromDate' => $request->FromDate,
            'toDate' => $request->ToDate,
        ]);
    }

    public function reconciliation(Request $request): View
    {
        $request->validate([
            'AsOfDate' => 'nullable|date',
        ]);

        $asOfDate = $request->get('AsOfDate', now()->toDateString());
        $rows = $this->service->getReconciliationSummary($asOfDate);

        return view('backEnd.accounts.report.reconciliation', compact('rows', 'asOfDate'));
    }

    private function getHeadName(int $headId, string $fallback): string
    {
        if ($headId <= 0) {
            return $fallback;
        }

        $head = AccountHead::valid()
            ->where('HeadId', $headId)
            ->first(['HeadName']);

        return $head?->HeadName ?: $fallback;
    }
}
