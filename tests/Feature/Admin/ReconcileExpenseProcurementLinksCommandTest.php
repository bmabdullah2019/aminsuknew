<?php

namespace Tests\Feature\Admin;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class ReconcileExpenseProcurementLinksCommandTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedBaseData();
    }

    public function test_dry_run_strict_fails_and_does_not_mutate_supplier_link(): void
    {
        DB::table('expenses')->insert([
            'id' => 1,
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => now()->toDateString(),
            'supplier_id' => null,
            'purchase_order_id' => 100,
            'grn_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('expenses:reconcile-procurement-links --strict')
            ->assertExitCode(1);

        $this->assertNull(DB::table('expenses')->where('id', 1)->value('supplier_id'));
    }

    public function test_apply_strict_fixes_missing_supplier_link_and_succeeds_when_no_other_issues(): void
    {
        DB::table('expenses')->insert([
            'id' => 1,
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => now()->toDateString(),
            'supplier_id' => null,
            'purchase_order_id' => 100,
            'grn_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('expenses:reconcile-procurement-links --apply --strict')
            ->assertExitCode(0);

        $this->assertSame(
            '1',
            (string) (int) DB::table('expenses')->where('id', 1)->value('supplier_id')
        );
    }

    public function test_apply_strict_fixes_safe_rows_but_fails_when_unresolved_mismatch_remains(): void
    {
        DB::table('expenses')->insert([
            [
                'id' => 1,
                'expense_number' => 'EXP-2026-0001',
                'expense_date' => now()->toDateString(),
                'supplier_id' => null,
                'purchase_order_id' => 100,
                'grn_id' => null,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'expense_number' => 'EXP-2026-0002',
                'expense_date' => now()->toDateString(),
                'supplier_id' => 2,
                'purchase_order_id' => 100,
                'grn_id' => null,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->artisan('expenses:reconcile-procurement-links --apply --strict')
            ->assertExitCode(1);

        $this->assertSame(
            '1',
            (string) (int) DB::table('expenses')->where('id', 1)->value('supplier_id')
        );
        $this->assertSame(
            '2',
            (string) (int) DB::table('expenses')->where('id', 2)->value('supplier_id')
        );
    }

    public function test_export_option_writes_unresolved_issues_csv(): void
    {
        DB::table('expenses')->insert([
            'id' => 1,
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => now()->toDateString(),
            'supplier_id' => 2,
            'purchase_order_id' => 100,
            'grn_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exportFile = 'expense_reconcile_test_'.uniqid('', true).'.csv';
        $expectedPath = storage_path('app/reconciliation/'.$exportFile);

        if (file_exists($expectedPath)) {
            @unlink($expectedPath);
        }

        $this->artisan('expenses:reconcile-procurement-links --export='.$exportFile)
            ->assertExitCode(0);

        $this->assertFileExists($expectedPath);
        $contents = (string) file_get_contents($expectedPath);
        $this->assertStringContainsString('expense_id,expense_number,supplier_id,purchase_order_id,grn_id,issues', $contents);
        $this->assertStringContainsString('1,EXP-2026-0001,2,100,0,supplier_mismatch', $contents);

        @unlink($expectedPath);
    }

    public function test_json_option_outputs_machine_readable_summary(): void
    {
        DB::table('expenses')->insert([
            'id' => 1,
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => now()->toDateString(),
            'supplier_id' => 2,
            'purchase_order_id' => 100,
            'grn_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('expenses:reconcile-procurement-links', [
            '--json' => true,
            '--max_issues' => 5,
        ]);

        $this->assertSame(0, $exitCode);
        $payload = json_decode(trim((string) Artisan::output()), true);

        $this->assertIsArray($payload);
        $this->assertSame('dry-run', $payload['mode'] ?? null);
        $this->assertSame(1, (int) ($payload['metrics']['supplier_mismatch'] ?? 0));
        $this->assertSame(1, (int) ($payload['issues_total'] ?? 0));
    }

    private function createTables(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('grns');
        Schema::dropIfExists('suppliers');

        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('po_number')->nullable();
            $table->timestamps();
        });

        Schema::create('grns', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('grn_number')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('expense_number');
            $table->date('expense_date')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('purchase_order_id')->nullable();
            $table->unsignedInteger('grn_id')->nullable();
            $table->string('status', 40)->default('pending');
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('suppliers')->insert([
            [
                'id' => 1,
                'name' => 'Supplier One',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Supplier Two',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('purchase_orders')->insert([
            'id' => 100,
            'supplier_id' => 1,
            'po_number' => 'PO-2026-0100',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
