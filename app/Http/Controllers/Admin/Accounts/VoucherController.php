<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\VoucherRequest;
use App\Models\Accounts\AccountTransaction;
use App\Models\Accounts\AccountTransactionDetail;
use App\Services\AccountsService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! Schema::hasTable('accounts_transaction')) {
            Toastr::error('Accounts module not ready.');

            return redirect()->route('admin.dashboard');
        }

        $vouchers = AccountTransaction::valid()
            ->manual()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('TranNo', 'like', '%'.$request->search.'%')
                    ->orWhere('Remarks', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('from_date'), fn ($q) => $q->where('TranDate', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->where('TranDate', '<=', $request->to_date))
            ->orderByDesc('TranDate')
            ->orderByDesc('TranId')
            ->paginate(30)
            ->appends($request->query());

        $voucherNo = AccountTransaction::newVoucherNo();

        return view('backEnd.accounts.voucher.index', compact('vouchers', 'voucherNo'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.accounts.voucher.index', ['modal' => 'create']);
    }

    public function show(int $id, AccountsService $service): View
    {
        $voucher = AccountTransaction::findOrFail($id);
        $details = $service->getVoucherDetails($id);

        return view('backEnd.accounts.voucher.show', compact('voucher', 'details'));
    }

    public function edit(int $id): RedirectResponse
    {
        AccountTransaction::findOrFail($id);

        return redirect()->route('admin.accounts.voucher.index', ['edit' => $id]);
    }

    public function data(int $id, AccountsService $service): JsonResponse
    {
        $voucher = AccountTransaction::findOrFail($id);
        $details = $service->getVoucherDetails($id);

        return response()->json([
            'voucher' => [
                'tran_id' => $voucher->TranId,
                'tran_no' => $voucher->TranNo,
                'tran_date' => optional($voucher->TranDate)->format('Y-m-d'),
                'remarks' => $voucher->Remarks ?? '',
            ],
            'lines' => $this->mapVoucherLines($details),
        ]);
    }

    public function store(VoucherRequest $request): JsonResponse
    {
        $request->validated();

        $user = auth()->user()->name ?? 'system';
        $now = now()->toDateTimeString();

        $result = DB::transaction(function () use ($request, $user, $now) {
            $tranId = $request->input('TranId');

            if ($tranId) {
                // Update existing
                $voucher = AccountTransaction::findOrFail($tranId);
                $voucher->update([
                    'TranDate' => $request->TranDate,
                    'TranAmount' => $request->TotalDebit,
                    'Remarks' => $request->Remarks ?? '',
                    'UpdatedBy' => $user,
                    'UpdatedAt' => $now,
                ]);

                // Delete old details
                DB::table('accounts_transaction_details')
                    ->where('TranId', $tranId)
                    ->update(['Validity' => 0, 'DeletedBy' => $user, 'DeletedAt' => $now]);
            } else {
                // Create new
                $voucher = AccountTransaction::create([
                    'FiscalYearId' => 0,
                    'ComId' => 0,
                    'TranDate' => $request->TranDate,
                    'TranNo' => AccountTransaction::newVoucherNo(),
                    'TranAmount' => $request->TotalDebit,
                    'Remarks' => $request->Remarks ?? '',
                    'ModuleName' => 'accounts_transaction',
                    'ModuleId' => 0,
                    'CreatedBy' => $user,
                    'CreatedAt' => $now,
                    'Validity' => 1,
                ]);

                $tranId = $voucher->TranId;
            }

            // Build line items from request arrays
            $headIds = $request->input('HeadId', []);
            $subIds = $request->input('SubId', []);
            $debits = $request->input('Debit', []);
            $credits = $request->input('Credit', []);
            $narrations = $request->input('Narration', []);
            $bankNames = $request->input('BankName', []);
            $branchNames = $request->input('BranchName', []);
            $chequeNos = $request->input('ChequeNo', []);
            $chequeDates = $request->input('ChequeDate', []);

            $lines = [];
            foreach ($headIds as $k => $hid) {
                $lines[] = [
                    'HeadId' => (int) $hid,
                    'SubId' => ! empty($subIds[$k]) ? (int) $subIds[$k] : null,
                    'Debit' => (float) ($debits[$k] ?? 0),
                    'Credit' => (float) ($credits[$k] ?? 0),
                    'Narration' => $narrations[$k] ?? '',
                    'BankName' => $bankNames[$k] ?? '',
                    'BranchName' => $branchNames[$k] ?? '',
                    'ChequeNo' => $chequeNos[$k] ?? '',
                    'ChequeDate' => ! empty($chequeDates[$k]) ? $chequeDates[$k] : null,
                ];
            }

            // Create double-entry pairs
            $pairs = AccountTransactionDetail::createDoubleEntryPairs($tranId, $lines);

            // Bulk insert
            DB::table('accounts_transaction_details')->insert($pairs);

            return $voucher;
        });

        return response()->json([
            'hasError' => 0,
            'message' => 'Voucher saved successfully.',
            'tranId' => $result->TranId,
            'tranNo' => $result->TranNo,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $tranId = (int) $request->input('TranId', 0);
        $user = auth()->user()->name ?? 'system';
        $now = now()->toDateTimeString();

        AccountTransaction::where('TranId', $tranId)->update([
            'Validity' => 0,
            'DeletedBy' => $user,
            'DeletedAt' => $now,
        ]);

        DB::table('accounts_transaction_details')
            ->where('TranId', $tranId)
            ->update(['Validity' => 0, 'DeletedBy' => $user, 'DeletedAt' => $now]);

        return response()->json(['hasError' => 0, 'message' => 'Voucher deleted.']);
    }

    public function approve(int $id): RedirectResponse
    {
        $voucher = AccountTransaction::findOrFail($id);

        if ($voucher->ApprovalStatus === 'approved') {
            Toastr::warning('Voucher is already approved.');

            return redirect()->back();
        }

        $voucher->update([
            'ApprovalStatus' => 'approved',
            'ApprovedBy' => auth()->user()->name ?? 'system',
            'ApprovedAt' => now(),
        ]);

        Toastr::success('Voucher approved successfully.');

        return redirect()->back();
    }

    public function reject(int $id): RedirectResponse
    {
        $voucher = AccountTransaction::findOrFail($id);

        if ($voucher->ApprovalStatus === 'rejected') {
            Toastr::warning('Voucher is already rejected.');

            return redirect()->back();
        }

        $voucher->update([
            'ApprovalStatus' => 'rejected',
        ]);

        Toastr::success('Voucher rejected.');

        return redirect()->back();
    }

    private function mapVoucherLines($details): array
    {
        return collect($details)->map(function ($detail) {
            return [
                'head_id' => (int) $detail->TranHead,
                'head_label' => trim(($detail->HeadCode ? $detail->HeadCode.' - ' : '').$detail->HeadName),
                'sub_id' => $detail->SubId ? (int) $detail->SubId : '',
                'sub_label' => trim(($detail->SubCode ? $detail->SubCode.' - ' : '').($detail->SubName ?? '')),
                'narration' => $detail->Narration ?? '',
                'bank_name' => $detail->BankName ?? '',
                'branch_name' => $detail->BranchName ?? '',
                'cheque_no' => $detail->ChequeNo ?? '',
                'cheque_date' => ! empty($detail->ChequeDate) ? \Carbon\Carbon::parse($detail->ChequeDate)->format('Y-m-d') : '',
                'debit' => (float) $detail->Debit,
                'credit' => (float) $detail->Credit,
            ];
        })->values()->all();
    }
}
