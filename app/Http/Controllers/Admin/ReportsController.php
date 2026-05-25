<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReportRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Modules\Reports\Queries\PurchaseReportQuery;
use Carbon\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportsController extends Controller
{
    private const MAX_REPORT_RANGE_DAYS = 730;

    public function __construct()
    {
        $this->middleware('permission:order-view|accounts-reports|stock-report|supplier-report-ledger', ['only' => ['purchaseReport']]);
    }

    public function purchaseReport(PurchaseReportRequest $request): View|BinaryFileResponse
    {
        $queryData = $request->queryData();
        [$startDate, $endDate] = $queryData->resolvedDateRange();
        $filters = $queryData->filters();

        if ($startDate && $endDate) {
            $this->guardDateRange($startDate, $endDate);
        }

        /** @var PurchaseReportQuery $reportQuery */
        $reportQuery = app(PurchaseReportQuery::class);
        $query = $reportQuery->lines($startDate, $endDate, $filters);
        $summary = $reportQuery->summary($startDate, $endDate, $filters);

        $totalOrderedQty = (float) $summary->total_ordered_quantity;
        $totalReceivedQty = (float) $summary->total_received_quantity;
        $totalOrderedCost = (float) $summary->total_ordered_cost;
        $totalReceivedCost = (float) $summary->total_purchase_amount;
        $totalPurchaseOrders = (int) $summary->total_orders;

        if ($queryData->exportsAsXlsx()) {
            $rows = (clone $query)
                ->orderByDesc('grns.grn_date')
                ->orderByDesc('grn_items.id')
                ->get()
                ->map(function (object $item): array {
                    $variantLabel = trim(implode(' / ', array_filter([
                        (string) ($item->color ?? ''),
                        (string) ($item->size ?? ''),
                        (string) ($item->age ?? ''),
                    ])));
                    $productLabel = (string) ($item->product_name ?: $item->item_description ?: '');

                    return [
                        (string) ($item->order_number ?? ''),
                        (string) ($item->supplier_code ?? ''),
                        (string) ($item->supplier_name ?? ''),
                        (string) ($item->warehouse_name ?? ''),
                        $productLabel,
                        (string) ($item->product_sku ?? $item->item_sku ?? ''),
                        $variantLabel,
                        (string) ($item->status ?? ''),
                        (float) ($item->quantity_ordered ?? 0),
                        (float) ($item->quantity_received ?? 0),
                        (float) ($item->unit_cost ?? 0),
                        round((float) ($item->ordered_cost ?? 0), 2),
                        round((float) ($item->total_cost ?? 0), 2),
                        $item->purchase_date
                            ? Carbon::parse((string) $item->purchase_date)->format('Y-m-d')
                            : '',
                    ];
                })
                ->values()
                ->all();

            return Excel::download(
                new ArrayReportExport(
                    [
                        'Purchase No',
                        'Supplier Code',
                        'Supplier Name',
                        'Warehouse',
                        'Product',
                        'SKU',
                        'Variant',
                        'Status',
                        'Qty Ordered',
                        'Qty Received',
                        'Unit Cost',
                        'Ordered Cost',
                        'Received Cost',
                        'Purchase Date',
                    ],
                    $rows
                ),
                'purchase-report-'.now()->format('Ymd_His').'.xlsx'
            );
        }

        $queryParams = $queryData->query();
        $items = $query
            ->orderByDesc('grns.grn_date')
            ->orderByDesc('grn_items.id')
            ->paginate(25)
            ->appends($queryParams);

        $suppliers = Supplier::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'supplier_code', 'name']);
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $products = Product::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('backEnd.reports.purchase', compact(
            'items',
            'suppliers',
            'warehouses',
            'products',
            'totalOrderedQty',
            'totalReceivedQty',
            'totalOrderedCost',
            'totalReceivedCost',
            'totalPurchaseOrders',
            'filters',
            'queryParams'
        ));
    }

    private function guardDateRange(string $startDate, string $endDate): void
    {
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        if ($days > self::MAX_REPORT_RANGE_DAYS) {
            abort(422, 'Date range is too large. Maximum allowed range is '.self::MAX_REPORT_RANGE_DAYS.' days.');
        }
    }
}
