<?php

namespace Tests\Feature\Payments;

use App\Domain\Orders\OrderStateMachine;
use App\Domain\Payments\PaymentEventRecorder;
use App\Domain\Payments\PaymentIdempotencyService;
use App\Domain\Payments\PaymentVerificationService;
use App\Http\Controllers\Frontend\BkashController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class BkashWebhookIdempotencyFlowTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();

        // Keep webhook permissive in tests (same as project default for testing env).
        config()->set('payments.webhooks.bkash.signature_secret', '');
        config()->set('payments.webhooks.allow_unsigned_when_secret_missing', true);
    }

    public function test_bkash_webhook_replay_is_idempotent_and_does_not_reprocess_payment(): void
    {
        $controller = new class(new PaymentEventRecorder, new PaymentIdempotencyService, new PaymentVerificationService, app(OrderStateMachine::class)) extends BkashController
        {
            public int $executeCalls = 0;

            public int $queryCalls = 0;

            public function execute(string $paymentID): string
            {
                $this->executeCalls++;

                return json_encode([
                    'statusCode' => '0000',
                ]);
            }

            public function query(string $paymentID): string
            {
                $this->queryCalls++;

                return json_encode([
                    'statusCode' => '0000',
                    'transactionStatus' => 'Completed',
                    'amount' => '100.00',
                    'currency' => 'BDT',
                ]);
            }
        };

        $request = Request::create('/webhooks/bkash/callback', 'POST', [
            'orderId' => 1,
            'paymentID' => 'pay_001',
        ]);

        $first = $controller->webhook($request);
        $second = $controller->webhook($request);

        $this->assertSame(200, $first->status());
        $this->assertSame('accepted', $first->getData(true)['status']);

        $this->assertSame(200, $second->status());
        $this->assertSame('duplicate_ignored', $second->getData(true)['status']);

        // Gateway execute/query must run only once.
        $this->assertSame(1, $controller->executeCalls);
        $this->assertSame(1, $controller->queryCalls);

        // Payment should be marked paid once, with one idempotent payment event row.
        $this->assertSame(1, DB::table('payment_events')->count());
        $event = DB::table('payment_events')->first();
        $this->assertNotNull($event);
        $this->assertSame('accepted', $event->status);
        $this->assertNotNull($event->processed_at);

        $payment = DB::table('payments')->where('order_id', 1)->first();
        $this->assertNotNull($payment);
        $this->assertSame('paid', $payment->payment_status);
        $this->assertSame('pay_001', $payment->gateway_payment_id);
        $this->assertSame('10000', (string) (int) $payment->amount_minor);

        $order = DB::table('orders')->where('id', 1)->first();
        $this->assertNotNull($order);
        $this->assertSame('2', (string) (int) $order->order_status);

        // Transition should only be logged once.
        $this->assertSame(1, DB::table('order_state_transitions')->count());
    }

    private function seedBaseData(): void
    {
        DB::table('order_statuses')->insert([
            ['id' => 1, 'name' => 'Pending', 'slug' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Confirmed', 'slug' => 'confirmed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('orders')->insert([
            'id' => 1,
            'amount' => 100,
            'amount_minor' => 10000,
            'currency' => 'BDT',
            'order_public_token' => 'token-1',
            'customer_id' => 1,
            'order_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'id' => 1,
            'order_id' => 1,
            'customer_id' => 1,
            'payment_method' => 'bkash',
            'gateway' => 'bkash',
            'amount' => 100,
            'amount_minor' => 10000,
            'currency' => 'BDT',
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('order_statuses');
        Schema::dropIfExists('order_state_transitions');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->string('order_public_token')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('order_status')->default(1);
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
            $table->string('gateway_payment_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });

        Schema::create('order_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('gateway', 30);
            $table->string('event_key', 150);
            $table->string('gateway_payment_id', 150)->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->string('payload_hash', 128)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->unsignedBigInteger('amount_minor_reported')->default(0);
            $table->string('currency_reported', 3)->default('BDT');
            $table->text('payload')->nullable();
            $table->string('status', 30)->default('received');
            $table->text('status_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_key']);
        });

        Schema::create('order_state_transitions', function (Blueprint $table) {
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
    }
}
