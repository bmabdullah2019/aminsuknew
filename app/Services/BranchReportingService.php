<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchReportingService
{
    public function branchWiseSales(string $dateFrom, string $dateTo): Collection
    {
        $orderAmountExpr = $this->orderAmountExpression('orders');
        $paymentAmountExpr = $this->paymentAmountExpression('payments');

        $salesSub = DB::table('orders')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('orders.branch_id')
            ->selectRaw('orders.branch_id')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("COALESCE(SUM({$orderAmountExpr}), 0) as gross_sales");

        $paymentSub = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.payment_status', '=', 'paid')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('orders.branch_id')
            ->selectRaw('orders.branch_id')
            ->selectRaw("COALESCE(SUM({$paymentAmountExpr}), 0) as paid_sales");

        return DB::table('branches')
            ->leftJoinSub($salesSub, 'sales', function ($join) {
                $join->on('sales.branch_id', '=', 'branches.id');
            })
            ->leftJoinSub($paymentSub, 'payments', function ($join) {
                $join->on('payments.branch_id', '=', 'branches.id');
            })
            ->selectRaw('branches.id as branch_id')
            ->selectRaw('branches.name as branch_name')
            ->selectRaw('branches.code as branch_code')
            ->selectRaw('COALESCE(sales.total_orders, 0) as total_orders')
            ->selectRaw('COALESCE(sales.gross_sales, 0) as gross_sales')
            ->selectRaw('COALESCE(payments.paid_sales, 0) as paid_sales')
            ->selectRaw('COALESCE(sales.gross_sales, 0) - COALESCE(payments.paid_sales, 0) as receivable_sales')
            ->orderBy('branches.name')
            ->get();
    }

    public function branchWiseExpense(string $dateFrom, string $dateTo): Collection
    {
        return DB::table('expenses')
            ->join('branches', 'branches.id', '=', 'expenses.branch_id')
            ->whereBetween('expenses.expense_date', [$dateFrom, $dateTo])
            ->groupBy('expenses.branch_id', 'branches.name', 'branches.code')
            ->selectRaw('expenses.branch_id')
            ->selectRaw('branches.name as branch_name')
            ->selectRaw('branches.code as branch_code')
            ->selectRaw('COUNT(expenses.id) as expense_count')
            ->selectRaw('COALESCE(SUM(expenses.total_amount), 0) as total_expense')
            ->selectRaw("COALESCE(SUM(CASE WHEN expenses.status = 'paid' THEN expenses.total_amount ELSE 0 END), 0) as paid_expense")
            ->orderBy('branches.name')
            ->get();
    }

    public function branchWiseProfit(string $dateFrom, string $dateTo): Collection
    {
        $orderAmountExpr = $this->orderAmountExpression('orders');

        $revenueSub = DB::table('orders')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('orders.branch_id')
            ->selectRaw('orders.branch_id')
            ->selectRaw("COALESCE(SUM({$orderAmountExpr}), 0) as revenue");

        $cogsSub = DB::table('order_details')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('orders.branch_id')
            ->selectRaw('orders.branch_id')
            ->selectRaw('COALESCE(SUM(order_details.qty * order_details.purchase_price), 0) as cogs');

        $expenseSub = DB::table('expenses')
            ->whereBetween('expenses.expense_date', [$dateFrom, $dateTo])
            ->groupBy('expenses.branch_id')
            ->selectRaw('expenses.branch_id')
            ->selectRaw('COALESCE(SUM(expenses.total_amount), 0) as expenses_total');

        return DB::table('branches')
            ->leftJoinSub($revenueSub, 'revenue', function ($join) {
                $join->on('revenue.branch_id', '=', 'branches.id');
            })
            ->leftJoinSub($cogsSub, 'cogs', function ($join) {
                $join->on('cogs.branch_id', '=', 'branches.id');
            })
            ->leftJoinSub($expenseSub, 'expense', function ($join) {
                $join->on('expense.branch_id', '=', 'branches.id');
            })
            ->selectRaw('branches.id as branch_id')
            ->selectRaw('branches.name as branch_name')
            ->selectRaw('branches.code as branch_code')
            ->selectRaw('COALESCE(revenue.revenue, 0) as revenue')
            ->selectRaw('COALESCE(cogs.cogs, 0) as cogs')
            ->selectRaw('COALESCE(expense.expenses_total, 0) as expenses')
            ->selectRaw('COALESCE(revenue.revenue, 0) - COALESCE(cogs.cogs, 0) - COALESCE(expense.expenses_total, 0) as net_profit')
            ->orderBy('branches.name')
            ->get();
    }

    public function consolidatedCompanyProfit(string $dateFrom, string $dateTo): object
    {
        $rows = $this->branchWiseProfit($dateFrom, $dateTo);

        return (object) [
            'revenue' => (float) $rows->sum('revenue'),
            'cogs' => (float) $rows->sum('cogs'),
            'expenses' => (float) $rows->sum('expenses'),
            'net_profit' => (float) $rows->sum('net_profit'),
        ];
    }

    public function warehouseWiseStock(): Collection
    {
        return DB::table('warehouse_stock')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_stock.warehouse_id')
            ->join('branches', 'branches.id', '=', 'warehouse_stock.branch_id')
            ->groupBy(
                'warehouse_stock.branch_id',
                'warehouse_stock.warehouse_id',
                'branches.name',
                'branches.code',
                'warehouses.name',
                'warehouses.code'
            )
            ->selectRaw('warehouse_stock.branch_id')
            ->selectRaw('warehouse_stock.warehouse_id')
            ->selectRaw('branches.name as branch_name')
            ->selectRaw('branches.code as branch_code')
            ->selectRaw('warehouses.name as warehouse_name')
            ->selectRaw('warehouses.code as warehouse_code')
            ->selectRaw('COALESCE(SUM(warehouse_stock.physical_quantity), 0) as total_physical_qty')
            ->selectRaw('COALESCE(SUM(warehouse_stock.available_quantity), 0) as total_available_qty')
            ->selectRaw('COALESCE(SUM(warehouse_stock.total_value), 0) as total_stock_value')
            ->orderBy('branches.name')
            ->orderBy('warehouses.name')
            ->get();
    }

    public function supplierDueByBranch(): Collection
    {
        return DB::table('supplier_ledgers')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_ledgers.supplier_id')
            ->join('branches', 'branches.id', '=', 'supplier_ledgers.branch_id')
            ->groupBy(
                'supplier_ledgers.branch_id',
                'supplier_ledgers.supplier_id',
                'branches.name',
                'branches.code',
                'suppliers.name',
                'suppliers.supplier_code'
            )
            ->selectRaw('supplier_ledgers.branch_id')
            ->selectRaw('supplier_ledgers.supplier_id')
            ->selectRaw('branches.name as branch_name')
            ->selectRaw('branches.code as branch_code')
            ->selectRaw('suppliers.name as supplier_name')
            ->selectRaw('suppliers.supplier_code')
            ->selectRaw('COALESCE(SUM(supplier_ledgers.debit), 0) as purchase_total')
            ->selectRaw('COALESCE(SUM(supplier_ledgers.credit), 0) as paid_total')
            ->selectRaw('COALESCE(SUM(supplier_ledgers.debit - supplier_ledgers.credit), 0) as due_total')
            ->havingRaw('COALESCE(SUM(supplier_ledgers.debit - supplier_ledgers.credit), 0) > 0')
            ->orderBy('branches.name')
            ->orderBy('suppliers.name')
            ->get();
    }

    public function customerReceivableByBranch(): Collection
    {
        $orderAmountExpr = $this->orderAmountExpression('orders');
        $paymentAmountExpr = $this->paymentAmountExpression('payments');

        $orderSub = DB::table('orders')
            ->groupBy('orders.branch_id', 'orders.customer_id')
            ->selectRaw('orders.branch_id')
            ->selectRaw('orders.customer_id')
            ->selectRaw("COALESCE(SUM({$orderAmountExpr}), 0) as order_total");

        $paymentSub = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.payment_status', '=', 'paid')
            ->groupBy('orders.branch_id', 'orders.customer_id')
            ->selectRaw('orders.branch_id')
            ->selectRaw('orders.customer_id')
            ->selectRaw("COALESCE(SUM({$paymentAmountExpr}), 0) as paid_total");

        $query = DB::table('customers')
            ->joinSub($orderSub, 'orders_agg', function ($join) {
                $join->on('orders_agg.customer_id', '=', 'customers.id');
            })
            ->join('branches', 'branches.id', '=', 'orders_agg.branch_id')
            ->leftJoinSub($paymentSub, 'payments_agg', function ($join) {
                $join->on('payments_agg.branch_id', '=', 'orders_agg.branch_id')
                    ->on('payments_agg.customer_id', '=', 'orders_agg.customer_id');
            });

        if (Schema::hasTable('return_orders')) {
            $refundSub = DB::table('return_orders')
                ->join('orders', 'orders.id', '=', 'return_orders.order_id')
                ->groupBy('orders.branch_id', 'orders.customer_id')
                ->selectRaw('orders.branch_id')
                ->selectRaw('orders.customer_id')
                ->selectRaw('COALESCE(SUM(return_orders.refund_amount), 0) as refund_total');

            $query->leftJoinSub($refundSub, 'refunds_agg', function ($join) {
                $join->on('refunds_agg.branch_id', '=', 'orders_agg.branch_id')
                    ->on('refunds_agg.customer_id', '=', 'orders_agg.customer_id');
            });
        }

        return $query
            ->selectRaw('orders_agg.branch_id')
            ->selectRaw('orders_agg.customer_id')
            ->selectRaw('branches.name as branch_name')
            ->selectRaw('branches.code as branch_code')
            ->selectRaw('customers.name as customer_name')
            ->selectRaw('customers.phone as customer_phone')
            ->selectRaw('COALESCE(orders_agg.order_total, 0) as order_total')
            ->selectRaw('COALESCE(payments_agg.paid_total, 0) as paid_total')
            ->selectRaw(Schema::hasTable('return_orders') ? 'COALESCE(refunds_agg.refund_total, 0) as refund_total' : '0 as refund_total')
            ->selectRaw(Schema::hasTable('return_orders')
                ? 'COALESCE(orders_agg.order_total, 0) - COALESCE(payments_agg.paid_total, 0) - COALESCE(refunds_agg.refund_total, 0) as receivable_total'
                : 'COALESCE(orders_agg.order_total, 0) - COALESCE(payments_agg.paid_total, 0) as receivable_total')
            ->whereRaw(Schema::hasTable('return_orders')
                ? 'COALESCE(orders_agg.order_total, 0) - COALESCE(payments_agg.paid_total, 0) - COALESCE(refunds_agg.refund_total, 0) > 0'
                : 'COALESCE(orders_agg.order_total, 0) - COALESCE(payments_agg.paid_total, 0) > 0')
            ->orderBy('branches.name')
            ->orderBy('customers.name')
            ->get();
    }

    private function orderAmountExpression(string $tableAlias): string
    {
        return "COALESCE(NULLIF({$tableAlias}.amount_minor, 0) / 100, {$tableAlias}.amount, 0)";
    }

    private function paymentAmountExpression(string $tableAlias): string
    {
        return "COALESCE(NULLIF({$tableAlias}.amount_minor, 0) / 100, {$tableAlias}.amount, 0)";
    }
}
