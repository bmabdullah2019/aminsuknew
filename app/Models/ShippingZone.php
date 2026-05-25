<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function areas()
    {
        return $this->hasMany(ShippingZoneArea::class);
    }

    public function rates()
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Get display name with area count.
     */
    public function getDisplayNameAttribute(): string
    {
        $areaCount = $this->areas_count ?? $this->areas()->count();

        return $this->name . " ({$areaCount} areas)";
    }
}
