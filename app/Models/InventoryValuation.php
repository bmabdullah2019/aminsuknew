<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryValuation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'valuation_date',
        'quantity_on_hand',
        'unit_cost_fifo',
        'unit_cost_wac',
        'total_value_fifo',
        'total_value_wac',
        'cost_layers',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'quantity_on_hand' => 'decimal:2',
        'unit_cost_fifo' => 'decimal:2',
        'unit_cost_wac' => 'decimal:2',
        'total_value_fifo' => 'decimal:2',
        'total_value_wac' => 'decimal:2',
        'cost_layers' => 'array',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Scopes
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('valuation_date', $date);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('valuation_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getTotalValueAttribute(): float
    {
        // Return FIFO value as default, WAC as fallback
        return $this->total_value_fifo ?? $this->total_value_wac ?? 0;
    }

    public function getUnitCostAttribute(): float
    {
        // Return FIFO cost as default, WAC as fallback
        return $this->unit_cost_fifo ?? $this->unit_cost_wac ?? 0;
    }

    // Methods
    public static function getLatestValuation(int $productId, int $warehouseId, ?string $date = null): ?self
    {
        $date = $date ?? now()->toDateString();

        return static::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('valuation_date', '<=', $date)
            ->orderBy('valuation_date', 'desc')
            ->first();
    }

    public function calculateValues(?array $costLayers = null): void
    {
        if ($costLayers) {
            // FIFO calculation
            $this->cost_layers = $costLayers;
            $totalValue = 0;
            $weightedCost = 0;
            $totalQuantity = 0;

            foreach ($costLayers as $layer) {
                $layerValue = $layer['quantity'] * $layer['unit_cost'];
                $totalValue += $layerValue;
                $weightedCost += $layerValue;
                $totalQuantity += $layer['quantity'];
            }

            $this->total_value_fifo = $totalValue;
            $this->unit_cost_fifo = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0;

            // WAC calculation
            $this->unit_cost_wac = $totalQuantity > 0 ? $weightedCost / $totalQuantity : 0;
            $this->total_value_wac = $this->quantity_on_hand * $this->unit_cost_wac;
        }
    }
}
