<?php

declare(strict_types=1);

namespace App\Queries\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class StockReportQuery
{
    public function warehouseWise(array $filters = []): Builder
    {
        $query = DB::table('warehouse_stock')
            ->join('products', 'products.id', '=', 'warehouse_stock.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_stock.warehouse_id')
            ->selectRaw('warehouse_stock.warehouse_id')
            ->selectRaw('warehouses.name as warehouse_name')
            ->selectRaw('warehouse_stock.product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('COALESCE(SUM(warehouse_stock.physical_quantity), 0) as physical_quantity')
            ->selectRaw('COALESCE(SUM(warehouse_stock.reserved_quantity), 0) as reserved_quantity')
            ->selectRaw('COALESCE(SUM(warehouse_stock.available_quantity), 0) as available_quantity')
            ->groupBy(
                'warehouse_stock.warehouse_id',
                'warehouses.name',
                'warehouse_stock.product_id',
                'products.name'
            );

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_stock.warehouse_id', (int) $filters['warehouse_id']);
        }

        if (isset($filters['product_id'])) {
            $query->where('warehouse_stock.product_id', (int) $filters['product_id']);
        }

        return $query;
    }
}
