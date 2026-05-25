<?php

namespace Tests\Unit\Domain\Orders;

use App\Domain\Orders\OrderStateMachine;
use App\Models\Order;
use App\Services\StockEngine;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_can_transition_respects_allowed_map(): void
    {
        $machine = new OrderStateMachine(Mockery::mock(StockEngine::class));

        $this->assertTrue($machine->canTransition(1, 2));
        $this->assertFalse($machine->canTransition(6, 2));
        $this->assertTrue($machine->canTransition(2, 2));
    }

    public function test_invalid_transition_throws_validation_exception_when_enforced(): void
    {
        config()->set('features.orders.state_machine_enforced', true);

        $machine = new OrderStateMachine(Mockery::mock(StockEngine::class));
        $order = new Order;
        $order->order_status = 6;

        $this->expectException(ValidationException::class);
        $machine->transition($order, 2, ['actor_type' => 'test']);
    }

    public function test_transition_to_delivered_bubbles_stock_failure(): void
    {
        $stockEngine = Mockery::mock(StockEngine::class);
        $order = new Order;
        $order->id = 100;
        $order->order_status = 4;

        $stockEngine
            ->shouldReceive('deductForOrder')
            ->once()
            ->with($order)
            ->andThrow(new \RuntimeException('stock failure'));

        $machine = new OrderStateMachine($stockEngine);

        $this->expectException(\RuntimeException::class);
        $machine->transition($order, 5, ['actor_type' => 'test']);
    }
}
