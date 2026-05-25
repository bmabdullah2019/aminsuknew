<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\ReportFilter;
use App\Services\ProfitLossService;
use Illuminate\Support\Facades\DB;

class PerformanceReportService
{
    public function __construct(
        protected ProfitLossService $profitLossService
    ) {}

    /**
     * Warehouse Wise Profit & Loss.
     */
    public function getWarehouseWisePL(ReportFilter $filter): array
    {
        // Leverage existing ProfitLossService if applicable, or implement modular logic
        // For now, we'll adapt the logic from ProfitLossService into a modular format
        $warehouses = DB::table('warehouses')->where('is_active', true)->get();
        $results = [];

        foreach ($warehouses as $warehouse) {
            $report = $this->profitLossService->generateProfitLossReport(
                $filter->startDate,
                $filter->endDate,
                ['warehouse_id' => $warehouse->id]
            );

            $results[] = [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'revenue' => $report['revenue'],
                'cogs' => $report['cogs'],
                'expenses' => $report['operating_expenses'],
                'net_profit' => $report['net_profit'],
            ];
        }

        return $results;
    }

    /**
     * Product Wise Profit & Loss.
     */
    public function getProductWisePL(ReportFilter $filter, int $perPage = 15)
    {
        // Implementation for product-wise P&L
        return DB::table('products')
            ->select('products.id', 'products.name')
            ->selectRaw('COALESCE((SELECT SUM(qty * (sale_price - purchase_price)) FROM order_details JOIN orders ON orders.id = order_details.order_id WHERE order_details.product_id = products.id AND orders.order_status = 5 AND orders.created_at BETWEEN ? AND ?), 0) as estimated_profit', [$filter->startDate, $filter->endDate])
            ->orderBy('estimated_profit', 'desc')
            ->paginate($perPage);
    }

    /**
     * Inventory Valuation.
     */
    public function getInventoryValuation(ReportFilter $filter): array
    {
        return $this->profitLossService->generateInventoryValuationReport(
            now()->toDateString(),
            'fifo',
            $filter->toArray()
        );
    }

    /**
     * Costing Comparison (FIFO vs WAC).
     */
    public function getCostingComparison(ReportFilter $filter, int $perPage = 15)
    {
        return DB::table('products')
            ->select('products.id', 'products.name')
            ->selectRaw('COALESCE((SELECT SUM(qty) FROM order_details JOIN orders ON orders.id = order_details.order_id WHERE order_details.product_id = products.id AND orders.order_status = 5 AND orders.created_at BETWEEN ? AND ?), 0) as units_sold', [$filter->startDate, $filter->endDate])
            ->selectRaw('COALESCE((SELECT SUM(total_value_fifo) FROM inventory_valuations WHERE product_id = products.id AND valuation_date <= ? ORDER BY valuation_date DESC LIMIT 1), 0) as fifo_valuation', [$filter->endDate])
            ->selectRaw('COALESCE((SELECT SUM(total_value_wac) FROM inventory_valuations WHERE product_id = products.id AND valuation_date <= ? ORDER BY valuation_date DESC LIMIT 1), 0) as wac_valuation', [$filter->endDate])
            ->orderBy('units_sold', 'desc')
            ->paginate($perPage);
    }
}
