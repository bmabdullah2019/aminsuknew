<?php

namespace App\Services;

use App\Models\Courierapi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteadfastService
{
    protected ?string $apiKey;
    protected ?string $secretKey;
    protected string $baseUrl = 'https://portal.packzy.com/api/v1';
    protected bool $isConfigured = false;

    public function __construct()
    {
        $courier = Courierapi::where(['type' => 'steadfast', 'status' => 1])->first();

        if ($courier) {
            $this->apiKey = $courier->api_key;
            $this->secretKey = $courier->secret_key;
            $this->isConfigured = true;

            // If a custom base URL is stored, use it (strip trailing /api/v1 if present)
            if (! empty($courier->url)) {
                $url = rtrim($courier->url, '/');
                // If the URL already ends with /api/v1, use its parent as base
                if (str_ends_with($url, '/api/v1')) {
                    $this->baseUrl = $url;
                } elseif (str_ends_with($url, '/create_order')) {
                    // Legacy: some setups store the full create_order endpoint
                    $this->baseUrl = str_replace('/create_order', '', $url);
                } else {
                    $this->baseUrl = $url;
                }
            }
        }
    }

    /**
     * Check if the service has valid API credentials configured.
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Get authenticated HTTP client.
     */
    protected function client()
    {
        return Http::withHeaders([
            'Api-Key' => $this->apiKey,
            'Secret-Key' => $this->secretKey,
            'Content-Type' => 'application/json',
        ])->timeout(30);
    }

    /**
     * Make a GET request to the Steadfast API.
     */
    protected function get(string $path): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . $path);
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('SteadfastService GET error', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            return ['status' => 500, 'error' => $e->getMessage()];
        }
    }

    /**
     * Make a POST request to the Steadfast API.
     */
    protected function post(string $path, array $data = []): array
    {
        try {
            $response = $this->client()->post($this->baseUrl . $path, $data);
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('SteadfastService POST error', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            return ['status' => 500, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // ORDER ENDPOINTS
    // =========================================================================

    /**
     * Create a single order / consignment.
     *
     * @param  array  $data  Keys: invoice, recipient_name, recipient_phone,
     *                       recipient_address, cod_amount, note (optional),
     *                       recipient_email (optional), alternative_phone (optional),
     *                       item_description (optional), total_lot (optional),
     *                       delivery_type (optional: 0=home, 1=point)
     */
    public function createOrder(array $data): array
    {
        return $this->post('/create_order', $data);
    }

    /**
     * Create orders in bulk (max 500 per batch).
     *
     * @param  array  $orders  Array of order data arrays
     */
    public function bulkCreateOrders(array $orders): array
    {
        return $this->post('/create_order/bulk-order', [
            'data' => json_encode($orders),
        ]);
    }

    // =========================================================================
    // STATUS CHECK ENDPOINTS
    // =========================================================================

    /**
     * Check delivery status by Steadfast consignment ID.
     */
    public function statusByConsignmentId(int $cid): array
    {
        return $this->get("/status_by_cid/{$cid}");
    }

    /**
     * Check delivery status by your invoice ID.
     */
    public function statusByInvoice(string $invoice): array
    {
        return $this->get("/status_by_invoice/{$invoice}");
    }

    /**
     * Check delivery status by tracking code.
     */
    public function statusByTrackingCode(string $code): array
    {
        return $this->get("/status_by_trackingcode/{$code}");
    }

    // =========================================================================
    // BALANCE
    // =========================================================================

    /**
     * Get current Steadfast balance.
     */
    public function getBalance(): array
    {
        return $this->get('/get_balance');
    }

    // =========================================================================
    // RETURN REQUESTS
    // =========================================================================

    /**
     * Create a return request.
     *
     * @param  array  $data  Keys: consignment_id|invoice|tracking_code, reason (optional)
     */
    public function createReturnRequest(array $data): array
    {
        return $this->post('/create_return_request', $data);
    }

    /**
     * Get a single return request by ID.
     */
    public function getReturnRequest(int $id): array
    {
        return $this->get("/get_return_request/{$id}");
    }

    /**
     * Get all return requests.
     */
    public function getReturnRequests(): array
    {
        return $this->get('/get_return_requests');
    }

    // =========================================================================
    // PAYMENTS
    // =========================================================================

    /**
     * Get all payments.
     */
    public function getPayments(): array
    {
        return $this->get('/payments');
    }

    /**
     * Get a single payment with its consignments.
     */
    public function getPayment(int $paymentId): array
    {
        return $this->get("/payments/{$paymentId}");
    }

    // =========================================================================
    // POLICE STATIONS
    // =========================================================================

    /**
     * Get all police stations.
     */
    public function getPoliceStations(): array
    {
        return $this->get('/police_stations');
    }
}
