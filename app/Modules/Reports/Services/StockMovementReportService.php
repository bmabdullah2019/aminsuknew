<?php

namespace App\Modules\Reports\Services;

use App\Models\StockMovement;
use App\Modules\Reports\ReportFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementReportService
{
    /**
     * Get stock movement report with pagination.
     */
    public function getMovementReport(ReportFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = StockMovement::query()
            ->with(['product', 'warehouse', 'creator'])
            ->whereBetween('created_at', [
                $filter->startDate.' 00:00:00',
                $filter->endDate.' 23:59:59',
            ])
            ->orderBy('created_at', 'desc');

        if ($filter->warehouseId) {
            $query->where('warehouse_id', $filter->warehouseId);
        }

        if ($filter->productId) {
            $query->where('product_id', $filter->productId);
        }

        if ($filter->status) { // Used as movement type here
            $query->where('type', $filter->status);
        }

        return $query->paginate($perPage);
    }
}
