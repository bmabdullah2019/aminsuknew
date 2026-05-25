<?php

namespace App\Listeners;

use App\Events\PurchaseReturnApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class UpdateDashboardOnPurchaseReturnApproved implements ShouldQueue
{
    use InteractsWithQueue;

    public $delay = 10;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PurchaseReturnApproved $event): void
    {
        $purchaseReturn = $event->purchaseReturn;
        $branchId = $purchaseReturn->branch_id;

        // Clear relevant dashboard caches
        Cache::tags([
            'dashboard',
            'branch-'.$branchId,
            'supplier-aging',
            'inventory-summary',
            'purchase-metrics',
        ])->flush();

        // Update specific dashboard metrics cache
        $cacheKey = 'dashboard_metrics_branch_'.$branchId;
        Cache::forget($cacheKey);

        // Update supplier summary cache
        $supplierCacheKey = 'supplier_summary_'.$purchaseReturn->supplier_id.'_branch_'.$branchId;
        Cache::forget($supplierCacheKey);

        // Update inventory summary cache
        foreach ($purchaseReturn->items as $item) {
            $inventoryCacheKey = 'product_inventory_'.$item->product_id.'_branch_'.$branchId;
            Cache::forget($inventoryCacheKey);
        }

        // Broadcast event for real-time dashboard updates if needed
        if (config('broadcasting.default') !== 'null') {
            broadcast(new \App\Events\DashboardDataUpdated($branchId))->toOthers();
        }
    }
}
