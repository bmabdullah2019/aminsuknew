<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Services\StockEngine;
use Illuminate\Support\Facades\Log;

class ReleaseStockOnOrderCancelled
{
    public function __construct(
        private readonly StockEngine $stockEngine
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;
        $this->stockEngine->releaseForOrder($order);

        Log::info('Stock released for cancelled order', [
            'order_id' => $order->id,
            'items' => (int) ($order->relationLoaded('orderdetails') ? $order->orderdetails->count() : 0),
        ]);
    }
}
