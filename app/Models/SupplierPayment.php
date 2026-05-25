<?php

namespace App\Models;

use App\Models\Accounts\AccountHead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'branch_id',
        'account_head_id',
        'supplier_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'bank_name',
        'bank_account_number',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'payment_method_label',
        'status_label',
    ];

    // Relationships

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function accountHead(): BelongsTo
    {
        return $this->belongsTo(AccountHead::class, 'account_head_id', 'HeadId');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'card' => 'Card',
            'online' => 'Online Payment',
            default => ucfirst(str_replace('_', ' ', $this->payment_method)),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    // Boot method to auto-generate payment number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('document_sequences')) {
                    $payment->payment_number = app(\App\Services\DocumentNumberService::class)
                        ->next('supplier_payment', 'PAY-'.now()->format('Ymd').'-', 0, 5);
                } else {
                    do {
                        $candidate = 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
                    } while (static::where('payment_number', $candidate)->exists());

                    $payment->payment_number = $candidate;
                }
            }

            if (empty($payment->branch_id)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('branches')) {
                    $payment->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $payment->branch_id = 1;
                }
            }
        });
    }

    // Scopes

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
}
