<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'rate_minor' => 'integer',
    ];

    /**
     * Boot: auto-sync rate_minor when rate is set.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->isDirty('rate') || ! $model->rate_minor) {
                $model->rate_minor = (int) round(((float) $model->rate ?? 0) * 100);
            }
        });
    }

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function profile()
    {
        return $this->belongsTo(ShippingProfile::class, 'shipping_profile_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Get human-readable display for admin lists.
     */
    public function getDisplaySummaryAttribute(): string
    {
        $zoneName = optional($this->zone)->name ?? '—';
        $profileName = optional($this->profile)->name ?? '—';

        return "{$zoneName} / {$profileName} / {$this->min_weight}-{$this->max_weight}kg = BDT {$this->rate}";
    }
}
