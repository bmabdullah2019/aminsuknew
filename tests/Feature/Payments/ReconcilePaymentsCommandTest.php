<?php

namespace Tests\Feature\Payments;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class ReconcilePaymentsCommandTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
    }

    public function test_json_option_outputs_machine_readable_summary(): void
    {
        $exitCode = Artisan::call('payments:reconcile', [
            '--date' => now()->toDateString(),
            '--json' => true,
            '--max_issues' => 10,
        ]);

        $this->assertSame(0, $exitCode);
        $payload = json_decode(trim((string) Artisan::output()), true);

        $this->assertIsArray($payload);
        $this->assertSame(now()->toDateString(), $payload['date'] ?? null);
        $this->assertSame(2, (int) ($payload['metrics']['payments_checked'] ?? 0));
        $this->assertSame(1, (int) ($payload['metrics']['payment_mismatches'] ?? 0));
        $this->assertSame(1, (int) ($payload['metrics']['unresolved_events'] ?? 0));
        $this->assertSame(1, (int) ($payload['metrics']['duplicate_events_ignored'] ?? 0));
        $this->assertFalse((bool) ($payload['ok'] ?? true));
    }

    public function test_json_option_returns_error_payload_for_invalid_date(): void
    {
        $exitCode = Artisan::call('payments:reconcile', [
            '--date' => '2026-99-99',
            '--json' => true,
        ]);

        $this->assertSame(2, $exitCode);
        $payload = json_decode(trim((string) Artisan::output()), true);

        $this->assertIsArray($payload);
        $this->assertFalse((bool) ($payload['ok'] ?? true));
        $this->assertStringContainsString('Invalid --date value', (string) ($payload['error'] ?? ''));
    }

    private function createTables(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id')->nullable();
            $table->string('gateway')->nullable();
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->timestamps();
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('gateway', 30);
            $table->string('event_key', 150);
            $table->unsignedInteger('order_id')->nullable();
            $table->string('status', 30)->default('received');
            $table->text('status_reason')->nullable();
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('orders')->insert([
            'id' => 1,
            'amount_minor' => 10000,
            'currency' => 'BDT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            [
                'id' => 1,
                'order_id' => 1,
                'gateway' => 'bkash',
                'amount_minor' => 5000,
                'currency' => 'BDT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'order_id' => 1,
                'gateway' => 'cod',
                'amount_minor' => 10000,
                'currency' => 'BDT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'order_id' => 1,
                'gateway' => 'other_gateway',
                'amount_minor' => 10000,
                'currency' => 'BDT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('payment_events')->insert([
            [
                'id' => 1,
                'gateway' => 'bkash',
                'event_key' => 'evt-rejected',
                'order_id' => 1,
                'status' => 'rejected',
                'status_reason' => 'Mismatch',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'gateway' => 'bkash',
                'event_key' => 'evt-duplicate',
                'order_id' => 1,
                'status' => 'duplicate_ignored',
                'status_reason' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
