<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttribute;
use App\Models\CatalogAttributeValue;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Productage;
use App\Models\Productcolor;
use App\Models\Productsize;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\SupplierService;
use App\Services\VariantAttributeService;
use App\Services\VariantStockService;
use App\Services\WarehouseStockService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * SIMPLIFIED INVENTORY CONTROLLER
 *
 * 5 Core Operations:
 * 1. View Inventory (All stock across warehouses)
 * 2. Add Stock (GRN - Goods Receipt)
 * 3. Adjust Stock (Corrections/Adjustments)
 * 4. Transfer Stock (Between warehouses)
 * 5. View History (Stock movements audit trail)
 */
class InventoryController extends Controller
{
    private const MAX_HISTORY_RANGE_DAYS = 730;

    protected WarehouseStockService $stockService;

    protected VariantStockService $variantStockService;

    protected VariantAttributeService $variantAttributeService;

    public function __construct(
        WarehouseStockService $stockService,
        VariantStockService $variantStockService,
        VariantAttributeService $variantAttributeService
    ) {
        $this->middleware('permission:inventory-view', ['only' => ['index', 'history', 'getProductStock', 'getWarehouseProducts', 'getProductVariants']]);
        $this->middleware('permission:inventory-add', ['only' => ['addStock', 'storeAddStock', 'searchProductsForGrn']]);
        $this->middleware('permission:inventory-adjust', ['only' => ['adjustStock', 'storeAdjustStock', 'quickAdjust']]);
        $this->middleware('permission:inventory-transfer', ['only' => ['transferStock', 'storeTransferStock']]);

        $this->stockService = $stockService;
        $this->variantStockService = $variantStockService;
        $this->variantAttributeService = $variantAttributeService;
    }

    /**
     * 1. VIEW INVENTORY - List all products with stock across warehouses
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'stock_status' => 'nullable|in:in_stock,low_stock,out_of_stock',
        ]);

        $warehouseId = isset($filters['warehouse_id']) ? (int) $filters['warehouse_id'] : null;
        $search = trim((string) ($filters['search'] ?? ''));
        $stockStatus = $filters['stock_status'] ?? null;

        $query = WarehouseStock::query()
            ->with([
                'warehouse',
                'product.image',
            ])
            ->whereHas('warehouse', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('product', function ($q) {
                $q->where('status', 1);
            });

        // Search by product name or SKU
        if ($search !== '') {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where(function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            });
        }

        // Filter by warehouse
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Filter by status
        if ($stockStatus) {
            switch ($stockStatus) {
                case 'in_stock':
                    $query->where('available_quantity', '>', 0);
                    break;
                case 'low_stock':
                    $query->whereRaw('available_quantity > 0 AND available_quantity <= reorder_point');
                    break;
                case 'out_of_stock':
                    $query->where('available_quantity', '<=', 0);
                    break;
            }
        }

        // One-page mode: disable pagination (show all results at once)
        $stocks = $query
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('admin.inventory.index', compact('stocks', 'warehouses'));
    }

    /**
     * 2. ADD STOCK - Receive goods from suppliers (GRN)
     */
    public function addStock()
    {
        $warehouses = Warehouse::active()->get();
        $suppliers = Supplier::active()
            ->orderBy('name')
            ->get(['id', 'supplier_code', 'name']);

        return view('admin.inventory.add-stock', compact('warehouses', 'suppliers'));
    }

    /**
     * Select2 / AJAX product lookup for Goods Receipt (keyword: name, SKU, code, variant SKU).
     */
    public function searchProductsForGrn(Request $request): JsonResponse
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

        $query = Product::query()
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
            ->limit($limit);

        $products = $query->get(['id', 'name', 'sku']);

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
     * Store new stock (GRN - Goods Receipt Note)
     */
    public function storeAddStock(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'grn_number' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:0.01|max:999999',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $items = collect($validated['items'])->values();
        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id')->all())
            ->get(['id', 'name', 'has_variant'])
            ->keyBy('id');

        $variantIds = $items
            ->pluck('variant_id')
            ->filter(fn ($id) => ! is_null($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->where('status', 'active')
            ->get(['id', 'product_id', 'sku_code', 'color', 'size', 'age'])
            ->keyBy('id');

        $productsWithActiveVariants = ProductVariant::query()
            ->selectRaw('DISTINCT product_id')
            ->whereIn('product_id', $items->pluck('product_id')->all())
            ->where('status', 'active')
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $productsWithLegacyAttributes = Productcolor::query()
            ->whereIn('product_id', $items->pluck('product_id')->all())
            ->distinct()
            ->pluck('product_id')
            ->merge(
                Productsize::query()
                    ->whereIn('product_id', $items->pluck('product_id')->all())
                    ->distinct()
                    ->pluck('product_id')
            )
            ->merge(
                $this->hasLegacyAgeTable()
                    ? Productage::query()
                        ->whereIn('product_id', $items->pluck('product_id')->all())
                        ->distinct()
                        ->pluck('product_id')
                    : collect()
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $validationErrors = [];
        foreach ($items as $index => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : 0;

            $product = $products->get($productId);
            if (! $product) {
                $validationErrors["items.{$index}.product_id"] = 'Selected product is unavailable.';

                continue;
            }

            $requiresVariant = in_array($productId, $productsWithActiveVariants, true)
                || in_array($productId, $productsWithLegacyAttributes, true);
            if ($requiresVariant && $variantId <= 0) {
                $validationErrors["items.{$index}.variant_id"] = "Variant is required for {$product->name}.";

                continue;
            }

            if ($variantId > 0) {
                $variant = $variants->get($variantId);
                if (! $variant || (int) $variant->product_id !== $productId) {
                    $validationErrors["items.{$index}.variant_id"] = 'Selected variant is invalid for the chosen product.';
                }
            }
        }

        if (! empty($validationErrors)) {
            throw ValidationException::withMessages($validationErrors);
        }

        $supplierLedgerFailed = false;
        $supplierLedgerMessage = '';

        try {
            DB::transaction(function () use ($validated, $items, $products, $variants, &$supplierLedgerFailed, &$supplierLedgerMessage) {
                $totalPurchaseAmount = 0.0;
                $supplierId = (int) ($validated['supplier_id'] ?? 0);
                $supplier = $supplierId > 0 ? Supplier::query()->find($supplierId) : null;
                $grnNumber = trim((string) ($validated['grn_number'] ?? ''));

                foreach ($items as $item) {
                    $product = $products->get((int) $item['product_id']);
                    if (! $product) {
                        throw new \RuntimeException('One or more selected products are unavailable.');
                    }

                    $quantity = (float) $item['quantity'];
                    $unitCost = (float) ($item['unit_cost'] ?? 0);
                    $totalPurchaseAmount += round($quantity * $unitCost, 2);

                    $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : 0;
                    if ($variantId > 0) {
                        $variant = $variants->get($variantId);
                        $variantLabelParts = array_filter([
                            (string) ($variant?->color ?? ''),
                            (string) ($variant?->size ?? ''),
                            (string) ($variant?->age ?? ''),
                        ]);
                        $variantLabel = empty($variantLabelParts) ? ('Variant #'.$variantId) : implode(' / ', $variantLabelParts);

                        $this->variantStockService->addStock(
                            warehouseId: (int) $validated['warehouse_id'],
                            variantId: $variantId,
                            quantity: $quantity,
                            unitCost: $unitCost,
                            referenceId: 0,
                            notes: 'Goods Receipt - '.$product->name.' ('.$variantLabel.')'
                        );

                        continue;
                    }

                    $this->stockService->increaseStock(
                        warehouseId: (int) $validated['warehouse_id'],
                        productId: (int) $item['product_id'],
                        quantity: $quantity,
                        unitCost: $unitCost,
                        referenceType: 'grn',
                        referenceId: 0,
                        notes: 'Goods Receipt - '.$product->name
                    );
                }

                // Optional supplier linkage — failures must not roll back received stock.
                if ($supplier && $totalPurchaseAmount > 0) {
                    $purchasePayload = [
                        'amount' => round($totalPurchaseAmount, 2),
                        'purchase_date' => now()->toDateString(),
                        'purchase_number' => $grnNumber !== '' ? $grnNumber : null,
                        'reference_type' => 'inventory_grn',
                        'description' => $grnNumber !== ''
                            ? "Inventory GRN receipt #{$grnNumber}"
                            : 'Inventory GRN receipt',
                        'created_by' => (int) (auth()->id()
                            ?? \App\Models\User::query()->value('id')
                            ?? 0),
                    ];

                    try {
                        app(SupplierService::class)->recordPurchase($supplier, $purchasePayload);
                    } catch (Throwable $ledgerException) {
                        report($ledgerException);
                        $supplierLedgerFailed = true;
                        $supplierLedgerMessage = $ledgerException->getMessage();
                    }
                }
            });

            Toastr::success('Stock added successfully ('.count($items).' items)', 'Success');
            if ($supplierLedgerFailed) {
                Toastr::warning(
                    'Stock was saved. Supplier payable could not be recorded'
                        .($supplierLedgerMessage !== '' ? ': '.$supplierLedgerMessage : '.')
                        .' You can fix this in accounts or retry the ledger entry.',
                    'Supplier ledger'
                );
            }

            return redirect()->route('admin.inventory.index');

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $detail = $e->getMessage();
            $safeDetail = $detail !== '' ? mb_substr($detail, 0, 420) : '';
            Toastr::error(
                $safeDetail !== ''
                    ? 'Failed to add stock: '.$safeDetail
                    : 'Failed to add stock. Please try again.',
                'Error'
            );

            return back()->withInput();
        }
    }

    /**
     * 3. ADJUST STOCK - Correct inventory (Damage, Shortage, Expiry)
     */
    public function adjustStock()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('admin.inventory.adjust-stock', compact('warehouses', 'products'));
    }

    /**
     * Store stock adjustment
     */
    public function storeAdjustStock(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'adjustment_type' => 'required|in:damage,shortage,expiry,other',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:-999999|max:999999',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $nonZeroItems = collect($validated['items'])
            ->filter(fn ($item) => (float) ($item['quantity'] ?? 0) !== 0.0)
            ->values();

        if ($nonZeroItems->isEmpty()) {
            return back()
                ->withErrors(['items' => 'At least one adjustment item must have a non-zero quantity.'])
                ->withInput();
        }

        $products = Product::query()
            ->whereIn('id', $nonZeroItems->pluck('product_id')->all())
            ->get(['id', 'name', 'has_variant'])
            ->keyBy('id');

        $variantIds = $nonZeroItems
            ->pluck('variant_id')
            ->filter(fn ($id) => ! is_null($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->where('status', 'active')
            ->get(['id', 'product_id', 'sku_code', 'color', 'size', 'age'])
            ->keyBy('id');

        $productsWithActiveVariants = ProductVariant::query()
            ->selectRaw('DISTINCT product_id')
            ->whereIn('product_id', $nonZeroItems->pluck('product_id')->all())
            ->where('status', 'active')
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $productsWithLegacyAttributes = Productcolor::query()
            ->whereIn('product_id', $nonZeroItems->pluck('product_id')->all())
            ->distinct()
            ->pluck('product_id')
            ->merge(
                Productsize::query()
                    ->whereIn('product_id', $nonZeroItems->pluck('product_id')->all())
                    ->distinct()
                    ->pluck('product_id')
            )
            ->merge(
                $this->hasLegacyAgeTable()
                    ? Productage::query()
                        ->whereIn('product_id', $nonZeroItems->pluck('product_id')->all())
                        ->distinct()
                        ->pluck('product_id')
                    : collect()
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $validationErrors = [];
        foreach ($nonZeroItems as $index => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : 0;

            $product = $products->get($productId);
            if (! $product) {
                $validationErrors["items.{$index}.product_id"] = 'Selected product is unavailable.';

                continue;
            }

            $requiresVariant = in_array($productId, $productsWithActiveVariants, true)
                || in_array($productId, $productsWithLegacyAttributes, true);
            if ($requiresVariant && $variantId <= 0) {
                $validationErrors["items.{$index}.variant_id"] = "Variant is required for {$product->name}.";

                continue;
            }

            if ($variantId > 0) {
                $variant = $variants->get($variantId);
                if (! $variant || (int) $variant->product_id !== $productId) {
                    $validationErrors["items.{$index}.variant_id"] = 'Selected variant is invalid for the chosen product.';
                }
            }
        }

        if (! empty($validationErrors)) {
            throw ValidationException::withMessages($validationErrors);
        }

        try {
            DB::transaction(function () use ($validated, $nonZeroItems, $variants) {
                $reasonDetails = trim((string) ($validated['reason'] ?? ''));
                $notesDetails = trim((string) ($validated['notes'] ?? ''));

                foreach ($nonZeroItems as $item) {
                    $quantity = (float) $item['quantity'];
                    $productId = (int) ($item['product_id'] ?? 0);
                    $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : 0;

                    if ($quantity === 0.0) {
                        continue;
                    }

                    if ($variantId > 0) {
                        $variant = $variants->get($variantId);
                        $variantLabelParts = array_filter([
                            (string) ($variant?->color ?? ''),
                            (string) ($variant?->size ?? ''),
                            (string) ($variant?->age ?? ''),
                        ]);
                        $variantLabel = empty($variantLabelParts) ? ('Variant #'.$variantId) : implode(' / ', $variantLabelParts);
                        $variantReasonParts = array_filter([
                            'Stock correction - '.$validated['adjustment_type'],
                            'Variant: '.$variantLabel,
                            $reasonDetails !== '' ? 'Reason: '.$reasonDetails : '',
                            $notesDetails !== '' ? 'Notes: '.$notesDetails : '',
                        ]);

                        $this->variantStockService->adjustStock(
                            warehouseId: (int) $validated['warehouse_id'],
                            variantId: $variantId,
                            quantity: $quantity,
                            reason: implode(' | ', $variantReasonParts)
                        );

                        continue;
                    }

                    $reasonParts = array_filter([
                        'Stock correction - '.$validated['adjustment_type'],
                        $reasonDetails !== '' ? 'Reason: '.$reasonDetails : '',
                        $notesDetails !== '' ? 'Notes: '.$notesDetails : '',
                    ]);
                    $reason = implode(' | ', $reasonParts);

                    // Positive = add, Negative = remove
                    if ($quantity > 0) {
                        $this->stockService->increaseStock(
                            warehouseId: (int) $validated['warehouse_id'],
                            productId: $productId,
                            quantity: $quantity,
                            referenceType: 'stock_adjustment',
                            referenceId: 0,
                            notes: $reason
                        );
                    } else {
                        $this->stockService->decreaseStock(
                            warehouseId: (int) $validated['warehouse_id'],
                            productId: $productId,
                            quantity: abs($quantity),
                            referenceType: 'stock_adjustment',
                            referenceId: 0,
                            notes: $reason
                        );
                    }
                }
            });

            Toastr::success('Stock adjusted successfully', 'Success');

            return redirect()->route('admin.inventory.index');

        } catch (Throwable $e) {
            report($e);
            Toastr::error('Failed to adjust stock. Please verify available quantities and try again.', 'Error');

            return back()->withInput();
        }
    }

    /**
     * 4. TRANSFER STOCK - Move stock between warehouses
     */
    public function transferStock()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('admin.inventory.transfer-stock', compact('warehouses', 'products'));
    }

    /**
     * Store stock transfer
     */
    public function storeTransferStock(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'to_warehouse_id' => [
                'required',
                'different:from_warehouse_id',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Prevent same warehouse transfer
        if ($validated['from_warehouse_id'] === $validated['to_warehouse_id']) {
            Toastr::error('Source and destination warehouses must be different', 'Error');

            return back()->withInput();
        }

        $items = collect($validated['items'])->values();
        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id')->all())
            ->get(['id', 'name', 'has_variant'])
            ->keyBy('id');

        $variantIds = $items
            ->pluck('variant_id')
            ->filter(fn ($id) => ! is_null($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->where('status', 'active')
            ->get(['id', 'product_id', 'sku_code', 'color', 'size', 'age'])
            ->keyBy('id');

        $productsWithActiveVariants = ProductVariant::query()
            ->selectRaw('DISTINCT product_id')
            ->whereIn('product_id', $items->pluck('product_id')->all())
            ->where('status', 'active')
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $productsWithLegacyAttributes = Productcolor::query()
            ->whereIn('product_id', $items->pluck('product_id')->all())
            ->distinct()
            ->pluck('product_id')
            ->merge(
                Productsize::query()
                    ->whereIn('product_id', $items->pluck('product_id')->all())
                    ->distinct()
                    ->pluck('product_id')
            )
            ->merge(
                $this->hasLegacyAgeTable()
                    ? Productage::query()
                        ->whereIn('product_id', $items->pluck('product_id')->all())
                        ->distinct()
                        ->pluck('product_id')
                    : collect()
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $validationErrors = [];
        foreach ($items as $index => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : 0;

            $product = $products->get($productId);
            if (! $product) {
                $validationErrors["items.{$index}.product_id"] = 'Selected product is unavailable.';

                continue;
            }

            $requiresVariant = in_array($productId, $productsWithActiveVariants, true)
                || in_array($productId, $productsWithLegacyAttributes, true);
            if ($requiresVariant && $variantId <= 0) {
                $validationErrors["items.{$index}.variant_id"] = "Variant is required for {$product->name}.";

                continue;
            }

            if ($variantId > 0) {
                $variant = $variants->get($variantId);
                if (! $variant || (int) $variant->product_id !== $productId) {
                    $validationErrors["items.{$index}.variant_id"] = 'Selected variant is invalid for the chosen product.';
                }
            }
        }

        if (! empty($validationErrors)) {
            throw ValidationException::withMessages($validationErrors);
        }

        try {
            DB::transaction(function () use ($validated, $items, $products, $variants) {
                $fromWarehouse = Warehouse::findOrFail((int) $validated['from_warehouse_id']);
                $toWarehouse = Warehouse::findOrFail((int) $validated['to_warehouse_id']);
                $notes = trim((string) ($validated['notes'] ?? ''));

                foreach ($items as $item) {
                    $productId = (int) ($item['product_id'] ?? 0);
                    $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : 0;
                    $product = $products->get($productId);
                    $quantity = (float) $item['quantity'];

                    if (! $product) {
                        throw new \RuntimeException('One or more selected products are unavailable.');
                    }

                    if ($variantId > 0) {
                        $variant = $variants->get($variantId);
                        $variantLabelParts = array_filter([
                            (string) ($variant?->color ?? ''),
                            (string) ($variant?->size ?? ''),
                            (string) ($variant?->age ?? ''),
                        ]);
                        $variantLabel = empty($variantLabelParts) ? ('Variant #'.$variantId) : implode(' / ', $variantLabelParts);

                        $variantTransferNotes = $notes !== ''
                            ? ($notes.' | '.$product->name.' ('.$variantLabel.')')
                            : ('Transfer '.$product->name.' ('.$variantLabel.')');

                        $this->variantStockService->transferStock(
                            fromWarehouseId: (int) $validated['from_warehouse_id'],
                            toWarehouseId: (int) $validated['to_warehouse_id'],
                            variantId: $variantId,
                            quantity: $quantity,
                            notes: $variantTransferNotes
                        );

                        continue;
                    }

                    // Check if source warehouse has enough stock
                    $sourceStock = WarehouseStock::where('warehouse_id', (int) $validated['from_warehouse_id'])
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    if (! $sourceStock || $sourceStock->available_quantity < $quantity) {
                        throw new \RuntimeException("Insufficient stock of {$product->name} in {$fromWarehouse->name}");
                    }

                    // Decrease from source
                    $this->stockService->decreaseStock(
                        warehouseId: (int) $validated['from_warehouse_id'],
                        productId: $productId,
                        quantity: $quantity,
                        referenceType: 'warehouse_transfer',
                        referenceId: 0,
                        notes: "Transfer to {$toWarehouse->name}"
                    );

                    // Increase to destination
                    $this->stockService->increaseStock(
                        warehouseId: (int) $validated['to_warehouse_id'],
                        productId: $productId,
                        quantity: $quantity,
                        referenceType: 'warehouse_transfer',
                        referenceId: 0,
                        notes: "Transfer from {$fromWarehouse->name}"
                    );
                }
            });

            Toastr::success('Stock transferred successfully', 'Success');

            return redirect()->route('admin.inventory.index');

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            Toastr::error('Transfer failed. Please verify source stock and try again.', 'Error');

            return back()->withInput();
        }
    }

    /**
     * 5. VIEW HISTORY - Stock movements and audit trail
     */
    public function history(Request $request)
    {
        $filters = $request->validate([
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'type' => 'nullable|in:grn,sale,transfer_in,transfer_out,adjustment_in,adjustment_out,reservation,release,loss,initial_stock',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        if (! empty($filters['from_date']) && ! empty($filters['to_date'])) {
            $this->guardHistoryRange($filters['from_date'], $filters['to_date']);
        }

        $query = StockMovement::with(['warehouse', 'product'])
            ->latest('created_at');

        // Filter by warehouse
        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        // Filter by product
        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        // Filter by type
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by date range
        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $movements = $query->paginate(100)->appends($request->query());
        $warehouses = Warehouse::active()->get();
        $products = Product::where('status', 1)->get();

        return view('admin.inventory.history', compact('movements', 'warehouses', 'products'));
    }

    /**
     * Get warehouse stock for a specific product (JSON API)
     */
    public function getProductStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'warehouse_id' => [
                'nullable',
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

        $productId = (int) $validator->validated()['product_id'];
        $warehouseId = $request->filled('warehouse_id') ? (int) $request->warehouse_id : null;
        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : 0;

        $product = Product::find($productId);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // If warehouse_id is provided, return detailed data for that warehouse (for quick adjust modal)
        if ($warehouseId) {
            if ($variantId > 0) {
                $variant = ProductVariant::query()
                    ->where('id', $variantId)
                    ->where('product_id', $productId)
                    ->where('status', 'active')
                    ->first();

                if (! $variant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Variant not found for this product',
                    ], 404);
                }

                $warehouse = Warehouse::query()->find($warehouseId);
                $inventory = Inventory::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->first();

                $physicalQuantity = (float) ($inventory->quantity_available ?? 0);
                $reservedQuantity = (float) ($inventory->quantity_reserved ?? 0);
                $availableQuantity = max(0, $physicalQuantity - $reservedQuantity);
                $reorderLevel = (float) ($inventory->reorder_level ?? 5);

                return response()->json([
                    'success' => true,
                    'product_name' => $product->name,
                    'warehouse_name' => (string) ($warehouse?->name ?? 'Unknown'),
                    'data' => [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouseId,
                        'variant_id' => $variantId,
                        'variant_label' => $this->formatVariantLabel($variant),
                        'physical_quantity' => $physicalQuantity,
                        'available_quantity' => $availableQuantity,
                        'reserved_quantity' => $reservedQuantity,
                        'reorder_point' => $reorderLevel,
                    ],
                ]);
            }

            $stock = WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->with('warehouse')
                ->first();

            if (! $stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product or stock not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'product_name' => $product->name,
                'warehouse_name' => $stock->warehouse->name,
                'data' => [
                    'product_id' => $product->id,
                    'warehouse_id' => $stock->warehouse_id,
                    'physical_quantity' => $stock->physical_quantity,
                    'available_quantity' => $stock->available_quantity,
                    'reserved_quantity' => $stock->reserved_quantity,
                    'reorder_point' => $stock->reorder_point,
                ],
            ]);
        }

        // Otherwise return all warehouses for this product
        $stocks = WarehouseStock::where('product_id', $productId)
            ->with('warehouse')
            ->get();

        return response()->json([
            'success' => true,
            'stocks' => $stocks->map(fn ($s) => [
                'warehouse_id' => $s->warehouse_id,
                'warehouse_name' => $s->warehouse->name,
                'physical_quantity' => $s->physical_quantity,
                'available_quantity' => $s->available_quantity,
                'reserved_quantity' => $s->reserved_quantity,
            ]),
        ]);
    }

    /**
     * Get products for a warehouse (JSON API)
     */
    public function getWarehouseProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $warehouseId = (int) $validator->validated()['warehouse_id'];

        $stocks = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('available_quantity', '>', 0)
            ->whereHas('product', function ($query) {
                $query->where('status', 1);
            })
            ->with('product')
            ->orderBy('product_id')
            ->get();

        return response()->json([
            'success' => true,
            'products' => $stocks->map(fn ($s) => [
                'product_id' => $s->product_id,
                'product_name' => $s->product->name,
                'sku' => $s->product->sku,
                'physical_quantity' => $s->physical_quantity,
                'available_quantity' => $s->available_quantity,
                'reserved_quantity' => $s->reserved_quantity,
            ]),
        ]);
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
        $warehouseId = $request->query('warehouse_id') ? (int) $request->query('warehouse_id') : null;

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

        $payload = $this->variantAttributeService->buildProductVariantPayload($product, $warehouseId);

        if (empty($payload['variants'])) {
            $this->syncLegacyVariants($product);
            $payload = $this->variantAttributeService->buildProductVariantPayload($product->fresh(['image']), $warehouseId);
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

    /**
     * Quick adjust stock from modal
     */
    public function quickAdjust(Request $request)
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
            'adjustment_quantity' => 'required|numeric|not_in:0',
            'reason' => 'required|string|min:3|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $productId = (int) $request->input('product_id');
            $warehouseId = (int) $request->input('warehouse_id');
            $variantId = (int) ($request->input('variant_id') ?? 0);
            $adjustment = (float) $request->input('adjustment_quantity');
            $reason = trim((string) $request->input('reason'));

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

                $oldQuantity = (float) ($inventory->quantity_available ?? 0);
                $reservedQuantity = (float) ($inventory->quantity_reserved ?? 0);
                $newQuantity = $oldQuantity + $adjustment;

                if ($newQuantity < 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot reduce stock below 0. Current: {$oldQuantity}, Adjustment: {$adjustment}",
                    ], 422);
                }

                if ($newQuantity < $reservedQuantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot reduce stock below currently reserved quantity.',
                    ], 422);
                }

                $variantLabel = $this->formatVariantLabel($variant);
                $this->variantStockService->adjustStock(
                    warehouseId: $warehouseId,
                    variantId: $variantId,
                    quantity: $adjustment,
                    reason: $reason.' | Variant: '.$variantLabel
                );

                $updatedInventory = Inventory::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Variant stock adjusted successfully',
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => (float) ($updatedInventory->quantity_available ?? 0),
                    'adjustment' => $adjustment,
                    'variant_id' => $variantId,
                    'variant_label' => $variantLabel,
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

            $stock = WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (! $stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock record not found',
                ], 404);
            }

            $oldQuantity = (float) $stock->physical_quantity;
            $newQuantity = $oldQuantity + $adjustment;

            // Validate new quantity isn't negative
            if ($newQuantity < 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot reduce stock below 0. Current: {$oldQuantity}, Adjustment: {$adjustment}",
                ], 422);
            }
            if ($newQuantity < (float) $stock->reserved_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot reduce stock below currently reserved quantity.',
                ], 422);
            }

            // Use service to adjust stock
            $updatedStock = $this->stockService->adjustStock(
                warehouseId: $warehouseId,
                productId: $productId,
                adjustment: $adjustment,
                reason: $reason,
                referenceId: 0
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'old_quantity' => $oldQuantity,
                'new_quantity' => (float) $updatedStock->physical_quantity,
                'adjustment' => $adjustment,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Error adjusting stock. Please try again.',
            ], 500);
        }
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

    protected function guardHistoryRange(string $fromDate, string $toDate): void
    {
        $days = Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate));
        if ($days > self::MAX_HISTORY_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'to_date' => 'Date range is too large. Maximum allowed range is '.self::MAX_HISTORY_RANGE_DAYS.' days.',
            ]);
        }
    }
}
