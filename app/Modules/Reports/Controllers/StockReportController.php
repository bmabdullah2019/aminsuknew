<?php

namespace App\Modules\Reports\Controllers;

use App\Modules\Reports\ReportFilter;
use App\Modules\Reports\Services\StockReportService;
use Illuminate\Http\Request;

class StockReportController extends ReportBaseController
{
    public function __construct(
        protected StockReportService $service
    ) {}

    /**
     * Display a listing of stock.
     */
    public function index(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);

        // Stock reports are usually snapshot-based, but filter might have dates for trends
        // For inventory report, we mostly care about current levels.

        $stock = $this->service->getStockReport($filter);
        $summary = $this->service->getStockSummary($filter);

        return view('backEnd.reports.stock_new', compact('stock', 'summary', 'filter'));
    }

    /**
     * Display low stock report.
     */
    public function lowStock(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        $filter->status = 'low_stock';

        $stock = $this->service->getStockReport($filter);
        $summary = $this->service->getStockSummary($filter);

        return view('backEnd.reports.low_stock', compact('stock', 'summary', 'filter'));
    }
}
