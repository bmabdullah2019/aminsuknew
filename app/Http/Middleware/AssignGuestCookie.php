<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class AssignGuestCookie
{
    public function handle(Request $request, Closure $next)
    {
        $cookieName = 'partial_device_id_v1';
        if (! $request->hasCookie($cookieName)) {
            $id = (string) Str::uuid();
            // minutes: 43200 = 30 days
            Cookie::queue($cookieName, $id, 43200);
            $request->merge([$cookieName => $id]);
        }

        return $next($request);
    }
}
