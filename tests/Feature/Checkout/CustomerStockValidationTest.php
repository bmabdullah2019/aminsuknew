<?php

namespace Tests\Feature\Checkout;

use App\Http\Controllers\Frontend\CustomerController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use ReflectionMethod;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class CustomerStockValidationTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        Session::start();
        \Cart::instance('shopping')->destroy();
    }

    protected function tearDown(): void
    {
        \Cart::instance('shopping')->destroy();
        parent::tearDown();
    }

    public function test_non_variant_stock_validation_uses_sellable_quantity_not_stale_available_column(): void
    {
        $this->seedWarehouse(id: 1, type: 'main');
        $this->seedWarehouseStock(
            warehouseId: 1,
            productId: 224,
            physical: 10,
            reserved: 4,
            available: 0 // Intentionally stale to verify sellable formula is used.
        );

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'Sellable Stock Product',
            'qty' => 6,
            'price' => 100,
            'options' => ['warehouse_id' => 1],
        ]);

        $result = $this->invokeValidateCartStock();

        $this->assertTrue($result['valid']);
        $this->assertSame('Stock validation passed', $result['message']);
        $this->assertSame(1, (int) Session::get('warehouse_id'));
    }

    public function test_stock_validation_switches_warehouse_when_current_warehouse_has_no_stock(): void
    {
        $this->seedWarehouse(id: 1, type: 'main');
        $this->seedWarehouse(id: 2, type: 'branch');
        $this->seedWarehouseStock(
            warehouseId: 2,
            productId: 224,
            physical: 5,
            reserved: 0,
            available: 0
        );

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'Warehouse Switch Product',
            'qty' => 1,
            'price' => 100,
            'options' => ['warehouse_id' => 1],
        ]);

        $result = $this->invokeValidateCartStock();

        $this->assertTrue($result['valid']);
        $this->assertSame(2, (int) Session::get('warehouse_id'));

        $cartItem = \Cart::instance('shopping')->content()->first();
        $this->assertNotNull($cartItem);
        $this->assertSame(2, (int) ($cartItem->options->warehouse_id ?? 0));
    }

    public function test_insufficient_message_uses_total_sellable_stock_across_warehouses(): void
    {
        $this->seedWarehouse(id: 1, type: 'main');
        $this->seedWarehouse(id: 2, type: 'branch');
        $this->seedWarehouseStock(
            warehouseId: 1,
            productId: 224,
            physical: 5,
            reserved: 3,
            available: 0
        );
        $this->seedWarehouseStock(
            warehouseId: 2,
            productId: 224,
            physical: 4,
            reserved: 2,
            available: 0
        );

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'Insufficient Product',
            'qty' => 5, // Total sellable is 4 across both warehouses.
            'price' => 100,
            'options' => ['warehouse_id' => 1],
        ]);

        $result = $this->invokeValidateCartStock();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString("Insufficient stock for 'Insufficient Product'.", $result['message']);
        $this->assertMatchesRegularExpression('/Total available:\s*4(?:\.0+)?/', $result['message']);
        $this->assertStringContainsString('Required: 5', $result['message']);
    }

    public function test_variant_stock_validation_passes_using_sellable_stock(): void
    {
        $this->seedWarehouse(id: 1, type: 'main');
        $this->seedProduct(id: 224, name: 'Variant Product', hasVariant: true);
        $variantId = $this->seedProductVariant(productId: 224, size: 'L', color: 'Blue');
        $this->seedInventory(
            warehouseId: 1,
            variantId: $variantId,
            quantityAvailable: 7,
            quantityReserved: 2 // Sellable = 5
        );

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'Variant Product',
            'qty' => 5,
            'price' => 100,
            'options' => [
                'warehouse_id' => 1,
                'product_variant_id' => $variantId,
            ],
        ]);

        $result = $this->invokeValidateCartStock();

        $this->assertTrue($result['valid']);
        $this->assertSame('Stock validation passed', $result['message']);
        $this->assertSame(1, (int) Session::get('warehouse_id'));
    }

    public function test_variant_stock_validation_switches_warehouse_when_needed(): void
    {
        $this->seedWarehouse(id: 1, type: 'main');
        $this->seedWarehouse(id: 2, type: 'branch');
        $this->seedProduct(id: 224, name: 'Variant Warehouse Switch Product', hasVariant: true);
        $variantId = $this->seedProductVariant(productId: 224, size: 'M', color: 'Green');
        $this->seedInventory(
            warehouseId: 2,
            variantId: $variantId,
            quantityAvailable: 4,
            quantityReserved: 1 // Sellable = 3
        );

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'Variant Warehouse Switch Product',
            'qty' => 2,
            'price' => 100,
            'options' => [
                'warehouse_id' => 1,
                'product_variant_id' => $variantId,
            ],
        ]);

        $result = $this->invokeValidateCartStock();

        $this->assertTrue($result['valid']);
        $this->assertSame(2, (int) Session::get('warehouse_id'));

        $cartItem = \Cart::instance('shopping')->content()->first();
        $this->assertNotNull($cartItem);
        $this->assertSame(2, (int) ($cartItem->options->warehouse_id ?? 0));
    }

    public function test_variant_insufficient_message_uses_total_sellable_stock_across_warehouses(): void
    {
        $this->seedWarehouse(id: 1, type: 'main');
        $this->seedWarehouse(id: 2, type: 'branch');
        $this->seedProduct(id: 224, name: 'Variant Insufficient Product', hasVariant: true);
        $variantId = $this->seedProductVariant(productId: 224, size: 'S', color: 'Black');
        $this->seedInventory(
            warehouseId: 1,
            variantId: $variantId,
            quantityAvailable: 2,
            quantityReserved: 1 // Sellable = 1
        );
        $this->seedInventory(
            warehouseId: 2,
            variantId: $variantId,
            quantityAvailable: 3,
            quantityReserved: 2 // Sellable = 1
        );

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'Variant Insufficient Product',
            'qty' => 3, // Total sellable is 2 across both warehouses.
            'price' => 100,
            'options' => [
                'warehouse_id' => 1,
                'product_variant_id' => $variantId,
            ],
        ]);

        $result = $this->invokeValidateCartStock();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString("Insufficient stock for 'Variant Insufficient Product'.", $result['message']);
        $this->assertMatchesRegularExpression('/Total available:\s*2(?:\.0+)?/', $result['message']);
        $this->assertStringContainsString('Required: 3', $result['message']);
    }

    private function invokeValidateCartStock(): array
    {
        $controller = app(CustomerController::class);
        $method = new ReflectionMethod($controller, 'validateCartStock');
        $method->setAccessible(true);

        /** @var array{valid:bool,message:string} $result */
        $result = $method->invoke($controller);

        return $result;
    }

    private function createTables(): void
    {
        Schema::dropIfExists('warehouse_stock');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('has_variant')->default(false);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouse_stock', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('product_id');
            $table->decimal('physical_quantity', 12, 2)->default(0);
            $table->decimal('reserved_quantity', 12, 2)->default(0);
            $table->decimal('available_quantity', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->decimal('stock', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_variant_id');
            $table->unsignedInteger('warehouse_id');
            $table->decimal('quantity_available', 12, 2)->default(0);
            $table->decimal('quantity_reserved', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    private function seedProduct(int $id, string $name, bool $hasVariant = false): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'name' => $name,
            'has_variant' => $hasVariant,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedWarehouseStock(
        int $warehouseId,
        int $productId,
        float $physical,
        float $reserved,
        float $available
    ): void {
        DB::table('warehouse_stock')->insert([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'physical_quantity' => $physical,
            'reserved_quantity' => $reserved,
            'available_quantity' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedProductVariant(int $productId, string $size, string $color): int
    {
        return (int) DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'size' => $size,
            'color' => $color,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedInventory(
        int $warehouseId,
        int $variantId,
        float $quantityAvailable,
        float $quantityReserved
    ): void {
        DB::table('inventories')->insert([
            'warehouse_id' => $warehouseId,
            'product_variant_id' => $variantId,
            'quantity_available' => $quantityAvailable,
            'quantity_reserved' => $quantityReserved,
            'reorder_level' => 0,
            'total_value' => 0,
            'last_updated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
