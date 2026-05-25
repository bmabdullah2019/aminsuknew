<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierSettlementOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_settlement_id',
        'order_id',
        'consignment_id',
        'tracking_code',
        'invoice_id',
        'cod_amount',
        'delivery_charge',
        'return_charge',
        'adjustment_amount',
        'net_amount',
        'delivery_status',
        'raw_payload',
    ];

    protected $casts = [
        'cod_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'return_charge' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CourierSettlement::class, 'courier_settlement_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
