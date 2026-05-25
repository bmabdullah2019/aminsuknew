<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku_code',
        'combination_key',
        'color',
        'size',
        'age',
        'price',
        'stock',
        'cost_price',
        'barcode',
        'image',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    protected $appends = [
        'sellable_stock',
        'total_stock',
        'is_low_stock',
        'is_out_of_stock',
    ];

    // Relationships

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetails::class, 'product_variant_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'variant_id');
    }

    public function variantImages(): HasMany
    {
        return $this->hasMany(VariantImage::class, 'product_variant_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function primaryVariantImage(): HasOne
    {
        return $this->hasOne(VariantImage::class, 'product_variant_id')
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class, 'product_variant_id')
            ->with(['attribute:id,name,slug,sort_order,status', 'value:id,catalog_attribute_id,value,slug,meta,sort_order,status']);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogAttributeValue::class,
            'product_variant_attribute_values',
            'product_variant_id',
            'catalog_attribute_value_id'
        )->withPivot(['catalog_attribute_id'])->withTimestamps();
    }

    // Accessors

    public function getSellableStockAttribute(): float
    {
        return $this->inventories->sum(function ($inventory) {
            return $inventory->quantity_available - $inventory->quantity_reserved;
        });
    }

    public function getTotalStockAttribute(): float
    {
        return $this->inventories->sum('quantity_available');
    }

    public function getReservedStockAttribute(): float
    {
        return $this->inventories->sum('quantity_reserved');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->sellable_stock <= $this->getLowestReorderLevel();
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

    // Scopes

    public function scopeActive($query)
    {
        return $query->where(function ($innerQuery) {
            $innerQuery->where('status', 'active')
                ->orWhere('status', 1)
                ->orWhere('status', '1');
        });
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('inventories', function ($q) {
            $q->whereRaw('(quantity_available - quantity_reserved) > 0');
        });
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('inventories', function ($q) {
            $q->whereRaw('(quantity_available - quantity_reserved) <= reorder_level')
                ->whereRaw('(quantity_available - quantity_reserved) > 0');
        });
    }

    public function scopeOutOfStock($query)
    {
        return $query->where(function ($q) {
            $q->whereDoesntHave('inventories')
                ->orWhereHas('inventories', function ($sq) {
                    $sq->whereRaw('(quantity_available - quantity_reserved) <= 0');
                });
        });
    }

    // Helper Methods

    /**
     * Human readable display name for this variant.
     *
     * Used in places where we want a stable label in exports/UI.
     */
    public function getDisplayName(): string
    {
        // Prefer attribute values when loaded (more accurate for dynamic attributes)
        if (! $this->relationLoaded('variantAttributeValues')) {
            $this->loadMissing('variantAttributeValues.attribute', 'variantAttributeValues.value');
        }

        $parts = [];

        if ($this->relationLoaded('variantAttributeValues') && $this->variantAttributeValues->isNotEmpty()) {
            foreach ($this->variantAttributeValues as $row) {
                $attrName = trim((string) ($row->attribute->name ?? ''));
                $val = trim((string) ($row->value->value ?? ($row->value->value ?? $row->value->value ?? '')));

                if ($val === '') {
                    continue;
                }

                $parts[] = $attrName !== ''
                    ? ($attrName.': '.$val)
                    : $val;
            }
        }

        // Fallback to legacy columns
        if (empty($parts)) {
            $legacy = array_filter([
                $this->color ? 'Color: '.trim((string) $this->color) : null,
                $this->size ? 'Size: '.trim((string) $this->size) : null,
                $this->age ? 'Age: '.trim((string) $this->age) : null,
            ], fn ($v) => ! empty($v));

            $parts = array_values($legacy);
        }

        return ! empty($parts)
            ? implode(' | ', $parts)
            : (string) ($this->sku_code ?? $this->id ?? '');
    }

    public function getInventoryForWarehouse(int $warehouseId): ?Inventory
    {
        return $this->inventories()->where('warehouse_id', $warehouseId)->first();
    }

    public function getLowestReorderLevel(): float
    {
        return $this->inventories->min('reorder_level') ?? 5;
    }

    public function generateSku(): string
    {
        $productCode = $this->product->product_code ?? $this->product->id;
        $variantParts = array_filter([$this->color, $this->size, $this->age]);

        if (empty($variantParts) && $this->relationLoaded('variantAttributeValues')) {
            $variantParts = $this->variantAttributeValues
                ->sortBy(fn ($row) => (int) ($row->attribute->sort_order ?? 0))
                ->map(fn ($row) => trim((string) ($row->value->value ?? '')))
                ->filter()
                ->values()
                ->all();
        }

        $variantSuffix = ! empty($variantParts) ? '-'.implode('-', $variantParts) : '';

        return strtoupper($productCode.$variantSuffix);
    }

    public function dynamicAttributeMap(): array
    {
        if (! $this->relationLoaded('variantAttributeValues')) {
            $this->load('variantAttributeValues.attribute', 'variantAttributeValues.value');
        }

        $map = [];
        foreach ($this->variantAttributeValues as $row) {
            $slug = Str::lower(trim((string) ($row->attribute->slug ?? '')));
            $value = trim((string) ($row->value->value ?? ''));
            if ($slug === '' || $value === '') {
                continue;
            }
            $map[$slug] = $value;
        }

        if (! isset($map['color']) && trim((string) $this->color) !== '') {
            $map['color'] = trim((string) $this->color);
        }
        if (! isset($map['size']) && trim((string) $this->size) !== '') {
            $map['size'] = trim((string) $this->size);
        }
        if (! isset($map['age']) && trim((string) $this->age) !== '') {
            $map['age'] = trim((string) $this->age);
        }

        return $map;
    }

    public function dynamicAttributeCollection(): Collection
    {
        if (! $this->relationLoaded('variantAttributeValues')) {
            $this->load('variantAttributeValues.attribute', 'variantAttributeValues.value');
        }

        return $this->variantAttributeValues
            ->filter(function ($row) {
                return ! empty($row->attribute?->slug) && ! empty($row->value?->value);
            })
            ->sortBy(function ($row) {
                return [
                    (int) ($row->attribute->sort_order ?? 0),
                    (string) ($row->attribute->name ?? ''),
                ];
            })
            ->values();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($variant) {
            if (empty($variant->sku_code)) {
                $variant->sku_code = $variant->generateSku();
            }
        });
    }
}
