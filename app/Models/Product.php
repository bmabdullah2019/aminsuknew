<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    /**
     * The legacy `products.stock` column was dropped in favor of per-warehouse stock.
     * Keep `$product->stock` working by deriving it from `warehouse_stock`.
     */
    public function getStockAttribute($value = null): float
    {
        // If the query already selected/aliased a `stock` value, prefer it (avoids N+1).
        if ($value !== null) {
            return (float) $value;
        }

        $warehouseId = Session::get('warehouse_id');
        if (! $warehouseId) {
            return 0;
        }

        $qty = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $this->id)
            ->value('available_quantity');

        return (float) ($qty ?? 0);
    }

    /**
     * Total available quantity across warehouses.
     */
    public function getAvailableStockAttribute(): float
    {
        if ($this->relationLoaded('warehouseStocks')) {
            return (float) $this->warehouseStocks->sum('available_quantity');
        }

        return (float) $this->warehouseStocks()->sum('available_quantity');
    }

    /**
     * Convenience accessor for storefront stock-out checks.
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_stock <= 0;
    }

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'has_variant' => 'boolean',
        'is_physical' => 'boolean',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    protected $appends = ['stock'];

    /**
     * ⚡ 4. PERFORMANCE OPTIMIZATION
     * Add missing database indexes for better query performance
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scopes for performance
        if (! app()->runningInConsole()) {
            static::addGlobalScope('active', function ($builder) {
                // Only apply in frontend contexts, not admin
                if (! request()->is('admin/*') && ! request()->is('api/*')) {
                    $builder->where('status', 1);
                }
            });
        }

        // Cache frequently accessed data
        static::created(function ($product) {
            Cache::forget('categories_with_product_count');
            Cache::forget('top_searched_products');
        });

        static::updated(function ($product) {
            Cache::forget('categories_with_product_count');
            Cache::forget('top_searched_products');
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function image()
    {
        return $this->hasOne(Productimage::class, 'product_id')
            ->select('id', 'image', 'webp_image', 'alt_text', 'sort_order', 'product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function images()
    {
        return $this->hasMany(Productimage::class, 'product_id')
            ->select('id', 'image', 'webp_image', 'alt_text', 'sort_order', 'product_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getDisplayImageAttribute(): string
    {
        $fallback = 'public/frontEnd/images/no-image.jpg';
        $image = $this->relationLoaded('image') ? $this->getRelation('image') : $this->image;

        $candidates = [
            $this->thumbnail ?? null,
            $image?->getRawOriginal('image'),
            $image?->image,
            $image?->getRawOriginal('webp_image'),
            $image?->webp_image,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeFrontendImagePath((string) $candidate);
            if ($normalized === '') {
                continue;
            }

            if (Str::startsWith($normalized, ['http://', 'https://', 'data:']) || is_file(base_path($normalized))) {
                return $normalized;
            }
        }

        return $fallback;
    }

    protected function normalizeFrontendImagePath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, ['http://', 'https://', 'data:'])) {
            return $normalized;
        }

        if (Str::startsWith($normalized, 'public/')) {
            return $normalized;
        }

        if (Str::startsWith($normalized, 'storage/')) {
            return 'public/' . $normalized;
        }

        if (Str::startsWith($normalized, 'uploads/')) {
            return 'public/' . $normalized;
        }

        return 'public/storage/' . $normalized;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id')->select('id');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id')->select('id', 'name', 'slug');
    }

    public function subcategory()
    {
        return $this->hasOne(Subcategory::class, 'id', 'subcategory_id')->select('id', 'subcategoryName', 'slug');
    }

    public function childcategory()
    {
        return $this->hasOne(Childcategory::class, 'id', 'childcategory_id')->select('id', 'childcategoryName', 'slug');
    }

    public function brand()
    {
        return $this->hasOne(Brand::class, 'id', 'brand_id')->select('id', 'name', 'slug');
    }

    public function sizes()
    {
        return $this->belongsToMany('App\Models\Size', 'productsizes')->withTimestamps();
    }

    public function ages()
    {
        return $this->belongsToMany('App\Models\Age', 'productages')->withTimestamps();
    }

    public function colors()
    {
        return $this->belongsToMany('App\Models\Color', 'productcolors')->withTimestamps();
    }

    public function prosizes()
    {
        return $this->hasMany('App\Models\Productsize');
    }

    public function procolors()
    {
        return $this->hasMany('App\Models\Productcolor');
    }

    public function prosize()
    {
        return $this->hasOne(Productsize::class, 'product_id');
    }

    public function procolor()
    {
        return $this->hasOne(Productcolor::class, 'product_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetails::class, 'product_id');
    }

    public function warehouseStocks()
    {
        // Always return product-level warehouse stocks (product_variant_id IS NULL)
        // This ensures consistency across variant and non-variant products
        return $this->hasMany(WarehouseStock::class, 'product_id')
            ->whereNull('product_variant_id');
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variantImages(): HasManyThrough
    {
        return $this->hasManyThrough(
            VariantImage::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
            'id',
            'id'
        )->orderBy('sort_order')->orderBy('id');
    }

    public function activeVariants()
    {
        return $this->productVariants()->active();
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ─── Shipping Module ────────────────────────────────────────────────

    public function shippingProfile()
    {
        return $this->belongsTo(ShippingProfile::class, 'shipping_profile_id');
    }

    public function isDigital(): bool
    {
        return $this->shipping_type === 'digital';
    }

    /**
     * Determine if this product is digital (no shipping required).
     */
    public function getIsDigitalAttribute(): bool
    {
        return $this->isDigital();
    }

    public function getShippingTypeLabel(): string
    {
        return $this->shipping_type_label;
    }

    /**
     * Human-readable shipping type label for admin UI.
     */
    public function getShippingTypeLabelAttribute(): string
    {
        return match ($this->shipping_type) {
            'weight_based'  => 'Weight Based',
            'fixed_rate'    => 'Fixed Rate',
            'free_shipping' => 'Free Shipping',
            'digital'       => 'Digital (No Shipping)',
            default         => 'Default (Legacy)',
        };
    }
}
