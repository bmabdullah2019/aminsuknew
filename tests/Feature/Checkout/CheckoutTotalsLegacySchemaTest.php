<?php

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\CheckoutTotalsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class CheckoutTotalsLegacySchemaTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createLegacyTables();
    }

    public function test_it_calculates_totals_without_minor_columns_in_legacy_schema(): void
    {
        \DB::table('products')->insert([
            'id' => 301,
            'status' => 1,
            'new_price' => 99.50,
            'currency' => 'BDT',
        ]);

        \DB::table('shipping_charges')->insert([
            'id' => 9,
            'name' => 'Legacy Area',
            'amount' => 10.75,
            'status' => 1,
            'currency' => 'BDT',
        ]);

        $cartItems = new Collection([
            (object) [
                'id' => 301,
                'rowId' => 'row-301',
                'qty' => 3,
            ],
        ]);

        $totals = app(CheckoutTotalsService::class)->calculateForShoppingCart($cartItems, 9, 125);

        $this->assertSame(29850, $totals['subtotal_minor']);
        $this->assertSame(1075, $totals['shipping_minor']);
        $this->assertSame(125, $totals['discount_minor']);
        $this->assertSame(30800, $totals['final_minor']);
        $this->assertSame(9950, $totals['lines'][0]['unit_minor']);
    }

    private function createLegacyTables(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('shipping_charges');

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->default(1);
            $table->decimal('new_price', 12, 2)->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->timestamps();
        });

        Schema::create('shipping_charges', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->string('currency', 3)->default('BDT');
            $table->timestamps();
        });
    }
}
