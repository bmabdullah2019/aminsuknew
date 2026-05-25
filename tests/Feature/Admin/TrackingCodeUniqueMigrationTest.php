<?php

namespace Tests\Feature\Admin;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class TrackingCodeUniqueMigrationTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
    }

    public function test_migration_normalizes_deduplicates_and_adds_unique_indexes(): void
    {
        DB::table('ecom_pixels')->insert([
            ['id' => 1, 'code' => ' PIXEL_DUP ', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'PIXEL_DUP', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'code' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('google_tag_managers')->insert([
            ['id' => 1, 'code' => ' GTM-AAA1 ', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'aaa1', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'code' => 'GTM-AAA1', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'code' => '', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration = $this->loadMigration();
        $migration->up();

        $this->assertTrue($this->hasSqliteIndex('ecom_pixels', 'ecom_pixels_code_unique'));
        $this->assertTrue($this->hasSqliteIndex('google_tag_managers', 'google_tag_managers_code_unique'));

        $pixel1 = DB::table('ecom_pixels')->where('id', 1)->first();
        $pixel2 = DB::table('ecom_pixels')->where('id', 2)->first();
        $pixel3 = DB::table('ecom_pixels')->where('id', 3)->first();

        $this->assertSame('PIXEL_DUP', (string) $pixel1->code);
        $this->assertSame('PIXEL_DUP_D2', (string) $pixel2->code);
        $this->assertSame(0, (int) $pixel2->status);
        $this->assertSame('CODE_3', (string) $pixel3->code);

        $gtm1 = DB::table('google_tag_managers')->where('id', 1)->first();
        $gtm2 = DB::table('google_tag_managers')->where('id', 2)->first();
        $gtm3 = DB::table('google_tag_managers')->where('id', 3)->first();
        $gtm4 = DB::table('google_tag_managers')->where('id', 4)->first();

        $this->assertSame('AAA1', (string) $gtm1->code);
        $this->assertSame('aaa1_D2', (string) $gtm2->code);
        $this->assertSame(0, (int) $gtm2->status);
        $this->assertSame('AAA1_D3', (string) $gtm3->code);
        $this->assertSame(0, (int) $gtm3->status);
        $this->assertSame('CODE_4', (string) $gtm4->code);

        $duplicatePixels = DB::table('ecom_pixels')
            ->selectRaw('LOWER(code) as code_key, COUNT(*) as total')
            ->groupBy('code_key')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertSame(0, $duplicatePixels);

        $duplicateGtm = DB::table('google_tag_managers')
            ->selectRaw('LOWER(code) as code_key, COUNT(*) as total')
            ->groupBy('code_key')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertSame(0, $duplicateGtm);
    }

    public function test_migration_down_drops_unique_indexes(): void
    {
        $migration = $this->loadMigration();
        $migration->up();

        $this->assertTrue($this->hasSqliteIndex('ecom_pixels', 'ecom_pixels_code_unique'));
        $this->assertTrue($this->hasSqliteIndex('google_tag_managers', 'google_tag_managers_code_unique'));

        $migration->down();

        $this->assertFalse($this->hasSqliteIndex('ecom_pixels', 'ecom_pixels_code_unique'));
        $this->assertFalse($this->hasSqliteIndex('google_tag_managers', 'google_tag_managers_code_unique'));
    }

    private function loadMigration(): object
    {
        /** @var object $migration */
        $migration = require base_path('database/migrations/2026_03_12_170100_harden_tracking_code_uniques.php');

        return $migration;
    }

    private function hasSqliteIndex(string $table, string $indexName): bool
    {
        $rows = DB::select("PRAGMA index_list('{$table}')");
        foreach ($rows as $row) {
            if (isset($row->name) && (string) $row->name === $indexName) {
                return true;
            }
        }

        return false;
    }

    private function createTables(): void
    {
        Schema::dropIfExists('google_tag_managers');
        Schema::dropIfExists('ecom_pixels');

        Schema::create('ecom_pixels', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('google_tag_managers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
}
