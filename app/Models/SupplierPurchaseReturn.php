<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupplierPurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'branch_id',
        'supplier_id',
        'original_purchase_id',
        'return_date',
        'total_amount',
        'return_reason',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'stock_processed_at',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'stock_processed_at' => 'datetime',
    ];

    protected $appends = [
        'return_reason_label',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function originalPurchase(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'original_purchase_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPurchaseReturnItem::class, 'supplier_purchase_return_id');
    }

    // Accessors

    public function getReturnReasonLabelAttribute(): string
    {
        return match ($this->return_reason) {
            'damaged' => 'Damaged Goods',
            'wrong_item' => 'Wrong Item',
            'quality_issue' => 'Quality Issue',
            'over_supply' => 'Over Supply',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', $this->return_reason)),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'approved' => 'Approved',
            'completed' => 'Completed',
            default => ucfirst($this->status),
        };
    }

    // Boot method to auto-generate return number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (empty($return->return_number)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('document_sequences')) {
                    $return->return_number = app(\App\Services\DocumentNumberService::class)
                        ->next('supplier_purchase_return', 'RTN-'.now()->format('Ymd').'-', 0, 5);
                } else {
                    do {
                        $candidate = 'RTN-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
                    } while (static::where('return_number', $candidate)->exists());

                    $return->return_number = $candidate;
                }
            }

            if (empty($return->branch_id)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('branches')) {
                    $return->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $return->branch_id = 1;
                }
            }
        });
    }

    // Scopes

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByReason($query, $reason)
    {
        return $query->where('return_reason', $reason);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('return_date', [$startDate, $endDate]);
    }
}
