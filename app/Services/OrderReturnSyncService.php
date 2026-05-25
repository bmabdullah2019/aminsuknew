<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\ReturnReason;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OrderReturnSyncService
{
    /**
     * Create a return-order entry when an order reaches returned status.
     *
     * Idempotent:
     * - If any return order already exists for the order, no new one is created.
     */
    public function syncReturnedOrder(Order $order, array $context = []): ?ReturnOrder
    {
        if (! $this->isAutoSyncEnabled() || ! $this->hasRequiredTables()) {
            return null;
        }

        $orderId = (int) $order->id;
        if ($orderId <= 0) {
            return null;
        }

        $creatorId = $this->resolveCreatorId($order, $context);
        if (! $creatorId) {
            Log::warning('Auto return sync skipped: no valid creator user id resolved', [
                'order_id' => $orderId,
            ]);

            return null;
        }

        $reason = $this->resolveDefaultReason();
        if (! $reason) {
            Log::warning('Auto return sync skipped: no active return reason available', [
                'order_id' => $orderId,
            ]);

            return null;
        }

        try {
            return DB::transaction(function () use ($order, $context, $creatorId, $reason): ?ReturnOrder {
                $lockedOrder = Order::query()
                    ->whereKey((int) $order->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedOrder) {
                    return null;
                }

                $existing = ReturnOrder::query()
                    ->where('order_id', (int) $lockedOrder->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $details = $this->returnableOrderDetails($lockedOrder);

                $returnType = $details->isEmpty()
                    ? 'partial'
                    : ($details->every(fn (OrderDetails $detail) => $this->availableQty($detail) >= (float) $detail->qty) ? 'full' : 'partial');

                $statusLabel = $this->resolveOrderStatusLabel((int) $lockedOrder->order_status);
                $orderSource = (string) ($context['source'] ?? 'order_state_machine');
                $notes = 'Auto-created from order status transition to returned ('.$statusLabel.') via '.$orderSource.'.';

                $returnOrder = ReturnOrder::query()->create([
                    'order_id' => (int) $lockedOrder->id,
                    'customer_id' => (int) $lockedOrder->customer_id,
                    'return_status' => 'pending',
                    'return_source' => 'warehouse',
                    'return_type' => $returnType,
                    'return_reason_id' => (int) $reason->id,
                    'refund_method' => ((bool) $reason->refund_eligible) ? 'none' : null,
                    'restock_flag' => (bool) $reason->auto_restock,
                    'damage_flag' => false,
                    'notes' => $notes,
                    'created_by' => (int) $creatorId,
                ]);

                $totalReturnValue = 0.0;
                $totalRefund = 0.0;

                foreach ($details as $detail) {
                    $quantity = $this->availableQty($detail);
                    if ($quantity <= 0) {
                        continue;
                    }

                    $warehouseId = $this->resolveWarehouseId($lockedOrder, $detail);
                    if (! $warehouseId) {
                        continue;
                    }

                    $unitPrice = (float) ($detail->sale_price ?? 0);
                    $refundAmount = ((bool) $reason->refund_eligible) ? round($quantity * $unitPrice, 2) : 0.0;

                    ReturnItem::query()->create([
                        'return_order_id' => (int) $returnOrder->id,
                        'order_detail_id' => (int) $detail->id,
                        'product_id' => (int) $detail->product_id,
                        'warehouse_id' => (int) $warehouseId,
                        'return_quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'unit_cost' => (float) ($detail->purchase_price ?? 0),
                        'return_condition' => 'opened',
                        'restock_quantity' => ((bool) $reason->auto_restock) ? $quantity : 0,
                        'damage_quantity' => 0,
                        'refund_amount' => $refundAmount,
                        'notes' => 'Auto-created from returned order status.',
                    ]);

                    $totalReturnValue += round($quantity * $unitPrice, 2);
                    $totalRefund += $refundAmount;
                }

                $returnOrder->update([
                    'total_return_value' => round($totalReturnValue, 2),
                    'refund_amount' => round($totalRefund, 2),
                ]);

                return $returnOrder->fresh(['returnItems']);
            });
        } catch (Throwable $exception) {
            Log::warning('Auto return sync failed', [
                'order_id' => $orderId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function isAutoSyncEnabled(): bool
    {
        return (bool) config('features.orders.returned_status_auto_return_enabled', true);
    }

    private function hasRequiredTables(): bool
    {
        return Schema::hasTable('orders')
            && Schema::hasTable('order_details')
            && Schema::hasTable('return_orders')
            && Schema::hasTable('return_items')
            && Schema::hasTable('return_reasons')
            && Schema::hasTable('users');
    }

    private function resolveCreatorId(Order $order, array $context): ?int
    {
        $contextActorId = (int) ($context['actor_id'] ?? 0);
        if ($contextActorId > 0 && User::query()->whereKey($contextActorId)->exists()) {
            return $contextActorId;
        }

        $authId = (int) auth()->id();
        if ($authId > 0 && User::query()->whereKey($authId)->exists()) {
            return $authId;
        }

        $orderUserId = (int) ($order->user_id ?? 0);
        if ($orderUserId > 0 && User::query()->whereKey($orderUserId)->exists()) {
            return $orderUserId;
        }

        $firstUserId = (int) (User::query()->orderBy('id')->value('id') ?? 0);

        return $firstUserId > 0 ? $firstUserId : null;
    }

    private function resolveDefaultReason(): ?ReturnReason
    {
        $preferred = ReturnReason::query()
            ->where('active', true)
            ->where(function ($query): void {
                $query->where('reason_code', 'OTHER-002')
                    ->orWhere('reason_name', 'Store Return');
            })
            ->orderBy('sort_order')
            ->first();

        if ($preferred) {
            return $preferred;
        }

        return ReturnReason::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    private function returnableOrderDetails(Order $order)
    {
        return OrderDetails::query()
            ->where('order_id', (int) $order->id)
            ->where(function ($query): void {
                if (Schema::hasColumn('order_details', 'return_eligible')) {
                    $query->whereNull('return_eligible')
                        ->orWhere('return_eligible', true);

                    return;
                }

                $query->whereRaw('1=1');
            })
            ->where(function ($query): void {
                if (Schema::hasColumn('order_details', 'return_deadline')) {
                    $query->whereNull('return_deadline')
                        ->orWhereDate('return_deadline', '>=', now()->toDateString());

                    return;
                }

                $query->whereRaw('1=1');
            })
            ->orderBy('id')
            ->get();
    }

    private function availableQty(OrderDetails $detail): float
    {
        $qty = (float) ($detail->qty ?? 0);
        $returnedQty = Schema::hasColumn('order_details', 'returned_quantity')
            ? (float) ($detail->returned_quantity ?? 0)
            : 0.0;

        return max(0, $qty - $returnedQty);
    }

    private function resolveWarehouseId(Order $order, OrderDetails $detail): ?int
    {
        $detailWarehouseId = (int) ($detail->warehouse_id ?? 0);
        if ($detailWarehouseId > 0) {
            return $detailWarehouseId;
        }

        $orderWarehouseId = (int) ($order->warehouse_id ?? 0);

        return $orderWarehouseId > 0 ? $orderWarehouseId : null;
    }

    private function resolveOrderStatusLabel(int $statusId): string
    {
        if ($statusId <= 0 || ! Schema::hasTable('order_statuses')) {
            return 'returned';
        }

        $status = OrderStatus::query()
            ->select('name')
            ->find($statusId);

        return (string) ($status?->name ?: 'returned');
    }
}
