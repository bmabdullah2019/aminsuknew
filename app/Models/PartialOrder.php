<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartialOrder extends Model
{
    protected $table = 'partial_orders';

    protected $fillable = [
        'device_id',
        'status',
        'products',
        'name',
        'phone',
        'address',
        'meta',
    ];

    protected $casts = [
        'products' => 'array',
        'meta' => 'array',
    ];
}
