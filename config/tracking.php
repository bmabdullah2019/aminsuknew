<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Purchase Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Enterprise-level configuration for server-side purchase tracking integrations.
    |
    */

    'ga4' => [
        'enabled' => env('TRACKING_GA4_ENABLED', true),
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'api_secret' => env('GA4_API_SECRET'),
        'endpoint' => env('TRACKING_GA4_ENDPOINT', 'https://www.google-analytics.com/mp/collect'),
        'debug_endpoint' => env('TRACKING_GA4_DEBUG_ENDPOINT', 'https://www.google-analytics.com/debug/mp/collect'),
    ],

    'facebook' => [
        'enabled' => env('TRACKING_FACEBOOK_ENABLED', true),
        'capi_access_token' => env('FACEBOOK_CAPI_ACCESS_TOKEN'),
        'api_version' => env('TRACKING_FACEBOOK_API_VERSION', 'v19.0'),
        'endpoint' => env('TRACKING_FACEBOOK_ENDPOINT', 'https://graph.facebook.com'),
    ],

    'tiktok' => [
        'enabled' => env('TRACKING_TIKTOK_ENABLED', true),
        'pixel_id' => env('TIKTOK_PIXEL_ID'),
        'access_token' => env('TIKTOK_EVENTS_API_ACCESS_TOKEN'),
        'api_version' => env('TRACKING_TIKTOK_API_VERSION', 'v1.3'),
        'endpoint' => env('TRACKING_TIKTOK_ENDPOINT', 'https://business-api.tiktok.com/open_api'),
    ],

    'fallback' => [
        'enable_client_fallback' => env('TRACKING_CLIENT_FALLBACK_ENABLED', true),
        'cookie_name' => env('TRACKING_FALLBACK_COOKIE_NAME', 'purchase_tracked_'),
    ],

    'queue' => [
        'connection' => env('TRACKING_QUEUE_CONNECTION', 'sync'), // Use 'database' or 'redis' in production
        'queue' => env('TRACKING_QUEUE_NAME', 'tracking'),
    ],

    'retry' => [
        'max_retries' => env('TRACKING_MAX_RETRIES', 3),
        'delay_seconds' => env('TRACKING_RETRY_DELAY_SECONDS', 5),
    ],
];
