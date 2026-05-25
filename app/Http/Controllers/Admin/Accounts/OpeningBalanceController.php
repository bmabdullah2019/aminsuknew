<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\OpeningBalanceRequest;
use App\Models\Accounts\AccountOpening;
use App\Models\Accounts\AccountTree;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OpeningBalanceController extends Controller
{
    public function index(): View
    {
        // Get all leaf accounts from the tree
        $heads = AccountTree::valid()
            ->where('HasChild', 0)
            ->orderBy('Serial')
            ->get();

        // Get existing opening balances
        $openings = AccountOpening::valid()
            ->where('ModuleName', 'accounts_opening')
            ->select('TranHead', DB::raw('SUM(Debit) as Debit'), DB::raw('SUM(Credit) as Credit'))
            ->groupBy('TranHead')
            ->get()
            ->keyBy('TranHead');

        return view('backEnd.accounts.opening.form', compact('heads', 'openings'));
    }

    public function store(OpeningBalanceRequest $request): JsonResponse
    {
        $user = auth()->user()->name ?? 'system';
        $now = now()->toDateTimeString();
        $today = now()->toDateString();

        DB::transaction(function () use ($request, $user, $now, $today) {
            // Delete all existing manual opening entries
            DB::table('accounts_opening')
                ->where('ModuleName', 'accounts_opening')
                ->update(['Validity' => 0, 'DeletedBy' => $user, 'DeletedAt' => $now]);

            $headIds = $request->input('HeadId', []);
            $debits = $request->input('Debit', []);
            $credits = $request->input('Credit', []);

            $rows = [];
            foreach ($headIds as $k => $hid) {
                $debit = (float) ($debits[$k] ?? 0);
                $credit = (float) ($credits[$k] ?? 0);

                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }

                $rows[] = [
                    'OpeningDate' => $today,
                    'FiscalYearId' => 0,
                    'ComId' => 0,
                    'TranHead' => (int) $hid,
                    'Debit' => $debit,
                    'Credit' => $credit,
                    'ModuleName' => 'accounts_opening',
                    'ModuleId' => 0,
                    'CreatedBy' => $user,
                    'CreatedAt' => $now,
                    'Validity' => 1,
                ];
            }

            if (! empty($rows)) {
                DB::table('accounts_opening')->insert($rows);
            }
        });

        return response()->json(['hasError' => 0, 'message' => 'Opening balances saved successfully.']);
    }
}
