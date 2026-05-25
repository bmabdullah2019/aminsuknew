<?php

namespace Tests\Feature\Inventory;

use App\Http\Controllers\Admin\InventoryController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class InventoryVariantApiTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
    }

    public function test_get_product_variants_builds_variants_from_legacy_colors_when_missing(): void
    {
        DB::table('products')->insert([
            'id' => 224,
            'name' => 'Legacy Variant Product',
            'status' => 1,
            'has_variant' => 0,
            'new_price' => 450,
            'purchase_price' => 250,
            'sku' => 'LVP-224',
            'product_code' => 'P224',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('colors')->insert([
            'id' => 74,
            'colorName' => 'Off White',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('productcolors')->insert([
            'product_id' => 224,
            'color_id' => 74,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(InventoryController::class);
        $request = Request::create('/admin/inventory/api/product-variants', 'GET', [
            'product_id' => 224,
        ]);

        /** @var JsonResponse $response */
        $response = $controller->getProductVariants($request);
        $payload = $response->getData(true);

        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertTrue((bool) ($payload['has_variant'] ?? false));
        $this->assertCount(1, $payload['variants'] ?? []);
        $this->assertSame('Off White', $payload['variants'][0]['color'] ?? null);
        $this->assertSame('', $payload['variants'][0]['size'] ?? null);

        $this->assertSame(1, DB::table('product_variants')->count());
        $created = DB::table('product_variants')->first();
        $this->assertNotNull($created);
        $this->assertSame('Off White', $created->color);
        $this->assertSame('active', $created->status);

        // Calling again should not duplicate generated variants.
        $controller->getProductVariants($request);
        $this->assertSame(1, DB::table('product_variants')->count());
    }

    private function createTables(): void
    {
        Schema::dropIfExists('productsizes');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('productcolors');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->boolean('has_variant')->default(false);
            $table->decimal('new_price', 10, 2)->default(0);
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->string('sku')->nullable();
            $table->string('product_code')->nullable();
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('sku_code', 100)->unique();
            $table->string('color', 50)->nullable();
            $table->string('size', 50)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('colorName')->nullable();
            $table->timestamps();
        });

        Schema::create('productcolors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('color_id');
            $table->timestamps();
        });

        Schema::create('sizes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sizeName')->nullable();
            $table->timestamps();
        });

        Schema::create('productsizes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('size_id');
            $table->timestamps();
        });
    }
}
