<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'domain',
        'license_key',
        'status',
        'expires_at',
        'last_checked_at',
    ];

    protected $casts = [
        // Encrypt at rest in DB (Laravel built-in encrypted cast).
        'license_key' => 'encrypted',
        'expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];
}
