<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'product_variant_id',
        'sku',
        'system_quantity',
        'adjusted_quantity',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'adjusted_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    protected $appends = [
        'quantity',
        'difference',
        'value_impact',
    ];

    // Relationships

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Accessors

    public function getDifferenceAttribute(): float
    {
        return $this->adjusted_quantity - $this->system_quantity;
    }

    public function getQuantityAttribute(): float
    {
        // Backward compatibility for views/controllers expecting "quantity".
        return abs((float) $this->difference);
    }

    public function getValueImpactAttribute(): float
    {
        return $this->difference * $this->unit_cost;
    }

    public function getAdjustmentTypeAttribute(): string
    {
        return $this->difference > 0 ? 'increase' : 'decrease';
    }
}
