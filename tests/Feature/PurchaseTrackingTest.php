<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Jobs\SendPurchaseTrackingJob;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\EcomPixel;
use App\Services\PurchaseTrackingService;
use App\Services\BranchAccountingService;
use App\Services\StockEngine;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class PurchaseTrackingTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();

        // Mock services that could be called during order boot/lifecycle events
        $this->mock(BranchAccountingService::class);
        $this->mock(StockEngine::class);
    }

    /**
     * Test changing status to Confirmed dispatches the listener and queues the job.
     */
    public function test_order_confirmed_dispatches_purchase_tracking_job(): void
    {
        Queue::fake();

        $order = Order::findOrFail(1001);

        // Transition status to Confirmed (ID 2)
        $orderStateMachine = app(\App\Domain\Orders\OrderStateMachine::class);
        $orderStateMachine->transition($order, 2, [
            'actor_type' => 'admin',
            'actor_id' => 1,
            'source' => 'test',
        ]);

        // Assert OrderStatusUpdated event was fired
        // (We don't fake events so the registered listener actually runs and queues the job)
        Queue::assertPushed(SendPurchaseTrackingJob::class, function ($job) use ($order) {
            return $job->tries() === 3 && $job->backoff() === 5;
        });

        // Verify order tracking status is updated to queued
        $order->refresh();
        $this->assertSame('queued', $order->purchase_tracking_status);
    }

    /**
     * Test fallback endpoint registers GTM client-side tracking and sets status.
     */
    public function test_fallback_endpoint_updates_status_correctly(): void
    {
        $order = Order::findOrFail(1001);

        $response = $this->postJson(route('tracking.fallback'), [
            'order_id' => 1001,
            'invoice_id' => 'INV-1001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Client fallback tracking recorded successfully.',
        ]);

        $order->refresh();
        $this->assertSame('success', $order->purchase_tracking_status);
        $this->assertNotNull($order->purchase_tracked_at);
        $this->assertNotNull($order->purchase_pixel_fired_at);
        
        $providerStatus = $order->tracking_provider_status;
        $this->assertIsArray($providerStatus);
        $this->assertSame('success', $providerStatus['gtm_client_fallback']);
    }

    /**
     * Test fallback security validation rejects mismatched invoice ID.
     */
    public function test_fallback_endpoint_validates_matching_invoice_id(): void
    {
        $response = $this->postJson(route('tracking.fallback'), [
            'order_id' => 1001,
            'invoice_id' => 'INV-WRONG',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Invoice ID mismatch.',
        ]);
    }

    /**
     * Test SendPurchaseTrackingJob runs clients correctly.
     */
    public function test_queue_job_sends_requests_to_all_enabled_providers(): void
    {
        Http::fake([
            'https://www.google-analytics.com/*' => Http::response([], 200),
            'https://graph.facebook.com/*' => Http::response([], 200),
            'https://business-api.tiktok.com/*' => Http::response([], 200),
        ]);

        // Configure credentials so clients are enabled and try to fire
        config([
            'tracking.ga4.measurement_id' => 'GA-12345',
            'tracking.ga4.api_secret' => 'SECRET-GA',
            'tracking.facebook.capi_access_token' => 'FB-TOKEN',
            'tracking.tiktok.pixel_id' => 'TT-PIXEL',
            'tracking.tiktok.access_token' => 'TT-TOKEN',
        ]);

        // Run the job synchronously
        $job = new SendPurchaseTrackingJob(1001);
        $job->handle();

        // Verify status updated to success
        $order = Order::findOrFail(1001);
        $this->assertSame('success', $order->purchase_tracking_status);
        $this->assertNotNull($order->purchase_tracked_at);

        $providerStatus = $order->tracking_provider_status;
        $this->assertSame('success', $providerStatus['ga4'] ?? null);
        $this->assertSame('success', $providerStatus['facebook'] ?? null);
        $this->assertSame('success', $providerStatus['tiktok'] ?? null);

        // Verify Http requests were made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'google-analytics.com');
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com');
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'business-api.tiktok.com');
        });
    }

    /**
     * Test queue job does not trigger if order was already successfully tracked (by fallback).
     */
    public function test_queue_job_skips_if_already_tracked_by_fallback(): void
    {
        Http::fake();

        // Mark as client tracked
        $order = Order::findOrFail(1001);
        app(PurchaseTrackingService::class)->markAsClientTracked($order);

        // Run the job
        $job = new SendPurchaseTrackingJob(1001);
        $job->handle();

        // Assert no HTTP requests were made
        Http::assertNothingSent();
    }

    /**
     * Test queue job failing a provider throws runtime exception to trigger retry.
     */
    public function test_queue_job_fails_and_throws_exception_on_api_error(): void
    {
        Http::fake([
            'https://www.google-analytics.com/*' => Http::response([], 500),
            'https://graph.facebook.com/*' => Http::response([], 200),
            'https://business-api.tiktok.com/*' => Http::response([], 200),
        ]);

        config([
            'tracking.ga4.measurement_id' => 'GA-12345',
            'tracking.ga4.api_secret' => 'SECRET-GA',
            'tracking.facebook.capi_access_token' => 'FB-TOKEN',
            'tracking.tiktok.pixel_id' => 'TT-PIXEL',
            'tracking.tiktok.access_token' => 'TT-TOKEN',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tracking failed for: ga4. Retrying...');

        $job = new SendPurchaseTrackingJob(1001);
        $job->handle();
    }

    private function createTables(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoice_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('shipping_charge', 15, 2)->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->integer('order_status')->default(1);
            $table->timestamp('purchase_pixel_fired_at')->nullable();
            $table->string('purchase_tracking_status')->default('pending');
            $table->timestamp('purchase_tracked_at')->nullable();
            $table->text('tracking_provider_status')->nullable();
            $table->string('steadfast_consignment_id')->nullable();
            $table->string('steadfast_tracking_code')->nullable();
            $table->string('steadfast_status')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->unsignedInteger('qty')->default(1);
            $table->string('product_color')->nullable();
            $table->string('product_size')->nullable();
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
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('ecom_pixels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('order_state_transitions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('branch_id')->nullable();
            $table->integer('from_status');
            $table->integer('to_status');
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('source')->nullable();
            $table->text('reason')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        // Seed order statuses
        DB::table('order_statuses')->insert([
            ['id' => 1, 'name' => 'Pending', 'slug' => 'pending'],
            ['id' => 2, 'name' => 'Confirmed', 'slug' => 'confirmed'],
            ['id' => 3, 'name' => 'Processing', 'slug' => 'processing'],
            ['id' => 4, 'name' => 'Shipped', 'slug' => 'shipped'],
            ['id' => 5, 'name' => 'Delivered', 'slug' => 'delivered'],
            ['id' => 6, 'name' => 'Cancelled', 'slug' => 'cancelled'],
            ['id' => 7, 'name' => 'Returned', 'slug' => 'returned'],
        ]);

        // Seed an order
        DB::table('orders')->insert([
            'id' => 1001,
            'invoice_id' => 'INV-1001',
            'amount' => 1200.00,
            'discount' => 50.00,
            'shipping_charge' => 60.00,
            'currency' => 'BDT',
            'customer_id' => 1,
            'order_status' => 1,
            'purchase_tracking_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed order items
        DB::table('order_details')->insert([
            'order_id' => 1001,
            'product_id' => 501,
            'product_name' => 'Premium Shirt',
            'sale_price' => 600.00,
            'qty' => 2,
            'product_color' => 'Blue',
            'product_size' => 'XL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed shipping details
        DB::table('shippings')->insert([
            'order_id' => 1001,
            'customer_id' => 1,
            'name' => 'John Doe',
            'phone' => '01712345678',
            'address' => 'Mirpur, Dhaka',
            'area' => 'Dhaka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed payment
        DB::table('payments')->insert([
            'order_id' => 1001,
            'payment_method' => 'cod',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed customer
        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'John Doe',
            'phone' => '01712345678',
            'email' => 'john@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed ecom pixel
        DB::table('ecom_pixels')->insert([
            'id' => 1,
            'code' => '1234567890',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
