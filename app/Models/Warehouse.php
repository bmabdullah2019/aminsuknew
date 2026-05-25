<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'type',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'manager_id',
        'capacity_sqft',
        'latitude',
        'longitude',
        'is_active',
        'opening_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'capacity_sqft' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'opening_date' => 'date',
    ];

    /**
     * Boot method - Generate warehouse code automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($warehouse) {
            if (empty($warehouse->code)) {
                $warehouse->code = self::generateWarehouseCode();
            }

            if (empty($warehouse->branch_id)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('branches')) {
                    $warehouse->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $warehouse->branch_id = 1;
                }
            }

            $warehouse->created_by = auth()->id();
        });

        static::updating(function ($warehouse) {
            $warehouse->updated_by = auth()->id();
        });
    }

    /**
     * Generate unique warehouse code
     */
    public static function generateWarehouseCode(): string
    {
        $lastWarehouse = self::orderBy('id', 'desc')->first();
        $number = $lastWarehouse ? intval(substr($lastWarehouse->code, 3)) + 1 : 1;

        return 'WH-'.str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    // Relationships

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'from_warehouse_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'to_warehouse_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function losses(): HasMany
    {
        return $this->hasMany(StockLoss::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function grns(): HasMany
    {
        return $this->hasMany(Grn::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function expenseAllocations(): HasMany
    {
        return $this->hasMany(ExpenseAllocation::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeMain($query)
    {
        return $query->where('type', 'main');
    }

    public function scopeBranch($query)
    {
        return $query->where('type', 'branch');
    }

    // Accessors

    public function getFullAddressAttribute(): string
    {
        return trim(implode(', ', array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->country,
            $this->postal_code,
        ])));
    }

    public function getTotalStockValueAttribute(): float
    {
        return $this->stock()->sum('total_value');
    }

    public function getLowStockCountAttribute(): int
    {
        return $this->stock()
            ->whereRaw('available_quantity <= reorder_point')
            ->count();
    }

    // Helper Methods

    public function activate(): bool
    {
        $this->is_active = true;

        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->is_active = false;

        return $this->save();
    }

    public function hasStock(int $productId): bool
    {
        return $this->stock()
            ->where('product_id', $productId)
            ->where('physical_quantity', '>', 0)
            ->exists();
    }

    public function getStockBalance(int $productId)
    {
        return $this->stock()
            ->where('product_id', $productId)
            ->first();
    }
}
