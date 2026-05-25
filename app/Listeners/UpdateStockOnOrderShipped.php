<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Services\StockEngine;
use Illuminate\Support\Facades\Log;

class UpdateStockOnOrderShipped
{
    public function __construct(
        private readonly StockEngine $stockEngine
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderShipped $event): void
    {
        $order = $event->order;
        $this->stockEngine->deductForOrder($order);

        Log::info('Stock deducted for shipped order', [
            'order_id' => $order->id,
            'items' => (int) ($order->relationLoaded('orderdetails') ? $order->orderdetails->count() : 0),
        ]);
    }
}
