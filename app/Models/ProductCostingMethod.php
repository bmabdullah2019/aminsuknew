<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCostingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'costing_method',
        'is_default',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('costing_method', $method);
    }

    public function scopeEffective($query, $date = null)
    {
        $date = $date ?? now()->toDateString();

        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        $today = now()->toDateString();

        return $this->effective_from <= $today &&
               (is_null($this->effective_to) || $this->effective_to >= $today);
    }

    public function getCostingMethodNameAttribute(): string
    {
        return match ($this->costing_method) {
            'fifo' => 'First In, First Out (FIFO)',
            'weighted_average' => 'Weighted Average Cost (WAC)',
            'lifo' => 'Last In, First Out (LIFO)',
            'specific_identification' => 'Specific Identification',
            default => ucfirst(str_replace('_', ' ', $this->costing_method)),
        };
    }

    // Methods
    public static function getDefaultForProduct(int $productId, ?string $date = null): ?self
    {
        return static::where('product_id', $productId)
            ->default()
            ->effective($date)
            ->first();
    }

    public static function setDefaultMethod(int $productId, string $method, ?string $effectiveFrom = null): self
    {
        // Remove existing default
        static::where('product_id', $productId)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        return static::create([
            'product_id' => $productId,
            'costing_method' => $method,
            'is_default' => true,
            'effective_from' => $effectiveFrom ?? now()->toDateString(),
        ]);
    }
}
