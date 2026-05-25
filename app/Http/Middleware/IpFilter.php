<?php

namespace App\Http\Middleware;

use App\Models\IpBlock;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class IpFilter
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        try {
            $ipblock = IpBlock::where('ip_no', $request->getClientIp())->first();
        } catch (Throwable $e) {
            Log::error('IP filter lookup failed. Blocking request by default.', [
                'ip' => $request->getClientIp(),
                'error' => $e->getMessage(),
            ]);
            abort(503, 'Security check unavailable. Request denied.');
        }

        if ($ipblock) {
            abort(403, 'You are restricted to access the site. Beacuse '.$ipblock->reason);
        }

        return $next($request);
    }
}
