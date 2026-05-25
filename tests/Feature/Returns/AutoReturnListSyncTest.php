<?php

namespace Tests\Feature\Returns;

use App\Domain\Orders\OrderStateMachine;
use App\Models\Order;
use App\Services\OrderReturnSyncService;
use App\Services\StockEngine;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class AutoReturnListSyncTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
    }

    public function test_transition_to_returned_auto_creates_return_entry_and_is_idempotent(): void
    {
        $stockEngine = Mockery::mock(StockEngine::class);
        $syncService = new OrderReturnSyncService;
        $stateMachine = new OrderStateMachine($stockEngine, null, $syncService);

        $order = Order::query()->findOrFail(1);

        $transitioned = $stateMachine->transition($order, 7, [
            'actor_type' => 'admin',
            'actor_id' => 1,
            'source' => 'test_returned_status',
            'reason' => 'Returned by courier',
        ]);

        $this->assertSame(7, (int) $transitioned->order_status);
        $this->assertSame(1, DB::table('return_orders')->count());
        $this->assertSame(1, DB::table('return_items')->count());
        $this->assertSame('pending', (string) DB::table('return_orders')->where('order_id', 1)->value('return_status'));
        $this->assertSame('2', (string) DB::table('order_details')->where('id', 1)->value('returned_quantity'));

        $existing = $syncService->syncReturnedOrder($order->fresh(), [
            'actor_type' => 'admin',
            'actor_id' => 1,
            'source' => 'idempotency_check',
        ]);

        $this->assertNotNull($existing);
        $this->assertSame(1, DB::table('return_orders')->count());
        $this->assertSame(1, DB::table('return_items')->count());
    }

    private function createTables(): void
    {
        Schema::dropIfExists('return_logs');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_orders');
        Schema::dropIfExists('return_reasons');
        Schema::dropIfExists('order_state_transitions');
        Schema::dropIfExists('order_details');
        Schema::dropIfExists('order_statuses');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('invoice_id')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->unsignedInteger('order_status')->default(1);
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedInteger('shipping_charge')->default(0);
            $table->unsignedBigInteger('shipping_charge_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->text('note')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_statuses', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('purchase_price')->default(0);
            $table->unsignedInteger('sale_price')->default(0);
            $table->unsignedInteger('qty')->default(0);
            $table->decimal('returned_quantity', 8, 2)->default(0);
            $table->boolean('return_eligible')->default(true);
            $table->date('return_deadline')->nullable();
            $table->timestamps();
        });

        Schema::create('order_state_transitions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('from_status');
            $table->unsignedInteger('to_status');
            $table->string('actor_type')->nullable();
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('source')->nullable();
            $table->text('reason')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('return_reasons', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('reason_code')->nullable();
            $table->string('reason_name')->nullable();
            $table->string('reason_category')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('auto_restock')->default(true);
            $table->boolean('refund_eligible')->default(true);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('return_orders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('return_number')->nullable();
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('customer_id');
            $table->string('return_status', 30)->default('pending');
            $table->string('return_source', 30)->default('customer');
            $table->string('return_type', 30)->default('partial');
            $table->unsignedInteger('return_reason_id');
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('refund_method', 30)->nullable();
            $table->boolean('restock_flag')->default(true);
            $table->boolean('damage_flag')->default(false);
            $table->decimal('total_return_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('return_items', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('return_order_id');
            $table->unsignedInteger('order_detail_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('warehouse_id');
            $table->decimal('return_quantity', 8, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('return_condition', 20)->default('opened');
            $table->decimal('restock_quantity', 8, 2)->default(0);
            $table->decimal('damage_quantity', 8, 2)->default(0);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->unsignedInteger('replacement_order_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('return_logs', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('return_order_id');
            $table->unsignedInteger('return_item_id')->nullable();
            $table->string('action_type', 40);
            $table->string('old_status', 40)->nullable();
            $table->string('new_status', 40)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('performed_by');
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'Return Customer',
            'phone' => '01700000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_statuses')->insert([
            ['id' => 5, 'name' => 'Delivered', 'slug' => 'delivered', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Returned', 'slug' => 'returned', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('orders')->insert([
            'id' => 1,
            'invoice_id' => 'INV-RETURN-1',
            'customer_id' => 1,
            'warehouse_id' => 1,
            'order_status' => 5,
            'amount' => 1000,
            'amount_minor' => 100000,
            'discount' => 0,
            'discount_minor' => 0,
            'shipping_charge' => 0,
            'shipping_charge_minor' => 0,
            'currency' => 'BDT',
            'note' => null,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_details')->insert([
            'id' => 1,
            'order_id' => 1,
            'product_id' => 101,
            'warehouse_id' => 1,
            'product_name' => 'Returned Product',
            'purchase_price' => 60,
            'sale_price' => 100,
            'qty' => 2,
            'returned_quantity' => 0,
            'return_eligible' => 1,
            'return_deadline' => now()->addDays(3)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('return_reasons')->insert([
            'id' => 1,
            'reason_code' => 'OTHER-002',
            'reason_name' => 'Store Return',
            'reason_category' => 'other',
            'requires_approval' => 0,
            'auto_restock' => 1,
            'refund_eligible' => 1,
            'active' => 1,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
