<?php

namespace App\Console\Commands\Finance;

use App\Models\JournalEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckJournalBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:check-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies that all Journal Entries strictly follow double-entry principles (Debits == Credits).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting integrity check on Journal Entries...');

        $entries = JournalEntry::with('items')->get();
        $invalidEntries = [];

        $this->output->progressStart($entries->count());

        foreach ($entries as $entry) {
            $totalDebit = (float) ($entry->items->sum('debit') ?? 0);
            $totalCredit = (float) ($entry->items->sum('credit') ?? 0);

            // Using rounding to avoid floating point precision issues
            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $invalidEntries[] = [
                    'id' => $entry->id,
                    'date' => $entry->date,
                    'reference' => $entry->reference_type.' #'.$entry->reference_id,
                    'debit' => $totalDebit,
                    'credit' => $totalCredit,
                    'difference' => abs($totalDebit - $totalCredit),
                ];
            }
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        if (count($invalidEntries) > 0) {
            $this->error('CRITICAL: Found '.count($invalidEntries).' imbalanced Journal Entries!');
            $this->table(['Journal ID', 'Date', 'Reference', 'Total Debit', 'Total Credit', 'Difference'], $invalidEntries);
            Log::error('Finance Checker found '.count($invalidEntries).' imbalanced journals.', $invalidEntries);

            return Command::FAILURE;
        }

        $this->info("SUCCESS: All {$entries->count()} journal entries are perfectly balanced (Debits = Credits).");

        return Command::SUCCESS;
    }
}
