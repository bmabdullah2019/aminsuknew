<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\FiscalYearRequest;
use App\Models\Accounts\AccountClosing;
use App\Services\AccountsService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalYearController extends Controller
{
    public function index(): View
    {
        $years = AccountClosing::valid()->orderByDesc('FiscalYearId')->paginate(20);

        return view('backEnd.accounts.fiscal-year.index', compact('years'));
    }

    public function create(): View
    {
        return view('backEnd.accounts.fiscal-year.form', ['year' => null]);
    }

    public function edit(int $id): View
    {
        $year = AccountClosing::findOrFail($id);

        return view('backEnd.accounts.fiscal-year.form', compact('year'));
    }

    public function store(FiscalYearRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = auth()->user()->name ?? 'system';
        $now = now()->toDateTimeString();
        $id = $request->input('FiscalYearId');

        if ($id) {
            $year = AccountClosing::findOrFail($id);
            $year->update([
                'OpeningDate' => $request->OpeningDate,
                'ClosingDate' => $request->ClosingDate,
                'Remarks' => $request->Remarks ?? '',
                'UpdatedBy' => $user,
                'UpdatedAt' => $now,
            ]);
        } else {
            AccountClosing::create([
                'ComId' => 0,
                'OpeningDate' => $request->OpeningDate,
                'ClosingDate' => $request->ClosingDate,
                'Remarks' => $request->Remarks ?? '',
                'CreatedBy' => $user,
                'CreatedAt' => $now,
                'Validity' => 1,
            ]);
        }

        Toastr::success('Fiscal year saved successfully.');

        return redirect()->route('admin.accounts.fiscal-year.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $id = (int) $request->input('FiscalYearId', 0);
        $user = auth()->user()->name ?? 'system';

        AccountClosing::where('FiscalYearId', $id)->update([
            'Validity' => 0,
            'DeletedBy' => $user,
            'DeletedAt' => now()->toDateTimeString(),
        ]);

        Toastr::success('Fiscal year deleted.');

        return redirect()->back();
    }

    public function close(int $id, AccountsService $service): RedirectResponse
    {
        try {
            $service->closeFiscalYear($id, auth()->user()->name ?? 'system');
            Toastr::success('Fiscal year closed successfully and balances carried forward.');
        } catch (\Exception $e) {
            Toastr::error('Closing failed: '.$e->getMessage());
        }

        return redirect()->back();
    }
}
