<?php

namespace App\Modules\Reports\Controllers;

use App\Modules\Reports\ReportFilter;
use App\Modules\Reports\Services\PurchaseReportService;
use Illuminate\Http\Request;

class PurchaseReportController extends ReportBaseController
{
    public function __construct(
        protected PurchaseReportService $service
    ) {}

    /**
     * Display a listing of purchases.
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

        $purchases = $this->service->getPurchaseReport($filter);
        $summary = $this->service->getPurchaseSummary($filter);

        return view('backEnd.reports.purchase_new', compact('purchases', 'summary', 'filter'));
    }
}
