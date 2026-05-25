<?php

$legacyServerUrl = env('LICENSE_SERVER_URL');
$verifyUrl = env('LICENSE_SERVER_VERIFY_URL');

if (($verifyUrl === null || $verifyUrl === '') && is_string($legacyServerUrl) && $legacyServerUrl !== '') {
    $verifyUrl = rtrim($legacyServerUrl, '/').'/verify-license';
}

return [
    // Toggle runtime domain-license enforcement.
    // Default: disabled unless explicitly enabled via env.
    'enforcement' => [
        'enabled' => (bool) env('LICENSE_ENFORCEMENT_ENABLED', false),
    ],

    // License Server settings (Master system)
    'server' => [
        // Example: https://licenses.example.com/api/verify-license
        'verify_url' => $verifyUrl,
        'timeout_seconds' => (int) env('LICENSE_SERVER_TIMEOUT_SECONDS', 3),
    ],

    // Client app credentials (this installation)
    'client' => [
        // Shared secret for signing requests. Must match the domain record on the License Server.
        'license_key' => env('LICENSE_KEY'),
    ],

    // Cache how long we treat a successful validation as "fresh".
    'cache_ttl_seconds' => (int) env('LICENSE_CACHE_TTL_SECONDS', 18 * 60 * 60),

    // Grace window: if the license server is temporarily unreachable, we can keep running if we have a recent
    // last-known-good validation that hasn't expired.
    'grace_ttl_seconds' => (int) env('LICENSE_GRACE_TTL_SECONDS', 72 * 60 * 60),

    // Signature context to prevent naive copy/paste signing between products.
    // This value must match the License Server implementation.
    'signature_context' => 'lic_v1',
];
