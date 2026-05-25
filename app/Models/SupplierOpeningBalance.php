<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOpeningBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'opening_date',
        'opening_balance',
        'balance_type',
        'description',
        'created_by',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'opening_balance' => 'decimal:2',
    ];

    // Relationships

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors

    public function getEffectiveBalanceAttribute(): float
    {
        return $this->balance_type === 'debit' ? $this->opening_balance : -$this->opening_balance;
    }
}
