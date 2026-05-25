<?php

namespace Tests\Feature\Checkout;

use App\Http\Controllers\Frontend\CustomerController;
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

class OrderSaveTransactionTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
        Session::start();
        \Cart::instance('shopping')->destroy();
    }

    protected function tearDown(): void
    {
        app()->forgetInstance(WarehouseStockService::class);
        \Cart::instance('shopping')->destroy();
        parent::tearDown();
    }

    public function test_order_save_commits_order_and_reserves_stock_on_success(): void
    {
        DB::table('warehouse_stock')->insert([
            'warehouse_id' => 1,
            'product_id' => 224,
            'physical_quantity' => 5,
            'available_quantity' => 4,
            'reserved_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'Txn Success Product',
            'qty' => 2,
            'price' => 1,
            'options' => [
                'warehouse_id' => 1,
                'purchase_price' => 20,
            ],
        ]);

        $response = $this->callOrderSave();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/customer/order-success/', $response->getTargetUrl());

        $this->assertSame(1, DB::table('orders')->count());
        $this->assertSame(1, DB::table('shippings')->count());
        $this->assertSame(1, DB::table('payments')->count());
        $this->assertSame(1, DB::table('order_details')->count());

        $stock = DB::table('warehouse_stock')
            ->where('warehouse_id', 1)
            ->where('product_id', 224)
            ->first();
        $this->assertNotNull($stock);
        $this->assertSame('2', (string) (int) $stock->available_quantity);
        $this->assertSame('3', (string) (int) $stock->reserved_quantity);
        $this->assertSame('5', (string) (int) $stock->physical_quantity);

        $this->assertSame(0, \Cart::instance('shopping')->count());
    }

    public function test_order_save_rolls_back_order_data_when_reservation_fails_inside_transaction(): void
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
                throw new \Exception('Forced reserve failure for rollback test');
            }
        });

        DB::table('warehouse_stock')->insert([
            'warehouse_id' => 1,
            'product_id' => 224,
            'physical_quantity' => 10,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'Txn Fail Product',
            'qty' => 1,
            'price' => 1,
            'options' => [
                'warehouse_id' => 1,
                'purchase_price' => 20,
            ],
        ]);

        $response = $this->callOrderSave();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringNotContainsString('/customer/order-success/', $response->getTargetUrl());

        // Order creation and children must be rolled back atomically.
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('shippings')->count());
        $this->assertSame(0, DB::table('payments')->count());
        $this->assertSame(0, DB::table('order_details')->count());

        // Stock must remain unchanged because reservation failed.
        $stock = DB::table('warehouse_stock')
            ->where('warehouse_id', 1)
            ->where('product_id', 224)
            ->first();
        $this->assertNotNull($stock);
        $this->assertSame('10', (string) (int) $stock->available_quantity);
        $this->assertSame('0', (string) (int) $stock->reserved_quantity);
        $this->assertSame('10', (string) (int) $stock->physical_quantity);

        // Cart should remain for retry.
        $this->assertSame(1, \Cart::instance('shopping')->count());
    }

    public function test_order_save_requires_guest_checkout_otp_when_feature_is_enabled(): void
    {
        config()->set('features.checkout.guest_otp_required', true);

        DB::table('warehouse_stock')->insert([
            'warehouse_id' => 1,
            'product_id' => 224,
            'physical_quantity' => 5,
            'available_quantity' => 5,
            'reserved_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('warehouse_id', 1);
        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'OTP Required Product',
            'qty' => 1,
            'price' => 1,
            'options' => [
                'warehouse_id' => 1,
                'purchase_price' => 20,
            ],
        ]);

        $response = $this->callOrderSave();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringNotContainsString('/customer/order-success/', $response->getTargetUrl());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(1, \Cart::instance('shopping')->count());
    }

    public function test_order_save_accepts_verified_guest_checkout_otp_when_feature_is_enabled(): void
    {
        config()->set('features.checkout.guest_otp_required', true);
        config()->set('features.checkout.guest_otp_verification_window_seconds', 1800);

        DB::table('warehouse_stock')->insert([
            'warehouse_id' => 1,
            'product_id' => 224,
            'physical_quantity' => 5,
            'available_quantity' => 5,
            'reserved_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('warehouse_id', 1);
        Session::put('guest_checkout_otp_verified', true);
        Session::put('guest_checkout_otp_verified_phone', '01700000000');
        Session::put('guest_checkout_otp_verified_at', now()->timestamp);

        \Cart::instance('shopping')->add([
            'id' => 224,
            'name' => 'OTP Verified Product',
            'qty' => 1,
            'price' => 1,
            'options' => [
                'warehouse_id' => 1,
                'purchase_price' => 20,
            ],
        ]);

        $response = $this->callOrderSave();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/customer/order-success/', $response->getTargetUrl());
        $this->assertSame(1, DB::table('orders')->count());
        $this->assertSame(0, \Cart::instance('shopping')->count());
    }

    private function callOrderSave(): RedirectResponse
    {
        $request = Request::create('/customer/order-save', 'POST', [
            'name' => 'Checkout User',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'area' => 1,
            'note' => null,
            'payment_method' => 'cod',
        ]);
        $request->headers->set('referer', 'http://localhost/customer/checkout');
        $request->setLaravelSession(app('session.store'));

        /** @var RedirectResponse $response */
        $response = app(CustomerController::class)->order_save($request);

        return $response;
    }

    private function seedBaseData(): void
    {
        DB::table('general_settings')->insert([
            'id' => 1,
            'name' => 'Test Shop',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
            'name' => 'Product 224',
            'status' => 1,
            'new_price' => 100,
            'new_price_minor' => 10000,
            'currency' => 'BDT',
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
            'name' => 'Checkout User',
            'slug' => 'checkout-user',
            'phone' => '01700000000',
            'password' => bcrypt('secret'),
            'verify' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('order_details');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('shippings');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('partial_orders');
        Schema::dropIfExists('sms_gateways');
        Schema::dropIfExists('general_settings');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('stock_movements');
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
            $table->rememberToken()->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('new_price')->default(0);
            $table->unsignedBigInteger('new_price_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
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

        Schema::create('product_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('branch_id')->nullable();
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->string('type');
            $table->string('reference_type')->nullable();
            $table->unsignedInteger('reference_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
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
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('purchase_price')->default(0);
            $table->unsignedBigInteger('purchase_price_minor')->default(0);
            $table->string('product_color')->nullable();
            $table->string('product_size')->nullable();
            $table->unsignedInteger('sale_price')->default(0);
            $table->unsignedBigInteger('sale_price_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();
        });

        Schema::create('partial_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('device_id')->nullable();
            $table->string('status')->nullable();
            $table->text('products')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('general_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('sms_gateways', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->default(0);
            $table->string('order')->nullable();
            $table->string('api_key')->nullable();
            $table->string('serderid')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }
}
