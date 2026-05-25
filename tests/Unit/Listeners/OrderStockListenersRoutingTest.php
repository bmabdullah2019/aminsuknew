<?php

namespace Tests\Unit\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Listeners\ReleaseStockOnOrderCancelled;
use App\Listeners\ReserveStockOnOrderPlaced;
use App\Listeners\UpdateStockOnOrderShipped;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Services\StockEngine;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class OrderStockListenersRoutingTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_order_placed_delegates_to_stock_engine(): void
    {
        $stockEngine = Mockery::mock(StockEngine::class);
        $order = $this->makeOrder(1001, 2, [
            ['product_id' => 11, 'variant_id' => 44, 'qty' => 3, 'warehouse_id' => 2],
        ]);

        $stockEngine
            ->shouldReceive('reserveForOrder')
            ->once()
            ->with($order);

        $listener = new ReserveStockOnOrderPlaced($stockEngine);
        $listener->handle(new OrderPlaced($order));
    }

    public function test_order_cancelled_delegates_to_stock_engine(): void
    {
        $stockEngine = Mockery::mock(StockEngine::class);
        $order = $this->makeOrder(2001, 5, [
            ['product_id' => 10, 'variant_id' => 70, 'qty' => 1, 'warehouse_id' => 5],
        ]);

        $stockEngine
            ->shouldReceive('releaseForOrder')
            ->once()
            ->with($order);

        $listener = new ReleaseStockOnOrderCancelled($stockEngine);
        $listener->handle(new OrderCancelled($order));
    }

    public function test_order_shipped_delegates_to_stock_engine(): void
    {
        $stockEngine = Mockery::mock(StockEngine::class);
        $order = $this->makeOrder(2002, 6, [
            ['product_id' => 12, 'variant_id' => 80, 'qty' => 1, 'warehouse_id' => 6],
            ['product_id' => 13, 'variant_id' => null, 'qty' => 4, 'warehouse_id' => 6],
        ]);

        $stockEngine
            ->shouldReceive('deductForOrder')
            ->once()
            ->with($order);

        $listener = new UpdateStockOnOrderShipped($stockEngine);
        $listener->handle(new OrderShipped($order));
    }

    private function makeOrder(int $id, ?int $warehouseId, array $items): Order
    {
        $order = new Order;
        $order->id = $id;
        $order->warehouse_id = $warehouseId;
        $order->setRelation('orderdetails', new Collection(array_map(function (array $item): OrderDetails {
            $detail = new OrderDetails;
            $detail->product_id = $item['product_id'];
            $detail->product_variant_id = $item['variant_id'];
            $detail->qty = $item['qty'];
            $detail->warehouse_id = $item['warehouse_id'];

            return $detail;
        }, $items)));

        return $order;
    }
}
