<?php

namespace App\Http\Middleware;

use App\Services\Licensing\LicensedDomainGuard;
use Closure;
use Illuminate\Http\Request;

class EnforceLicensedDomain
{
    public function handle(Request $request, Closure $next)
    {
        if (! (bool) config('license.enforcement.enabled', false)) {
            return $next($request);
        }

        app(LicensedDomainGuard::class)->enforce();

        return $next($request);
    }
}
