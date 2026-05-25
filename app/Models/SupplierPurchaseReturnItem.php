<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_purchase_return_id',
        'purchase_item_id',
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'unit_cost',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchaseReturn::class, 'supplier_purchase_return_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $item): void {
            $quantity = max(0, (float) $item->quantity);
            $unitCost = max(0, (float) $item->unit_cost);

            $item->quantity = $quantity;
            $item->unit_cost = $unitCost;
            $item->line_total = round($quantity * $unitCost, 2);
        });
    }
}
