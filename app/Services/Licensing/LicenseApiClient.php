<?php

namespace App\Services\Licensing;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LicenseApiClient
{
    public function verify(string $domain): LicenseValidationResult
    {
        $verifyUrl = (string) config('license.server.verify_url');
        $licenseKey = (string) config('license.client.license_key');
        $timeoutSeconds = (int) config('license.server.timeout_seconds', 3);

        $appKey = hash('sha256', $licenseKey);
        $payload = $domain.'|'.$appKey.'|'.(string) config('license.signature_context');
        $signature = hash_hmac('sha256', $payload, $licenseKey);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout($timeoutSeconds)
                ->post($verifyUrl, [
                    'domain' => $domain,
                    'app_key' => $appKey,
                    'signature' => $signature,
                ]);
        } catch (Throwable $e) {
            Log::warning('License server unreachable', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            throw new LicenseServerUnavailableException('License server unreachable', 0, $e);
        }

        if ($response->serverError()) {
            Log::warning('License server error response', [
                'domain' => $domain,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new LicenseServerUnavailableException('License server error response: '.$response->status());
        }

        if (! $response->ok()) {
            Log::warning('License server rejected request', [
                'domain' => $domain,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return new LicenseValidationResult('inactive', null, 'License verification failed.');
        }

        $status = (string) ($response->json('status') ?? 'inactive');
        $message = (string) ($response->json('message') ?? '');

        $expiresAt = null;
        $expiresAtRaw = $response->json('expires_at');
        if (! empty($expiresAtRaw)) {
            try {
                $expiresAt = Carbon::parse($expiresAtRaw);
            } catch (Throwable $e) {
                Log::warning('License server returned invalid expires_at', [
                    'domain' => $domain,
                    'expires_at' => $expiresAtRaw,
                    'error' => $e->getMessage(),
                ]);

                return new LicenseValidationResult('inactive', null, 'License verification failed.');
            }
        }

        return new LicenseValidationResult($status, $expiresAt, $message);
    }
}
