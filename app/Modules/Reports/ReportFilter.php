<?php

namespace App\Modules\Reports;

use Illuminate\Http\Request;

class ReportFilter
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $warehouseId = null;

    public ?array $warehouseIds = null;

    public ?int $branchId = null;

    public ?int $productId = null;

    public ?int $categoryId = null;

    public ?int $customerId = null;

    public ?int $supplierId = null;

    public ?string $keyword = null;

    public ?string $status = null;

    public ?string $period = 'custom';

    public bool $export = false;

    public string $exportFormat = 'xlsx';

    public static function fromRequest(Request $request): self
    {
        $filter = new self;
        $filter->period = $request->input('period', 'custom');
        $filter->startDate = $request->input('start_date');
        $filter->endDate = $request->input('end_date');
        $filter->warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        $filter->warehouseIds = $request->input('warehouse_ids') ? (array) $request->input('warehouse_ids') : null;
        $filter->branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $filter->productId = $request->input('product_id') ? (int) $request->input('product_id') : null;
        $filter->categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $filter->customerId = $request->input('customer_id') ? (int) $request->input('customer_id') : null;
        $filter->supplierId = $request->input('supplier_id') ? (int) $request->input('supplier_id') : null;
        $filter->keyword = $request->input('keyword');
        $filter->status = $request->input('status') ?: $request->input('stock_status') ?: $request->input('order_status');
        $filter->export = $request->has('export');
        $filter->exportFormat = $request->input('export', 'xlsx');

        return $filter;
    }

    public function toArray(): array
    {
        return array_filter([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'warehouse_id' => $this->warehouseId,
            'warehouse_ids' => $this->warehouseIds,
            'branch_id' => $this->branchId,
            'product_id' => $this->productId,
            'category_id' => $this->categoryId,
            'customer_id' => $this->customerId,
            'supplier_id' => $this->supplierId,
            'keyword' => $this->keyword,
            'status' => $this->status,
            'period' => $this->period,
        ], fn ($value) => ! is_null($value));
    }
}
