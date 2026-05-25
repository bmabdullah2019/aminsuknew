<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\ExpenseController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class ExpenseIndexProcurementFilterTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
    }

    public function test_index_filters_expenses_by_supplier_id(): void
    {
        $view = $this->callIndex([
            'supplier_id' => 1,
        ]);

        $ids = collect($view->getData()['expenses']->items())->pluck('id')->values()->all();
        $this->assertSame([1], $ids);
    }

    public function test_index_filters_expenses_by_purchase_order_id(): void
    {
        $view = $this->callIndex([
            'purchase_order_id' => 202,
        ]);

        $ids = collect($view->getData()['expenses']->items())->pluck('id')->values()->all();
        $this->assertSame([2], $ids);
    }

    public function test_index_filters_expenses_by_grn_id(): void
    {
        $view = $this->callIndex([
            'grn_id' => 301,
        ]);

        $ids = collect($view->getData()['expenses']->items())->pluck('id')->values()->all();
        $this->assertSame([1], $ids);
    }

    private function callIndex(array $query = []): View
    {
        $request = Request::create('/admin/expense', 'GET', $query);
        /** @var View $view */
        $view = app(ExpenseController::class)->index($request);

        return $view;
    }

    private function createTables(): void
    {
        Schema::dropIfExists('expense_allocations');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('grns');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('supplier_code')->nullable();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('po_number');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('status', 40)->default('draft');
            $table->timestamps();
        });

        Schema::create('grns', function (Blueprint $table) {
            $table->increments('id');
            $table->string('grn_number');
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
            $table->string('expense_number', 50)->unique();
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
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_allocations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('expense_id');
            $table->unsignedInteger('warehouse_id');
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Index User',
            'email' => 'index@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'Main Warehouse',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('expense_categories')->insert([
            'id' => 1,
            'name' => 'Operations',
            'code' => 'OPS',
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
                'id' => 201,
                'po_number' => 'PO-201',
                'supplier_id' => 1,
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 202,
                'po_number' => 'PO-202',
                'supplier_id' => 2,
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('grns')->insert([
            [
                'id' => 301,
                'grn_number' => 'GRN-301',
                'supplier_id' => 1,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 302,
                'grn_number' => 'GRN-302',
                'supplier_id' => 2,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('expenses')->insert([
            [
                'id' => 1,
                'expense_number' => 'EXP-2026-0001',
                'expense_date' => now()->toDateString(),
                'category_id' => 1,
                'supplier_id' => 1,
                'purchase_order_id' => 201,
                'grn_id' => 301,
                'total_amount' => 120.00,
                'payment_method' => 'cash',
                'description' => 'Supplier One expense',
                'status' => 'pending',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'expense_number' => 'EXP-2026-0002',
                'expense_date' => now()->toDateString(),
                'category_id' => 1,
                'supplier_id' => 2,
                'purchase_order_id' => 202,
                'grn_id' => 302,
                'total_amount' => 240.00,
                'payment_method' => 'cash',
                'description' => 'Supplier Two expense',
                'status' => 'pending',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
