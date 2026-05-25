<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\InventoryController;
use App\Services\VariantAttributeService;
use App\Services\VariantStockService;
use App\Services\WarehouseStockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class InventoryIndexTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemorySqliteDatabase();
        $this->createTables();
    }

    public function test_index_lists_real_stock_rows_even_when_variant_id_is_present(): void
    {
        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'Main Warehouse',
            'is_active' => 1,
        ]);

        DB::table('products')->insert([
            ['id' => 100, 'name' => 'Variant Stock Product', 'sku' => 'VAR-100', 'status' => 1],
            ['id' => 101, 'name' => 'Simple Stock Product', 'sku' => 'SIM-101', 'status' => 1],
            ['id' => 102, 'name' => 'No Stock Product', 'sku' => 'NON-102', 'status' => 1],
        ]);

        DB::table('warehouse_stock')->insert([
            [
                'id' => 1,
                'warehouse_id' => 1,
                'product_id' => 100,
                'product_variant_id' => 999,
                'physical_quantity' => 12,
                'available_quantity' => 10,
                'reserved_quantity' => 2,
                'reorder_point' => 5,
            ],
            [
                'id' => 2,
                'warehouse_id' => 1,
                'product_id' => 101,
                'product_variant_id' => null,
                'physical_quantity' => 4,
                'available_quantity' => 4,
                'reserved_quantity' => 0,
                'reorder_point' => 5,
            ],
        ]);

        $stocks = $this->inventoryIndexData();

        $this->assertSame(2, $stocks->total());
        $this->assertSame([100, 101], collect($stocks->items())->pluck('product_id')->all());
    }

    public function test_index_pagination_keeps_active_filters(): void
    {
        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'Main Warehouse',
            'is_active' => 1,
        ]);

        $products = [];
        $stocks = [];

        for ($i = 1; $i <= 55; $i++) {
            $products[] = [
                'id' => $i,
                'name' => 'Paged Product '.$i,
                'sku' => 'SKU-'.$i,
                'status' => 1,
            ];

            $stocks[] = [
                'id' => $i,
                'warehouse_id' => 1,
                'product_id' => $i,
                'product_variant_id' => null,
                'physical_quantity' => 10,
                'available_quantity' => 10,
                'reserved_quantity' => 0,
                'reorder_point' => 5,
            ];
        }

        DB::table('products')->insert($products);
        DB::table('warehouse_stock')->insert($stocks);

        $paginator = $this->inventoryIndexData([
            'search' => 'SKU-',
            'warehouse_id' => 1,
            'stock_status' => 'in_stock',
        ]);

        $this->assertSame(55, $paginator->total());
        $this->assertStringContainsString('search=SKU-', $paginator->url(2));
        $this->assertStringContainsString('warehouse_id=1', $paginator->url(2));
        $this->assertStringContainsString('stock_status=in_stock', $paginator->url(2));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function inventoryIndexData(array $query = []): LengthAwarePaginator
    {
        $request = Request::create('http://localhost/admin/inventory', 'GET', $query);
        $this->app->instance('request', $request);

        $view = $this->makeController()->index($request);

        /** @var LengthAwarePaginator $stocks */
        $stocks = $view->getData()['stocks'];

        return $stocks;
    }

    private function makeController(): InventoryController
    {
        return new InventoryController(
            new WarehouseStockService,
            new VariantStockService,
            new VariantAttributeService
        );
    }

    private function createTables(): void
    {
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('status')->default(true);
        });

        Schema::create('productimages', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('image')->nullable();
            $table->string('webp_image')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('warehouse_stock', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->decimal('physical_quantity', 18, 2)->default(0);
            $table->decimal('available_quantity', 18, 2)->default(0);
            $table->decimal('reserved_quantity', 18, 2)->default(0);
            $table->decimal('reorder_point', 18, 2)->default(5);
        });
    }
}
