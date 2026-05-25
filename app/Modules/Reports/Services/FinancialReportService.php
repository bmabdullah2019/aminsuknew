<?php

namespace App\Modules\Reports\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Supplier;
use App\Modules\Reports\ReportFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinancialReportService
{
    /**
     * Get detailed expense report.
     */
    public function getExpenseReport(ReportFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = Expense::query()
            ->with(['category', 'createdBy'])
            ->whereBetween('expense_date', [$filter->startDate, $filter->endDate])
            ->orderBy('expense_date', 'desc');

        if ($filter->categoryId) {
            $query->where('expense_category_id', $filter->categoryId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get supplier due report.
     */
    public function getSupplierDueReport(ReportFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        // Supplier due is often calculated from supplier_ledgers or the supplier balance itself
        $query = Supplier::query()
            ->select('suppliers.*')
            ->selectRaw('COALESCE((SELECT SUM(debit - credit) FROM supplier_ledgers WHERE supplier_id = suppliers.id), 0) as current_due')
            ->having('current_due', '>', 0)
            ->orderBy('current_due', 'desc');

        if ($filter->supplierId) {
            $query->where('id', $filter->supplierId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get customer due report (receivables).
     */
    public function getCustomerDueReport(ReportFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        // Using the logic seen in BranchReportingService but for general customer reporting
        $query = Customer::query()
            ->select('customers.*')
            ->selectRaw('COALESCE((SELECT SUM(amount_minor) FROM orders WHERE customer_id = customers.id AND order_status = 5), 0) / 100 as total_sales')
            ->selectRaw('COALESCE((SELECT SUM(amount_minor) FROM payments JOIN orders ON orders.id = payments.order_id WHERE orders.customer_id = customers.id AND payments.payment_status = "paid"), 0) / 100 as total_paid')
            ->selectRaw('(COALESCE((SELECT SUM(amount_minor) FROM orders WHERE customer_id = customers.id AND order_status = 5), 0) - COALESCE((SELECT SUM(amount_minor) FROM payments JOIN orders ON orders.id = payments.order_id WHERE orders.customer_id = customers.id AND payments.payment_status = "paid"), 0)) / 100 as current_due')
            ->having('current_due', '>', 0)
            ->orderBy('current_due', 'desc');

        if ($filter->customerId) {
            $query->where('id', $filter->customerId);
        }

        return $query->paginate($perPage);
    }
}
