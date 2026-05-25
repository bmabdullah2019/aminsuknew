<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'phone',
        'purpose',
        'otp_hash',
        'attempts_count',
        'resend_count',
        'last_sent_at',
        'expires_at',
        'verified_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'attempts_count' => 'integer',
        'resend_count' => 'integer',
        'last_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
