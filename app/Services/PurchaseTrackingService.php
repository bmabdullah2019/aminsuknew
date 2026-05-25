<?php

namespace App\Services;

use App\Models\Order;
use App\Jobs\SendPurchaseTrackingJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseTrackingService
{
    /**
     * Dispatch purchase tracking for a confirmed order.
     * Uses atomic database transactions and pessimistic locking to prevent race conditions.
     */
    public function dispatchTracking(Order $order): bool
    {
        try {
            return DB::transaction(function () use ($order) {
                // Fetch the order with a pessimistic lock
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Prevent duplicate tracking triggers completely
                if ($lockedOrder->purchase_tracking_status === 'success') {
                    Log::debug('Purchase tracking already completed for order', [
                        'order_id' => $lockedOrder->id,
                    ]);
                    return false;
                }

                if ($lockedOrder->purchase_tracking_status === 'queued') {
                    Log::debug('Purchase tracking already queued for order', [
                        'order_id' => $lockedOrder->id,
                    ]);
                    return false;
                }

                // Update status to 'queued' to lock the state
                $lockedOrder->purchase_tracking_status = 'queued';
                $lockedOrder->save();

                // Dispatch the queue job
                $queueConfig = config('tracking.queue');
                SendPurchaseTrackingJob::dispatch($lockedOrder->id)
                    ->onConnection($queueConfig['connection'])
                    ->onQueue($queueConfig['queue']);

                Log::info('Purchase tracking job dispatched for order', [
                    'order_id' => $lockedOrder->id,
                    'invoice_id' => $lockedOrder->invoice_id,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch purchase tracking', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark an order as tracked via client-side fallback.
     */
    public function markAsClientTracked(Order $order): bool
    {
        try {
            return DB::transaction(function () use ($order) {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $providerStatus = $lockedOrder->tracking_provider_status ?? [];
                if (! is_array($providerStatus)) {
                    $providerStatus = [];
                }

                if (($providerStatus['gtm_browser_purchase'] ?? null) === 'success') {
                    return false;
                }

                $providerStatus['gtm_browser_purchase'] = 'success';
                $providerStatus['gtm_browser_purchase_at'] = now()->toIso8601String();
                $lockedOrder->tracking_provider_status = $providerStatus;
                $lockedOrder->save();

                Log::info('Order marked as tracked via GTM browser-side purchase event', [
                    'order_id' => $lockedOrder->id,
                    'invoice_id' => $lockedOrder->invoice_id,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to mark order as client tracked', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
