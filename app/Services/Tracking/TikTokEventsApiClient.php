<?php

namespace App\Services\Tracking;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokEventsApiClient
{
    /**
     * Send a purchase event to TikTok Events API.
     */
    public function sendPurchase(Order $order): bool
    {
        $config = config('tracking.tiktok');

        if (!$config['enabled'] || empty($config['pixel_id']) || empty($config['access_token'])) {
            Log::debug('TikTok Events API Purchase tracking skipped: Disabled or missing credentials.');
            return true;
        }

        $payload = $this->buildPayload($order, $config['pixel_id']);
        $url = rtrim($config['endpoint'], '/') . '/' . $config['api_version'] . '/event/track/';

        try {
            $response = Http::withHeaders([
                'Access-Token' => $config['access_token'],
                'Content-Type' => 'application/json',
            ])
            ->timeout(10)
            ->post($url, $payload);

            if ($response->successful()) {
                Log::info('TikTok Events API Purchase tracked successfully', [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                ]);
                return true;
            }

            Log::error('TikTok Events API Purchase tracking failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('TikTok Events API Purchase tracking exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build the TikTok Event payload.
     */
    private function buildPayload(Order $order, string $pixelId): array
    {
        $shipping = $order->shipping;
        $customer = $order->customer;

        $email = $customer?->email ?? '';
        $phone = $shipping?->phone ?? $customer?->phone ?? '';

        $user = [];
        if (!empty($email)) {
            $user['email'] = $this->hashValue(strtolower(trim($email)));
        }
        if (!empty($phone)) {
            $user['phone_number'] = $this->hashValue($this->normalizePhone($phone));
        }

        $contents = [];
        foreach ($order->orderItems as $item) {
            $contents[] = [
                'content_id' => (string) $item->product_id,
                'content_type' => 'product',
                'content_name' => (string) ($item->product_name ?: 'Product ' . $item->product_id),
                'quantity' => (int) $item->qty,
                'price' => (float) $item->sale_price,
            ];
        }

        return [
            'pixel_code' => $pixelId,
            'event' => 'CompletePayment', // TikTok standard purchase event
            'event_id' => 'order-' . $order->id,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'context' => [
                'ip' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'Server-Side-PHP',
                'user' => $user,
            ],
            'properties' => [
                'currency' => strtoupper((string) ($order->currency ?: 'BDT')),
                'value' => (float) $order->amount,
                'contents' => $contents,
            ],
        ];
    }

    /**
     * Hash value with SHA-256.
     */
    private function hashValue(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Normalize phone numbers to include country code (e.g. Bangladesh +880).
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        
        if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            return '88' . $digits;
        }

        return $digits;
    }
}
