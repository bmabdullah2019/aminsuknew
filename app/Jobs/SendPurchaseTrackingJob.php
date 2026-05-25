<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Tracking\Ga4MeasurementProtocolClient;
use App\Services\Tracking\MetaCapiClient;
use App\Services\Tracking\TikTokEventsApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendPurchaseTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $orderId
    ) {}

    /**
     * Get the number of times the job can be attempted.
     */
    public function tries(): int
    {
        return (int) config('tracking.retry.max_retries', 3);
    }

    /**
     * Get the number of seconds to wait before retrying the job.
     */
    public function backoff(): int
    {
        return (int) config('tracking.retry.delay_seconds', 5);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Fetch Order and lock it for update in transaction
        $order = DB::transaction(function () {
            return Order::query()
                ->with(['orderItems', 'shipping', 'customer'])
                ->lockForUpdate()
                ->findOrFail($this->orderId);
        });

        // If already fully tracked, stop immediately
        if ($order->purchase_tracking_status === 'success') {
            Log::info('Order already successfully tracked. Skipping job.', [
                'order_id' => $this->orderId,
            ]);
            return;
        }

        $providerStatus = $order->tracking_provider_status ?? [];
        $failedProviders = [];

        // 2. Track GA4
        if (config('tracking.ga4.enabled')) {
            $ga4Status = $providerStatus['ga4'] ?? 'pending';
            if ($ga4Status !== 'success') {
                $ga4Client = app(Ga4MeasurementProtocolClient::class);
                if ($ga4Client->sendPurchase($order)) {
                    $providerStatus['ga4'] = 'success';
                } else {
                    $providerStatus['ga4'] = 'failed';
                    $failedProviders[] = 'ga4';
                }
            }
        }

        // 3. Track Facebook Conversions API
        if (config('tracking.facebook.enabled')) {
            $fbStatus = $providerStatus['facebook'] ?? 'pending';
            if ($fbStatus !== 'success') {
                $fbClient = app(MetaCapiClient::class);
                if ($fbClient->sendPurchase($order)) {
                    $providerStatus['facebook'] = 'success';
                } else {
                    $providerStatus['facebook'] = 'failed';
                    $failedProviders[] = 'facebook';
                }
            }
        }

        // 4. Track TikTok Events API
        if (config('tracking.tiktok.enabled')) {
            $tiktokStatus = $providerStatus['tiktok'] ?? 'pending';
            if ($tiktokStatus !== 'success') {
                $tiktokClient = app(TikTokEventsApiClient::class);
                if ($tiktokClient->sendPurchase($order)) {
                    $providerStatus['tiktok'] = 'success';
                } else {
                    $providerStatus['tiktok'] = 'failed';
                    $failedProviders[] = 'tiktok';
                }
            }
        }

        // 5. Update Order status based on results
        DB::transaction(function () use ($order, $providerStatus, $failedProviders) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($this->orderId);
            
            $lockedOrder->tracking_provider_status = $providerStatus;

            if (empty($failedProviders)) {
                $lockedOrder->purchase_tracking_status = 'success';
                $lockedOrder->purchase_tracked_at = now();
                $lockedOrder->purchase_pixel_fired_at = now(); // Legacy compatibility
            } else {
                $lockedOrder->purchase_tracking_status = 'failed';
            }

            $lockedOrder->save();
        });

        // 6. Throw exception if any failed to trigger retry with backoff
        if (!empty($failedProviders)) {
            $attempt = $this->attempts();
            $maxTries = $this->tries();
            
            Log::warning("Purchase tracking failed for providers: " . implode(', ', $failedProviders) . ". Attempt {$attempt}/{$maxTries}", [
                'order_id' => $this->orderId,
            ]);

            if ($attempt < $maxTries) {
                throw new \RuntimeException("Tracking failed for: " . implode(', ', $failedProviders) . ". Retrying...");
            }
        }
    }
}
