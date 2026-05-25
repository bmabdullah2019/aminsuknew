<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantStockService
{
    /**
     * Add stock for a variant (Goods Receipt / manual add).
     */
    public function addStock(
        int $warehouseId,
        int $variantId,
        float $quantity,
        float $unitCost = 0,
        ?int $referenceId = null,
        string $notes = ''
    ): bool {
        return DB::transaction(function () use ($warehouseId, $variantId, $quantity, $unitCost, $referenceId, $notes) {
            if ($quantity <= 0) {
                throw new \InvalidArgumentException('Quantity must be greater than zero.');
            }

            $branchId = $this->resolveBranchIdForWarehouse($warehouseId);

            $inventory = Inventory::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $variantId,
                ],
                [
                    'branch_id' => $branchId,
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                    'reorder_level' => 5,
                ]
            );

            if (empty($inventory->branch_id)) {
                $inventory->branch_id = $branchId;
                $inventory->save();
            }

            $inventory->increaseStock($quantity, $unitCost > 0 ? $unitCost : null);
            $productId = $this->getVariantProductId($variantId);

            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'grn',
                'reference_type' => 'grn',
                'reference_id' => $referenceId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost > 0 ? $unitCost : ((float) ($inventory->productVariant?->cost_price ?? 0)),
                'balance_after' => $inventory->sellable_stock,
                'notes' => $notes !== '' ? $notes : "Goods receipt for variant #{$variantId}",
                'created_by' => $this->resolveCreatorId(),
            ]);

            $this->clearStockCache($warehouseId, $variantId);
            $this->syncWarehouseStockAggregate($warehouseId, $productId);
            $this->checkAndTriggerLowStockAlert($inventory);

            return true;
        });
    }

    /**
     * Get stock balance for a specific variant in a warehouse
     */
    public function getStockBalance(int $warehouseId, int $variantId): ?Inventory
    {
        return Inventory::where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $variantId)
            ->first();
    }

    /**
     * Get stock summary with caching
     */
    public function getStockSummary(int $warehouseId, int $variantId): array
    {
        $cacheKey = "variant_stock_summary_{$warehouseId}_{$variantId}";

        return Cache::remember($cacheKey, 300, function () use ($warehouseId, $variantId) {
            $inventory = $this->getStockBalance($warehouseId, $variantId);

            if (! $inventory) {
                return [
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                    'sellable_stock' => 0,
                    'total_value' => 0,
                    'status' => 'not_found',
                ];
            }

            return [
                'quantity_available' => $inventory->quantity_available,
                'quantity_reserved' => $inventory->quantity_reserved,
                'sellable_stock' => $inventory->sellable_stock,
                'total_value' => $inventory->total_value,
                'status' => $inventory->stock_status,
                'reorder_level' => $inventory->reorder_level,
                'last_updated' => $inventory->last_updated_at,
            ];
        });
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStock(int $warehouseId, int $variantId, float $quantity, int $orderId): bool
    {
        return DB::transaction(function () use ($warehouseId, $variantId, $quantity, $orderId) {
            $branchId = $this->resolveBranchIdForWarehouse($warehouseId);

            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $warehouseStock = $this->getWarehouseStockForVariant($warehouseId, $variantId);
                if ($warehouseStock <= 0) {
                    throw new \Exception("Inventory not found for variant {$variantId} in warehouse {$warehouseId}");
                }

                $inventory = Inventory::create([
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $variantId,
                    'branch_id' => $branchId,
                    'quantity_available' => $warehouseStock,
                    'quantity_reserved' => 0,
                    'reorder_level' => 5,
                ]);
            }

            // checkout validation uses WarehouseStock when Inventory is low; reservation must not disagree.
            $this->reconcileInventoryWithWarehouseVariantStock($inventory, $warehouseId, $variantId);
            $inventory->refresh();

            if ($inventory->sellable_stock < $quantity) {
                throw new \Exception("Insufficient stock available. Available: {$inventory->sellable_stock}, Requested: {$quantity}");
            }

            $inventory->reserveStock($quantity);
            $productId = $this->getVariantProductId($variantId);

            // Record movement
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'reservation',
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'quantity' => -$quantity,
                'balance_after' => $inventory->sellable_stock,
                'notes' => "Stock reserved for order #{$orderId}",
                'created_by' => $this->resolveCreatorId(),
            ]);

            $this->clearStockCache($warehouseId, $variantId);
            $this->syncWarehouseStockAggregate($warehouseId, $productId);

            // Check for alerts
            $this->checkAndTriggerLowStockAlert($inventory);

            return true;
        });
    }

    /**
     * Release reserved stock
     */
    public function releaseReservedStock(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $reservations = StockMovement::where('reference_type', 'order')
                ->where('reference_id', $orderId)
                ->where('type', 'reservation')
                ->whereNotNull('product_variant_id')
                ->get();

            foreach ($reservations as $reservation) {
                $branchId = $this->resolveBranchIdForWarehouse((int) $reservation->warehouse_id);

                $inventory = Inventory::where('warehouse_id', $reservation->warehouse_id)
                    ->where('product_variant_id', $reservation->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    $quantity = abs($reservation->quantity);
                    $inventory->releaseReservedStock($quantity);
                    $productId = $this->getVariantProductId((int) $reservation->product_variant_id);

                    // Record release movement
                    StockMovement::create([
                        'branch_id' => $branchId,
                        'warehouse_id' => $reservation->warehouse_id,
                        'product_id' => $productId,
                        'product_variant_id' => $reservation->product_variant_id,
                        'type' => 'release',
                        'reference_type' => 'order',
                        'reference_id' => $orderId,
                        'quantity' => $quantity,
                        'balance_after' => $inventory->sellable_stock,
                        'notes' => "Stock released from cancelled order #{$orderId}",
                        'created_by' => $this->resolveCreatorId(),
                    ]);

                    $this->clearStockCache($reservation->warehouse_id, $reservation->product_variant_id);
                    $this->syncWarehouseStockAggregate((int) $reservation->warehouse_id, $productId);

                    // Remove reservation
                    $reservation->delete();
                }
            }

            return true;
        });
    }

    /**
     * Confirm sale and deduct from inventory
     */
    public function confirmSale(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $reservations = StockMovement::where('reference_type', 'order')
                ->where('reference_id', $orderId)
                ->where('type', 'reservation')
                ->whereNotNull('product_variant_id')
                ->get();

            foreach ($reservations as $reservation) {
                $branchId = $this->resolveBranchIdForWarehouse((int) $reservation->warehouse_id);

                $inventory = Inventory::where('warehouse_id', $reservation->warehouse_id)
                    ->where('product_variant_id', $reservation->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    $quantity = abs($reservation->quantity);

                    // Convert reserved stock into an actual deduction in one flow.
                    if ($inventory->quantity_reserved < $quantity) {
                        throw new \RuntimeException("Reserved quantity mismatch for variant {$reservation->product_variant_id} in warehouse {$reservation->warehouse_id}");
                    }
                    $inventory->releaseReservedStock($quantity);
                    $inventory->decreaseStock($quantity);
                    $productId = $this->getVariantProductId((int) $reservation->product_variant_id);

                    // Record sale movement
                    StockMovement::create([
                        'branch_id' => $branchId,
                        'warehouse_id' => $reservation->warehouse_id,
                        'product_id' => $productId,
                        'product_variant_id' => $reservation->product_variant_id,
                        'type' => 'sale',
                        'reference_type' => 'order',
                        'reference_id' => $orderId,
                        'quantity' => -$quantity,
                        'balance_after' => $inventory->sellable_stock,
                        'notes' => "Stock sold for order #{$orderId}",
                        'created_by' => $this->resolveCreatorId(),
                    ]);

                    $this->clearStockCache($reservation->warehouse_id, $reservation->product_variant_id);
                    $this->syncWarehouseStockAggregate((int) $reservation->warehouse_id, $productId);

                    // Remove reservation
                    $reservation->delete();
                }
            }

            return true;
        });
    }

    /**
     * Receive stock from purchase order
     */
    public function receiveStockFromPO(int $purchaseOrderId, array $items): bool
    {
        return DB::transaction(function () use ($purchaseOrderId, $items) {
            $purchaseOrder = PurchaseOrder::with('supplier')
                ->whereKey($purchaseOrderId)
                ->lockForUpdate()
                ->firstOrFail();
            $creatorId = $this->resolveCreatorId() ?? (int) $purchaseOrder->created_by;
            $branchId = (int) ($purchaseOrder->branch_id ?? $this->resolveBranchIdForWarehouse((int) $purchaseOrder->warehouse_id));

            foreach ($items as $itemData) {
                $purchaseItemId = $itemData['purchase_item_id'] ?? $itemData['id'] ?? null;
                if (! $purchaseItemId) {
                    throw new \Exception('Purchase item ID is required while receiving stock from PO.');
                }

                $purchaseItem = \App\Models\PurchaseItem::findOrFail($purchaseItemId);
                if ((int) $purchaseItem->purchase_order_id !== (int) $purchaseOrder->id) {
                    throw new \RuntimeException("Purchase item #{$purchaseItem->id} does not belong to purchase order #{$purchaseOrder->id}.");
                }
                $receivedQuantity = (float) ($itemData['quantity_received'] ?? $itemData['quantity'] ?? 0);
                if ($receivedQuantity <= 0) {
                    throw new \Exception('Received quantity must be greater than zero');
                }
                $unitCost = $itemData['unit_cost'] ?? $purchaseItem->unit_cost;
                $variant = $purchaseItem->productVariant;
                if (! $variant) {
                    throw new \Exception("Product variant missing for purchase item #{$purchaseItem->id}");
                }
                $productId = (int) $variant->product_id;

                // Update purchase item
                $purchaseItem->receiveQuantity($receivedQuantity, $unitCost);

                // Update inventory
                $inventory = Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $purchaseItem->product_variant_id,
                        'warehouse_id' => $purchaseOrder->warehouse_id,
                    ],
                    [
                        'branch_id' => $branchId,
                        'quantity_available' => 0,
                        'quantity_reserved' => 0,
                        'reorder_level' => 5,
                    ]
                );

                if (empty($inventory->branch_id)) {
                    $inventory->branch_id = $branchId;
                    $inventory->save();
                }

                $inventory->increaseStock($receivedQuantity, $unitCost);

                // Record stock movement
                StockMovement::create([
                    'branch_id' => $branchId,
                    'warehouse_id' => $purchaseOrder->warehouse_id,
                    'product_id' => $productId,
                    'product_variant_id' => $purchaseItem->product_variant_id,
                    'type' => 'grn',
                    'reference_type' => 'grn',
                    'reference_id' => $purchaseOrderId,
                    'quantity' => $receivedQuantity,
                    'unit_cost' => $unitCost,
                    'balance_after' => $inventory->sellable_stock,
                    'notes' => "GRN for PO #{$purchaseOrder->po_number}",
                    'created_by' => $creatorId,
                ]);

                $this->clearStockCache($purchaseOrder->warehouse_id, $purchaseItem->product_variant_id);
                $this->syncWarehouseStockAggregate((int) $purchaseOrder->warehouse_id, $productId);
            }

            // Update PO status
            $purchaseOrder->updateStatusBasedOnReceipt();
            $this->postSupplierLedgerForReceivedStock($purchaseOrder, $creatorId);

            return true;
        });
    }

    /**
     * Process return and restock
     */
    public function processReturn(int $warehouseId, int $variantId, float $quantity, int $returnId, string $condition = 'good'): bool
    {
        return DB::transaction(function () use ($warehouseId, $variantId, $quantity, $returnId, $condition) {
            $branchId = $this->resolveBranchIdForWarehouse($warehouseId);

            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \Exception("Inventory not found for variant {$variantId} in warehouse {$warehouseId}");
            }

            // Both good and damaged returns increase stock here; downstream flow can
            // decide if damaged stock is quarantined.
            if (! in_array($condition, ['good', 'new', 'damaged', 'used'], true)) {
                throw new \Exception("Invalid return condition: {$condition}");
            }
            $inventory->increaseStock($quantity);
            $productId = $this->getVariantProductId($variantId);

            // Record movement
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'adjustment_in',
                'reference_type' => 'stock_adjustment',
                'reference_id' => $returnId,
                'quantity' => $quantity,
                'balance_after' => $inventory->sellable_stock,
                'notes' => "Return ({$condition}) #{$returnId}",
                'created_by' => $this->resolveCreatorId(),
            ]);

            $this->clearStockCache($warehouseId, $variantId);
            $this->syncWarehouseStockAggregate($warehouseId, $productId);

            return true;
        });
    }

    /**
     * Process supplier purchase return and deduct stock.
     */
    public function processSupplierReturn(
        int $warehouseId,
        int $variantId,
        float $quantity,
        int $supplierReturnId,
        string $notes = '',
        ?int $createdBy = null
    ): bool {
        return DB::transaction(function () use ($warehouseId, $variantId, $quantity, $supplierReturnId, $notes, $createdBy) {
            if ($quantity <= 0) {
                throw new \InvalidArgumentException('Supplier return quantity must be greater than zero.');
            }

            $branchId = $this->resolveBranchIdForWarehouse($warehouseId);

            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \RuntimeException("Inventory not found for variant {$variantId} in warehouse {$warehouseId}");
            }

            if ($inventory->sellable_stock < $quantity) {
                throw new \RuntimeException("Insufficient stock for supplier return. Available: {$inventory->sellable_stock}, Requested: {$quantity}");
            }

            $inventory->decreaseStock($quantity);
            $productId = $this->getVariantProductId($variantId);

            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'adjustment_out',
                'reference_type' => 'stock_adjustment',
                'reference_id' => $supplierReturnId,
                'quantity' => -$quantity,
                'unit_cost' => (float) ($inventory->productVariant->cost_price ?? 0),
                'balance_after' => $inventory->sellable_stock,
                'notes' => $notes !== '' ? $notes : "Supplier return #{$supplierReturnId}",
                'created_by' => $createdBy ?: $this->resolveCreatorId(),
            ]);

            $this->clearStockCache($warehouseId, $variantId);
            $this->syncWarehouseStockAggregate($warehouseId, $productId);
            $this->checkAndTriggerLowStockAlert($inventory);

            return true;
        });
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock(int $fromWarehouseId, int $toWarehouseId, int $variantId, float $quantity, string $notes = ''): bool
    {
        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $variantId, $quantity, $notes) {
            $fromBranchId = $this->resolveBranchIdForWarehouse($fromWarehouseId);
            $toBranchId = $this->resolveBranchIdForWarehouse($toWarehouseId);

            // Get source inventory
            $fromInventory = Inventory::where('warehouse_id', $fromWarehouseId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $fromInventory || $fromInventory->sellable_stock < $quantity) {
                throw new \Exception('Insufficient stock in source warehouse');
            }

            // Decrease from source
            $fromInventory->decreaseStock($quantity);

            // Get or create destination inventory
            $toInventory = Inventory::firstOrCreate(
                [
                    'warehouse_id' => $toWarehouseId,
                    'product_variant_id' => $variantId,
                ],
                [
                    'branch_id' => $toBranchId,
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                    'reorder_level' => $fromInventory->reorder_level,
                ]
            );

            if (empty($toInventory->branch_id)) {
                $toInventory->branch_id = $toBranchId;
                $toInventory->save();
            }

            // Increase destination
            $toInventory->increaseStock($quantity, $fromInventory->productVariant->cost_price);

            // Record movements
            $productId = $this->getVariantProductId($variantId);
            StockMovement::create([
                'branch_id' => $fromBranchId,
                'warehouse_id' => $fromWarehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'transfer_out',
                'reference_type' => 'warehouse_transfer',
                'reference_id' => null,
                'quantity' => -$quantity,
                'balance_after' => $fromInventory->sellable_stock,
                'notes' => "Transfer to warehouse #{$toWarehouseId}: {$notes}",
                'created_by' => $this->resolveCreatorId(),
            ]);

            StockMovement::create([
                'branch_id' => $toBranchId,
                'warehouse_id' => $toWarehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'transfer_in',
                'reference_type' => 'warehouse_transfer',
                'reference_id' => null,
                'quantity' => $quantity,
                'balance_after' => $toInventory->sellable_stock,
                'notes' => "Transfer from warehouse #{$fromWarehouseId}: {$notes}",
                'created_by' => $this->resolveCreatorId(),
            ]);

            // Clear cache
            $this->clearStockCache($fromWarehouseId, $variantId);
            $this->clearStockCache($toWarehouseId, $variantId);
            $this->syncWarehouseStockAggregate($fromWarehouseId, $productId);
            $this->syncWarehouseStockAggregate($toWarehouseId, $productId);

            return true;
        });
    }

    /**
     * Manual stock adjustment
     */
    public function adjustStock(int $warehouseId, int $variantId, float $quantity, string $reason): bool
    {
        if ($quantity == 0.0) {
            throw new \InvalidArgumentException('Adjustment quantity must be non-zero.');
        }

        return DB::transaction(function () use ($warehouseId, $variantId, $quantity, $reason) {
            $branchId = $this->resolveBranchIdForWarehouse($warehouseId);

            $inventory = Inventory::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $variantId,
                ],
                [
                    'branch_id' => $branchId,
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                    'reorder_level' => 5,
                ]
            );

            $oldQuantity = (float) $inventory->quantity_available;
            $newQuantity = $oldQuantity + $quantity;

            if ($newQuantity < 0) {
                throw new \RuntimeException("Cannot reduce stock below 0. Current: {$oldQuantity}, Adjustment: {$quantity}");
            }
            if ($newQuantity < (float) $inventory->quantity_reserved) {
                throw new \RuntimeException("Cannot reduce stock below reserved quantity ({$inventory->quantity_reserved}).");
            }

            $inventory->adjustStock($newQuantity, $reason);
            $inventory->refresh();

            // Record movement
            $movementType = $quantity > 0 ? 'adjustment_in' : 'adjustment_out';
            $productId = $this->getVariantProductId($variantId);
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => $movementType,
                'reference_type' => 'stock_adjustment',
                'reference_id' => null,
                'quantity' => $quantity,
                'unit_cost' => (float) ($inventory->productVariant->cost_price ?? 0),
                'balance_after' => $inventory->sellable_stock,
                'notes' => "Manual adjustment: {$reason} (from {$oldQuantity} to {$newQuantity})",
                'created_by' => $this->resolveCreatorId(),
            ]);

            $this->clearStockCache($warehouseId, $variantId);
            $this->syncWarehouseStockAggregate($warehouseId, $productId);

            // Check for alerts
            $this->checkAndTriggerLowStockAlert($inventory);

            return true;
        });
    }

    /**
     * Check stock availability for checkout
     */
    public function checkStockAvailability(int $warehouseId, int $variantId, float $quantity): bool
    {
        $inventory = $this->getStockBalance($warehouseId, $variantId);

        if ($inventory && $inventory->sellable_stock >= $quantity) {
            return true;
        }

        // Check warehouse_stock fallback
        $warehouseStock = \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($warehouseStock && ($warehouseStock->physical_quantity - $warehouseStock->reserved_quantity) >= $quantity) {
            return true;
        }

        return false;
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(?int $warehouseId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Inventory::with(['productVariant.product', 'warehouse'])
            ->whereRaw('(quantity_available - quantity_reserved) <= reorder_level')
            ->whereRaw('(quantity_available - quantity_reserved) > 0');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * Get out of stock alerts
     */
    public function getOutOfStockAlerts(?int $warehouseId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Inventory::with(['productVariant.product', 'warehouse'])
            ->whereRaw('(quantity_available - quantity_reserved) <= 0');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * Get total inventory value for warehouse
     */
    public function getWarehouseTotalValue(int $warehouseId): float
    {
        return Inventory::where('warehouse_id', $warehouseId)->sum('total_value');
    }

    /**
     * Check and trigger low stock alerts
     */
    private function checkAndTriggerLowStockAlert(Inventory $inventory): void
    {
        if ($inventory->is_low_stock || $inventory->is_out_of_stock) {
            // Here you could trigger notifications, emails, etc.
            // For now, just log it
            \Log::warning('Stock Alert', [
                'variant_id' => $inventory->product_variant_id,
                'warehouse_id' => $inventory->warehouse_id,
                'available' => $inventory->sellable_stock,
                'reorder_level' => $inventory->reorder_level,
                'status' => $inventory->stock_status,
            ]);
        }
    }

    /**
     * Clear stock cache
     */
    private function clearStockCache(int $warehouseId, int $variantId): void
    {
        Cache::forget("variant_stock_summary_{$warehouseId}_{$variantId}");
        Cache::forget("variant_stock_balance_{$warehouseId}_{$variantId}");
    }

    /**
     * Keep legacy/product-level warehouse_stock in sync with variant inventory.
     * This ensures existing dashboards and product screens show updated stock.
     */
    private function syncWarehouseStockAggregate(int $warehouseId, int $productId): void
    {
        $branchId = $this->resolveBranchIdForWarehouse($warehouseId);

        $aggregate = Inventory::query()
            ->join('product_variants', 'inventories.product_variant_id', '=', 'product_variants.id')
            ->where('inventories.warehouse_id', $warehouseId)
            ->where('product_variants.product_id', $productId)
            ->selectRaw('COALESCE(SUM(inventories.quantity_available), 0) AS physical_quantity')
            ->selectRaw('COALESCE(SUM(inventories.quantity_reserved), 0) AS reserved_quantity')
            ->selectRaw('COALESCE(SUM(inventories.total_value), 0) AS total_value')
            ->first();

        $physicalQuantity = (float) ($aggregate->physical_quantity ?? 0);
        $reservedQuantity = (float) ($aggregate->reserved_quantity ?? 0);
        $availableQuantity = max(0, $physicalQuantity - $reservedQuantity);
        $totalValue = (float) ($aggregate->total_value ?? 0);

        $product = Product::query()->find($productId, ['id', 'sku', 'product_code', 'purchase_price']);
        $sku = $product?->sku ?: ($product?->product_code ?: ('SKU-'.$productId));
        $averageCost = $physicalQuantity > 0
            ? round($totalValue / $physicalQuantity, 4)
            : (float) ($product?->purchase_price ?? 0);

        $warehouseStockKeys = [
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
        ];
        if (Schema::hasColumn((new WarehouseStock)->getTable(), 'product_variant_id')) {
            $warehouseStockKeys['product_variant_id'] = null;
        }

        WarehouseStock::updateOrCreate(
            $warehouseStockKeys,
            [
                'branch_id' => $branchId,
                'sku' => $sku,
                'physical_quantity' => $physicalQuantity,
                'reserved_quantity' => $reservedQuantity,
                'available_quantity' => $availableQuantity,
                'average_cost' => $averageCost,
                'total_value' => $totalValue,
            ]
        );

        Cache::forget("stock_summary_{$warehouseId}_{$productId}");
        Cache::forget("stock_balance_{$warehouseId}_{$productId}");
        Cache::forget("product_stock_{$productId}");
        Cache::forget("warehouse_total_value_{$warehouseId}");

        // NEW: Also sync the global Stock model (aggregate across all warehouses)
        $this->syncGlobalStock($productId);
    }

    /**
     * Synchronize the global Stock model for a product and its variants.
     * Aggregates data from all warehouses.
     */
    private function syncGlobalStock(int $productId): void
    {
        if (! Schema::hasTable('stocks')) {
            return;
        }

        $branchId = $this->defaultBranchId();

        // 1. Sync variant-level global stocks
        $variantAggregates = Inventory::query()
            ->join('product_variants', 'inventories.product_variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $productId)
            ->whereNotNull('inventories.product_variant_id')
            ->groupBy('inventories.product_variant_id')
            ->select('inventories.product_variant_id')
            ->selectRaw('COALESCE(SUM(inventories.quantity_available), 0) as available_qty')
            ->selectRaw('COALESCE(SUM(inventories.quantity_reserved), 0) as reserved_qty')
            ->get();

        foreach ($variantAggregates as $variantAgg) {
            \App\Models\Stock::updateOrCreate(
                [
                    'product_id' => $productId,
                    'variant_id' => $variantAgg->product_variant_id,
                    'branch_id' => $branchId,
                ],
                [
                    'available_qty' => $variantAgg->available_qty,
                    'reserved_qty' => $variantAgg->reserved_qty,
                ]
            );
        }

        // 2. Sync product-level (no variant) global stock from warehouse_stock
        $productAggregateAvailable = WarehouseStock::where('product_id', $productId)
            ->sum('available_quantity');

        $productAggregateReserved = WarehouseStock::where('product_id', $productId)
            ->sum('reserved_quantity');

        \App\Models\Stock::updateOrCreate(
            [
                'product_id' => $productId,
                'variant_id' => null,
                'branch_id' => $branchId,
            ],
            [
                'available_qty' => $productAggregateAvailable,
                'reserved_qty' => $productAggregateReserved,
            ]
        );
    }

    private function defaultBranchId(): int
    {
        if (! Schema::hasTable('branches')) {
            return 1;
        }

        return (int) (DB::table('branches')->where('code', 'MAIN')->value('id')
            ?? DB::table('branches')->value('id')
            ?? 1);
    }

    private function postSupplierLedgerForReceivedStock(PurchaseOrder $purchaseOrder, ?int $creatorId): void
    {
        $targetReceivedAmount = (float) $purchaseOrder->purchaseItems()
            ->selectRaw('COALESCE(SUM(quantity_received * unit_cost), 0) AS total_received_amount')
            ->value('total_received_amount');

        if ($targetReceivedAmount <= 0) {
            $this->updatePurchaseOrderFinancialSnapshot($purchaseOrder, 0.0);

            return;
        }

        if ($purchaseOrder->supplier) {
            $actorId = (int) ($creatorId ?: $purchaseOrder->created_by ?: 0);
            if ($actorId <= 0) {
                $actorId = (int) (\App\Models\User::query()->value('id') ?? 0);
            }

            if ($actorId <= 0) {
                throw new \RuntimeException('Unable to post supplier purchase entry: no valid user context.');
            }

            app(SupplierService::class)->syncPurchaseReceiptLedger($purchaseOrder->supplier, [
                'branch_id' => (int) ($purchaseOrder->branch_id ?: $this->resolveBranchIdForWarehouse((int) $purchaseOrder->warehouse_id)),
                'target_received_amount' => round($targetReceivedAmount, 2),
                'purchase_date' => now()->toDateString(),
                'purchase_id' => $purchaseOrder->id,
                'purchase_number' => $purchaseOrder->po_number,
                'reference_type' => 'purchase_receipt',
                'description' => "Goods receipt posted for PO #{$purchaseOrder->po_number}",
                'created_by' => $actorId,
            ]);
        }

        $this->updatePurchaseOrderFinancialSnapshot($purchaseOrder, $targetReceivedAmount);

        if ($purchaseOrder->status === 'received') {
            app(BranchAccountingService::class)->postPurchaseEntry($purchaseOrder->fresh());
        }
    }

    private function updatePurchaseOrderFinancialSnapshot(PurchaseOrder $purchaseOrder, ?float $targetReceivedAmount = null): void
    {
        $hasReceivedAt = $this->hasPurchaseOrderColumn('received_at');
        $hasLedgerPostedAmount = $this->hasPurchaseOrderColumn('ledger_posted_amount');
        if (! $hasReceivedAt && ! $hasLedgerPostedAmount) {
            return;
        }

        $payload = [];

        if ($hasReceivedAt && $purchaseOrder->status === 'received' && empty($purchaseOrder->received_at)) {
            $payload['received_at'] = now();
        }

        if ($hasLedgerPostedAmount) {
            $postedAmount = 0.0;
            if ($purchaseOrder->supplier_id) {
                $postedAmount = app(SupplierService::class)->getPostedPurchaseReceiptAmount(
                    (int) $purchaseOrder->supplier_id,
                    (int) $purchaseOrder->id
                );
            } elseif ($targetReceivedAmount !== null) {
                $postedAmount = max(0, $targetReceivedAmount);
            }

            $payload['ledger_posted_amount'] = round((float) $postedAmount, 2);
        }

        if (empty($payload)) {
            return;
        }

        PurchaseOrder::query()
            ->whereKey($purchaseOrder->id)
            ->update($payload);
    }

    private function hasPurchaseOrderColumn(string $column): bool
    {
        static $cache = [];

        if (! array_key_exists($column, $cache)) {
            $cache[$column] = Schema::hasColumn('purchase_orders', $column);
        }

        return $cache[$column];
    }

    private function resolveBranchIdForWarehouse(int $warehouseId): int
    {
        $branchId = (int) (Warehouse::query()
            ->whereKey($warehouseId)
            ->value('branch_id') ?? 0);

        if ($branchId > 0) {
            return $branchId;
        }

        if (! Schema::hasTable('branches')) {
            return 1;
        }

        return (int) (DB::table('branches')->where('code', 'MAIN')->value('id')
            ?? DB::table('branches')->value('id')
            ?? 1);
    }

    private function getVariantProductId(int $variantId): int
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            throw new \Exception("Product variant not found: {$variantId}");
        }

        return (int) $variant->product_id;
    }

    private function resolveCreatorId(): ?int
    {
        return auth()->id() ?? \App\Models\User::query()->value('id');
    }

    /**
     * Get variant stock from the warehouse stock table.
     */
    private function getWarehouseStockForVariant(int $warehouseId, int $variantId): float
    {
        return (float) (WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $variantId)
            ->selectRaw('SUM(CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END) AS available_stock')
            ->value('available_stock') ?? 0);
    }

    /**
     * Align inventories.quantity_* with warehouse_stock variant rows when the ledger row drifted.
     * Without this, checkStockAvailability() can pass (fallback to warehouse_stock) while reserveStock()
     * fails because it only looked at the stale inventories row — common on variable products / PDP.
     */
    private function reconcileInventoryWithWarehouseVariantStock(Inventory $inventory, int $warehouseId, int $variantId): void
    {
        $warehouseSellable = $this->getWarehouseStockForVariant($warehouseId, $variantId);
        $inventorySellable = (float) $inventory->sellable_stock;

        if ($warehouseSellable <= $inventorySellable) {
            return;
        }

        $reserved = (float) ($inventory->quantity_reserved ?? 0);
        $inventory->quantity_available = $warehouseSellable + $reserved;
        $inventory->save();
    }
}
