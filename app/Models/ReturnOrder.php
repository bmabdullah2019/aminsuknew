<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'order_id',
        'customer_id',
        'return_status',
        'return_source',
        'return_type',
        'return_reason_id',
        'refund_amount',
        'refund_method',
        'restock_flag',
        'damage_flag',
        'total_return_value',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'total_return_value' => 'decimal:2',
        'restock_flag' => 'boolean',
        'damage_flag' => 'boolean',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function returnReason(): BelongsTo
    {
        return $this->belongsTo(ReturnReason::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function returnLogs(): HasMany
    {
        return $this->hasMany(ReturnLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('return_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('return_status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('return_status', 'completed');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match ($this->return_status) {
            'draft' => 'secondary',
            'pending' => 'warning',
            'approved' => 'info',
            'processing' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->return_status));
    }

    public function getTotalRefundAmountAttribute(): float
    {
        if (! $this->refund_eligible) {
            return 0;
        }

        return $this->returnItems->sum('refund_amount');
    }

    public function getRefundEligibleAttribute(): bool
    {
        return $this->returnReason && $this->returnReason->refund_eligible;
    }

    // Methods
    public function canBeApprovedBy(User $user): bool
    {
        if (! $user->id) {
            return false;
        }

        if ($this->created_by && (int) $this->created_by === (int) $user->id) {
            return false;
        }

        return true;
    }

    public function approve(User $approver, ?string $notes = null): bool
    {
        $oldStatus = $this->return_status;

        $this->update([
            'return_status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->logAction('approved', 'approved', $notes, $approver, $oldStatus);

        return true;
    }

    public function reject(User $rejector, ?string $notes = null): bool
    {
        $oldStatus = $this->return_status;
        $this->update(['return_status' => 'rejected']);
        $this->logAction('rejected', 'rejected', $notes, $rejector, $oldStatus);

        return true;
    }

    public function process(User $processor, ?string $notes = null): bool
    {
        $oldStatus = $this->return_status;

        $this->update([
            'return_status' => 'processing',
            'processed_by' => $processor->id,
            'processed_at' => now(),
        ]);

        $this->logAction('processed', 'processing', $notes, $processor, $oldStatus);

        return true;
    }

    public function complete(User $completer, ?string $notes = null): bool
    {
        $oldStatus = $this->return_status;
        $this->update(['return_status' => 'completed']);
        $this->logAction('completed', 'completed', $notes, $completer, $oldStatus);

        // Auto-restock: restore inventory for restockable items
        if ($this->restock_flag) {
            $this->autoRestockItems($completer);
        }

        return true;
    }

    /**
     * Automatically restock inventory for restockable return items.
     */
    protected function autoRestockItems(User $user): void
    {
        foreach ($this->returnItems as $item) {
            if (! $item->shouldBeRestocked() || (float) $item->restock_quantity <= 0) {
                continue;
            }

            $restockQty = (float) $item->restock_quantity;
            $warehouseId = $item->warehouse_id
                ?? $this->order?->warehouse_id
                ?? null;

            if (! $warehouseId || ! $item->product_id) {
                continue;
            }

            // Find or create variant-level inventory
            $variantId = $item->orderDetail?->product_variant_id;
            if ($variantId) {
                $inventory = \App\Models\Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $variantId,
                        'warehouse_id' => $warehouseId,
                    ],
                    [
                        'quantity_available' => 0,
                        'quantity_reserved' => 0,
                        'reorder_level' => 5,
                    ]
                );
                $inventory->increaseStock($restockQty, (float) $item->unit_cost);

                // Record stock movement
                \App\Models\StockMovement::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $variantId,
                    'type' => 'adjustment_in',
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => $this->id,
                    'quantity' => $restockQty,
                    'unit_cost' => (float) $item->unit_cost,
                    'balance_after' => $inventory->sellable_stock,
                    'notes' => "Auto-restock from return #{$this->return_number}",
                    'created_by' => $user->id,
                ]);
            }
        }
    }

    protected function logAction(
        string $action,
        string $newStatus,
        ?string $notes = null,
        ?User $user = null,
        ?string $oldStatus = null
    ): void {
        ReturnLog::logReturnAction(
            $this,
            $action,
            $newStatus,
            $notes,
            $user,
            $oldStatus
        );
    }

    protected static function booted(): void
    {
        static::creating(function (ReturnOrder $returnOrder) {
            if (empty($returnOrder->return_number)) {
                $returnOrder->return_number = static::generateReturnNumber();
            }
        });

        static::created(function (ReturnOrder $returnOrder) {
            $returnOrder->logAction('created', $returnOrder->return_status, 'Return order created');
        });
    }

    public static function generateReturnNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = 'RTR-'.now()->format('YmdHis').'-'.random_int(1000, 9999);

            if (! static::where('return_number', $candidate)->exists()) {
                return $candidate;
            }

            usleep(10000);
        }

        return 'RTR-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(2)));
    }
}
