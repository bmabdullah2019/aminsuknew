<?php

use App\Services\Licensing\LicensedDomainGuard;

if (! function_exists('license_tick')) {
    function license_tick(): void
    {
        app(LicensedDomainGuard::class)->touch();
    }
}
