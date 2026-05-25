<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Services\PurchaseTrackingService;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Log;

class ProcessOrderPurchaseTracking
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected PurchaseTrackingService $trackingService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;
        $newStatusId = $event->newStatusId;

        $status = OrderStatus::find($newStatusId);
        if (!$status) {
            Log::warning('OrderStatusUpdated fired but status ID could not be resolved', [
                'status_id' => $newStatusId,
                'order_id' => $order->id,
            ]);
            return;
        }

        // Check if the order status is Confirmed
        $isConfirmed = $newStatusId === 2
            || strtolower((string) $status->slug) === 'confirmed'
            || strtolower((string) $status->name) === 'confirmed';

        if ($isConfirmed) {
            $this->trackingService->dispatchTracking($order);
        }
    }
}
