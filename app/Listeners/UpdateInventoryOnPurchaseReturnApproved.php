<?php

namespace App\Listeners;

use App\Events\PurchaseReturnApproved;
use App\Models\StockMovement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class UpdateInventoryOnPurchaseReturnApproved implements ShouldQueue
{
    use InteractsWithQueue;

    public $delay = 5;

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

        DB::transaction(function () use ($purchaseReturn) {
            // Process each item in the return
            foreach ($purchaseReturn->items as $item) {
                // Reduce product stock for the returned items
                if ($item->product) {
                    // Update product quantity
                    $product = $item->product;

                    if ($item->variant) {
                        // If variant exists, update variant quantity
                        $item->variant->decrement('quantity', $item->quantity);
                    } else {
                        // Otherwise update product quantity directly
                        $product->decrement('quantity', $item->quantity);
                    }

                    // Log stock movement for audit trail
                    StockMovement::create([
                        'branch_id' => $purchaseReturn->branch_id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'movement_type' => 'purchase_return',
                        'reference_id' => $purchaseReturn->id,
                        'reference_type' => 'App\Models\PurchaseReturn',
                        'quantity' => -$item->quantity,
                        'movement_date' => $purchaseReturn->return_date,
                        'notes' => 'Purchase return approved - '.$purchaseReturn->return_reason,
                    ]);
                }
            }

            // Update purchase return status to 'processed'
            $purchaseReturn->mark_processed();
        });
    }
}
