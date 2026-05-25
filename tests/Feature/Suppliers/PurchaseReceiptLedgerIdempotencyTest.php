<?php

namespace Tests\Feature\Suppliers;

use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class PurchaseReceiptLedgerIdempotencyTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
    }

    public function test_sync_purchase_receipt_ledger_posts_only_delta_and_is_idempotent(): void
    {
        $supplier = Supplier::query()->findOrFail(1);
        $service = app(SupplierService::class);

        $first = $service->syncPurchaseReceiptLedger($supplier, [
            'purchase_id' => 1001,
            'target_received_amount' => 150.00,
            'purchase_date' => '2026-02-24',
            'purchase_number' => 'PO-2026-1001',
            'created_by' => 1,
        ]);

        $second = $service->syncPurchaseReceiptLedger($supplier, [
            'purchase_id' => 1001,
            'target_received_amount' => 150.00,
            'purchase_date' => '2026-02-24',
            'purchase_number' => 'PO-2026-1001',
            'created_by' => 1,
        ]);

        $third = $service->syncPurchaseReceiptLedger($supplier, [
            'purchase_id' => 1001,
            'target_received_amount' => 220.00,
            'purchase_date' => '2026-02-24',
            'purchase_number' => 'PO-2026-1001',
            'created_by' => 1,
        ]);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertNotNull($third);

        $this->assertSame(2, DB::table('supplier_ledgers')->count());
        $this->assertSame(
            '220.00',
            number_format((float) DB::table('supplier_ledgers')
                ->where('reference_type', 'purchase_receipt')
                ->where('reference_id', 1001)
                ->sum('debit'), 2, '.', '')
        );
        $this->assertSame(
            '70.00',
            number_format((float) ($third->debit ?? 0), 2, '.', '')
        );
        $this->assertSame(
            '220.00',
            number_format($service->getPostedPurchaseReceiptAmount(1, 1001), 2, '.', '')
        );
    }

    private function createTables(): void
    {
        Schema::dropIfExists('supplier_ledgers');
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
            'name' => 'Ledger User',
            'email' => 'ledger@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('suppliers')->insert([
            'id' => 1,
            'supplier_code' => 'SUP-001',
            'name' => 'Supplier One',
            'status' => 'active',
            'credit_limit' => 10000,
            'payment_terms_days' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
