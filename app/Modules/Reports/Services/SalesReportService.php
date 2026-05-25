<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Queries\SalesReportQuery;
use App\Modules\Reports\ReportFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SalesReportService
{
    public function __construct(
        protected SalesReportQuery $query
    ) {}

    /**
     * Get detailed sales report with pagination.
     */
    public function getSalesReport(ReportFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query->detailedLines($filter->startDate, $filter->endDate, $filter->toArray());

        return $query->paginate($perPage);
    }

    /**
     * Get sales summary statistics.
     */
    public function getSalesSummary(ReportFilter $filter): array
    {
        $startDate = $filter->startDate;
        $endDate = $filter->endDate;
        $filters = $filter->toArray();

        $revenueMinor = $this->query->revenueMinor($startDate, $endDate, $filters);

        // Detailed summary including COGS and Profit
        $summary = $this->query->summary($startDate, $endDate, $filters);

        return [
            'gross_sales' => round($summary->total_sales_minor / 100, 2),
            'returns' => round($summary->total_returns_minor / 100, 2),
            'net_sales' => round($revenueMinor / 100, 2),
            'cogs' => round($summary->total_cogs_minor / 100, 2),
            'gross_profit' => round(($summary->total_sales_minor - $summary->total_cogs_minor - $summary->total_returns_minor) / 100, 2),
            'total_orders' => $summary->total_orders,
            'total_items' => $summary->total_items,
        ];
    }
}
