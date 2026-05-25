<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_order_id',
        'order_detail_id',
        'product_id',
        'warehouse_id',
        'return_quantity',
        'unit_price',
        'unit_cost',
        'return_condition',
        'restock_quantity',
        'damage_quantity',
        'refund_amount',
        'replacement_order_id',
        'notes',
    ];

    protected $casts = [
        'return_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'restock_quantity' => 'decimal:2',
        'damage_quantity' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    // Relationships
    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetails::class, 'order_detail_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function replacementOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'replacement_order_id');
    }

    // Scopes
    public function scopeRestockable($query)
    {
        return $query->whereIn('return_condition', ['new', 'opened']);
    }

    public function scopeDamaged($query)
    {
        return $query->whereIn('return_condition', ['damaged', 'defective', 'expired']);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // Accessors
    public function getReturnValueAttribute(): float
    {
        return $this->return_quantity * $this->unit_price;
    }

    public function getIsRestockableAttribute(): bool
    {
        return in_array($this->return_condition, ['new', 'opened']);
    }

    public function getIsDamagedAttribute(): bool
    {
        return in_array($this->return_condition, ['damaged', 'defective', 'expired']);
    }

    public function getConditionColorAttribute(): string
    {
        return match ($this->return_condition) {
            'new' => 'success',
            'opened' => 'info',
            'damaged' => 'warning',
            'defective' => 'danger',
            'expired' => 'danger',
            default => 'secondary',
        };
    }

    // Methods
    public function calculateRefundAmount(): float
    {
        if (! $this->returnOrder || ! $this->returnOrder->refund_eligible) {
            return 0.0;
        }

        $amount = (float) $this->return_quantity * (float) $this->unit_price;
        if ($this->returnOrder->damage_flag || $this->is_damaged) {
            $amount *= 0.8;
        }

        return round($amount, 2);
    }

    public function shouldBeRestocked(): bool
    {
        $returnOrder = $this->returnOrder;
        if (! $returnOrder) {
            return false;
        }

        return ! $returnOrder->damage_flag
            && $returnOrder->restock_flag
            && $this->is_restockable;
    }

    public function shouldCreateDamageEntry(): bool
    {
        $returnOrder = $this->returnOrder;
        if (! $returnOrder) {
            return $this->is_damaged;
        }

        return $returnOrder->damage_flag || $this->is_damaged;
    }

    public function updateOrderDetail(): void
    {
        if ($this->orderDetail) {
            $this->orderDetail->increment('returned_quantity', $this->return_quantity);
        }
    }

    protected static function booted(): void
    {
        static::created(function (ReturnItem $item) {
            $item->updateOrderDetail();
        });
    }
}
