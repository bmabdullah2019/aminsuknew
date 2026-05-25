<?php

namespace Tests\Feature\Accounts;

use App\Models\Payment;
use App\Models\PaymentHeadMapping;
use App\Models\SupplierPayment;
use App\Services\BranchAccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class BranchAccountingServiceTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedAccounts();
        $this->seedSettings();
        $this->seedMappings();
    }

    public function test_customer_payment_entry_uses_configured_receivable_head_and_mapped_receipt_head(): void
    {
        $payment = new Payment([
            'branch_id' => 1,
            'amount' => 1250.00,
            'payment_method' => 'cash',
        ]);
        $payment->id = 10;
        $payment->created_at = Carbon::parse('2026-04-23 10:00:00');

        $entry = app(BranchAccountingService::class)->postCustomerPaymentEntry($payment);

        $this->assertNotNull($entry);
        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'reference_type' => 'customer_receipt',
            'reference_id' => 10,
            'branch_id' => 1,
        ]);

        $items = DB::table('journal_entry_items')
            ->where('journal_entry_id', $entry->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $items);
        $this->assertSame(174, (int) $items[0]->account_id);
        $this->assertSame(1250.0, (float) $items[0]->debit);
        $this->assertSame(0.0, (float) $items[0]->credit);
        $this->assertSame(201, (int) $items[1]->account_id);
        $this->assertSame(0.0, (float) $items[1]->debit);
        $this->assertSame(1250.0, (float) $items[1]->credit);
    }

    public function test_supplier_payment_entry_uses_configured_payable_head_and_mapped_disbursement_head(): void
    {
        $payment = new SupplierPayment([
            'payment_number' => 'PAY-001',
            'branch_id' => 1,
            'amount' => 500.00,
            'payment_method' => 'bank_transfer',
        ]);
        $payment->id = 20;
        $payment->payment_date = Carbon::parse('2026-04-23');

        $entry = app(BranchAccountingService::class)->postSupplierPaymentEntry($payment);

        $this->assertNotNull($entry);
        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'reference_type' => 'supplier_payment',
            'reference_id' => 20,
            'branch_id' => 1,
        ]);

        $items = DB::table('journal_entry_items')
            ->where('journal_entry_id', $entry->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $items);
        $this->assertSame(202, (int) $items[0]->account_id);
        $this->assertSame(500.0, (float) $items[0]->debit);
        $this->assertSame(0.0, (float) $items[0]->credit);
        $this->assertSame(175, (int) $items[1]->account_id);
        $this->assertSame(0.0, (float) $items[1]->debit);
        $this->assertSame(500.0, (float) $items[1]->credit);
    }

    private function createTables(): void
    {
        Schema::create('accounts_head', function (Blueprint $table): void {
            $table->unsignedInteger('HeadId')->primary();
            $table->string('HeadCode')->nullable();
            $table->string('HeadName')->nullable();
            $table->boolean('Validity')->default(true);
        });

        Schema::create('accounts_settings', function (Blueprint $table): void {
            $table->unsignedInteger('Receivable')->nullable();
            $table->unsignedInteger('Payable')->nullable();
            $table->boolean('Validity')->default(true);
        });

        Schema::create('payment_head_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('context', 60);
            $table->string('payment_method', 40);
            $table->unsignedInteger('branch_id')->nullable();
            $table->unsignedInteger('account_head_id');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('branch_id');
            $table->date('date');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entry_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedInteger('account_id');
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    private function seedAccounts(): void
    {
        DB::table('accounts_head')->insert([
            ['HeadId' => 174, 'HeadCode' => '101001', 'HeadName' => 'Cash in Hand', 'Validity' => 1],
            ['HeadId' => 175, 'HeadCode' => '101002', 'HeadName' => 'Bank Account', 'Validity' => 1],
            ['HeadId' => 201, 'HeadCode' => '103001', 'HeadName' => 'Accounts Receivable', 'Validity' => 1],
            ['HeadId' => 202, 'HeadCode' => '201001', 'HeadName' => 'Accounts Payable', 'Validity' => 1],
        ]);
    }

    private function seedSettings(): void
    {
        DB::table('accounts_settings')->insert([
            'Receivable' => 201,
            'Payable' => 202,
            'Validity' => 1,
        ]);
    }

    private function seedMappings(): void
    {
        PaymentHeadMapping::query()->create([
            'context' => PaymentHeadMapping::CONTEXT_CUSTOMER_PAYMENT,
            'payment_method' => 'cash',
            'branch_id' => null,
            'account_head_id' => 174,
            'is_active' => true,
            'is_locked' => false,
        ]);

        PaymentHeadMapping::query()->create([
            'context' => PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            'payment_method' => 'bank_transfer',
            'branch_id' => null,
            'account_head_id' => 175,
            'is_active' => true,
            'is_locked' => false,
        ]);
    }
}
