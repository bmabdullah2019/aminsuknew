<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GoogleTagManager extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public const ACTIVE_GTM_CACHE_KEY = 'storefront:gtm:active:v1';

    public const ACTIVE_GTM_CODES_CACHE_KEY = 'storefront:gtm:active-codes:v1';

    public const ACTIVE_GTM_CACHE_MINUTES = 10;

    public static function getActiveTagsCached()
    {
        return Cache::remember(
            self::ACTIVE_GTM_CACHE_KEY,
            now()->addMinutes(self::ACTIVE_GTM_CACHE_MINUTES),
            static fn () => static::query()
                ->where('status', 1)
                ->select('id', 'code', 'status')
                ->orderBy('id', 'DESC')
                ->get()
        );
    }

    public static function getActiveCodesCached()
    {
        return Cache::remember(
            self::ACTIVE_GTM_CODES_CACHE_KEY,
            now()->addMinutes(self::ACTIVE_GTM_CACHE_MINUTES),
            static fn () => static::getActiveTagsCached()
                ->pluck('code')
                ->map(static fn ($code) => static::normalizeCode((string) $code))
                ->filter(static fn (string $code) => preg_match('/^[A-Za-z0-9_-]+$/', $code) === 1)
                ->unique()
                ->values()
        );
    }

    public static function forgetActiveTagsCache(): void
    {
        Cache::forget(self::ACTIVE_GTM_CACHE_KEY);
        Cache::forget(self::ACTIVE_GTM_CODES_CACHE_KEY);
    }

    public static function normalizeCode(string $code): string
    {
        $normalized = trim($code);
        $normalized = preg_replace('/^GTM-/i', '', $normalized) ?? $normalized;

        return trim($normalized);
    }

    protected static function booted(): void
    {
        static::saved(static function (): void {
            static::forgetActiveTagsCache();
        });

        static::deleted(static function (): void {
            static::forgetActiveTagsCache();
        });
    }
}
