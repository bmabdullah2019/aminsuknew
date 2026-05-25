<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OrderDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'warehouse_id',
        'branch_id',
        'product_name',
        'purchase_price',
        'purchase_price_minor',
        'sale_price',
        'sale_price_minor',
        'qty',
        'product_discount',
        'product_size',
        'product_color',
        'currency',
        'returned_quantity',
        'return_eligible',
        'return_deadline',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function image()
    {
        return $this->belongsTo(Productimage::class, 'product_id', 'product_id')->select('id', 'product_id', 'image');
    }

    public function shipping()
    {
        return $this->belongsTo(Shipping::class, 'order_id', 'order_id')->select('id', 'order_id', 'name', 'phone', 'address');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id')->select('id', 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $detail): void {
            if (! self::hasBranchColumn()) {
                unset($detail->branch_id);

                return;
            }

            if (empty($detail->branch_id) && ! empty($detail->warehouse_id)) {
                $detail->branch_id = (int) (Warehouse::query()
                    ->whereKey((int) $detail->warehouse_id)
                    ->value('branch_id') ?? 0);
            }

            if (empty($detail->branch_id) && ! empty($detail->order_id)) {
                $detail->branch_id = (int) (Order::query()
                    ->whereKey((int) $detail->order_id)
                    ->value('branch_id') ?? 0);
            }

            if (empty($detail->branch_id)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('branches')) {
                    $detail->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $detail->branch_id = 1;
                }
            }
        });

        static::updating(function (self $detail): void {
            if (! self::hasBranchColumn()) {
                unset($detail->branch_id);

                return;
            }

            if (! $detail->isDirty('warehouse_id') && ! $detail->isDirty('order_id')) {
                return;
            }

            if (! empty($detail->warehouse_id)) {
                $detail->branch_id = (int) (Warehouse::query()
                    ->whereKey((int) $detail->warehouse_id)
                    ->value('branch_id')
                    ?? $detail->branch_id
                    ?? 0);

                return;
            }

            if (! empty($detail->order_id)) {
                $detail->branch_id = (int) (Order::query()
                    ->whereKey((int) $detail->order_id)
                    ->value('branch_id')
                    ?? $detail->branch_id
                    ?? 0);
            }
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
}
