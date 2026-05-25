<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'branch_id',
        'supplier_id',
        'warehouse_id',
        'status',
        'total_cost',
        'ordered_at',
        'received_at',
        'expected_delivery_date',
        'ledger_posted_amount',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'ledger_posted_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
        'expected_delivery_date' => 'datetime',
    ];

    protected $appends = [
        'total_received_quantity',
        'total_ordered_quantity',
        'is_fully_received',
        'is_partially_received',
        'remaining_quantity',
    ];

    // Relationships

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierPurchaseReturn::class, 'original_purchase_id');
    }

    // Accessors

    public function getTotalReceivedQuantityAttribute(): float
    {
        return $this->purchaseItems->sum('quantity_received');
    }

    public function getTotalOrderedQuantityAttribute(): float
    {
        return $this->purchaseItems->sum('quantity_ordered');
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->total_received_quantity >= $this->total_ordered_quantity && $this->total_ordered_quantity > 0;
    }

    public function getIsPartiallyReceivedAttribute(): bool
    {
        return $this->total_received_quantity > 0 && $this->total_received_quantity < $this->total_ordered_quantity;
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->total_ordered_quantity - $this->total_received_quantity);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'pending' => 'warning',
            'approved' => 'info',
            'ordered' => 'primary',
            'partial_received' => 'warning',
            'received' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    // Scopes

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'received']);
    }

    // Helper Methods

    public function generatePoNumber(): string
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('document_sequences')) {
            return app(\App\Services\DocumentNumberService::class)->next('purchase_order', 'PO-'.date('Y').'-', 0, 4);
        }

        $year = date('Y');
        $lastPo = static::where('po_number', 'like', "PO-{$year}-%")
            ->orderBy('po_number', 'desc')
            ->first();

        $number = $lastPo ? intval(substr($lastPo->po_number, -4)) + 1 : 1;

        return sprintf('PO-%s-%04d', $year, $number);
    }

    public function approve(User $approver): bool
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending purchase orders can be approved');
        }

        $this->status = 'approved';
        $this->approved_by = $approver->id;

        return $this->save();
    }

    public function markAsOrdered(): bool
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Only approved purchase orders can be marked as ordered');
        }

        $this->status = 'ordered';
        $this->ordered_at = now();

        return $this->save();
    }

    public function receiveItems(array $itemsData): bool
    {
        if (! in_array($this->status, ['ordered', 'partial_received'])) {
            throw new \Exception('Purchase order must be ordered before receiving items');
        }

        DB::transaction(function () use ($itemsData) {
            foreach ($itemsData as $itemData) {
                $itemId = $itemData['purchase_item_id'] ?? $itemData['id'] ?? null;
                if (! $itemId) {
                    throw new \Exception('Purchase item ID is required while receiving items');
                }
                $item = $this->purchaseItems()->findOrFail($itemId);
                $receivedQuantity = (float) ($itemData['quantity_received'] ?? $itemData['quantity'] ?? 0);
                if ($receivedQuantity <= 0) {
                    throw new \Exception('Received quantity must be greater than zero');
                }
                $unitCost = $itemData['unit_cost'] ?? $item->unit_cost;
                $variant = $item->productVariant;
                if (! $variant) {
                    throw new \Exception('Product variant not found for purchase item #'.$item->id);
                }

                // Update purchase item
                $item->receiveQuantity($receivedQuantity, (float) $unitCost);

                // Update inventory
                $inventory = Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $item->product_variant_id,
                        'warehouse_id' => $this->warehouse_id,
                    ],
                    [
                        'branch_id' => (int) ($this->branch_id ?: (Warehouse::query()->whereKey((int) $this->warehouse_id)->value('branch_id') ?? 0)),
                        'quantity_available' => 0,
                        'quantity_reserved' => 0,
                        'reorder_level' => 5,
                    ]
                );

                $inventory->increaseStock($receivedQuantity, $unitCost);

                // Record stock movement
                StockMovement::create([
                    'branch_id' => (int) ($this->branch_id ?: (Warehouse::query()->whereKey((int) $this->warehouse_id)->value('branch_id') ?? 0)),
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'type' => 'grn',
                    'reference_type' => 'grn',
                    'reference_id' => $this->id,
                    'quantity' => $receivedQuantity,
                    'unit_cost' => $unitCost,
                    'balance_after' => $inventory->sellable_stock,
                    'notes' => "GRN for PO #{$this->po_number}",
                    'created_by' => auth()->id() ?? User::query()->value('id'),
                ]);
            }

            // Update PO status
            $this->updateStatusBasedOnReceipt();
        });

        // Dispatch event AFTER transaction commits for supplier ledger posting
        $this->refresh();
        $totalReceivedCost = $this->purchaseItems->sum(function ($item) {
            return (float) $item->quantity_received * (float) $item->unit_cost;
        });
        event(new \App\Events\PurchaseOrderReceived($this, $totalReceivedCost));

        return true;
    }

    public function updateStatusBasedOnReceipt(): void
    {
        $totalOrdered = $this->total_ordered_quantity;
        $totalReceived = $this->total_received_quantity;

        if ($totalReceived >= $totalOrdered) {
            $this->status = 'received';
        } elseif ($totalReceived > 0) {
            $this->status = 'partial_received';
        }

        $this->save();
    }

    public function calculateTotalCost(): float
    {
        return $this->purchaseItems->sum(function ($item) {
            return $item->quantity_ordered * $item->unit_cost;
        });
    }

    public function updateTotalCost(): bool
    {
        $this->total_cost = $this->calculateTotalCost();

        return $this->save();
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    public function canApprove(): bool
    {
        return $this->status === 'pending';
    }

    public function canReceive(): bool
    {
        return in_array($this->status, ['ordered', 'partial_received']);
    }

    public function canCancel(): bool
    {
        return ! in_array($this->status, ['received', 'cancelled']);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (empty($po->po_number)) {
                $po->po_number = $po->generatePoNumber();
            }

            if (! self::hasBranchColumn()) {
                unset($po->branch_id);

                return;
            }

            if (empty($po->branch_id) && ! empty($po->warehouse_id)) {
                $po->branch_id = Warehouse::query()
                    ->whereKey((int) $po->warehouse_id)
                    ->value('branch_id');
            }

            if (empty($po->branch_id)) {
                if (Schema::hasTable('branches')) {
                    $po->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $po->branch_id = 1;
                }
            }
        });

        static::created(function ($po) {
            // Update total cost after creation
            $po->updateTotalCost();
        });

        static::updating(function ($po) {
            if (! self::hasBranchColumn()) {
                unset($po->branch_id);

                return;
            }

            if ($po->isDirty('warehouse_id') && ! empty($po->warehouse_id)) {
                $po->branch_id = (int) (Warehouse::query()
                    ->whereKey((int) $po->warehouse_id)
                    ->value('branch_id')
                    ?? $po->branch_id
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
