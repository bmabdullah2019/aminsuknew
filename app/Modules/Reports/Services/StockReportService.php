<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Queries\StockReportQuery;
use App\Modules\Reports\ReportFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockReportService
{
    public function __construct(
        protected StockReportQuery $query
    ) {}

    public function getStockReport(ReportFilter $filter, int $perPage = 15)
    {
        $query = $this->query->warehouseWise($filter->toArray());

        if ($filter->status === 'low_stock') {
            $query->whereRaw('warehouse_stock.physical_quantity <= warehouse_stock.reorder_point');
        }

        return $query->get();
    }

    public function getStockSummary(ReportFilter $filter): array
    {
        $query = DB::table('warehouse_stock');

        if ($filter->warehouseId) {
            $query->where('warehouse_id', $filter->warehouseId);
        }

        if ($filter->productId) {
            $query->where('product_id', $filter->productId);
        }

        if ($filter->status === 'low_stock') {
            $query->whereRaw('physical_quantity <= reorder_point');
        }

        $summary = $query
            ->selectRaw('SUM(physical_quantity) as total_physical_qty')
            ->selectRaw('SUM(available_quantity) as total_available_qty')
            ->selectRaw('SUM(physical_quantity * average_cost) as total_stock_value')
            ->first();

        return [
            'total_physical_qty' => (float) ($summary->total_physical_qty ?? 0),
            'total_available_qty' => (float) ($summary->total_available_qty ?? 0),
            'total_stock_value' => (float) ($summary->total_stock_value ?? 0),
        ];
    }
}
