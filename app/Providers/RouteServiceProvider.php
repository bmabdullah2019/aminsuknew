<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('customer-login', function (Request $request) {
            $key = strtolower((string) $request->input('phone', 'guest'));

            return Limit::perMinute(10)->by($request->ip().'|'.$key);
        });

        RateLimiter::for('checkout-orders', function (Request $request) {
            $phone = strtolower(trim((string) $request->input('phone', 'guest')));

            return [
                Limit::perMinute(12)->by($request->ip().'|'.$phone),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        RateLimiter::for('payment-callbacks', function (Request $request) {
            $orderRef = (string) ($request->input('order_id') ?? $request->input('orderId') ?? 'unknown');

            return [
                Limit::perMinute(120)->by($request->ip()),
                Limit::perMinute(30)->by($request->ip().'|'.$orderRef),
            ];
        });

        RateLimiter::for('checkout-otp', function (Request $request) {
            $phone = strtolower(trim((string) $request->input('phone', 'guest')));

            return [
                Limit::perMinute(6)->by($request->ip().'|'.$phone),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $phone = strtolower(trim((string) $request->input('phone', (string) $request->session()->get('verify_phone', 'guest'))));

            return Limit::perMinute(8)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('otp-resend', function (Request $request) {
            $phone = strtolower(trim((string) $request->input('phone', (string) $request->session()->get('verify_phone', 'guest'))));

            return Limit::perMinute(4)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('forgot-otp-request', function (Request $request) {
            $phone = strtolower(trim((string) $request->input('phone', 'guest')));

            return Limit::perMinute(5)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('forgot-otp-verify', function (Request $request) {
            $phone = strtolower(trim((string) $request->input('phone', (string) $request->session()->get('verify_phone', 'guest'))));

            return Limit::perMinute(6)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('order-tracking', function (Request $request) {
            $phone = strtolower(trim((string) $request->query('phone', '')));
            $invoice = strtolower(trim((string) $request->query('invoice_id', '')));
            $identity = $phone !== '' || $invoice !== '' ? $phone.'|'.$invoice : 'guest';

            return Limit::perMinute(20)->by($request->ip().'|'.$identity);
        });
    }
}
