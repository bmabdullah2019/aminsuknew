<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

final class ProfitLossQuery
{
    public function __construct(
        public readonly SalesReportQuery $salesReportQuery
    ) {}

    public function salesRevenue(string $startDate, string $endDate, array $filters = []): float
    {
        return round($this->salesReportQuery->revenueMinor($startDate, $endDate, $filters) / 100, 2);
    }

    public function cogs(string $startDate, string $endDate, array $filters = []): array
    {
        $lines = $this->salesReportQuery->lines($startDate, $endDate, $filters);

        // We use the purchase_price_minor from order_details as a base,
        // but the goal is to refactor this to use StockMovement layers in the Service layer.
        // For the Query level, we ensure it accounts for returns.

        $purchaseMinorExpression = 'COALESCE(NULLIF(order_details.purchase_price_minor, 0), order_details.purchase_price * 100, 0)';

        $totals = $lines
            ->selectRaw("COALESCE(SUM(order_details.qty * {$purchaseMinorExpression}), 0) AS total_minor")
            ->selectRaw('COALESCE(SUM(order_details.qty), 0) AS total_units')
            ->first();

        $totalSalesMinor = (float) ($totals->total_minor ?? 0);
        $totalSalesUnits = (float) ($totals->total_units ?? 0);

        // Subtract returns from COGS (reversing the cost)
        $returnsCostMinor = 0;
        $returnUnits = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('return_items')) {
            $returnsQuery = \Illuminate\Support\Facades\DB::table('return_items')
                ->join('return_orders', 'return_orders.id', '=', 'return_items.return_order_id')
                ->whereBetween('return_orders.created_at', [
                    \Carbon\Carbon::parse($startDate)->startOfDay(),
                    \Carbon\Carbon::parse($endDate)->endOfDay(),
                ])
                ->where('return_orders.return_status', 'completed');

            if (isset($filters['product_id'])) {
                $returnsQuery->where('return_items.product_id', (int) $filters['product_id']);
            }

            if (isset($filters['warehouse_id'])) {
                $returnsQuery->where('return_items.warehouse_id', (int) $filters['warehouse_id']);
            }

            $returnTotals = $returnsQuery
                ->selectRaw('COALESCE(SUM(return_items.return_quantity * return_items.unit_cost * 100), 0) as total_return_cost_minor')
                ->selectRaw('COALESCE(SUM(return_items.return_quantity), 0) as total_return_units')
                ->first();

            $returnsCostMinor = (float) ($returnTotals->total_return_cost_minor ?? 0);
            $returnUnits = (float) ($returnTotals->total_return_units ?? 0);
        }

        $netCostMinor = max(0, $totalSalesMinor - $returnsCostMinor);
        $netUnits = max(0, $totalSalesUnits - $returnUnits);

        $total = round($netCostMinor / 100, 2);

        return [
            'total' => $total,
            'units_sold' => $netUnits,
            'average_cogs' => $netUnits > 0 ? $total / $netUnits : 0.0,
        ];
    }
}
