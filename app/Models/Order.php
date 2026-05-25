<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'amount_minor' => 'integer',
        'discount_minor' => 'integer',
        'shipping_charge_minor' => 'integer',
        'purchase_pixel_fired_at' => 'datetime',
        'purchase_tracked_at' => 'datetime',
        'tracking_provider_status' => 'array',
    ];

    protected $fillable = [
        'invoice_id',
        'amount',
        'amount_minor',
        'discount',
        'discount_minor',
        'shipping_charge',
        'shipping_charge_minor',
        'customer_id',
        'warehouse_id',
        'branch_id',
        'order_status',
        'currency',
        'user_id',
        'note',
        'admin_note',
        'purchase_pixel_fired_at',
        'steadfast_consignment_id',
        'steadfast_tracking_code',
        'steadfast_status',
        'purchase_tracking_status',
        'purchase_tracked_at',
        'tracking_provider_status',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function (self $order) {
            if (! self::hasBranchColumn()) {
                unset($order->branch_id);
            } elseif ($order->isDirty('warehouse_id') && ! empty($order->warehouse_id)) {
                $order->branch_id = (int) (Warehouse::query()
                    ->whereKey((int) $order->warehouse_id)
                    ->value('branch_id')
                    ?? $order->branch_id
                    ?? 0);
            }

            if (! $order->isDirty('order_status')) {
                return;
            }
        });

        static::creating(function (self $order): void {
            if (! self::hasBranchColumn()) {
                unset($order->branch_id);

                return;
            }

            if (empty($order->branch_id) && ! empty($order->warehouse_id)) {
                $order->branch_id = (int) (Warehouse::query()
                    ->whereKey((int) $order->warehouse_id)
                    ->value('branch_id') ?? 0);
            }

            if (empty($order->branch_id)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('branches')) {
                    $order->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $order->branch_id = 1;
                }
            }
        });

        static::created(function (self $order): void {
            if (! self::hasBranchColumn()) {
                return;
            }

            try {
                app(\App\Services\BranchAccountingService::class)->postOrderEntry($order);
            } catch (\Throwable $exception) {
                Log::error('Failed to post branch sales journal', [
                    'order_id' => (int) $order->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }

    public function orderdetails()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(OrderDetails::class, 'id', 'order_id')->select('id', 'order_id', 'product_id');
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status');
    }

    public function shipping()
    {
        return $this->belongsTo(Shipping::class, 'id', 'order_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function returnOrders()
    {
        return $this->hasMany(ReturnOrder::class, 'order_id');
    }

    public function stateTransitions()
    {
        return $this->hasMany(OrderStateTransition::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Generate a collision-resistant public invoice id.
     */
    public static function generateInvoiceId(): string
    {
        if (Schema::hasTable('document_sequences')) {
            return app(\App\Services\DocumentNumberService::class)->next('sales_invoice', 'INV-', 0, 5);
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = 'INV-'.now()->format('YmdHis').'-'.random_int(1000, 9999);

            if (! static::where('invoice_id', $candidate)->exists()) {
                return $candidate;
            }

            usleep(10000);
        }

        return 'INV-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(2)));
    }

    private static function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $hasBranchColumn = Schema::hasColumn((new self)->getTable(), 'branch_id');
        }

        return $hasBranchColumn;
    }
}
