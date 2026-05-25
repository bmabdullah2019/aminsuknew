<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function rates()
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'shipping_profile_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Get display name with status badge.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->is_default ? ' (Default)' : '');
    }
}
