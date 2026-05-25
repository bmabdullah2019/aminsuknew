<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|integer|min:1',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'reference_type' => 'nullable|string|max:80',
        ]);

        $journalEntries = JournalEntry::query()
            ->with([
                'branch:id,name,code',
                'items:id,journal_entry_id,debit,credit',
            ])
            ->when(! empty($validated['branch_id']), function ($query) use ($validated) {
                $query->where('branch_id', (int) $validated['branch_id']);
            })
            ->when(! empty($validated['date_from']), function ($query) use ($validated) {
                $query->whereDate('date', '>=', $validated['date_from']);
            })
            ->when(! empty($validated['date_to']), function ($query) use ($validated) {
                $query->whereDate('date', '<=', $validated['date_to']);
            })
            ->when(! empty($validated['reference_type']), function ($query) use ($validated) {
                $query->where('reference_type', $validated['reference_type']);
            })
            ->latest('date')
            ->latest('id')
            ->paginate(25)
            ->appends($request->query());

        $branches = Branch::query()->orderBy('name')->get(['id', 'name', 'code']);

        return view('backEnd.journal.index', compact('journalEntries', 'branches'));
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $branches = Branch::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'code']);
        $accounts = Account::query()->where('Validity', 1)->where('HasChild', 0)->orderBy('AccType')->orderBy('HeadCode')->get(['HeadId', 'HeadCode', 'HeadName', 'AccType']);

        return view('backEnd.journal.create', compact('branches', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'date' => 'required|date',
            'reference_type' => 'nullable|string|max:80',
            'reference_id' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|integer|exists:accounts_head,HeadId',
            'items.*.debit' => 'nullable|numeric|min:0',
            'items.*.credit' => 'nullable|numeric|min:0',
        ]);

        $normalizedItems = [];
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($validated['items'] as $item) {
            $debit = round((float) ($item['debit'] ?? 0), 2);
            $credit = round((float) ($item['credit'] ?? 0), 2);

            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            if ($debit > 0 && $credit > 0) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Each journal line must be either debit or credit, not both.');
            }

            $normalizedItems[] = [
                'account_id' => (int) $item['account_id'],
                'debit' => $debit,
                'credit' => $credit,
            ];

            $debitTotal += $debit;
            $creditTotal += $credit;
        }

        if (count($normalizedItems) < 2) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Journal entry requires at least two non-zero lines.');
        }

        if ($debitTotal <= 0 || $creditTotal <= 0 || abs($debitTotal - $creditTotal) > 0.0001) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Journal entry is not balanced. Total debit must equal total credit.');
        }

        $journalEntry = DB::transaction(function () use ($validated, $normalizedItems) {
            $entry = JournalEntry::create([
                'branch_id' => (int) $validated['branch_id'],
                'date' => $validated['date'],
                'reference_type' => $validated['reference_type'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($normalizedItems as $item) {
                $entry->items()->create($item);
            }

            return $entry;
        });

        return redirect()->route('admin.journal.show', $journalEntry->id)
            ->with('success', 'Journal entry created successfully.');
    }

    public function show(int $journal): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $journal = JournalEntry::query()->findOrFail($journal);
        $journal->load([
            'branch:id,name,code',
            'items.account:HeadId,HeadCode,HeadName,AccType',
        ]);

        return view('backEnd.journal.show', compact('journal'));
    }

    private function ready(): bool
    {
        return Schema::hasTable('branches')
            && Schema::hasTable('accounts_head')
            && Schema::hasTable('journal_entries')
            && Schema::hasTable('journal_entry_items');
    }

    private function missingTableRedirect(): RedirectResponse
    {
        return redirect()->route('admin.dashboard')
            ->with('error', 'Journal module is not ready. Run migrations first.');
    }
}
