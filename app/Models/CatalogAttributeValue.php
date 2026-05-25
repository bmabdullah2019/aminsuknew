<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalog_attribute_id',
        'value',
        'slug',
        'meta',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'meta' => 'array',
        'status' => 'boolean',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(CatalogAttribute::class, 'catalog_attribute_id');
    }

    public function variantLinks(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class, 'catalog_attribute_value_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
