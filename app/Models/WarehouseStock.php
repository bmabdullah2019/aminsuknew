<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseStock extends Model
{
    use HasFactory;

    protected $table = 'warehouse_stock';

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'sku',
        'physical_quantity',
        'available_quantity',
        'reserved_quantity',
        'reorder_point',
        'reorder_quantity',
        'average_cost',
        'total_value',
        'expiry_date',
        'last_stock_in_date',
        'last_stock_out_date',
        'last_audit_date',
    ];

    protected $casts = [
        'physical_quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'expiry_date' => 'date',
        'last_stock_in_date' => 'datetime',
        'last_stock_out_date' => 'datetime',
        'last_audit_date' => 'datetime',
    ];

    protected $appends = [
        'available_quantity',
        'total_value',
        'is_low_stock',
        'is_out_of_stock',
    ];

    // Relationships

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id', 'warehouse_id')
            ->where('product_id', $this->product_id)
            ->orderBy('created_at', 'desc');
    }

    // Accessors

    public function getAvailableQuantityAttribute($value): float
    {
        if ($value !== null) {
            return (float) $value;
        }

        return (float) $this->physical_quantity - (float) $this->reserved_quantity;
    }

    public function getTotalValueAttribute($value): float
    {
        if ($value !== null) {
            return (float) $value;
        }

        return (float) $this->physical_quantity * (float) $this->average_cost;
    }

    public function getUnitCostAttribute(): float
    {
        return $this->average_cost;
    }

    public function setUnitCostAttribute($value): void
    {
        $this->attributes['average_cost'] = $value;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->available_quantity <= $this->reorder_point && $this->available_quantity > 0;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_quantity <= 0;
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

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('(physical_quantity - reserved_quantity) <= reorder_point')
            ->whereRaw('(physical_quantity - reserved_quantity) > 0');
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(physical_quantity - reserved_quantity) <= 0');
    }

    public function scopeInStock($query)
    {
        return $query->whereRaw('(physical_quantity - reserved_quantity) > reorder_point');
    }

    // Helper Methods

    public function increaseStock(float $quantity, ?float $unitCost = null): bool
    {
        $this->physical_quantity += $quantity;

        if ($unitCost !== null) {
            // Update weighted average cost
            $totalValue = ($this->physical_quantity - $quantity) * $this->unit_cost + ($quantity * $unitCost);
            $this->unit_cost = $totalValue / $this->physical_quantity;
        }

        $this->last_stock_in_date = now();

        return $this->save();
    }

    public function decreaseStock(float $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            throw new \Exception('Insufficient stock available');
        }

        $this->physical_quantity -= $quantity;
        $this->last_stock_out_date = now();

        return $this->save();
    }

    public function reserveStock(float $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            throw new \Exception('Insufficient stock available for reservation');
        }

        $this->reserved_quantity += $quantity;

        return $this->save();
    }

    public function releaseReservedStock(float $quantity): bool
    {
        if ($this->reserved_quantity < $quantity) {
            throw new \Exception('Cannot release more than reserved quantity');
        }

        $this->reserved_quantity -= $quantity;

        return $this->save();
    }

    public function adjustStock(float $newQuantity, string $reason): bool
    {
        $this->physical_quantity = $newQuantity;
        $this->last_audit_date = now();

        return $this->save();
    }
}
