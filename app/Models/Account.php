<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Backward-compatibility adapter.
 *
 * After the accounts module rebuild the canonical table is `accounts_head`.
 * This model keeps the old `Account::where('code', ...)` calls working by
 * querying `accounts_head` with translated column names.
 */
class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts_head';

    protected $primaryKey = 'HeadId';

    public $timestamps = false;

    protected $fillable = [
        'HeadCode', 'HeadName', 'AccType', 'Validity',
    ];

    protected $casts = [
        'Validity' => 'boolean',
    ];

    // ── Attribute aliases for backward compatibility ──

    public function getCodeAttribute(): ?string
    {
        return $this->HeadCode;
    }

    public function getNameAttribute(): ?string
    {
        return $this->HeadName;
    }

    public function getTypeAttribute(): ?string
    {
        $typeMap = [1 => 'asset', 2 => 'liability', 3 => 'equity', 4 => 'revenue', 5 => 'expense'];

        return $typeMap[$this->AccType] ?? 'asset';
    }

    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->Validity;
    }

    public function getIdAttribute(): int
    {
        return (int) $this->HeadId;
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('Validity', 1);
    }

    /**
     * Allow legacy code like Account::where('code', 'xxx') to work.
     * Intercepts queries on the virtual `code` column and maps them to HeadCode.
     */
    public function scopeWhereCode($query, string $code)
    {
        return $query->where('HeadCode', $code);
    }

    /**
     * Override the query builder's where to translate known legacy column names.
     */
    public function newQuery()
    {
        return parent::newQuery();
    }

    // ── Relationships (legacy stubs) ──

    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class, 'account_id', 'HeadId');
    }
}
