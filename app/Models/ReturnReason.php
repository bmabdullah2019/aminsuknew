<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'reason_code',
        'reason_name',
        'reason_category',
        'requires_approval',
        'auto_restock',
        'refund_eligible',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'auto_restock' => 'boolean',
        'refund_eligible' => 'boolean',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function returnOrders(): HasMany
    {
        return $this->hasMany(ReturnOrder::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('reason_category', $category);
    }

    public function scopeRefundEligible($query)
    {
        return $query->where('refund_eligible', true);
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    public function scopeOrderBySort($query)
    {
        return $query->orderBy('sort_order')->orderBy('reason_name');
    }

    // Accessors
    public function getCategoryColorAttribute(): string
    {
        return match ($this->reason_category) {
            'customer' => 'primary',
            'product' => 'warning',
            'shipping' => 'info',
            'other' => 'secondary',
            default => 'secondary',
        };
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->reason_code} - {$this->reason_name}";
    }

    // Methods
    public function getUsageCount(): int
    {
        return $this->returnOrders()->count();
    }

    public function isUsed(): bool
    {
        return $this->getUsageCount() > 0;
    }

    public static function getActiveReasons()
    {
        return static::active()->orderBySort()->get();
    }

    public static function getReasonsByCategory()
    {
        return static::active()
            ->orderBy('reason_category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('reason_category');
    }
}
