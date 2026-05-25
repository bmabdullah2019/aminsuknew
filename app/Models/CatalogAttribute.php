<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_required',
        'status',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'status' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CatalogAttributeValue::class, 'catalog_attribute_id')
            ->orderBy('sort_order')
            ->orderBy('value');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
