<?php

namespace Tests\Feature\Stock;

use App\Services\WarehouseStockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class WarehouseStockMovementIntegrityTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createSchema();
        $this->seedCoreRows();
    }

    public function test_reservation_writes_stock_movement_even_without_reference_id(): void
    {
        $service = app(WarehouseStockService::class);
        $service->reserveStock(1, 1, 2.0, null, 'order', 'test reservation');

        $movement = DB::table('stock_movements')
            ->where('warehouse_id', 1)
            ->where('product_id', 1)
            ->where('type', 'reservation')
            ->first();

        $this->assertNotNull($movement);
        $this->assertNull($movement->reference_id);
        $this->assertSame('-2', (string) (int) $movement->quantity);
    }

    private function createSchema(): void
    {
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->string('product_code')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_stock', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('product_id');
            $table->decimal('physical_quantity', 10, 2)->default(0);
            $table->decimal('reserved_quantity', 10, 2)->default(0);
            $table->decimal('available_quantity', 10, 2)->default(0);
            $table->decimal('average_cost', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('branch_id')->nullable();
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->string('type');
            $table->string('reference_type')->nullable();
            $table->unsignedInteger('reference_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('balance_after', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    private function seedCoreRows(): void
    {
        DB::table('warehouses')->insert([
            'id' => 1,
            'branch_id' => 1,
            'name' => 'Main Warehouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => 1,
            'name' => 'Test Product',
            'sku' => 'SKU-1',
            'product_code' => 'P-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouse_stock')->insert([
            'warehouse_id' => 1,
            'product_id' => 1,
            'physical_quantity' => 10,
            'reserved_quantity' => 0,
            'available_quantity' => 10,
            'average_cost' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
