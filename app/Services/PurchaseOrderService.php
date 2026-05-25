<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseOrderService
{
    /**
     * Create a new purchase order
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $creatorId = auth()->id() ?? \App\Models\User::query()->value('id');
            if (! $creatorId) {
                throw new \RuntimeException('Unable to create purchase order: no valid user context.');
            }

            // Create purchase order
            $branchId = $this->resolveBranchIdFromWarehouse((int) $data['warehouse_id']);

            $purchaseOrder = PurchaseOrder::create([
                'branch_id' => $branchId,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'draft',
                'ordered_at' => null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $creatorId,
            ]);

            // Create purchase items
            $totalCost = 0;
            foreach ($data['items'] as $itemData) {
                ProductVariant::findOrFail($itemData['product_variant_id']);
                $quantity = (float) ($itemData['quantity'] ?? $itemData['quantity_ordered'] ?? 0);
                $unitCost = (float) ($itemData['unit_cost'] ?? $itemData['cost'] ?? 0);

                if ($quantity <= 0) {
                    throw new \Exception('Purchase item quantity must be greater than zero');
                }

                if ($unitCost < 0) {
                    throw new \Exception('Purchase item unit cost cannot be negative');
                }

                $lineTotal = $quantity * $unitCost;

                PurchaseItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'branch_id' => $branchId,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'quantity_ordered' => $quantity,
                    'quantity_received' => 0,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $totalCost += $lineTotal;
            }

            // Update total cost
            $purchaseOrder->update(['total_cost' => $totalCost]);

            return $purchaseOrder;
        });
    }

    /**
     * Approve purchase order
     */
    public function approvePurchaseOrder(int $purchaseOrderId, int $approverId): bool
    {
        $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);

        if ($purchaseOrder->status !== 'pending') {
            throw new \Exception('Only pending purchase orders can be approved');
        }

        return $purchaseOrder->approve(\App\Models\User::findOrFail($approverId));
    }

    /**
     * Submit purchase order for approval
     */
    public function submitForApproval(int $purchaseOrderId): bool
    {
        $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);

        if ($purchaseOrder->status !== 'draft') {
            throw new \Exception('Only draft purchase orders can be submitted for approval');
        }

        if ($purchaseOrder->purchaseItems->isEmpty()) {
            throw new \Exception('Purchase order must have at least one item');
        }

        $purchaseOrder->status = 'pending';

        return $purchaseOrder->save();
    }

    /**
     * Mark purchase order as ordered
     */
    public function markAsOrdered(int $purchaseOrderId): bool
    {
        $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);

        return $purchaseOrder->markAsOrdered();
    }

    /**
     * Receive items from purchase order
     */
    public function receiveItems(int $purchaseOrderId, array $items): bool
    {
        $variantStockService = app(VariantStockService::class);

        return $variantStockService->receiveStockFromPO($purchaseOrderId, $items);
    }

    /**
     * Get purchase orders by status
     */
    public function getPurchaseOrdersByStatus(string $status, ?int $warehouseId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = PurchaseOrder::with(['supplier', 'warehouse', 'creator', 'approver', 'purchaseItems.productVariant.product'])
            ->where('status', $status);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->latest()->get();
    }

    /**
     * Get overdue purchase orders
     */
    public function getOverduePurchaseOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseOrder::with(['supplier', 'warehouse'])
            ->where('expected_delivery_date', '<', now())
            ->whereIn('status', ['ordered', 'partial_received'])
            ->get();
    }

    /**
     * Calculate supplier performance metrics
     */
    public function calculateSupplierPerformance(int $supplierId): array
    {
        $purchaseOrders = PurchaseOrder::where('supplier_id', $supplierId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->get();

        $totalOrders = $purchaseOrders->count();
        $completedOrders = $purchaseOrders->where('status', 'received')->count();
        $onTimeDeliveries = $purchaseOrders->filter(function ($po) {
            if ($po->status !== 'received') {
                return false;
            }

            if (! $po->expected_delivery_date) {
                return true;
            }

            $receivedAt = $po->received_at ?? $po->updated_at;

            return $receivedAt && $receivedAt <= $po->expected_delivery_date;
        })->count();

        $totalValue = $purchaseOrders->sum('total_cost');
        $completedValue = $purchaseOrders->where('status', 'received')->sum('total_cost');

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'completion_rate' => $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0,
            'on_time_delivery_rate' => $completedOrders > 0 ? ($onTimeDeliveries / $completedOrders) * 100 : 0,
            'total_value' => $totalValue,
            'completed_value' => $completedValue,
        ];
    }

    /**
     * Generate purchase order report
     */
    public function generatePurchaseOrderReport(array $filters = []): array
    {
        $query = PurchaseOrder::with(['supplier', 'warehouse', 'purchaseItems']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $purchaseOrders = $query->get();

        return [
            'summary' => [
                'total_orders' => $purchaseOrders->count(),
                'total_value' => $purchaseOrders->sum('total_cost'),
                'draft_orders' => $purchaseOrders->where('status', 'draft')->count(),
                'pending_orders' => $purchaseOrders->where('status', 'pending')->count(),
                'approved_orders' => $purchaseOrders->where('status', 'approved')->count(),
                'ordered_orders' => $purchaseOrders->where('status', 'ordered')->count(),
                'partial_received' => $purchaseOrders->where('status', 'partial_received')->count(),
                'received_orders' => $purchaseOrders->where('status', 'received')->count(),
                'cancelled_orders' => $purchaseOrders->where('status', 'cancelled')->count(),
            ],
            'orders' => $purchaseOrders,
        ];
    }

    /**
     * Auto-generate purchase orders for low stock items
     */
    public function generateAutoPurchaseOrders(int $warehouseId): array
    {
        $variantStockService = app(VariantStockService::class);
        $lowStockItems = $variantStockService->getLowStockAlerts($warehouseId);

        $generatedOrders = [];

        foreach ($lowStockItems as $inventory) {
            $variant = $inventory->productVariant;

            // Check if there's already a pending/approved PO for this variant
            $existingPO = PurchaseOrder::whereHas('purchaseItems', function ($query) use ($variant) {
                $query->where('product_variant_id', $variant->id);
            })
                ->whereIn('status', ['pending', 'approved', 'ordered', 'partial_received'])
                ->where('warehouse_id', $warehouseId)
                ->exists();

            if (! $existingPO && $variant->product->supplier) {
                // Generate auto PO
                $quantity = $inventory->reorder_level * 2; // Order double the reorder level

                try {
                    $po = $this->createPurchaseOrder([
                        'supplier_id' => $variant->product->supplier->id,
                        'warehouse_id' => $warehouseId,
                        'expected_delivery_date' => now()->addDays(7),
                        'notes' => 'Auto-generated purchase order for low stock',
                        'items' => [
                            [
                                'product_variant_id' => $variant->id,
                                'quantity' => $quantity,
                                'unit_cost' => $variant->cost_price,
                                'notes' => 'Auto-reorder',
                            ],
                        ],
                    ]);

                    $generatedOrders[] = $po;
                } catch (\Exception $e) {
                    \Log::error('Failed to generate auto PO', [
                        'variant_id' => $variant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $generatedOrders;
    }

    private function resolveBranchIdFromWarehouse(int $warehouseId): int
    {
        $branchId = (int) (\App\Models\Warehouse::query()
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
}
