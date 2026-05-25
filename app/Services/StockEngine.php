<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Inventory\Exceptions\StockOperationException;
use App\Models\Order;
use App\Models\OrderDetails;
use Illuminate\Support\Facades\DB;

class StockEngine
{
    public function __construct(
        private readonly WarehouseStockService $warehouseStockService,
        private readonly VariantStockService $variantStockService
    ) {}

    public function reserve(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $referenceId,
        string $referenceType = 'order',
        ?int $variantId = null,
        string $notes = ''
    ): void {
        $this->assertPositiveQuantity('reserve', $quantity);

        DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $referenceId,
            $referenceType,
            $variantId,
            $notes
        ): void {
            if ($variantId !== null) {
                $this->assertVariantOrderReference('reserve', $referenceId, $referenceType);
                $ok = $this->variantStockService->reserveStock($warehouseId, $variantId, $quantity, (int) $referenceId);
                if (! $ok) {
                    throw StockOperationException::operationFailed('reserve', 'variant reservation returned false');
                }

                return;
            }

            $this->warehouseStockService->reserveStock(
                $warehouseId,
                $productId,
                $quantity,
                $referenceId,
                $referenceType,
                $notes !== '' ? $notes : 'Reserved through stock engine'
            );
        });
    }

    public function release(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $referenceId,
        string $referenceType = 'order',
        ?int $variantId = null,
        string $notes = ''
    ): void {
        $this->assertPositiveQuantity('release', $quantity);

        DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $referenceId,
            $referenceType,
            $variantId,
            $notes
        ): void {
            if ($variantId !== null) {
                $this->assertVariantOrderReference('release', $referenceId, $referenceType);
                $ok = $this->variantStockService->releaseReservedStock((int) $referenceId);
                if (! $ok) {
                    throw StockOperationException::operationFailed('release', 'variant release returned false');
                }

                return;
            }

            $this->warehouseStockService->releaseReservedStock(
                $warehouseId,
                $productId,
                $quantity,
                $referenceId,
                $referenceType,
                $notes !== '' ? $notes : 'Released through stock engine'
            );
        });
    }

    public function deduct(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $referenceId,
        string $referenceType = 'order',
        ?int $variantId = null,
        string $notes = ''
    ): void {
        $this->assertPositiveQuantity('deduct', $quantity);

        DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $referenceId,
            $referenceType,
            $variantId,
            $notes
        ): void {
            if ($variantId !== null) {
                $this->assertVariantOrderReference('deduct', $referenceId, $referenceType);
                $ok = $this->variantStockService->confirmSale((int) $referenceId);
                if (! $ok) {
                    throw StockOperationException::operationFailed('deduct', 'variant sale confirmation returned false');
                }

                return;
            }

            $this->warehouseStockService->commitReservedStock(
                $warehouseId,
                $productId,
                $quantity,
                $referenceId,
                $referenceType,
                $notes !== '' ? $notes : 'Deducted through stock engine'
            );
        });
    }

    public function adjust(
        int $warehouseId,
        int $productId,
        float $adjustment,
        string $reason,
        ?int $variantId = null
    ): void {
        if ($adjustment === 0.0) {
            throw StockOperationException::invalidQuantity('adjust', $adjustment);
        }

        DB::transaction(function () use (
            $warehouseId,
            $productId,
            $adjustment,
            $reason,
            $variantId
        ): void {
            if ($variantId !== null) {
                $ok = $this->variantStockService->adjustStock($warehouseId, $variantId, $adjustment, $reason);
                if (! $ok) {
                    throw StockOperationException::operationFailed('adjust', 'variant stock adjustment returned false');
                }

                return;
            }

            $this->warehouseStockService->adjustStock(
                $warehouseId,
                $productId,
                $adjustment,
                $reason,
                0
            );
        });
    }

    public function reserveForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = $this->lockOrderWithDetails((int) $order->id);
            foreach ($lockedOrder->orderdetails as $detail) {
                $warehouseId = $this->resolveWarehouseId($lockedOrder, $detail);
                $this->reserve(
                    $warehouseId,
                    (int) $detail->product_id,
                    (float) $detail->qty,
                    (int) $lockedOrder->id,
                    'order',
                    $detail->product_variant_id ? (int) $detail->product_variant_id : null,
                    "Reserved stock for order #{$lockedOrder->id}"
                );
            }
        });
    }

    public function releaseForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = $this->lockOrderWithDetails((int) $order->id);

            if ($lockedOrder->orderdetails->whereNotNull('product_variant_id')->isNotEmpty()) {
                $ok = $this->variantStockService->releaseReservedStock((int) $lockedOrder->id);
                if (! $ok) {
                    throw StockOperationException::operationFailed('release', 'variant release for order returned false');
                }
            }

            foreach ($lockedOrder->orderdetails->whereNull('product_variant_id') as $detail) {
                $warehouseId = $this->resolveWarehouseId($lockedOrder, $detail);
                $this->release(
                    $warehouseId,
                    (int) $detail->product_id,
                    (float) $detail->qty,
                    (int) $lockedOrder->id,
                    'order',
                    null,
                    "Released stock for cancelled order #{$lockedOrder->id}"
                );
            }
        });
    }

    public function deductForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = $this->lockOrderWithDetails((int) $order->id);

            if ($lockedOrder->orderdetails->whereNotNull('product_variant_id')->isNotEmpty()) {
                $ok = $this->variantStockService->confirmSale((int) $lockedOrder->id);
                if (! $ok) {
                    throw StockOperationException::operationFailed('deduct', 'variant deduct for order returned false');
                }
            }

            foreach ($lockedOrder->orderdetails->whereNull('product_variant_id') as $detail) {
                $warehouseId = $this->resolveWarehouseId($lockedOrder, $detail);
                $this->deduct(
                    $warehouseId,
                    (int) $detail->product_id,
                    (float) $detail->qty,
                    (int) $lockedOrder->id,
                    'order',
                    null,
                    "Deducted stock for shipped order #{$lockedOrder->id}"
                );
            }
        });
    }

    private function lockOrderWithDetails(int $orderId): Order
    {
        return Order::query()
            ->with('orderdetails')
            ->lockForUpdate()
            ->findOrFail($orderId);
    }

    private function resolveWarehouseId(Order $order, OrderDetails $detail): int
    {
        $warehouseId = (int) ($detail->warehouse_id ?? $order->warehouse_id ?? 0);
        if ($warehouseId > 0) {
            return $warehouseId;
        }

        throw StockOperationException::missingWarehouse((int) $order->id, (int) $detail->product_id);
    }

    private function assertPositiveQuantity(string $operation, float $quantity): void
    {
        if (! is_finite($quantity) || $quantity <= 0.0) {
            throw StockOperationException::invalidQuantity($operation, $quantity);
        }
    }

    private function assertVariantOrderReference(string $operation, ?int $referenceId, string $referenceType): void
    {
        if ($referenceType !== 'order' || $referenceId === null || $referenceId <= 0) {
            throw StockOperationException::unsupportedVariantOperation($operation, $referenceId, $referenceType);
        }
    }
}
