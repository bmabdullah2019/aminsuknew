<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VariantImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getImagePathAttribute($value): string
    {
        $normalized = trim(str_replace('\\', '/', (string) $value));
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, ['http://', 'https://', 'data:', 'storage/', 'public/'])) {
            return $normalized;
        }

        return 'storage/'.ltrim($normalized, '/');
    }
}
