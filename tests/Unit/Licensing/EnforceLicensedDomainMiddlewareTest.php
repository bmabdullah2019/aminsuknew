<?php

namespace Tests\Unit\Licensing;

use App\Http\Middleware\EnforceLicensedDomain;
use App\Services\Licensing\LicensedDomainGuard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class EnforceLicensedDomainMiddlewareTest extends TestCase
{
    public function test_it_skips_enforcement_when_feature_is_disabled(): void
    {
        config(['license.enforcement.enabled' => false]);

        $guard = Mockery::mock(LicensedDomainGuard::class);
        $guard->shouldNotReceive('enforce');
        $this->app->instance(LicensedDomainGuard::class, $guard);

        $middleware = new EnforceLicensedDomain;
        $request = Request::create('/health', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('ok', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_calls_enforcement_when_feature_is_enabled(): void
    {
        config(['license.enforcement.enabled' => true]);

        $guard = Mockery::mock(LicensedDomainGuard::class);
        $guard->shouldReceive('enforce')->once();
        $this->app->instance(LicensedDomainGuard::class, $guard);

        $middleware = new EnforceLicensedDomain;
        $request = Request::create('/health', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('ok', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }
}
