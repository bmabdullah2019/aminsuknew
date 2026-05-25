<?php

namespace Tests\Unit\Models;

use App\Models\EcomPixel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class EcomPixelCacheTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTable();
        Cache::flush();
    }

    public function test_get_active_pixels_cached_returns_only_active_pixels(): void
    {
        DB::table('ecom_pixels')->insert([
            ['code' => 'PIXEL_ACTIVE', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PIXEL_INACTIVE', 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $pixels = EcomPixel::getActivePixelsCached();

        $this->assertCount(1, $pixels);
        $this->assertSame('PIXEL_ACTIVE', $pixels->first()->code);
    }

    public function test_get_active_codes_cached_filters_and_deduplicates_codes(): void
    {
        DB::table('ecom_pixels')->insert([
            ['code' => ' PIXEL_1 ', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PIXEL_1', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PIXEL-2', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'INVALID CODE', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PIXEL_3', 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $codes = EcomPixel::getActiveCodesCached();

        $this->assertSame(['PIXEL-2', 'PIXEL_1'], $codes->all());
    }

    public function test_normalize_code_trims_whitespace(): void
    {
        $this->assertSame('PIXEL_A', EcomPixel::normalizeCode('  PIXEL_A  '));
    }

    public function test_forget_active_pixels_cache_forces_reload(): void
    {
        DB::table('ecom_pixels')->insert([
            'id' => 1,
            'code' => 'PIXEL_OLD',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstRead = EcomPixel::getActivePixelsCached();
        $this->assertSame('PIXEL_OLD', $firstRead->first()->code);

        DB::table('ecom_pixels')->where('id', 1)->update([
            'code' => 'PIXEL_NEW',
            'updated_at' => now(),
        ]);

        $cachedRead = EcomPixel::getActivePixelsCached();
        $this->assertSame('PIXEL_OLD', $cachedRead->first()->code);

        EcomPixel::forgetActivePixelsCache();
        $freshRead = EcomPixel::getActivePixelsCached();
        $this->assertSame('PIXEL_NEW', $freshRead->first()->code);
    }

    public function test_forget_active_pixels_cache_forces_code_cache_reload(): void
    {
        DB::table('ecom_pixels')->insert([
            'id' => 1,
            'code' => 'PIXEL_OLD',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstCodes = EcomPixel::getActiveCodesCached();
        $this->assertSame(['PIXEL_OLD'], $firstCodes->all());

        DB::table('ecom_pixels')->where('id', 1)->update([
            'code' => 'PIXEL_NEW',
            'updated_at' => now(),
        ]);

        $cachedCodes = EcomPixel::getActiveCodesCached();
        $this->assertSame(['PIXEL_OLD'], $cachedCodes->all());

        EcomPixel::forgetActivePixelsCache();
        $freshCodes = EcomPixel::getActiveCodesCached();
        $this->assertSame(['PIXEL_NEW'], $freshCodes->all());
    }

    public function test_model_save_automatically_invalidates_active_pixels_cache(): void
    {
        $pixel = EcomPixel::query()->create([
            'code' => 'PIXEL_A',
            'status' => 1,
        ]);

        $cached = EcomPixel::getActivePixelsCached();
        $this->assertSame('PIXEL_A', $cached->first()->code);

        $pixel->code = 'PIXEL_B';
        $pixel->save();

        $fresh = EcomPixel::getActivePixelsCached();
        $this->assertSame('PIXEL_B', $fresh->first()->code);
    }

    public function test_model_delete_automatically_invalidates_active_pixels_cache(): void
    {
        $pixel = EcomPixel::query()->create([
            'code' => 'PIXEL_TO_DELETE',
            'status' => 1,
        ]);

        $cached = EcomPixel::getActivePixelsCached();
        $this->assertCount(1, $cached);

        $pixel->delete();

        $fresh = EcomPixel::getActivePixelsCached();
        $this->assertCount(0, $fresh);
    }

    private function createTable(): void
    {
        Schema::dropIfExists('ecom_pixels');

        Schema::create('ecom_pixels', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
}
