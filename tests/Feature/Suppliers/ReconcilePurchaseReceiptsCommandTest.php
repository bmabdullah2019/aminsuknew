<?php

namespace Tests\Feature\Suppliers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class ReconcilePurchaseReceiptsCommandTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
    }

    public function test_reconcile_purchase_receipts_apply_posts_missing_delta_and_updates_snapshots(): void
    {
        $this->artisan('purchase-orders:reconcile-receipts --apply --strict')
            ->assertExitCode(0);

        $posted = (float) DB::table('supplier_ledgers')
            ->where('supplier_id', 1)
            ->where('transaction_type', 'purchase')
            ->where('reference_type', 'purchase_receipt')
            ->where('reference_id', 1)
            ->sum('debit');

        $this->assertSame('100.00', number_format($posted, 2, '.', ''));

        $snapshotPosted = (float) DB::table('purchase_orders')
            ->where('id', 1)
            ->value('ledger_posted_amount');
        $this->assertSame('100.00', number_format($snapshotPosted, 2, '.', ''));

        $receivedAt = DB::table('purchase_orders')->where('id', 1)->value('received_at');
        $this->assertNotNull($receivedAt);
    }

    public function test_reconcile_purchase_receipts_dry_run_strict_fails_without_mutation(): void
    {
        $this->artisan('purchase-orders:reconcile-receipts --strict')
            ->assertExitCode(1);

        $this->assertSame(0, DB::table('supplier_ledgers')->count());
        $this->assertSame(
            '0.00',
            number_format((float) DB::table('purchase_orders')->where('id', 1)->value('ledger_posted_amount'), 2, '.', '')
        );
        $this->assertNull(DB::table('purchase_orders')->where('id', 1)->value('received_at'));
    }

    public function test_reconcile_purchase_receipts_apply_strict_fails_when_over_posted_exists(): void
    {
        $this->seedOverPostedCase();

        $this->artisan('purchase-orders:reconcile-receipts --apply --strict')
            ->assertExitCode(1);

        // Under-posted PO #1 should still be fixed.
        $po1Posted = (float) DB::table('supplier_ledgers')
            ->where('reference_type', 'purchase_receipt')
            ->where('reference_id', 1)
            ->sum('debit');
        $this->assertSame('100.00', number_format($po1Posted, 2, '.', ''));

        // Over-posted PO #2 should remain unresolved for manual review.
        $po2Posted = (float) DB::table('supplier_ledgers')
            ->where('reference_type', 'purchase_receipt')
            ->where('reference_id', 2)
            ->sum('debit');
        $this->assertSame('70.00', number_format($po2Posted, 2, '.', ''));

        $po2SnapshotPosted = (float) DB::table('purchase_orders')->where('id', 2)->value('ledger_posted_amount');
        $this->assertSame('70.00', number_format($po2SnapshotPosted, 2, '.', ''));
    }

    public function test_reconcile_purchase_receipts_json_option_outputs_machine_readable_summary(): void
    {
        $exitCode = Artisan::call('purchase-orders:reconcile-receipts', [
            '--json' => true,
            '--max_issues' => 10,
        ]);

        $this->assertSame(0, $exitCode);
        $payload = json_decode(trim((string) Artisan::output()), true);

        $this->assertIsArray($payload);
        $this->assertSame('dry-run', $payload['mode'] ?? null);
        $this->assertSame(1, (int) ($payload['metrics']['under_posted'] ?? 0));
        $this->assertSame(1, (int) ($payload['issues_total'] ?? 0));
    }

    private function createTables(): void
    {
        Schema::dropIfExists('supplier_ledgers');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('supplier_code')->nullable();
            $table->string('name');
            $table->string('status')->default('active');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('payment_terms_days')->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('po_number');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('status', 40)->default('draft');
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->decimal('ledger_posted_amount', 14, 2)->default(0);
            $table->timestamp('expected_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('purchase_order_id');
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->decimal('quantity_ordered', 12, 2)->default(0);
            $table->decimal('quantity_received', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_ledgers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('supplier_id');
            $table->date('transaction_date');
            $table->string('transaction_type', 40);
            $table->string('reference_type', 40)->nullable();
            $table->unsignedInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('running_balance', 15, 2)->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Reconcile User',
            'email' => 'reconcile@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('suppliers')->insert([
            'id' => 1,
            'supplier_code' => 'SUP-001',
            'name' => 'Supplier One',
            'status' => 'active',
            'credit_limit' => 50000,
            'payment_terms_days' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_orders')->insert([
            'id' => 1,
            'po_number' => 'PO-2026-0001',
            'supplier_id' => 1,
            'warehouse_id' => 1,
            'status' => 'received',
            'total_cost' => 120,
            'ordered_at' => now()->subDay(),
            'received_at' => null,
            'ledger_posted_amount' => 0,
            'created_by' => 1,
            'approved_by' => 1,
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_items')->insert([
            [
                'purchase_order_id' => 1,
                'product_variant_id' => 11,
                'quantity_ordered' => 4,
                'quantity_received' => 2,
                'unit_cost' => 30,
                'line_total' => 120,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'purchase_order_id' => 1,
                'product_variant_id' => 12,
                'quantity_ordered' => 2,
                'quantity_received' => 1,
                'unit_cost' => 40,
                'line_total' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedOverPostedCase(): void
    {
        DB::table('purchase_orders')->insert([
            'id' => 2,
            'po_number' => 'PO-2026-0002',
            'supplier_id' => 1,
            'warehouse_id' => 1,
            'status' => 'received',
            'total_cost' => 70,
            'ordered_at' => now()->subDay(),
            'received_at' => null,
            'ledger_posted_amount' => 0,
            'created_by' => 1,
            'approved_by' => 1,
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_items')->insert([
            [
                'purchase_order_id' => 2,
                'product_variant_id' => 21,
                'quantity_ordered' => 2,
                'quantity_received' => 1,
                'unit_cost' => 50,
                'line_total' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Pre-existing over-posted entry (target=50, posted=70).
        DB::table('supplier_ledgers')->insert([
            'supplier_id' => 1,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'purchase',
            'reference_type' => 'purchase_receipt',
            'reference_id' => 2,
            'reference_number' => 'PO-2026-0002',
            'description' => 'Manual over-posted entry',
            'debit' => 70,
            'credit' => 0,
            'running_balance' => 70,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
