<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\ProfitLossEntry;
use App\Models\ProfitLossReport;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Queries\Reports\ProfitLossQuery;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfitLossService
{
    /**
     * Normalize dates returned by both Eloquent models and query builder rows.
     */
    private function toDateString(mixed $date): string
    {
        return Carbon::parse($date)->toDateString();
    }

    /**
     * Calculate Cost of Goods Sold (COGS) using FIFO method.
     */
    public function calculateCOGSFIFO(int $productId, int $warehouseId, float $quantitySold, string $saleDate): array
    {
        $costLayers = $this->getCostLayers($productId, $warehouseId, $saleDate);
        $remainingQuantity = (float) $quantitySold;
        $totalCOGS = 0.0;
        $usedLayers = [];

        foreach ($costLayers as $layer) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableQuantity = (float) $layer['remaining_quantity'];
            $quantityToUse = min($remainingQuantity, $availableQuantity);
            $layerCOGS = $quantityToUse * (float) $layer['unit_cost'];

            $totalCOGS += $layerCOGS;
            $remainingQuantity -= $quantityToUse;

            $usedLayers[] = [
                'purchase_id' => $layer['purchase_id'],
                'quantity_used' => $quantityToUse,
                'unit_cost' => (float) $layer['unit_cost'],
                'cogs_amount' => $layerCOGS,
            ];
        }

        return [
            'total_cogs' => $totalCOGS,
            'average_cost' => $quantitySold > 0 ? $totalCOGS / $quantitySold : 0.0,
            'used_layers' => $usedLayers,
            'remaining_quantity' => $remainingQuantity,
        ];
    }

    /**
     * Calculate Cost of Goods Sold (COGS) using Weighted Average Cost.
     */
    public function calculateCOGSWAC(int $productId, int $warehouseId, float $quantitySold, string $saleDate): array
    {
        $wacCost = $this->calculateWACCost($productId, $warehouseId, $saleDate);

        return [
            'total_cogs' => $quantitySold * $wacCost,
            'average_cost' => $wacCost,
            'costing_method' => 'weighted_average',
        ];
    }

    /**
     * Build remaining FIFO layers from stock movements.
     */
    private function getCostLayers(int $productId, int $warehouseId, string $saleDate): array
    {
        $movementQuery = StockMovement::query()
            ->where('product_id', $productId)
            ->whereDate('created_at', '<=', Carbon::parse($saleDate)->toDateString())
            ->orderBy('created_at')
            ->orderBy('id');

        if ($warehouseId > 0) {
            $movementQuery->where('warehouse_id', $warehouseId);
        }

        $movements = $movementQuery->get([
            'id',
            'type',
            'reference_id',
            'reference_type',
            'quantity',
            'unit_cost',
            'created_at',
        ]);

        $layers = [];
        $outboundQuantity = 0.0;

        foreach ($movements as $movement) {
            $quantity = (float) $movement->quantity;

            $isInbound = in_array($movement->type, ['grn', 'transfer_in', 'adjustment_in', 'initial_stock'], true);
            $isOutbound = in_array($movement->type, ['sale', 'transfer_out', 'adjustment_out', 'loss'], true);

            if ($isInbound && $quantity > 0 && $movement->unit_cost !== null && (float) $movement->unit_cost > 0) {
                $layers[] = [
                    'purchase_id' => $movement->reference_id ?: $movement->id,
                    'unit_cost' => (float) $movement->unit_cost,
                    'quantity_purchased' => $quantity,
                    'remaining_quantity' => $quantity,
                    'purchase_date' => $this->toDateString($movement->created_at),
                ];

                continue;
            }

            if ($isOutbound && $quantity != 0.0) {
                $outboundQuantity += abs($quantity);
            }
        }

        if (empty($layers)) {
            return [];
        }

        foreach ($layers as &$layer) {
            if ($outboundQuantity <= 0) {
                break;
            }

            $consumed = min((float) $layer['remaining_quantity'], $outboundQuantity);
            $layer['remaining_quantity'] -= $consumed;
            $outboundQuantity -= $consumed;
        }
        unset($layer);

        return array_values(array_filter($layers, function (array $layer) {
            return (float) $layer['remaining_quantity'] > 0;
        }));
    }

    /**
     * Calculate weighted average cost from inbound stock movements.
     */
    private function calculateWACCost(int $productId, int $warehouseId, string $saleDate): float
    {
        $movementQuery = StockMovement::query()
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->whereIn('type', ['grn', 'transfer_in', 'adjustment_in', 'initial_stock'])
            ->whereNotNull('unit_cost')
            ->whereDate('created_at', '<=', Carbon::parse($saleDate)->toDateString());

        if ($warehouseId > 0) {
            $movementQuery->where('warehouse_id', $warehouseId);
        }

        $totals = $movementQuery
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) as total_cost')
            ->first();

        $totalQty = (float) ($totals->total_qty ?? 0);
        $totalCost = (float) ($totals->total_cost ?? 0);

        if ($totalQty > 0) {
            return $totalCost / $totalQty;
        }

        $stockQuery = WarehouseStock::query()->where('product_id', $productId);
        if ($warehouseId > 0) {
            $stockQuery->where('warehouse_id', $warehouseId);
        }

        $warehouseAverage = (float) $stockQuery->avg('average_cost');
        if ($warehouseAverage > 0) {
            return $warehouseAverage;
        }

        return (float) optional(Product::find($productId))->purchase_price;
    }

    /**
     * Generate profit/loss report for a specific period.
     */
    public function generateProfitLossReport(
        string $reportType,
        string $startDate,
        string $endDate,
        array $filters = []
    ): ProfitLossReport {
        $normalizedStart = Carbon::parse($startDate)->toDateString();
        $normalizedEnd = Carbon::parse($endDate)->toDateString();
        $costingMethod = $filters['costing_method'] ?? 'fifo';

        $report = ProfitLossReport::firstOrNew([
            'report_type' => $reportType,
            'period_start' => $normalizedStart,
            'period_end' => $normalizedEnd,
            'product_id' => $filters['product_id'] ?? null,
            'warehouse_id' => $filters['warehouse_id'] ?? null,
            'costing_method' => $costingMethod,
        ]);

        $report->report_date = $normalizedEnd;
        $report->category_id = $filters['category_id'] ?? null;

        $salesRevenue = $this->calculateSalesRevenue($normalizedStart, $normalizedEnd, $filters);
        $report->sales_revenue = $salesRevenue;

        $cogs = $this->calculateCOGS($normalizedStart, $normalizedEnd, $filters, $costingMethod);
        $report->cost_of_goods_sold = $cogs['total'];

        $report->gross_profit = $salesRevenue - $report->cost_of_goods_sold;

        $operatingExpenses = $this->calculateOperatingExpenses($normalizedStart, $normalizedEnd, $filters);
        $report->operating_expenses = $operatingExpenses;

        $report->net_profit = $report->gross_profit - $operatingExpenses;

        $inventoryLosses = $this->calculateInventoryLosses($normalizedStart, $normalizedEnd, $filters);
        $report->inventory_losses = $inventoryLosses['total'];
        $report->damage_losses = $inventoryLosses['damage'];
        $report->expired_losses = $inventoryLosses['expired'];
        $report->theft_losses = $inventoryLosses['theft'];

        $inventoryValues = $this->calculateInventoryValues($normalizedEnd, $filters);
        $report->inventory_value_fifo = $inventoryValues['fifo'];
        $report->inventory_value_wac = $inventoryValues['wac'];

        $report->units_sold = $cogs['units_sold'];
        $report->additional_metrics = [
            'gross_margin_percentage' => $salesRevenue > 0 ? ($report->gross_profit / $salesRevenue) * 100 : 0,
            'net_margin_percentage' => $salesRevenue > 0 ? ($report->net_profit / $salesRevenue) * 100 : 0,
            'loss_percentage' => $salesRevenue > 0 ? ($report->inventory_losses / $salesRevenue) * 100 : 0,
        ];
        $report->generated_at = now();
        $report->save();

        return $report;
    }

    /**
     * Calculate sales revenue for a period.
     */
    public function calculateSalesRevenue(string $startDate, string $endDate, array $filters = []): float
    {
        return app(ProfitLossQuery::class)->salesRevenue($startDate, $endDate, $filters);
    }

    /**
     * Calculate Cost of Goods Sold.
     */
    public function calculateCOGS(string $startDate, string $endDate, array $filters = [], string $costingMethod = 'fifo'): array
    {
        $query = app(ProfitLossQuery::class);
        $costingMethod = strtolower($costingMethod);

        // For performance, we still get the sales lines in bulk
        $lines = $query->salesReportQuery->lines($startDate, $endDate, $filters)
            ->select('order_details.product_id', 'order_details.warehouse_id', 'order_details.qty', 'orders.created_at')
            ->get();

        $totalCOGS = 0.0;
        $totalUnits = 0.0;

        foreach ($lines as $line) {
            $qty = (float) $line->qty;
            if ($qty <= 0) {
                continue;
            }

            $saleDate = $this->toDateString($line->created_at);

            $result = $costingMethod === 'weighted_average'
                ? $this->calculateCOGSWAC((int) $line->product_id, (int) $line->warehouse_id, $qty, $saleDate)
                : $this->calculateCOGSFIFO((int) $line->product_id, (int) $line->warehouse_id, $qty, $saleDate);

            $totalCOGS += (float) $result['total_cogs'];
            $totalUnits += $qty;
        }

        // We also need to subtract returns from COGS (reversing the cost)
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

            $returnLines = $returnsQuery
                ->select('return_items.product_id', 'return_items.warehouse_id', 'return_items.return_quantity', 'return_items.unit_cost')
                ->get();

            foreach ($returnLines as $ret) {
                // Returns reduce the COGS (since the goods are back in stock or recorded as loss separately)
                $totalCOGS -= (float) ($ret->return_quantity * $ret->unit_cost);
                $totalUnits -= (float) $ret->return_quantity;
            }
        }

        $totalUnits = max(0, $totalUnits);
        $totalCOGS = max(0, $totalCOGS);

        return [
            'total' => round($totalCOGS, 2),
            'units_sold' => $totalUnits,
            'average_cogs' => $totalUnits > 0 ? $totalCOGS / $totalUnits : 0.0,
        ];
    }

    /**
     * Calculate operating expenses.
     */
    private function calculateOperatingExpenses(string $startDate, string $endDate, array $filters = []): float
    {
        $baseQuery = Expense::query()
            ->whereBetween('expense_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ])
            ->where('status', 'paid');

        if (! isset($filters['warehouse_id'])) {
            return (float) $baseQuery->sum('total_amount');
        }

        $warehouseId = (int) $filters['warehouse_id'];
        $expenses = $baseQuery
            ->whereHas('allocations', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->with(['allocations' => function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }])
            ->get();

        return (float) $expenses->sum(function ($expense) {
            return (float) $expense->allocations->sum('allocated_amount');
        });
    }

    /**
     * Calculate inventory losses.
     */
    private function calculateInventoryLosses(string $startDate, string $endDate, array $filters = []): array
    {
        $query = ProfitLossEntry::query()
            ->whereBetween('entry_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ])
            ->where('status', 'approved');

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        $total = (float) (clone $query)->sum('total_loss_amount');
        $damage = (float) (clone $query)->where('entry_type', 'damage')->sum('total_loss_amount');
        $expired = (float) (clone $query)->where('entry_type', 'expired')->sum('total_loss_amount');
        $theft = (float) (clone $query)->whereIn('entry_type', ['stolen', 'theft'])->sum('total_loss_amount');

        return [
            'total' => $total,
            'damage' => $damage,
            'expired' => $expired,
            'theft' => $theft,
        ];
    }

    /**
     * Calculate inventory values.
     */
    private function calculateInventoryValues(string $date, array $filters = []): array
    {
        $valuationQuery = InventoryValuation::query()
            ->whereDate('valuation_date', '<=', Carbon::parse($date)->toDateString());

        if (isset($filters['product_id'])) {
            $valuationQuery->where('product_id', $filters['product_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $valuationQuery->where('warehouse_id', $filters['warehouse_id']);
        }

        $latestValuations = $valuationQuery
            ->orderByDesc('valuation_date')
            ->get()
            ->unique(function ($row) {
                return $row->product_id.'-'.$row->warehouse_id;
            });

        if ($latestValuations->isNotEmpty()) {
            return [
                'fifo' => (float) $latestValuations->sum(function ($row) {
                    return (float) ($row->total_value_fifo ?? 0);
                }),
                'wac' => (float) $latestValuations->sum(function ($row) {
                    return (float) ($row->total_value_wac ?? 0);
                }),
            ];
        }

        $stockQuery = WarehouseStock::query();
        if (isset($filters['product_id'])) {
            $stockQuery->where('product_id', $filters['product_id']);
        }
        if (isset($filters['warehouse_id'])) {
            $stockQuery->where('warehouse_id', $filters['warehouse_id']);
        }

        $stocks = $stockQuery->get(['physical_quantity', 'average_cost']);
        $stockValue = (float) $stocks->sum(function ($stock) {
            return (float) $stock->physical_quantity * (float) $stock->average_cost;
        });

        return [
            'fifo' => $stockValue,
            'wac' => $stockValue,
        ];
    }

    /**
     * Generate product-wise profit analysis.
     */
    public function generateProductWiseProfit(string $startDate, string $endDate, array $filters = []): Collection
    {
        $costingMethod = $filters['costing_method'] ?? 'fifo';
        $warehouseFilter = $filters['warehouse_id'] ?? null;

        $productsQuery = Product::query();
        if (isset($filters['product_id'])) {
            $productsQuery->where('id', $filters['product_id']);
        } else {
            $productsQuery->whereHas('orderDetails', function ($query) use ($startDate, $endDate, $warehouseFilter) {
                $query->whereHas('order', function ($orderQuery) use ($startDate, $endDate) {
                    $orderQuery->whereBetween('created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay(),
                    ])->where('order_status', 5);
                });

                if ($warehouseFilter) {
                    $query->where('warehouse_id', $warehouseFilter);
                }
            });
        }

        $products = $productsQuery->get();
        $totalPeriodRevenue = $this->calculateSalesRevenue($startDate, $endDate, ['warehouse_id' => $warehouseFilter]);
        $totalPeriodExpenses = $this->calculateOperatingExpenses($startDate, $endDate, ['warehouse_id' => $warehouseFilter]);

        return $products->map(function ($product) use (
            $startDate,
            $endDate,
            $warehouseFilter,
            $costingMethod,
            $totalPeriodRevenue,
            $totalPeriodExpenses
        ) {
            $salesRevenue = $this->calculateSalesRevenue($startDate, $endDate, [
                'product_id' => $product->id,
                'warehouse_id' => $warehouseFilter,
            ]);

            $cogs = $this->calculateCOGS($startDate, $endDate, [
                'product_id' => $product->id,
                'warehouse_id' => $warehouseFilter,
            ], $costingMethod);

            $allocatedExpenses = $totalPeriodRevenue > 0
                ? $totalPeriodExpenses * ($salesRevenue / $totalPeriodRevenue)
                : 0.0;

            $losses = $this->calculateInventoryLosses($startDate, $endDate, [
                'product_id' => $product->id,
                'warehouse_id' => $warehouseFilter,
            ]);

            return [
                'product' => $product,
                'sales_revenue' => $salesRevenue,
                'cost_of_goods_sold' => $cogs['total'],
                'gross_profit' => $salesRevenue - $cogs['total'],
                'operating_expenses' => $allocatedExpenses,
                'inventory_losses' => $losses['total'],
                'net_profit' => $salesRevenue - $cogs['total'] - $allocatedExpenses - $losses['total'],
                'units_sold' => $cogs['units_sold'],
                'profit_margin' => $salesRevenue > 0 ? (($salesRevenue - $cogs['total']) / $salesRevenue) * 100 : 0,
            ];
        })->sortByDesc('net_profit')->values();
    }

    /**
     * Generate warehouse-wise profit analysis.
     */
    public function generateWarehouseWiseProfit(string $startDate, string $endDate, array $filters = []): Collection
    {
        $costingMethod = $filters['costing_method'] ?? 'fifo';
        $warehouses = Warehouse::query()
            ->when(isset($filters['warehouse_id']), function ($query) use ($filters) {
                $query->where('id', $filters['warehouse_id']);
            })
            ->get();

        return $warehouses->map(function ($warehouse) use ($startDate, $endDate, $costingMethod) {
            $warehouseFilter = ['warehouse_id' => $warehouse->id];

            $salesRevenue = $this->calculateSalesRevenue($startDate, $endDate, $warehouseFilter);
            $cogs = $this->calculateCOGS($startDate, $endDate, $warehouseFilter, $costingMethod);
            $expenses = $this->calculateOperatingExpenses($startDate, $endDate, $warehouseFilter);
            $losses = $this->calculateInventoryLosses($startDate, $endDate, $warehouseFilter);

            return [
                'warehouse' => $warehouse,
                'sales_revenue' => $salesRevenue,
                'cost_of_goods_sold' => $cogs['total'],
                'gross_profit' => $salesRevenue - $cogs['total'],
                'operating_expenses' => $expenses,
                'inventory_losses' => $losses['total'],
                'net_profit' => $salesRevenue - $cogs['total'] - $expenses - $losses['total'],
                'units_sold' => $cogs['units_sold'],
                'profit_margin' => $salesRevenue > 0 ? (($salesRevenue - $cogs['total']) / $salesRevenue) * 100 : 0,
            ];
        })->sortByDesc('net_profit')->values();
    }

    /**
     * Update inventory valuation.
     */
    public function updateInventoryValuation(int $productId, int $warehouseId, string $date): void
    {
        $valuation = InventoryValuation::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'valuation_date' => Carbon::parse($date)->toDateString(),
        ]);

        $stock = WarehouseStock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        $currentStock = (float) ($stock->physical_quantity ?? 0);
        $valuation->quantity_on_hand = $currentStock;

        $layers = $this->getCostLayers($productId, $warehouseId, $date);
        $valuationLayers = array_map(function (array $layer) {
            return [
                'quantity' => (float) $layer['remaining_quantity'],
                'unit_cost' => (float) $layer['unit_cost'],
                'purchase_id' => $layer['purchase_id'],
                'purchase_date' => $layer['purchase_date'],
            ];
        }, $layers);

        if (! empty($valuationLayers)) {
            $valuation->calculateValues($valuationLayers);
        } else {
            $fallbackUnitCost = (float) (optional($stock)->average_cost ?? optional(Product::find($productId))->purchase_price ?? 0);
            $valuation->cost_layers = [];
            $valuation->unit_cost_fifo = $fallbackUnitCost;
            $valuation->unit_cost_wac = $fallbackUnitCost;
            $valuation->total_value_fifo = $currentStock * $fallbackUnitCost;
            $valuation->total_value_wac = $currentStock * $fallbackUnitCost;
        }

        $valuation->save();
    }

    /**
     * Build detailed inventory valuation dataset for reporting.
     */
    public function generateInventoryValuationReport(
        string $valuationDate,
        string $costingMethod = 'fifo',
        array $filters = []
    ): array {
        $normalizedDate = Carbon::parse($valuationDate)->toDateString();
        $resolvedCostingMethod = $costingMethod === 'weighted_average' ? 'weighted_average' : 'fifo';

        $stocksQuery = WarehouseStock::query()
            ->where('physical_quantity', '>', 0)
            ->whereHas('product', function ($query) {
                $query->where('status', 1);
            })
            ->whereHas('warehouse', function ($query) {
                $query->where('is_active', true);
            })
            ->with([
                'product:id,name,product_code,sku,purchase_price,status',
                'warehouse:id,name,city,is_active',
            ]);

        if (isset($filters['product_id'])) {
            $stocksQuery->where('product_id', (int) $filters['product_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $stocksQuery->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        $stocks = $stocksQuery
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();

        if ($stocks->isEmpty()) {
            return [
                'items' => collect(),
                'summary' => [
                    'total_inventory_value' => 0.0,
                    'products_in_stock' => 0,
                    'stock_records' => 0,
                    'total_units' => 0.0,
                    'average_unit_cost' => 0.0,
                    'fifo_total_value' => 0.0,
                    'wac_total_value' => 0.0,
                ],
                'warehouse_summary' => collect(),
            ];
        }

        $productIds = $stocks->pluck('product_id')->unique()->values();
        $warehouseIds = $stocks->pluck('warehouse_id')->unique()->values();

        $valuations = InventoryValuation::query()
            ->whereDate('valuation_date', '<=', $normalizedDate)
            ->whereIn('product_id', $productIds)
            ->whereIn('warehouse_id', $warehouseIds)
            ->orderByDesc('valuation_date')
            ->get()
            ->unique(function (InventoryValuation $valuation) {
                return $valuation->product_id.'-'.$valuation->warehouse_id;
            })
            ->keyBy(function (InventoryValuation $valuation) {
                return $valuation->product_id.'-'.$valuation->warehouse_id;
            });

        $items = $stocks->map(function (WarehouseStock $stock) use ($valuations) {
            $key = $stock->product_id.'-'.$stock->warehouse_id;
            $valuation = $valuations->get($key);

            $quantityOnHand = (float) $stock->physical_quantity;
            $fallbackUnitCost = (float) ($stock->average_cost ?: optional($stock->product)->purchase_price ?: 0);

            $unitCostFifo = (float) ($valuation->unit_cost_fifo ?? $fallbackUnitCost);
            $unitCostWac = (float) ($valuation->unit_cost_wac ?? $fallbackUnitCost);

            $totalValueFifo = (float) ($valuation->total_value_fifo ?? ($quantityOnHand * $unitCostFifo));
            $totalValueWac = (float) ($valuation->total_value_wac ?? ($quantityOnHand * $unitCostWac));

            $reorderPoint = (float) ($stock->reorder_point ?? 0);
            $stockLevel = 'high';
            if ($quantityOnHand <= 0) {
                $stockLevel = 'out';
            } elseif ($reorderPoint > 0 && $quantityOnHand <= $reorderPoint) {
                $stockLevel = 'low';
            } elseif ($reorderPoint > 0 && $quantityOnHand <= ($reorderPoint * 2)) {
                $stockLevel = 'medium';
            }

            return [
                'product_id' => (int) $stock->product_id,
                'warehouse_id' => (int) $stock->warehouse_id,
                'product_name' => optional($stock->product)->name ?? 'N/A',
                'product_code' => optional($stock->product)->product_code
                    ?? optional($stock->product)->sku
                    ?? ('P-'.$stock->product_id),
                'warehouse_name' => optional($stock->warehouse)->name ?? 'N/A',
                'warehouse_city' => optional($stock->warehouse)->city,
                'quantity_on_hand' => $quantityOnHand,
                'unit_cost_fifo' => $unitCostFifo,
                'unit_cost_wac' => $unitCostWac,
                'total_value_fifo' => $totalValueFifo,
                'total_value_wac' => $totalValueWac,
                'last_valuation_date' => $valuation?->valuation_date
                    ? $this->toDateString($valuation->valuation_date)
                    : null,
                'stock_level' => $stockLevel,
            ];
        });

        $selectedTotalKey = $resolvedCostingMethod === 'weighted_average' ? 'total_value_wac' : 'total_value_fifo';
        $totalInventoryValue = (float) $items->sum($selectedTotalKey);
        $totalUnits = (float) $items->sum('quantity_on_hand');

        $summary = [
            'total_inventory_value' => $totalInventoryValue,
            'products_in_stock' => (int) $items->pluck('product_id')->unique()->count(),
            'stock_records' => (int) $items->count(),
            'total_units' => $totalUnits,
            'average_unit_cost' => $totalUnits > 0 ? $totalInventoryValue / $totalUnits : 0.0,
            'fifo_total_value' => (float) $items->sum('total_value_fifo'),
            'wac_total_value' => (float) $items->sum('total_value_wac'),
        ];

        $warehouseSummary = $items
            ->groupBy('warehouse_id')
            ->map(function (Collection $warehouseItems) use ($totalInventoryValue, $selectedTotalKey) {
                $first = $warehouseItems->first();
                $warehouseValue = (float) $warehouseItems->sum($selectedTotalKey);

                return [
                    'warehouse_id' => (int) ($first['warehouse_id'] ?? 0),
                    'warehouse_name' => $first['warehouse_name'] ?? 'N/A',
                    'products_count' => (int) $warehouseItems->pluck('product_id')->unique()->count(),
                    'total_units' => (float) $warehouseItems->sum('quantity_on_hand'),
                    'inventory_value' => $warehouseValue,
                    'percentage_of_total' => $totalInventoryValue > 0 ? ($warehouseValue / $totalInventoryValue) * 100 : 0.0,
                ];
            })
            ->sortByDesc('inventory_value')
            ->values();

        return [
            'items' => $items,
            'summary' => $summary,
            'warehouse_summary' => $warehouseSummary,
        ];
    }

    /**
     * Get profit/loss trends over time.
     */
    public function getProfitTrends(int $months = 12): array
    {
        $trends = [];
        $currentDate = now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $currentDate->copy()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth()->toDateString();
            $monthEnd = $date->copy()->endOfMonth()->toDateString();

            $report = ProfitLossReport::query()
                ->where('report_type', 'monthly')
                ->where('period_start', $monthStart)
                ->where('period_end', $monthEnd)
                ->whereNull('product_id')
                ->whereNull('warehouse_id')
                ->where('costing_method', 'fifo')
                ->first();

            if (! $report) {
                $report = $this->generateProfitLossReport('monthly', $monthStart, $monthEnd);
            }

            $profitMargin = $report->sales_revenue > 0
                ? ((float) $report->net_profit / (float) $report->sales_revenue) * 100
                : 0.0;

            $trends[] = [
                'month' => $date->format('M Y'),
                'month_key' => $date->format('Y-m'),
                'sales_revenue' => (float) $report->sales_revenue,
                'gross_profit' => (float) $report->gross_profit,
                'net_profit' => (float) $report->net_profit,
                'inventory_losses' => (float) $report->inventory_losses,
                'profit_margin' => $profitMargin,
            ];
        }

        return $trends;
    }
}
