<?php

namespace App\Modules\Reports\Controllers;

use App\Modules\Reports\ReportFilter;
use App\Modules\Reports\Services\StockMovementReportService;
use Illuminate\Http\Request;

class StockMovementController extends ReportBaseController
{
    public function __construct(
        protected StockMovementReportService $service
    ) {}

    /**
     * Display a listing of stock movements.
     */
    public function index(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);

        [$startDate, $endDate] = $this->resolvePeriodDateRange(
            $filter->period,
            $filter->startDate,
            $filter->endDate
        );

        $filter->startDate = $startDate;
        $filter->endDate = $endDate;

        $this->guardReportDateRange($startDate, $endDate);

        $movements = $this->service->getMovementReport($filter);

        return view('backEnd.reports.stock_movement', compact('movements', 'filter'));
    }
}
