<?php

namespace App\Listeners;

use App\Events\PurchaseOrderReceived;
use App\Services\BranchAccountingService;
use Illuminate\Support\Facades\Log;

class PostPurchaseVoucherOnReceive
{
    public function handle(PurchaseOrderReceived $event): void
    {
        $purchaseOrder = $event->purchaseOrder->fresh();
        if (! $purchaseOrder) {
            return;
        }

        if ($purchaseOrder->status !== 'received') {
            return;
        }

        try {
            app(BranchAccountingService::class)->postPurchaseEntry($purchaseOrder);
        } catch (\Throwable $exception) {
            Log::error('Purchase receive voucher posting failed', [
                'purchase_order_id' => (int) $purchaseOrder->id,
                'po_number' => (string) $purchaseOrder->po_number,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
