<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\ProfitLossEntry;
use App\Models\ReturnItem;
use App\Models\ReturnLog;
use App\Models\ReturnOrder;
use App\Models\ReturnReason;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    private const RESTOCKABLE_CONDITIONS = ['new', 'opened'];

    private const DAMAGED_CONDITIONS = ['damaged', 'defective', 'expired'];

    private const ACCOUNT_SALES_RETURNS = 'sales_returns_contra_revenue';

    private const ACCOUNT_REVENUE_CLEARING = 'accounts_receivable_clearing';

    private const ACCOUNT_CASH_ON_HAND = 'cash_on_hand';

    private const ACCOUNT_BANK_ACCOUNT = 'bank_operating_account';

    private const ACCOUNT_CUSTOMER_CREDIT = 'customer_credit_wallet';

    private const ACCOUNT_VOUCHER_LIABILITY = 'voucher_liability';

    private const ACCOUNT_REFUNDS_PAYABLE = 'refunds_payable';

    public function __construct(
        protected LedgerService $ledgerService,
        protected ?LegacyAccountingPostingService $legacyAccountingPostingService = null
    ) {}

    /**
     * Create a return order from an existing order.
     */
    public function createReturnOrder(array $data, User $user): ReturnOrder
    {
        return DB::transaction(function () use ($data, $user) {
            $order = Order::findOrFail($data['order_id']);
            $this->validateOrderForReturn($order);
            $returnReason = ReturnReason::find($data['return_reason_id']);
            if (! $returnReason || ! $returnReason->active) {
                throw new \InvalidArgumentException('Selected return reason is not active.');
            }
            $returnItems = collect($data['return_items'] ?? [])->values();

            if ($returnItems->isEmpty()) {
                throw new \InvalidArgumentException('At least one return item is required');
            }

            $duplicateOrderDetailIds = $returnItems
                ->pluck('order_detail_id')
                ->filter()
                ->duplicates();
            if ($duplicateOrderDetailIds->isNotEmpty()) {
                throw new \InvalidArgumentException('Duplicate order detail items are not allowed in a single return');
            }

            $refundMethod = $data['refund_method'] ?? 'none';
            $allowedRefundMethods = ['cash', 'bank', 'credit', 'voucher', 'none'];
            if (! in_array($refundMethod, $allowedRefundMethods, true)) {
                $refundMethod = 'none';
            }
            if (! (bool) $returnReason->refund_eligible) {
                $refundMethod = 'none';
            }

            $calculatedReturnType = $this->determineReturnType($order, $returnItems);
            $requestedReturnType = strtolower((string) ($data['return_type'] ?? $calculatedReturnType));
            if ($requestedReturnType === 'full' && $calculatedReturnType !== 'full') {
                throw new \InvalidArgumentException('Full return must include all returnable quantities.');
            }
            $returnType = $calculatedReturnType;

            $restockFlag = (bool) ($data['restock_flag'] ?? true) && (bool) $returnReason->auto_restock;
            $damageFlag = (bool) ($data['damage_flag'] ?? false);

            $returnOrder = ReturnOrder::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'return_status' => 'pending',
                'return_source' => $data['return_source'] ?? 'customer',
                'return_type' => $returnType,
                'return_reason_id' => $returnReason->id,
                'refund_method' => $refundMethod,
                'restock_flag' => $restockFlag,
                'damage_flag' => $damageFlag,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($returnItems as $itemData) {
                $this->createReturnItem($returnOrder, $itemData);
            }

            $totals = $returnOrder->returnItems()
                ->selectRaw('COALESCE(SUM(return_quantity * unit_price), 0) as total_return_value')
                ->selectRaw('COALESCE(SUM(refund_amount), 0) as refund_amount')
                ->first();

            $returnOrder->update([
                'total_return_value' => (float) ($totals->total_return_value ?? 0),
                'refund_amount' => (float) ($totals->refund_amount ?? 0),
            ]);

            if (! (bool) $returnReason->requires_approval) {
                $oldStatus = $returnOrder->return_status;
                $returnOrder->update([
                    'return_status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                ReturnLog::logReturnAction(
                    $returnOrder,
                    'approved',
                    'approved',
                    'Auto-approved based on return reason policy',
                    $user,
                    $oldStatus
                );
            }

            return $returnOrder;
        });
    }

    /**
     * Create a return item.
     */
    public function createReturnItem(ReturnOrder $returnOrder, array $itemData): ReturnItem
    {
        $orderDetail = OrderDetails::lockForUpdate()->findOrFail($itemData['order_detail_id']);
        $returnQuantity = (float) ($itemData['return_quantity'] ?? 0);

        if ($orderDetail->order_id != $returnOrder->order_id) {
            throw new \InvalidArgumentException('Return item does not belong to the selected order');
        }

        if ($returnQuantity <= 0) {
            throw new \InvalidArgumentException('Return quantity must be greater than zero');
        }

        if (isset($orderDetail->return_eligible) && ! $orderDetail->return_eligible) {
            throw new \InvalidArgumentException('Selected item is not eligible for return');
        }

        if (! empty($orderDetail->return_deadline) && now()->isAfter(Carbon::parse($orderDetail->return_deadline)->endOfDay())) {
            throw new \InvalidArgumentException('Return deadline has passed for one or more selected items');
        }

        $availableQuantity = (float) $orderDetail->qty - (float) $orderDetail->returned_quantity;
        if ($returnQuantity > $availableQuantity) {
            throw new \InvalidArgumentException('Return quantity exceeds available quantity');
        }

        $warehouseId = $itemData['warehouse_id']
            ?? $orderDetail->warehouse_id
            ?? optional($returnOrder->order)->warehouse_id;

        if (! $warehouseId) {
            throw new \InvalidArgumentException('Warehouse is required for each return item');
        }
        $warehouse = Warehouse::query()
            ->whereKey((int) $warehouseId)
            ->where('is_active', true)
            ->first();
        if (! $warehouse) {
            throw new \InvalidArgumentException('Selected warehouse is invalid or inactive');
        }

        $returnCondition = strtolower((string) ($itemData['return_condition'] ?? 'new'));
        if (! in_array($returnCondition, array_merge(self::RESTOCKABLE_CONDITIONS, self::DAMAGED_CONDITIONS), true)) {
            throw new \InvalidArgumentException('Invalid return condition provided');
        }

        $isDamagedCondition = in_array($returnCondition, self::DAMAGED_CONDITIONS, true);
        $shouldRestock = ! $returnOrder->damage_flag
            && $returnOrder->restock_flag
            && in_array($returnCondition, self::RESTOCKABLE_CONDITIONS, true);
        $shouldMarkDamage = $returnOrder->damage_flag || $isDamagedCondition;

        return ReturnItem::create([
            'return_order_id' => $returnOrder->id,
            'order_detail_id' => $orderDetail->id,
            'product_id' => $orderDetail->product_id,
            'warehouse_id' => $warehouse->id,
            'return_quantity' => $returnQuantity,
            'unit_price' => $orderDetail->sale_price,
            'unit_cost' => $orderDetail->purchase_price,
            'return_condition' => $returnCondition,
            'restock_quantity' => $shouldRestock ? $returnQuantity : 0,
            'damage_quantity' => $shouldMarkDamage ? $returnQuantity : 0,
            'refund_amount' => $this->calculateRefundAmount(
                $returnQuantity,
                (float) $orderDetail->sale_price,
                $returnOrder,
                $returnCondition
            ),
            'notes' => $itemData['notes'] ?? null,
        ]);
    }

    /**
     * Determine return type based on selected quantities.
     */
    protected function determineReturnType(Order $order, Collection $returnItems): string
    {
        $availableQuantities = $order->orderdetails()
            ->select(['id', 'qty', 'returned_quantity', 'return_eligible', 'return_deadline'])
            ->get()
            ->mapWithKeys(function (OrderDetails $detail) {
                $eligible = ! isset($detail->return_eligible) || (bool) $detail->return_eligible;
                $hasOpenWindow = empty($detail->return_deadline)
                    || now()->lessThanOrEqualTo(Carbon::parse($detail->return_deadline)->endOfDay());
                if (! $eligible || ! $hasOpenWindow) {
                    return [];
                }

                $available = max(0, (float) $detail->qty - (float) $detail->returned_quantity);

                return [(int) $detail->id => $available];
            })
            ->filter(fn (float $available) => $available > 0);

        if ($availableQuantities->isEmpty()) {
            throw new \InvalidArgumentException('No returnable items available for this order');
        }

        $requestedQuantities = $returnItems->mapWithKeys(function (array $item) {
            return [(int) ($item['order_detail_id'] ?? 0) => (float) ($item['return_quantity'] ?? 0)];
        });

        foreach ($requestedQuantities as $detailId => $quantity) {
            if (! $availableQuantities->has($detailId) || $quantity <= 0) {
                throw new \InvalidArgumentException('Invalid return item selection detected');
            }
        }

        $isFullReturn = $availableQuantities->every(function (float $availableQty, int $detailId) use ($requestedQuantities) {
            $requestedQty = (float) ($requestedQuantities->get($detailId) ?? 0);

            return $requestedQty >= $availableQty;
        });

        return $isFullReturn ? 'full' : 'partial';
    }

    /**
     * Update editable return metadata and recalculate dependent values.
     */
    public function updateReturnOrder(ReturnOrder $returnOrder, array $data): ReturnOrder
    {
        return DB::transaction(function () use ($returnOrder, $data) {
            $lockedReturn = ReturnOrder::query()
                ->whereKey($returnOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedReturn->return_status, ['draft', 'pending'], true)) {
                throw new \InvalidArgumentException('Cannot update return in current status');
            }

            $returnReason = ReturnReason::query()
                ->whereKey((int) $data['return_reason_id'])
                ->where('active', true)
                ->first();

            if (! $returnReason) {
                throw new \InvalidArgumentException('Selected return reason is invalid or inactive');
            }

            $restockFlag = (bool) ($data['restock_flag'] ?? false) && (bool) $returnReason->auto_restock;
            $damageFlag = (bool) ($data['damage_flag'] ?? false);

            $lockedReturn->update([
                'return_reason_id' => $returnReason->id,
                'restock_flag' => $restockFlag,
                'damage_flag' => $damageFlag,
                'refund_method' => $returnReason->refund_eligible ? ($lockedReturn->refund_method ?? 'none') : 'none',
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedReturn->unsetRelation('returnReason');
            $lockedReturn->load(['returnReason', 'returnItems']);

            foreach ($lockedReturn->returnItems as $item) {
                $itemCondition = (string) $item->return_condition;
                $isDamagedCondition = in_array($itemCondition, self::DAMAGED_CONDITIONS, true);
                $shouldRestock = ! $lockedReturn->damage_flag
                    && $lockedReturn->restock_flag
                    && in_array($itemCondition, self::RESTOCKABLE_CONDITIONS, true);
                $shouldMarkDamage = $lockedReturn->damage_flag || $isDamagedCondition;

                $item->update([
                    'restock_quantity' => $shouldRestock ? (float) $item->return_quantity : 0,
                    'damage_quantity' => $shouldMarkDamage ? (float) $item->return_quantity : 0,
                    'refund_amount' => $this->calculateRefundAmount(
                        (float) $item->return_quantity,
                        (float) $item->unit_price,
                        $lockedReturn,
                        $itemCondition
                    ),
                ]);
            }

            $totals = $lockedReturn->returnItems()
                ->selectRaw('COALESCE(SUM(return_quantity * unit_price), 0) as total_return_value')
                ->selectRaw('COALESCE(SUM(refund_amount), 0) as refund_amount')
                ->first();

            $lockedReturn->update([
                'total_return_value' => (float) ($totals->total_return_value ?? 0),
                'refund_amount' => (float) ($totals->refund_amount ?? 0),
            ]);

            return $lockedReturn->fresh(['returnReason', 'returnItems']);
        });
    }

    /**
     * Validate if an order is eligible for return.
     */
    public function validateOrderForReturn(Order $order): void
    {
        if ((string) $order->order_status !== '5') {
            throw new \InvalidArgumentException('Order must be delivered to be eligible for return');
        }

        $returnableDetails = $order->orderdetails()
            ->select(['id', 'qty', 'returned_quantity', 'return_eligible', 'return_deadline'])
            ->get()
            ->filter(function (OrderDetails $detail) {
                $eligible = ! isset($detail->return_eligible) || (bool) $detail->return_eligible;
                $availableQty = (float) $detail->qty - (float) $detail->returned_quantity;

                return $eligible && $availableQty > 0;
            });

        if ($returnableDetails->isEmpty()) {
            throw new \InvalidArgumentException('No returnable items available for this order');
        }

        $hasOpenWindow = $returnableDetails->contains(function (OrderDetails $detail) {
            if (empty($detail->return_deadline)) {
                return true;
            }

            return now()->lessThanOrEqualTo(Carbon::parse($detail->return_deadline)->endOfDay());
        });

        if (! $hasOpenWindow) {
            throw new \InvalidArgumentException('Return window has expired');
        }
    }

    /**
     * Calculate refund amount for a return item.
     */
    public function calculateRefundAmount(
        float $quantity,
        float $unitPrice,
        ReturnOrder $returnOrder,
        ?string $returnCondition = null
    ): float {
        if (! $returnOrder->refund_eligible) {
            return 0;
        }

        $totalAmount = $quantity * $unitPrice;
        $normalizedCondition = $returnCondition !== null ? strtolower(trim($returnCondition)) : null;
        $isDamagedCondition = $normalizedCondition !== null
            && in_array($normalizedCondition, self::DAMAGED_CONDITIONS, true);

        if ($returnOrder->damage_flag || $isDamagedCondition) {
            $totalAmount *= 0.8;
        }

        return round($totalAmount, 2);
    }

    /**
     * Approve a return order.
     */
    public function approveReturn(ReturnOrder $returnOrder, User $approver, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($returnOrder, $approver, $notes) {
            $lockedReturn = ReturnOrder::query()
                ->whereKey($returnOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReturn->return_status === 'approved') {
                return true;
            }

            if (! in_array($lockedReturn->return_status, ['draft', 'pending'], true)) {
                throw new \InvalidArgumentException('Only draft or pending returns can be approved');
            }

            if (! $lockedReturn->canBeApprovedBy($approver)) {
                throw new \InvalidArgumentException('User is not authorized to approve this return');
            }

            return $lockedReturn->approve($approver, $notes);
        });
    }

    /**
     * Reject a return order and release reserved return quantities.
     */
    public function rejectReturn(ReturnOrder $returnOrder, User $rejector, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($returnOrder, $rejector, $notes) {
            $lockedReturn = ReturnOrder::query()
                ->whereKey($returnOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReturn->return_status === 'rejected') {
                return true;
            }

            if (in_array($lockedReturn->return_status, ['processing', 'completed', 'cancelled'], true)) {
                throw new \InvalidArgumentException('Cannot reject a return in current status');
            }

            $oldStatus = $lockedReturn->return_status;
            $this->releaseReservedQuantities($lockedReturn);

            $lockedReturn->update([
                'return_status' => 'rejected',
            ]);

            ReturnLog::logReturnAction($lockedReturn, 'rejected', 'rejected', $notes, $rejector, $oldStatus);

            return true;
        });
    }

    /**
     * Cancel a return order and release reserved return quantities.
     */
    public function cancelReturn(ReturnOrder $returnOrder, User $canceller, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($returnOrder, $canceller, $notes) {
            $lockedReturn = ReturnOrder::query()
                ->whereKey($returnOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReturn->return_status === 'cancelled') {
                return true;
            }

            if (! in_array($lockedReturn->return_status, ['draft', 'pending', 'approved'], true)) {
                throw new \InvalidArgumentException('Cannot cancel return in current status');
            }

            $oldStatus = $lockedReturn->return_status;
            $this->releaseReservedQuantities($lockedReturn);

            $lockedReturn->update([
                'return_status' => 'cancelled',
            ]);

            ReturnLog::logReturnAction($lockedReturn, 'cancelled', 'cancelled', $notes, $canceller, $oldStatus);

            return true;
        });
    }

    /**
     * Process a return order after approval.
     */
    public function processReturn(ReturnOrder $returnOrder, User $processor, array $processingData = []): bool
    {
        $refundMethod = $processingData['refund_method'] ?? null;
        if ($refundMethod !== null && ! in_array($refundMethod, ['cash', 'bank', 'credit', 'voucher', 'none'], true)) {
            throw new \InvalidArgumentException('Invalid refund method');
        }

        return DB::transaction(function () use ($returnOrder, $processor, $processingData, $refundMethod) {
            $lockedReturn = ReturnOrder::query()
                ->whereKey($returnOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedReturn->return_status, ['processing', 'completed'], true)) {
                return true;
            }

            if ($lockedReturn->return_status !== 'approved') {
                throw new \InvalidArgumentException('Only approved returns can be processed');
            }

            $this->processReturnLocked($lockedReturn, $processor, $processingData, $refundMethod);

            return true;
        });
    }

    /**
     * Execute processing steps for an approved return under row lock.
     */
    protected function processReturnLocked(
        ReturnOrder $returnOrder,
        User $processor,
        array $processingData = [],
        ?string $refundMethod = null
    ): void {
        $returnOrder->loadMissing('returnItems.product');
        if ($returnOrder->returnItems->isEmpty()) {
            throw new \InvalidArgumentException('Return has no items to process');
        }

        $returnOrder->process($processor, $processingData['notes'] ?? null);

        foreach ($returnOrder->returnItems as $item) {
            $this->processReturnItem($item, $processor);
        }

        $totalRefund = (float) $returnOrder->returnItems->sum('refund_amount');
        $totalReturnValue = (float) $returnOrder->returnItems->sum(function (ReturnItem $item) {
            return (float) $item->return_quantity * (float) $item->unit_price;
        });

        $returnOrder->update([
            'refund_amount' => $totalRefund,
            'total_return_value' => $totalReturnValue,
            'refund_method' => $returnOrder->refund_eligible
                ? ($refundMethod ?? $returnOrder->refund_method ?? 'none')
                : 'none',
        ]);
    }

    /**
     * Process individual return item.
     */
    protected function processReturnItem(ReturnItem $item, User $actor): void
    {
        if ($item->shouldBeRestocked()) {
            $this->restockItem($item, $actor);
        }

        if ($item->shouldCreateDamageEntry()) {
            $this->createDamageEntry($item, $actor);
        }
    }

    /**
     * Restock a returned item.
     */
    protected function restockItem(ReturnItem $item, User $actor): void
    {
        if ((float) $item->restock_quantity <= 0) {
            return;
        }

        $warehouseStock = WarehouseStock::where('warehouse_id', $item->warehouse_id)
            ->where('product_id', $item->product_id)
            ->where('product_variant_id', $item->orderDetail->variant_id ?? null)
            ->lockForUpdate()
            ->first();

        if (! $warehouseStock) {
            $product = $item->product;
            $warehouseStock = WarehouseStock::create([
                'warehouse_id' => $item->warehouse_id,
                'product_id' => $item->product_id,
                'sku' => $product?->sku ?? $product?->product_code ?? ('SKU-'.$item->product_id),
                'physical_quantity' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
                'reorder_point' => 0,
                'reorder_quantity' => 0,
                'average_cost' => (float) $item->unit_cost,
            ]);
        }

        $newPhysicalQuantity = (float) $warehouseStock->physical_quantity + (float) $item->restock_quantity;
        $newAvailableQuantity = max(0, $newPhysicalQuantity - (float) $warehouseStock->reserved_quantity);

        $warehouseStock->update([
            'physical_quantity' => $newPhysicalQuantity,
            'available_quantity' => $newAvailableQuantity,
            'average_cost' => (float) $item->unit_cost > 0 ? (float) $item->unit_cost : (float) $warehouseStock->average_cost,
            'last_stock_in_date' => now(),
        ]);

        StockMovement::create([
            'branch_id' => $warehouseStock->branch_id,
            'warehouse_id' => $item->warehouse_id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->orderDetail->variant_id ?? null,
            'type' => 'adjustment_in',
            'reference_type' => 'return',
            'reference_id' => $item->return_order_id,
            'quantity' => (float) $item->restock_quantity,
            'unit_cost' => (float) ($item->unit_cost ?? 0),
            'balance_after' => $newPhysicalQuantity,
            'created_by' => $actor->id,
            'notes' => 'Sales return restock',
        ]);

        ReturnLog::logItemAction($item, 'restocked', 'Item restocked to warehouse', $actor, 'processing');
    }

    /**
     * Create damage entry for damaged return.
     */
    protected function createDamageEntry(ReturnItem $item, User $actor): void
    {
        if ((float) $item->damage_quantity <= 0) {
            return;
        }

        ProfitLossEntry::create([
            'entry_number' => 'DAMAGE-'.$item->id,
            'entry_date' => now()->toDateString(),
            'entry_type' => 'damage',
            'product_id' => $item->product_id,
            'warehouse_id' => $item->warehouse_id,
            'quantity' => $item->damage_quantity,
            'unit_cost' => $item->unit_cost,
            'total_loss_amount' => $item->damage_quantity * $item->unit_cost,
            'description' => 'Return damage entry',
            'reason_details' => 'Damaged item returned',
            'status' => 'approved',
            'reported_by' => $actor->id,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        ReturnLog::logItemAction($item, 'processed', 'Damage entry created', $actor, 'processing');
    }

    /**
     * Complete a return order.
     */
    public function completeReturn(ReturnOrder $returnOrder, User $completer, array $completionData = []): bool
    {
        $refundMethod = $completionData['refund_method'] ?? null;
        if ($refundMethod !== null && ! in_array($refundMethod, ['cash', 'bank', 'credit', 'voucher', 'none'], true)) {
            throw new \InvalidArgumentException('Invalid refund method');
        }

        return DB::transaction(function () use ($returnOrder, $completer, $completionData, $refundMethod) {
            $lockedReturn = ReturnOrder::query()
                ->whereKey($returnOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReturn->return_status === 'completed') {
                return true;
            }

            if ($lockedReturn->return_status === 'approved') {
                $this->processReturnLocked($lockedReturn, $completer, $completionData, $refundMethod);
                $lockedReturn->refresh();
            }

            if ($lockedReturn->return_status !== 'processing') {
                throw new \InvalidArgumentException('Only processing returns can be completed');
            }

            if ($refundMethod !== null) {
                $resolvedRefundMethod = $lockedReturn->refund_eligible ? $refundMethod : 'none';
                $lockedReturn->update(['refund_method' => $resolvedRefundMethod]);
            }

            $this->processFinancialAdjustments($lockedReturn);
            $lockedReturn->complete($completer, $completionData['notes'] ?? null);

            if ((float) $lockedReturn->refund_amount > 0) {
                $this->processRefund($lockedReturn, $completer);
            }

            return true;
        });
    }

    /**
     * Process financial adjustments for completed return.
     */
    protected function processFinancialAdjustments(ReturnOrder $returnOrder): void
    {
        $this->adjustOrderTotals($returnOrder);
        $this->postRevenueReversalJournal($returnOrder);
        $this->legacyAccountingPostingService()->postSalesReturn($returnOrder);
        $this->updateProfitCalculations($returnOrder);
    }

    protected function legacyAccountingPostingService(): LegacyAccountingPostingService
    {
        return $this->legacyAccountingPostingService ??= app(LegacyAccountingPostingService::class);
    }

    /**
     * Adjust original order totals.
     */
    protected function adjustOrderTotals(ReturnOrder $returnOrder): void
    {
        $returnOrder->loadMissing('returnItems');
        $order = $returnOrder->order;

        if (! $order) {
            return;
        }

        $lockedOrder = Order::query()
            ->whereKey($order->id)
            ->lockForUpdate()
            ->first();

        if (! $lockedOrder) {
            return;
        }

        $totalReturnValueMajor = (float) $returnOrder->returnItems->sum(function ($item) {
            return $item->return_quantity * $item->unit_price;
        });
        $totalReturnValueMinor = Money::fromMajor($totalReturnValueMajor);

        $currentAmountMinor = (int) ($lockedOrder->amount_minor ?? 0);
        if ($currentAmountMinor <= 0) {
            $currentAmountMinor = Money::fromMajor((float) $lockedOrder->amount);
        }

        $newAmountMinor = max(0, $currentAmountMinor - $totalReturnValueMinor);
        $lockedOrder->forceFill([
            'amount_minor' => $newAmountMinor,
            'amount' => Money::toMajorInt($newAmountMinor),
        ])->save();
    }

    /**
     * Update profit/loss calculations.
     */
    protected function updateProfitCalculations(ReturnOrder $returnOrder): void
    {
        // Return effects are captured through order amount adjustments and
        // damage entries in profit_loss_entries. Aggregate P&L reports are
        // generated lazily from source data.
    }

    /**
     * Process refund for return order.
     */
    protected function processRefund(ReturnOrder $returnOrder, User $actor): void
    {
        $refundAmountMinor = Money::fromMajor((float) $returnOrder->refund_amount);
        if ($refundAmountMinor <= 0) {
            return;
        }

        $journalDescription = 'Refund disbursement for return '.($returnOrder->return_number ?? ('#'.$returnOrder->id));
        $this->ledgerService->postJournal(
            [
                [
                    'account_code' => self::ACCOUNT_REVENUE_CLEARING,
                    'direction' => 'debit',
                    'amount_minor' => $refundAmountMinor,
                    'description' => $journalDescription,
                ],
                [
                    'account_code' => $this->resolveRefundCreditAccount($returnOrder->refund_method),
                    'direction' => 'credit',
                    'amount_minor' => $refundAmountMinor,
                    'description' => $journalDescription,
                ],
            ],
            'return_order',
            (int) $returnOrder->id,
            $journalDescription,
            'BDT',
            $actor->id,
            'return_refund_disbursement',
            [
                'return_number' => $returnOrder->return_number,
                'refund_method' => $returnOrder->refund_method,
            ]
        );

        ReturnLog::logReturnAction(
            $returnOrder,
            'refunded',
            'completed',
            'Refund processed: '.number_format((float) $returnOrder->refund_amount, 2).' via '.($returnOrder->refund_method ?? 'none'),
            $actor,
            'completed'
        );
    }

    protected function postRevenueReversalJournal(ReturnOrder $returnOrder): void
    {
        $returnOrder->loadMissing('returnItems');

        $totalReturnValueMajor = (float) $returnOrder->returnItems->sum(function (ReturnItem $item) {
            return (float) $item->return_quantity * (float) $item->unit_price;
        });
        $totalReturnMinor = Money::fromMajor($totalReturnValueMajor);

        if ($totalReturnMinor <= 0) {
            return;
        }

        $description = 'Revenue reversal for return '.($returnOrder->return_number ?? ('#'.$returnOrder->id));
        $createdBy = $returnOrder->processed_by
            ?? $returnOrder->approved_by
            ?? $returnOrder->created_by;

        $this->ledgerService->postJournal(
            [
                [
                    'account_code' => self::ACCOUNT_SALES_RETURNS,
                    'direction' => 'debit',
                    'amount_minor' => $totalReturnMinor,
                    'description' => $description,
                ],
                [
                    'account_code' => self::ACCOUNT_REVENUE_CLEARING,
                    'direction' => 'credit',
                    'amount_minor' => $totalReturnMinor,
                    'description' => $description,
                ],
            ],
            'return_order',
            (int) $returnOrder->id,
            $description,
            'BDT',
            $createdBy ? (int) $createdBy : null,
            'return_revenue_reversal',
            [
                'return_number' => $returnOrder->return_number,
                'return_type' => $returnOrder->return_type,
            ]
        );
    }

    protected function resolveRefundCreditAccount(?string $refundMethod): string
    {
        return match (strtolower((string) $refundMethod)) {
            'cash' => self::ACCOUNT_CASH_ON_HAND,
            'bank' => self::ACCOUNT_BANK_ACCOUNT,
            'credit' => self::ACCOUNT_CUSTOMER_CREDIT,
            'voucher' => self::ACCOUNT_VOUCHER_LIABILITY,
            default => self::ACCOUNT_REFUNDS_PAYABLE,
        };
    }

    /**
     * Release quantities that were reserved in order_details.returned_quantity.
     */
    protected function releaseReservedQuantities(ReturnOrder $returnOrder): void
    {
        $reservedItems = $returnOrder->returnItems()
            ->select('order_detail_id', DB::raw('SUM(return_quantity) as total_return_quantity'))
            ->groupBy('order_detail_id')
            ->get();

        foreach ($reservedItems as $reservedItem) {
            $orderDetail = OrderDetails::where('id', $reservedItem->order_detail_id)
                ->lockForUpdate()
                ->first();

            if (! $orderDetail) {
                continue;
            }

            $updatedReturnedQuantity = max(
                0,
                (float) $orderDetail->returned_quantity - (float) $reservedItem->total_return_quantity
            );

            $orderDetail->update([
                'returned_quantity' => $updatedReturnedQuantity,
            ]);
        }
    }

    /**
     * Get return statistics.
     */
    public function getReturnStatistics(array $filters = []): array
    {
        $baseQuery = ReturnOrder::query();
        $this->applyReturnStatisticsFilters($baseQuery, $filters);

        $totalReturns = (int) (clone $baseQuery)->count();
        $totalReturnValue = (float) (clone $baseQuery)->sum('total_return_value');
        $totalRefundAmount = (float) (clone $baseQuery)->sum('refund_amount');

        $returnsByStatus = (clone $baseQuery)
            ->select('return_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('return_status')
            ->pluck('aggregate', 'return_status');

        $returnsByReason = (clone $baseQuery)
            ->select('return_reason_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('return_reason_id')
            ->pluck('aggregate', 'return_reason_id');

        $averageReturnValue = $totalReturns > 0 ? $totalReturnValue / $totalReturns : 0.0;

        return [
            'total_returns' => $totalReturns,
            'total_return_value' => $totalReturnValue,
            'total_refund_amount' => $totalRefundAmount,
            'returns_by_status' => $returnsByStatus,
            'returns_by_reason' => $returnsByReason,
            'average_return_value' => $averageReturnValue,
            'return_rate' => $this->calculateReturnRate($filters),
        ];
    }

    /**
     * Calculate return rate (returns vs total orders).
     */
    protected function calculateReturnRate(array $filters = []): float
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30)->toDateString();
        $endDate = $filters['end_date'] ?? now()->toDateString();

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $totalOrders = Order::query()
            ->where('order_status', 5)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $totalReturns = ReturnOrder::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return $totalOrders > 0 ? ($totalReturns / $totalOrders) * 100 : 0.0;
    }

    /**
     * Get product-wise return analysis.
     */
    public function getProductReturnAnalysis(array $filters = []): Collection
    {
        $returnedItemsQuery = ReturnItem::query()
            ->selectRaw('product_id, SUM(return_quantity) as total_returned, SUM(refund_amount) as total_refund')
            ->groupBy('product_id')
            ->when(isset($filters['start_date']), function ($query) use ($filters) {
                $query->whereHas('returnOrder', function ($q) use ($filters) {
                    $q->whereDate('created_at', '>=', $filters['start_date']);
                });
            })
            ->when(isset($filters['end_date']), function ($query) use ($filters) {
                $query->whereHas('returnOrder', function ($q) use ($filters) {
                    $q->whereDate('created_at', '<=', $filters['end_date']);
                });
            });

        $returnedItems = $returnedItemsQuery
            ->with('product')
            ->orderByDesc('total_returned')
            ->get();

        if ($returnedItems->isEmpty()) {
            return collect();
        }

        $productIds = $returnedItems->pluck('product_id')->unique()->values();

        $soldByProductQuery = OrderDetails::query()
            ->selectRaw('order_details.product_id, SUM(order_details.qty) as total_sold')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereIn('order_details.product_id', $productIds)
            ->where('orders.order_status', 5)
            ->groupBy('order_details.product_id');

        if (! empty($filters['start_date'])) {
            $soldByProductQuery->whereDate('orders.created_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $soldByProductQuery->whereDate('orders.created_at', '<=', $filters['end_date']);
        }

        $soldByProduct = $soldByProductQuery->pluck('total_sold', 'product_id');

        return $returnedItems->map(function ($item) use ($soldByProduct) {
            $productId = (int) $item->product_id;
            $totalSold = (float) ($soldByProduct[$productId] ?? 0);
            $totalReturned = (float) ($item->total_returned ?? 0);

            return [
                'product' => $item->product,
                'total_returned' => $totalReturned,
                'total_refund' => (float) ($item->total_refund ?? 0),
                'return_rate' => $totalSold > 0 ? ($totalReturned / $totalSold) * 100 : 0.0,
            ];
        });
    }

    protected function applyReturnStatisticsFilters($query, array $filters): void
    {
        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['status'])) {
            $query->where('return_status', $filters['status']);
        }
    }
}
