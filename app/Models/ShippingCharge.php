<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCharge extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Boot the model's Eloquent lifecycle hooks.
     * Automatically sync amount_minor when amount is modified.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-sync amount_minor to ensure data consistency
            // amount_minor stores the value in cents (amount * 100)
            if ($model->isDirty('amount') || ! $model->amount_minor) {
                $model->amount_minor = (int) round(((float) $model->amount ?? 0) * 100);
            }
        });
    }

    /**
     * Get the shipping area display name.
     */
    public function getDisplayNameAttribute()
    {
        return $this->name.' - BDT '.number_format((float) $this->amount, 2);
    }
}
