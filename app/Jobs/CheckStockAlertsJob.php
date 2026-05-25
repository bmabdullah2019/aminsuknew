<?php

namespace App\Jobs;

use App\Models\StockAlert;
use App\Models\WarehouseStock;
use App\Services\StockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckStockAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(StockService $stockService): void
    {
        try {
            Log::info('Starting stock alerts check');

            // Get stock alerts
            $alerts = $stockService->getStockAlerts();

            // Process low stock alerts
            foreach ($alerts['low_stock'] as $stock) {
                $this->createOrUpdateAlert($stock, 'low_stock');
            }

            // Process out of stock alerts
            foreach ($alerts['out_of_stock'] as $stock) {
                $this->createOrUpdateAlert($stock, 'out_of_stock');
            }

            // Mark resolved alerts for products that are now adequately stocked
            $this->resolveAlerts();

            Log::info('Completed stock alerts check');

        } catch (\Exception $e) {
            Log::error('Error in stock alerts job: '.$e->getMessage());
        }
    }

    /**
     * Create or update stock alert
     */
    private function createOrUpdateAlert(WarehouseStock $stock, string $alertType): void
    {
        $existingAlert = StockAlert::where('warehouse_id', $stock->warehouse_id)
            ->where('product_id', $stock->product_id)
            ->where('alert_type', $alertType)
            ->where('status', 'active')
            ->first();

        if (! $existingAlert) {
            StockAlert::create([
                'warehouse_id' => $stock->warehouse_id,
                'product_id' => $stock->product_id,
                'alert_type' => $alertType,
                'current_quantity' => $stock->available_quantity,
                'threshold_quantity' => $alertType === 'low_stock' ? $stock->reorder_point : 0,
                'message' => $this->generateAlertMessage($stock, $alertType),
                'status' => 'active',
                'severity' => $alertType === 'out_of_stock' ? 'critical' : 'warning',
                'created_by' => 1, // System user
            ]);
        } else {
            // Update existing alert
            $existingAlert->update([
                'current_quantity' => $stock->available_quantity,
                'message' => $this->generateAlertMessage($stock, $alertType),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Generate alert message
     */
    private function generateAlertMessage(WarehouseStock $stock, string $alertType): string
    {
        $productName = $stock->product->name ?? 'Product #'.$stock->product_id;
        $warehouseName = $stock->warehouse->name ?? 'Warehouse #'.$stock->warehouse_id;

        switch ($alertType) {
            case 'out_of_stock':
                return "Product '{$productName}' is out of stock in {$warehouseName}. Immediate action required.";
            case 'low_stock':
                return "Product '{$productName}' is running low in {$warehouseName}. Current stock: {$stock->available_quantity}, Reorder point: {$stock->reorder_point}";
            default:
                return "Stock alert for '{$productName}' in {$warehouseName}";
        }
    }

    /**
     * Resolve alerts for products that are now adequately stocked
     */
    private function resolveAlerts(): void
    {
        // Get active alerts
        $activeAlerts = StockAlert::where('status', 'active')->get();

        foreach ($activeAlerts as $alert) {
            $stock = WarehouseStock::where('warehouse_id', $alert->warehouse_id)
                ->where('product_id', $alert->product_id)
                ->first();

            if (! $stock) {
                continue;
            }

            $shouldResolve = false;

            switch ($alert->alert_type) {
                case 'out_of_stock':
                    $shouldResolve = $stock->available_quantity > 0;
                    break;
                case 'low_stock':
                    $shouldResolve = $stock->available_quantity > $stock->reorder_point;
                    break;
            }

            if ($shouldResolve) {
                $alert->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'resolved_by' => 1, // System user
                ]);
            }
        }
    }
}
