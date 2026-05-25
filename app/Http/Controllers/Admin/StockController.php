<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\CatalogAttributeValue;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Productage;
use App\Models\Productcolor;
use App\Models\Productsize;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockService;
use App\Services\VariantAttributeService;
use App\Services\VariantStockService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class StockController extends Controller
{
    protected StockService $stockService;

    protected VariantStockService $variantStockService;

    protected VariantAttributeService $variantAttributeService;

    public function __construct(
        StockService $stockService,
        VariantStockService $variantStockService,
        VariantAttributeService $variantAttributeService
    ) {
        $this->middleware('permission:stock-list', ['only' => ['index', 'inventory', 'getInventoryData', 'getProductStock', 'getProductVariants', 'balance', 'movements', 'searchProductsForMovements', 'alerts', 'resolveAlert', 'deadStock', 'audit']]);
        $this->middleware('permission:stock-adjust', ['only' => ['create', 'store', 'setForm']]);
        $this->middleware('permission:stock-adjust', ['only' => ['edit', 'update']]);
        $this->middleware('permission:stock-approve', ['only' => ['destroy']]);

        $this->stockService = $stockService;
        $this->variantStockService = $variantStockService;
        $this->variantAttributeService = $variantAttributeService;
    }

    /**
     * Display all stock items
     */
    public function index(Request $request)
    {
        $query = Stock::with(['product', 'variant']);

        // Search functionality
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })->orWhereHas('variant', function ($q) use ($search) {
                $q->where('sku_code', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->status) {
            switch ($request->status) {
                case 'in_stock':
                    $query->inStock();
                    break;
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'out_of_stock':
                    $query->outOfStock();
                    break;
            }
        }

        $stocks = $query->orderBy('updated_at', 'desc')->paginate(25);

        // Statistics
        $stats = [
            'total_items' => Stock::count(),
            'in_stock' => Stock::inStock()->count(),
            'low_stock' => Stock::lowStock()->count(),
            'out_of_stock' => Stock::outOfStock()->count(),
        ];

        return view('backEnd.stocks.index', compact('stocks', 'stats'));
    }

    /**
     * Advanced inventory management dashboard
     */
    public function inventory(Request $request)
    {
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $brands = Brand::where('status', 1)->orderBy('name')->get();

        // Calculate analytics
        $analytics = [
            'total_products' => Product::where('status', 1)->count(),
            'total_warehouses' => $warehouses->count(),
            'total_value' => WarehouseStock::sum('total_value'),
            'low_stock_count' => WarehouseStock::whereRaw('available_quantity > 0 AND available_quantity <= reorder_point')->count(),
        ];

        // Get initial products for server-side rendering
        try {
            $query = Product::with(['category', 'image', 'warehouseStocks.warehouse'])
                ->where('status', 1)
                ->limit(25); // Load first 25 products initially

            $products = $query->get();

            $data = $products->map(function ($product) {
                $warehouseStocks = $product->warehouseStocks ?? collect();
                $totalStock = $warehouseStocks->sum('physical_quantity');
                $totalAvailable = $warehouseStocks->sum('available_quantity');

                $stockData = $warehouseStocks->map(function ($stock) {
                    return [
                        'warehouse_id' => $stock->warehouse_id,
                        'warehouse_name' => $stock->warehouse ? $stock->warehouse->name : 'Unknown',
                        'physical_quantity' => $stock->physical_quantity,
                        'available_quantity' => $stock->available_quantity,
                    ];
                });

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image ? $product->image->image : null,
                    'sku' => $product->sku,
                    'product_code' => $product->product_code,
                    'category' => $product->category ? $product->category->name : 'N/A',
                    'new_price' => $product->new_price,
                    'total_stock' => $totalStock,
                    'total_available' => $totalAvailable,
                    'stock_data' => $stockData,
                ];
            });

            $initialProducts = [
                'success' => true,
                'data' => $data->toArray(),
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 25,
                    'total' => $products->count(),
                ],
            ];
        } catch (\Exception $e) {
            $initialProducts = [
                'success' => false,
                'message' => 'Failed to load initial products: '.$e->getMessage(),
            ];
        }

        return view('backEnd.stock.inventory', compact('warehouses', 'categories', 'brands', 'analytics', 'initialProducts'));
    }

    /**
     * Get inventory data for AJAX requests
     */
    public function getInventoryData(Request $request)
    {
        try {
            $selectedWarehouseId = $request->filled('warehouse_id') ? (int) $request->warehouse_id : null;

            $query = Product::select([
                'products.id',
                'products.name',
                'products.sku',
                'products.product_code',
                'products.new_price',
                'products.category_id',
                'products.brand_id',
                'products.has_variant',
            ])
                ->with([
                    'category:id,name',
                    'image:id,image',
                    'warehouseStocks' => function ($q) {
                        $q->select('id', 'product_id', 'product_variant_id', 'warehouse_id', 'physical_quantity', 'available_quantity', 'reorder_point', 'reorder_quantity')
                            ->with('warehouse:id,name');
                    },
                ])
                ->where('products.status', 1);

            // Search filter
            if ($request->search) {
                $search = trim($request->search);
                if (strlen($search) > 0) {
                    $query->where(function ($q) use ($search) {
                        $q->where('products.name', 'like', "%{$search}%")
                            ->orWhere('products.sku', 'like', "%{$search}%")
                            ->orWhere('products.product_code', 'like', "%{$search}%");
                    });
                }
            }

            // Warehouse filter
            if ($selectedWarehouseId) {
                $query->whereHas('warehouseStocks', function ($q) use ($selectedWarehouseId) {
                    $q->where('warehouse_id', $selectedWarehouseId);
                });
            }

            // Category filter
            if ($request->category_id) {
                $query->where('products.category_id', $request->category_id);
            }

            // Brand filter
            if ($request->brand_id) {
                $query->where('products.brand_id', $request->brand_id);
            }

            // Status filter
            if ($request->status) {
                switch ($request->status) {
                    case 'in_stock':
                        $query->whereHas('warehouseStocks', function ($q) use ($selectedWarehouseId) {
                            if ($selectedWarehouseId) {
                                $q->where('warehouse_id', $selectedWarehouseId);
                            }
                            $q->where('available_quantity', '>', 0);
                        });
                        break;
                    case 'out_of_stock':
                        if ($selectedWarehouseId) {
                            $query->whereHas('warehouseStocks', function ($q) use ($selectedWarehouseId) {
                                $q->where('warehouse_id', $selectedWarehouseId)
                                    ->where('available_quantity', '<=', 0);
                            });
                        } else {
                            $query->whereDoesntHave('warehouseStocks', function ($q) {
                                $q->where('available_quantity', '>', 0);
                            });
                        }
                        break;
                    case 'low_stock':
                        $query->whereHas('warehouseStocks', function ($q) use ($selectedWarehouseId) {
                            if ($selectedWarehouseId) {
                                $q->where('warehouse_id', $selectedWarehouseId);
                            }
                            $q->whereRaw('available_quantity > 0 AND available_quantity <= reorder_point');
                        });
                        break;
                    case 'overstock':
                        $query->whereHas('warehouseStocks', function ($q) use ($selectedWarehouseId) {
                            if ($selectedWarehouseId) {
                                $q->where('warehouse_id', $selectedWarehouseId);
                            }
                            $q->whereRaw('reorder_quantity > 0 AND available_quantity >= reorder_quantity');
                        });
                        break;
                }
            }

            $products = $query->paginate($request->per_page ?? 50);

            $data = $products->map(function ($product) use ($selectedWarehouseId) {
                $warehouseStocks = $product->warehouseStocks ?? collect();
                $displayStocks = $selectedWarehouseId
                    ? $warehouseStocks->where('warehouse_id', $selectedWarehouseId)->values()
                    : $warehouseStocks;
                $totalStock = $displayStocks->sum('physical_quantity');
                $totalAvailable = $displayStocks->sum('available_quantity');

                $stockData = $displayStocks->map(function ($stock) {
                    return [
                        'warehouse_id' => $stock->warehouse_id,
                        'warehouse_name' => $stock->warehouse ? $stock->warehouse->name : 'Unknown',
                        'physical_quantity' => $stock->physical_quantity,
                        'available_quantity' => $stock->available_quantity,
                    ];
                });

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image ? $product->image->image : null,
                    'sku' => $product->sku,
                    'product_code' => $product->product_code,
                    'category' => $product->category ? $product->category->name : 'N/A',
                    'new_price' => $product->new_price,
                    'total_stock' => $totalStock,
                    'total_available' => $totalAvailable,
                    'stock_data' => $stockData,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Inventory data loading error: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load inventory data: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show stock details
     */
    public function show($warehouseId, $productId)
    {
        $stock = $this->resolveWarehouseStock((int) $warehouseId, (int) $productId)
            ->load(['product.images', 'product.category', 'product.brand', 'warehouse']);

        // Get recent movements for this product and warehouse
        $movements = StockMovement::with('creator')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('backEnd.stock.show', compact('stock', 'movements'));
    }

    /**
     * Manual stock adjustment
     */
    public function adjust(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $stock = Stock::findOrFail($id);

            $this->stockService->adjustStock(
                $stock->product_id,
                $stock->variant_id,
                $validated['quantity']
            );

            Toastr::success('Stock adjusted successfully', 'Success');

            return redirect()->back();

        } catch (\Exception $e) {
            Toastr::error('Failed to adjust stock: '.$e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Display stock balance with detailed warehouse stock information
     */
    public function balance(Request $request)
    {
        if ($request->ajax() && $request->filled('warehouse_id') && $request->filled('product_id')) {
            $stock = WarehouseStock::query()
                ->select([
                    'warehouse_id',
                    'product_id',
                    'physical_quantity',
                    'reserved_quantity',
                    'available_quantity',
                    'reorder_point',
                    'reorder_quantity',
                    'average_cost',
                    'total_value',
                ])
                ->where('warehouse_id', $request->warehouse_id)
                ->where('product_id', $request->product_id)
                ->first();

            return response()->json([
                'warehouse_id' => (int) $request->warehouse_id,
                'product_id' => (int) $request->product_id,
                'physical_quantity' => (float) ($stock->physical_quantity ?? 0),
                'reserved_quantity' => (float) ($stock->reserved_quantity ?? 0),
                'available_quantity' => (float) ($stock->available_quantity ?? 0),
                'reorder_point' => (float) ($stock->reorder_point ?? 0),
                'reorder_quantity' => (float) ($stock->reorder_quantity ?? 0),
                'average_cost' => (float) ($stock->average_cost ?? 0),
                'total_value' => (float) ($stock->total_value ?? 0),
            ]);
        }

        $warehouses = Warehouse::active()->orderBy('name')->get();

        // Build query for warehouse stocks
        $query = WarehouseStock::with(['product', 'warehouse']);

        // Search filter
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        // Warehouse filter
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        // Status filter
        if ($request->status) {
            switch ($request->status) {
                case 'in':
                    $query->where('available_quantity', '>', 0);
                    break;
                case 'low':
                    $query->whereRaw('available_quantity > 0 AND available_quantity <= reorder_point');
                    break;
                case 'out':
                    $query->where('available_quantity', '<=', 0);
                    break;
            }
        }

        // Quantity filters
        if ($request->min_available) {
            $query->where('available_quantity', '>=', $request->min_available);
        }

        if ($request->max_available) {
            $query->where('available_quantity', '<=', $request->max_available);
        }

        // Sorting
        $sortBy = $request->sort_by ?: 'created_at';
        $sortDirection = $request->sort_direction ?: 'desc';

        switch ($sortBy) {
            case 'physical_quantity':
            case 'available_quantity':
            case 'total_value':
                $query->orderBy($sortBy, $sortDirection);
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $allowedPerPage = [25, 50, 100, 200];
        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 25;
        }
        $stocks = $query->paginate($perPage)->withQueryString();

        // Calculate statistics
        $stats = [
            'total_items' => WarehouseStock::count(),
            'total_value' => WarehouseStock::sum('total_value'),
            'low_stock_count' => WarehouseStock::whereRaw('available_quantity > 0 AND available_quantity <= reorder_point')->count(),
            'out_of_stock_count' => WarehouseStock::where('available_quantity', '<=', 0)->count(),
        ];

        return view('backEnd.stock.balance', compact('stocks', 'warehouses', 'stats'));
    }

    /**
     * Keyword search for Stock Ledger product filter (Select2 AJAX).
     */
    public function searchProductsForMovements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $term = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 25);

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%'.$term.'%';

        $products = Product::query()
            ->where('status', 1)
            ->where(function ($outer) use ($like) {
                $outer->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('product_code', 'like', $like);

                if (Schema::hasColumn('products', 'slug')) {
                    $outer->orWhere('slug', 'like', $like);
                }

                $outer->orWhereHas('productVariants', function ($q) use ($like) {
                    $q->where('sku_code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'sku']);

        $results = $products->map(function (Product $product) {
            $skuLabel = $product->sku !== null && $product->sku !== '' ? $product->sku : 'N/A';

            return [
                'id' => $product->id,
                'text' => $product->name.' (SKU: '.$skuLabel.')',
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Display stock movements history
     */
    public function movements(Request $request)
    {
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $selectedProductId = $request->filled('product_id') ? (int) $request->product_id : null;
        $selectedProduct = $selectedProductId
            ? Product::query()
                ->where('status', 1)
                ->find($selectedProductId)
            : null;
        $ledgerMode = $selectedProductId ? 'single' : 'all';

        // Check if any filters are applied
        $filtersApplied = $request->filled('warehouse_id') || 
                          $request->filled('type') || 
                          $request->filled('product_id') ||
                          $request->filled('search') || 
                          ($request->filled('start_date') && $request->filled('end_date'));

        if (!$filtersApplied && $request->query('export') !== 'xlsx') {
            $movements = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
            $summaryRows = collect();
            $openingBalance = 0;
            return view('backEnd.stock.movements', compact('warehouses', 'selectedProduct', 'ledgerMode', 'movements', 'summaryRows', 'openingBalance', 'filtersApplied'));
        }

        // Build query for stock movements
        $query = StockMovement::with(['product', 'productVariant', 'warehouse', 'creator']);

        // Warehouse filter
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Type filter
        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($selectedProductId) {
            $query->where('product_id', $selectedProductId);
        }

        // Product search (including SKU and variants)
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                })->orWhereHas('productVariant', function ($vq) use ($search) {
                    $vq->where('sku_code', 'like', "%{$search}%")
                        ->orWhere('color', 'like', "%{$search}%")
                        ->orWhere('size', 'like', "%{$search}%");
                });
            });
        }

        // Date range filter
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }

        if ($filtersApplied && in_array($request->query('export'), ['xlsx', 'pdf'], true)) {
            if ($request->query('export') === 'xlsx') {
                $rows = (clone $query)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function (StockMovement $movement) {
                        return [
                            (string) ($movement->created_at?->format('Y-m-d H:i:s') ?? ''),
                            (string) ($movement->warehouse?->name ?? ''),
                            (string) ($movement->product?->name ?? '').($movement->productVariant ? ' ('.$movement->productVariant->getDisplayName().')' : ''),
                            (string) ($movement->type ?? ''),
                            (string) ($movement->reference_type ?? ''),
                            (string) ($movement->reference_id ?? ''),
                            (float) ($movement->quantity ?? 0),
                            (float) ($movement->unit_cost ?? 0),
                            (float) ($movement->balance_after ?? 0),
                            (string) ($movement->notes ?? ''),
                            (string) ($movement->creator?->name ?? ''),
                        ];
                    })
                    ->values()
                    ->all();

                return Excel::download(
                    new ArrayReportExport(
                        ['Date', 'Warehouse', 'Product', 'Type', 'Reference Type', 'Reference ID', 'Quantity', 'Unit Cost', 'Balance After', 'Notes', 'Created By'],
                        $rows
                    ),
                    'stock-movements-'.now()->format('Ymd_His').'.xlsx'
                );
            }

            // PDF export
            $warehouseName = null;
            if ($request->filled('warehouse_id')) {
                $warehouseName = (string) ($warehouses->firstWhere('id', (int) $request->warehouse_id)?->name ?? '');
            }

            if ($ledgerMode === 'single') {
                $movementsPdf = (clone $query)->orderBy('created_at', 'asc')->get();
                $summaryRowsPdf = collect();
                $openingBalancePdf = 0;
            } else {
                $periodMovements = (clone $query)->orderBy('created_at', 'asc')->get();
                $openingByProduct = collect();

                if ($request->filled('start_date')) {
                    $openingQuery = StockMovement::query();
                    if ($request->warehouse_id) {
                        $openingQuery->where('warehouse_id', $request->warehouse_id);
                    }
                    $openingByProduct = $openingQuery
                        ->where('created_at', '<', $request->start_date.' 00:00:00')
                        ->selectRaw('product_id, COALESCE(SUM(quantity), 0) as opening')
                        ->groupBy('product_id')
                        ->pluck('opening', 'product_id');
                }

                $movementsPdf = collect();
                $openingBalancePdf = 0;

                $summaryRowsPdf = $periodMovements
                    ->groupBy('product_id')
                    ->map(function ($items, $productId) use ($openingByProduct) {
                        $product = optional($items->first())->product;
                        $opening = (float) ($openingByProduct->get($productId) ?? 0);
                        $purchase = (float) $items->where('type', 'grn')->sum('quantity');
                        $purchaseReceive = (float) $items->where('type', 'transfer_in')->sum('quantity');
                        $salesReturn = (float) $items->where('type', 'sales_return')->sum('quantity');
                        $reject = (float) $items->where('type', 'reject')->sum('quantity');
                        $purchaseReturn = (float) abs($items->where('type', 'purchase_return')->sum('quantity'));
                        $purchaseIssue = (float) abs($items->where('type', 'transfer_out')->sum('quantity'));
                        $sales = (float) abs($items->where('type', 'sale')->sum('quantity'));
                        $balance = $opening + (float) $items->sum('quantity');

                        return [
                            'product_name' => (string) ($product->name ?? 'N/A'),
                            'product_code' => (string) ($product->product_code ?? $product->sku ?? ''),
                            'item_type' => (bool) ($product->has_variant ?? false) ? 'Variant' : 'Single',
                            'opening' => $opening,
                            'purchase' => $purchase,
                            'purchase_receive' => $purchaseReceive,
                            'sales_return' => $salesReturn,
                            'reject' => $reject,
                            'purchase_return' => $purchaseReturn,
                            'purchase_issue' => $purchaseIssue,
                            'sales' => $sales,
                            'balance' => $balance,
                        ];
                    })
                    ->values();
            }

            $selectedProductName = $selectedProduct ? (string) ($selectedProduct->name ?? '') : null;

            $pdf = Pdf::loadView('backEnd.stock.movements_pdf', [
                'movements' => $ledgerMode === 'single' ? $movementsPdf : collect(),
                'summaryRows' => $ledgerMode === 'all' ? collect($summaryRowsPdf ?? []) : collect(),
                'ledgerMode' => $ledgerMode,
                'startDate' => (string) ($request->start_date ?? ''),
                'endDate' => (string) ($request->end_date ?? ''),
                'warehouseName' => $warehouseName,
                'selectedProductName' => $selectedProductName,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('stock-ledger-'.now()->format('Ymd_His').'.pdf');
        }

        $summaryRows = collect();
        $openingBalance = 0;
        $movements = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);

        if ($ledgerMode === 'single') {
            $movements = $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->paginate(50);
            
            $firstItem = $movements->first();
            if ($firstItem) {
                $openingBalance = StockMovement::query()
                    ->where('product_id', $selectedProductId)
                    ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
                    ->where(function ($q) use ($firstItem) {
                        $q->where('created_at', '<', $firstItem->created_at)
                            ->orWhere(function ($sq) use ($firstItem) {
                                $sq->where('created_at', $firstItem->created_at)
                                    ->where('id', '<', $firstItem->id);
                            });
                    })
                    ->sum('quantity');
            } else {
                // If no items on current page, calculate base opening balance for the filter
                $openingQuery = StockMovement::query()->where('product_id', $selectedProductId);
                if ($request->warehouse_id) {
                    $openingQuery->where('warehouse_id', $request->warehouse_id);
                }
                $openingBalance = (float) $openingQuery
                    ->where('created_at', '<', ($request->start_date ?: '1970-01-01').' 00:00:00')
                    ->sum('quantity');
            }
        } else {
            $periodMovements = (clone $query)->orderBy('created_at', 'asc')->get();
            $openingByProduct = collect();

            if ($request->filled('start_date')) {
                $openingQuery = StockMovement::query();
                if ($request->warehouse_id) {
                    $openingQuery->where('warehouse_id', $request->warehouse_id);
                }
                $openingByProduct = $openingQuery
                    ->where('created_at', '<', $request->start_date.' 00:00:00')
                    ->selectRaw('product_id, COALESCE(SUM(quantity), 0) as opening')
                    ->groupBy('product_id')
                    ->pluck('opening', 'product_id');
            }

            $summaryRows = $periodMovements
                ->groupBy('product_id')
                ->map(function ($items, $productId) use ($openingByProduct) {
                    $product = optional($items->first())->product;
                    $opening = (float) ($openingByProduct->get($productId) ?? 0);
                    $purchase = (float) $items->where('type', 'grn')->sum('quantity');
                    $purchaseReceive = (float) $items->where('type', 'transfer_in')->sum('quantity');
                    $salesReturn = (float) $items->where('type', 'sales_return')->sum('quantity');
                    $reject = (float) $items->where('type', 'reject')->sum('quantity');
                    $purchaseReturn = (float) abs($items->where('type', 'purchase_return')->sum('quantity'));
                    $purchaseIssue = (float) abs($items->where('type', 'transfer_out')->sum('quantity'));
                    $sales = (float) abs($items->where('type', 'sale')->sum('quantity'));
                    $balance = $opening + (float) $items->sum('quantity');

                    return [
                        'product_name' => (string) ($product->name ?? 'N/A'),
                        'product_code' => (string) ($product->product_code ?? $product->sku ?? ''),
                        'item_type' => (bool) ($product->has_variant ?? false) ? 'Variant' : 'Single',
                        'opening' => $opening,
                        'purchase' => $purchase,
                        'purchase_receive' => $purchaseReceive,
                        'sales_return' => $salesReturn,
                        'reject' => $reject,
                        'purchase_return' => $purchaseReturn,
                        'purchase_issue' => $purchaseIssue,
                        'sales' => $sales,
                        'balance' => $balance,
                    ];
                })
                ->values();
        }

        return view('backEnd.stock.movements', compact(
            'warehouses',
            'selectedProduct',
            'ledgerMode',
            'movements',
            'summaryRows',
            'openingBalance',
            'filtersApplied'
        ));
    }


    /**
     * Display stock alerts
     */
    public function alerts(Request $request)
    {
        $query = StockAlert::with(['product', 'warehouse']);

        $status = $request->input('status', 'active');
        if (in_array($status, ['active', 'resolved'], true)) {
            $query->where('status', $status);
        }

        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $alerts = $query
            ->orderByRaw("
                CASE severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'warning' THEN 3
                    WHEN 'medium' THEN 4
                    WHEN 'low' THEN 5
                    ELSE 6
                END
            ")
            ->orderByDesc('created_at')
            ->paginate(25)
            ->appends($request->query());

        return view('backEnd.stock.alerts', compact('alerts'));
    }

    /**
     * Get alerts data as JSON for dynamic loading
     */
    public function getAlertsData(Request $request)
    {
        try {
            $query = StockAlert::with(['product', 'warehouse']);

            $status = $request->input('status', 'active');
            if (in_array($status, ['active', 'resolved'], true)) {
                $query->where('status', $status);
            }

            if ($request->filled('alert_type')) {
                $query->where('alert_type', $request->alert_type);
            }

            if ($request->filled('severity')) {
                $query->where('severity', $request->severity);
            }

            if ($request->filled('search')) {
                $search = "%{$request->search}%";
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', $search)
                        ->orWhere('sku', 'like', $search);
                });
            }

            $alerts = $query
                ->orderByRaw("
                    CASE severity
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'warning' THEN 3
                        WHEN 'medium' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END
                ")
                ->orderByDesc('created_at')
                ->paginate(25)
                ->appends($request->query());

            $html = view('backEnd.stock._alerts_table', compact('alerts'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'pagination' => (string) $alerts->links('pagination::bootstrap-5'),
                'total' => $alerts->total(),
                'from' => $alerts->firstItem(),
                'to' => $alerts->lastItem(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load alerts: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve a stock alert
     */
    public function resolveAlert($id)
    {
        try {
            $alert = StockAlert::findOrFail($id);

            if ($alert->status === 'resolved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock alert is already resolved',
                ]);
            }

            $alert->resolve(auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Stock alert resolved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve stock alert: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display dead stock
     */
    public function deadStock(Request $request)
    {
        $days = max((int) $request->input('days', 90), 1);
        $cutoff = now()->subDays($days)->toDateTimeString();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        $lastMovementExpression = 'COALESCE(GREATEST(last_stock_in_date, last_stock_out_date), last_stock_in_date, last_stock_out_date)';

        $query = WarehouseStock::with(['product', 'warehouse'])
            ->select('warehouse_stock.*')
            ->selectRaw($lastMovementExpression.' as last_movement_date')
            ->where('available_quantity', '>', 0)
            ->where(function ($q) use ($lastMovementExpression, $cutoff) {
                $q->whereRaw($lastMovementExpression.' IS NULL')
                    ->orWhereRaw($lastMovementExpression.' <= ?', [$cutoff]);
            });

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->search) {
            $search = trim($request->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('product_code', 'like', '%'.$search.'%');
            });
        }

        $deadStocks = $query->orderByDesc('total_value')
            ->paginate(25)
            ->appends($request->query());

        $deadStocks->getCollection()->transform(function ($stock) {
            $lastMovement = $stock->getAttribute('last_movement_date');
            $lastMovementDate = $lastMovement ? Carbon::parse($lastMovement) : null;

            $stock->last_movement_date = $lastMovementDate;
            $stock->days_since_last_sale = $lastMovementDate ? $lastMovementDate->diffInDays(now()) : null;

            return $stock;
        });

        return view('backEnd.stock.dead-stock', compact('deadStocks', 'warehouses'));
    }

    /**
     * Display stock audit
     */
    public function audit(Request $request)
    {
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $warehouse = null;
        $stock = collect();

        if ($request->filled('warehouse_id')) {
            $warehouse = Warehouse::active()->findOrFail((int) $request->warehouse_id);

            $stock = WarehouseStock::with(['product'])
                ->where('warehouse_id', $warehouse->id)
                ->orderBy('product_id')
                ->get();

            if ($stock->isNotEmpty()) {
                $latestMovements = StockMovement::query()
                    ->where('warehouse_id', $warehouse->id)
                    ->whereIn('product_id', $stock->pluck('product_id')->unique())
                    ->orderByDesc('created_at')
                    ->get()
                    ->groupBy('product_id')
                    ->map(function ($items) {
                        return $items->first();
                    });

                $stock->each(function ($item) use ($latestMovements) {
                    $latest = $latestMovements->get($item->product_id);
                    $item->setRelation('movements', $latest ? collect([$latest]) : collect());
                });
            }
        }

        return view('backEnd.warehouse.stock.audit', compact('warehouse', 'stock', 'warehouses'));
    }

    /**
     * Show set stock form
     */
    public function setForm(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'search' => 'nullable|string|max:120',
        ]);

        // Get selected warehouse
        $selectedWarehouseId = (int) ($validated['warehouse_id'] ?? Warehouse::active()->value('id'));
        $searchTerm = trim((string) ($validated['search'] ?? ''));

        if (! $selectedWarehouseId) {
            Toastr::error('No active warehouses found', 'Error');

            return redirect()->back();
        }

        $warehouse = Warehouse::findOrFail($selectedWarehouseId);
        $warehouses = Warehouse::active()->orderBy('name')->get();

        // Get products with their warehouse stock for the selected warehouse
        $productsQuery = Product::with(['images', 'category', 'warehouseStocks' => function ($query) use ($selectedWarehouseId) {
            $query->where('warehouse_id', $selectedWarehouseId);
        }])
            ->where('status', 1);

        if ($searchTerm !== '') {
            $productsQuery->where('name', 'like', '%'.$searchTerm.'%');
        }

        $products = $productsQuery
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        // Add current_stock and reorder_point to each product
        $products->getCollection()->transform(function ($product) {
            $warehouseStock = $product->warehouseStocks->first();
            $product->current_stock = $warehouseStock ? $warehouseStock->available_quantity : 0;
            $product->reorder_point = $warehouseStock ? $warehouseStock->reorder_point : 0;

            return $product;
        });

        return view('backEnd.stock.set', compact(
            'warehouse',
            'warehouses',
            'selectedWarehouseId',
            'products',
            'searchTerm'
        ));
    }

    /**
     * Edit stock details for a specific warehouse and product
     */
    public function edit($warehouseId, $productId)
    {
        $stock = $this->resolveWarehouseStock((int) $warehouseId, (int) $productId)
            ->load(['product.images', 'product.category', 'product.brand', 'warehouse']);

        return view('backEnd.stock.edit', compact('stock'));
    }

    /**
     * Update stock details for a specific warehouse and product
     */
    public function update(Request $request, $warehouseId, $productId)
    {
        $validated = $request->validate([
            'physical_quantity' => 'required|numeric|min:0',
            'reserved_quantity' => 'required|numeric|min:0',
            'reorder_point' => 'required|numeric|min:0',
            'reorder_quantity' => 'required|numeric|min:0',
            'average_cost' => 'nullable|numeric|min:0',
            'reason_category' => 'required|string|in:audit,correction,received,returned,damaged,adjustment,other',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $stock = $this->resolveWarehouseStock((int) $warehouseId, (int) $productId);

            // Store old values for logging
            $oldValues = [
                'physical_quantity' => $stock->physical_quantity,
                'reserved_quantity' => $stock->reserved_quantity,
                'reorder_point' => $stock->reorder_point,
                'reorder_quantity' => $stock->reorder_quantity,
                'average_cost' => $stock->average_cost,
            ];

            // Update the stock
            $averageCost = $validated['average_cost'] ?? $stock->average_cost;
            $newAvailableQuantity = max((float) $validated['physical_quantity'] - (float) $validated['reserved_quantity'], 0);
            $stock->update([
                'physical_quantity' => $validated['physical_quantity'],
                'reserved_quantity' => $validated['reserved_quantity'],
                'available_quantity' => $newAvailableQuantity,
                'reorder_point' => $validated['reorder_point'],
                'reorder_quantity' => $validated['reorder_quantity'],
                'average_cost' => $averageCost,
                'total_value' => (float) $validated['physical_quantity'] * (float) $averageCost,
            ]);

            $movementQty = (float) $validated['physical_quantity'] - (float) $oldValues['physical_quantity'];

            if ($movementQty !== 0.0) {
                // Log stock delta as an adjustment movement.
                StockMovement::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'type' => $movementQty > 0 ? 'adjustment_in' : 'adjustment_out',
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => null,
                    'quantity' => $movementQty,
                    'unit_cost' => $averageCost,
                    'notes' => $validated['reason'].' ('.$validated['reason_category'].')',
                    'created_by' => auth()->id(),
                ]);
            }

            Toastr::success('Stock details updated successfully', 'Success');

            return redirect()->route('admin.stock.show', [$warehouseId, $productId]);

        } catch (\Exception $e) {
            Toastr::error('Failed to update stock details: '.$e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Quick adjust stock for a single product
     */
    public function quickAdjust(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|integer',
            'adjustment_type' => 'required|in:set,add,subtract',
            'physical_quantity' => 'required|numeric|min:0',
            'reason_category' => 'required|string|in:adjustment,correction,audit,received,returned,damaged,other',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $warehouseId = (int) $validated['warehouse_id'];
            $productId = (int) $validated['product_id'];
            $variantId = (int) ($validated['variant_id'] ?? 0);
            $adjustmentType = (string) $validated['adjustment_type'];
            $inputQuantity = (float) $validated['physical_quantity'];
            $reasonCategory = (string) $validated['reason_category'];
            $reason = trim((string) $validated['reason']);

            if ($variantId > 0) {
                $variant = ProductVariant::query()
                    ->where('id', $variantId)
                    ->where('product_id', $productId)
                    ->where('status', 'active')
                    ->first();

                if (! $variant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected variant is invalid for this product.',
                    ], 422);
                }

                $inventory = Inventory::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->first();

                $oldQuantity = (float) ($inventory->quantity_available ?? 0);
                $reservedQuantity = (float) ($inventory->quantity_reserved ?? 0);

                $targetQuantity = $oldQuantity;
                if ($adjustmentType === 'set') {
                    $targetQuantity = $inputQuantity;
                } elseif ($adjustmentType === 'add') {
                    $targetQuantity = $oldQuantity + $inputQuantity;
                } elseif ($adjustmentType === 'subtract') {
                    $targetQuantity = max(0, $oldQuantity - $inputQuantity);
                }

                if ($targetQuantity < $reservedQuantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot set stock below currently reserved quantity.',
                    ], 422);
                }

                $adjustment = $targetQuantity - $oldQuantity;
                if (abs($adjustment) > 0.000001) {
                    $variantLabel = $this->formatVariantLabel($variant);
                    $this->variantStockService->adjustStock(
                        warehouseId: $warehouseId,
                        variantId: $variantId,
                        quantity: $adjustment,
                        reason: $reason.' ('.$reasonCategory.') | Variant: '.$variantLabel
                    );
                }

                $updatedInventory = Inventory::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Variant stock adjusted successfully',
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => (float) ($updatedInventory->quantity_available ?? $targetQuantity),
                    'variant_id' => $variantId,
                    'variant_label' => $this->formatVariantLabel($variant),
                ]);
            }

            if ($this->productRequiresVariant($productId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a variant for this product.',
                    'errors' => [
                        'variant_id' => ['Variant is required for this product.'],
                    ],
                ], 422);
            }

            $warehouseStock = $this->resolveWarehouseStock(
                $warehouseId,
                $productId
            );

            $oldQuantity = (float) $warehouseStock->physical_quantity;
            $targetQuantity = $oldQuantity;

            switch ($adjustmentType) {
                case 'set':
                    $targetQuantity = $inputQuantity;
                    break;
                case 'add':
                    $targetQuantity = $oldQuantity + $inputQuantity;
                    break;
                case 'subtract':
                    $targetQuantity = max(0, $oldQuantity - $inputQuantity);
                    break;
            }

            if ($targetQuantity < (float) $warehouseStock->reserved_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot set stock below currently reserved quantity.',
                ], 422);
            }

            $warehouseStock->physical_quantity = $targetQuantity;

            // Update available quantity (assuming no reservations for quick adjust)
            $warehouseStock->available_quantity = $warehouseStock->physical_quantity - $warehouseStock->reserved_quantity;
            if ($warehouseStock->available_quantity < 0) {
                $warehouseStock->available_quantity = 0;
            }
            $warehouseStock->total_value = $warehouseStock->physical_quantity * ($warehouseStock->average_cost ?? 0);

            $warehouseStock->save();

            $movementQty = (float) $warehouseStock->physical_quantity - (float) $oldQuantity;

            if (abs($movementQty) > 0.000001) {
                // Log the movement
                StockMovement::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'type' => $movementQty > 0 ? 'adjustment_in' : 'adjustment_out',
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => null,
                    'quantity' => $movementQty,
                    'unit_cost' => $warehouseStock->average_cost ?? 0,
                    'notes' => $reason.' ('.$reasonCategory.')',
                    'created_by' => auth()->id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'old_quantity' => $oldQuantity,
                'new_quantity' => (float) $warehouseStock->physical_quantity,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk adjust stock for multiple products
     */
    public function bulkAdjust(Request $request)
    {
        if (is_string($request->input('product_ids'))) {
            $decoded = json_decode((string) $request->input('product_ids'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['product_ids' => $decoded]);
            }
        }

        if (is_string($request->input('items'))) {
            $decoded = json_decode((string) $request->input('items'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['items' => $decoded]);
            }
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'adjustment_type' => 'required|in:set,add,subtract',
            'quantity' => 'nullable|numeric|min:0',
            'reason_category' => 'required|string|in:adjustment,correction,audit,received,returned,damaged,other',
            'reason' => 'required|string|max:1000',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity' => 'nullable|numeric|min:0',
        ]);

        try {
            $successCount = 0;
            $errors = [];
            $warehouseId = (int) $validated['warehouse_id'];
            $adjustmentType = (string) $validated['adjustment_type'];
            $reasonCategory = (string) $validated['reason_category'];
            $reason = trim((string) $validated['reason']);
            $defaultQuantity = array_key_exists('quantity', $validated) && $validated['quantity'] !== null
                ? (float) $validated['quantity']
                : null;

            $items = $validated['items'] ?? [];
            if (empty($items)) {
                $productIds = $validated['product_ids'] ?? [];
                foreach ($productIds as $productId) {
                    $items[] = [
                        'product_id' => (int) $productId,
                        'variant_id' => null,
                        'quantity' => $defaultQuantity,
                    ];
                }
            }

            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products selected for bulk adjustment.',
                ], 422);
            }

            foreach ($items as $item) {
                try {
                    $productId = (int) ($item['product_id'] ?? 0);
                    $variantId = (int) ($item['variant_id'] ?? 0);
                    $itemQuantity = array_key_exists('quantity', $item) && $item['quantity'] !== null
                        ? (float) $item['quantity']
                        : $defaultQuantity;

                    if ($productId <= 0) {
                        throw new \RuntimeException('Invalid product selected.');
                    }

                    if ($itemQuantity === null) {
                        throw new \RuntimeException("Quantity is required for product ID {$productId}.");
                    }

                    if ($itemQuantity < 0) {
                        throw new \RuntimeException("Quantity cannot be negative for product ID {$productId}.");
                    }

                    if ($variantId > 0) {
                        $variant = ProductVariant::query()
                            ->where('id', $variantId)
                            ->where('product_id', $productId)
                            ->where('status', 'active')
                            ->first();

                        if (! $variant) {
                            throw new \RuntimeException("Invalid variant selected for product ID {$productId}.");
                        }

                        $inventory = Inventory::query()
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_variant_id', $variantId)
                            ->first();

                        $oldQuantity = (float) ($inventory->quantity_available ?? 0);
                        $reservedQuantity = (float) ($inventory->quantity_reserved ?? 0);
                        $targetQuantity = $oldQuantity;

                        if ($adjustmentType === 'set') {
                            $targetQuantity = $itemQuantity;
                        } elseif ($adjustmentType === 'add') {
                            $targetQuantity = $oldQuantity + $itemQuantity;
                        } elseif ($adjustmentType === 'subtract') {
                            $targetQuantity = max(0, $oldQuantity - $itemQuantity);
                        }

                        if ($targetQuantity < $reservedQuantity) {
                            throw new \RuntimeException("Cannot set variant stock below reserved quantity for product ID {$productId}.");
                        }

                        $adjustment = $targetQuantity - $oldQuantity;
                        if (abs($adjustment) > 0.000001) {
                            $variantLabel = $this->formatVariantLabel($variant);
                            $this->variantStockService->adjustStock(
                                warehouseId: $warehouseId,
                                variantId: $variantId,
                                quantity: $adjustment,
                                reason: $reason.' ('.$reasonCategory.') - Bulk operation | Variant: '.$variantLabel
                            );
                        }

                        $successCount++;

                        continue;
                    }

                    if ($this->productRequiresVariant($productId)) {
                        throw new \RuntimeException("Variant is required for product ID {$productId}.");
                    }

                    $warehouseStock = $this->resolveWarehouseStock($warehouseId, $productId);

                    $oldQuantity = (float) $warehouseStock->physical_quantity;
                    $targetQuantity = $oldQuantity;

                    if ($adjustmentType === 'set') {
                        $targetQuantity = $itemQuantity;
                    } elseif ($adjustmentType === 'add') {
                        $targetQuantity = $oldQuantity + $itemQuantity;
                    } elseif ($adjustmentType === 'subtract') {
                        $targetQuantity = max(0, $oldQuantity - $itemQuantity);
                    }

                    if ($targetQuantity < (float) $warehouseStock->reserved_quantity) {
                        throw new \RuntimeException("Cannot set stock below reserved quantity for product ID {$productId}.");
                    }

                    $warehouseStock->physical_quantity = $targetQuantity;
                    $warehouseStock->available_quantity = max($warehouseStock->physical_quantity - $warehouseStock->reserved_quantity, 0);
                    $warehouseStock->total_value = $warehouseStock->physical_quantity * ((float) ($warehouseStock->average_cost ?? 0));
                    $warehouseStock->save();

                    $movementQty = (float) $warehouseStock->physical_quantity - $oldQuantity;
                    if (abs($movementQty) > 0.000001) {
                        StockMovement::create([
                            'warehouse_id' => $warehouseId,
                            'product_id' => $productId,
                            'type' => $movementQty > 0 ? 'adjustment_in' : 'adjustment_out',
                            'reference_type' => 'stock_adjustment',
                            'reference_id' => null,
                            'quantity' => $movementQty,
                            'unit_cost' => $warehouseStock->average_cost ?? 0,
                            'notes' => $reason.' ('.$reasonCategory.') - Bulk operation',
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $successCount++;
                } catch (\Throwable $e) {
                    $errors[] = "Product ID {$productId}: ".$e->getMessage();
                }
            }

            $message = "Successfully adjusted {$successCount} products";
            if (! empty($errors)) {
                $message .= '. Errors: '.implode(', ', $errors);
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => $message,
                'success_count' => $successCount,
                'error_count' => count($errors),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk adjustment failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bulk products data for bulk operations
     */
    public function bulkProducts(Request $request)
    {
        try {
            $query = Product::with(['category', 'warehouseStocks' => function ($q) use ($request) {
                if ($request->warehouse_id) {
                    $q->where('warehouse_id', $request->warehouse_id);
                }
            }])
                ->where('status', 1);

            // Search filter
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('product_code', 'like', "%{$search}%");
                });
            }

            $products = $query->paginate(50);

            $data = $products->map(function ($product) use ($request) {
                $warehouseStock = null;
                if ($request->warehouse_id) {
                    $warehouseStock = $product->warehouseStocks->first();
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'product_code' => $product->product_code,
                    'category' => $product->category ? $product->category->name : 'N/A',
                    'current_stock' => $warehouseStock ? $warehouseStock->available_quantity : 0,
                    'physical_stock' => $warehouseStock ? $warehouseStock->physical_quantity : 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load products: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get warehouse stock for a specific product/variant (JSON API)
     */
    public function getProductStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'variant_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $productId = (int) $validator->validated()['product_id'];
            $warehouseId = (int) $validator->validated()['warehouse_id'];
            $variantId = $request->filled('variant_id') ? (int) $request->variant_id : 0;

            $product = Product::query()->select('id', 'name')->find($productId);
            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $warehouse = Warehouse::query()->select('id', 'name')->find($warehouseId);
            if (! $warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found',
                ], 404);
            }

            if ($variantId > 0) {
                $variant = ProductVariant::query()
                    ->where('id', $variantId)
                    ->where('product_id', $productId)
                    ->where('status', 'active')
                    ->first();

                if (! $variant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected variant is invalid for the selected product.',
                    ], 422);
                }

                $inventory = Inventory::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->first();

                $physicalQuantity = (float) ($inventory->quantity_available ?? 0);
                $reservedQuantity = (float) ($inventory->quantity_reserved ?? 0);
                $availableQuantity = max(0, $physicalQuantity - $reservedQuantity);

                return response()->json([
                    'success' => true,
                    'product_name' => (string) $product->name,
                    'warehouse_name' => (string) $warehouse->name,
                    'data' => [
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                        'variant_id' => $variantId,
                        'variant_label' => $this->formatVariantLabel($variant),
                        'physical_quantity' => $physicalQuantity,
                        'available_quantity' => $availableQuantity,
                        'reserved_quantity' => $reservedQuantity,
                        'reorder_point' => (float) ($inventory->reorder_level ?? 5),
                    ],
                ]);
            }

            $stock = WarehouseStock::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            return response()->json([
                'success' => true,
                'product_name' => (string) $product->name,
                'warehouse_name' => (string) $warehouse->name,
                'data' => [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'variant_id' => null,
                    'variant_label' => null,
                    'physical_quantity' => (float) ($stock->physical_quantity ?? 0),
                    'available_quantity' => (float) ($stock->available_quantity ?? 0),
                    'reserved_quantity' => (float) ($stock->reserved_quantity ?? 0),
                    'reorder_point' => (float) ($stock->reorder_point ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load stock: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active variants for a product (JSON API)
     */
    public function getProductVariants(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $productId = (int) $validator->validated()['product_id'];
        $product = Product::query()
            ->select('id', 'name', 'has_variant', 'sku', 'product_code', 'new_price', 'purchase_price')
            ->with('image')
            ->find($productId);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $payload = $this->variantAttributeService->buildProductVariantPayload($product);

        if (empty($payload['variants'])) {
            $this->syncLegacyVariants($product);
            $payload = $this->variantAttributeService->buildProductVariantPayload($product->fresh(['image']));
        }

        return response()->json([
            'success' => true,
            'product_name' => (string) $product->name,
            'has_variant' => (bool) ($product->has_variant || ! empty($payload['variants'])),
            'has_dynamic_attributes' => (bool) ($payload['has_dynamic_attributes'] ?? false),
            'attribute_groups' => $payload['attribute_groups'] ?? [],
            'variants' => collect($payload['variants'] ?? [])->map(function ($variant) {
                return [
                    'id' => (int) ($variant['id'] ?? 0),
                    'product_id' => (int) ($variant['product_id'] ?? 0),
                    'sku_code' => (string) ($variant['sku_code'] ?? ''),
                    'color' => (string) ($variant['color'] ?? ''),
                    'size' => (string) ($variant['size'] ?? ''),
                    'age' => (string) ($variant['age'] ?? ''),
                    'label' => (string) ($variant['label'] ?? ''),
                    'price' => (float) ($variant['price'] ?? 0),
                    'cost_price' => (float) ($variant['cost_price'] ?? 0),
                    'image' => (string) ($variant['image'] ?? ''),
                    'sellable_stock' => (float) ($variant['sellable_stock'] ?? 0),
                    'attributes' => $variant['attributes'] ?? [],
                    'attribute_values' => $variant['attribute_values'] ?? [],
                ];
            })->values(),
        ]);
    }

    /**
     * Build product_variants from legacy productcolors/productsizes/productages rows.
     */
    private function syncLegacyVariants(Product $product): void
    {
        $productId = (int) $product->id;

        $colors = Productcolor::query()
            ->where('product_id', $productId)
            ->with('color:id,colorName')
            ->get()
            ->map(fn ($row) => trim((string) ($row->color->colorName ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $sizes = Productsize::query()
            ->where('product_id', $productId)
            ->with('size:id,sizeName')
            ->get()
            ->map(fn ($row) => trim((string) ($row->size->sizeName ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $ages = $this->hasLegacyAgeTable()
            ? Productage::query()
                ->where('product_id', $productId)
                ->with('age:id,ageName')
                ->get()
                ->map(fn ($row) => trim((string) ($row->age->ageName ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [];

        if (empty($colors) && empty($sizes) && empty($ages)) {
            return;
        }

        $supportsVariantAge = $this->hasVariantAgeColumn();
        $colorOptions = empty($colors) ? [null] : $colors;
        $sizeOptions = empty($sizes) ? [null] : $sizes;
        $ageOptions = ($supportsVariantAge && ! empty($ages)) ? $ages : [null];

        $combinations = [];
        foreach ($colorOptions as $color) {
            foreach ($sizeOptions as $size) {
                foreach ($ageOptions as $age) {
                    $combinations[] = [
                        'color' => $color,
                        'size' => $size,
                        'age' => $age,
                    ];
                }
            }
        }

        foreach ($combinations as $combo) {
            $existingQuery = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('color', $combo['color'])
                ->where('size', $combo['size']);
            if ($supportsVariantAge) {
                $existingQuery->where('age', $combo['age']);
            }

            $existing = $existingQuery->first();

            if ($existing) {
                if ((string) $existing->status !== 'active') {
                    $existing->status = 'active';
                    $existing->save();
                }

                $valueIds = $this->upsertLegacyVariantAttributeMappings(
                    $existing,
                    $combo['color'],
                    $combo['size'],
                    $supportsVariantAge ? $combo['age'] : null
                );
                if (Schema::hasColumn('product_variants', 'combination_key')) {
                    $combinationKey = $this->variantAttributeService->buildCombinationKey($valueIds);
                    if ($existing->combination_key !== $combinationKey) {
                        $existing->combination_key = $combinationKey;
                        $existing->save();
                    }
                }

                continue;
            }

            $skuBase = $this->buildVariantSkuBase(
                $product,
                $combo['color'],
                $combo['size'],
                $supportsVariantAge ? $combo['age'] : null
            );
            $skuCode = $this->ensureUniqueVariantSku($skuBase);

            $variantPayload = [
                'product_id' => $productId,
                'sku_code' => $skuCode,
                'color' => $combo['color'],
                'size' => $combo['size'],
                'price' => (float) ($product->new_price ?? 0),
                'cost_price' => (float) ($product->purchase_price ?? 0),
                'status' => 'active',
            ];
            if ($supportsVariantAge) {
                $variantPayload['age'] = $combo['age'];
            }

            $variant = ProductVariant::query()->create($variantPayload);

            $valueIds = $this->upsertLegacyVariantAttributeMappings(
                $variant,
                $combo['color'],
                $combo['size'],
                $supportsVariantAge ? $combo['age'] : null
            );

            if (Schema::hasColumn('product_variants', 'combination_key')) {
                $variant->combination_key = $this->variantAttributeService->buildCombinationKey($valueIds);
                $variant->save();
            }
        }

        if (! $product->has_variant) {
            $product->has_variant = true;
            $product->save();
        }
    }

    private function buildVariantSkuBase(Product $product, ?string $color, ?string $size, ?string $age): string
    {
        $base = trim((string) ($product->sku ?? $product->product_code ?? ('P'.$product->id)));
        $suffixParts = array_filter([
            $color ? Str::upper(Str::slug($color, '-')) : null,
            $size ? Str::upper(Str::slug($size, '-')) : null,
            $age ? Str::upper(Str::slug($age, '-')) : null,
        ]);

        return ! empty($suffixParts)
            ? Str::upper($base.'-'.implode('-', $suffixParts))
            : Str::upper($base.'-VAR');
    }

    private function ensureUniqueVariantSku(string $baseSku): string
    {
        $candidate = $baseSku;
        $counter = 1;

        while (ProductVariant::query()->where('sku_code', $candidate)->exists()) {
            $counter++;
            $candidate = $baseSku.'-'.$counter;
        }

        return $candidate;
    }

    /**
     * @return int[]
     */
    private function upsertLegacyVariantAttributeMappings(
        ProductVariant $variant,
        ?string $color,
        ?string $size,
        ?string $age
    ): array {
        if (
            ! Schema::hasTable('catalog_attributes')
            || ! Schema::hasTable('catalog_attribute_values')
            || ! Schema::hasTable('product_variant_attribute_values')
        ) {
            return [];
        }

        $valueIds = [];

        $colorValueId = $this->resolveCatalogAttributeValueId('Color', 'color', $color, null, 10);
        if ($colorValueId > 0) {
            $valueIds[] = $colorValueId;
        }

        $sizeValueId = $this->resolveCatalogAttributeValueId('Size', 'size', $size, null, 20);
        if ($sizeValueId > 0) {
            $valueIds[] = $sizeValueId;
        }

        if ($this->hasVariantAgeColumn()) {
            $ageValueId = $this->resolveCatalogAttributeValueId('Age', 'age', $age, null, 30);
            if ($ageValueId > 0) {
                $valueIds[] = $ageValueId;
            }
        }

        $valueIds = $this->variantAttributeService->normalizeValueIds($valueIds);
        if (empty($valueIds)) {
            ProductVariantAttributeValue::query()
                ->where('product_variant_id', $variant->id)
                ->delete();

            return [];
        }

        $valueRows = CatalogAttributeValue::query()
            ->whereIn('id', $valueIds)
            ->get(['id', 'catalog_attribute_id']);

        ProductVariantAttributeValue::query()
            ->where('product_variant_id', $variant->id)
            ->delete();

        foreach ($valueRows as $valueRow) {
            ProductVariantAttributeValue::query()->create([
                'product_variant_id' => (int) $variant->id,
                'catalog_attribute_id' => (int) $valueRow->catalog_attribute_id,
                'catalog_attribute_value_id' => (int) $valueRow->id,
            ]);
        }

        return $valueIds;
    }

    private function resolveCatalogAttributeValueId(
        string $attributeName,
        string $attributeSlug,
        ?string $rawValue,
        ?array $meta,
        int $defaultSortOrder
    ): int {
        $value = trim((string) ($rawValue ?? ''));
        if ($value === '') {
            return 0;
        }

        $attribute = CatalogAttribute::query()->firstOrCreate(
            ['slug' => $attributeSlug],
            [
                'name' => $attributeName,
                'sort_order' => $defaultSortOrder,
                'status' => true,
                'is_required' => false,
            ]
        );

        $existing = CatalogAttributeValue::query()
            ->where('catalog_attribute_id', (int) $attribute->id)
            ->whereRaw('LOWER(value) = ?', [Str::lower($value)])
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $baseSlug = Str::slug($value);
        if ($baseSlug === '') {
            $baseSlug = 'value-'.$attribute->id;
        }
        $slug = $baseSlug;
        $counter = 1;

        while (
            CatalogAttributeValue::query()
                ->where('catalog_attribute_id', (int) $attribute->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $counter++;
            $slug = $baseSlug.'-'.$counter;
        }

        $payload = [
            'catalog_attribute_id' => (int) $attribute->id,
            'value' => $value,
            'slug' => $slug,
            'status' => true,
            'sort_order' => 999,
        ];

        if (is_array($meta) && ! empty($meta)) {
            $payload['meta'] = $meta;
        }

        $created = CatalogAttributeValue::query()->create($payload);

        return (int) $created->id;
    }

    private function productRequiresVariant(int $productId): bool
    {
        $hasActiveVariants = ProductVariant::query()
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveVariants) {
            return true;
        }

        return Productcolor::query()->where('product_id', $productId)->exists()
            || Productsize::query()->where('product_id', $productId)->exists()
            || ($this->hasLegacyAgeTable() && Productage::query()->where('product_id', $productId)->exists());
    }

    private function formatVariantLabel(ProductVariant $variant): string
    {
        return $this->variantAttributeService->formatVariantLabel(
            $variant->loadMissing('variantAttributeValues.attribute', 'variantAttributeValues.value')
        );
    }

    private function hasLegacyAgeTable(): bool
    {
        static $hasLegacyAgeTable = null;

        if ($hasLegacyAgeTable === null) {
            $hasLegacyAgeTable = Schema::hasTable((new Productage)->getTable());
        }

        return (bool) $hasLegacyAgeTable;
    }

    private function hasVariantAgeColumn(): bool
    {
        static $hasVariantAgeColumn = null;

        if ($hasVariantAgeColumn === null) {
            $table = (new ProductVariant)->getTable();
            $hasVariantAgeColumn = Schema::hasTable($table) && Schema::hasColumn($table, 'age');
        }

        return (bool) $hasVariantAgeColumn;
    }

    /**
     * Resolve warehouse stock row for a warehouse/product pair.
     * Creates a default row when missing so stock actions can proceed from set view.
     */
    private function resolveWarehouseStock(int $warehouseId, int $productId): WarehouseStock
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);
        $product = Product::query()->findOrFail($productId);

        $sku = (string) ($product->sku ?: ($product->product_code ?: ('SKU-'.$product->id)));
        $reorderPoint = max(0, (float) ($product->reorder_point ?? 0));

        // Get the first product variant for this product, or create one if missing
        $firstVariant = ProductVariant::query()
            ->where('product_id', $productId)
            ->orderBy('id')
            ->first();

        if (! $firstVariant) {
            $firstVariant = ProductVariant::create([
                'product_id' => $productId,
                'sku_code' => $sku,
                'status' => 'active',
            ]);
        }

        return WarehouseStock::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
            ],
            [
                'product_variant_id' => $firstVariant->id,
                'branch_id' => (int) ($warehouse->branch_id ?? 1),
                'sku' => $sku,
                'physical_quantity' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
                'reorder_point' => $reorderPoint,
                'reorder_quantity' => 0,
                'average_cost' => 0,
                'total_value' => 0,
            ]
        );
    }
}
