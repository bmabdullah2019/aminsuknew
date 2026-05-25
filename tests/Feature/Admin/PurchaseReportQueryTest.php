<?php

namespace Tests\Feature\Admin;

use App\Modules\Reports\Queries\PurchaseReportQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class PurchaseReportQueryTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemorySqliteDatabase();
        $this->createTables();
        $this->seedData();
    }

    public function test_lines_return_grn_rows_even_without_purchase_order_tables(): void
    {
        $rows = app(PurchaseReportQuery::class)->lines(null, null)->get();

        $this->assertCount(1, $rows);
        $this->assertSame('GRN-2026-001', $rows[0]->order_number);
        $this->assertSame('Acme Supplier', $rows[0]->supplier_name);
        $this->assertSame('Demo Product', $rows[0]->product_name);
        $this->assertSame('Red', $rows[0]->color);
        $this->assertSame(5.0, (float) $rows[0]->quantity_received);
        $this->assertSame(52.0, (float) $rows[0]->total_cost);
    }

    public function test_summary_aggregates_grn_quantities_and_amounts(): void
    {
        $summary = app(PurchaseReportQuery::class)->summary(null, null, [
            'keyword' => 'GRN-2026-001',
        ]);

        $this->assertSame(1, (int) $summary->total_orders);
        $this->assertSame(7.0, (float) $summary->total_ordered_quantity);
        $this->assertSame(5.0, (float) $summary->total_received_quantity);
        $this->assertSame(72.0, (float) $summary->total_ordered_cost);
        $this->assertSame(52.0, (float) $summary->total_purchase_amount);
    }

    private function createTables(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('supplier_code')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id')->nullable();
            $table->string('sku_code')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('age')->nullable();
            $table->timestamps();
        });

        Schema::create('grns', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('grn_number');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->date('grn_date');
            $table->string('invoice_number')->nullable();
            $table->string('status')->default('approved');
            $table->timestamps();
        });

        Schema::create('grn_items', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('grn_id');
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('ordered_quantity', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    private function seedData(): void
    {
        DB::table('suppliers')->insert([
            'id' => 1,
            'supplier_code' => 'SUP-001',
            'name' => 'Acme Supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'Main Warehouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => 1,
            'name' => 'Demo Product',
            'sku' => 'SKU-001',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variants')->insert([
            'id' => 1,
            'product_id' => 1,
            'sku_code' => 'SKU-001-RED',
            'color' => 'Red',
            'size' => 'XL',
            'age' => 'Adult',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('grns')->insert([
            'id' => 1,
            'grn_number' => 'GRN-2026-001',
            'warehouse_id' => 1,
            'supplier_id' => 1,
            'grn_date' => '2026-04-23',
            'invoice_number' => 'INV-100',
            'status' => 'approved',
            'created_at' => '2026-04-23 10:00:00',
            'updated_at' => '2026-04-23 10:00:00',
        ]);

        DB::table('grn_items')->insert([
            'id' => 1,
            'grn_id' => 1,
            'product_id' => 1,
            'product_variant_id' => 1,
            'sku' => 'SKU-001',
            'description' => 'Demo Product purchase line',
            'quantity' => 5,
            'ordered_quantity' => 7,
            'unit_cost' => 10,
            'tax_amount' => 2,
            'created_at' => '2026-04-23 10:00:00',
            'updated_at' => '2026-04-23 10:00:00',
        ]);
    }
}
