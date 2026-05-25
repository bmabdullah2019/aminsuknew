<?php

namespace Tests\Unit\Services;

use App\Domain\Inventory\Exceptions\StockOperationException;
use App\Services\StockEngine;
use App\Services\VariantStockService;
use App\Services\WarehouseStockService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class StockEngineTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
    }

    public function test_reserve_non_variant_calls_warehouse_service_with_reference(): void
    {
        $warehouseStockService = Mockery::mock(WarehouseStockService::class);
        $variantStockService = Mockery::mock(VariantStockService::class);

        $warehouseStockService
            ->shouldReceive('reserveStock')
            ->once()
            ->with(3, 11, 2.0, 9001, 'order', Mockery::type('string'));

        $variantStockService->shouldNotReceive('reserveStock');

        $engine = new StockEngine($warehouseStockService, $variantStockService);
        $engine->reserve(3, 11, 2.0, 9001, 'order', null, 'reserve test');
    }

    public function test_variant_reserve_requires_order_reference(): void
    {
        $warehouseStockService = Mockery::mock(WarehouseStockService::class);
        $variantStockService = Mockery::mock(VariantStockService::class);

        $engine = new StockEngine($warehouseStockService, $variantStockService);

        $this->expectException(StockOperationException::class);
        $engine->reserve(3, 11, 2.0, null, 'grn', 77);
    }
}
