<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'normalized_phone',
        'cancel_count',
        'is_active',
        'blocked_source',
        'blocked_by_order_id',
        'reason',
        'blocked_at',
    ];

    protected $casts = [
        'cancel_count' => 'integer',
        'is_active' => 'boolean',
        'blocked_by_order_id' => 'integer',
        'blocked_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
