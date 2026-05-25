<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitLossReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_type',
        'report_date',
        'period_start',
        'period_end',
        'product_id',
        'warehouse_id',
        'category_id',
        'sales_revenue',
        'cost_of_goods_sold',
        'gross_profit',
        'operating_expenses',
        'net_profit',
        'inventory_losses',
        'damage_losses',
        'expired_losses',
        'theft_losses',
        'inventory_value_fifo',
        'inventory_value_wac',
        'units_sold',
        'costing_method',
        'additional_metrics',
        'generated_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'sales_revenue' => 'decimal:2',
        'cost_of_goods_sold' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'operating_expenses' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'inventory_losses' => 'decimal:2',
        'damage_losses' => 'decimal:2',
        'expired_losses' => 'decimal:2',
        'theft_losses' => 'decimal:2',
        'inventory_value_fifo' => 'decimal:2',
        'inventory_value_wac' => 'decimal:2',
        'units_sold' => 'integer',
        'additional_metrics' => 'array',
        'generated_at' => 'datetime',
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

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('report_date', $date);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // Accessors
    public function getProfitMarginAttribute(): float
    {
        return $this->sales_revenue > 0 ? ($this->net_profit / $this->sales_revenue) * 100 : 0;
    }

    public function getGrossMarginAttribute(): float
    {
        return $this->sales_revenue > 0 ? ($this->gross_profit / $this->sales_revenue) * 100 : 0;
    }

    public function getGrossMarginPercentageAttribute(): float
    {
        if (isset($this->additional_metrics['gross_margin_percentage'])) {
            return (float) $this->additional_metrics['gross_margin_percentage'];
        }

        return $this->gross_margin;
    }

    public function getNetMarginPercentageAttribute(): float
    {
        if (isset($this->additional_metrics['net_margin_percentage'])) {
            return (float) $this->additional_metrics['net_margin_percentage'];
        }

        return $this->sales_revenue > 0 ? ((float) $this->net_profit / (float) $this->sales_revenue) * 100 : 0;
    }

    public function getLossPercentageAttribute(): float
    {
        if (isset($this->additional_metrics['loss_percentage'])) {
            return (float) $this->additional_metrics['loss_percentage'];
        }

        return $this->sales_revenue > 0 ? ((float) $this->inventory_losses / (float) $this->sales_revenue) * 100 : 0;
    }

    public function getTotalLossesAttribute(): float
    {
        if ((float) $this->inventory_losses > 0) {
            return (float) $this->inventory_losses;
        }

        return (float) $this->damage_losses + (float) $this->expired_losses + (float) $this->theft_losses;
    }

    // Methods
    public static function generateDailyReport(string $date, string $costingMethod = 'fifo'): self
    {
        $report = new self;
        $report->report_type = 'daily';
        $report->report_date = $date;
        $report->period_start = $date;
        $report->period_end = $date;
        $report->costing_method = $costingMethod;

        // Calculate metrics (this would be implemented in the service)
        // For now, set defaults
        $report->sales_revenue = 0;
        $report->cost_of_goods_sold = 0;
        $report->gross_profit = 0;
        $report->operating_expenses = 0;
        $report->net_profit = 0;
        $report->inventory_losses = 0;
        $report->damage_losses = 0;
        $report->expired_losses = 0;
        $report->theft_losses = 0;
        $report->inventory_value_fifo = 0;
        $report->inventory_value_wac = 0;
        $report->units_sold = 0;
        $report->generated_at = now();

        return $report;
    }

    public static function generateMonthlyReport(int $year, int $month, string $costingMethod = 'fifo'): self
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $report = new self;
        $report->report_type = 'monthly';
        $report->report_date = $endDate->toDateString();
        $report->period_start = $startDate->toDateString();
        $report->period_end = $endDate->toDateString();
        $report->costing_method = $costingMethod;

        // Calculate metrics (this would be implemented in the service)
        $report->generated_at = now();

        return $report;
    }
}
