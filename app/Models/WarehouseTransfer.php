<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_date',
        'status',
        'reason',
        'shipping_method',
        'tracking_number',
        'estimated_arrival',
        'actual_arrival',
        'notes',
        'requested_by',
        'approved_by',
        'dispatched_by',
        'received_by',
        'approved_at',
        'dispatched_at',
        'received_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'estimated_arrival' => 'date',
        'actual_arrival' => 'date',
        'approved_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = self::generateTransferNumber();
            }
            $transfer->requested_by = auth()->id();
        });
    }

    public static function generateTransferNumber(): string
    {
        $year = date('Y');
        $lastTransfer = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastTransfer ? intval(substr($lastTransfer->transfer_number, -3)) + 1 : 1;

        return 'TRF-'.$year.'-'.str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    // Relationships

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Scopes

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'dispatched');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Status Methods

    public function approve(int $userId): bool
    {
        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();

        return $this->save();
    }

    public function reject(int $userId): bool
    {
        $this->status = 'rejected';
        $this->approved_by = $userId;
        $this->approved_at = now();

        return $this->save();
    }

    public function dispatch(int $userId): bool
    {
        $this->status = 'dispatched';
        $this->dispatched_by = $userId;
        $this->dispatched_at = now();

        return $this->save();
    }

    public function receive(int $userId): bool
    {
        $this->status = 'received';
        $this->received_by = $userId;
        $this->received_at = now();
        $this->actual_arrival = now()->toDateString();

        return $this->save();
    }

    public function complete(): bool
    {
        $this->status = 'completed';

        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = 'cancelled';

        return $this->save();
    }
}
