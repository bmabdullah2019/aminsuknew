<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PurchaseReportQuery
{
    public function lines(?string $startDate, ?string $endDate, array $filters = []): Builder
    {
        $query = $this->baseQuery($startDate, $endDate, $filters)
            ->select(
                'grns.id as report_id',
                'grn_items.id as line_id',
                'grns.grn_number as order_number',
                'grns.grn_number as po_number',
                'grns.invoice_number',
                'grns.grn_date as purchase_date',
                'grns.created_at',
                'grns.status',
                'suppliers.supplier_code',
                'suppliers.name as supplier_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name'
            );

        $query->addSelect(
            'products.name as product_name',
            'products.sku as product_sku',
            'grn_items.sku as item_sku',
            'grn_items.description as item_description',
            'grn_items.unit_cost'
        );

        if ($this->supportsProductVariants()) {
            $query->addSelect(
                'product_variants.sku_code as variant_sku',
                'product_variants.color',
                'product_variants.size',
                'product_variants.age'
            );
        } else {
            $query->selectRaw('NULL as variant_sku');
            $query->selectRaw('NULL as color');
            $query->selectRaw('NULL as size');
            $query->selectRaw('NULL as age');
        }

        $query->selectRaw($this->orderedQuantityExpression().' as quantity_ordered');
        $query->selectRaw('grn_items.quantity as quantity_received');
        $query->selectRaw('grn_items.quantity as quantity');
        $query->selectRaw('0 as returned_qty');
        $query->selectRaw($this->orderedCostExpression().' as ordered_cost');
        $query->selectRaw($this->lineTotalExpression().' as total_cost');
        $query->selectRaw($this->lineTotalExpression().' as line_total');

        return $query;
    }

    public function summary(?string $startDate, ?string $endDate, array $filters = []): object
    {
        return $this->baseQuery($startDate, $endDate, $filters)
            ->selectRaw('COALESCE(SUM('.$this->orderedQuantityExpression().'), 0) as total_ordered_quantity')
            ->selectRaw('COALESCE(SUM(grn_items.quantity), 0) as total_received_quantity')
            ->selectRaw('COALESCE(SUM('.$this->orderedCostExpression().'), 0) as total_ordered_cost')
            ->selectRaw('COALESCE(SUM('.$this->lineTotalExpression().'), 0) as total_purchase_amount')
            ->selectRaw('COUNT(DISTINCT grns.id) as total_orders')
            ->first();
    }

    private function baseQuery(?string $startDate, ?string $endDate, array $filters = []): Builder
    {
        $query = DB::table('grn_items')
            ->join('grns', 'grns.id', '=', 'grn_items.grn_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'grns.supplier_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'grns.warehouse_id')
            ->leftJoin('products', 'products.id', '=', 'grn_items.product_id');

        if ($this->supportsProductVariants()) {
            $query->leftJoin('product_variants', 'product_variants.id', '=', 'grn_items.product_variant_id');
        }

        if ($startDate && $endDate) {
            $query->whereBetween('grns.grn_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ]);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function (Builder $keywordQuery) use ($keyword): void {
                $keywordQuery->where('grns.grn_number', 'like', '%'.$keyword.'%')
                    ->orWhere('grns.invoice_number', 'like', '%'.$keyword.'%')
                    ->orWhere('suppliers.name', 'like', '%'.$keyword.'%')
                    ->orWhere('products.name', 'like', '%'.$keyword.'%')
                    ->orWhere('products.sku', 'like', '%'.$keyword.'%')
                    ->orWhere('grn_items.sku', 'like', '%'.$keyword.'%')
                    ->orWhere('grn_items.description', 'like', '%'.$keyword.'%');

                if ($this->supportsProductVariants()) {
                    $keywordQuery->orWhere('product_variants.sku_code', 'like', '%'.$keyword.'%');
                }
            });
        }

        if (isset($filters['supplier_id'])) {
            $query->where('grns.supplier_id', (int) $filters['supplier_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $query->where('grns.warehouse_id', (int) $filters['warehouse_id']);
        }

        if (isset($filters['warehouse_ids']) && is_array($filters['warehouse_ids']) && $filters['warehouse_ids'] !== []) {
            $query->whereIn('grns.warehouse_id', array_map('intval', $filters['warehouse_ids']));
        }

        if (isset($filters['product_id'])) {
            $query->where('grn_items.product_id', (int) $filters['product_id']);
        }

        if (isset($filters['status'])) {
            $query->where('grns.status', (string) $filters['status']);
        }

        if (isset($filters['branch_id']) && $this->warehousesHaveBranchColumn()) {
            $query->where('warehouses.branch_id', (int) $filters['branch_id']);
        }

        return $query;
    }

    private function orderedQuantityExpression(): string
    {
        return $this->supportsOrderedQuantity()
            ? 'COALESCE(grn_items.ordered_quantity, grn_items.quantity)'
            : 'grn_items.quantity';
    }

    private function lineTotalExpression(): string
    {
        return '(grn_items.quantity * grn_items.unit_cost) + COALESCE(grn_items.tax_amount, 0)';
    }

    private function orderedCostExpression(): string
    {
        return '('.$this->orderedQuantityExpression().' * grn_items.unit_cost) + COALESCE(grn_items.tax_amount, 0)';
    }

    private function supportsOrderedQuantity(): bool
    {
        static $supportsOrderedQuantity = null;

        if ($supportsOrderedQuantity === null) {
            $supportsOrderedQuantity = Schema::hasColumn('grn_items', 'ordered_quantity');
        }

        return $supportsOrderedQuantity;
    }

    private function supportsProductVariants(): bool
    {
        static $supportsProductVariants = null;

        if ($supportsProductVariants === null) {
            $supportsProductVariants = Schema::hasTable('product_variants')
                && Schema::hasColumn('grn_items', 'product_variant_id');
        }

        return $supportsProductVariants;
    }

    private function warehousesHaveBranchColumn(): bool
    {
        static $warehousesHaveBranchColumn = null;

        if ($warehousesHaveBranchColumn === null) {
            $warehousesHaveBranchColumn = Schema::hasColumn('warehouses', 'branch_id');
        }

        return $warehousesHaveBranchColumn;
    }
}
