<?php

namespace Tests\Feature\Admin;

use App\Models\EcomPixel;
use App\Models\GoogleTagManager;
use App\Support\StorefrontCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StorefrontCacheClearCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(StorefrontCache::VERSION_CACHE_KEY);
        Cache::forget(EcomPixel::ACTIVE_PIXELS_CACHE_KEY);
        Cache::forget(EcomPixel::ACTIVE_PIXEL_CODES_CACHE_KEY);
        Cache::forget(GoogleTagManager::ACTIVE_GTM_CACHE_KEY);
        Cache::forget(GoogleTagManager::ACTIVE_GTM_CODES_CACHE_KEY);
    }

    public function test_command_bumps_version_and_clears_tracking_caches(): void
    {
        Cache::forever(StorefrontCache::VERSION_CACHE_KEY, 5);
        Cache::put(EcomPixel::ACTIVE_PIXELS_CACHE_KEY, collect(['pixel-row']), 60);
        Cache::put(EcomPixel::ACTIVE_PIXEL_CODES_CACHE_KEY, collect(['PIXEL_CODE']), 60);
        Cache::put(GoogleTagManager::ACTIVE_GTM_CACHE_KEY, collect(['gtm-row']), 60);
        Cache::put(GoogleTagManager::ACTIVE_GTM_CODES_CACHE_KEY, collect(['GTM_CODE']), 60);

        $this->artisan('storefront:cache:clear')
            ->assertExitCode(0);

        $this->assertSame(6, StorefrontCache::currentVersion());
        $this->assertNull(Cache::get(EcomPixel::ACTIVE_PIXELS_CACHE_KEY));
        $this->assertNull(Cache::get(EcomPixel::ACTIVE_PIXEL_CODES_CACHE_KEY));
        $this->assertNull(Cache::get(GoogleTagManager::ACTIVE_GTM_CACHE_KEY));
        $this->assertNull(Cache::get(GoogleTagManager::ACTIVE_GTM_CODES_CACHE_KEY));
    }

    public function test_command_bump_only_keeps_tracking_caches(): void
    {
        Cache::forever(StorefrontCache::VERSION_CACHE_KEY, 2);
        Cache::put(EcomPixel::ACTIVE_PIXELS_CACHE_KEY, collect(['pixel-row']), 60);
        Cache::put(GoogleTagManager::ACTIVE_GTM_CACHE_KEY, collect(['gtm-row']), 60);

        $this->artisan('storefront:cache:clear --bump-only')
            ->assertExitCode(0);

        $this->assertSame(3, StorefrontCache::currentVersion());
        $this->assertNotNull(Cache::get(EcomPixel::ACTIVE_PIXELS_CACHE_KEY));
        $this->assertNotNull(Cache::get(GoogleTagManager::ACTIVE_GTM_CACHE_KEY));
    }
}
