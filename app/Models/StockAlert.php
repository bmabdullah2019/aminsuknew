<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockAlert extends Model
{
    use HasFactory;

    protected static ?array $legacyAlertTypeOptions = null;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'alert_type',
        'alert_date',
        'current_quantity',
        'threshold_quantity',
        'message',
        'status',
        'severity',
        'is_resolved',
        'created_by',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'alert_date' => 'datetime',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $alert): void {
            if (empty($alert->alert_date)) {
                $alert->alert_date = now();
            }

            self::normalizeAlertTypeForLegacySchema($alert);
            self::syncResolvedFlags($alert);
        });

        static::updating(function (self $alert): void {
            if ($alert->isDirty('alert_type')) {
                self::normalizeAlertTypeForLegacySchema($alert);
            }

            self::syncResolvedFlags($alert);
        });
    }

    private static function syncResolvedFlags(self $alert): void
    {
        if (! empty($alert->status)) {
            if ($alert->status === 'resolved') {
                $alert->is_resolved = true;
                if (empty($alert->resolved_at)) {
                    $alert->resolved_at = now();
                }
            } elseif ($alert->status === 'active') {
                $alert->is_resolved = false;
                $alert->resolved_at = null;
                $alert->resolved_by = null;
            }

            return;
        }

        if ($alert->is_resolved !== null) {
            $alert->status = $alert->is_resolved ? 'resolved' : 'active';
        }
    }

    private static function normalizeAlertTypeForLegacySchema(self $alert): void
    {
        $currentType = trim((string) $alert->alert_type);
        if ($currentType === '') {
            return;
        }

        $allowedTypes = self::legacyAlertTypeOptions();
        if (empty($allowedTypes)) {
            // Modern schema uses string/varchar alert_type, no normalization needed.
            return;
        }

        if (in_array($currentType, $allowedTypes, true)) {
            return;
        }

        $aliases = [
            'expiry_warning' => 'expiring_soon',
            'expiring_stock' => 'expiring_soon',
            'overstock' => 'critical',
            'over_stock' => 'critical',
            'grn_discrepancy' => 'critical',
        ];

        $mappedType = $aliases[$currentType] ?? null;
        if ($mappedType && in_array($mappedType, $allowedTypes, true)) {
            $alert->alert_type = $mappedType;

            return;
        }

        $alert->alert_type = in_array('critical', $allowedTypes, true)
            ? 'critical'
            : (in_array('low_stock', $allowedTypes, true) ? 'low_stock' : $allowedTypes[0]);
    }

    private static function legacyAlertTypeOptions(): array
    {
        if (self::$legacyAlertTypeOptions !== null) {
            return self::$legacyAlertTypeOptions;
        }

        try {
            $column = DB::selectOne("SHOW COLUMNS FROM `stock_alerts` LIKE 'alert_type'");
            $type = (string) ($column->Type ?? '');

            if (str_starts_with(strtolower($type), 'enum(')) {
                preg_match_all("/'([^']+)'/", $type, $matches);
                self::$legacyAlertTypeOptions = $matches[1] ?? [];
            } else {
                self::$legacyAlertTypeOptions = [];
            }
        } catch (\Throwable $e) {
            self::$legacyAlertTypeOptions = [];
        }

        return self::$legacyAlertTypeOptions;
    }

    /**
     * Get the warehouse that owns the alert
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product that owns the alert
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created the alert
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who resolved the alert
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope a query to only include active alerts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include resolved alerts
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope a query to only include critical alerts
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope a query to only include warning alerts
     */
    public function scopeWarning($query)
    {
        return $query->where('severity', 'warning');
    }

    /**
     * Get alert severity color for UI
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get alert type display name
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return match ($this->alert_type) {
            'low_stock' => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
            'over_stock' => 'Overstock',
            'overstock' => 'Overstock',
            'expiry_warning' => 'Expiry Warning',
            'expiring_soon' => 'Expiry Warning',
            'grn_discrepancy' => 'GRN Discrepancy',
            default => ucfirst(str_replace('_', ' ', $this->alert_type)),
        };
    }

    /**
     * Check if alert is critical
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Check if alert is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Resolve the alert
     */
    public function resolve(?int $resolvedBy = null): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy ?? auth()->id(),
        ]);
    }
}
