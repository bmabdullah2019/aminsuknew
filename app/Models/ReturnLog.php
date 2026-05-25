<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class ReturnLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_order_id',
        'return_item_id',
        'action_type',
        'old_status',
        'new_status',
        'notes',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    // Relationships
    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function returnItem(): BelongsTo
    {
        return $this->belongsTo(ReturnItem::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action_type', $action);
    }

    public function scopeByStatusChange($query, $oldStatus, $newStatus)
    {
        return $query->where('old_status', $oldStatus)->where('new_status', $newStatus);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getActionColorAttribute(): string
    {
        return match ($this->action_type) {
            'created' => 'primary',
            'approved' => 'success',
            'rejected' => 'danger',
            'processed' => 'info',
            'restocked' => 'warning',
            'refunded' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action_type) {
            'created' => 'plus-circle',
            'approved' => 'check-circle',
            'rejected' => 'x-circle',
            'processed' => 'cog',
            'restocked' => 'package',
            'refunded' => 'credit-card',
            'completed' => 'check-square',
            'cancelled' => 'x-square',
            default => 'circle',
        };
    }

    public function getStatusChangeDescriptionAttribute(): string
    {
        if ($this->old_status && $this->new_status) {
            return "Status changed from '{$this->old_status}' to '{$this->new_status}'";
        }

        return "Status set to '{$this->new_status}'";
    }

    // Methods
    public static function logReturnAction(
        ReturnOrder $returnOrder,
        string $action,
        string $newStatus,
        ?string $notes = null,
        ?User $user = null,
        ?string $oldStatus = null
    ): self {
        $resolvedOldStatus = $oldStatus ?? $returnOrder->return_status;

        return static::create([
            'return_order_id' => $returnOrder->id,
            'action_type' => $action,
            'old_status' => $resolvedOldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'performed_by' => static::resolvePerformerId($user, $returnOrder),
            'performed_at' => now(),
        ]);
    }

    public static function logItemAction(
        ReturnItem $returnItem,
        string $action,
        ?string $notes = null,
        ?User $user = null,
        ?string $status = null
    ): self {
        $statusSnapshot = $status ?? optional($returnItem->returnOrder)->return_status ?? 'processing';

        return static::create([
            'return_order_id' => $returnItem->return_order_id,
            'return_item_id' => $returnItem->id,
            'action_type' => $action,
            'old_status' => $statusSnapshot,
            'new_status' => $statusSnapshot,
            'notes' => $notes,
            'performed_by' => static::resolvePerformerId($user, $returnItem->returnOrder),
            'performed_at' => now(),
        ]);
    }

    public static function getActionTimeline(int $returnOrderId): \Illuminate\Support\Collection
    {
        return static::where('return_order_id', $returnOrderId)
            ->with(['performer'])
            ->orderBy('performed_at', 'desc')
            ->get();
    }

    protected static function resolvePerformerId(?User $user = null, ?ReturnOrder $returnOrder = null): int
    {
        if ($user?->id) {
            return (int) $user->id;
        }

        if (auth()->id()) {
            return (int) auth()->id();
        }

        if ($returnOrder?->created_by) {
            return (int) $returnOrder->created_by;
        }

        throw new RuntimeException('Cannot log return action: no user available for performed_by.');
    }
}
