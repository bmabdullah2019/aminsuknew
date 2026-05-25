<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'gateway',
        'event_key',
        'gateway_payment_id',
        'order_id',
        'payload_hash',
        'signature_valid',
        'amount_minor_reported',
        'currency_reported',
        'payload',
        'status',
        'status_reason',
        'processed_at',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'amount_minor_reported' => 'integer',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
