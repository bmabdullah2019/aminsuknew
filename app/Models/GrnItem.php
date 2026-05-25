<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id',
        'product_id',
        'product_variant_id',
        'sku',
        'description',
        'quantity',
        'ordered_quantity',
        'unit_cost',
        'tax_rate',
        'tax_amount',
        'batch_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'ordered_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    protected $appends = [
        'line_total',
    ];

    // Relationships

    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Accessors

    public function getLineTotalAttribute(): float
    {
        return ($this->quantity * $this->unit_cost) + $this->tax_amount;
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }
}
