<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Accounts\AccountSetting;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentHeadMapping;
use App\Models\PurchaseOrder;
use App\Models\SupplierPayment;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class BranchAccountingService
{
    /**
     * @var array<string,int>
     */
    private array $accountIdCache = [];

    private ?AccountSetting $settings = null;

    public function postPurchaseEntry(PurchaseOrder $purchaseOrder): ?JournalEntry
    {
        $amount = round((float) ($purchaseOrder->ledger_posted_amount ?? 0), 2);
        if ($amount <= 0) {
            $amount = round((float) $purchaseOrder->purchaseItems()
                ->selectRaw('COALESCE(SUM(quantity_received * unit_cost), 0) AS received_total')
                ->value('received_total'), 2);
        }
        if ($amount <= 0) {
            $amount = round((float) $purchaseOrder->total_cost, 2);
        }

        if ($amount <= 0) {
            return null;
        }

        return $this->postBalancedEntry(
            referenceType: 'purchase_order_accrual',
            referenceId: (int) $purchaseOrder->id,
            branchId: $this->resolveBranchId((int) ($purchaseOrder->branch_id ?? 0)),
            entryDate: optional($purchaseOrder->received_at ?? $purchaseOrder->created_at)->toDateString() ?? now()->toDateString(),
            description: 'Purchase accrual for PO #'.$purchaseOrder->po_number,
            lines: [
                ['account_code' => 'inventory_asset', 'debit' => $amount, 'credit' => 0.0],
                ['account_id' => $this->supplierPayableAccountId(), 'debit' => 0.0, 'credit' => $amount],
            ]
        );
    }

    public function postOrderEntry(Order $order): ?JournalEntry
    {
        $amount = $this->resolveOrderAmount($order);
        if ($amount <= 0) {
            return null;
        }

        $discount = $this->resolveOrderDiscountAmount($order);
        $tax = $this->resolveOrderTaxAmount($order);
        $salesCredit = round(max(0, $amount + $discount - $tax), 2);
        $lines = [
            ['account_id' => $this->customerReceivableAccountId(), 'debit' => $amount, 'credit' => 0.0],
        ];

        $discountAccountId = $this->discountAllowedAccountId();
        if ($discount > 0 && $discountAccountId !== null) {
            $lines[] = ['account_id' => $discountAccountId, 'debit' => $discount, 'credit' => 0.0];
        } elseif ($discount > 0) {
            $salesCredit = $amount;
            $tax = 0.0;
        }

        $lines[] = ['account_id' => $this->salesRevenueAccountId(), 'debit' => 0.0, 'credit' => $salesCredit];

        $vatAccountId = $this->vatPayableAccountId();
        if ($tax > 0 && $vatAccountId !== null) {
            $lines[] = ['account_id' => $vatAccountId, 'debit' => 0.0, 'credit' => $tax];
        }

        return $this->postBalancedEntry(
            referenceType: 'sales_order_invoice',
            referenceId: (int) $order->id,
            branchId: $this->resolveBranchId((int) ($order->branch_id ?? 0)),
            entryDate: optional($order->created_at)->toDateString() ?? now()->toDateString(),
            description: 'Sales invoice for order #'.$order->invoice_id,
            lines: $lines
        );
    }

    public function postExpenseEntry(Expense $expense): ?JournalEntry
    {
        $amount = round((float) $expense->total_amount, 2);
        if ($amount <= 0) {
            return null;
        }

        return $this->postBalancedEntry(
            referenceType: 'expense_accrual',
            referenceId: (int) $expense->id,
            branchId: $this->resolveBranchId((int) ($expense->branch_id ?? 0)),
            entryDate: $expense->expense_date?->toDateString() ?? now()->toDateString(),
            description: 'Expense accrual #'.$expense->expense_number,
            lines: [
                ['account_code' => 'operating_expense', 'debit' => $amount, 'credit' => 0.0],
                ['account_code' => 'expense_payable', 'debit' => 0.0, 'credit' => $amount],
            ]
        );
    }

    public function postExpenseSettlement(Expense $expense): ?JournalEntry
    {
        $amount = round((float) $expense->total_amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $creditAccount = $this->resolveCashAccountCode($expense->payment_method ?? null, null);

        return $this->postBalancedEntry(
            referenceType: 'expense_payment',
            referenceId: (int) $expense->id,
            branchId: $this->resolveBranchId((int) ($expense->branch_id ?? 0)),
            entryDate: optional($expense->paid_at ?? $expense->updated_at)->toDateString() ?? now()->toDateString(),
            description: 'Expense payment #'.$expense->expense_number,
            lines: [
                ['account_code' => 'expense_payable', 'debit' => $amount, 'credit' => 0.0],
                ['account_code' => $creditAccount, 'debit' => 0.0, 'credit' => $amount],
            ]
        );
    }

    public function postCustomerPaymentEntry(Payment $payment): ?JournalEntry
    {
        $amount = $this->resolvePaymentAmount($payment);
        if ($amount <= 0) {
            return null;
        }

        $branchId = $this->resolveBranchId((int) ($payment->branch_id ?? 0));
        $debitAccountId = $this->resolvePaymentDestinationAccountId(
            PaymentHeadMapping::CONTEXT_CUSTOMER_PAYMENT,
            $payment->payment_method ?? null,
            $payment->gateway ?? null,
            $branchId
        );

        return $this->postBalancedEntry(
            referenceType: 'customer_receipt',
            referenceId: (int) $payment->id,
            branchId: $branchId,
            entryDate: optional($payment->created_at)->toDateString() ?? now()->toDateString(),
            description: 'Customer payment receipt #'.$payment->id,
            lines: [
                ['account_id' => $debitAccountId, 'debit' => $amount, 'credit' => 0.0],
                ['account_id' => $this->customerReceivableAccountId(), 'debit' => 0.0, 'credit' => $amount],
            ]
        );
    }

    public function postSupplierPaymentFromPayment(Payment $payment): ?JournalEntry
    {
        $amount = $this->resolvePaymentAmount($payment);
        if ($amount <= 0) {
            return null;
        }

        $branchId = $this->resolveBranchId((int) ($payment->branch_id ?? 0));
        $creditAccountId = $this->resolvePaymentDestinationAccountId(
            PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            $payment->payment_method ?? null,
            $payment->gateway ?? null,
            $branchId
        );

        return $this->postBalancedEntry(
            referenceType: 'supplier_payment_from_payment',
            referenceId: (int) $payment->id,
            branchId: $branchId,
            entryDate: optional($payment->created_at)->toDateString() ?? now()->toDateString(),
            description: 'Supplier payment (payments table) #'.$payment->id,
            lines: [
                ['account_id' => $this->supplierPayableAccountId(), 'debit' => $amount, 'credit' => 0.0],
                ['account_id' => $creditAccountId, 'debit' => 0.0, 'credit' => $amount],
            ]
        );
    }

    public function postSupplierPaymentEntry(SupplierPayment $payment): ?JournalEntry
    {
        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $branchId = $this->resolveBranchId((int) ($payment->branch_id ?? 0));
        $creditAccountId = $this->resolveSupplierPaymentAccountId($payment, $branchId);

        return $this->postBalancedEntry(
            referenceType: 'supplier_payment',
            referenceId: (int) $payment->id,
            branchId: $branchId,
            entryDate: $payment->payment_date?->toDateString() ?? now()->toDateString(),
            description: 'Supplier payment #'.$payment->payment_number,
            lines: [
                ['account_id' => $this->supplierPayableAccountId(), 'debit' => $amount, 'credit' => 0.0],
                ['account_id' => $creditAccountId, 'debit' => 0.0, 'credit' => $amount],
            ]
        );
    }

    public function supplierPayables(?int $branchId = null): Collection
    {
        return $this->supplierPayablesQuery($branchId)->get();
    }

    public function supplierPayablesQuery(?int $branchId = null): Builder
    {
        return DB::table('supplier_ledgers')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'supplier_ledgers.supplier_id')
            ->leftJoin('branches', 'branches.id', '=', 'supplier_ledgers.branch_id')
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('supplier_ledgers.branch_id', $branchId);
            })
            ->groupBy(
                'supplier_ledgers.branch_id',
                'supplier_ledgers.supplier_id',
                'branches.code',
                'branches.name',
                'suppliers.supplier_code',
                'suppliers.name'
            )
            ->selectRaw('supplier_ledgers.branch_id')
            ->selectRaw('supplier_ledgers.supplier_id')
            ->selectRaw('COALESCE(branches.code, \'\') as branch_code')
            ->selectRaw('COALESCE(branches.name, \'\') as branch_name')
            ->selectRaw('COALESCE(suppliers.supplier_code, \'\') as supplier_code')
            ->selectRaw('COALESCE(suppliers.name, \'\') as supplier_name')
            ->selectRaw('COALESCE(SUM(supplier_ledgers.debit), 0) as purchase_total')
            ->selectRaw('COALESCE(SUM(supplier_ledgers.credit), 0) as paid_amount')
            ->selectRaw('COALESCE(SUM(supplier_ledgers.debit - supplier_ledgers.credit), 0) as due_amount');
    }

    public function customerReceivables(?int $branchId = null): Collection
    {
        return $this->customerReceivablesQuery($branchId)->get();
    }

    public function customerReceivablesQuery(?int $branchId = null): Builder
    {
        $paymentSub = DB::table('payments')
            ->where('payments.payment_status', '=', 'paid')
            ->groupBy('payments.order_id')
            ->selectRaw('payments.order_id')
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(payments.amount_minor, 0) / 100, payments.amount, 0)), 0) as paid_amount');

        $query = DB::table('orders')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('branches', 'branches.id', '=', 'orders.branch_id')
            ->leftJoinSub($paymentSub, 'paid', function ($join) {
                $join->on('paid.order_id', '=', 'orders.id');
            });

        if (Schema::hasTable('return_orders')) {
            $refundSub = DB::table('return_orders')
                ->groupBy('return_orders.order_id')
                ->selectRaw('return_orders.order_id')
                ->selectRaw('COALESCE(SUM(return_orders.refund_amount), 0) as refund_amount');

            $query->leftJoinSub($refundSub, 'refund', function ($join) {
                $join->on('refund.order_id', '=', 'orders.id');
            });
        }

        return $query
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('orders.branch_id', $branchId);
            })
            ->selectRaw('orders.id as order_id')
            ->selectRaw('orders.branch_id')
            ->selectRaw('orders.customer_id')
            ->selectRaw('orders.invoice_id')
            ->selectRaw('COALESCE(customers.name, \'\') as customer_name')
            ->selectRaw('COALESCE(customers.phone, \'\') as customer_phone')
            ->selectRaw('COALESCE(branches.code, \'\') as branch_code')
            ->selectRaw('COALESCE(branches.name, \'\') as branch_name')
            ->selectRaw('COALESCE(NULLIF(orders.amount_minor, 0) / 100, orders.amount, 0) as order_total')
            ->selectRaw('COALESCE(paid.paid_amount, 0) as paid_amount')
            ->selectRaw(Schema::hasTable('return_orders') ? 'COALESCE(refund.refund_amount, 0) as refund_amount' : '0 as refund_amount')
            ->selectRaw(Schema::hasTable('return_orders')
                ? 'COALESCE(NULLIF(orders.amount_minor, 0) / 100, orders.amount, 0) - COALESCE(paid.paid_amount, 0) - COALESCE(refund.refund_amount, 0) as due_amount'
                : 'COALESCE(NULLIF(orders.amount_minor, 0) / 100, orders.amount, 0) - COALESCE(paid.paid_amount, 0) as due_amount');
    }

    /**
     * @param  array<int,array{account_code?:string,account_id?:int,debit:float,credit:float}>  $lines
     */
    private function postBalancedEntry(
        string $referenceType,
        int $referenceId,
        int $branchId,
        string $entryDate,
        string $description,
        array $lines
    ): ?JournalEntry {
        $existing = JournalEntry::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->first();
        if ($existing) {
            return $existing->load('items');
        }

        $totals = collect($lines)->reduce(function (array $carry, array $line): array {
            $carry['debit'] += round((float) ($line['debit'] ?? 0), 2);
            $carry['credit'] += round((float) ($line['credit'] ?? 0), 2);

            return $carry;
        }, ['debit' => 0.0, 'credit' => 0.0]);

        if (abs($totals['debit'] - $totals['credit']) > 0.01) {
            throw new InvalidArgumentException('Branch journal entry must remain balanced.');
        }

        return DB::transaction(function () use ($referenceType, $referenceId, $branchId, $entryDate, $description, $lines) {
            $entry = JournalEntry::create([
                'branch_id' => $branchId,
                'date' => $entryDate,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);

            foreach ($lines as $line) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $this->resolveLineAccountId($line),
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                ]);
            }

            return $entry->load('items');
        });
    }

    private function accountIdByCode(string $code): int
    {
        if (isset($this->accountIdCache[$code])) {
            return $this->accountIdCache[$code];
        }

        $accountId = (int) (Account::query()
            ->where('HeadCode', $code)
            ->where('Validity', 1)
            ->value('HeadId') ?? 0);

        if ($accountId <= 0) {
            throw new InvalidArgumentException("Accounting account is missing for code [{$code}].");
        }

        $this->accountIdCache[$code] = $accountId;

        return $accountId;
    }

    /**
     * @param  array{account_code?:string,account_id?:int,debit:float,credit:float}  $line
     */
    private function resolveLineAccountId(array $line): int
    {
        $accountId = (int) ($line['account_id'] ?? 0);
        if ($accountId > 0) {
            return $this->validatedAccountId($accountId);
        }

        $accountCode = trim((string) ($line['account_code'] ?? ''));
        if ($accountCode === '') {
            throw new InvalidArgumentException('Journal line account is missing.');
        }

        return $this->accountIdByCode($accountCode);
    }

    private function validatedAccountId(int $accountId): int
    {
        if ($accountId <= 0) {
            throw new InvalidArgumentException('Accounting account id is invalid.');
        }

        $exists = Account::query()
            ->where('HeadId', $accountId)
            ->where('Validity', 1)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException("Accounting account is missing for id [{$accountId}].");
        }

        return $accountId;
    }

    private function resolveBranchId(int $branchId): int
    {
        if ($branchId > 0) {
            return $branchId;
        }

        if (! Schema::hasTable('branches')) {
            return 1;
        }

        return (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id') ?? 1);
    }

    private function customerReceivableAccountId(): int
    {
        $configuredHeadId = (int) ($this->accountSettings()?->Receivable ?? 0);

        if ($configuredHeadId > 0) {
            return $this->validatedAccountId($configuredHeadId);
        }

        return $this->accountIdByCode('accounts_receivable');
    }

    private function supplierPayableAccountId(): int
    {
        $configuredHeadId = (int) ($this->accountSettings()?->Payable ?? 0);

        if ($configuredHeadId > 0) {
            return $this->validatedAccountId($configuredHeadId);
        }

        return $this->accountIdByCode('accounts_payable_suppliers');
    }

    private function salesRevenueAccountId(): int
    {
        $configuredHeadId = (int) ($this->accountSettings()?->Sales ?? 0);

        if ($configuredHeadId > 0) {
            return $this->validatedAccountId($configuredHeadId);
        }

        return $this->accountIdByCode('sales_revenue');
    }

    private function discountAllowedAccountId(): ?int
    {
        $configuredHeadId = (int) ($this->accountSettings()?->DiscountAllowed ?? 0);

        return $configuredHeadId > 0 ? $this->validatedAccountId($configuredHeadId) : null;
    }

    private function vatPayableAccountId(): ?int
    {
        $configuredHeadId = (int) ($this->accountSettings()?->VATPayable ?? 0);

        return $configuredHeadId > 0 ? $this->validatedAccountId($configuredHeadId) : null;
    }

    private function resolveCashAccountCode(?string $paymentMethod, ?string $gateway): string
    {
        $value = strtolower(trim((string) ($paymentMethod ?: $gateway)));

        if (in_array($value, ['bank_transfer', 'bank', 'card', 'online', 'bkash', 'shurjopay'], true)) {
            return 'bank_operating_account';
        }

        return 'cash_on_hand';
    }

    private function resolvePaymentDestinationAccountId(
        string $context,
        ?string $paymentMethod,
        ?string $gateway,
        int $branchId
    ): int {
        $mappedHeadId = PaymentHeadMapping::resolveAccountHeadId(
            $context,
            $paymentMethod,
            $branchId > 0 ? $branchId : null,
            $gateway
        );

        if ($mappedHeadId !== null && $mappedHeadId > 0) {
            return $this->validatedAccountId($mappedHeadId);
        }

        return $this->accountIdByCode($this->resolveCashAccountCode($paymentMethod, $gateway));
    }

    private function resolveSupplierPaymentAccountId(SupplierPayment $payment, int $branchId): int
    {
        $accountHeadId = (int) ($payment->account_head_id ?? 0);
        if ($accountHeadId > 0) {
            return $this->validatedAccountId($accountHeadId);
        }

        return $this->resolvePaymentDestinationAccountId(
            PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
            $payment->payment_method ?? null,
            null,
            $branchId
        );
    }

    private function accountSettings(): ?AccountSetting
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $this->settings = AccountSetting::current();

        return $this->settings;
    }

    private function resolveOrderAmount(Order $order): float
    {
        $amountMinor = (int) ($order->amount_minor ?? 0);
        if ($amountMinor > 0) {
            return round($amountMinor / 100, 2);
        }

        return round((float) $order->amount, 2);
    }

    private function resolveOrderDiscountAmount(Order $order): float
    {
        $discountMinor = (int) ($order->discount_minor ?? 0);
        if ($discountMinor > 0) {
            return round($discountMinor / 100, 2);
        }

        return round((float) ($order->discount ?? 0), 2);
    }

    private function resolveOrderTaxAmount(Order $order): float
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

    private function resolvePaymentAmount(Payment $payment): float
    {
        $amountMinor = (int) ($payment->amount_minor ?? 0);
        if ($amountMinor > 0) {
            return round($amountMinor / 100, 2);
        }

        return round((float) $payment->amount, 2);
    }
}
