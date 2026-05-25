<?php

namespace Tests\Feature\Returns;

use App\Models\ReturnOrder;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\ReturnService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class ReturnCompletionIdempotencyTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
    }

    public function test_complete_return_is_idempotent_for_financial_postings_and_order_totals(): void
    {
        $returnService = new ReturnService(new LedgerService);
        $returnOrder = ReturnOrder::query()->findOrFail(1);
        $actor = User::query()->findOrFail(1);

        $firstRun = $returnService->completeReturn($returnOrder, $actor, ['notes' => 'first completion']);
        $secondRun = $returnService->completeReturn($returnOrder->fresh(), $actor, ['notes' => 'duplicate completion']);

        $this->assertTrue($firstRun);
        $this->assertTrue($secondRun);

        $orderMinor = (int) DB::table('orders')->where('id', 1)->value('amount_minor');
        $this->assertSame(80000, $orderMinor);

        // Must not create duplicate journals on repeated completion call.
        $this->assertSame(2, DB::table('ledger_journals')->count());
        $this->assertSame(4, DB::table('ledger_entries')->count());
        $this->assertSame(1, DB::table('ledger_journals')->where('journal_type', 'return_revenue_reversal')->count());
        $this->assertSame(1, DB::table('ledger_journals')->where('journal_type', 'return_refund_disbursement')->count());

        // Must not double-log completion/refund on repeated completion call.
        $this->assertSame(1, DB::table('return_logs')->where('action_type', 'completed')->count());
        $this->assertSame(1, DB::table('return_logs')->where('action_type', 'refunded')->count());
    }

    private function seedBaseData(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Return Actor',
            'email' => 'return-actor@example.com',
            'password' => bcrypt('secret'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'id' => 1,
            'amount' => 1000,
            'amount_minor' => 100000,
            'order_status' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('return_orders')->insert([
            'id' => 1,
            'return_number' => 'RTR-TEST-1',
            'order_id' => 1,
            'customer_id' => null,
            'return_status' => 'processing',
            'return_source' => 'customer',
            'return_type' => 'partial',
            'return_reason_id' => null,
            'refund_amount' => 180.00,
            'refund_method' => 'cash',
            'restock_flag' => 0,
            'damage_flag' => 0,
            'total_return_value' => 200.00,
            'notes' => null,
            'created_by' => 1,
            'processed_by' => 1,
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('return_items')->insert([
            'id' => 1,
            'return_order_id' => 1,
            'order_detail_id' => null,
            'product_id' => 1,
            'warehouse_id' => 1,
            'return_quantity' => 2,
            'unit_price' => 100,
            'unit_cost' => 50,
            'return_condition' => 'opened',
            'restock_quantity' => 0,
            'damage_quantity' => 0,
            'refund_amount' => 180,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_journals');
        Schema::dropIfExists('return_logs');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_orders');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->unsignedInteger('order_status')->default(1);
            $table->timestamps();
        });

        Schema::create('return_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('return_number')->nullable();
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('return_status', 30)->default('pending');
            $table->string('return_source', 30)->nullable();
            $table->string('return_type', 30)->nullable();
            $table->unsignedInteger('return_reason_id')->nullable();
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('refund_method', 30)->nullable();
            $table->boolean('restock_flag')->default(false);
            $table->boolean('damage_flag')->default(false);
            $table->decimal('total_return_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('return_order_id');
            $table->unsignedInteger('order_detail_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->decimal('return_quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('return_condition', 30)->nullable();
            $table->decimal('restock_quantity', 12, 2)->default(0);
            $table->decimal('damage_quantity', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->unsignedInteger('replacement_order_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('return_logs', function (Blueprint $table) {
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

        Schema::create('ledger_journals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('journal_number', 40)->unique();
            $table->string('journal_type', 60)->default('general');
            $table->date('journal_date');
            $table->string('reference_type', 60)->nullable();
            $table->unsignedInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->unsignedBigInteger('total_debit_minor')->default(0);
            $table->unsignedBigInteger('total_credit_minor')->default(0);
            $table->string('status', 20)->default('posted');
            $table->string('immutable_hash', 64);
            $table->text('meta')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['journal_type', 'reference_type', 'reference_id'], 'ledger_journal_type_reference_unique');
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ledger_journal_id');
            $table->string('account_code', 80);
            $table->string('direction', 10);
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('BDT');
            $table->text('description')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }
}
