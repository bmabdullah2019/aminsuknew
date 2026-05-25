<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'branch_id',
        'product_id',
        'variant_id',
        'available_qty',
        'reserved_qty',
        'sold_qty',
    ];

    protected $casts = [
        'available_qty' => 'decimal:2',
        'reserved_qty' => 'decimal:2',
        'sold_qty' => 'decimal:2',
    ];

    protected $appends = [
        'total_qty',
        'on_hold_qty',
        'stock_status',
        'stock_status_color',
    ];

    protected static function booted(): void
    {
        static::creating(function (Stock $stock): void {
            if (! self::hasBranchColumn()) {
                unset($stock->branch_id);

                return;
            }

            if (empty($stock->branch_id)) {
                if (Schema::hasTable('branches')) {
                    $stock->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $stock->branch_id = 1;
                }
            }
        });

        // Legacy `stocks.id` is often a plain INT PRIMARY KEY without AUTO_INCREMENT (manual ids).
        static::creating(function (Stock $stock): void {
            if (! empty($stock->id)) {
                return;
            }

            if (! self::stockIdRequiresManualValue()) {
                return;
            }

            $stock->id = self::nextStockPrimaryKey();
        });
    }

    // Relationships

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // Accessors

    public function getTotalQtyAttribute(): float
    {
        return $this->available_qty + $this->reserved_qty + $this->sold_qty;
    }

    public function getOnHoldQtyAttribute(): float
    {
        return $this->reserved_qty;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->available_qty <= 0) {
            return 'out_of_stock';
        }

        if ($this->available_qty <= 5) { // Low stock threshold
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

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeInStock($query)
    {
        return $query->where('available_qty', '>', 0);
    }

    public function scopeLowStock($query, $threshold = 5)
    {
        return $query->where('available_qty', '<=', $threshold)
            ->where('available_qty', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('available_qty', '<=', 0);
    }

    // Helper Methods

    public function reserveStock(float $quantity): bool
    {
        $quantity = $this->normalizeQuantity($quantity);
        if ($quantity <= 0) {
            return false;
        }

        $qtySql = $this->toSqlDecimal($quantity);

        $updated = static::query()
            ->whereKey($this->id)
            ->where('available_qty', '>=', $quantity)
            ->update([
                'available_qty' => DB::raw("available_qty - {$qtySql}"),
                'reserved_qty' => DB::raw("reserved_qty + {$qtySql}"),
            ]);

        if ($updated !== 1) {
            return false;
        }

        $this->refresh();

        return true;
    }

    public function releaseReservedStock(float $quantity): bool
    {
        $quantity = $this->normalizeQuantity($quantity);
        if ($quantity <= 0) {
            return false;
        }

        $qtySql = $this->toSqlDecimal($quantity);

        $updated = static::query()
            ->whereKey($this->id)
            ->where('reserved_qty', '>=', $quantity)
            ->update([
                'reserved_qty' => DB::raw("reserved_qty - {$qtySql}"),
                'available_qty' => DB::raw("available_qty + {$qtySql}"),
            ]);

        if ($updated !== 1) {
            return false;
        }

        $this->refresh();

        return true;
    }

    public function convertReservedToSold(float $quantity): bool
    {
        $quantity = $this->normalizeQuantity($quantity);
        if ($quantity <= 0) {
            return false;
        }

        $qtySql = $this->toSqlDecimal($quantity);

        $updated = static::query()
            ->whereKey($this->id)
            ->where('reserved_qty', '>=', $quantity)
            ->update([
                'reserved_qty' => DB::raw("reserved_qty - {$qtySql}"),
                'sold_qty' => DB::raw("sold_qty + {$qtySql}"),
            ]);

        if ($updated !== 1) {
            return false;
        }

        $this->refresh();

        return true;
    }

    public function processReturn(float $quantity, bool $resellable = true): bool
    {
        $quantity = $this->normalizeQuantity($quantity);
        if ($quantity <= 0) {
            return false;
        }

        $qtySql = $this->toSqlDecimal($quantity);

        if ($resellable) {
            $updated = static::query()
                ->whereKey($this->id)
                ->where('sold_qty', '>=', $quantity)
                ->update([
                    'sold_qty' => DB::raw("sold_qty - {$qtySql}"),
                    'available_qty' => DB::raw("available_qty + {$qtySql}"),
                ]);

            if ($updated !== 1) {
                return false;
            }

            $this->refresh();

            return true;
        }

        $updated = static::query()
            ->whereKey($this->id)
            ->where('sold_qty', '>=', $quantity)
            ->update([
                'sold_qty' => DB::raw("sold_qty - {$qtySql}"),
            ]);

        if ($updated !== 1) {
            return false;
        }

        $this->refresh();

        return true;
    }

    public function adjustStock(float $quantity): bool
    {
        $quantity = round($quantity, 2);
        if ($quantity === 0.0) {
            return true;
        }

        if ($quantity > 0) {
            $qtySql = $this->toSqlDecimal($quantity);
            $updated = static::query()
                ->whereKey($this->id)
                ->update([
                    'available_qty' => DB::raw("available_qty + {$qtySql}"),
                ]);

            if ($updated !== 1) {
                return false;
            }

            $this->refresh();

            return true;
        }

        $absQty = abs($quantity);
        $qtySql = $this->toSqlDecimal($absQty);
        $updated = static::query()
            ->whereKey($this->id)
            ->where('available_qty', '>=', $absQty)
            ->update([
                'available_qty' => DB::raw("available_qty - {$qtySql}"),
            ]);

        if ($updated !== 1) {
            return false;
        }

        $this->refresh();

        return true;
    }

    public function getDisplayName(): string
    {
        $name = $this->product->name;

        if ($this->variant) {
            $variantParts = array_filter([$this->variant->color, $this->variant->size, $this->variant->age]);
            if (! empty($variantParts)) {
                $name .= ' ('.implode(' / ', $variantParts).')';
            }
        }

        return $name;
    }

    private function normalizeQuantity(float $quantity): float
    {
        return max(0, round($quantity, 2));
    }

    private function toSqlDecimal(float $quantity): string
    {
        return number_format($quantity, 2, '.', '');
    }

    private static function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $hasBranchColumn = Schema::hasColumn((new self)->getTable(), 'branch_id');
        }

        return $hasBranchColumn;
    }

    private static function stockIdRequiresManualValue(): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        $table = (new self)->getTable();
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            return false;
        }

        try {
            $column = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE 'id'");

            return $column && ! str_contains(strtolower((string) ($column->Extra ?? '')), 'auto_increment');
        } catch (\Throwable) {
            return false;
        }
    }

    private static function nextStockPrimaryKey(): int
    {
        $table = (new self)->getTable();

        return ((int) DB::table($table)->max('id')) + 1;
    }
}
