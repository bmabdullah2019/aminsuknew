<?php

namespace App\Services\Licensing;

use App\Exceptions\UnauthorizedInstallationException;
use Illuminate\Support\Facades\Cache;

class LicensedDomainGuard
{
    private DomainNormalizer $normalizer;

    private LicenseApiClient $client;

    public function __construct(DomainNormalizer $normalizer, LicenseApiClient $client)
    {
        $this->normalizer = $normalizer;
        $this->client = $client;
    }

    /**
     * Main enforcement entry point.
     *
     * - Validates on every request, but hits the remote server only when cache is stale.
     * - Fails closed when misconfigured.
     */
    public function enforce(): void
    {
        if (! (bool) config('license.enforcement.enabled', false)) {
            return;
        }

        if (app()->runningInConsole() || app()->runningUnitTests()) {
            return;
        }

        if (app()->environment(['local', 'testing'])) {
            return;
        }

        $host = request()->getHost();

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test')) {
            return;
        }

        $domain = $this->normalizer->normalize($host);

        if ($domain === '') {
            throw UnauthorizedInstallationException::misconfigured('unknown');
        }

        $this->ensureConfigured($domain);

        $result = $this->getLicenseForDomain($domain);

        if ($result->isActive() && ! $result->isExpired()) {
            return;
        }

        throw UnauthorizedInstallationException::forDomain($domain);
    }

    /**
     * Extra enforcement layer that can be called from views/helpers to make removal harder.
     */
    public function touch(): void
    {
        if (! (bool) config('license.enforcement.enabled', false)) {
            return;
        }

        if (app()->runningInConsole() || app()->runningUnitTests()) {
            return;
        }

        if (app()->environment(['local', 'testing'])) {
            return;
        }
        if (random_int(1, 100) <= 20) {
            $this->enforce();
        }
    }

    private function ensureConfigured(string $domain): void
    {
        $verifyUrl = (string) config('license.server.verify_url');
        $licenseKey = (string) config('license.client.license_key');

        if ($verifyUrl === '' || $licenseKey === '') {
            throw UnauthorizedInstallationException::misconfigured($domain);
        }
    }

    private function getLicenseForDomain(string $domain): LicenseValidationResult
    {
        $freshKey = $this->freshCacheKey($domain);
        $lastGoodKey = $this->lastGoodCacheKey($domain);

        $fresh = Cache::get($freshKey);
        if (is_array($fresh)) {
            return LicenseValidationResult::fromCacheArray($fresh);
        }

        try {
            $remote = $this->client->verify($domain);
        } catch (LicenseServerUnavailableException $e) {
            $lastGood = Cache::get($lastGoodKey);
            if (is_array($lastGood)) {
                return LicenseValidationResult::fromCacheArray($lastGood);
            }

            // No grace available.
            throw UnauthorizedInstallationException::forDomain($domain);
        }

        if ($remote->isActive() && ! $remote->isExpired()) {
            Cache::put($freshKey, $remote->toCacheArray(), (int) config('license.cache_ttl_seconds'));
            Cache::put($lastGoodKey, $remote->toCacheArray(), (int) config('license.grace_ttl_seconds'));

            return $remote;
        }

        // Fail closed: clear cache and return inactive.
        Cache::forget($freshKey);
        Cache::forget($lastGoodKey);

        return $remote;
    }

    private function freshCacheKey(string $domain): string
    {
        return 'license:fresh:'.sha1($domain);
    }

    private function lastGoodCacheKey(string $domain): string
    {
        return 'license:last_good:'.sha1($domain);
    }
}
