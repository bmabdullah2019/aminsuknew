<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CashAccount;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CashAccountController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $validated = $request->validate([
            'search' => 'nullable|string|max:120',
            'branch_id' => 'nullable|integer|min:1',
            'status' => 'nullable|in:1,0,active,inactive',
        ]);

        $cashAccounts = CashAccount::query()
            ->with('branch:id,name,code')
            ->when(! empty($validated['search']), function ($query) use ($validated) {
                $keyword = trim((string) $validated['search']);
                $query->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('account_number', 'like', '%'.$keyword.'%');
                });
            })
            ->when(! empty($validated['branch_id']), function ($query) use ($validated) {
                $query->where('branch_id', (int) $validated['branch_id']);
            })
            ->when(isset($validated['status']) && $validated['status'] !== '', function ($query) use ($validated) {
                $status = strtolower((string) $validated['status']);
                $query->where('status', in_array($status, ['1', 'active'], true));
            })
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        $branches = Branch::query()->orderBy('name')->get(['id', 'name', 'code']);

        return view('backEnd.cash-account.index', compact('cashAccounts', 'branches'));
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);
        $accounts = Account::query()->where('Validity', 1)->where('AccType', 1)->where('HasChild', 0)->orderBy('HeadName')->get(['HeadId', 'HeadName', 'HeadCode']);

        return view('backEnd.cash-account.create', compact('branches', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'account_id' => 'nullable|integer|exists:accounts_head,HeadId',
            'name' => 'required|string|max:120',
            'account_number' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $openingBalance = round((float) ($validated['opening_balance'] ?? 0), 2);
        $currentBalance = array_key_exists('current_balance', $validated)
            ? round((float) $validated['current_balance'], 2)
            : $openingBalance;

        CashAccount::create([
            'branch_id' => (int) $validated['branch_id'],
            'account_id' => isset($validated['account_id']) ? (int) $validated['account_id'] : null,
            'name' => trim((string) $validated['name']),
            'account_number' => $validated['account_number'] ?? null,
            'description' => $validated['description'] ?? null,
            'opening_balance' => $openingBalance,
            'current_balance' => $currentBalance,
            'status' => $request->boolean('status'),
        ]);

        Toastr::success('Cash account created successfully.');

        return redirect()->route('admin.cash-accounts.index');
    }

    public function edit(int $cashAccount): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $cashAccount = CashAccount::query()->findOrFail($cashAccount);
        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);
        $accounts = Account::query()->where('Validity', 1)->where('AccType', 1)->where('HasChild', 0)->orderBy('HeadName')->get(['HeadId', 'HeadName', 'HeadCode']);

        return view('backEnd.cash-account.edit', compact('cashAccount', 'branches', 'accounts'));
    }

    public function update(Request $request, int $cashAccount): RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $cashAccount = CashAccount::query()->findOrFail($cashAccount);

        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'account_id' => 'nullable|integer|exists:accounts_head,HeadId',
            'name' => 'required|string|max:120',
            'account_number' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $cashAccount->update([
            'branch_id' => (int) $validated['branch_id'],
            'account_id' => isset($validated['account_id']) ? (int) $validated['account_id'] : null,
            'name' => trim((string) $validated['name']),
            'account_number' => $validated['account_number'] ?? null,
            'description' => $validated['description'] ?? null,
            'opening_balance' => round((float) ($validated['opening_balance'] ?? 0), 2),
            'current_balance' => round((float) ($validated['current_balance'] ?? 0), 2),
            'status' => $request->boolean('status'),
        ]);

        Toastr::success('Cash account updated successfully.');

        return redirect()->route('admin.cash-accounts.index');
    }

    private function ready(): bool
    {
        return Schema::hasTable('branches') && Schema::hasTable('cash_accounts');
    }

    private function missingTableRedirect(): RedirectResponse
    {
        Toastr::error('Cash accounts module is not ready. Run migrations first.');

        return redirect()->route('admin.dashboard');
    }
}
