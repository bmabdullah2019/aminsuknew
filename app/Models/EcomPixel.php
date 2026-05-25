<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EcomPixel extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public const ACTIVE_PIXELS_CACHE_KEY = 'storefront:pixels:active:v1';

    public const ACTIVE_PIXEL_CODES_CACHE_KEY = 'storefront:pixels:active-codes:v1';

    public const ACTIVE_PIXELS_CACHE_MINUTES = 10;

    public static function getActivePixelsCached()
    {
        return Cache::remember(
            self::ACTIVE_PIXELS_CACHE_KEY,
            now()->addMinutes(self::ACTIVE_PIXELS_CACHE_MINUTES),
            static fn () => static::query()
                ->where('status', 1)
                ->select('id', 'code', 'status')
                ->orderBy('id', 'DESC')
                ->get()
        );
    }

    public static function normalizeCode(string $code): string
    {
        return trim($code);
    }

    public static function getActiveCodesCached()
    {
        return Cache::remember(
            self::ACTIVE_PIXEL_CODES_CACHE_KEY,
            now()->addMinutes(self::ACTIVE_PIXELS_CACHE_MINUTES),
            static fn () => static::getActivePixelsCached()
                ->pluck('code')
                ->map(static fn ($code) => static::normalizeCode((string) $code))
                ->filter(static fn (string $code) => preg_match('/^[A-Za-z0-9_-]+$/', $code) === 1)
                ->unique()
                ->values()
        );
    }

    public static function forgetActivePixelsCache(): void
    {
        Cache::forget(self::ACTIVE_PIXELS_CACHE_KEY);
        Cache::forget(self::ACTIVE_PIXEL_CODES_CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(static function (): void {
            static::forgetActivePixelsCache();
        });

        static::deleted(static function (): void {
            static::forgetActivePixelsCache();
        });
    }
}
