<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'line_amount',
        'item_notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_amount' => 'decimal:2',
    ];

    // Relationships
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Methods
    public function calculateLineAmount()
    {
        return $this->quantity * $this->unit_price;
    }

    public function syncLineAmount()
    {
        $this->update(['line_amount' => $this->calculateLineAmount()]);

        return $this;
    }
}
