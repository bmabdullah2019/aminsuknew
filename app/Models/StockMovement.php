<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'balance_after',
        'batch_number',
        'expiry_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'total_cost',
        'movement_direction',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            if (! self::hasBranchColumn()) {
                unset($movement->branch_id);
            } elseif (! $movement->branch_id && $movement->warehouse_id) {
                $movement->branch_id = (int) (Warehouse::query()
                    ->whereKey((int) $movement->warehouse_id)
                    ->value('branch_id')
                    ?? 0);
            }

            // In many flows (e.g. customer checkout), there is no authenticated users guard.
            // Leave created_by null in that case; the DB column is nullable.
            if (! $movement->created_by && auth()->check()) {
                $movement->created_by = auth()->id();
            }
        });

        static::created(function (self $movement): void {
            if (! function_exists('activity')) {
                return;
            }

            try {
                $logger = activity('inventory');
                if (auth()->check()) {
                    $logger->causedBy(auth()->user());
                }

                $logger->performedOn($movement)
                    ->withProperties([
                        'movement_id' => (int) $movement->id,
                        'warehouse_id' => (int) $movement->warehouse_id,
                        'product_id' => (int) $movement->product_id,
                        'product_variant_id' => $movement->product_variant_id ? (int) $movement->product_variant_id : null,
                        'type' => (string) $movement->type,
                        'reference_type' => (string) ($movement->reference_type ?? ''),
                        'reference_id' => $movement->reference_id ? (int) $movement->reference_id : null,
                        'quantity' => (float) $movement->quantity,
                        'unit_cost' => (float) ($movement->unit_cost ?? 0),
                        'balance_after' => (float) ($movement->balance_after ?? 0),
                    ])
                    ->log('stock_movement_recorded');
            } catch (\Throwable $exception) {
                report($exception);
            }
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
        return $this->belongsTo(ProductVariant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors

    public function getTotalCostAttribute(): float
    {
        return abs($this->quantity) * $this->unit_cost;
    }

    public function getMovementDirectionAttribute(): string
    {
        return $this->quantity > 0 ? 'in' : 'out';
    }

    public function getFormattedQuantityAttribute(): string
    {
        return ($this->quantity > 0 ? '+' : '').number_format($this->quantity, 2);
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Static Methods

    public static function recordStockIn(array $data): self
    {
        return self::create([
            'warehouse_id' => $data['warehouse_id'],
            'product_id' => $data['product_id'] ?? null,
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'type' => $data['type'] ?? 'grn',
            'reference_type' => $data['reference_type'] ?? 'grn',
            'reference_id' => $data['reference_id'] ?? null,
            'quantity' => abs($data['quantity']),
            'unit_cost' => $data['unit_cost'] ?? null,
            'balance_after' => $data['balance_after'] ?? null,
            'batch_number' => $data['batch_number'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    public static function recordStockOut(array $data): self
    {
        return self::create([
            'warehouse_id' => $data['warehouse_id'],
            'product_id' => $data['product_id'],
            'type' => $data['type'] ?? 'sale',
            'reference_type' => $data['reference_type'] ?? 'order',
            'reference_id' => $data['reference_id'] ?? null,
            'quantity' => -abs($data['quantity']),
            'unit_cost' => $data['unit_cost'] ?? null,
            'balance_after' => $data['balance_after'] ?? null,
            'batch_number' => $data['batch_number'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    public static function recordTransfer(array $data): self
    {
        $direction = $data['direction'] ?? 'out';
        $isIn = $direction === 'in';

        return self::create([
            'warehouse_id' => $data['warehouse_id'],
            'product_id' => $data['product_id'],
            'type' => $isIn ? 'transfer_in' : 'transfer_out',
            'reference_type' => $data['reference_type'] ?? 'warehouse_transfer',
            'reference_id' => $data['reference_id'] ?? null,
            'quantity' => $isIn ? abs($data['quantity']) : -abs($data['quantity']),
            'unit_cost' => $data['unit_cost'] ?? null,
            'balance_after' => $data['balance_after'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    public static function recordAdjustment(array $data): self
    {
        $quantity = (float) $data['quantity'];

        return self::create([
            'warehouse_id' => $data['warehouse_id'],
            'product_id' => $data['product_id'],
            'type' => $quantity > 0 ? 'adjustment_in' : 'adjustment_out',
            'reference_type' => $data['reference_type'] ?? 'stock_adjustment',
            'reference_id' => $data['reference_id'] ?? null,
            'quantity' => $quantity,
            'unit_cost' => $data['unit_cost'] ?? null,
            'balance_after' => $data['balance_after'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }
}
