<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PaymentHeadMapping extends Model
{
    public const CONTEXT_SUPPLIER_PAYMENT = 'supplier_payment';

    public const CONTEXT_CUSTOMER_PAYMENT = 'customer_payment';

    private const CONTROL_HEAD_FIELDS = [
        self::CONTEXT_SUPPLIER_PAYMENT => 'Payable',
        self::CONTEXT_CUSTOMER_PAYMENT => 'Receivable',
    ];

    protected $fillable = [
        'context',
        'payment_method',
        'branch_id',
        'account_head_id',
        'is_active',
        'is_locked',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'account_head_id' => 'integer',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function methodOptions(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'card' => 'Card',
            'online' => 'Online Payment',
        ];
    }

    public static function controlHeadFieldForContext(string $context): ?string
    {
        return self::CONTROL_HEAD_FIELDS[$context] ?? null;
    }

    public static function controlHeadLabelForContext(string $context): string
    {
        return match ($context) {
            self::CONTEXT_SUPPLIER_PAYMENT => 'Supplier Payable Control Head',
            self::CONTEXT_CUSTOMER_PAYMENT => 'Customer Receivable Control Head',
            default => 'Control Head',
        };
    }

    public static function resolveMapping(
        string $context,
        ?string $paymentMethod,
        ?int $branchId = null,
        ?string $gateway = null
    ): ?self {
        $normalizedMethod = self::normalizeMethod($paymentMethod, $gateway);
        if ($normalizedMethod === null) {
            return null;
        }

        $mappings = self::query()
            ->forContext($context)
            ->active()
            ->where('payment_method', $normalizedMethod)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id');

                if ($branchId !== null && $branchId > 0) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->get();

        return self::preferredMapping($mappings, $branchId);
    }

    public static function resolveAccountHeadId(
        string $context,
        ?string $paymentMethod,
        ?int $branchId = null,
        ?string $gateway = null
    ): ?int {
        $mapping = self::resolveMapping($context, $paymentMethod, $branchId, $gateway);

        return $mapping ? (int) $mapping->account_head_id : null;
    }

    public static function normalizeMethod(?string $paymentMethod, ?string $gateway = null): ?string
    {
        $value = strtolower(trim((string) ($paymentMethod ?: $gateway)));
        if ($value === '') {
            return null;
        }

        return match ($value) {
            'cash', 'cod', 'cash on delivery' => 'cash',
            'bank', 'bank_transfer', 'bank transfer' => 'bank_transfer',
            'cheque', 'check' => 'cheque',
            'card', 'credit_card', 'credit card', 'debit_card', 'debit card' => 'card',
            'online', 'other', 'bkash', 'nagad', 'rocket', 'shurjopay', 'sslcommerz', 'paypal' => 'online',
            default => array_key_exists($value, self::methodOptions()) ? $value : null,
        };
    }

    private static function preferredMapping(Collection $mappings, ?int $branchId): ?self
    {
        if ($branchId !== null && $branchId > 0) {
            $branchMapping = $mappings->first(
                fn (self $mapping): bool => (int) ($mapping->branch_id ?? 0) === $branchId
            );

            if ($branchMapping) {
                return $branchMapping;
            }
        }

        return $mappings->first(
            fn (self $mapping): bool => $mapping->branch_id === null
        );
    }
}
