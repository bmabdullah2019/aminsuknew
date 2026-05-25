<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;

class StockValidationService
{
    /**
     * Validate stock levels for multiple products
     */
    public function validateBulkStock(array $items, ?int $warehouseId = null): array
    {
        $warehouse = $warehouseId
            ? Warehouse::active()->whereKey($warehouseId)->first()
            : (Warehouse::main()->active()->first() ?? Warehouse::active()->first());

        if (! $warehouse) {
            return [
                'valid' => false,
                'message' => 'No active warehouse found',
                'errors' => [],
            ];
        }

        $errors = [];
        $warnings = [];

        foreach ($items as $item) {
            $validation = $this->validateProductStock(
                $item['product_id'],
                $item['quantity'],
                $warehouse->id
            );

            if (! $validation['valid']) {
                $errors[] = $validation;
            } elseif ($validation['warning']) {
                $warnings[] = $validation;
            }
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors)
                ? 'All stock validations passed'
                : 'Some products have insufficient stock',
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate stock for a single product
     */
    public function validateProductStock(int $productId, float $quantity, int $warehouseId): array
    {
        $product = Product::find($productId);

        if (! $product) {
            return [
                'valid' => false,
                'product_id' => $productId,
                'message' => 'Product not found',
                'warning' => false,
            ];
        }

        $warehouseStock = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();

        if (! $warehouseStock) {
            return [
                'valid' => false,
                'product_id' => $productId,
                'product_name' => $product->name,
                'message' => 'Product not available in warehouse stock',
                'warning' => false,
            ];
        }

        // Check available quantity
        if ($warehouseStock->available_quantity < $quantity) {
            return [
                'valid' => false,
                'product_id' => $productId,
                'product_name' => $product->name,
                'message' => "Insufficient stock. Available: {$warehouseStock->available_quantity}, Required: {$quantity}",
                'available_quantity' => $warehouseStock->available_quantity,
                'required_quantity' => $quantity,
                'warning' => false,
            ];
        }

        // Check if this will bring stock below reorder point
        $stockAfterSale = $warehouseStock->available_quantity - $quantity;
        $warning = false;
        $warningMessage = '';

        if ($stockAfterSale <= $warehouseStock->reorder_point && $stockAfterSale > 0) {
            $warning = true;
            $warningMessage = "This sale will bring stock below reorder point ({$warehouseStock->reorder_point})";
        }

        return [
            'valid' => true,
            'product_id' => $productId,
            'product_name' => $product->name,
            'message' => 'Stock validation passed',
            'available_quantity' => $warehouseStock->available_quantity,
            'stock_after_sale' => $stockAfterSale,
            'warning' => $warning,
            'warning_message' => $warningMessage,
        ];
    }

    /**
     * Check if product can be ordered based on various constraints
     */
    public function canOrderProduct(int $productId, float $quantity, array $constraints = []): array
    {
        $product = Product::find($productId);

        if (! $product) {
            return [
                'can_order' => false,
                'message' => 'Product not found',
            ];
        }

        // Check if product is active
        if (! $product->status) {
            return [
                'can_order' => false,
                'message' => 'Product is not active',
            ];
        }

        // Check minimum order quantity
        if (isset($constraints['min_order_qty']) && $quantity < $constraints['min_order_qty']) {
            return [
                'can_order' => false,
                'message' => "Minimum order quantity is {$constraints['min_order_qty']}",
            ];
        }

        // Check maximum order quantity
        if (isset($constraints['max_order_qty']) && $quantity > $constraints['max_order_qty']) {
            return [
                'can_order' => false,
                'message' => "Maximum order quantity is {$constraints['max_order_qty']}",
            ];
        }

        // Validate stock
        $warehouse = Warehouse::main()->active()->first() ?? Warehouse::active()->first();
        if (! $warehouse) {
            return [
                'can_order' => false,
                'message' => 'No active warehouse found',
            ];
        }

        $stockValidation = $this->validateProductStock($productId, $quantity, $warehouse->id);

        return [
            'can_order' => $stockValidation['valid'],
            'message' => $stockValidation['message'],
            'stock_info' => $stockValidation,
        ];
    }

    /**
     * Get stock forecast for a product
     */
    public function getStockForecast(int $productId, int $days = 30): array
    {
        $warehouse = Warehouse::main()->active()->first() ?? Warehouse::active()->first();

        if (! $warehouse) {
            return [
                'error' => 'No active warehouse found',
            ];
        }

        $currentStock = WarehouseStock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $productId)
            ->first();

        if (! $currentStock) {
            return [
                'error' => 'Product not found in warehouse',
            ];
        }

        // Calculate average daily sales over the last 30 days
        $avgDailySales = OrderDetails::where('product_id', $productId)
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30))
                    ->where('order_status', '!=', 'cancelled');
            })
            ->sum('qty') / 30;

        // Calculate days until stock out
        $daysUntilStockOut = $avgDailySales > 0
            ? ceil($currentStock->available_quantity / $avgDailySales)
            : null;

        // Forecast stock levels
        $forecast = [];
        for ($i = 1; $i <= $days; $i++) {
            $forecastStock = $currentStock->available_quantity - ($avgDailySales * $i);
            $forecast[] = [
                'day' => $i,
                'date' => now()->addDays($i)->toDateString(),
                'forecast_stock' => max(0, $forecastStock),
                'status' => $forecastStock <= 0 ? 'stockout' :
                           ($forecastStock <= $currentStock->reorder_point ? 'low' : 'normal'),
            ];
        }

        return [
            'current_stock' => $currentStock->available_quantity,
            'reorder_point' => $currentStock->reorder_point,
            'avg_daily_sales' => round($avgDailySales, 2),
            'days_until_stockout' => $daysUntilStockOut,
            'forecast' => $forecast,
        ];
    }

    /**
     * Validate return eligibility
     */
    public function validateReturnEligibility(int $orderId, array $returnItems): array
    {
        $order = Order::with('orderdetails')->find($orderId);

        if (! $order) {
            return [
                'eligible' => false,
                'message' => 'Order not found',
            ];
        }

        // Check if order is delivered
        if ($order->order_status !== '5') {
            return [
                'eligible' => false,
                'message' => 'Order must be delivered to be eligible for return',
            ];
        }

        // Check return window (30 days)
        $deliveredAt = $order->updated_at;
        if (now()->diffInDays($deliveredAt) > 30) {
            return [
                'eligible' => false,
                'message' => 'Return window (30 days) has expired',
            ];
        }

        $errors = [];

        foreach ($returnItems as $returnItem) {
            $orderDetail = $order->orderdetails->where('id', $returnItem['order_detail_id'])->first();

            if (! $orderDetail) {
                $errors[] = "Order detail #{$returnItem['order_detail_id']} not found";

                continue;
            }

            $alreadyReturned = $orderDetail->returned_quantity ?? 0;
            $maxReturnable = $orderDetail->qty - $alreadyReturned;

            if ($returnItem['quantity'] > $maxReturnable) {
                $errors[] = "Cannot return {$returnItem['quantity']} of {$orderDetail->product_name}. Maximum returnable: {$maxReturnable}";
            }
        }

        return [
            'eligible' => empty($errors),
            'message' => empty($errors) ? 'Return eligibility validated' : 'Some items cannot be returned',
            'errors' => $errors,
        ];
    }
}
