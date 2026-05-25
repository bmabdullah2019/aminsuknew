<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\SubsidiaryRequest;
use App\Models\Accounts\AccountHead;
use App\Models\Accounts\AccountSubsidiary;
use App\Models\Accounts\AccountSubsidiaryHead;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubsidiaryController extends Controller
{
    public function index(Request $request): View
    {
        $subsidiaries = AccountSubsidiary::valid()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('SubName', 'like', '%'.$request->search.'%')
                    ->orWhere('SubCode', 'like', '%'.$request->search.'%');
            })
            ->orderByDesc('SubId')
            ->paginate(30)
            ->appends($request->query());

        return view('backEnd.accounts.subsidiary.index', compact('subsidiaries'));
    }

    public function create(): View
    {
        $subCode = AccountSubsidiary::newSubCode();
        $heads = AccountHead::valid()->where('HasChild', 0)->orderBy('HeadCode')->get();

        return view('backEnd.accounts.subsidiary.form', [
            'subsidiary' => null,
            'subCode' => $subCode,
            'heads' => $heads,
            'assignedHeads' => [],
        ]);
    }

    public function edit(int $id): View
    {
        $subsidiary = AccountSubsidiary::findOrFail($id);
        $heads = AccountHead::valid()->where('HasChild', 0)->orderBy('HeadCode')->get();
        $assignedHeads = AccountSubsidiaryHead::valid()
            ->where('SubId', $id)
            ->pluck('HeadId')
            ->toArray();

        return view('backEnd.accounts.subsidiary.form', [
            'subsidiary' => $subsidiary,
            'subCode' => $subsidiary->SubCode,
            'heads' => $heads,
            'assignedHeads' => $assignedHeads,
        ]);
    }

    public function store(SubsidiaryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = auth()->user()->name ?? 'system';
        $now = now()->toDateTimeString();

        DB::transaction(function () use ($request, $user, $now) {
            $subId = $request->input('SubId');

            if ($subId) {
                // Update
                $subsidiary = AccountSubsidiary::findOrFail($subId);
                $subsidiary->update([
                    'SubName' => $request->SubName,
                    'Description' => $request->Description ?? '',
                    'Status' => $request->Status,
                    'UpdatedBy' => $user,
                    'UpdatedAt' => $now,
                ]);

                // Remove old head assignments
                AccountSubsidiaryHead::where('SubId', $subId)->update([
                    'Validity' => 0, 'DeletedBy' => $user, 'DeletedAt' => $now,
                ]);
            } else {
                // Create
                $subsidiary = AccountSubsidiary::create([
                    'ComId' => 0,
                    'SubCode' => AccountSubsidiary::newSubCode(),
                    'SubName' => $request->SubName,
                    'Description' => $request->Description ?? '',
                    'Status' => $request->Status,
                    'CreatedBy' => $user,
                    'CreatedAt' => $now,
                    'Validity' => 1,
                ]);
                $subId = $subsidiary->SubId;
            }

            // Assign heads
            foreach ($request->input('HeadId', []) as $headId) {
                AccountSubsidiaryHead::create([
                    'SubId' => $subId,
                    'ComId' => 0,
                    'HeadId' => (int) $headId,
                    'CreatedBy' => $user,
                    'CreatedAt' => $now,
                    'Validity' => 1,
                ]);
            }
        });

        Toastr::success('Subsidiary saved successfully.');

        return redirect()->route('admin.accounts.subsidiary.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $subId = (int) $request->input('SubId', 0);
        $user = auth()->user()->name ?? 'system';
        $now = now()->toDateTimeString();

        AccountSubsidiary::where('SubId', $subId)->update([
            'Validity' => 0, 'DeletedBy' => $user, 'DeletedAt' => $now,
        ]);

        AccountSubsidiaryHead::where('SubId', $subId)->update([
            'Validity' => 0, 'DeletedBy' => $user, 'DeletedAt' => $now,
        ]);

        Toastr::success('Subsidiary deleted.');

        return redirect()->route('admin.accounts.subsidiary.index');
    }
}
