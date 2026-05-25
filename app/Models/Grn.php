<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grn extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_number',
        'warehouse_id',
        'supplier_id',
        'grn_date',
        'invoice_number',
        'invoice_date',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'other_charges',
        'total_amount',
        'notes',
        'received_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'grn_date' => 'date',
        'invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($grn) {
            if (empty($grn->grn_number)) {
                $grn->grn_number = self::generateGrnNumber();
            }
            $grn->received_by = auth()->id();
        });
    }

    public static function generateGrnNumber(): string
    {
        $year = date('Y');
        $lastGrn = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastGrn ? intval(substr($lastGrn->grn_number, -3)) + 1 : 1;

        return 'GRN-'.$year.'-'.str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    // Relationships

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Status Methods

    public function approve(int $userId): bool
    {
        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();

        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = 'cancelled';

        return $this->save();
    }
}
