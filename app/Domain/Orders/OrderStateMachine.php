<?php

namespace App\Domain\Orders;

use App\Models\Order;
use App\Models\OrderStateTransition;
use App\Models\OrderStatus;
use App\Services\OrderReturnSyncService;
use App\Services\PhoneBlockService;
use App\Services\StockEngine;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderStateMachine
{
    public function __construct(
        private readonly StockEngine $stockEngine,
        private readonly ?PhoneBlockService $phoneBlockService = null,
        private readonly ?OrderReturnSyncService $orderReturnSyncService = null
    ) {}

    /**
     * @var array<int, array<int, int>>
     */
    private array $allowedTransitions = [
        1 => [2, 6],
        2 => [3, 6],
        3 => [4, 6],
        4 => [5, 6, 7],
        5 => [7, 8],
        6 => [],
        7 => [8],
        8 => [],
    ];

    public function canTransition(int $fromStatus, int $toStatus): bool
    {
        if ($fromStatus === $toStatus) {
            return true;
        }

        return in_array($toStatus, $this->allowedTransitions[$fromStatus] ?? [], true);
    }

    /**
     * @param  array<string,mixed>  $context
     */
    public function transition(Order $order, int $toStatus, array $context = []): Order
    {
        $fromStatus = (int) $order->order_status;
        $enforced = (bool) config('features.orders.state_machine_enforced', true);
        $isAdmin = isset($context['actor_type']) && $context['actor_type'] === 'admin';

        if ($enforced && !$isAdmin && ! $this->canTransition($fromStatus, $toStatus)) {
            throw ValidationException::withMessages([
                'order_status' => "Invalid state transition {$fromStatus} -> {$toStatus}.",
            ]);
        }

        if ($fromStatus !== $toStatus) {
            $this->applyStockTransition($order, $fromStatus, $toStatus);
            $order->order_status = $toStatus;
            $order->save();

            // Fire the status updated event to trigger purchase tracking and other actions
            event(new \App\Events\OrderStatusUpdated($order, $fromStatus, $toStatus));
        }

        OrderStateTransition::create([
            'id' => OrderStateTransition::max('id') + 1,
            'order_id' => (int) $order->id,
            'branch_id' => (int) ($order->branch_id ?? 0) ?: null,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_type' => $context['actor_type'] ?? null,
            'actor_id' => $context['actor_id'] ?? null,
            'source' => $context['source'] ?? null,
            'reason' => $context['reason'] ?? null,
            'meta' => $context['meta'] ?? null,
        ]);

        if ($fromStatus !== $toStatus) {
            $this->handlePhoneBlockTransition($order, $fromStatus, $toStatus);
            $this->handleReturnedTransition($order, $fromStatus, $toStatus, $context);
        }

        return $order;
    }

    private function applyStockTransition(Order $order, int $fromStatus, int $toStatus): void
    {
        if ($toStatus === 5 && $fromStatus !== 5) {
            $this->stockEngine->deductForOrder($order);

            return;
        }

        if ($fromStatus !== 5 && $this->isCancellationStatus($toStatus)) {
            $this->stockEngine->releaseForOrder($order);
        }
    }

    private function isCancellationStatus(int $statusId): bool
    {
        if ($statusId === 6) {
            return true;
        }

        $status = OrderStatus::query()
            ->select(['slug', 'name'])
            ->find($statusId);

        if (! $status) {
            return false;
        }

        return str_contains(strtolower((string) $status->slug), 'cancel')
            || str_contains(strtolower((string) $status->name), 'cancel');
    }

    private function handlePhoneBlockTransition(Order $order, int $fromStatus, int $toStatus): void
    {
        if (! $this->phoneBlockService) {
            return;
        }

        if ($this->isCancellationStatus($fromStatus) || ! $this->isCancellationStatus($toStatus)) {
            return;
        }

        try {
            $this->phoneBlockService->autoBlockAfterCancellation($order);
        } catch (Throwable $exception) {
            Log::warning('Failed to auto-block phone after order cancellation transition', [
                'order_id' => (int) $order->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function handleReturnedTransition(Order $order, int $fromStatus, int $toStatus, array $context): void
    {
        if (! $this->orderReturnSyncService) {
            return;
        }

        if ($this->isReturnedStatus($fromStatus) || ! $this->isReturnedStatus($toStatus)) {
            return;
        }

        try {
            $this->orderReturnSyncService->syncReturnedOrder($order, $context);
        } catch (Throwable $exception) {
            Log::warning('Failed to auto-sync return list after order moved to returned status', [
                'order_id' => (int) $order->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function isReturnedStatus(int $statusId): bool
    {
        if ($statusId === 7) {
            return true;
        }

        $status = OrderStatus::query()
            ->select(['slug', 'name'])
            ->find($statusId);

        if (! $status) {
            return false;
        }

        return str_contains(strtolower((string) $status->slug), 'return')
            || str_contains(strtolower((string) $status->name), 'return');
    }
}
