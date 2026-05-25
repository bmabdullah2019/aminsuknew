<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class Ga4PurchaseTrackingService
{
    public function sendPurchaseIfEligible(Order $order): bool
    {
        if (! $this->isConfigured()) {
            Log::info('GA4 purchase tracking skipped because credentials are not configured.', [
                'order_id' => (int) $order->id,
            ]);

            return false;
        }

        if (! $this->isOrderEligible($order)) {
            return false;
        }

        if (! Schema::hasColumn('orders', 'purchase_pixel_fired_at')) {
            Log::warning('GA4 purchase tracking skipped because orders.purchase_pixel_fired_at is missing.', [
                'order_id' => (int) $order->id,
            ]);

            return false;
        }

        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()
                ->with(['orderdetails', 'status'])
                ->whereKey((int) $order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder || ! empty($lockedOrder->purchase_pixel_fired_at)) {
                return false;
            }

            if (! $this->isOrderEligible($lockedOrder)) {
                return false;
            }

            if (! $this->sendPurchase($lockedOrder)) {
                return false;
            }

            $lockedOrder->purchase_pixel_fired_at = now();
            $lockedOrder->save();

            return true;
        });
    }

    private function isConfigured(): bool
    {
        return trim((string) config('services.ga4.measurement_id')) !== ''
            && trim((string) config('services.ga4.api_secret')) !== '';
    }

    private function sendPurchase(Order $order): bool
    {
        $measurementId = trim((string) config('services.ga4.measurement_id'));
        $apiSecret = trim((string) config('services.ga4.api_secret'));
        $endpoint = rtrim((string) config('services.ga4.endpoint'), '/');
        $timeout = max(1, (int) config('services.ga4.timeout_seconds', 5));

        $url = $endpoint.'?measurement_id='.rawurlencode($measurementId).'&api_secret='.rawurlencode($apiSecret);

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout($timeout)
                ->post($url, $this->buildPayload($order));

            if ($response->successful()) {
                return true;
            }

            Log::warning('GA4 purchase tracking failed with non-success response.', [
                'order_id' => (int) $order->id,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);
        } catch (Throwable $exception) {
            Log::warning('GA4 purchase tracking request failed.', [
                'order_id' => (int) $order->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPayload(Order $order): array
    {
        $order->loadMissing('orderdetails');

        return [
            'client_id' => $this->clientIdForOrder($order),
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'transaction_id' => (string) ($order->invoice_id ?: $order->id),
                        'value' => round((float) $order->amount, 2),
                        'tax' => 0,
                        'shipping' => round((float) ($order->shipping_charge ?? 0), 2),
                        'currency' => strtoupper((string) ($order->currency ?: 'BDT')),
                        'items' => $order->orderdetails->map(function ($item): array {
                            return [
                                'item_id' => (string) $item->product_id,
                                'item_name' => (string) ($item->product_name ?: 'Product '.$item->product_id),
                                'price' => round((float) $item->sale_price, 2),
                                'quantity' => (int) $item->qty,
                                'item_variant' => trim((string) (($item->product_color ? $item->product_color.' ' : '').($item->product_size ?: ''))),
                            ];
                        })->values()->all(),
                    ],
                ],
            ],
        ];
    }

    private function clientIdForOrder(Order $order): string
    {
        $seed = (string) ($order->order_public_token ?: $order->invoice_id ?: $order->id);

        return 'order.'.((int) $order->id).'.'.sprintf('%u', crc32($seed));
    }

    private function isOrderEligible(Order $order): bool
    {
        if (! Schema::hasColumn('orders', 'order_status')) {
            return false;
        }

        $statusId = (int) ($order->order_status ?? 0);
        if ($statusId <= 0) {
            return false;
        }

        $status = $order->relationLoaded('status') ? $order->status : null;
        if (! $status && Schema::hasTable('order_statuses')) {
            $status = OrderStatus::query()->select(['id', 'name', 'slug'])->find($statusId);
        }

        if (! $status) {
            return false;
        }

        $slug = Str::slug((string) ($status->slug ?: $status->name));

        return in_array($slug, ['confirmed', 'processing', 'shipped', 'delivered'], true);
    }
}
