<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Expense extends Model
{
    use HasFactory;

    private const AUDITABLE_LOG_FIELDS = [
        'branch_id',
        'expense_date',
        'category_id',
        'supplier_id',
        'purchase_order_id',
        'grn_id',
        'total_amount',
        'payment_method',
        'bank_name',
        'cheque_number',
        'card_number',
        'description',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    protected $fillable = [
        'expense_number',
        'branch_id',
        'expense_date',
        'category_id',
        'supplier_id',
        'purchase_order_id',
        'grn_id',
        'total_amount',
        'payment_method',
        'bank_name',
        'cheque_number',
        'card_number',
        'description',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_order_id');
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ExpenseAllocation::class);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'expense_allocations')
            ->withPivot('allocated_amount', 'percentage', 'notes')
            ->withTimestamps();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExpenseLog::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->whereHas('allocations', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        });
    }

    // Accessors
    public function getIsAllocatedAttribute(): bool
    {
        return $this->allocations()->exists();
    }

    public function getAllocatedTotalAttribute(): float
    {
        return $this->allocations()->sum('allocated_amount');
    }

    public function getIsFullyAllocatedAttribute(): bool
    {
        return abs($this->allocated_total - $this->total_amount) < 0.01;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'rejected' => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    // Methods
    public function approve(User $approver): bool
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->logActivity($approver, 'approved', 'Expense approved');

        return true;
    }

    public function reject(User $rejector, ?string $reason = null): bool
    {
        $this->update(['status' => 'rejected']);

        $description = 'Expense rejected'.($reason ? ": {$reason}" : '');
        $this->logActivity($rejector, 'rejected', $description);

        return true;
    }

    public function markAsPaid(User $user): bool
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->logActivity($user, 'paid', 'Expense marked as paid');

        return true;
    }

    public function allocateToWarehouses(array $allocations, User $user): bool
    {
        $totalAllocated = 0.0;
        $activeWarehouseIds = [];

        DB::transaction(function () use ($allocations, &$totalAllocated, &$activeWarehouseIds) {
            foreach ($allocations as $allocation) {
                $warehouseId = (int) ($allocation['warehouse_id'] ?? 0);
                if ($warehouseId <= 0) {
                    continue;
                }

                $amount = (float) ($allocation['amount'] ?? 0);
                $percentage = $this->total_amount > 0
                    ? (($amount / (float) $this->total_amount) * 100)
                    : 0;

                $this->allocations()->updateOrCreate(
                    [
                        'warehouse_id' => $warehouseId,
                    ],
                    [
                        'allocated_amount' => $amount,
                        'percentage' => $percentage,
                        'notes' => $allocation['notes'] ?? null,
                    ]
                );

                $activeWarehouseIds[] = $warehouseId;
                $totalAllocated += $amount;
            }

            if (empty($activeWarehouseIds)) {
                $this->allocations()->delete();
            } else {
                $this->allocations()
                    ->whereNotIn('warehouse_id', array_unique($activeWarehouseIds))
                    ->delete();
            }
        });

        $this->logActivity($user, 'allocated', "Expense allocated to warehouses. Total: BDT {$totalAllocated}");

        return true;
    }

    public function logActivity(User $user, string $action, string $description, ?array $oldValues = null, ?array $newValues = null): void
    {
        ExpenseLog::create([
            'expense_id' => $this->id,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (empty($expense->expense_number)) {
                $expense->expense_number = static::generateExpenseNumber();
            }

            if (! self::hasBranchColumn()) {
                unset($expense->branch_id);

                return;
            }

            if (empty($expense->branch_id) && ! empty($expense->purchase_order_id)) {
                $expense->branch_id = (int) (PurchaseOrder::query()
                    ->whereKey((int) $expense->purchase_order_id)
                    ->value('branch_id') ?? 0);
            }

            if (empty($expense->branch_id) && ! empty($expense->grn_id)) {
                $expense->branch_id = (int) (Grn::query()
                    ->leftJoin('warehouses', 'warehouses.id', '=', 'grns.warehouse_id')
                    ->where('grns.id', (int) $expense->grn_id)
                    ->value('warehouses.branch_id') ?? 0);
            }

            if (empty($expense->branch_id)) {
                if (Schema::hasTable('branches')) {
                    $expense->branch_id = (int) (Branch::query()->where('code', 'MAIN')->value('id') ?? Branch::query()->value('id'));
                } else {
                    $expense->branch_id = 1;
                }
            }
        });

        static::created(function (Expense $expense) {
            $user = auth()->user();
            if ($user instanceof User) {
                $expense->logActivity(
                    $user,
                    'created',
                    'Expense entry created'
                );
            }

            try {
                app(\App\Services\BranchAccountingService::class)->postExpenseEntry($expense);
            } catch (\Throwable $exception) {
                Log::error('Failed to post expense accrual journal', [
                    'expense_id' => (int) $expense->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        });

        static::updated(function (Expense $expense) {
            $user = auth()->user();
            if (! $user instanceof User) {
                return;
            }

            $changes = $expense->getAuditableChanges();
            if (empty($changes) || $expense->hasOnlyWorkflowStateChanges($changes)) {
                return;
            }

            $expense->logActivity(
                $user,
                'updated',
                'Expense entry updated',
                $expense->getOriginalValuesFor(array_keys($changes)),
                $changes
            );

            if ($expense->status === 'paid') {
                try {
                    app(\App\Services\BranchAccountingService::class)->postExpenseSettlement($expense);
                } catch (\Throwable $exception) {
                    Log::error('Failed to post expense settlement journal', [
                        'expense_id' => (int) $expense->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        });

        static::updating(function (Expense $expense): void {
            if (! self::hasBranchColumn()) {
                unset($expense->branch_id);

                return;
            }

            if (! $expense->isDirty('purchase_order_id') && ! $expense->isDirty('grn_id')) {
                return;
            }

            if (! empty($expense->purchase_order_id)) {
                $expense->branch_id = (int) (PurchaseOrder::query()
                    ->whereKey((int) $expense->purchase_order_id)
                    ->value('branch_id')
                    ?? $expense->branch_id
                    ?? 0);

                return;
            }

            if (! empty($expense->grn_id)) {
                $expense->branch_id = (int) (Grn::query()
                    ->leftJoin('warehouses', 'warehouses.id', '=', 'grns.warehouse_id')
                    ->where('grns.id', (int) $expense->grn_id)
                    ->value('warehouses.branch_id')
                    ?? $expense->branch_id
                    ?? 0);
            }
        });
    }

    public static function generateExpenseNumber(): string
    {
        $year = date('Y');
        $lastExpense = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastExpense ? intval(substr($lastExpense->expense_number, -4)) + 1 : 1;

        return sprintf('EXP-%s-%04d', $year, $sequence);
    }

    protected function getAuditableChanges(): array
    {
        $changes = $this->getChanges();
        unset($changes['updated_at']);

        return $this->truncateLogValues(
            array_intersect_key($changes, array_flip(self::AUDITABLE_LOG_FIELDS))
        );
    }

    protected function getOriginalValuesFor(array $keys): array
    {
        $original = [];

        foreach ($keys as $key) {
            $original[$key] = $this->getOriginal($key);
        }

        return $this->truncateLogValues($original);
    }

    protected function hasOnlyWorkflowStateChanges(array $changes): bool
    {
        $workflowFields = ['status', 'approved_by', 'approved_at', 'paid_at'];

        return empty(array_diff(array_keys($changes), $workflowFields));
    }

    protected function truncateLogValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($value) && strlen($value) > 500) {
                $values[$key] = substr($value, 0, 500).'...';
            }
        }

        return $values;
    }

    private static function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $table = (new self)->getTable();
            $hasBranchColumn = Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id');
        }

        return (bool) $hasBranchColumn;
    }
}
