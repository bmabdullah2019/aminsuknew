<?php

namespace App\Listeners;

use App\Events\PurchaseReturnApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class UpdateSupplierAgingOnPurchaseReturnApproved implements ShouldQueue
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
            $supplier = $purchaseReturn->supplier;

            // Reduce supplier's outstanding balance by return amount
            // This helps in aging analysis - reduces what they owe us
            if ($supplier) {
                $supplier->decrement('total_outstanding', $purchaseReturn->total_return_amount);

                // If purchase order exists, update its received amount
                if ($purchaseReturn->purchaseOrder) {
                    $purchaseOrder = $purchaseReturn->purchaseOrder;

                    // Reduce the amount received from the PO
                    $newReceivedAmount = $purchaseOrder->received_amount - $purchaseReturn->total_return_amount;
                    $newReceivedAmount = max(0, $newReceivedAmount); // Ensure it doesn't go negative

                    $purchaseOrder->update([
                        'received_amount' => $newReceivedAmount,
                    ]);

                    // Check if all items are returned or received
                    if ($newReceivedAmount == 0) {
                        // Mark PO as partially returned or update status
                    }
                }

                // Log the return in supplier aging records
                // This helps track supplier returns and account reconciliation
                $supplier->update([
                    'last_transaction_date' => now(),
                ]);
            }
        });
    }
}
