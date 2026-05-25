<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\Accounts\PaymentHeadMappingController;
use App\Models\PaymentHeadMapping;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class PaymentHeadMappingControllerTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedLookupData();

        $user = new User;
        $user->forceFill(['id' => 1]);
        $this->be($user);
    }

    public function test_update_creates_global_supplier_payment_mapping(): void
    {
        $response = $this->submit([
            'context' => PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            'branch_id' => null,
            'mappings' => [
                'cash' => 174,
            ],
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('payment_head_mappings', [
            'context' => PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            'payment_method' => 'cash',
            'branch_id' => null,
            'account_head_id' => 174,
            'is_active' => 1,
            'is_locked' => 0,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
    }

    public function test_update_reuses_existing_mapping_for_same_scope(): void
    {
        PaymentHeadMapping::query()->create([
            'context' => PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            'payment_method' => 'cash',
            'branch_id' => null,
            'account_head_id' => 174,
            'is_active' => true,
            'is_locked' => false,
            'created_by' => 99,
            'updated_by' => 99,
        ]);

        $this->submit([
            'context' => PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            'branch_id' => null,
            'mappings' => [
                'cash' => 175,
            ],
            'locks' => [
                'cash' => 1,
            ],
        ]);

        $this->assertSame(1, PaymentHeadMapping::query()->count());
        $this->assertDatabaseHas('payment_head_mappings', [
            'context' => PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            'payment_method' => 'cash',
            'branch_id' => null,
            'account_head_id' => 175,
            'is_locked' => 1,
            'created_by' => 99,
            'updated_by' => 1,
        ]);
    }

    public function test_update_stores_control_head_setting_for_context(): void
    {
        $this->submit([
            'context' => PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            'branch_id' => null,
            'control_head_id' => 202,
            'mappings' => [
                'cash' => 174,
            ],
        ]);

        $this->assertDatabaseHas('accounts_settings', [
            'Validity' => 1,
            'Payable' => 202,
        ]);
    }

    private function submit(array $payload)
    {
        $request = Request::create('/admin/accounts/payment-head-mappings', 'POST', $payload);

        return app(PaymentHeadMappingController::class)->update($request);
    }

    private function createTables(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->integer('status')->default(1);
        });

        Schema::create('accounts_head', function (Blueprint $table): void {
            $table->increments('HeadId');
            $table->string('HeadCode')->nullable();
            $table->string('HeadName')->nullable();
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

        Schema::create('accounts_settings', function (Blueprint $table): void {
            $table->unsignedInteger('Payable')->nullable();
            $table->unsignedInteger('Receivable')->nullable();
            $table->boolean('Validity')->default(true);
        });
    }

    private function seedLookupData(): void
    {
        \DB::table('branches')->insert([
            'id' => 1,
            'name' => 'Main Branch',
            'status' => 1,
        ]);

        \DB::table('accounts_head')->insert([
            ['HeadId' => 174, 'HeadCode' => '101001', 'HeadName' => 'Cash in Hand'],
            ['HeadId' => 175, 'HeadCode' => '101002', 'HeadName' => 'Bank Account'],
            ['HeadId' => 201, 'HeadCode' => '103001', 'HeadName' => 'Accounts Receivable'],
            ['HeadId' => 202, 'HeadCode' => '201001', 'HeadName' => 'Accounts Payable'],
        ]);
    }
}
