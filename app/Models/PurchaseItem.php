<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Schema;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'branch_id',
        'product_variant_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected $appends = [
        'remaining_quantity',
        'is_fully_received',
        'received_percentage',
    ];

    // Relationships

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_order_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(
            Product::class,
            ProductVariant::class,
            'id',
            'id',
            'product_variant_id',
            'product_id'
        );
    }

    public function supplierReturnItems(): HasMany
    {
        return $this->hasMany(SupplierPurchaseReturnItem::class);
    }

    // Accessors

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    public function getReceivedPercentageAttribute(): float
    {
        if ($this->quantity_ordered <= 0) {
            return 0;
        }

        return min(100, ($this->quantity_received / $this->quantity_ordered) * 100);
    }

    public function getLineTotalAttribute($value): float
    {
        // Calculate line total if not stored
        if ($value === null) {
            return $this->quantity_ordered * $this->unit_cost;
        }

        return (float) $value;
    }

    // Helper Methods

    public function receiveQuantity(float $quantity, ?float $unitCost = null): bool
    {
        if ($quantity <= 0) {
            throw new \Exception('Received quantity must be greater than zero');
        }

        if ($quantity > $this->remaining_quantity) {
            throw new \Exception('Cannot receive more than remaining quantity');
        }

        $this->quantity_received += $quantity;

        if ($unitCost !== null) {
            $this->unit_cost = $unitCost;
        }

        // Recalculate line total
        $this->line_total = $this->quantity_ordered * $this->unit_cost;

        return $this->save();
    }

    public function canReceive(): bool
    {
        return $this->remaining_quantity > 0;
    }

    public function getProgressColor(): string
    {
        $percentage = $this->received_percentage;

        if ($percentage >= 100) {
            return 'success';
        } elseif ($percentage >= 50) {
            return 'warning';
        } elseif ($percentage > 0) {
            return 'info';
        } else {
            return 'secondary';
        }
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Ensure quantities are not negative
            $item->quantity_ordered = max(0, $item->quantity_ordered);
            $item->quantity_received = max(0, $item->quantity_received);

            if (! self::hasBranchColumn()) {
                unset($item->branch_id);
            } elseif (empty($item->branch_id) && ! empty($item->purchase_order_id)) {
                $item->branch_id = PurchaseOrder::query()
                    ->whereKey((int) $item->purchase_order_id)
                    ->value('branch_id');
            }

            // Ensure received doesn't exceed ordered
            if ($item->quantity_received > $item->quantity_ordered) {
                $item->quantity_received = $item->quantity_ordered;
            }

            // Calculate line total
            $item->line_total = $item->quantity_ordered * $item->unit_cost;
        });
    }

    private static function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $hasBranchColumn = Schema::hasColumn((new self)->getTable(), 'branch_id');
        }

        return $hasBranchColumn;
    }
}
