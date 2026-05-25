<?php

namespace Tests\Feature\Checkout;

use App\Http\Controllers\Frontend\CustomerController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class OrderSuccessPixelIdempotencyTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        Session::start();
    }

    public function test_purchase_pixel_is_marked_once_for_same_order(): void
    {
        DB::table('orders')->insert([
            'id' => 1001,
            'invoice_id' => 'INV-1001',
            'amount' => 1200,
            'currency' => 'BDT',
            'order_public_token' => 'public-token-1001',
            'purchase_pixel_fired_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_details')->insert([
            'order_id' => 1001,
            'product_id' => 501,
            'qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shippings')->insert([
            'order_id' => 1001,
            'name' => 'Test Buyer',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'order_id' => 1001,
            'payment_method' => 'cod',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstResponse = $this->callOrderSuccess(1001, 'public-token-1001');
        $this->assertInstanceOf(View::class, $firstResponse);
        $this->assertTrue((bool) $firstResponse->getData()['shouldFirePurchasePixel']);

        $firstFiredAt = DB::table('orders')->where('id', 1001)->value('purchase_pixel_fired_at');
        $this->assertNotNull($firstFiredAt);

        $secondResponse = $this->callOrderSuccess(1001, 'public-token-1001');
        $this->assertInstanceOf(View::class, $secondResponse);
        $this->assertFalse((bool) $secondResponse->getData()['shouldFirePurchasePixel']);

        $secondFiredAt = DB::table('orders')->where('id', 1001)->value('purchase_pixel_fired_at');
        $this->assertSame((string) $firstFiredAt, (string) $secondFiredAt);
    }

    public function test_guest_access_requires_matching_public_token(): void
    {
        DB::table('orders')->insert([
            'id' => 2002,
            'invoice_id' => 'INV-2002',
            'amount' => 900,
            'currency' => 'BDT',
            'order_public_token' => 'valid-token-2002',
            'purchase_pixel_fired_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_details')->insert([
            'order_id' => 2002,
            'product_id' => 777,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->callOrderSuccess(2002, 'wrong-token');
            $this->fail('Expected 403 for invalid public token.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_purchase_pixel_fallback_without_column_is_once_per_session(): void
    {
        Schema::dropIfExists('orders');
        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('invoice_id')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->string('order_public_token', 100)->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->timestamps();
        });

        DB::table('orders')->insert([
            'id' => 3003,
            'invoice_id' => 'INV-3003',
            'amount' => 1500,
            'currency' => 'BDT',
            'order_public_token' => 'public-token-3003',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_details')->insert([
            'order_id' => 3003,
            'product_id' => 888,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shippings')->insert([
            'order_id' => 3003,
            'name' => 'Fallback Buyer',
            'phone' => '01800000000',
            'address' => 'Dhaka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'order_id' => 3003,
            'payment_method' => 'cod',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstResponse = $this->callOrderSuccess(3003, 'public-token-3003');
        $this->assertInstanceOf(View::class, $firstResponse);
        $this->assertTrue((bool) $firstResponse->getData()['shouldFirePurchasePixel']);

        $secondResponse = $this->callOrderSuccess(3003, 'public-token-3003');
        $this->assertInstanceOf(View::class, $secondResponse);
        $this->assertFalse((bool) $secondResponse->getData()['shouldFirePurchasePixel']);

        $firedOrders = collect((array) Session::get('purchase_pixel_fired_orders', []))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->assertContains(3003, $firedOrders);
    }

    private function callOrderSuccess(int $orderId, string $publicToken): View
    {
        $request = Request::create('/customer/order-success/'.$orderId, 'GET', [
            't' => $publicToken,
        ]);
        $request->setLaravelSession(app('session.store'));

        /** @var View $response */
        $response = app(CustomerController::class)->order_success($request, $orderId);

        return $response;
    }

    private function createTables(): void
    {
        Schema::dropIfExists('order_details');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('shippings');
        Schema::dropIfExists('orders');

        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('invoice_id')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->string('order_public_token', 100)->nullable();
            $table->timestamp('purchase_pixel_fired_at')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();
        });

        Schema::create('shippings', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }
}
