<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Inventory extends Model
{
    use HasFactory;

    // Points to the dedicated per-variant inventory table (NOT warehouse_stock)
    protected $table = 'inventories';

    protected $fillable = [
        'branch_id',
        'product_variant_id',
        'warehouse_id',
        'quantity_available',
        'quantity_reserved',
        'reorder_level',
        'last_updated_at',
        'total_value',
    ];

    protected $casts = [
        'quantity_available' => 'decimal:2',
        'quantity_reserved' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'total_value' => 'decimal:2',
        'last_updated_at' => 'datetime',
    ];

    protected $appends = [
        'sellable_stock',
        'is_low_stock',
        'is_out_of_stock',
        'stock_status',
        'stock_status_color',
    ];

    // Relationships

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id', 'warehouse_id')
            ->where('product_variant_id', $this->product_variant_id)
            ->orderBy('created_at', 'desc');
    }

    // Accessors

    /**
     * Sellable stock = available minus reserved.
     */
    public function getSellableStockAttribute(): float
    {
        if (array_key_exists('sellable_stock', $this->attributes)) {
            return (float) $this->attributes['sellable_stock'];
        }
        $avail = (float) ($this->attributes['quantity_available'] ?? 0);
        $reserved = (float) ($this->attributes['quantity_reserved'] ?? 0);

        return max(0, $avail - $reserved);
    }

    public function getTotalValueAttribute($value = null): float
    {
        if (isset($this->attributes['total_value']) && $this->attributes['total_value'] !== null) {
            return (float) $this->attributes['total_value'];
        }
        $variant = $this->relationLoaded('productVariant') ? $this->getRelation('productVariant') : null;
        $costPrice = $variant ? (float) ($variant->cost_price ?? 0) : 0.0;

        return (float) ($this->attributes['quantity_available'] ?? 0) * $costPrice;
    }

    public function getIsLowStockAttribute(): bool
    {
        $sellable = $this->sellable_stock;

        return $sellable <= (float) ($this->attributes['reorder_level'] ?? 0) && $sellable > 0;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->sellable_stock <= 0;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        }
        if ($this->is_low_stock) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function getStockStatusColorAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'danger',
            'low_stock' => 'warning',
            'in_stock' => 'success',
            default => 'secondary',
        };
    }

    // Scopes

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByProductVariant($query, $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('(quantity_available - quantity_reserved) <= reorder_level')
            ->whereRaw('(quantity_available - quantity_reserved) > 0');
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(quantity_available - quantity_reserved) <= 0');
    }

    public function scopeInStock($query)
    {
        return $query->whereRaw('(quantity_available - quantity_reserved) > reorder_level');
    }

    public function scopeOverstock($query)
    {
        return $query->whereRaw('(quantity_available - quantity_reserved) >= (reorder_level * 1.5)');
    }

    // Helper Methods

    public function increaseStock(float $quantity, ?float $unitCost = null): bool
    {
        $existingQuantity = (float) ($this->attributes['quantity_available'] ?? 0);
        $newQuantity = $existingQuantity + $quantity;

        $this->attributes['quantity_available'] = $newQuantity;

        if ($unitCost !== null && $newQuantity > 0) {
            $variant = $this->relationLoaded('productVariant') ? $this->getRelation('productVariant') : null;
            if ($variant) {
                $existingUnitCost = (float) ($variant->cost_price ?? 0);
                $currentValue = $existingQuantity * $existingUnitCost;
                $incomingValue = $quantity * $unitCost;
                $variant->cost_price = ($currentValue + $incomingValue) / $newQuantity;
                $variant->save();
            }
        }

        $this->attributes['last_updated_at'] = now();

        return $this->save();
    }

    public function decreaseStock(float $quantity): bool
    {
        if ($this->sellable_stock < $quantity) {
            throw new \Exception('Insufficient stock available');
        }

        $this->attributes['quantity_available'] = max(0, (float) ($this->attributes['quantity_available'] ?? 0) - $quantity);
        $this->attributes['last_updated_at'] = now();

        return $this->save();
    }

    public function reserveStock(float $quantity): bool
    {
        if ($this->sellable_stock < $quantity) {
            throw new \Exception('Insufficient stock available for reservation');
        }

        $this->attributes['quantity_reserved'] = (float) ($this->attributes['quantity_reserved'] ?? 0) + $quantity;
        $this->attributes['last_updated_at'] = now();

        return $this->save();
    }

    public function releaseReservedStock(float $quantity): bool
    {
        $currentReserved = (float) ($this->attributes['quantity_reserved'] ?? 0);
        if ($currentReserved < $quantity) {
            throw new \Exception('Cannot release more than reserved quantity');
        }

        $this->attributes['quantity_reserved'] = max(0, $currentReserved - $quantity);
        $this->attributes['last_updated_at'] = now();

        return $this->save();
    }

    public function adjustStock(float $newQuantity, string $reason): bool
    {
        $this->attributes['quantity_available'] = max(0, $newQuantity);
        $this->attributes['last_updated_at'] = now();

        return $this->save();
    }

    public function canFulfill(float $quantity): bool
    {
        return $this->sellable_stock >= $quantity;
    }

    public function getAvailableForAllocation(): float
    {
        return $this->sellable_stock;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($inventory) {
            // Clamp negatives
            $avail = max(0, (float) ($inventory->attributes['quantity_available'] ?? 0));
            $reserved = max(0, (float) ($inventory->attributes['quantity_reserved'] ?? 0));

            if ($reserved > $avail) {
                $reserved = $avail;
            }

            $inventory->attributes['quantity_available'] = $avail;
            $inventory->attributes['quantity_reserved'] = $reserved;
            $inventory->attributes['last_updated_at'] = now();

            // Recompute total value if possible
            if (array_key_exists('total_value', $inventory->attributes) || $inventory->exists) {
                $variant = isset($inventory->relations['productVariant']) ? $inventory->getRelation('productVariant') : null;
                $costPrice = $variant ? (float) ($variant->cost_price ?? 0) : 0.0;
                $inventory->attributes['total_value'] = $avail * $costPrice;
            }

            // Handle branch_id if the column exists
            if (self::hasBranchColumn()) {
                if (empty($inventory->branch_id) && ! empty($inventory->warehouse_id)) {
                    $inventory->branch_id = (int) (Warehouse::query()
                        ->whereKey((int) $inventory->warehouse_id)
                        ->value('branch_id')
                        ?? 0);
                }
            }
        });
    }

    private static function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $hasBranchColumn = Schema::hasColumn('inventories', 'branch_id');
        }

        return $hasBranchColumn;
    }
}
