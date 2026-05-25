<?php

namespace Tests\Feature\Payments;

use App\Domain\Payments\PaymentIdempotencyService;
use App\Models\PaymentEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class PaymentIdempotencyServiceTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createPaymentEventsTable();
    }

    public function test_claim_for_processing_is_idempotent(): void
    {
        $service = new PaymentIdempotencyService;

        $event = PaymentEvent::create([
            'gateway' => 'bkash',
            'event_key' => 'evt_001',
            'status' => 'received',
        ]);

        $claimed = $service->claimForProcessing($event);
        $this->assertNotNull($claimed);
        $this->assertSame('processing', $claimed->status);

        $secondClaim = $service->claimForProcessing($event);
        $this->assertNull($secondClaim);
    }

    public function test_processed_event_is_detected_as_duplicate(): void
    {
        $service = new PaymentIdempotencyService;

        $event = PaymentEvent::create([
            'gateway' => 'bkash',
            'event_key' => 'evt_002',
            'status' => 'accepted',
            'processed_at' => now(),
        ]);

        $this->assertTrue($service->isDuplicateProcessed($event));
        $this->assertTrue($service->isDuplicateAccepted($event));
    }

    private function createPaymentEventsTable(): void
    {
        Schema::dropIfExists('payment_events');

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
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
    }
}
