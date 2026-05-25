<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingZoneArea extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    /**
     * Bridge to legacy shipping_charges for backward compatibility.
     */
    public function shippingCharge()
    {
        return $this->belongsTo(ShippingCharge::class, 'shipping_charge_id');
    }
}
