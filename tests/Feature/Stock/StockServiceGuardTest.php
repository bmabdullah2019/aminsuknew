<?php

namespace Tests\Feature\Stock;

use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class StockServiceGuardTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createStockTable();
    }

    public function test_stock_service_prevents_negative_available_quantity(): void
    {
        Stock::create([
            'product_id' => 55,
            'variant_id' => null,
            'available_qty' => 1.00,
            'reserved_qty' => 0.00,
            'sold_qty' => 0.00,
        ]);

        $service = app(StockService::class);

        $firstReserve = $service->reserveStock(55, null, 1);
        $secondReserve = $service->reserveStock(55, null, 1);

        $this->assertTrue($firstReserve);
        $this->assertFalse($secondReserve);

        $stock = Stock::query()->where('product_id', 55)->whereNull('variant_id')->firstOrFail();
        $this->assertSame('0.00', number_format((float) $stock->available_qty, 2, '.', ''));
        $this->assertSame('1.00', number_format((float) $stock->reserved_qty, 2, '.', ''));
        $this->assertGreaterThanOrEqual(0, (float) $stock->available_qty);
    }

    private function createStockTable(): void
    {
        Schema::dropIfExists('stocks');

        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('available_qty', 10, 2)->default(0);
            $table->decimal('reserved_qty', 10, 2)->default(0);
            $table->decimal('sold_qty', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'variant_id']);
        });
    }
}
