<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'order_id',
        'purchase_order_id',
        'customer_id',
        'amount',
        'amount_minor',
        'currency',
        'trx_id',
        'gateway_payment_id',
        'sender_number',
        'payment_method',
        'gateway',
        'payment_status',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            if (! self::hasBranchColumn()) {
                unset($payment->branch_id);

                return;
            }

            if (empty($payment->branch_id) && ! empty($payment->order_id)) {
                $payment->branch_id = (int) (Order::query()
                    ->whereKey((int) $payment->order_id)
                    ->value('branch_id') ?? 0);
            }

            if (empty($payment->branch_id) && ! empty($payment->purchase_order_id)) {
                $payment->branch_id = (int) (PurchaseOrder::query()
                    ->whereKey((int) $payment->purchase_order_id)
                    ->value('branch_id') ?? 0);
            }

            if (empty($payment->branch_id)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('branches')) {
                    $payment->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $payment->branch_id = 1;
                }
            }
        });

        static::created(function (self $payment): void {
            $payment->postPaymentJournalIfPaid();
            $payment->logPaymentActivity('payment_created');
        });

        static::updating(function (self $payment): void {
            if (! self::hasBranchColumn()) {
                unset($payment->branch_id);

                return;
            }

            if (! $payment->isDirty('order_id') && ! $payment->isDirty('purchase_order_id')) {
                return;
            }

            if (! empty($payment->order_id)) {
                $payment->branch_id = (int) (Order::query()
                    ->whereKey((int) $payment->order_id)
                    ->value('branch_id')
                    ?? $payment->branch_id
                    ?? 0);

                return;
            }

            if (! empty($payment->purchase_order_id)) {
                $payment->branch_id = (int) (PurchaseOrder::query()
                    ->whereKey((int) $payment->purchase_order_id)
                    ->value('branch_id')
                    ?? $payment->branch_id
                    ?? 0);
            }
        });

        static::updated(function (self $payment): void {
            $payment->postPaymentJournalIfPaid();
            $payment->logPaymentActivity('payment_updated');
        });
    }

    private static function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $hasBranchColumn = Schema::hasColumn((new self)->getTable(), 'branch_id');
        }

        return $hasBranchColumn;
    }

    private function postPaymentJournalIfPaid(): void
    {
        if (strtolower((string) $this->payment_status) !== 'paid') {
            return;
        }

        if (empty($this->order_id) && empty($this->purchase_order_id)) {
            return;
        }

        try {
            if (! empty($this->order_id)) {
                app(\App\Services\BranchAccountingService::class)->postCustomerPaymentEntry($this);

                return;
            }

            app(\App\Services\BranchAccountingService::class)->postSupplierPaymentFromPayment($this);
        } catch (\Throwable $exception) {
            Log::error('Failed to post payment journal', [
                'payment_id' => (int) $this->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function logPaymentActivity(string $event): void
    {
        if (! function_exists('activity')) {
            return;
        }

        try {
            $logger = activity('finance');
            if (auth()->check()) {
                $logger->causedBy(auth()->user());
            }

            $logger->performedOn($this)
                ->withProperties([
                    'payment_id' => (int) $this->id,
                    'order_id' => $this->order_id ? (int) $this->order_id : null,
                    'purchase_order_id' => $this->purchase_order_id ? (int) $this->purchase_order_id : null,
                    'status' => (string) ($this->payment_status ?? ''),
                    'method' => (string) ($this->payment_method ?? ''),
                    'gateway' => (string) ($this->gateway ?? ''),
                    'amount_minor' => (int) ($this->amount_minor ?? 0),
                    'currency' => (string) ($this->currency ?? 'BDT'),
                ])
                ->log($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
