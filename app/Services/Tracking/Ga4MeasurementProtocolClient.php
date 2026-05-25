<?php

namespace App\Services\Tracking;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Ga4MeasurementProtocolClient
{
    /**
     * Send a purchase event to Google Analytics 4.
     */
    public function sendPurchase(Order $order): bool
    {
        $config = config('tracking.ga4');

        if (! $config['enabled']) {
            Log::debug('GA4 MP Purchase tracking skipped: Disabled.');
            return true;
        }

        if (empty($config['measurement_id']) || empty($config['api_secret'])) {
            Log::warning('GA4 MP Purchase tracking failed: Missing measurement_id or api_secret.', [
                'order_id' => $order->id,
                'invoice_id' => $order->invoice_id,
            ]);

            return false;
        }

        $clientId = $this->resolveClientId($order);
        $payload = $this->buildPayload($order, $clientId);

        $url = $config['endpoint'] . '?measurement_id=' . urlencode($config['measurement_id']) . '&api_secret=' . urlencode($config['api_secret']);

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(10)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('GA4 MP Purchase tracked successfully', [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                ]);
                return true;
            }

            Log::error('GA4 MP Purchase tracking failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('GA4 MP Purchase tracking exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build the GA4 ecommerce purchase payload.
     */
    private function buildPayload(Order $order, string $clientId): array
    {
        $items = [];
        foreach ($order->orderItems as $item) {
            $variantParts = array_filter([$item->product_color, $item->product_size]);
            $items[] = [
                'item_id' => (string) $item->product_id,
                'item_name' => (string) ($item->product_name ?: 'Product ' . $item->product_id),
                'price' => round((float) $item->sale_price, 2),
                'quantity' => (int) $item->qty,
                'item_variant' => !empty($variantParts) ? implode(' / ', $variantParts) : null,
            ];
        }

        return [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'transaction_id' => (string) ($order->invoice_id ?: $order->id),
                        'value' => round((float) $order->amount, 2),
                        'tax' => 0.0,
                        'shipping' => round((float) ($order->shipping_charge ?? 0), 2),
                        'currency' => strtoupper((string) ($order->currency ?: 'BDT')),
                        'items' => $items,
                    ],
                ],
            ],
        ];
    }

    /**
     * Resolve a deterministic client_id for the user.
     */
    private function resolveClientId(Order $order): string
    {
        // Try to read GA cookie if current request is matching.
        // Fallback to deterministic ID based on order info.
        $cookie = request()->cookie('_ga');
        if ($cookie && preg_match('/GA1\.\d+\.(\d+\.\d+)/', $cookie, $matches)) {
            return $matches[1];
        }

        $seed = $order->customer_id 
            ? 'cust-' . $order->customer_id 
            : 'guest-' . md5((string) ($order->shipping?->phone ?? $order->invoice_id));

        $hash = md5($seed);
        
        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
<?php

namespace App\Services\Tracking;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Ga4MeasurementProtocolClient
{
    /**
     * Send a purchase event to Google Analytics 4.
     */
    public function sendPurchase(Order $order): bool
    {
        $config = config('tracking.ga4');

        if (! $config['enabled']) {
            Log::debug('GA4 MP Purchase tracking skipped: Disabled.');
            return true;
        }

        if (empty($config['measurement_id']) || empty($config['api_secret'])) {
            Log::warning('GA4 MP Purchase tracking failed: Missing measurement_id or api_secret.', [
                'order_id' => $order->id,
                'invoice_id' => $order->invoice_id,
            ]);

            return false;
        }

        $clientId = $this->resolveClientId($order);
        $payload = $this->buildPayload($order, $clientId);

        $url = $config['endpoint'] . '?measurement_id=' . urlencode($config['measurement_id']) . '&api_secret=' . urlencode($config['api_secret']);

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(10)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('GA4 MP Purchase tracked successfully', [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                ]);
                return true;
            }

            Log::error('GA4 MP Purchase tracking failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('GA4 MP Purchase tracking exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build the GA4 ecommerce purchase payload.
     */
    private function buildPayload(Order $order, string $clientId): array
    {
        $items = [];
        foreach ($order->orderItems as $item) {
            $variantParts = array_filter([$item->product_color, $item->product_size]);
            $items[] = [
                'item_id' => (string) $item->product_id,
                'item_name' => (string) ($item->product_name ?: 'Product ' . $item->product_id),
                'price' => round((float) $item->sale_price, 2),
                'quantity' => (int) $item->qty,
                'item_variant' => !empty($variantParts) ? implode(' / ', $variantParts) : null,
            ];
        }

        return [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'transaction_id' => (string) ($order->invoice_id ?: $order->id),
                        'value' => round((float) $order->amount, 2),
                        'tax' => 0.0,
                        'shipping' => round((float) ($order->shipping_charge ?? 0), 2),
                        'currency' => strtoupper((string) ($order->currency ?: 'BDT')),
                        'items' => $items,
                    ],
                ],
            ],
        ];
    }

    /**
     * Resolve a deterministic client_id for the user.
     */
    private function resolveClientId(Order $order): string
    {
        // Try to read GA cookie if current request is matching.
        // Fallback to deterministic ID based on order info.
        $cookie = request()->cookie('_ga');
        if ($cookie && preg_match('/GA1\.\d+\.(\d+\.\d+)/', $cookie, $matches)) {
            return $matches[1];
        }

        $seed = $order->customer_id 
            ? 'cust-' . $order->customer_id 
            : 'guest-' . md5((string) ($order->shipping?->phone ?? $order->invoice_id));

        $hash = md5($seed);
        
        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
<?php

namespace App\Services\Tracking;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Ga4MeasurementProtocolClient
{
    /**
     * Send a purchase event to Google Analytics 4.
     */
    public function sendPurchase(Order $order): bool
    {
        $config = config('tracking.ga4');

        if (! $config['enabled']) {
            Log::debug('GA4 MP Purchase tracking skipped: Disabled.');
            return true;
        }

        if (empty($config['measurement_id']) || empty($config['api_secret'])) {
            Log::warning('GA4 MP Purchase tracking failed: Missing measurement_id or api_secret.', [
                'order_id' => $order->id,
                'invoice_id' => $order->invoice_id,
            ]);

            return false;
        }

        $clientId = $this->resolveClientId($order);
        $payload = $this->buildPayload($order, $clientId);

        $url = $config['endpoint'] . '?measurement_id=' . urlencode($config['measurement_id']) . '&api_secret=' . urlencode($config['api_secret']);

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(10)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('GA4 MP Purchase tracked successfully', [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                ]);
                return true;
            }

            Log::error('GA4 MP Purchase tracking failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('GA4 MP Purchase tracking exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build the GA4 ecommerce purchase payload.
     */
    private function buildPayload(Order $order, string $clientId): array
    {
        $items = [];
        foreach ($order->orderItems as $item) {
            $variantParts = array_filter([$item->product_color, $item->product_size]);
            $items[] = [
                'item_id' => (string) $item->product_id,
                'item_name' => (string) ($item->product_name ?: 'Product ' . $item->product_id),
                'price' => round((float) $item->sale_price, 2),
                'quantity' => (int) $item->qty,
                'item_variant' => !empty($variantParts) ? implode(' / ', $variantParts) : null,
            ];
        }

        return [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'transaction_id' => (string) ($order->invoice_id ?: $order->id),
                        'value' => round((float) $order->amount, 2),
                        'tax' => 0.0,
                        'shipping' => round((float) ($order->shipping_charge ?? 0), 2),
                        'currency' => strtoupper((string) ($order->currency ?: 'BDT')),
                        'items' => $items,
                    ],
                ],
            ],
        ];
    }

    /**
     * Resolve a deterministic client_id for the user.
     */
    private function resolveClientId(Order $order): string
    {
        // Try to read GA cookie if current request is matching.
        // Fallback to deterministic ID based on order info.
        $cookie = request()->cookie('_ga');
        if ($cookie && preg_match('/GA1\.\d+\.(\d+\.\d+)/', $cookie, $matches)) {
            return $matches[1];
        }

        $seed = $order->customer_id 
            ? 'cust-' . $order->customer_id 
            : 'guest-' . md5((string) ($order->shipping?->phone ?? $order->invoice_id));

        $hash = md5($seed);
        
        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
<?php

namespace App\Services\Tracking;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Ga4MeasurementProtocolClient
{
    /**
     * Send a purchase event to Google Analytics 4.
     */
    public function sendPurchase(Order $order): bool
    {
        $config = config('tracking.ga4');

        if (! $config['enabled']) {
            Log::debug('GA4 MP Purchase tracking skipped: Disabled.');
            return true;
        }

        if (empty($config['measurement_id']) || empty($config['api_secret'])) {
            Log::warning('GA4 MP Purchase tracking failed: Missing measurement_id or api_secret.', [
                'order_id' => $order->id,
                'invoice_id' => $order->invoice_id,
            ]);

            return false;
        }

        $clientId = $this->resolveClientId($order);
        $payload = $this->buildPayload($order, $clientId);

        $url = $config['endpoint'] . '?measurement_id=' . urlencode($config['measurement_id']) . '&api_secret=' . urlencode($config['api_secret']);

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(10)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('GA4 MP Purchase tracked successfully', [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                ]);
                return true;
            }

            Log::error('GA4 MP Purchase tracking failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('GA4 MP Purchase tracking exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build the GA4 ecommerce purchase payload.
     */
    private function buildPayload(Order $order, string $clientId): array
    {
        $items = [];
        foreach ($order->orderItems as $item) {
            $variantParts = array_filter([$item->product_color, $item->product_size]);
            $items[] = [
                'item_id' => (string) $item->product_id,
                'item_name' => (string) ($item->product_name ?: 'Product ' . $item->product_id),
                'price' => round((float) $item->sale_price, 2),
                'quantity' => (int) $item->qty,
                'item_variant' => !empty($variantParts) ? implode(' / ', $variantParts) : null,
            ];
        }

        return [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'transaction_id' => (string) ($order->invoice_id ?: $order->id),
                        'value' => round((float) $order->amount, 2),
                        'tax' => 0.0,
                        'shipping' => round((float) ($order->shipping_charge ?? 0), 2),
                        'currency' => strtoupper((string) ($order->currency ?: 'BDT')),
                        'items' => $items,
                    ],
                ],
            ],
        ];
    }

    /**
     * Resolve a deterministic client_id for the user.
     */
    private function resolveClientId(Order $order): string
    {
        // Try to read GA cookie if current request is matching.
        // Fallback to deterministic ID based on order info.
        $cookie = request()->cookie('_ga');
        if ($cookie && preg_match('/GA1\.\d+\.(\d+\.\d+)/', $cookie, $matches)) {
            return $matches[1];
        }

        $seed = $order->customer_id 
            ? 'cust-' . $order->customer_id 
            : 'guest-' . md5((string) ($order->shipping?->phone ?? $order->invoice_id));

        $hash = md5($seed);
        
        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
