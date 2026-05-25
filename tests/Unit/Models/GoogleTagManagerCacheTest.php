<?php

namespace Tests\Unit\Models;

use App\Models\GoogleTagManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class GoogleTagManagerCacheTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTable();
        Cache::flush();
    }

    public function test_get_active_tags_cached_returns_only_active_tags(): void
    {
        DB::table('google_tag_managers')->insert([
            ['code' => 'AAAA111', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'BBBB222', 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $tags = GoogleTagManager::getActiveTagsCached();

        $this->assertCount(1, $tags);
        $this->assertSame('AAAA111', $tags->first()->code);
    }

    public function test_get_active_codes_cached_normalizes_and_filters_codes(): void
    {
        DB::table('google_tag_managers')->insert([
            ['code' => ' GTM-AAA111 ', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'aaa111', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GTM-BBB_222', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'INVALID CODE', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GTM-CCC333', 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $codes = GoogleTagManager::getActiveCodesCached();

        $this->assertSame(['BBB_222', 'aaa111', 'AAA111'], $codes->all());
    }

    public function test_model_save_and_delete_invalidate_gtm_cache(): void
    {
        $tag = GoogleTagManager::query()->create([
            'code' => 'TAG_OLD',
            'status' => 1,
        ]);

        $cached = GoogleTagManager::getActiveTagsCached();
        $this->assertSame('TAG_OLD', $cached->first()->code);

        $tag->code = 'TAG_NEW';
        $tag->save();

        $freshAfterSave = GoogleTagManager::getActiveTagsCached();
        $this->assertSame('TAG_NEW', $freshAfterSave->first()->code);

        $tag->delete();

        $freshAfterDelete = GoogleTagManager::getActiveTagsCached();
        $this->assertCount(0, $freshAfterDelete);
    }

    public function test_forget_active_tags_cache_forces_code_cache_reload(): void
    {
        DB::table('google_tag_managers')->insert([
            'id' => 1,
            'code' => 'GTM-OLD_1',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstCodes = GoogleTagManager::getActiveCodesCached();
        $this->assertSame(['OLD_1'], $firstCodes->all());

        DB::table('google_tag_managers')->where('id', 1)->update([
            'code' => 'GTM-NEW_2',
            'updated_at' => now(),
        ]);

        $cachedCodes = GoogleTagManager::getActiveCodesCached();
        $this->assertSame(['OLD_1'], $cachedCodes->all());

        GoogleTagManager::forgetActiveTagsCache();
        $freshCodes = GoogleTagManager::getActiveCodesCached();
        $this->assertSame(['NEW_2'], $freshCodes->all());
    }

    public function test_normalize_code_removes_gtm_prefix_and_whitespace(): void
    {
        $this->assertSame('ABC123', GoogleTagManager::normalizeCode(' GTM-ABC123 '));
        $this->assertSame('abc123', GoogleTagManager::normalizeCode('gtm-abc123'));
        $this->assertSame('X_Y-Z', GoogleTagManager::normalizeCode('X_Y-Z'));
    }

    private function createTable(): void
    {
        Schema::dropIfExists('google_tag_managers');

        Schema::create('google_tag_managers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
}
