<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_type',
        'courier_payment_id',
        'settlement_date',
        'gross_cod_amount',
        'delivery_charge',
        'return_charge',
        'adjustment_amount',
        'net_receivable_amount',
        'received_amount',
        'status',
        'raw_payload',
        'synced_at',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'gross_cod_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'return_charge' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'net_receivable_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(CourierSettlementOrder::class);
    }
}
