<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\ExpenseController;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class ExpenseProcurementLinkingTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
        $this->be(User::query()->findOrFail(1));
    }

    public function test_store_auto_assigns_supplier_from_purchase_order(): void
    {
        $response = $this->callStore([
            'purchase_order_id' => 100,
        ]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/admin/expense', $response->getTargetUrl());

        $expense = DB::table('expenses')->first();
        $this->assertNotNull($expense);
        $this->assertSame('1', (string) (int) $expense->supplier_id);
        $this->assertSame('100', (string) (int) $expense->purchase_order_id);
    }

    public function test_store_rejects_purchase_order_supplier_mismatch(): void
    {
        try {
            $this->callStore([
                'supplier_id' => 2,
                'purchase_order_id' => 100,
            ]);
            $this->fail('Expected ValidationException for supplier and purchase order mismatch.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('purchase_order_id', $e->errors());
        }

        $this->assertSame(0, DB::table('expenses')->count());
    }

    public function test_update_rejects_grn_supplier_mismatch(): void
    {
        DB::table('expenses')->insert([
            'id' => 1,
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => now()->toDateString(),
            'category_id' => 1,
            'supplier_id' => 1,
            'purchase_order_id' => null,
            'grn_id' => null,
            'total_amount' => 200.00,
            'payment_method' => 'cash',
            'description' => 'Initial expense',
            'notes' => null,
            'status' => 'pending',
            'created_by' => 1,
            'approved_by' => null,
            'approved_at' => null,
            'paid_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->callUpdate(1, [
                'supplier_id' => 2,
                'grn_id' => 200,
            ]);
            $this->fail('Expected ValidationException for supplier and GRN mismatch.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('grn_id', $e->errors());
        }

        $expense = DB::table('expenses')->where('id', 1)->first();
        $this->assertNotNull($expense);
        $this->assertSame('1', (string) (int) $expense->supplier_id);
        $this->assertNull($expense->grn_id);
        $this->assertSame('200.00', number_format((float) $expense->total_amount, 2, '.', ''));
    }

    private function callStore(array $overrides = []): RedirectResponse
    {
        $payload = array_merge([
            'expense_date' => now()->toDateString(),
            'category_id' => 1,
            'total_amount' => 150.00,
            'payment_method' => 'cash',
            'description' => 'Transport expense',
            'notes' => 'Store test',
        ], $overrides);

        $request = Request::create('/admin/expense/store', 'POST', $payload);
        $request->headers->set('referer', 'http://localhost/admin/expense/create');
        $request->setLaravelSession(app('session.store'));

        /** @var RedirectResponse $response */
        $response = app(ExpenseController::class)->store($request);

        return $response;
    }

    private function callUpdate(int $expenseId, array $overrides = []): RedirectResponse
    {
        $payload = array_merge([
            'expense_date' => now()->toDateString(),
            'category_id' => 1,
            'total_amount' => 210.00,
            'payment_method' => 'cash',
            'description' => 'Updated expense',
            'notes' => 'Update test',
        ], $overrides);

        $request = Request::create('/admin/expense/'.$expenseId, 'PUT', $payload);
        $request->headers->set('referer', 'http://localhost/admin/expense/'.$expenseId.'/edit');
        $request->setLaravelSession(app('session.store'));

        $expense = Expense::query()->findOrFail($expenseId);

        /** @var RedirectResponse $response */
        $response = app(ExpenseController::class)->update($request, $expense);

        return $response;
    }

    private function createTables(): void
    {
        Schema::dropIfExists('expense_logs');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('grns');
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
            $table->string('supplier_code', 30)->nullable();
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('po_number', 60);
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('status', 40)->default('draft');
            $table->timestamps();
        });

        Schema::create('grns', function (Blueprint $table) {
            $table->increments('id');
            $table->string('grn_number', 60);
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('status', 40)->default('draft');
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('expense_number', 60)->unique();
            $table->date('expense_date');
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('purchase_order_id')->nullable();
            $table->unsignedInteger('grn_id')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method', 30);
            $table->string('bank_name')->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('card_number')->nullable();
            $table->text('description');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('expense_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->string('action', 100);
            $table->text('description');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Expense User',
            'email' => 'expense@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('expense_categories')->insert([
            'id' => 1,
            'name' => 'Logistics',
            'code' => 'LOG',
            'is_active' => 1,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('suppliers')->insert([
            [
                'id' => 1,
                'supplier_code' => 'SUP-001',
                'name' => 'Supplier One',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'supplier_code' => 'SUP-002',
                'name' => 'Supplier Two',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('purchase_orders')->insert([
            [
                'id' => 100,
                'po_number' => 'PO-2026-0100',
                'supplier_id' => 1,
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 101,
                'po_number' => 'PO-2026-0101',
                'supplier_id' => 2,
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('grns')->insert([
            [
                'id' => 200,
                'grn_number' => 'GRN-2026-0200',
                'supplier_id' => 1,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 201,
                'grn_number' => 'GRN-2026-0201',
                'supplier_id' => 2,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
