<?php

namespace App\Services;

use App\Models\Accounts\AccountSetting;
use App\Models\Accounts\AccountSettingItem;
use App\Models\Accounts\AccountTransaction;
use App\Models\Accounts\AccountTransactionDetail;
use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LegacyAccountingPostingService
{
    public function postSale(Order $order): ?AccountTransaction
    {
        if (! Schema::hasTable('accounts_transaction') || ! Schema::hasTable('accounts_transaction_details')) {
            return null;
        }

        $settings = AccountSetting::current();
        if (! $settings) {
            throw new RuntimeException('Accounts settings are not configured.');
        }

        $amount = $this->orderAmount($order);
        if ($amount <= 0) {
            return null;
        }

        $receivableHead = (int) ($settings->Receivable ?? 0);
        $salesHead = (int) ($settings->Sales ?? 0);
        $discountHead = (int) ($settings->DiscountAllowed ?? 0);
        $vatHead = (int) ($settings->VATPayable ?? 0);
        $discount = $this->orderDiscount($order);
        $tax = $this->orderTax($order);

        $this->requireHead($receivableHead, 'Receivable account');
        $this->requireHead($salesHead, 'Sales account');
        if ($discount > 0 && $discountHead > 0) {
            $this->requireHead($discountHead, 'Discount allowed account');
        } elseif ($discount > 0) {
            $discount = 0.0;
            $tax = 0.0;
        }
        if ($tax > 0 && $vatHead > 0) {
            $this->requireHead($vatHead, 'VAT payable account');
        } elseif ($tax > 0) {
            $tax = 0.0;
        }

        $salesCredit = round($amount + $discount - $tax, 2);

        return DB::transaction(function () use ($order, $amount, $receivableHead, $salesHead, $discountHead, $vatHead, $discount, $tax, $salesCredit) {
            $existing = AccountTransaction::query()
                ->where('ModuleName', 'sales_order_invoice')
                ->where('ModuleId', $order->id)
                ->where('Validity', 1)
                ->first();

            if ($existing) {
                return $existing->load('details');
            }

            $voucher = $this->createVoucher(
                'sales_order_invoice',
                (int) $order->id,
                $this->nextModuleTranNo('INV'),
                $order->created_at?->toDateString() ?? now()->toDateString(),
                round($amount + $discount, 2),
                'Sales invoice posting for order #'.$order->invoice_id
            );

            $narration = 'Sales invoice for order #'.$order->invoice_id;
            $lines = [
                $this->detailRow($voucher, $receivableHead, $salesHead, $amount, 0, $narration, null, $order->customer_id),
                $this->detailRow($voucher, $salesHead, $receivableHead, 0, $salesCredit, $narration, null, $order->customer_id),
            ];

            if ($discount > 0) {
                $lines[] = $this->detailRow($voucher, $discountHead, $salesHead, $discount, 0, 'Discount allowed for order #'.$order->invoice_id, null, $order->customer_id);
            }

            if ($tax > 0) {
                $lines[] = $this->detailRow($voucher, $vatHead, $receivableHead, 0, $tax, 'VAT payable for order #'.$order->invoice_id, null, $order->customer_id);
            }

            AccountTransactionDetail::query()->insert($lines);

            return $voucher->load('details');
        });
    }

    public function postSalesReturn(ReturnOrder $returnOrder): ?AccountTransaction
    {
        if (! Schema::hasTable('accounts_transaction') || ! Schema::hasTable('accounts_transaction_details')) {
            return null;
        }

        $settings = AccountSetting::current();
        if (! $settings) {
            throw new RuntimeException('Accounts settings are not configured.');
        }

        $returnOrder->loadMissing('returnItems');

        $returnValue = $this->sumReturnValue($returnOrder);
        $returnedCost = $this->sumReturnedCost($returnOrder);

        if ($returnValue <= 0 && $returnedCost <= 0) {
            return null;
        }

        $salesReturnHead = $this->resolveSalesReturnHead($returnOrder, $settings);
        $receivableHead = (int) ($settings->Receivable ?? 0);
        $refundHead = $this->resolveRefundCreditHead($returnOrder, $settings);
        $inventoryHead = $this->resolveInventoryHead($returnOrder, $settings);
        $cogsHead = $this->resolveCogsHead($returnOrder, $settings);
        $refundAmount = min($returnValue, max(0, (float) $returnOrder->refund_amount));
        $receivableReduction = round(max(0, $returnValue - $refundAmount), 2);

        $this->requireHead($salesReturnHead, 'Sales return account');
        if ($receivableReduction > 0) {
            $this->requireHead($receivableHead, 'Receivable account');
        }
        if ($refundAmount > 0) {
            $this->requireHead($refundHead, 'Refund credit account');
        }
        if ($returnedCost > 0) {
            $this->requireHead($inventoryHead, 'Inventory account');
            $this->requireHead($cogsHead, 'COGS account');
        }

        return DB::transaction(function () use (
            $returnOrder,
            $returnValue,
            $returnedCost,
            $salesReturnHead,
            $receivableHead,
            $refundHead,
            $refundAmount,
            $receivableReduction,
            $inventoryHead,
            $cogsHead
        ) {
            $existing = AccountTransaction::query()
                ->where('ModuleName', 'return_order')
                ->where('ModuleId', $returnOrder->id)
                ->where('Validity', 1)
                ->first();

            if ($existing) {
                return $existing->load('details');
            }

            $voucher = $this->createVoucher(
                'return_order',
                (int) $returnOrder->id,
                $this->nextModuleTranNo('SR'),
                $returnOrder->updated_at?->toDateString() ?? now()->toDateString(),
                round($returnValue + $returnedCost, 2),
                'Sales return posting for '.($returnOrder->return_number ?? ('#'.$returnOrder->id))
            );

            $lines = [];
            if ($receivableReduction > 0) {
                $narration = 'Sales return receivable reduction for '.($returnOrder->return_number ?? ('#'.$returnOrder->id));
                $lines[] = $this->detailRow($voucher, $salesReturnHead, $receivableHead, $receivableReduction, 0, $narration, $returnOrder);
                $lines[] = $this->detailRow($voucher, $receivableHead, $salesReturnHead, 0, $receivableReduction, $narration, $returnOrder);
            }

            if ($refundAmount > 0) {
                $narration = 'Sales return refund for '.($returnOrder->return_number ?? ('#'.$returnOrder->id));
                $lines[] = $this->detailRow($voucher, $salesReturnHead, $refundHead, $refundAmount, 0, $narration, $returnOrder);
                $lines[] = $this->detailRow($voucher, $refundHead, $salesReturnHead, 0, $refundAmount, $narration, $returnOrder);
            }

            if ($returnedCost > 0) {
                $narration = 'Sales return inventory cost reversal for '.($returnOrder->return_number ?? ('#'.$returnOrder->id));
                $lines[] = $this->detailRow($voucher, $inventoryHead, $cogsHead, $returnedCost, 0, $narration, $returnOrder);
                $lines[] = $this->detailRow($voucher, $cogsHead, $inventoryHead, 0, $returnedCost, $narration, $returnOrder);
            }

            AccountTransactionDetail::query()->insert($lines);

            return $voucher->load('details');
        });
    }

    private function sumReturnValue(ReturnOrder $returnOrder): float
    {
        return round((float) $returnOrder->returnItems->sum(function (ReturnItem $item) {
            return (float) $item->return_quantity * (float) $item->unit_price;
        }), 2);
    }

    private function sumReturnedCost(ReturnOrder $returnOrder): float
    {
        return round((float) $returnOrder->returnItems->sum(function (ReturnItem $item) {
            return (float) $item->restock_quantity * (float) $item->unit_cost;
        }), 2);
    }

    private function resolveSalesReturnHead(ReturnOrder $returnOrder, AccountSetting $settings): int
    {
        $itemType = $this->firstItemType($returnOrder);
        $itemSetting = $itemType
            ? AccountSettingItem::query()->valid()->where('ItemType', $itemType)->first()
            : null;

        return (int) ($itemSetting->SalesReturn ?? $settings->SalesReturn ?? $settings->Sales ?? 0);
    }

    private function resolveInventoryHead(ReturnOrder $returnOrder, AccountSetting $settings): int
    {
        $itemType = $this->firstItemType($returnOrder);
        $itemSetting = $itemType
            ? AccountSettingItem::query()->valid()->where('ItemType', $itemType)->first()
            : null;

        return (int) ($itemSetting->Inventory ?? $settings->Inventory ?? 0);
    }

    private function resolveCogsHead(ReturnOrder $returnOrder, AccountSetting $settings): int
    {
        $itemType = $this->firstItemType($returnOrder);
        $itemSetting = $itemType
            ? AccountSettingItem::query()->valid()->where('ItemType', $itemType)->first()
            : null;

        return (int) ($itemSetting->COGS ?? $settings->COGS ?? 0);
    }

    private function resolveRefundCreditHead(ReturnOrder $returnOrder, AccountSetting $settings): int
    {
        return match (strtolower((string) $returnOrder->refund_method)) {
            'cash' => (int) ($settings->Cash ?? 0),
            'bank' => (int) ($settings->Bank ?? 0),
            'credit', 'voucher' => (int) ($settings->CashAdvance ?? $settings->Payable ?? 0),
            default => (int) ($settings->Payable ?? 0),
        };
    }

    private function firstItemType(ReturnOrder $returnOrder): ?string
    {
        $returnOrder->loadMissing('returnItems.product');
        $product = optional($returnOrder->returnItems->first())->product;

        return $product->item_type
            ?? $product->ItemType
            ?? $product->type
            ?? null;
    }

    private function requireHead(int $headId, string $label): void
    {
        if ($headId <= 0 || ! DB::table('accounts_head')->where('HeadId', $headId)->where('Validity', 1)->exists()) {
            throw new RuntimeException($label.' is not configured or inactive.');
        }
    }

    private function detailRow(
        AccountTransaction $voucher,
        int $tranHead,
        int $particular,
        float $debit,
        float $credit,
        string $narration,
        ?ReturnOrder $returnOrder = null,
        ?int $customerId = null
    ): array {
        $now = now()->toDateTimeString();

        return [
            'TranId' => $voucher->TranId,
            'FiscalYearId' => (int) ($voucher->FiscalYearId ?? 0),
            'ComId' => (int) ($voucher->ComId ?? 0),
            'CustId' => $customerId ?? $returnOrder?->customer_id,
            'TranParticular' => $particular,
            'TranHead' => $tranHead,
            'Narration' => $narration,
            'PartNarration' => $narration,
            'Debit' => round($debit, 2),
            'Credit' => round($credit, 2),
            'BankName' => '',
            'BranchName' => '',
            'ChequeNo' => '',
            'ChequeDate' => null,
            'CreatedBy' => auth()->user()->name ?? 'system',
            'CreatedAt' => $now,
            'Validity' => 1,
        ];
    }

    private function nextModuleTranNo(string $prefix): string
    {
        if (DB::getDriverName() !== 'mysql') {
            $max = DB::table('accounts_transaction')
                ->where('TranNo', 'like', $prefix.'-%')
                ->lockForUpdate()
                ->pluck('TranNo')
                ->map(function ($number) use ($prefix) {
                    return preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $number, $matches)
                        ? (int) $matches[1]
                        : 0;
                })
                ->max();

            return $prefix.'-'.str_pad((string) (((int) $max) + 1), 5, '0', STR_PAD_LEFT);
        }

        $max = DB::table('accounts_transaction')
            ->where('TranNo', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->max(DB::raw("CAST(SUBSTRING_INDEX(TranNo, '-', -1) AS UNSIGNED)"));

        return $prefix.'-'.str_pad((string) (((int) $max) + 1), 5, '0', STR_PAD_LEFT);
    }

    private function defaultCompanyId(): int
    {
        return (int) (config('app.company_id') ?? 0);
    }

    private function createVoucher(string $moduleName, int $moduleId, string $tranNo, string $date, float $amount, string $remarks): AccountTransaction
    {
        $now = now()->toDateTimeString();
        $voucher = new AccountTransaction([
            'FiscalYearId' => 0,
            'ComId' => $this->defaultCompanyId(),
            'TranDate' => $date,
            'TranNo' => $tranNo,
            'TranAmount' => $amount,
            'Remarks' => $remarks,
            'ModuleName' => $moduleName,
            'ModuleId' => $moduleId,
            'CreatedBy' => auth()->user()->name ?? 'system',
            'CreatedAt' => $now,
            'Validity' => 1,
        ]);

        if (Schema::hasColumn('accounts_transaction', 'ApprovalStatus')) {
            $voucher->ApprovalStatus = 'approved';
        }
        if (Schema::hasColumn('accounts_transaction', 'ApprovedBy')) {
            $voucher->ApprovedBy = auth()->id();
        }
        if (Schema::hasColumn('accounts_transaction', 'ApprovedAt')) {
            $voucher->ApprovedAt = $now;
        }
        if (Schema::hasColumn('accounts_transaction', 'PostedAt')) {
            $voucher->PostedAt = $now;
        }
        if (Schema::hasColumn('accounts_transaction', 'PostedBy')) {
            $voucher->PostedBy = auth()->id();
        }

        $voucher->save();

        return $voucher;
    }

    private function orderAmount(Order $order): float
    {
        $amountMinor = (int) ($order->amount_minor ?? 0);

        return $amountMinor > 0 ? round($amountMinor / 100, 2) : round((float) $order->amount, 2);
    }

    private function orderDiscount(Order $order): float
    {
        $discountMinor = (int) ($order->discount_minor ?? 0);

        return $discountMinor > 0 ? round($discountMinor / 100, 2) : round((float) ($order->discount ?? 0), 2);
    }

    private function orderTax(Order $order): float
    {
        foreach (['vat_minor', 'tax_minor'] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                $value = (int) ($order->{$column} ?? 0);
                if ($value > 0) {
                    return round($value / 100, 2);
                }
            }
        }

        foreach (['vat', 'tax'] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                $value = (float) ($order->{$column} ?? 0);
                if ($value > 0) {
                    return round($value, 2);
                }
            }
        }

        return 0.0;
    }
}
