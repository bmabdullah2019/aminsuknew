<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $validated = $request->validate([
            'search' => 'nullable|string|max:150',
            'branch_id' => 'nullable|integer|min:1',
            'status' => 'nullable|in:1,0,active,inactive',
        ]);

        $bankAccounts = BankAccount::query()
            ->with('branch:id,name,code')
            ->when(! empty($validated['search']), function ($query) use ($validated) {
                $keyword = trim((string) $validated['search']);
                $query->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('bank_name', 'like', '%'.$keyword.'%')
                        ->orWhere('account_name', 'like', '%'.$keyword.'%')
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
            ->orderBy('bank_name')
            ->paginate(20)
            ->appends($request->query());

        $branches = Branch::query()->orderBy('name')->get(['id', 'name', 'code']);

        return view('backEnd.bank-account.index', compact('bankAccounts', 'branches'));
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);
        $accounts = Account::query()->where('Validity', 1)->where('AccType', 1)->where('HasChild', 0)->orderBy('HeadName')->get(['HeadId', 'HeadName', 'HeadCode']);

        return view('backEnd.bank-account.create', compact('branches', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'account_id' => 'nullable|integer|exists:accounts_head,HeadId',
            'bank_name' => 'required|string|max:150',
            'account_name' => 'nullable|string|max:150',
            'account_number' => ['required', 'string', 'max:120', Rule::unique('bank_accounts', 'account_number')],
            'routing_number' => 'nullable|string|max:80',
            'swift_code' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $openingBalance = round((float) ($validated['opening_balance'] ?? 0), 2);
        $currentBalance = array_key_exists('current_balance', $validated)
            ? round((float) $validated['current_balance'], 2)
            : $openingBalance;

        BankAccount::create([
            'branch_id' => (int) $validated['branch_id'],
            'account_id' => isset($validated['account_id']) ? (int) $validated['account_id'] : null,
            'bank_name' => trim((string) $validated['bank_name']),
            'account_name' => $validated['account_name'] ?? null,
            'account_number' => trim((string) $validated['account_number']),
            'routing_number' => $validated['routing_number'] ?? null,
            'swift_code' => $validated['swift_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'opening_balance' => $openingBalance,
            'current_balance' => $currentBalance,
            'status' => $request->boolean('status'),
        ]);

        Toastr::success('Bank account created successfully.');

        return redirect()->route('admin.bank-accounts.index');
    }

    public function edit(int $bankAccount): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $bankAccount = BankAccount::query()->findOrFail($bankAccount);
        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);
        $accounts = Account::query()->where('Validity', 1)->where('AccType', 1)->where('HasChild', 0)->orderBy('HeadName')->get(['HeadId', 'HeadName', 'HeadCode']);

        return view('backEnd.bank-account.edit', compact('bankAccount', 'branches', 'accounts'));
    }

    public function update(Request $request, int $bankAccount): RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $bankAccount = BankAccount::query()->findOrFail($bankAccount);

        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'account_id' => 'nullable|integer|exists:accounts_head,HeadId',
            'bank_name' => 'required|string|max:150',
            'account_name' => 'nullable|string|max:150',
            'account_number' => ['required', 'string', 'max:120', Rule::unique('bank_accounts', 'account_number')->ignore($bankAccount->id)],
            'routing_number' => 'nullable|string|max:80',
            'swift_code' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $bankAccount->update([
            'branch_id' => (int) $validated['branch_id'],
            'account_id' => isset($validated['account_id']) ? (int) $validated['account_id'] : null,
            'bank_name' => trim((string) $validated['bank_name']),
            'account_name' => $validated['account_name'] ?? null,
            'account_number' => trim((string) $validated['account_number']),
            'routing_number' => $validated['routing_number'] ?? null,
            'swift_code' => $validated['swift_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'opening_balance' => round((float) ($validated['opening_balance'] ?? 0), 2),
            'current_balance' => round((float) ($validated['current_balance'] ?? 0), 2),
            'status' => $request->boolean('status'),
        ]);

        Toastr::success('Bank account updated successfully.');

        return redirect()->route('admin.bank-accounts.index');
    }

    private function ready(): bool
    {
        return Schema::hasTable('branches') && Schema::hasTable('bank_accounts');
    }

    private function missingTableRedirect(): RedirectResponse
    {
        Toastr::error('Bank accounts module is not ready. Run migrations first.');

        return redirect()->route('admin.dashboard');
    }
}
