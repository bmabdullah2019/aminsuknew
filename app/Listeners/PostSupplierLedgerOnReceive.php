<?php

namespace App\Listeners;

use App\Events\PurchaseOrderReceived;
use App\Models\SupplierLedger;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;

class PostSupplierLedgerOnReceive
{
    public function handle(PurchaseOrderReceived $event): void
    {
        $po = $event->purchaseOrder;
        $totalCost = $event->totalCost;

        // Skip if already posted for this PO
        $alreadyPosted = SupplierLedger::where('supplier_id', $po->supplier_id)
            ->where('reference_type', 'purchase_order')
            ->where('reference_id', $po->id)
            ->exists();

        if ($alreadyPosted) {
            return;
        }

        $supplier = $po->supplier;
        if (! $supplier) {
            Log::warning("PurchaseOrderReceived: Supplier not found for PO #{$po->po_number}");

            return;
        }

        $branchId = $po->branch_id ?: ($po->warehouse_id
            ? Warehouse::query()->whereKey((int) $po->warehouse_id)->value('branch_id')
            : null);

        $supplier->addLedgerEntry('purchase', $totalCost, 0, [
            'reference_type' => 'purchase_order',
            'reference_id' => $po->id,
            'reference_number' => $po->po_number,
            'description' => "Purchase from PO #{$po->po_number}",
            'transaction_date' => now()->toDateString(),
            'created_by' => $po->created_by ?? User::query()->value('id'),
            'branch_id' => $branchId,
        ]);

        // Update the ledger_posted_amount on PO
        $po->update(['ledger_posted_amount' => $totalCost]);

        Log::info("Supplier ledger auto-posted for PO #{$po->po_number}: BDT {$totalCost}");
    }
}
