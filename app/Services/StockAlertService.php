<?php

namespace App\Services;

use App\Models\StockAlert;
use App\Models\WarehouseStock;
use Carbon\Carbon;

class StockAlertService
{
    /**
     * Check and generate low stock alerts
     */
    public function checkLowStock(): int
    {
        $count = 0;

        $lowStockItems = WarehouseStock::with(['warehouse', 'product'])
            ->whereRaw('available_quantity <= reorder_point')
            ->where('available_quantity', '>', 0)
            ->get();

        foreach ($lowStockItems as $stock) {
            // Check if alert already exists
            $existingAlert = StockAlert::where('warehouse_id', $stock->warehouse_id)
                ->where('product_id', $stock->product_id)
                ->where('alert_type', 'low_stock')
                ->where('status', 'active')
                ->first();

            if (! $existingAlert) {
                StockAlert::create([
                    'warehouse_id' => $stock->warehouse_id,
                    'product_id' => $stock->product_id,
                    'alert_type' => 'low_stock',
                    'severity' => $this->calculateSeverity($stock->available_quantity, $stock->reorder_point),
                    'current_quantity' => $stock->available_quantity,
                    'threshold_quantity' => $stock->reorder_point,
                    'message' => "Low stock alert: {$stock->product->name} has {$stock->available_quantity} units remaining (Reorder point: {$stock->reorder_point})",
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
                $count++;
            } else {
                // Update existing alert
                $existingAlert->update([
                    'current_quantity' => $stock->available_quantity,
                    'severity' => $this->calculateSeverity($stock->available_quantity, $stock->reorder_point),
                    'message' => "Low stock alert: {$stock->product->name} has {$stock->available_quantity} units remaining (Reorder point: {$stock->reorder_point})",
                ]);
            }
        }

        return $count;
    }

    /**
     * Check and generate out of stock alerts
     */
    public function checkOutOfStock(): int
    {
        $count = 0;

        $outOfStockItems = WarehouseStock::with(['warehouse', 'product'])
            ->where('available_quantity', '<=', 0)
            ->where('physical_quantity', '<=', 0)
            ->get();

        foreach ($outOfStockItems as $stock) {
            // Check if alert already exists
            $existingAlert = StockAlert::where('warehouse_id', $stock->warehouse_id)
                ->where('product_id', $stock->product_id)
                ->where('alert_type', 'out_of_stock')
                ->where('status', 'active')
                ->first();

            if (! $existingAlert) {
                StockAlert::create([
                    'warehouse_id' => $stock->warehouse_id,
                    'product_id' => $stock->product_id,
                    'alert_type' => 'out_of_stock',
                    'severity' => 'critical',
                    'current_quantity' => 0,
                    'threshold_quantity' => 0,
                    'message' => "Out of stock: {$stock->product->name} is completely out of stock",
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check and generate expiring stock alerts
     */
    public function checkExpiringStock(int $daysAhead = 30): int
    {
        $count = 0;
        $expiryDate = Carbon::now()->addDays($daysAhead);

        // Note: This assumes you have an expiry_date field in warehouse_stock
        // If not, you may need to add it or adjust this logic
        $expiringItems = WarehouseStock::with(['warehouse', 'product'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $expiryDate)
            ->where('expiry_date', '>', Carbon::now())
            ->where('physical_quantity', '>', 0)
            ->get();

        foreach ($expiringItems as $stock) {
            $daysUntilExpiry = Carbon::now()->diffInDays($stock->expiry_date);

            $existingAlert = StockAlert::where('warehouse_id', $stock->warehouse_id)
                ->where('product_id', $stock->product_id)
                ->where('alert_type', 'expiry_warning')
                ->where('status', 'active')
                ->first();

            if (! $existingAlert) {
                StockAlert::create([
                    'warehouse_id' => $stock->warehouse_id,
                    'product_id' => $stock->product_id,
                    'alert_type' => 'expiry_warning',
                    'severity' => $daysUntilExpiry <= 7 ? 'critical' : 'warning',
                    'current_quantity' => $stock->physical_quantity,
                    'threshold_quantity' => 0,
                    'message' => "Expiring stock: {$stock->product->name} will expire in {$daysUntilExpiry} days ({$stock->expiry_date->format('Y-m-d')})",
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
                $count++;
            } else {
                $existingAlert->update([
                    'current_quantity' => $stock->physical_quantity,
                    'severity' => $daysUntilExpiry <= 7 ? 'critical' : 'warning',
                    'message' => "Expiring stock: {$stock->product->name} will expire in {$daysUntilExpiry} days ({$stock->expiry_date->format('Y-m-d')})",
                ]);
            }
        }

        return $count;
    }

    /**
     * Check and generate dead stock alerts
     */
    public function checkDeadStock(int $daysThreshold = 90): int
    {
        $count = 0;
        $thresholdDate = Carbon::now()->subDays($daysThreshold);

        $deadStockItems = WarehouseStock::with(['warehouse', 'product'])
            ->where('physical_quantity', '>', 0)
            ->whereDoesntHave('movements', function ($query) use ($thresholdDate) {
                $query->where('created_at', '>=', $thresholdDate)
                    ->where('quantity', '<', 0); // No sales/outgoing movements
            })
            ->get();

        foreach ($deadStockItems as $stock) {
            $lastMovement = $stock->movements()
                ->where('quantity', '<', 0)
                ->latest()
                ->first();

            if ($lastMovement && $lastMovement->created_at < $thresholdDate) {
                $existingAlert = StockAlert::where('warehouse_id', $stock->warehouse_id)
                    ->where('product_id', $stock->product_id)
                    ->where('alert_type', 'dead_stock')
                    ->where('status', 'active')
                    ->first();

                if (! $existingAlert) {
                    $daysSinceLastSale = Carbon::now()->diffInDays($lastMovement->created_at);

                    StockAlert::create([
                        'warehouse_id' => $stock->warehouse_id,
                        'product_id' => $stock->product_id,
                        'alert_type' => 'dead_stock',
                        'severity' => 'warning',
                        'current_quantity' => $stock->physical_quantity,
                        'threshold_quantity' => 0,
                        'message' => "Dead stock: {$stock->product->name} has not been sold in {$daysSinceLastSale} days. Current stock: {$stock->physical_quantity} units",
                        'status' => 'active',
                        'created_by' => auth()->id(),
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Generate all alerts
     */
    public function generateAlerts(): array
    {
        return [
            'low_stock' => $this->checkLowStock(),
            'out_of_stock' => $this->checkOutOfStock(),
            'expiring_stock' => $this->checkExpiringStock(),
            'dead_stock' => $this->checkDeadStock(),
        ];
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert(int $alertId, ?string $notes = null): bool
    {
        $alert = StockAlert::findOrFail($alertId);

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Calculate alert severity based on stock level
     */
    private function calculateSeverity(float $currentQuantity, float $reorderPoint): string
    {
        if ($currentQuantity <= 0) {
            return 'critical';
        }

        if ($reorderPoint <= 0) {
            return 'warning';
        }

        $percentage = ($currentQuantity / $reorderPoint) * 100;

        if ($percentage <= 25) {
            return 'critical';
        }

        if ($percentage <= 100) {
            return 'warning';
        }

        return 'info';
    }

    /**
     * Get active alerts count by type
     */
    public function getActiveAlertsCount(): array
    {
        return StockAlert::where('status', 'active')
            ->selectRaw('alert_type, COUNT(*) as count')
            ->groupBy('alert_type')
            ->pluck('count', 'alert_type')
            ->toArray();
    }

    /**
     * Check stock alerts for a specific stock item
     */
    public function checkStockAlerts(WarehouseStock $stock): void
    {
        // Check for low stock alerts
        if ($stock->available_quantity <= $stock->reorder_point && $stock->available_quantity > 0) {
            $this->createOrUpdateAlert($stock, 'low_stock', 'warning');
        }

        // Check for out of stock alerts
        if ($stock->available_quantity <= 0) {
            $this->createOrUpdateAlert($stock, 'out_of_stock', 'critical');
        }

        // Check for overstock alerts (if available > 1.5x reorder point)
        if ($stock->available_quantity >= ($stock->reorder_point * 1.5) && $stock->reorder_point > 0) {
            $this->createOrUpdateAlert($stock, 'overstock', 'info');
        }
    }

    /**
     * Create or update stock alert
     */
    private function createOrUpdateAlert(WarehouseStock $stock, string $alertType, string $severity): void
    {
        $existingAlert = StockAlert::where('warehouse_id', $stock->warehouse_id)
            ->where('product_id', $stock->product_id)
            ->where('alert_type', $alertType)
            ->where('status', 'active')
            ->first();

        $message = $this->generateAlertMessage($stock, $alertType);

        if ($existingAlert) {
            $existingAlert->update([
                'current_quantity' => $stock->available_quantity,
                'severity' => $severity,
                'message' => $message,
                'updated_at' => now(),
            ]);
        } else {
            StockAlert::create([
                'warehouse_id' => $stock->warehouse_id,
                'product_id' => $stock->product_id,
                'alert_type' => $alertType,
                'severity' => $severity,
                'current_quantity' => $stock->available_quantity,
                'threshold_quantity' => $stock->reorder_point,
                'message' => $message,
                'status' => 'active',
                'created_by' => auth()->id() ?? \App\Models\User::query()->value('id'),
            ]);
        }
    }

    /**
     * Generate alert message based on type
     */
    private function generateAlertMessage(WarehouseStock $stock, string $alertType): string
    {
        switch ($alertType) {
            case 'low_stock':
                return "Low stock alert: {$stock->product->name} has {$stock->available_quantity} units remaining (Reorder point: {$stock->reorder_point})";

            case 'out_of_stock':
                return "Out of stock: {$stock->product->name} is completely out of stock";

            case 'overstock':
                return "Overstock alert: {$stock->product->name} has {$stock->available_quantity} units (1.5x reorder point: ".($stock->reorder_point * 1.5).')';

            default:
                return "Stock alert for {$stock->product->name}";
        }
    }
}
