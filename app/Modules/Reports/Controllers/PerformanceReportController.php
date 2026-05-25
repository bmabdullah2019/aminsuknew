<?php

namespace App\Modules\Reports\Controllers;

use App\Modules\Reports\ReportFilter;
use App\Modules\Reports\Services\PerformanceReportService;
use Illuminate\Http\Request;

class PerformanceReportController extends ReportBaseController
{
    public function __construct(
        protected PerformanceReportService $service
    ) {}

    /**
     * Warehouse Wise Profit & Loss.
     */
    public function warehousePL(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        [$filter->startDate, $filter->endDate] = $this->resolvePeriodDateRange(
            $filter->period,
            $filter->startDate,
            $filter->endDate
        );

        $reports = $this->service->getWarehouseWisePL($filter);

        return view('backEnd.reports.warehouse_pl', compact('reports', 'filter'));
    }

    /**
     * Product Wise Profit & Loss.
     */
    public function productPL(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        [$filter->startDate, $filter->endDate] = $this->resolvePeriodDateRange(
            $filter->period,
            $filter->startDate,
            $filter->endDate
        );

        $products = $this->service->getProductWisePL($filter);

        return view('backEnd.reports.product_pl', compact('products', 'filter'));
    }

    /**
     * Inventory Valuation Report.
     */
    public function inventoryValuation(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        $valuation = $this->service->getInventoryValuation($filter);

        return view('backEnd.reports.inventory_valuation', compact('valuation', 'filter'));
    }

    /**
     * Costing Comparison Report (FIFO vs WAC).
     */
    public function costingComparison(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        [$filter->startDate, $filter->endDate] = $this->resolvePeriodDateRange(
            $filter->period,
            $filter->startDate,
            $filter->endDate
        );

        $results = $this->service->getCostingComparison($filter);

        return view('backEnd.reports.costing_comparison', compact('results', 'filter'));
    }
}
