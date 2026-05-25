<?php

namespace App\Services;

use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierOpeningBalance;
use App\Models\SupplierPayment;
use App\Models\SupplierPurchaseReturn;
use App\Models\SupplierPurchaseReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierService
{
    private const PURCHASE_LEDGER_TYPE = 'purchase';

    private const PURCHASE_RECEIPT_REFERENCE_TYPE = 'purchase_receipt';

    private const PURCHASE_RETURN_LEDGER_TYPE = 'purchase_return';

    private const PURCHASE_RETURN_REFERENCE_TYPE = 'return';

    private const PURCHASE_RETURN_EFFECTIVE_STATUSES = ['approved', 'completed'];

    /**
     * Set opening balance for a supplier
     */
    public function setOpeningBalance(Supplier $supplier, array $data): SupplierOpeningBalance
    {
        return DB::transaction(function () use ($supplier, $data) {
            $creatorId = $this->requireCreatorId($data, 'set opening balance');
            $branchId = $this->resolveBranchId($data);

            // Delete any existing opening balance and its opening-balance ledger entries.
            $supplier->openingBalances()->delete();
            $supplier->ledger()->where('transaction_type', 'opening_balance')->delete();

            $openingBalance = $supplier->openingBalances()->create([
                'opening_date' => $data['opening_date'],
                'opening_balance' => $data['opening_balance'],
                'balance_type' => $data['balance_type'] ?? 'debit',
                'description' => $data['description'] ?? 'Opening Balance',
                'created_by' => $creatorId,
            ]);

            $debit = $openingBalance->balance_type === 'debit' ? (float) $openingBalance->opening_balance : 0;
            $credit = $openingBalance->balance_type === 'credit' ? (float) $openingBalance->opening_balance : 0;

            // Add opening balance entry to ledger.
            $supplier->addLedgerEntry('opening_balance', $debit, $credit, [
                'branch_id' => $branchId,
                'transaction_date' => $openingBalance->opening_date,
                'description' => $openingBalance->description,
                'created_by' => $creatorId,
            ]);

            $this->recalculateBalance($supplier);

            return $openingBalance;
        });
    }

    /**
     * Record a purchase transaction
     */
    public function recordPurchase(Supplier $supplier, array $data): SupplierLedger
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Purchase amount must be greater than zero.');
        }

        $creatorId = isset($data['created_by'])
            ? (int) $data['created_by']
            : (int) (auth()->id() ?? \App\Models\User::query()->value('id') ?? 0);

        if ($creatorId <= 0) {
            throw new \RuntimeException('Unable to record purchase: no valid user context.');
        }

        return DB::transaction(function () use ($supplier, $data, $amount, $creatorId) {
            $branchId = $this->resolveBranchId($data);
            $lockedSupplier = Supplier::query()
                ->whereKey($supplier->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingPurchaseEntry = $this->findExistingPurchaseLedgerEntry($lockedSupplier, $data);
            if ($existingPurchaseEntry) {
                $existingPurchaseEntry->fill([
                    'branch_id' => $branchId,
                    'transaction_date' => $data['purchase_date'] ?? $existingPurchaseEntry->transaction_date ?? now()->toDateString(),
                    'reference_number' => $data['purchase_number'] ?? $existingPurchaseEntry->reference_number,
                    'description' => 'Purchase: '.($data['description'] ?? 'Purchase from supplier'),
                    'debit' => $amount,
                    'credit' => 0,
                ])->save();

                $this->recalculateBalance($lockedSupplier);

                return $existingPurchaseEntry->fresh();
            }

            $entry = $lockedSupplier->addLedgerEntry('purchase', $amount, 0, [
                'branch_id' => $branchId,
                'transaction_date' => $data['purchase_date'] ?? now()->toDateString(),
                'reference_type' => $data['reference_type'] ?? 'purchase',
                'reference_id' => $data['purchase_id'] ?? null,
                'reference_number' => $data['purchase_number'] ?? null,
                'description' => 'Purchase: '.($data['description'] ?? 'Purchase from supplier'),
                'created_by' => $creatorId,
            ]);

            $this->recalculateBalance($lockedSupplier);

            return $entry;
        });
    }

    /**
     * Idempotently sync purchase receipt payable entry for a purchase order.
     * Only the delta between posted amount and target received amount is posted.
     */
    public function syncPurchaseReceiptLedger(Supplier $supplier, array $data): ?SupplierLedger
    {
        $purchaseId = isset($data['purchase_id']) ? (int) $data['purchase_id'] : 0;
        if ($purchaseId <= 0) {
            throw new \InvalidArgumentException('Purchase order ID is required to sync purchase receipt ledger.');
        }

        $targetReceivedAmount = round((float) ($data['target_received_amount'] ?? 0), 2);
        if ($targetReceivedAmount <= 0) {
            return null;
        }

        $creatorId = isset($data['created_by'])
            ? (int) $data['created_by']
            : (int) (auth()->id() ?? \App\Models\User::query()->value('id') ?? 0);
        if ($creatorId <= 0) {
            throw new \RuntimeException('Unable to sync purchase receipt ledger: no valid user context.');
        }

        $referenceType = (string) ($data['reference_type'] ?? self::PURCHASE_RECEIPT_REFERENCE_TYPE);
        $purchaseDate = $data['purchase_date'] ?? now()->toDateString();
        $purchaseNumber = $data['purchase_number'] ?? null;
        $description = $data['description'] ?? ('Goods receipt posted for purchase #'.$purchaseId);
        $branchId = $this->resolveBranchId($data);

        return DB::transaction(function () use (
            $supplier,
            $purchaseId,
            $targetReceivedAmount,
            $creatorId,
            $referenceType,
            $purchaseDate,
            $purchaseNumber,
            $description,
            $branchId
        ) {
            $lockedSupplier = Supplier::query()
                ->whereKey($supplier->id)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyPosted = (float) SupplierLedger::query()
                ->where('supplier_id', $lockedSupplier->id)
                ->where('transaction_type', self::PURCHASE_LEDGER_TYPE)
                ->where('reference_type', $referenceType)
                ->where('reference_id', $purchaseId)
                ->sum('debit');

            $deltaAmount = round($targetReceivedAmount - $alreadyPosted, 2);
            if ($deltaAmount <= 0) {
                return null;
            }

            $entry = $lockedSupplier->addLedgerEntry(self::PURCHASE_LEDGER_TYPE, $deltaAmount, 0, [
                'branch_id' => $branchId,
                'transaction_date' => $purchaseDate,
                'reference_type' => $referenceType,
                'reference_id' => $purchaseId,
                'reference_number' => $purchaseNumber,
                'description' => $description,
                'created_by' => $creatorId,
            ]);

            $this->recalculateBalance($lockedSupplier);

            return $entry;
        });
    }

    /**
     * Get already posted payable amount for a purchase receipt ledger reference.
     */
    public function getPostedPurchaseReceiptAmount(int $supplierId, int $purchaseId): float
    {
        if ($supplierId <= 0 || $purchaseId <= 0) {
            return 0.0;
        }

        return (float) SupplierLedger::query()
            ->where('supplier_id', $supplierId)
            ->where('transaction_type', self::PURCHASE_LEDGER_TYPE)
            ->where('reference_type', self::PURCHASE_RECEIPT_REFERENCE_TYPE)
            ->where('reference_id', $purchaseId)
            ->sum('debit');
    }

    /**
     * Get outstanding due amount for a supplier.
     * If branch ID is provided, amount is scoped to that branch.
     */
    public function outstandingDue(Supplier $supplier, ?int $branchId = null): float
    {
        $query = SupplierLedger::query()
            ->where('supplier_id', (int) $supplier->id);

        if ($branchId !== null && $branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        return round((float) ($query
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as due_amount')
            ->value('due_amount') ?? 0), 2);
    }

    /**
     * Record a payment to supplier
     */
    public function recordPayment(Supplier $supplier, array $data): SupplierPayment
    {
        $creatorId = $this->requireCreatorId($data, 'record payment');
        $branchId = $this->resolveBranchId($data);
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $status = (string) ($data['status'] ?? 'completed');

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if (! in_array($status, ['pending', 'completed', 'cancelled'], true)) {
            $status = 'completed';
        }

        return DB::transaction(function () use ($supplier, $data, $creatorId, $branchId, $amount, $status) {
            $lockedSupplier = Supplier::query()
                ->whereKey($supplier->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($status === 'completed') {
                $outstandingDue = $this->outstandingDue($lockedSupplier, $branchId);

                if ($outstandingDue <= 0.01) {
                    throw new \InvalidArgumentException(
                        'No outstanding due is available in the selected branch for this supplier.'
                    );
                }

                if ($amount > ($outstandingDue + 0.01)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Payment amount exceeds branch due. Maximum payable is BDT %.2f.',
                        $outstandingDue
                    ));
                }
            }

            $payment = $lockedSupplier->payments()->create([
                'branch_id' => $branchId,
                'account_head_id' => ! empty($data['account_head_id']) ? (int) $data['account_head_id'] : null,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $status,
                'created_by' => $creatorId,
            ]);

            // Only completed payments affect payable balance.
            if ($payment->status === 'completed') {
                $lockedSupplier->addLedgerEntry('payment', 0, $payment->amount, [
                    'branch_id' => $branchId,
                    'transaction_date' => $payment->payment_date,
                    'reference_type' => 'payment',
                    'reference_id' => $payment->id,
                    'reference_number' => $payment->payment_number,
                    'description' => 'Payment: '.$payment->payment_method_label,
                    'created_by' => $creatorId,
                ]);

                $this->recalculateBalance($lockedSupplier);

                app(BranchAccountingService::class)->postSupplierPaymentEntry($payment);
            }

            return $payment;
        });
    }

    /**
     * Record a purchase return
     */
    public function recordPurchaseReturn(Supplier $supplier, array $data): SupplierPurchaseReturn
    {
        $creatorId = $this->requireCreatorId($data, 'record purchase return');
        $branchId = $this->resolvePurchaseReturnBranchId($data);

        return DB::transaction(function () use ($supplier, $data, $creatorId, $branchId) {
            $lockedSupplier = Supplier::query()
                ->whereKey($supplier->id)
                ->lockForUpdate()
                ->firstOrFail();

            $status = (string) ($data['status'] ?? 'draft');
            if (! in_array($status, ['draft', 'approved', 'completed'], true)) {
                $status = 'draft';
            }

            $payload = [
                'return_date' => $data['return_date'],
                'total_amount' => round((float) $data['total_amount'], 2),
                'return_reason' => $data['return_reason'],
                'original_purchase_id' => $data['original_purchase_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $status,
                'created_by' => $creatorId,
                'approved_by' => in_array($status, self::PURCHASE_RETURN_EFFECTIVE_STATUSES, true) ? $creatorId : null,
                'approved_at' => in_array($status, self::PURCHASE_RETURN_EFFECTIVE_STATUSES, true) ? now() : null,
            ];

            if (Schema::hasColumn('supplier_purchase_returns', 'branch_id')) {
                $payload['branch_id'] = $branchId;
            }

            $return = $lockedSupplier->purchaseReturns()->create($payload);

            $itemTotal = $this->persistPurchaseReturnItems($return, $lockedSupplier, $data);
            if ($itemTotal > 0 && abs((float) $return->total_amount - $itemTotal) > 0.01) {
                $return->update(['total_amount' => $itemTotal]);
            }

            $this->syncPurchaseReturnLedgerEntry($lockedSupplier, $return, $creatorId, $branchId);
            $this->processPurchaseReturnStockOut($return, $creatorId);

            return $return->fresh(['items']);
        });
    }

    /**
     * Approve a draft purchase return.
     */
    public function approvePurchaseReturn(
        SupplierPurchaseReturn $purchaseReturn,
        int $approverId,
        ?string $notes = null
    ): SupplierPurchaseReturn {
        return DB::transaction(function () use ($purchaseReturn, $approverId, $notes) {
            $lockedReturn = SupplierPurchaseReturn::query()
                ->whereKey($purchaseReturn->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedSupplier = Supplier::query()
                ->whereKey($lockedReturn->supplier_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedReturn->status, self::PURCHASE_RETURN_EFFECTIVE_STATUSES, true)) {
                $this->syncPurchaseReturnLedgerEntry($lockedSupplier, $lockedReturn, $approverId);

                return $lockedReturn->fresh(['supplier', 'creator', 'approver']);
            }

            if ($lockedReturn->status !== 'draft') {
                throw new \InvalidArgumentException('Only draft purchase returns can be approved.');
            }

            $payload = [
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ];
            if ($notes !== null && trim($notes) !== '') {
                $payload['notes'] = $this->mergeNotes((string) ($lockedReturn->notes ?? ''), $notes);
            }

            $lockedReturn->update($payload);
            $this->syncPurchaseReturnLedgerEntry($lockedSupplier, $lockedReturn, $approverId);

            return $lockedReturn->fresh(['supplier', 'creator', 'approver']);
        });
    }

    /**
     * Complete an approved purchase return.
     */
    public function completePurchaseReturn(
        SupplierPurchaseReturn $purchaseReturn,
        int $actorId,
        ?string $notes = null
    ): SupplierPurchaseReturn {
        return DB::transaction(function () use ($purchaseReturn, $actorId, $notes) {
            $lockedReturn = SupplierPurchaseReturn::query()
                ->whereKey($purchaseReturn->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedSupplier = Supplier::query()
                ->whereKey($lockedReturn->supplier_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReturn->status === 'completed') {
                $this->syncPurchaseReturnLedgerEntry($lockedSupplier, $lockedReturn, $actorId);
                $this->processPurchaseReturnStockOut($lockedReturn, $actorId);

                return $lockedReturn->fresh(['supplier', 'creator', 'approver']);
            }

            if ($lockedReturn->status === 'draft') {
                throw new \InvalidArgumentException('Draft purchase return must be approved before completion.');
            }

            if ($lockedReturn->status !== 'approved') {
                throw new \InvalidArgumentException('Only approved purchase returns can be completed.');
            }

            $payload = [
                'status' => 'completed',
                'approved_by' => $lockedReturn->approved_by ?: $actorId,
                'approved_at' => $lockedReturn->approved_at ?: now(),
            ];
            if ($notes !== null && trim($notes) !== '') {
                $payload['notes'] = $this->mergeNotes((string) ($lockedReturn->notes ?? ''), $notes);
            }

            $lockedReturn->update($payload);
            $this->syncPurchaseReturnLedgerEntry($lockedSupplier, $lockedReturn, $actorId);
            $this->processPurchaseReturnStockOut($lockedReturn, $actorId);

            return $lockedReturn->fresh(['supplier', 'creator', 'approver']);
        });
    }

    /**
     * Get supplier aging report
     */
    public function getSupplierAgingReport(): array
    {
        try {
            $suppliers = Supplier::active()->withBalance()->get();

            $aging = [
                'current' => 0,
                'overdue_1_30' => 0,
                'overdue_31_60' => 0,
                'overdue_61_90' => 0,
                'overdue_90_plus' => 0,
            ];

            foreach ($suppliers as $supplier) {
                try {
                    $supplierAging = $supplier->getAgingSummary();
                    $aging['current'] += $supplierAging['current'] ?? 0;
                    $aging['overdue_1_30'] += $supplierAging['overdue_1_30'] ?? 0;
                    $aging['overdue_31_60'] += $supplierAging['overdue_31_60'] ?? 0;
                    $aging['overdue_61_90'] += $supplierAging['overdue_61_90'] ?? 0;
                    $aging['overdue_90_plus'] += $supplierAging['overdue_90_plus'] ?? 0;
                } catch (\Exception $e) {
                    // Skip this supplier if there's an error
                    continue;
                }
            }

            return $aging;
        } catch (\Exception $e) {
            // Return empty aging data if there's an error
            return [
                'current' => 0,
                'overdue_1_30' => 0,
                'overdue_31_60' => 0,
                'overdue_61_90' => 0,
                'overdue_90_plus' => 0,
            ];
        }
    }

    /**
     * Get supplier performance metrics
     */
    public function getSupplierPerformanceMetrics(): array
    {
        try {
            $suppliers = Supplier::active()->get();

            $metrics = [
                'total_suppliers' => $suppliers->count(),
                'suppliers_with_dues' => $suppliers->filter(function ($s) {
                    try {
                        return $s->total_dues > 0;
                    } catch (\Exception $e) {
                        return false;
                    }
                })->count(),
                'suppliers_over_credit_limit' => $suppliers->filter(function ($s) {
                    try {
                        return $s->is_over_credit_limit;
                    } catch (\Exception $e) {
                        return false;
                    }
                })->count(),
                'average_performance_score' => $suppliers->count() > 0 ? $suppliers->avg(function ($s) {
                    try {
                        return $s->performance_score;
                    } catch (\Exception $e) {
                        return 0;
                    }
                }) : 0,
                'top_performers' => $suppliers->sortByDesc(function ($s) {
                    try {
                        return $s->performance_score;
                    } catch (\Exception $e) {
                        return 0;
                    }
                })->take(5),
                'under_performers' => $suppliers->sortBy(function ($s) {
                    try {
                        return $s->performance_score;
                    } catch (\Exception $e) {
                        return 100;
                    }
                })->take(5),
            ];

            return $metrics;
        } catch (\Exception $e) {
            // Return default metrics if there's an error
            return [
                'total_suppliers' => 0,
                'suppliers_with_dues' => 0,
                'suppliers_over_credit_limit' => 0,
                'average_performance_score' => 0,
                'top_performers' => collect(),
                'under_performers' => collect(),
            ];
        }
    }

    /**
     * Get supplier ledger summary
     */
    public function getSupplierLedgerSummary(Supplier $supplier, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $supplier->ledger();

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        $entries = $query->get();
        $periodBalance = (float) $entries->sum(function ($entry) {
            return ((float) $entry->debit) - ((float) $entry->credit);
        });

        return [
            'opening_balance' => $entries->where('transaction_type', 'opening_balance')->sum('debit')
                - $entries->where('transaction_type', 'opening_balance')->sum('credit'),
            'total_purchases' => $entries->where('transaction_type', 'purchase')->sum('debit'),
            'total_payments' => $entries->where('transaction_type', 'payment')->sum('credit'),
            'total_returns' => $entries->where('transaction_type', 'purchase_return')->sum('credit'),
            'net_balance' => ($startDate && $endDate) ? $periodBalance : $supplier->current_balance,
            'transaction_count' => $entries->count(),
        ];
    }

    /**
     * Recalculate supplier balance (for data integrity)
     */
    public function recalculateBalance(Supplier $supplier): void
    {
        $entries = $supplier->ledger()
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $runningBalance = 0;

        foreach ($entries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            $entry->update(['running_balance' => $runningBalance]);
        }
    }

    private function requireCreatorId(array $data, string $action): int
    {
        $creatorId = isset($data['created_by']) ? (int) $data['created_by'] : 0;
        if ($creatorId <= 0) {
            throw new \RuntimeException("Unable to {$action}: no valid user context.");
        }

        return $creatorId;
    }

    private function syncPurchaseReturnLedgerEntry(
        Supplier $supplier,
        SupplierPurchaseReturn $purchaseReturn,
        int $actorId,
        ?int $branchId = null
    ): void {
        if (! in_array($purchaseReturn->status, self::PURCHASE_RETURN_EFFECTIVE_STATUSES, true)) {
            return;
        }

        $entryExists = $supplier->ledger()
            ->where('transaction_type', self::PURCHASE_RETURN_LEDGER_TYPE)
            ->where('reference_type', self::PURCHASE_RETURN_REFERENCE_TYPE)
            ->where('reference_id', $purchaseReturn->id)
            ->exists();

        if (! $entryExists) {
            $supplier->addLedgerEntry(self::PURCHASE_RETURN_LEDGER_TYPE, 0, (float) $purchaseReturn->total_amount, [
                'branch_id' => $branchId
                    ?: (int) ($purchaseReturn->branch_id ?? 0)
                    ?: $this->resolveBranchId([]),
                'transaction_date' => $purchaseReturn->return_date,
                'reference_type' => self::PURCHASE_RETURN_REFERENCE_TYPE,
                'reference_id' => $purchaseReturn->id,
                'reference_number' => $purchaseReturn->return_number,
                'description' => 'Purchase Return: '.$purchaseReturn->return_reason_label,
                'created_by' => $actorId,
            ]);
        }

        $this->recalculateBalance($supplier);
    }

    private function persistPurchaseReturnItems(
        SupplierPurchaseReturn $purchaseReturn,
        Supplier $supplier,
        array $data
    ): float {
        if (! Schema::hasTable('supplier_purchase_return_items')) {
            return 0.0;
        }

        $items = collect($data['items'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values();

        if ($items->isEmpty()) {
            return 0.0;
        }

        $existing = $purchaseReturn->items()->get();
        if ($existing->isNotEmpty()) {
            $existing->each->delete();
        }

        $declaredOriginalPurchaseId = (int) ($data['original_purchase_id'] ?? 0);
        $total = 0.0;

        foreach ($items as $row) {
            $purchaseItemId = (int) ($row['purchase_item_id'] ?? 0);
            $warehouseId = (int) ($row['warehouse_id'] ?? 0);
            $productVariantId = (int) ($row['product_variant_id'] ?? 0);
            $quantity = round((float) ($row['quantity'] ?? 0), 2);
            $unitCost = round((float) ($row['unit_cost'] ?? 0), 2);
            $notes = isset($row['notes']) ? trim((string) $row['notes']) : null;

            if ($quantity <= 0) {
                throw new \InvalidArgumentException('Return item quantity must be greater than zero.');
            }

            $purchaseItem = null;
            if ($purchaseItemId > 0) {
                $purchaseItem = PurchaseItem::query()
                    ->with('purchaseOrder')
                    ->whereKey($purchaseItemId)
                    ->lockForUpdate()
                    ->first();

                if (! $purchaseItem || ! $purchaseItem->purchaseOrder) {
                    throw new \InvalidArgumentException('Selected purchase item is invalid.');
                }

                if ((int) $purchaseItem->purchaseOrder->supplier_id !== (int) $supplier->id) {
                    throw new \InvalidArgumentException('Selected purchase item does not belong to this supplier.');
                }

                if (
                    $declaredOriginalPurchaseId > 0
                    && (int) $purchaseItem->purchase_order_id !== $declaredOriginalPurchaseId
                ) {
                    throw new \InvalidArgumentException('Return items must belong to the selected original purchase.');
                }

                $productVariantId = (int) $purchaseItem->product_variant_id;
                $warehouseId = $warehouseId > 0
                    ? $warehouseId
                    : (int) ($purchaseItem->purchaseOrder->warehouse_id ?? 0);
                if ($unitCost <= 0) {
                    $unitCost = round((float) ($purchaseItem->unit_cost ?? 0), 2);
                }

                $alreadyReturned = (float) SupplierPurchaseReturnItem::query()
                    ->where('purchase_item_id', $purchaseItem->id)
                    ->whereHas('purchaseReturn', function ($query) {
                        $query->whereIn('status', self::PURCHASE_RETURN_EFFECTIVE_STATUSES);
                    })
                    ->sum('quantity');

                $maxReturnable = max(0.0, round((float) $purchaseItem->quantity_received - $alreadyReturned, 2));
                if ($quantity > $maxReturnable + 0.01) {
                    throw new \InvalidArgumentException(
                        "Return quantity exceeds received quantity for purchase item #{$purchaseItem->id}."
                    );
                }
            }

            if ($productVariantId <= 0) {
                throw new \InvalidArgumentException('Product variant is required for return item.');
            }

            if ($warehouseId <= 0) {
                throw new \InvalidArgumentException('Warehouse is required for return item.');
            }

            $lineTotal = round($quantity * max(0, $unitCost), 2);

            $purchaseReturn->items()->create([
                'purchase_item_id' => $purchaseItem?->id,
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'unit_cost' => max(0, $unitCost),
                'line_total' => $lineTotal,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            $total += $lineTotal;
        }

        return round($total, 2);
    }

    private function processPurchaseReturnStockOut(
        SupplierPurchaseReturn $purchaseReturn,
        int $actorId
    ): void {
        if ($purchaseReturn->status !== 'completed') {
            return;
        }

        if (! Schema::hasTable('supplier_purchase_return_items')) {
            return;
        }

        if (! Schema::hasColumn('supplier_purchase_returns', 'stock_processed_at')) {
            return;
        }

        if ($purchaseReturn->stock_processed_at !== null) {
            return;
        }

        $items = $purchaseReturn->items()
            ->with('purchaseItem.purchaseOrder')
            ->lockForUpdate()
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $variantStockService = app(VariantStockService::class);

        foreach ($items as $item) {
            $warehouseId = (int) ($item->warehouse_id
                ?? ($item->purchaseItem?->purchaseOrder?->warehouse_id ?? 0));
            if ($warehouseId <= 0) {
                throw new \RuntimeException("Unable to resolve warehouse for supplier return item #{$item->id}.");
            }

            $variantStockService->processSupplierReturn(
                $warehouseId,
                (int) $item->product_variant_id,
                (float) $item->quantity,
                (int) $purchaseReturn->id,
                'Supplier return #'.$purchaseReturn->return_number,
                $actorId
            );
        }

        $purchaseReturn->forceFill(['stock_processed_at' => now()])->save();
    }

    private function mergeNotes(string $existingNotes, string $newNotes): string
    {
        $existing = trim($existingNotes);
        $incoming = trim($newNotes);
        if ($incoming === '') {
            return $existing;
        }
        if ($existing === '') {
            return $incoming;
        }

        return $existing.PHP_EOL.$incoming;
    }

    private function resolveBranchId(array $data): int
    {
        $branchId = (int) ($data['branch_id'] ?? 0);
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

    private function resolvePurchaseReturnBranchId(array $data): int
    {
        $branchId = (int) ($data['branch_id'] ?? 0);
        if ($branchId > 0) {
            return $branchId;
        }

        $originalPurchaseId = (int) ($data['original_purchase_id'] ?? 0);
        if ($originalPurchaseId > 0 && Schema::hasTable('purchase_orders')) {
            $purchaseOrderBranchId = (int) (DB::table('purchase_orders')
                ->where('id', $originalPurchaseId)
                ->value('branch_id') ?? 0);
            if ($purchaseOrderBranchId > 0) {
                return $purchaseOrderBranchId;
            }
        }

        if (Schema::hasTable('warehouses')) {
            foreach ((array) ($data['items'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $warehouseId = (int) ($row['warehouse_id'] ?? 0);
                if ($warehouseId <= 0) {
                    continue;
                }

                $warehouseBranchId = (int) (DB::table('warehouses')
                    ->where('id', $warehouseId)
                    ->value('branch_id') ?? 0);
                if ($warehouseBranchId > 0) {
                    return $warehouseBranchId;
                }
            }
        }

        return $this->resolveBranchId($data);
    }

    private function findExistingPurchaseLedgerEntry(Supplier $supplier, array $data): ?SupplierLedger
    {
        $referenceType = trim((string) ($data['reference_type'] ?? ''));
        $referenceId = isset($data['purchase_id']) ? (int) $data['purchase_id'] : 0;
        $referenceNumber = trim((string) ($data['purchase_number'] ?? ''));

        if ($referenceType === '' || ($referenceId <= 0 && $referenceNumber === '')) {
            return null;
        }

        $query = SupplierLedger::query()
            ->where('supplier_id', (int) $supplier->id)
            ->where('transaction_type', self::PURCHASE_LEDGER_TYPE)
            ->where('reference_type', $referenceType);

        if ($referenceId > 0) {
            $query->where('reference_id', $referenceId);
        } else {
            $query->whereNull('reference_id')
                ->where('reference_number', $referenceNumber);
        }

        return $query
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }
}
