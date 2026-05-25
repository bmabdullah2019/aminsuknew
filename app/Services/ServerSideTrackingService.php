<?php

namespace App\Services;

use App\Models\Order;
use App\Models\EcomPixel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServerSideTrackingService
{
    /**
     * Track a confirmed purchase on GA4 and Facebook CAPI.
     */
    public function trackPurchase(Order $order): void
    {
        try {
            $order->loadMissing(['orderdetails', 'shipping', 'customer']);

            // 1. Send to Facebook Conversions API (CAPI)
            $this->sendFacebookCapiPurchase($order);

            // 2. Send to GA4 Measurement Protocol
            $this->sendGa4MPPurchase($order);

        } catch (Throwable $e) {
            Log::error('ServerSideTrackingService tracking failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send Purchase event to Facebook Conversions API.
     */
    private function sendFacebookCapiPurchase(Order $order): void
    {
        $accessToken = config('tracking.facebook.capi_access_token');
        if (empty($accessToken)) {
            Log::debug('Facebook CAPI skipped: ACCESS_TOKEN not configured.');
            return;
        }

        // Fetch active Pixel IDs from database
        $pixelIds = EcomPixel::getActiveCodesCached();
        if ($pixelIds->isEmpty()) {
            Log::debug('Facebook CAPI skipped: No active Pixel IDs found in database.');
            return;
        }

        // Prepare User Data (Normalized and SHA256 hashed)
        $shipping = $order->shipping;
        $customer = $order->customer;

        $email = $customer?->email ?? '';
        $phone = $shipping?->phone ?? $customer?->phone ?? '';
        $name = $shipping?->name ?? $customer?->name ?? '';
        $city = $shipping?->area ?? '';

        $userData = [];
        if (!empty($email)) {
            $userData['em'] = [hash('sha256', strtolower(trim($email)))];
        }
        if (!empty($phone)) {
            $normalizedPhone = preg_replace('/\D+/', '', $phone);
            $userData['ph'] = [hash('sha256', $normalizedPhone)];
        }
        if (!empty($name)) {
            $parts = explode(' ', trim($name));
            $firstName = strtolower(array_shift($parts));
            $userData['fn'] = [hash('sha256', $firstName)];
            if (!empty($parts)) {
                $lastName = strtolower(implode(' ', $parts));
                $userData['ln'] = [hash('sha256', $lastName)];
            }
        }
        if (!empty($city)) {
            $userData['ct'] = [hash('sha256', strtolower(trim($city)))];
        }

        $userData['client_user_agent'] = request()->userAgent() ?? 'Server-Side-PHP';
        $userData['client_ip_address'] = request()->ip() ?? '127.0.0.1';

        // Prepare Contents
        $contents = [];
        foreach ($order->orderdetails as $detail) {
            $contents[] = [
                'id' => (string) $detail->product_id,
                'quantity' => (int) $detail->qty,
                'item_price' => (float) $detail->sale_price
            ];
        }

        // Event Payload
        $eventData = [
            'event_name' => 'Purchase',
            'event_time' => time(),
            'event_id' => 'order-' . $order->id,
            'event_source_url' => url('/order-success/' . $order->id . '?t=' . $order->order_public_token),
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => [
                'currency' => strtoupper($order->currency ?: 'BDT'),
                'value' => (float) $order->amount,
                'content_type' => 'product',
                'contents' => $contents
            ]
        ];

        // Send to each active Pixel ID
        foreach ($pixelIds as $pixelId) {
            try {
                $url = "https://graph.facebook.com/v19.0/{$pixelId}/events";
                $response = Http::post($url, [
                    'data' => [$eventData],
                    'access_token' => $accessToken
                ]);

                if ($response->successful()) {
                    Log::info("Facebook CAPI Purchase event sent successfully", [
                        'pixel_id' => $pixelId,
                        'order_id' => $order->id,
                        'invoice' => $order->invoice_id
                    ]);
                } else {
                    Log::error("Facebook CAPI request failed", [
                        'pixel_id' => $pixelId,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                }
            } catch (Throwable $ex) {
                Log::error("Error sending Facebook CAPI event to pixel {$pixelId}", [
                    'order_id' => $order->id,
                    'message' => $ex->getMessage()
                ]);
            }
        }
    }

    /**
     * Send Purchase event to GA4 Measurement Protocol.
     */
    private function sendGa4MPPurchase(Order $order): void
    {
        $measurementId = config('tracking.ga4.measurement_id');
        $apiSecret = config('tracking.ga4.api_secret');

        if (empty($measurementId) || empty($apiSecret)) {
            Log::debug('GA4 Measurement Protocol skipped: measurement_id or api_secret not configured.');
            return;
        }

        // Generate a deterministic client_id for this customer
        $clientId = md5('customer-' . ($order->customer_id ?? rand(1000, 9999)));
        $clientIdFormatted = sprintf('%08s-%04s-%04s-%04s-%12s',
            substr($clientId, 0, 8),
            substr($clientId, 8, 4),
            substr($clientId, 12, 4),
            substr($clientId, 16, 4),
            substr($clientId, 20, 12)
        );

        $items = [];
        foreach ($order->orderdetails as $detail) {
            $items[] = [
                'item_id' => (string) $detail->product_id,
                'item_name' => $detail->product_name,
                'price' => (float) $detail->sale_price,
                'quantity' => (int) $detail->qty
            ];
        }

        $payload = [
            'client_id' => $clientIdFormatted,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'transaction_id' => (string) $order->invoice_id,
                        'value' => (float) $order->amount,
                        'currency' => strtoupper($order->currency ?: 'BDT'),
                        'shipping' => (float) $order->shipping_charge,
                        'tax' => 0.0,
                        'items' => $items
                    ]
                ]
            ]
        ];

        try {
            $url = "https://www.google-analytics.com/mp/collect?api_secret={$apiSecret}&measurement_id={$measurementId}";
            $response = Http::post($url, $payload);

            if ($response->successful()) {
                Log::info("GA4 MP Purchase event sent successfully", [
                    'order_id' => $order->id,
                    'invoice' => $order->invoice_id
                ]);
            } else {
                Log::error("GA4 MP request failed", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (Throwable $ex) {
            Log::error("Error sending GA4 MP event", [
                'order_id' => $order->id,
                'message' => $ex->getMessage()
            ]);
        }
    }
}
