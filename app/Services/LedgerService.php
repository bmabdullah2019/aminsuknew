<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\LedgerJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class LedgerService
{
    /**
     * @param  array<int,array{account_code:string,direction:string,amount_minor:int,description?:string,meta?:array<string,mixed>}>  $entries
     * @param  array<string,mixed>  $meta
     */
    public function postJournal(
        array $entries,
        string $referenceType,
        ?int $referenceId,
        string $description,
        string $currency = 'BDT',
        ?int $createdBy = null,
        string $journalType = 'general',
        array $meta = [],
        ?int $branchId = null
    ): LedgerJournal {
        $normalizedCurrency = strtoupper(trim($currency)) ?: 'BDT';
        $this->validateEntries($entries);
        $hasBranchColumn = $this->hasLedgerJournalBranchColumn();
        $resolvedBranchId = $hasBranchColumn ? ($branchId ?: $this->defaultBranchId()) : null;

        $totals = $this->calculateTotals($entries);
        if ($totals['debit'] !== $totals['credit']) {
            throw new InvalidArgumentException('Ledger journal is not balanced.');
        }

        return DB::transaction(function () use (
            $entries,
            $referenceType,
            $referenceId,
            $description,
            $normalizedCurrency,
            $createdBy,
            $journalType,
            $meta,
            $totals,
            $resolvedBranchId,
            $hasBranchColumn
        ) {
            if ($referenceId !== null) {
                $existingJournal = LedgerJournal::query()
                    ->where('journal_type', $journalType)
                    ->where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId)
                    ->first();

                if ($existingJournal) {
                    return $existingJournal->load('entries');
                }
            }

            $payloadForHash = [
                'journal_type' => $journalType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'currency' => $normalizedCurrency,
                'entries' => $entries,
                'meta' => $meta,
            ];

            $payload = [
                'journal_number' => LedgerJournal::generateJournalNumber(),
                'journal_type' => $journalType,
                'journal_date' => now()->toDateString(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'currency' => $normalizedCurrency,
                'total_debit_minor' => $totals['debit'],
                'total_credit_minor' => $totals['credit'],
                'status' => 'posted',
                'immutable_hash' => hash('sha256', json_encode($payloadForHash)),
                'meta' => $meta,
                'created_by' => $createdBy,
            ];
            if ($hasBranchColumn) {
                $payload['branch_id'] = $resolvedBranchId;
            }

            $journal = LedgerJournal::create($payload);

            foreach ($entries as $entry) {
                LedgerEntry::create([
                    'ledger_journal_id' => $journal->id,
                    'account_code' => trim((string) $entry['account_code']),
                    'direction' => trim((string) $entry['direction']),
                    'amount_minor' => (int) $entry['amount_minor'],
                    'currency' => $normalizedCurrency,
                    'description' => $entry['description'] ?? $description,
                    'meta' => $entry['meta'] ?? null,
                ]);
            }

            return $journal->load('entries');
        });
    }

    /**
     * @param  array<int,array{account_code:string,direction:string,amount_minor:int}>  $entries
     */
    protected function validateEntries(array $entries): void
    {
        if (count($entries) < 2) {
            throw new InvalidArgumentException('A ledger journal requires at least two entries.');
        }

        foreach ($entries as $entry) {
            $accountCode = trim((string) ($entry['account_code'] ?? ''));
            $direction = trim((string) ($entry['direction'] ?? ''));
            $amountMinor = (int) ($entry['amount_minor'] ?? 0);

            if ($accountCode === '') {
                throw new InvalidArgumentException('Ledger entry account code is required.');
            }

            if (! in_array($direction, ['debit', 'credit'], true)) {
                throw new InvalidArgumentException('Ledger entry direction must be debit or credit.');
            }

            if ($amountMinor <= 0) {
                throw new InvalidArgumentException('Ledger entry amount must be greater than zero.');
            }
        }
    }

    /**
     * @param  array<int,array{direction:string,amount_minor:int}>  $entries
     * @return array{debit:int,credit:int}
     */
    protected function calculateTotals(array $entries): array
    {
        $debit = 0;
        $credit = 0;

        foreach ($entries as $entry) {
            $amount = (int) $entry['amount_minor'];
            if ($entry['direction'] === 'debit') {
                $debit += $amount;
            } else {
                $credit += $amount;
            }
        }

        return ['debit' => $debit, 'credit' => $credit];
    }

    private function defaultBranchId(): int
    {
        if (! Schema::hasTable('branches')) {
            return 1;
        }

        return (int) (DB::table('branches')->where('code', 'MAIN')->value('id')
            ?? DB::table('branches')->value('id')
            ?? 1);
    }

    private function hasLedgerJournalBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $hasBranchColumn = Schema::hasTable('ledger_journals')
                && Schema::hasColumn('ledger_journals', 'branch_id');
        }

        return (bool) $hasBranchColumn;
    }

    /**
     * Reverse all ledger journals for a given reference (e.g. when an order is deleted).
     * Creates counter-journals with debits and credits swapped.
     *
     * @return int Number of journals reversed
     */
    public function reverseJournalsByReference(string $referenceType, int $referenceId, ?int $createdBy = null): int
    {
        $journals = LedgerJournal::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', 'posted')
            ->with('entries')
            ->get();

        if ($journals->isEmpty()) {
            return 0;
        }

        $reversed = 0;

        foreach ($journals as $journal) {
            // Skip if already reversed
            $alreadyReversed = LedgerJournal::query()
                ->where('journal_type', 'reversal')
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->where('description', 'LIKE', '%Reversal of '.$journal->journal_number.'%')
                ->exists();

            if ($alreadyReversed) {
                continue;
            }

            // Build reversed entries (swap debit <-> credit)
            $reversedEntries = [];
            foreach ($journal->entries as $entry) {
                $reversedEntries[] = [
                    'account_code' => $entry->account_code,
                    'direction' => $entry->direction === 'debit' ? 'credit' : 'debit',
                    'amount_minor' => (int) $entry->amount_minor,
                    'description' => 'Reversal: '.($entry->description ?? ''),
                ];
            }

            if (count($reversedEntries) < 2) {
                continue;
            }

            $this->postJournal(
                entries: $reversedEntries,
                referenceType: $referenceType,
                referenceId: null, // null to avoid idempotency check blocking reversal
                description: 'Reversal of '.$journal->journal_number.' for '.$referenceType.' #'.$referenceId,
                currency: $journal->currency ?? 'BDT',
                createdBy: $createdBy,
                journalType: 'reversal',
                meta: [
                    'reversed_journal_id' => $journal->id,
                    'reversed_journal_number' => $journal->journal_number,
                    'original_reference_id' => $referenceId,
                ],
                branchId: $journal->branch_id ?? null
            );

            $reversed++;
        }

        return $reversed;
    }
}
