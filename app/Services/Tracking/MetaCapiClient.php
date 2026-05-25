<?php

namespace App\Services\Tracking;

use App\Models\Order;
use App\Models\EcomPixel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCapiClient
{
    /**
     * Send a purchase event to Meta Conversions API.
     */
    public function sendPurchase(Order $order): bool
    {
        $config = config('tracking.facebook');

        if (!$config['enabled'] || empty($config['capi_access_token'])) {
            Log::debug('Meta CAPI Purchase tracking skipped: Disabled or missing access token.');
            return true;
        }

        // Get pixel codes from DB (primary) or config
        $pixelIds = EcomPixel::getActiveCodesCached();
        if ($pixelIds->isEmpty()) {
            $pixelId = env('FACEBOOK_PIXEL_ID');
            if ($pixelId) {
                $pixelIds = collect([trim($pixelId)]);
            }
        }

        if ($pixelIds->isEmpty()) {
            Log::debug('Meta CAPI Purchase tracking skipped: No active Pixel IDs found.');
            return true;
        }

        $payload = $this->buildPayload($order);
        $accessToken = $config['capi_access_token'];
        $apiVersion = $config['api_version'];
        $endpoint = $config['endpoint'];

        $allSuccessful = true;

        foreach ($pixelIds as $pixelId) {
            $url = "{$endpoint}/{$apiVersion}/{$pixelId}/events";
            try {
                $response = Http::asJson()
                    ->acceptJson()
                    ->timeout(10)
                    ->post($url, [
                        'data' => [$payload],
                        'access_token' => $accessToken,
                    ]);

                if ($response->successful()) {
                    Log::info('Meta CAPI Purchase tracked successfully', [
                        'pixel_id' => $pixelId,
                        'order_id' => $order->id,
                    ]);
                } else {
                    Log::error('Meta CAPI Purchase tracking failed', [
                        'pixel_id' => $pixelId,
                        'order_id' => $order->id,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    $allSuccessful = false;
                }
            } catch (\Throwable $e) {
                Log::error('Meta CAPI Purchase tracking exception', [
                    'pixel_id' => $pixelId,
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
                $allSuccessful = false;
            }
        }

        return $allSuccessful;
    }

    /**
     * Build the CAPI event payload.
     */
    private function buildPayload(Order $order): array
    {
        $shipping = $order->shipping;
        $customer = $order->customer;

        $email = $customer?->email ?? '';
        $phone = $shipping?->phone ?? $customer?->phone ?? '';
        $name = $shipping?->name ?? $customer?->name ?? '';
        $city = $shipping?->area ?? '';

        $userData = [
            'client_user_agent' => request()->userAgent() ?? 'Server-Side-PHP',
            'client_ip_address' => request()->ip() ?? '127.0.0.1',
        ];

        // Add hashed user identity parameters
        if (!empty($email)) {
            $userData['em'] = [$this->hashValue(strtolower(trim($email)))];
        }
        if (!empty($phone)) {
            $userData['ph'] = [$this->hashValue($this->normalizePhone($phone))];
        }
        if (!empty($name)) {
            $parts = explode(' ', trim($name));
            $firstName = strtolower(array_shift($parts));
            $userData['fn'] = [$this->hashValue($firstName)];
            if (!empty($parts)) {
                $lastName = strtolower(implode(' ', $parts));
                $userData['ln'] = [$this->hashValue($lastName)];
            }
        }
        if (!empty($city)) {
            $userData['ct'] = [$this->hashValue(strtolower(trim($city)))];
        }

        // Contents
        $contents = [];
        foreach ($order->orderItems as $item) {
            $contents[] = [
                'id' => (string) $item->product_id,
                'quantity' => (int) $item->qty,
                'item_price' => (float) $item->sale_price,
            ];
        }

        return [
            'event_name' => 'Purchase',
            'event_time' => time(),
            'event_id' => 'order-' . $order->id,
            'event_source_url' => url('/order-success/' . $order->id),
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => [
                'currency' => strtoupper((string) ($order->currency ?: 'BDT')),
                'value' => (float) $order->amount,
                'content_type' => 'product',
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
        
        // Bangladesh phone matching: if 11 digits starting with 01
        if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            return '88' . $digits;
        }

        return $digits;
    }
}
