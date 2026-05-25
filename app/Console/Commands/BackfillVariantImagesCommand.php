<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use App\Models\VariantImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillVariantImagesCommand extends Command
{
    protected $signature = 'variants:backfill-images';

    protected $description = 'Backfill legacy product_variants.image into variant_images';

    public function handle(): int
    {
        $updated = 0;

        ProductVariant::query()
            ->whereNotNull('image')
            ->where('image', '<>', '')
            ->with('variantImages')
            ->chunkById(100, function ($variants) use (&$updated) {
                foreach ($variants as $variant) {
                    if ($variant->variantImages->isNotEmpty()) {
                        $this->ensureSinglePrimary($variant->id);
                        continue;
                    }

                    $normalized = $this->normalizePath((string) $variant->image);
                    if ($normalized === '') {
                        continue;
                    }

                    DB::transaction(function () use ($variant, $normalized, &$updated) {
                        VariantImage::query()->create([
                            'product_variant_id' => $variant->id,
                            'image_path' => $normalized,
                            'is_primary' => true,
                            'sort_order' => 1,
                        ]);

                        $variant->image = 'storage/'.ltrim($normalized, '/');
                        $variant->save();
                        $updated++;
                    });
                }
            });

        $this->info("Backfill completed. Updated variants: {$updated}");

        return self::SUCCESS;
    }

    private function ensureSinglePrimary(int $variantId): void
    {
        $images = VariantImage::query()
            ->where('product_variant_id', $variantId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($images->isEmpty()) {
            return;
        }

        $primary = $images->firstWhere('is_primary', true) ?? $images->first();
        VariantImage::query()
            ->where('product_variant_id', $variantId)
            ->update(['is_primary' => false]);

        $primary->is_primary = true;
        $primary->save();
    }

    private function normalizePath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, 'storage/')) {
            return Str::after($normalized, 'storage/');
        }

        if (Str::startsWith($normalized, 'public/')) {
            return Str::after($normalized, 'public/');
        }

        return $normalized;
    }
}
