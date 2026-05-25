<?php

namespace Tests\Unit\Support;

use App\Support\StorefrontCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StorefrontCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(StorefrontCache::VERSION_CACHE_KEY);
    }

    public function test_current_version_defaults_to_one(): void
    {
        $this->assertSame(1, StorefrontCache::currentVersion());
    }

    public function test_versioned_key_uses_current_version(): void
    {
        $key = StorefrontCache::versionedKey('storefront:categories:menu:v1');
        $this->assertSame('storefront:v1:storefront:categories:menu:v1', $key);
    }

    public function test_bump_version_changes_versioned_key_namespace(): void
    {
        $before = StorefrontCache::versionedKey('sample-key');
        $newVersion = StorefrontCache::bumpVersion();
        $after = StorefrontCache::versionedKey('sample-key');

        $this->assertSame(2, $newVersion);
        $this->assertNotSame($before, $after);
        $this->assertSame('storefront:v2:sample-key', $after);
    }
}
