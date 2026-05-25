<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ProfitLossEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_number',
        'entry_date',
        'entry_type',
        'product_id',
        'warehouse_id',
        'quantity',
        'unit_cost',
        'total_loss_amount',
        'description',
        'reason_details',
        'evidence_attachments',
        'status',
        'reported_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_loss_amount' => 'decimal:2',
        'evidence_attachments' => 'array',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeByType($query, $type)
    {
        return $query->where('entry_type', $type);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getEntryTypeColorAttribute(): string
    {
        return match ($this->entry_type) {
            'damage' => 'warning',
            'expired' => 'danger',
            'stolen' => 'dark',
            'theft' => 'dark',
            'other' => 'secondary',
            default => 'secondary',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    // Methods
    public function approve(User $approver): bool
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Log activity
        if (function_exists('activity')) {
            activity()
                ->performedOn($this)
                ->causedBy($approver)
                ->withProperties(['action' => 'approved'])
                ->log('Loss entry approved');
        } else {
            Log::info('Loss entry approved', ['entry_id' => $this->id, 'approver_id' => $approver->id]);
        }

        return true;
    }

    public function reject(User $rejector, ?string $reason = null): bool
    {
        $this->update(['status' => 'rejected']);

        // Log activity
        if (function_exists('activity')) {
            activity()
                ->performedOn($this)
                ->causedBy($rejector)
                ->withProperties(['action' => 'rejected', 'reason' => $reason])
                ->log('Loss entry rejected');
        } else {
            Log::info('Loss entry rejected', ['entry_id' => $this->id, 'rejector_id' => $rejector->id, 'reason' => $reason]);
        }

        return true;
    }

    protected static function booted(): void
    {
        static::creating(function (ProfitLossEntry $entry) {
            if (empty($entry->entry_number)) {
                $entry->entry_number = static::generateEntryNumber();
            }
        });

        static::created(function (ProfitLossEntry $entry) {
            if (function_exists('activity')) {
                activity()
                    ->performedOn($entry)
                    ->causedBy(auth()->user())
                    ->log('Loss entry created');
            } else {
                $user = auth()->user();
                Log::info('Loss entry created', ['entry_id' => $entry->id, 'user_id' => $user->id ?? null]);
            }
        });
    }

    public static function generateEntryNumber(): string
    {
        $year = date('Y');
        $lastEntry = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastEntry ? intval(substr($lastEntry->entry_number, -4)) + 1 : 1;

        return sprintf('PL-%s-%04d', $year, $sequence);
    }
}
