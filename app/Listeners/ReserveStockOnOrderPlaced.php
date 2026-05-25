<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\StockEngine;
use Illuminate\Support\Facades\Log;

class ReserveStockOnOrderPlaced
{
    public function __construct(
        private readonly StockEngine $stockEngine
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $this->stockEngine->reserveForOrder($order);

        Log::info('Stock reserved for order', [
            'order_id' => $order->id,
            'items' => (int) ($order->relationLoaded('orderdetails') ? $order->orderdetails->count() : 0),
        ]);
    }
}
