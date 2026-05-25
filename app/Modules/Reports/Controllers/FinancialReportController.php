<?php

namespace App\Modules\Reports\Controllers;

use App\Modules\Reports\ReportFilter;
use App\Modules\Reports\Services\FinancialReportService;
use App\Modules\Reports\Services\ReturnsAnalyticsService;
use Illuminate\Http\Request;

class FinancialReportController extends ReportBaseController
{
    public function __construct(
        protected FinancialReportService $financeService,
        protected ReturnsAnalyticsService $returnsService
    ) {}

    /**
     * Expense Report.
     */
    public function expenses(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        [$filter->startDate, $filter->endDate] = $this->resolvePeriodDateRange(
            $filter->period,
            $filter->startDate,
            $filter->endDate
        );
        $this->guardReportDateRange($filter->startDate, $filter->endDate);

        $expenses = $this->financeService->getExpenseReport($filter);

        return view('backEnd.reports.expenses', compact('expenses', 'filter'));
    }

    /**
     * Supplier Due Report.
     */
    public function supplierDue(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        $suppliers = $this->financeService->getSupplierDueReport($filter);

        return view('backEnd.reports.supplier_due', compact('suppliers', 'filter'));
    }

    /**
     * Customer Due Report.
     */
    public function customerDue(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        $customers = $this->financeService->getCustomerDueReport($filter);

        return view('backEnd.reports.customer_due', compact('customers', 'filter'));
    }

    /**
     * Returns Analytics Report.
     */
    public function returns(Request $request)
    {
        $filter = ReportFilter::fromRequest($request);
        [$filter->startDate, $filter->endDate] = $this->resolvePeriodDateRange(
            $filter->period,
            $filter->startDate,
            $filter->endDate
        );
        $this->guardReportDateRange($filter->startDate, $filter->endDate);

        $returns = $this->returnsService->getReturnsReport($filter);
        $stats = $this->returnsService->getReturnStats($filter);

        return view('backEnd.reports.returns', compact('returns', 'stats', 'filter'));
    }
}
