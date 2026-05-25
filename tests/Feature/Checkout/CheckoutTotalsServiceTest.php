<?php

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\CheckoutTotalsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class CheckoutTotalsServiceTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createTables();
    }

    public function test_it_calculates_totals_from_server_side_catalog_prices(): void
    {
        \DB::table('products')->insert([
            'id' => 101,
            'status' => 1,
            'new_price' => 999,
            'new_price_minor' => 2500,
            'currency' => 'BDT',
        ]);

        \DB::table('shipping_charges')->insert([
            'id' => 5,
            'name' => 'Inside Dhaka',
            'amount' => 120,
            'amount_minor' => 1000,
            'status' => 1,
            'currency' => 'BDT',
        ]);

        $cartItems = new Collection([
            (object) [
                'id' => 101,
                'rowId' => 'row-101',
                'qty' => 2,
                // Client-side tampering, should be ignored by service:
                'price' => 1,
            ],
        ]);

        $totals = app(CheckoutTotalsService::class)->calculateForShoppingCart($cartItems, 5, 500);

        $this->assertSame(5000, $totals['subtotal_minor']);
        $this->assertSame(1000, $totals['shipping_minor']);
        $this->assertSame(500, $totals['discount_minor']);
        $this->assertSame(5500, $totals['final_minor']);
    }

    public function test_it_rejects_invalid_shipping_charge(): void
    {
        \DB::table('products')->insert([
            'id' => 202,
            'status' => 1,
            'new_price' => 100,
            'new_price_minor' => 10000,
            'currency' => 'BDT',
        ]);

        $cartItems = new Collection([
            (object) ['id' => 202, 'rowId' => 'row-202', 'qty' => 1],
        ]);

        $this->expectException(ValidationException::class);
        app(CheckoutTotalsService::class)->calculateForShoppingCart($cartItems, 999);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('shipping_charges');

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('new_price')->default(0);
            $table->unsignedBigInteger('new_price_minor')->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->timestamps();
        });

        Schema::create('shipping_charges', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->string('currency', 3)->default('BDT');
            $table->timestamps();
        });
    }
}
