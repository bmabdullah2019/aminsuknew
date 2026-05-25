<?php

namespace App\Modules\Reports\Controllers;

use App\Modules\Reports\ReportFilter;
use App\Modules\Reports\Services\SalesReportService;
use Illuminate\Http\Request;

class SalesReportController extends ReportBaseController
{
    public function __construct(
        protected SalesReportService $service
    ) {}

    /**
     * Display a listing of sales.
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

        $sales = $this->service->getSalesReport($filter);
        $summary = $this->service->getSalesSummary($filter);

        return view('backEnd.reports.sales', compact('sales', 'summary', 'filter'));
    }
}
