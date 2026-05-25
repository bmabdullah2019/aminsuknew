<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class SupplierLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'transaction_date',
        'transaction_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'description',
        'debit',
        'credit',
        'running_balance',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_amount',
        'transaction_type_label',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupplierLedger $ledger): void {
            if (! self::hasBranchColumn()) {
                unset($ledger->branch_id);

                return;
            }

            if (empty($ledger->branch_id)) {
                if (Schema::hasTable('branches')) {
                    $ledger->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $ledger->branch_id = 1;
                }
            }
        });
    }

    // Relationships

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors

    public function getFormattedAmountAttribute(): string
    {
        if ($this->debit > 0) {
            return '+BDT '.number_format($this->debit, 2);
        }

        if ($this->credit > 0) {
            return '-BDT '.number_format($this->credit, 2);
        }

        return 'BDT 0.00';
    }

    public function getTransactionTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            'opening_balance' => 'Opening Balance',
            'purchase' => 'Purchase',
            'payment' => 'Payment',
            'purchase_return' => 'Purchase Return',
            'adjustment' => 'Adjustment',
            default => ucfirst(str_replace('_', ' ', $this->transaction_type)),
        };
    }

    // Scopes

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeDebit($query)
    {
        return $query->where('debit', '>', 0);
    }

    public function scopeCredit($query)
    {
        return $query->where('credit', '>', 0);
    }

    private static function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $hasBranchColumn = Schema::hasTable((new self)->getTable())
                && Schema::hasColumn((new self)->getTable(), 'branch_id');
        }

        return (bool) $hasBranchColumn;
    }
}
