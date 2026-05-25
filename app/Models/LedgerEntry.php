<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'ledger_journal_id',
        'account_code',
        'direction',
        'amount_minor',
        'currency',
        'description',
        'meta',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Ledger entries are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('Ledger entries are immutable and cannot be deleted.');
        });
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(LedgerJournal::class, 'ledger_journal_id');
    }
}
