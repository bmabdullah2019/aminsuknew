<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class Customer
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('customer')->user()) {
            return $next($request);
        }

        return redirect()->route('customer.login');
    }
}
