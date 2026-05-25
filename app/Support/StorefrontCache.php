<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class StorefrontCache
{
    public const VERSION_CACHE_KEY = 'storefront:cache-version:v1';

    public static function versionedKey(string $baseKey): string
    {
        return 'storefront:v'.static::currentVersion().':'.$baseKey;
    }

    public static function currentVersion(): int
    {
        $version = Cache::get(self::VERSION_CACHE_KEY);
        if (is_numeric($version) && (int) $version > 0) {
            return (int) $version;
        }

        Cache::forever(self::VERSION_CACHE_KEY, 1);

        return 1;
    }

    public static function bumpVersion(): int
    {
        $nextVersion = static::currentVersion() + 1;
        Cache::forever(self::VERSION_CACHE_KEY, $nextVersion);

        return $nextVersion;
    }
}
