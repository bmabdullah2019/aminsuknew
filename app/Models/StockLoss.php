<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLoss extends Model
{
    use HasFactory;

    protected $fillable = [
        'loss_number',
        'warehouse_id',
        'product_id',
        'loss_date',
        'loss_type',
        'quantity',
        'unit_cost',
        'reason_details',
        'attachment_path',
        'reported_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'loss_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'total_loss_value',
        'status',
        'total_value',
        'notes',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($loss) {
            if (empty($loss->loss_number)) {
                $loss->loss_number = self::generateLossNumber();
            }
            $loss->reported_by = auth()->id();
        });
    }

    public static function generateLossNumber(): string
    {
        $year = date('Y');
        $lastLoss = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastLoss ? intval(substr($lastLoss->loss_number, -3)) + 1 : 1;

        return 'LOSS-'.$year.'-'.str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    // Relationships

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockLossItem::class);
    }

    // Accessors

    public function getTotalLossValueAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }

    public function getStatusAttribute(): string
    {
        // Respect persisted enum status while staying backward-compatible.
        return $this->attributes['status'] ?? ($this->approved_at ? 'approved' : 'pending');
    }

    public function getTotalValueAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(function ($item) {
                return (float) $item->quantity * (float) ($item->unit_cost ?? 0);
            });
        }

        // Fallback to legacy single-line fields
        return (float) $this->quantity * (float) ($this->unit_cost ?? 0);
    }

    public function getNotesAttribute(): ?string
    {
        return $this->reason_details;
    }

    // Scopes

    public function scopeByType($query, $type)
    {
        return $query->where('loss_type', $type);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Status Methods

    public function approve(int $userId): bool
    {
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->status = 'approved';

        return $this->save();
    }
}
