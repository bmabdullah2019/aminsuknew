<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_transfer_id',
        'product_id',
        'sku',
        'quantity_requested',
        'quantity_dispatched',
        'quantity_received',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_dispatched' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    // Relationships

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransfer::class, 'warehouse_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors

    public function getQuantityPendingAttribute(): float
    {
        return $this->quantity_requested - $this->quantity_dispatched;
    }

    public function getQuantityShortageAttribute(): float
    {
        return $this->quantity_dispatched - $this->quantity_received;
    }

    public function getIsFullyDispatchedAttribute(): bool
    {
        return $this->quantity_dispatched >= $this->quantity_requested;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->quantity_received >= $this->quantity_dispatched;
    }
}
