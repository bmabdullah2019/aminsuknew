<?php

namespace App\Http\Middleware;

use Closure;

class CheckReffer
{
    public function handle($request, Closure $next)
    {
        // Skip check for testing environment or safe methods (GET, HEAD, OPTIONS)
        if (app()->environment('testing') || $request->isMethodSafe()) {
            return $next($request);
        }

        $allowedOrigin = rtrim((string) config('app.url'), '/');
        if ($allowedOrigin === '') {
            $allowedOrigin = $request->getSchemeAndHttpHost();
        }

        // For Origin header: only compare scheme://host (not path, per CORS spec)
        $allowedSchemeAndHost = $request->getSchemeAndHttpHost();

        $origin = trim((string) $request->headers->get('origin', ''));
        $referer = trim((string) $request->headers->get('referer', ''));

        // Allow requests without origin/referer headers (same-origin requests)
        if (empty($origin) && empty($referer)) {
            return $next($request);
        }

        // Check if origin matches scheme://host (not path)
        // Check if referer matches full URL (includes path)
        $originAllowed = ! empty($origin) && $origin === $allowedSchemeAndHost;
        $refererAllowed = ! empty($referer) && str_starts_with($referer, $allowedOrigin);

        // Block only if headers are present but don't match allowed origin
        if ((! empty($origin) || ! empty($referer)) && ! $originAllowed && ! $refererAllowed) {
            abort(403, 'Unauthorized cross-origin request.');
        }

        return $next($request);
    }
}
