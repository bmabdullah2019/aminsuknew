<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class LedgerJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_number',
        'journal_type',
        'branch_id',
        'journal_date',
        'reference_type',
        'reference_id',
        'description',
        'currency',
        'total_debit_minor',
        'total_credit_minor',
        'status',
        'immutable_hash',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'branch_id' => 'integer',
        'total_debit_minor' => 'integer',
        'total_credit_minor' => 'integer',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Ledger journals are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('Ledger journals are immutable and cannot be deleted.');
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'ledger_journal_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateJournalNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = 'JRN-'.now()->format('YmdHis').'-'.random_int(1000, 9999);
            if (! static::where('journal_number', $candidate)->exists()) {
                return $candidate;
            }
            usleep(10000);
        }

        return 'JRN-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(2)));
    }
}
