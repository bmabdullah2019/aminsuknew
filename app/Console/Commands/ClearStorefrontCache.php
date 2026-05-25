<?php

namespace App\Console\Commands;

use App\Models\EcomPixel;
use App\Models\GoogleTagManager;
use App\Support\StorefrontCache;
use Illuminate\Console\Command;

class ClearStorefrontCache extends Command
{
    protected $signature = 'storefront:cache:clear
                            {--bump-only : Only bump versioned storefront cache namespace}';

    protected $description = 'Clear storefront caches safely by rotating cache namespace and flushing tracking caches.';

    public function handle(): int
    {
        $oldVersion = StorefrontCache::currentVersion();
        $newVersion = StorefrontCache::bumpVersion();

        $this->info("Storefront cache version bumped from {$oldVersion} to {$newVersion}.");

        if (! $this->option('bump-only')) {
            EcomPixel::forgetActivePixelsCache();
            GoogleTagManager::forgetActiveTagsCache();
            $this->info('Tracking caches cleared: ecom_pixels and google_tag_managers.');
        }

        return self::SUCCESS;
    }
}
