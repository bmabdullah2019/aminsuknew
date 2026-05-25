<?php

namespace App\Services;

use App\Models\FraudCheckerApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FraudCheckerService
{
    private $fraudApi;

    public function __construct()
    {
        $this->fraudApi = FraudCheckerApi::where('status', true)->first();

        if (! $this->fraudApi) {
            $this->fraudApi = $this->buildFallbackApiConfig();
        }

        if ($this->fraudApi && isset($this->fraudApi->api_url)) {
            $this->fraudApi->api_url = self::normalizeConfiguredApiUrl((string) $this->fraudApi->api_url);
        }
    }

    /**
     * Check phone number for fraud using the configured API
     */
    public function checkPhone(string $phone, ?string $queryType = null, bool $forceFresh = false): array
    {
        if (! $this->fraudApi) {
            return [
                'success' => false,
                'message' => 'Fraud checker API not configured or disabled',
                'risk_level' => 'unknown',
                'risk_score' => 0,
                'data' => null,
            ];
        }

        try {
            $requestUrl = $this->resolveApiRequestUrl();
            if ($requestUrl === '') {
                return [
                    'success' => false,
                    'message' => 'Fraud checker API endpoint is not configured',
                    'risk_level' => 'unknown',
                    'risk_score' => 0,
                    'data' => null,
                ];
            }

            $normalizedPhone = $this->normalizePhone($phone);
            if ($normalizedPhone === '') {
                return [
                    'success' => false,
                    'message' => 'Phone number is required',
                    'risk_level' => 'unknown',
                    'risk_score' => 0,
                    'data' => null,
                ];
            }

            $queryTypeKey = $this->isQcApi() ? 'qc' : ($queryType ?? $this->fraudApi->query_type);
            $cacheKey = "fraud_check_{$normalizedPhone}_{$queryTypeKey}";

            // Check cache first (cache for 1 hour) unless a fresh lookup is requested.
            if (! $forceFresh && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $queryType = $queryType ?? $this->fraudApi->query_type;

            $request = Http::withToken($this->fraudApi->api_key)
                ->connectTimeout(10)
                ->timeout(40);

            // fraudchecker.link QC endpoint expects x-www-form-urlencoded with only `phone`
            if ($this->isQcApi()) {
                $request = $request->asForm();
                $payload = [
                    'phone' => $normalizedPhone,
                ];
            } else {
                $payload = [
                    'phone' => $normalizedPhone,
                ];

                if ($queryType !== null && ! $this->isBdCourierApi()) {
                    $payload['query_type'] = $queryType;
                }
            }

            $response = $request->post($requestUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                $result = [
                    'success' => true,
                    'message' => 'Fraud check completed successfully',
                    'risk_level' => $this->parseRiskLevel($data),
                    'risk_score' => $this->parseRiskScore($data),
                    'data' => $data,
                ];

                // Cache successful results for 1 hour
                Cache::put($cacheKey, $result, 3600);

                return $result;
            } else {
                Log::warning('Fraud checker API error', [
                    'phone' => $normalizedPhone,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                $message = $response->json('message') ?? null;
                if (! $message && $response->status() === 401) {
                    $message = 'Authorization header missing';
                } elseif (! $message && $response->status() === 403) {
                    $message = 'Invalid API key';
                } elseif (! $message && $response->status() === 400) {
                    $message = 'Phone number is required';
                }

                return [
                    'success' => false,
                    'message' => $message ? $message : ('Fraud checker API error: '.$response->status()),
                    'risk_level' => 'unknown',
                    'risk_score' => 0,
                    'data' => null,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Fraud checker service error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Service error: '.$e->getMessage(),
                'risk_level' => 'unknown',
                'risk_score' => 0,
                'data' => null,
            ];
        }
    }

    /**
     * Parse risk level from API response
     */
    private function parseRiskLevel(array $data): string
    {
        // FraudChecker QC response format
        if ($this->isQcResponse($data)) {
            $riskScore = $this->parseRiskScore($data); // cancellation %

            if ($riskScore <= 20) {
                return 'low';
            } elseif ($riskScore <= 50) {
                return 'medium';
            } else {
                return 'high';
            }
        }

        // BDCourier response format
        if ($this->isBdCourierResponse($data)) {
            $riskScore = $this->parseRiskScore($data); // cancellation %

            if ($riskScore <= 20) {
                return 'low';
            } elseif ($riskScore <= 50) {
                return 'medium';
            } else {
                return 'high';
            }
        }

        // Try different possible response formats
        if (isset($data['data']['risk_level'])) {
            return strtolower($data['data']['risk_level']);
        }

        if (isset($data['risk_level'])) {
            return strtolower($data['risk_level']);
        }

        // Fallback based on risk score
        $riskScore = $this->parseRiskScore($data);

        if ($riskScore <= 30) {
            return 'low';
        } elseif ($riskScore <= 70) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Parse risk score from API response
     */
    private function parseRiskScore(array $data): int
    {
        // FraudChecker QC response format: use cancellation ratio (%) as risk score
        if ($this->isQcResponse($data)) {
            $totalParcels = (int) ($data['total_parcels'] ?? 0);
            $totalCancel = (int) ($data['total_cancel'] ?? 0);

            if ($totalParcels <= 0) {
                return 0;
            }

            return (int) round(($totalCancel / $totalParcels) * 100);
        }

        // BDCourier response format: use cancelled ratio (%) as risk score
        if ($this->isBdCourierResponse($data)) {
            $summary = $data['data']['summary'] ?? [];
            $totalParcels = (int) ($summary['total_parcel'] ?? 0);
            $totalCancelled = (int) ($summary['cancelled_parcel'] ?? 0);

            if ($totalParcels <= 0) {
                return 0;
            }

            return (int) round(($totalCancelled / $totalParcels) * 100);
        }

        // Try different possible response formats
        if (isset($data['data']['risk_score'])) {
            return (int) $data['data']['risk_score'];
        }

        if (isset($data['risk_score'])) {
            return (int) $data['risk_score'];
        }

        if (isset($data['data']['score'])) {
            return (int) $data['data']['score'];
        }

        if (isset($data['score'])) {
            return (int) $data['score'];
        }

        // Default fallback
        return 0;
    }

    /**
     * Check if fraud checker API is enabled
     */
    public function isEnabled(): bool
    {
        return $this->fraudApi !== null;
    }

    /**
     * Get fraud check result with formatted badge class
     */
    public function getFormattedRiskData(string $phone, ?string $queryType = null, bool $forceFresh = false): array
    {
        $result = $this->checkPhone($phone, $queryType, $forceFresh);

        return $this->formatRiskData($result);
    }

    /**
     * Return cached formatted risk data without calling the remote API.
     */
    public function getCachedFormattedRiskData(string $phone, ?string $queryType = null): ?array
    {
        if (! $this->fraudApi) {
            return null;
        }

        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            return null;
        }

        $queryTypeKey = $this->isQcApi() ? 'qc' : ($queryType ?? $this->fraudApi->query_type);
        $cacheKey = "fraud_check_{$normalizedPhone}_{$queryTypeKey}";
        $cachedResult = Cache::get($cacheKey);

        if (! is_array($cachedResult)) {
            return null;
        }

        return $this->formatRiskData($cachedResult);
    }

    /**
     * Bulk check multiple phone numbers
     */
    public function bulkCheckPhones(array $phones, ?string $queryType = null): array
    {
        $results = [];

        foreach ($phones as $phone) {
            $results[$phone] = $this->getFormattedRiskData($phone, $queryType);
        }

        return $results;
    }

    /**
     * Clear cache for a specific phone number
     */
    public function clearPhoneCache(string $phone): void
    {
        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            return;
        }

        $queryTypes = ['basic', 'detailed', 'comprehensive', 'qc'];

        foreach ($queryTypes as $type) {
            $cacheKey = "fraud_check_{$normalizedPhone}_{$type}";
            Cache::forget($cacheKey);
        }
    }

    /**
     * Test API connection
     */
    public function testApiConnection(): array
    {
        if (! $this->fraudApi) {
            return [
                'success' => false,
                'message' => 'No fraud checker API configured',
            ];
        }

        try {
            // Use a valid-looking BD number so fraudchecker.link QC doesn't fail immediately
            $testPhone = '01712345678';
            $result = $this->checkPhone($testPhone);

            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'API connection successful' : $result['message'],
                'api_name' => $this->fraudApi->name,
                'api_url' => $this->fraudApi->api_url,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get detailed fraud analysis with courier and order information
     */
    public function getDetailedAnalysis(string $phone, ?int $orderId = null, bool $forceFresh = false): array
    {
        $phoneForDisplay = $this->normalizePhone($phone);
        if ($phoneForDisplay === '') {
            $phoneForDisplay = $phone;
        }

        $basicResult = $this->getFormattedRiskData($phoneForDisplay, null, $forceFresh);

        // Get detailed risk data from API if available
        $detailedRiskData = [];
        if ($basicResult['success'] && isset($basicResult['data'])) {
            $apiData = $basicResult['data'];

            // FraudChecker QC response format
            if ($this->isQcResponse($apiData)) {
                $detailedRiskData = $this->buildQcDetailedRiskData($apiData, $phoneForDisplay);
            } elseif ($this->isBdCourierResponse($apiData)) {
                $detailedRiskData = $this->buildBdCourierDetailedRiskData($apiData, $phoneForDisplay);
            } else {
                // Extract detailed information from other (legacy) response formats
                $detailedRiskData = [
                    'phone' => $phoneForDisplay,
                    'risk_score' => $basicResult['risk_score'],
                    'risk_level' => $basicResult['risk_level'],
                    'verification_status' => $apiData['data']['verified'] ?? $apiData['verified'] ?? 'Unknown',
                    'carrier' => $apiData['data']['carrier'] ?? $apiData['carrier'] ?? 'Unknown',
                    'country' => $apiData['data']['country'] ?? $apiData['country'] ?? 'Unknown',
                    'line_type' => $apiData['data']['line_type'] ?? $apiData['line_type'] ?? 'Unknown',
                    'fraud_indicators' => $apiData['data']['fraud_indicators'] ?? $apiData['fraud_indicators'] ?? [],
                    'reputation_score' => $apiData['data']['reputation_score'] ?? $apiData['reputation_score'] ?? 0,
                    'blacklist_status' => $apiData['data']['blacklisted'] ?? $apiData['blacklisted'] ?? false,
                    'recent_activities' => $apiData['data']['recent_activities'] ?? $apiData['recent_activities'] ?? [],
                ];
            }
        }

        return [
            'success' => $basicResult['success'],
            'message' => $basicResult['message'],
            'basic_risk' => [
                'level' => $basicResult['risk_level'],
                'score' => $basicResult['risk_score'],
                'text' => $basicResult['risk_text'],
                'badge_class' => $basicResult['badge_class'],
            ],
            'detailed_risk' => $detailedRiskData,
            'analysis_time' => now()->format('M d, Y h:i A'),
            'phone' => $phoneForDisplay,
        ];
    }

    /**
     * Get risk recommendations based on analysis
     */
    public function getRiskRecommendations(array $analysisData): array
    {
        $recommendations = [];
        $riskLevel = $analysisData['basic_risk']['level'] ?? 'unknown';
        $riskScore = $analysisData['basic_risk']['score'] ?? 0;

        if ($riskLevel === 'high') {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'High risk detected - Consider manual verification before processing',
                'icon' => 'fe-alert-triangle',
                'color' => 'danger',
            ];
            $recommendations[] = [
                'type' => 'action',
                'message' => 'Recommend additional identity verification',
                'icon' => 'fe-user-check',
                'color' => 'warning',
            ];
        } elseif ($riskLevel === 'medium') {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Moderate risk - Monitor closely',
                'icon' => 'fe-eye',
                'color' => 'warning',
            ];
            $recommendations[] = [
                'type' => 'action',
                'message' => 'Consider COD payment method',
                'icon' => 'fe-credit-card',
                'color' => 'info',
            ];
        } elseif ($riskLevel === 'low') {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Low risk - Normal processing recommended',
                'icon' => 'fe-check-circle',
                'color' => 'success',
            ];
        } else {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Risk could not be determined - Review order details manually if needed',
                'icon' => 'fe-info',
                'color' => 'secondary',
            ];
        }

        // Check for blacklist status
        if (isset($analysisData['detailed_risk']['blacklist_status']) && $analysisData['detailed_risk']['blacklist_status']) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Phone number appears on blacklist',
                'icon' => 'fe-shield-off',
                'color' => 'danger',
            ];
        }

        return $recommendations;
    }

    private function isQcApi(): bool
    {
        $apiUrl = $this->resolveApiRequestUrl();
        if ($apiUrl === '') {
            return false;
        }

        return stripos($apiUrl, '/api/v1/qc') !== false;
    }

    private function isBdCourierApi(): bool
    {
        $apiUrl = $this->resolveApiRequestUrl();
        if ($apiUrl === '') {
            return false;
        }

        $host = strtolower((string) parse_url($apiUrl, PHP_URL_HOST));
        $path = strtolower((string) parse_url($apiUrl, PHP_URL_PATH));

        return str_contains($host, 'api.bdcourier.com')
            || str_contains($path, '/courier-check');
    }

    public static function normalizeConfiguredApiUrl(string $apiUrl): string
    {
        $apiUrl = trim($apiUrl);
        if ($apiUrl === '') {
            return '';
        }

        $parsedUrl = parse_url($apiUrl);
        if (! is_array($parsedUrl)) {
            return rtrim($apiUrl, '/');
        }

        $host = strtolower((string) ($parsedUrl['host'] ?? ''));
        if ($host === '') {
            return rtrim($apiUrl, '/');
        }

        $scheme = (string) ($parsedUrl['scheme'] ?? 'https');
        $port = isset($parsedUrl['port']) ? ':'.$parsedUrl['port'] : '';
        $path = '/'.trim((string) ($parsedUrl['path'] ?? ''), '/');
        $query = isset($parsedUrl['query']) && $parsedUrl['query'] !== '' ? '?'.$parsedUrl['query'] : '';

        if ($path === '/') {
            $path = '';
        }

        if (str_contains($host, 'api.bdcourier.com')) {
            $pathLower = strtolower($path);
            if ($path === '' || ! str_contains($pathLower, 'courier-check')) {
                $path = '/courier-check';
                $query = '';
            }
        } else {
            $path = rtrim($path, '/');
        }

        return $scheme.'://'.$host.$port.$path.$query;
    }

    private function resolveApiRequestUrl(): string
    {
        if (! $this->fraudApi || ! isset($this->fraudApi->api_url)) {
            return '';
        }

        return self::normalizeConfiguredApiUrl((string) $this->fraudApi->api_url);
    }

    private function isQcResponse(array $data): bool
    {
        return isset($data['total_parcels']) && (isset($data['total_delivered']) || isset($data['total_cancel']) || isset($data['apis']));
    }

    private function isBdCourierResponse(array $data): bool
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            return false;
        }

        $summary = $data['data']['summary'] ?? null;

        return is_array($summary)
            && array_key_exists('total_parcel', $summary)
            && array_key_exists('cancelled_parcel', $summary);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // Normalize BD numbers that include country code (88XXXXXXXXXXX)
        if (strlen($digits) === 13 && strpos($digits, '88') === 0) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    private function buildFallbackApiConfig(): ?object
    {
        $apiUrl = self::normalizeConfiguredApiUrl((string) config('services.bdcourier.api_url', ''));
        $apiKey = (string) config('services.bdcourier.api_key', '');

        if ($apiUrl === '' || $apiKey === '') {
            return null;
        }

        return (object) [
            'name' => (string) config('services.bdcourier.name', 'BDCourier'),
            'api_url' => $apiUrl,
            'api_key' => $apiKey,
            'query_type' => (string) config('services.bdcourier.query_type', 'basic'),
            'status' => true,
        ];
    }

    /**
     * Normalize risk output into badge-friendly payload.
     */
    private function formatRiskData(array $result): array
    {
        $riskLevel = strtolower((string) ($result['risk_level'] ?? 'unknown'));
        $riskScore = (int) ($result['risk_score'] ?? 0);

        switch ($riskLevel) {
            case 'low':
                $badgeClass = 'success';
                $riskText = 'Low';
                break;
            case 'medium':
                $badgeClass = 'warning';
                $riskText = 'Medium';
                break;
            case 'high':
                $badgeClass = 'danger';
                $riskText = 'High';
                break;
            default:
                $badgeClass = 'secondary';
                $riskText = 'Unknown';
                break;
        }

        return [
            'success' => (bool) ($result['success'] ?? false),
            'message' => (string) ($result['message'] ?? 'Fraud check unavailable'),
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'risk_text' => $riskText,
            'badge_class' => $badgeClass,
            'formatted_text' => "{$riskText} ({$riskScore}%)",
            'data' => $result['data'] ?? null,
        ];
    }

    private function buildQcDetailedRiskData(array $apiData, string $phone): array
    {
        $totalParcels = (int) ($apiData['total_parcels'] ?? 0);
        $totalDelivered = (int) ($apiData['total_delivered'] ?? 0);
        $totalCancel = (int) ($apiData['total_cancel'] ?? 0);
        $totalOther = max($totalParcels - $totalDelivered - $totalCancel, 0);

        $deliveredPct = $totalParcels > 0 ? round(($totalDelivered / $totalParcels) * 100, 1) : 0;
        $cancelPct = $totalParcels > 0 ? round(($totalCancel / $totalParcels) * 100, 1) : 0;
        $otherPct = $totalParcels > 0 ? round(($totalOther / $totalParcels) * 100, 1) : 0;

        $couriers = [];
        foreach (($apiData['apis'] ?? []) as $courierName => $stats) {
            $courierTotal = (int) ($stats['total_parcels'] ?? 0);
            $courierDelivered = (int) ($stats['total_delivered_parcels'] ?? 0);
            $courierCancelled = (int) ($stats['total_cancelled_parcels'] ?? 0);
            $courierOther = max($courierTotal - $courierDelivered - $courierCancelled, 0);

            $couriers[] = [
                'name' => (string) $courierName,
                'total_parcels' => $courierTotal,
                'delivered_parcels' => $courierDelivered,
                'cancelled_parcels' => $courierCancelled,
                'other_parcels' => $courierOther,
                'delivered_pct' => $courierTotal > 0 ? round(($courierDelivered / $courierTotal) * 100, 1) : 0,
                'cancelled_pct' => $courierTotal > 0 ? round(($courierCancelled / $courierTotal) * 100, 1) : 0,
            ];
        }

        usort($couriers, function ($a, $b) {
            return ($b['total_parcels'] ?? 0) <=> ($a['total_parcels'] ?? 0);
        });

        return [
            'source' => 'fraudchecker_qc',
            'mobile_number' => $apiData['mobile_number'] ?? $phone,
            'total_parcels' => $totalParcels,
            'total_delivered' => $totalDelivered,
            'total_cancel' => $totalCancel,
            'total_other' => $totalOther,
            'delivered_pct' => $deliveredPct,
            'cancel_pct' => $cancelPct,
            'other_pct' => $otherPct,
            'couriers' => $couriers,
        ];
    }

    private function buildBdCourierDetailedRiskData(array $apiData, string $phone): array
    {
        $data = $apiData['data'] ?? [];
        $summary = $data['summary'] ?? [];

        $totalParcels = (int) ($summary['total_parcel'] ?? 0);
        $successParcels = (int) ($summary['success_parcel'] ?? 0);
        $cancelledParcels = (int) ($summary['cancelled_parcel'] ?? 0);
        $otherParcels = max($totalParcels - $successParcels - $cancelledParcels, 0);

        $couriers = [];
        foreach ($data as $key => $stats) {
            if ($key === 'summary' || ! is_array($stats)) {
                continue;
            }

            $courierTotal = (int) ($stats['total_parcel'] ?? 0);
            $courierSuccess = (int) ($stats['success_parcel'] ?? 0);
            $courierCancelled = (int) ($stats['cancelled_parcel'] ?? 0);
            $courierOther = max($courierTotal - $courierSuccess - $courierCancelled, 0);

            $couriers[] = [
                'name' => (string) ($stats['name'] ?? ucfirst((string) $key)),
                'logo' => (string) ($stats['logo'] ?? ''),
                'total_parcels' => $courierTotal,
                'delivered_parcels' => $courierSuccess,
                'cancelled_parcels' => $courierCancelled,
                'other_parcels' => $courierOther,
                'delivered_pct' => $courierTotal > 0 ? round(($courierSuccess / $courierTotal) * 100, 1) : 0,
                'cancelled_pct' => $courierTotal > 0 ? round(($courierCancelled / $courierTotal) * 100, 1) : 0,
            ];
        }

        usort($couriers, function ($a, $b) {
            return ($b['total_parcels'] ?? 0) <=> ($a['total_parcels'] ?? 0);
        });

        return [
            'source' => 'bdcourier',
            'mobile_number' => $phone,
            'total_parcels' => $totalParcels,
            'total_delivered' => $successParcels,
            'total_cancel' => $cancelledParcels,
            'total_other' => $otherParcels,
            'delivered_pct' => $totalParcels > 0 ? round(($successParcels / $totalParcels) * 100, 1) : 0,
            'cancel_pct' => $totalParcels > 0 ? round(($cancelledParcels / $totalParcels) * 100, 1) : 0,
            'other_pct' => $totalParcels > 0 ? round(($otherParcels / $totalParcels) * 100, 1) : 0,
            'couriers' => $couriers,
            'reports' => $apiData['reports'] ?? [],
        ];
    }
}
