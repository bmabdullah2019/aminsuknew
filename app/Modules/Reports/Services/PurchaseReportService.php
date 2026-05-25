<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Queries\PurchaseReportQuery;
use App\Modules\Reports\ReportFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseReportService
{
    public function __construct(
        protected PurchaseReportQuery $query
    ) {}

    public function getPurchaseReport(ReportFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query->lines($filter->startDate, $filter->endDate, $filter->toArray());

        return $query
            ->orderByDesc('grns.grn_date')
            ->orderByDesc('grn_items.id')
            ->paginate($perPage);
    }

    public function getPurchaseSummary(ReportFilter $filter): array
    {
        $summary = $this->query->summary($filter->startDate, $filter->endDate, $filter->toArray());

        return [
            'total_purchase_amount' => (float) $summary->total_purchase_amount,
            'total_quantity' => (float) $summary->total_received_quantity,
            'total_orders' => (int) $summary->total_orders,
        ];
    }
}
