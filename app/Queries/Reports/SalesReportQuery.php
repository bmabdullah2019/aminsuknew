<?php

declare(strict_types=1);

namespace App\Queries\Reports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SalesReportQuery
{
    public function lines(string $startDate, string $endDate, array $filters = []): Builder
    {
        $query = DB::table('order_details')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween('orders.created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->where('orders.order_status', 5);

        if (isset($filters['product_id'])) {
            $query->where('order_details.product_id', (int) $filters['product_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $query->where('order_details.warehouse_id', (int) $filters['warehouse_id']);
        }

        return $query;
    }

    public function revenueMinor(string $startDate, string $endDate, array $filters = []): int
    {
        $saleMinorExpression = 'COALESCE(NULLIF(order_details.sale_price_minor, 0), order_details.sale_price * 100, 0)';

        $salesTotalMinor = (float) $this->lines($startDate, $endDate, $filters)
            ->selectRaw("COALESCE(SUM(order_details.qty * {$saleMinorExpression}), 0) AS total_minor")
            ->value('total_minor');

        // Subtract returns
        $returnsTotalMinor = 0;
        if (Schema::hasTable('return_items')) {
            $returnsQuery = DB::table('return_items')
                ->join('return_orders', 'return_orders.id', '=', 'return_items.return_order_id')
                ->whereBetween('return_orders.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ])
                ->where('return_orders.return_status', 'completed');

            if (isset($filters['product_id'])) {
                $returnsQuery->where('return_items.product_id', (int) $filters['product_id']);
            }

            if (isset($filters['warehouse_id'])) {
                $returnsQuery->where('return_items.warehouse_id', (int) $filters['warehouse_id']);
            }

            $returnsTotalMinor = (float) $returnsQuery
                ->selectRaw('COALESCE(SUM(return_items.return_quantity * return_items.unit_price * 100), 0) as total_return_minor')
                ->value('total_return_minor');
        }

        $netTotalMinor = max(0, $salesTotalMinor - $returnsTotalMinor);

        return (int) round($netTotalMinor, 0, PHP_ROUND_HALF_UP);
    }
}
