<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\OrderController;
use App\Models\WarehouseStock;
use App\Services\WarehouseStockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class OrderPosTransactionTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
        Session::start();
        \Cart::instance('pos_shopping')->destroy();
    }

    protected function tearDown(): void
    {
        app()->forgetInstance(WarehouseStockService::class);
        \Cart::instance('pos_shopping')->destroy();
        parent::tearDown();
    }

    public function test_admin_pos_order_store_commits_data_and_reserves_stock(): void
    {
        DB::table('warehouse_stock')->insert([
            'warehouse_id' => 1,
            'product_id' => 224,
            'physical_quantity' => 10,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
            'average_cost' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Cart::instance('pos_shopping')->add([
            'id' => 224,
            'name' => 'POS Product',
            'qty' => 2,
            'price' => 100,
            'options' => [
                'warehouse_id' => 1,
                'purchase_price' => 20,
                'product_discount' => 0,
            ],
        ]);

        $response = $this->callOrderStore();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/admin/order/pending', $response->getTargetUrl());

        $this->assertSame(1, DB::table('orders')->count());
        $this->assertSame(1, DB::table('shippings')->count());
        $this->assertSame(1, DB::table('payments')->count());
        $this->assertSame(1, DB::table('order_details')->count());

        $order = DB::table('orders')->first();
        $this->assertNotNull($order);
        $this->assertSame('270', (string) (int) $order->amount);
        $this->assertSame('27000', (string) (int) $order->amount_minor);
        $this->assertSame('7000', (string) (int) $order->shipping_charge_minor);

        $stock = DB::table('warehouse_stock')
            ->where('warehouse_id', 1)
            ->where('product_id', 224)
            ->first();
        $this->assertNotNull($stock);
        $this->assertSame('8', (string) (int) $stock->available_quantity);
        $this->assertSame('2', (string) (int) $stock->reserved_quantity);
        $this->assertSame('10', (string) (int) $stock->physical_quantity);

        $movement = DB::table('stock_movements')->first();
        $this->assertNotNull($movement);
        $this->assertSame('reservation', $movement->type);
        $this->assertSame('order', $movement->reference_type);
        $this->assertSame('-2', (string) (int) $movement->quantity);

        $this->assertSame(0, \Cart::instance('pos_shopping')->count());
    }

    public function test_admin_pos_order_store_rolls_back_on_reservation_failure(): void
    {
        app()->instance(WarehouseStockService::class, new class extends WarehouseStockService
        {
            public function reserveStock(
                int $warehouseId,
                int $productId,
                float $quantity,
                ?int $referenceId = null,
                string $referenceType = 'order',
                string $notes = ''
            ): WarehouseStock {
                throw new \Exception('Forced reserve failure for POS store rollback test');
            }
        });

        DB::table('warehouse_stock')->insert([
            'warehouse_id' => 1,
            'product_id' => 224,
            'physical_quantity' => 10,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
            'average_cost' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Cart::instance('pos_shopping')->add([
            'id' => 224,
            'name' => 'POS Rollback Product',
            'qty' => 1,
            'price' => 100,
            'options' => [
                'warehouse_id' => 1,
                'purchase_price' => 20,
                'product_discount' => 0,
            ],
        ]);

        $response = $this->callOrderStore();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringNotContainsString('/admin/order/pending', $response->getTargetUrl());

        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('shippings')->count());
        $this->assertSame(0, DB::table('payments')->count());
        $this->assertSame(0, DB::table('order_details')->count());
        $this->assertSame(0, DB::table('stock_movements')->count());

        $stock = DB::table('warehouse_stock')
            ->where('warehouse_id', 1)
            ->where('product_id', 224)
            ->first();
        $this->assertNotNull($stock);
        $this->assertSame('10', (string) (int) $stock->available_quantity);
        $this->assertSame('0', (string) (int) $stock->reserved_quantity);
        $this->assertSame('10', (string) (int) $stock->physical_quantity);

        $this->assertSame(1, \Cart::instance('pos_shopping')->count());
    }

    public function test_admin_pos_order_update_releases_old_and_reserves_new_stock(): void
    {
        $this->seedExistingOrderWithReservation();

        \Cart::instance('pos_shopping')->add([
            'id' => 224,
            'name' => 'POS Product',
            'qty' => 3,
            'price' => 100,
            'options' => [
                'warehouse_id' => 1,
                'purchase_price' => 20,
                'product_discount' => 0,
            ],
        ]);

        $response = $this->callOrderUpdate(1);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/admin/order/pending', $response->getTargetUrl());

        $order = DB::table('orders')->where('id', 1)->first();
        $this->assertNotNull($order);
        $this->assertSame('370', (string) (int) $order->amount);
        $this->assertSame('37000', (string) (int) $order->amount_minor);

        $detail = DB::table('order_details')->where('order_id', 1)->first();
        $this->assertNotNull($detail);
        $this->assertSame('3', (string) (int) $detail->qty);

        $stock = DB::table('warehouse_stock')
            ->where('warehouse_id', 1)
            ->where('product_id', 224)
            ->first();
        $this->assertNotNull($stock);
        $this->assertSame('7', (string) (int) $stock->available_quantity);
        $this->assertSame('3', (string) (int) $stock->reserved_quantity);
        $this->assertSame('10', (string) (int) $stock->physical_quantity);

        $this->assertSame(2, DB::table('stock_movements')->count());
        $this->assertSame(1, DB::table('stock_movements')->where('type', 'release')->count());
        $this->assertSame(1, DB::table('stock_movements')->where('type', 'reservation')->count());
    }

    public function test_admin_pos_order_update_rolls_back_release_when_new_reserve_fails(): void
    {
        app()->instance(WarehouseStockService::class, new class extends WarehouseStockService
        {
            public function reserveStock(
                int $warehouseId,
                int $productId,
                float $quantity,
                ?int $referenceId = null,
                string $referenceType = 'order',
                string $notes = ''
            ): WarehouseStock {
                throw new \Exception('Forced reserve failure for POS update rollback test');
            }
        });

        $this->seedExistingOrderWithReservation();

        \Cart::instance('pos_shopping')->add([
            'id' => 224,
            'name' => 'POS Product',
            'qty' => 3,
            'price' => 100,
            'options' => [
                'warehouse_id' => 1,
                'purchase_price' => 20,
                'product_discount' => 0,
            ],
        ]);

        $response = $this->callOrderUpdate(1);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringNotContainsString('/admin/order/pending', $response->getTargetUrl());

        // Existing order details and stock reservation must be fully restored by transaction rollback.
        $detail = DB::table('order_details')->where('order_id', 1)->first();
        $this->assertNotNull($detail);
        $this->assertSame('2', (string) (int) $detail->qty);

        $stock = DB::table('warehouse_stock')
            ->where('warehouse_id', 1)
            ->where('product_id', 224)
            ->first();
        $this->assertNotNull($stock);
        $this->assertSame('8', (string) (int) $stock->available_quantity);
        $this->assertSame('2', (string) (int) $stock->reserved_quantity);
        $this->assertSame('10', (string) (int) $stock->physical_quantity);

        $order = DB::table('orders')->where('id', 1)->first();
        $this->assertNotNull($order);
        $this->assertSame('270', (string) (int) $order->amount);
        $this->assertSame('27000', (string) (int) $order->amount_minor);

        // Release movement inside failed transaction must not persist.
        $this->assertSame(0, DB::table('stock_movements')->count());
        $this->assertSame(3, \Cart::instance('pos_shopping')->count());
    }

    private function callOrderStore(): RedirectResponse
    {
        $request = Request::create('/admin/order/store', 'POST', [
            'name' => 'POS Customer',
            'phone' => '01700000001',
            'address' => 'Dhaka',
            'area' => 1,
            'note' => 'Test note',
        ]);
        $request->headers->set('referer', 'http://localhost/admin/order/create');
        $request->setLaravelSession(app('session.store'));

        /** @var RedirectResponse $response */
        $response = app(OrderController::class)->order_store($request);

        return $response;
    }

    private function callOrderUpdate(int $orderId): RedirectResponse
    {
        $request = Request::create('/admin/order/update', 'POST', [
            'order_id' => $orderId,
            'name' => 'POS Customer',
            'phone' => '01700000001',
            'address' => 'Dhaka',
            'area' => 1,
            'note' => 'Updated note',
        ]);
        $request->headers->set('referer', 'http://localhost/admin/order/edit/INV-1');
        $request->setLaravelSession(app('session.store'));

        /** @var RedirectResponse $response */
        $response = app(OrderController::class)->order_update($request);

        return $response;
    }

    private function seedExistingOrderWithReservation(): void
    {
        DB::table('warehouse_stock')->insert([
            'warehouse_id' => 1,
            'product_id' => 224,
            'physical_quantity' => 10,
            'available_quantity' => 8,
            'reserved_quantity' => 2,
            'average_cost' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'id' => 1,
            'invoice_id' => 'INV-1',
            'amount' => 270,
            'amount_minor' => 27000,
            'discount' => 0,
            'discount_minor' => 0,
            'shipping_charge' => 70,
            'shipping_charge_minor' => 7000,
            'currency' => 'BDT',
            'order_public_token' => 'token-1',
            'customer_id' => 1,
            'warehouse_id' => 1,
            'order_status' => 1,
            'note' => 'Existing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shippings')->insert([
            'order_id' => 1,
            'customer_id' => 1,
            'name' => 'POS Customer',
            'phone' => '01700000001',
            'address' => 'Dhaka',
            'area' => 'Inside Dhaka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'order_id' => 1,
            'customer_id' => 1,
            'payment_method' => 'Cash On Delivery',
            'gateway' => 'cod',
            'amount' => 270,
            'amount_minor' => 27000,
            'currency' => 'BDT',
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_details')->insert([
            'order_id' => 1,
            'product_id' => 224,
            'warehouse_id' => 1,
            'product_name' => 'POS Product',
            'purchase_price' => 20,
            'purchase_price_minor' => 2000,
            'product_discount' => 0,
            'product_color' => null,
            'product_size' => null,
            'sale_price' => 100,
            'sale_price_minor' => 10000,
            'currency' => 'BDT',
            'qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedBaseData(): void
    {
        DB::table('shipping_charges')->insert([
            'id' => 1,
            'name' => 'Inside Dhaka',
            'amount' => 70,
            'amount_minor' => 7000,
            'status' => 1,
            'currency' => 'BDT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => 224,
            'name' => 'POS Product',
            'status' => 1,
            'new_price' => 100,
            'new_price_minor' => 10000,
            'purchase_price' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'WH-1',
            'type' => 'main',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'POS Customer',
            'slug' => 'pos-customer',
            'phone' => '01700000001',
            'password' => bcrypt('secret'),
            'verify' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('order_details');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('shippings');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('warehouse_stock');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('products');
        Schema::dropIfExists('shipping_charges');
        Schema::dropIfExists('customers');

        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->integer('verify')->default(1);
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('new_price')->default(0);
            $table->unsignedBigInteger('new_price_minor')->default(0);
            $table->unsignedInteger('purchase_price')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_charges', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->string('currency', 3)->default('BDT');
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
            $table->string('sku')->nullable();
            $table->decimal('physical_quantity', 12, 2)->default(0);
            $table->decimal('available_quantity', 12, 2)->default(0);
            $table->decimal('reserved_quantity', 12, 2)->default(0);
            $table->decimal('reorder_point', 12, 2)->default(0);
            $table->decimal('reorder_quantity', 12, 2)->default(0);
            $table->decimal('average_cost', 12, 2)->default(0);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->timestamp('last_stock_in_date')->nullable();
            $table->timestamp('last_stock_out_date')->nullable();
            $table->timestamp('last_audit_date')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoice_id')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedInteger('shipping_charge')->default(0);
            $table->unsignedBigInteger('shipping_charge_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->string('order_public_token')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->unsignedInteger('order_status')->default(1);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('shippings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('gateway')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->string('trx_id')->nullable();
            $table->string('sender_number')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('purchase_price')->default(0);
            $table->unsignedBigInteger('purchase_price_minor')->default(0);
            $table->unsignedInteger('product_discount')->default(0);
            $table->string('product_color')->nullable();
            $table->string('product_size')->nullable();
            $table->unsignedInteger('sale_price')->default(0);
            $table->unsignedBigInteger('sale_price_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->string('type', 50);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }
}
