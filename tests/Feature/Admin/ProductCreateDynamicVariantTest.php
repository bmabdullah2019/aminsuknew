<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\ProductController;
use App\Models\Product;
use App\Services\VariantAttributeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class ProductCreateDynamicVariantTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemorySqliteDatabase();
        $this->createTables();
    }

    public function test_variable_product_creation_uses_catalog_attributes_for_storefront_variant_payload(): void
    {
        DB::table('categories')->insert([
            'id' => 1,
            'name' => 'Apparel',
            'status' => 1,
        ]);

        DB::table('catalog_attributes')->insert([
            [
                'id' => 1,
                'name' => 'Material',
                'slug' => 'material',
                'sort_order' => 10,
                'is_required' => 1,
                'status' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Size',
                'slug' => 'size',
                'sort_order' => 20,
                'is_required' => 1,
                'status' => 1,
            ],
        ]);

        DB::table('catalog_attribute_values')->insert([
            [
                'id' => 11,
                'catalog_attribute_id' => 1,
                'value' => 'Cotton',
                'slug' => 'cotton',
                'meta' => null,
                'sort_order' => 10,
                'status' => 1,
            ],
            [
                'id' => 21,
                'catalog_attribute_id' => 2,
                'value' => 'Large',
                'slug' => 'large',
                'meta' => null,
                'sort_order' => 10,
                'status' => 1,
            ],
        ]);

        $request = Request::create('/admin/products/save', 'POST', [
            'product_type' => 'variable',
            'name' => 'Dynamic Attribute Tee',
            'category_id' => 1,
            'purchase_price' => 700,
            'description' => 'Dynamic variant product',
            'selected_attribute_ids' => [1, 2],
            'variants' => [
                [
                    'attribute_value_map' => [
                        1 => 11,
                        2 => 21,
                    ],
                    'sku_code' => 'TEE-COT-L',
                    'price' => 1250,
                    'existing_images' => [
                        'public/uploads/product/existing-tee.jpg',
                        '/storage/uploads/product/existing-tee.jpg',
                        'public/uploads/product/existing-tee.jpg',
                    ],
                ],
            ],
        ]);

        $session = app('session.store');
        $session->start();
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);

        $response = app(ProductController::class)->store($request);
        $errorBag = $session->get('errors');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNull($errorBag, $errorBag ? json_encode($errorBag->all()) : '');
        $this->assertSame(route('admin.products.index'), $response->getTargetUrl());

        $product = Product::query()->firstOrFail();

        $this->assertSame(1, (int) $product->has_variant);
        $this->assertSame(1, DB::table('product_variants')->count());
        $this->assertDatabaseHas('product_variants', [
            'sku_code' => 'TEE-COT-L',
            'stock' => 0,
        ]);
        $this->assertSame(1, DB::table('variant_images')->count());
        $this->assertDatabaseHas('variant_images', [
            'product_variant_id' => 1,
            'image_path' => 'public/uploads/product/existing-tee.jpg',
            'is_primary' => true,
        ]);
        $this->assertSame(2, DB::table('product_variant_attribute_values')->count());
        $this->assertDatabaseHas('product_variant_attribute_values', [
            'catalog_attribute_id' => 1,
            'catalog_attribute_value_id' => 11,
        ]);
        $this->assertDatabaseHas('product_variant_attribute_values', [
            'catalog_attribute_id' => 2,
            'catalog_attribute_value_id' => 21,
        ]);

        $payload = app(VariantAttributeService::class)->buildProductVariantPayload($product->fresh());

        $this->assertTrue((bool) ($payload['has_variant'] ?? false));
        $this->assertTrue((bool) ($payload['has_dynamic_attributes'] ?? false));
        $this->assertSame(['Material', 'Size'], collect($payload['attribute_groups'] ?? [])->pluck('attribute_name')->all());
        $this->assertSame('Cotton', $payload['variants'][0]['attributes']['material'] ?? null);
        $this->assertSame('Large', $payload['variants'][0]['attributes']['size'] ?? null);
    }

    public function test_variable_product_update_syncs_dynamic_variant_attributes_for_edit_flow(): void
    {
        DB::table('categories')->insert([
            'id' => 1,
            'name' => 'Apparel',
            'status' => 1,
        ]);

        DB::table('catalog_attributes')->insert([
            [
                'id' => 1,
                'name' => 'Material',
                'slug' => 'material',
                'sort_order' => 10,
                'is_required' => 1,
                'status' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Size',
                'slug' => 'size',
                'sort_order' => 20,
                'is_required' => 1,
                'status' => 1,
            ],
        ]);

        DB::table('catalog_attribute_values')->insert([
            [
                'id' => 11,
                'catalog_attribute_id' => 1,
                'value' => 'Cotton',
                'slug' => 'cotton',
                'meta' => null,
                'sort_order' => 10,
                'status' => 1,
            ],
            [
                'id' => 12,
                'catalog_attribute_id' => 1,
                'value' => 'Linen',
                'slug' => 'linen',
                'meta' => null,
                'sort_order' => 20,
                'status' => 1,
            ],
            [
                'id' => 21,
                'catalog_attribute_id' => 2,
                'value' => 'Large',
                'slug' => 'large',
                'meta' => null,
                'sort_order' => 10,
                'status' => 1,
            ],
            [
                'id' => 22,
                'catalog_attribute_id' => 2,
                'value' => 'Medium',
                'slug' => 'medium',
                'meta' => null,
                'sort_order' => 20,
                'status' => 1,
            ],
        ]);

        DB::table('products')->insert([
            'id' => 1,
            'name' => 'Dynamic Attribute Tee',
            'category_id' => 1,
            'purchase_price' => 700,
            'new_price' => 1250,
            'old_price' => 1250,
            'sku' => 'TEE-001',
            'slug' => 'dynamic-attribute-tee',
            'product_code' => 'P0001',
            'description' => 'Initial variable product',
            'status' => 1,
            'has_variant' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variants')->insert([
            'id' => 1,
            'product_id' => 1,
            'sku_code' => 'TEE-COT-L',
            'combination_key' => 'initial-combo',
            'price' => 1250,
            'stock' => 8,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variant_attribute_values')->insert([
            [
                'product_variant_id' => 1,
                'catalog_attribute_id' => 1,
                'catalog_attribute_value_id' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_variant_id' => 1,
                'catalog_attribute_id' => 2,
                'catalog_attribute_value_id' => 21,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('variant_images')->insert([
            'product_variant_id' => 1,
            'image_path' => 'public/uploads/product/existing-tee.jpg',
            'is_primary' => 1,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admin/products/update', 'POST', [
            'id' => 1,
            'product_type' => 'variable',
            'name' => 'Dynamic Attribute Tee',
            'category_id' => 1,
            'purchase_price' => 760,
            'description' => 'Updated variable product',
            'selected_attribute_ids' => [1, 2],
            'variants' => [
                [
                    'id' => 1,
                    'attribute_value_map' => [
                        1 => 12,
                        2 => 22,
                    ],
                    'sku' => 'TEE-LIN-M',
                    'price' => 1420,
                    'existing_images' => [
                        'public/uploads/product/existing-tee.jpg',
                    ],
                ],
            ],
        ]);

        $session = app('session.store');
        $session->start();
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);

        $response = app(ProductController::class)->update($request);
        $errorBag = $session->get('errors');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNull($errorBag, $errorBag ? json_encode($errorBag->all()) : '');
        $this->assertSame(route('admin.products.index'), $response->getTargetUrl());

        $this->assertDatabaseHas('product_variants', [
            'id' => 1,
            'sku_code' => 'TEE-LIN-M',
            'stock' => 8,
        ]);
        $this->assertDatabaseHas('product_variant_attribute_values', [
            'product_variant_id' => 1,
            'catalog_attribute_id' => 1,
            'catalog_attribute_value_id' => 12,
        ]);
        $this->assertDatabaseHas('product_variant_attribute_values', [
            'product_variant_id' => 1,
            'catalog_attribute_id' => 2,
            'catalog_attribute_value_id' => 22,
        ]);
        $this->assertDatabaseMissing('product_variant_attribute_values', [
            'product_variant_id' => 1,
            'catalog_attribute_id' => 1,
            'catalog_attribute_value_id' => 11,
        ]);

        $payload = app(VariantAttributeService::class)->buildProductVariantPayload(Product::query()->findOrFail(1)->fresh());

        $this->assertTrue((bool) ($payload['has_variant'] ?? false));
        $this->assertTrue((bool) ($payload['has_dynamic_attributes'] ?? false));
        $this->assertSame('Linen', $payload['variants'][0]['attributes']['material'] ?? null);
        $this->assertSame('Medium', $payload['variants'][0]['attributes']['size'] ?? null);
    }

    private function createTables(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->unsignedInteger('parent_id')->default(0);
            $table->boolean('status')->default(true);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('subcategory_id')->nullable();
            $table->unsignedInteger('childcategory_id')->nullable();
            $table->unsignedInteger('brand_id')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('new_price', 10, 2)->default(0);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->string('sku')->nullable();
            $table->string('slug')->nullable();
            $table->string('product_code')->nullable();
            $table->string('thumbnail')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('pro_video')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('topsale')->default(false);
            $table->boolean('flashsale')->default(false);
            $table->boolean('feature_product')->default(false);
            $table->boolean('has_variant')->default(false);
            $table->timestamps();
        });

        Schema::create('catalog_attributes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('status')->default(true);
        });

        Schema::create('catalog_attribute_values', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('catalog_attribute_id');
            $table->string('value');
            $table->string('slug');
            $table->json('meta')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('sku_code', 100)->unique();
            $table->string('combination_key')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('age')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->string('barcode', 100)->nullable();
            $table->string('image')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('product_variant_attribute_values', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_variant_id');
            $table->unsignedInteger('catalog_attribute_id');
            $table->unsignedInteger('catalog_attribute_value_id');
            $table->timestamps();
        });

        Schema::create('variant_images', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_variant_id');
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('productimages', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('image')->nullable();
            $table->string('webp_image')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('inventories', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->decimal('quantity_available', 10, 2)->default(0);
            $table->decimal('quantity_reserved', 10, 2)->default(0);
        });

        Schema::create('warehouse_stock', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->decimal('physical_quantity', 10, 2)->default(0);
            $table->decimal('reserved_quantity', 10, 2)->default(0);
            $table->decimal('available_quantity', 10, 2)->default(0);
        });
    }
}
