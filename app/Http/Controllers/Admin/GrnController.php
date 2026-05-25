<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GrnRequest;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockAlertService;
use App\Services\StockMovementService;
use App\Services\SupplierService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GrnController extends Controller
{
    protected $movementService;

    protected $stockAlertService;

    protected $supplierService;

    public function __construct(
        StockMovementService $movementService,
        StockAlertService $stockAlertService,
        SupplierService $supplierService
    )
    {
        $this->movementService = $movementService;
        $this->stockAlertService = $stockAlertService;
        $this->supplierService = $supplierService;

        $this->middleware('permission:grn-list', ['only' => ['index']]);
        $this->middleware('permission:grn-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:grn-edit', ['only' => ['edit', 'update', 'data']]);
        $this->middleware('permission:grn-delete', ['only' => ['destroy']]);
        $this->middleware('permission:grn-approve', ['only' => ['approve']]);
        $this->middleware('permission:grn-view', ['only' => ['show', 'print']]);
    }

    /**
     * Keyword search for Purchase modal (Select2). Uses same rules as inventory GRN search.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        abort_unless(
            optional(auth()->user())->can('grn-create') || optional(auth()->user())->can('grn-edit'),
            403
        );

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
            ->get(['id', 'name', 'sku', 'purchase_price', 'new_price']);

        $results = $products->map(function (Product $product) {
            $skuLabel = $product->sku !== null && $product->sku !== '' ? $product->sku : 'N/A';
            $defaultCost = (float) ($product->purchase_price ?? 0);
            if ($defaultCost <= 0) {
                $defaultCost = (float) ($product->new_price ?? 0);
            }

            return [
                'id' => $product->id,
                'text' => $product->name.' (SKU: '.$skuLabel.')',
                'default_cost' => round($defaultCost, 2),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function index(Request $request)
    {
        $query = Grn::with(['warehouse', 'supplier', 'receiver'])->withCount('items');

        // Filter by status
        if ($request->status) {
            $query->byStatus($request->status);
        }

        // Filter by warehouse
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by supplier
        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('grn_number', 'like', "%{$request->search}%")
                    ->orWhere('invoice_number', 'like', "%{$request->search}%");
            });
        }

        $grns = $query->latest()->paginate(20);
        $warehouses = Warehouse::active()->get();
        $products = Product::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'new_price', 'purchase_price']);
        $suppliers = Supplier::query()
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('status', 1)
                    ->orWhere('status', '1');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'supplier_code']);
        $variantApiUrl = route('admin.inventory.api.product-variants');

        return view('backEnd.grn.index', compact('grns', 'warehouses', 'suppliers', 'products', 'variantApiUrl'));
    }

    public function create()
    {
        return redirect()->route('admin.grn.index', ['modal' => 'create']);
    }

    public function store(GrnRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $grn = Grn::create([
                'warehouse_id' => $validated['warehouse_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'grn_date' => $validated['grn_date'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'] ?? null,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'other_charges' => $validated['other_charges'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
            ]);

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $variant = $this->resolveVariantFromPayload(
                    (int) $item['product_id'],
                    isset($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    $item['sku'] ?? null,
                    [
                        'color' => $item['color'] ?? null,
                        'size' => $item['size'] ?? null,
                        'age' => $item['age'] ?? null,
                    ]
                );
                $orderedQuantity = isset($item['ordered_quantity']) && $item['ordered_quantity'] !== ''
                    ? (float) $item['ordered_quantity']
                    : (float) $item['quantity'];
                $sku = trim((string) ($item['sku'] ?? ''));
                if ($sku === '') {
                    $sku = (string) ($variant->sku_code ?? $product->sku ?? ('SKU-'.$product->id));
                }
                $taxAmount = ($item['quantity'] * $item['unit_cost']) * ($item['tax_rate'] ?? 0) / 100;

                $grn->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variant?->id,
                    'sku' => $sku,
                    'description' => $product->name,
                    'quantity' => $item['quantity'],
                    'ordered_quantity' => $orderedQuantity,
                    'unit_cost' => $item['unit_cost'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $taxAmount,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);

                $subtotal += $item['quantity'] * $item['unit_cost'];
                $taxTotal += $taxAmount;
            }

            $grn->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $subtotal + $taxTotal + ($validated['shipping_cost'] ?? 0) + ($validated['other_charges'] ?? 0),
            ]);

            DB::commit();

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'message' => 'Purchase recorded successfully.',
                    'id' => $grn->id,
                ]);
            }

            Toastr::success('GRN created successfully', 'Success');

            return redirect()->route('admin.grn.show', $grn->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create GRN purchase.', [
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'item_count' => isset($validated['items']) && is_array($validated['items']) ? count($validated['items']) : 0,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'Failed to create purchase.',
                ], 500);
            }

            Toastr::error('Failed to create GRN: '.$e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        $grn = Grn::with(['warehouse', 'supplier', 'items.product', 'items.productVariant', 'receiver', 'approver'])
            ->findOrFail($id);

        $itemSkuList = $grn->items
            ->pluck('sku')
            ->filter()
            ->map(fn ($sku) => trim((string) $sku))
            ->filter()
            ->unique()
            ->values();

        $variantsBySku = ProductVariant::query()
            ->whereIn('sku_code', $itemSkuList)
            ->get(['id', 'product_id', 'sku_code', 'color', 'size', 'age'])
            ->keyBy('sku_code');

        $resolvedVariantIds = $grn->items
            ->map(function (GrnItem $item) use ($variantsBySku) {
                if ($item->product_variant_id) {
                    return (int) $item->product_variant_id;
                }

                return (int) ($variantsBySku->get((string) $item->sku)?->id ?? 0);
            })
            ->filter()
            ->unique()
            ->values();

        $stocksByVariant = WarehouseStock::query()
            ->where('warehouse_id', $grn->warehouse_id)
            ->whereIn('product_variant_id', $resolvedVariantIds)
            ->get()
            ->keyBy('product_variant_id');

        $fallbackStocksByProduct = WarehouseStock::query()
            ->where('warehouse_id', $grn->warehouse_id)
            ->whereIn('product_id', $grn->items->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('product_id');

        $itemMeta = [];
        $expiryAlerts = [];
        $lowStockAlerts = [];
        $discrepancyAlerts = [];
        $expiryThreshold = Carbon::now()->addDays(30)->endOfDay();

        foreach ($grn->items as $item) {
            $variant = $item->productVariant ?: $variantsBySku->get((string) $item->sku);
            $stock = $variant
                ? $stocksByVariant->get((int) $variant->id)
                : $fallbackStocksByProduct->get((int) $item->product_id);

            $orderedQuantity = $item->ordered_quantity !== null
                ? (float) $item->ordered_quantity
                : (float) $item->quantity;
            $receivedQuantity = (float) $item->quantity;
            $delta = $receivedQuantity - $orderedQuantity;
            $isDiscrepancy = abs($delta) > 0.0001;

            $itemExpiry = $item->expiry_date ? Carbon::parse($item->expiry_date)->endOfDay() : null;
            $stockExpiry = $stock && $stock->expiry_date ? Carbon::parse($stock->expiry_date)->endOfDay() : null;
            $isItemExpiring = $itemExpiry && $itemExpiry->lte($expiryThreshold);
            $isStockExpiring = $stockExpiry && $stockExpiry->lte($expiryThreshold);
            $isLowStock = $stock && ((float) $stock->available_quantity <= (float) $stock->reorder_point);

            $variantLabel = $this->buildVariantLabel($variant);
            $itemMeta[$item->id] = [
                'variant' => $variant,
                'variant_label' => $variantLabel,
                'stock' => $stock,
                'ordered_quantity' => $orderedQuantity,
                'received_quantity' => $receivedQuantity,
                'delta_quantity' => $delta,
                'is_discrepancy' => $isDiscrepancy,
                'is_low_stock' => $isLowStock,
                'is_item_expiring' => $isItemExpiring,
                'is_stock_expiring' => $isStockExpiring,
            ];

            if ($isItemExpiring || $isStockExpiring) {
                $expiryAlerts[] = sprintf(
                    '%s (%s) has expiry risk.',
                    (string) ($item->product->name ?? $item->description ?? 'Product'),
                    (string) ($item->sku ?? 'N/A')
                );
            }

            if ($isLowStock) {
                $lowStockAlerts[] = sprintf(
                    '%s (%s) is at low stock (%s available).',
                    (string) ($item->product->name ?? $item->description ?? 'Product'),
                    (string) ($item->sku ?? 'N/A'),
                    number_format((float) $stock->available_quantity, 2)
                );
            }

            if ($isDiscrepancy) {
                $discrepancyAlerts[] = sprintf(
                    '%s (%s): ordered %s, received %s.',
                    (string) ($item->product->name ?? $item->description ?? 'Product'),
                    (string) ($item->sku ?? 'N/A'),
                    number_format($orderedQuantity, 2),
                    number_format($receivedQuantity, 2)
                );
            }
        }

        $expiryAlerts = array_values(array_unique($expiryAlerts));
        $lowStockAlerts = array_values(array_unique($lowStockAlerts));
        $discrepancyAlerts = array_values(array_unique($discrepancyAlerts));

        return view('backEnd.grn.show', compact('grn', 'itemMeta', 'expiryAlerts', 'lowStockAlerts', 'discrepancyAlerts'));
    }

    public function edit($id)
    {
        $grn = Grn::query()->findOrFail($id);

        if ($grn->status !== 'draft') {
            Toastr::error('Only draft GRNs can be edited', 'Error');

            return redirect()->route('admin.grn.show', $id);
        }

        return redirect()->route('admin.grn.index', ['edit' => $id]);
    }

    public function data($id): JsonResponse
    {
        $grn = Grn::with(['items.productVariant'])->findOrFail($id);

        if ($grn->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft purchases can be edited.',
            ], 422);
        }

        return response()->json([
            'grn' => [
                'id' => (int) $grn->id,
                'grn_number' => (string) $grn->grn_number,
                'warehouse_id' => (int) $grn->warehouse_id,
                'supplier_id' => $grn->supplier_id ? (int) $grn->supplier_id : null,
                'grn_date' => optional($grn->grn_date)->format('Y-m-d'),
                'invoice_date' => optional($grn->invoice_date)->format('Y-m-d'),
                'invoice_number' => (string) ($grn->invoice_number ?? ''),
                'shipping_cost' => (float) ($grn->shipping_cost ?? 0),
                'other_charges' => (float) ($grn->other_charges ?? 0),
                'notes' => (string) ($grn->notes ?? ''),
                'items' => $grn->items->map(function (GrnItem $item) {
                    return [
                        'product_id' => (int) $item->product_id,
                        'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                        'sku' => (string) ($item->sku ?? ''),
                        'color' => (string) ($item->productVariant->color ?? ''),
                        'size' => (string) ($item->productVariant->size ?? ''),
                        'age' => (string) ($item->productVariant->age ?? ''),
                        'ordered_quantity' => (float) ($item->ordered_quantity ?? 0),
                        'quantity' => (float) ($item->quantity ?? 0),
                        'unit_cost' => (float) ($item->unit_cost ?? 0),
                        'tax_rate' => (float) ($item->tax_rate ?? 0),
                        'batch_number' => (string) ($item->batch_number ?? ''),
                        'expiry_date' => $item->expiry_date ? Carbon::parse($item->expiry_date)->format('Y-m-d') : '',
                        'notes' => (string) ($item->notes ?? ''),
                    ];
                })->values(),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $grn = Grn::findOrFail($id);

        if ($grn->status !== 'draft') {
            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'message' => 'Only draft purchases can be updated.',
                ], 422);
            }

            Toastr::error('Only draft GRNs can be updated', 'Error');

            return redirect()->back();
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'grn_date' => 'required|date|before_or_equal:today',
            'invoice_number' => 'nullable|max:100',
            'invoice_date' => 'nullable|date|before_or_equal:today',
            'shipping_cost' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.sku' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.ordered_quantity' => 'nullable|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.color' => 'nullable|string|max:50',
            'items.*.size' => 'nullable|string|max:50',
            'items.*.age' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Delete old items
            $grn->items()->delete();

            // Update GRN
            $grn->update([
                'warehouse_id' => $validated['warehouse_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'grn_date' => $validated['grn_date'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'] ?? null,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'other_charges' => $validated['other_charges'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Recreate items
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $variant = $this->resolveVariantFromPayload(
                    (int) $item['product_id'],
                    isset($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    $item['sku'] ?? null,
                    [
                        'color' => $item['color'] ?? null,
                        'size' => $item['size'] ?? null,
                        'age' => $item['age'] ?? null,
                    ]
                );
                $orderedQuantity = isset($item['ordered_quantity']) && $item['ordered_quantity'] !== ''
                    ? (float) $item['ordered_quantity']
                    : (float) $item['quantity'];
                $sku = trim((string) ($item['sku'] ?? ''));
                if ($sku === '') {
                    $sku = (string) ($variant->sku_code ?? $product->sku ?? ('SKU-'.$product->id));
                }
                $taxAmount = ($item['quantity'] * $item['unit_cost']) * ($item['tax_rate'] ?? 0) / 100;

                $grn->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variant?->id,
                    'sku' => $sku,
                    'description' => $product->name,
                    'quantity' => $item['quantity'],
                    'ordered_quantity' => $orderedQuantity,
                    'unit_cost' => $item['unit_cost'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $taxAmount,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);

                $subtotal += $item['quantity'] * $item['unit_cost'];
                $taxTotal += $taxAmount;
            }

            $grn->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $subtotal + $taxTotal + ($validated['shipping_cost'] ?? 0) + ($validated['other_charges'] ?? 0),
            ]);

            DB::commit();

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'message' => 'Purchase updated successfully.',
                    'id' => $grn->id,
                ]);
            }

            Toastr::success('GRN updated successfully', 'Success');

            return redirect()->route('admin.grn.show', $grn->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update GRN purchase.', [
                'grn_id' => $grn->id,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'item_count' => isset($validated['items']) && is_array($validated['items']) ? count($validated['items']) : 0,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'Failed to update purchase.',
                ], 500);
            }

            Toastr::error('Failed to update GRN: '.$e->getMessage(), 'Error');

            return redirect()->back()->withInput();
        }
    }

    public function approve($id)
    {
        $grn = Grn::with(['items.product', 'items.productVariant'])->findOrFail($id);

        if ($grn->status !== 'draft') {
            Toastr::error('Only draft GRNs can be approved', 'Error');

            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            // Approve GRN
            $grn->approve(auth()->id());

            $warnings = [];

            // Update stock for each item using StockMovementService
            foreach ($grn->items as $item) {
                $variant = $this->resolveVariantForItem($item);
                $orderedQty = $item->ordered_quantity !== null
                    ? (float) $item->ordered_quantity
                    : (float) $item->quantity;
                $receivedQty = (float) $item->quantity;
                $deltaQty = $receivedQty - $orderedQty;

                $this->movementService->recordStockIn([
                    'warehouse_id' => $grn->warehouse_id,
                    'product_id' => (int) $item->product_id,
                    'product_variant_id' => $variant?->id,
                    'sku' => $item->sku ?: ($variant?->sku_code ?? null),
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'batch_number' => $item->batch_number,
                    'expiry_date' => $item->expiry_date,
                    'type' => 'grn',
                    'reference_type' => 'grn',
                    'reference_id' => $grn->id,
                    'notes' => "GRN #{$grn->grn_number} - {$item->description}",
                ]);

                $stock = WarehouseStock::query()
                    ->where('warehouse_id', $grn->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($stock) {
                    $this->stockAlertService->checkStockAlerts($stock);

                    if ((float) $stock->available_quantity <= (float) $stock->reorder_point) {
                        $warnings[] = sprintf(
                            '%s (%s) is at low stock after GRN approval.',
                            (string) ($item->product->name ?? 'Product'),
                            (string) ($item->sku ?? 'N/A')
                        );
                    }
                }

                if ($item->expiry_date && Carbon::parse($item->expiry_date)->lte(Carbon::now()->addDays(30))) {
                    $warnings[] = sprintf(
                        '%s (%s) has near-expiry batch (%s).',
                        (string) ($item->product->name ?? 'Product'),
                        (string) ($item->sku ?? 'N/A'),
                        Carbon::parse($item->expiry_date)->format('d M Y')
                    );
                }

                if (abs($deltaQty) > 0.0001) {
                    $this->createOrUpdateDiscrepancyAlert($grn, $item, $orderedQty, $receivedQty, $deltaQty);
                    $warnings[] = sprintf(
                        'Discrepancy for %s (%s): ordered %s, received %s.',
                        (string) ($item->product->name ?? 'Product'),
                        (string) ($item->sku ?? 'N/A'),
                        number_format($orderedQty, 2),
                        number_format($receivedQty, 2)
                    );
                }
            }

            if ($grn->supplier_id && (float) ($grn->total_amount ?? 0) > 0) {
                $branchId = (int) (Warehouse::query()
                    ->whereKey((int) $grn->warehouse_id)
                    ->value('branch_id') ?? 0);

                try {
                    $this->supplierService->recordPurchase($grn->supplier, [
                        'amount' => round((float) $grn->total_amount, 2),
                        'branch_id' => $branchId > 0 ? $branchId : null,
                        'purchase_date' => optional($grn->grn_date)->format('Y-m-d') ?: now()->toDateString(),
                        'purchase_id' => (int) $grn->id,
                        'purchase_number' => (string) ($grn->grn_number ?? ''),
                        'reference_type' => 'grn',
                        'description' => 'GRN Purchase #'.($grn->grn_number ?? $grn->id),
                        'created_by' => (int) (auth()->id()
                            ?? \App\Models\User::query()->value('id')
                            ?? 0),
                    ]);
                } catch (\Throwable $ledgerException) {
                    report($ledgerException);
                    $warnings[] = 'Supplier ledger could not be updated: '.$ledgerException->getMessage();
                }
            }

            $this->stockAlertService->checkExpiringStock(30);

            DB::commit();
            Toastr::success('GRN approved and stock updated successfully', 'Success');

            foreach (array_unique($warnings) as $warning) {
                Toastr::warning($warning, 'Inventory Alert');
            }

            return redirect()->route('admin.grn.show', $grn->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to approve GRN: '.$e->getMessage(), 'Error');

            return redirect()->back();
        }
    }

    private function resolveVariantFromPayload(
        int $productId,
        ?int $variantId = null,
        ?string $sku = null,
        array $axes = []
    ): ?ProductVariant {
        if ($variantId) {
            $variant = ProductVariant::query()
                ->whereKey($variantId)
                ->where('product_id', $productId)
                ->first();

            if ($variant) {
                return $variant;
            }
        }

        $normalizedSku = trim((string) $sku);
        if ($normalizedSku !== '') {
            $variant = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('sku_code', $normalizedSku)
                ->first();

            if ($variant) {
                return $variant;
            }
        }

        $normalizedAxes = $this->normalizeVariantAxes($axes);
        $hasAnyAxis = false;
        foreach (['color', 'size', 'age'] as $axisKey) {
            if (! Schema::hasColumn('product_variants', $axisKey)) {
                continue;
            }

            if ($normalizedAxes[$axisKey] !== null) {
                $hasAnyAxis = true;
                break;
            }
        }

        if ($hasAnyAxis) {
            $axisVariantQuery = ProductVariant::query()
                ->where('product_id', $productId);

            foreach (['color', 'size', 'age'] as $axisKey) {
                if (! Schema::hasColumn('product_variants', $axisKey)) {
                    continue;
                }

                if ($normalizedAxes[$axisKey] !== null) {
                    $axisVariantQuery->where($axisKey, $normalizedAxes[$axisKey]);
                }
            }

            $axisVariant = $axisVariantQuery->orderBy('id')->first();
            if ($axisVariant) {
                return $axisVariant;
            }

            $createPayload = [
                'product_id' => $productId,
                'sku_code' => $this->generateUniqueVariantSku($productId, $normalizedSku, $normalizedAxes),
                'status' => 'active',
            ];

            if (Schema::hasColumn('product_variants', 'color')) {
                $createPayload['color'] = $normalizedAxes['color'];
            }
            if (Schema::hasColumn('product_variants', 'size')) {
                $createPayload['size'] = $normalizedAxes['size'];
            }
            if (Schema::hasColumn('product_variants', 'age')) {
                $createPayload['age'] = $normalizedAxes['age'];
            }

            return ProductVariant::query()->create($createPayload);
        }

        $firstVariant = ProductVariant::query()
            ->where('product_id', $productId)
            ->orderBy('id')
            ->first();

        if ($firstVariant) {
            return $firstVariant;
        }

        return ProductVariant::query()->create([
            'product_id' => $productId,
            'sku_code' => $this->generateUniqueVariantSku($productId, $normalizedSku, $normalizedAxes),
            'status' => 'active',
        ]);
    }

    private function resolveVariantForItem(GrnItem $item): ?ProductVariant
    {
        if ($item->relationLoaded('productVariant') && $item->productVariant) {
            return $item->productVariant;
        }

        return $this->resolveVariantFromPayload(
            (int) $item->product_id,
            $item->product_variant_id ? (int) $item->product_variant_id : null,
            $item->sku
        );
    }

    private function normalizeVariantAxes(array $axes): array
    {
        return [
            'color' => $this->normalizeAxisValue($axes['color'] ?? null),
            'size' => $this->normalizeAxisValue($axes['size'] ?? null),
            'age' => $this->normalizeAxisValue($axes['age'] ?? null),
        ];
    }

    private function normalizeAxisValue($value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function generateUniqueVariantSku(int $productId, ?string $preferredSku = null, array $axes = []): string
    {
        $candidate = strtoupper(trim((string) ($preferredSku ?? '')));
        if ($candidate !== '' && ! ProductVariant::query()->where('sku_code', $candidate)->exists()) {
            return $candidate;
        }

        $baseSku = (string) (Product::query()->whereKey($productId)->value('sku') ?? '');
        $baseSku = strtoupper(trim($baseSku)) ?: ('VAR-'.$productId);

        $suffixParts = [];
        foreach (['color', 'size', 'age'] as $axisKey) {
            $axisValue = trim((string) ($axes[$axisKey] ?? ''));
            if ($axisValue === '') {
                continue;
            }

            $slug = preg_replace('/[^A-Za-z0-9]+/', '', strtoupper($axisValue));
            if ($slug !== '') {
                $suffixParts[] = substr($slug, 0, 8);
            }
        }

        $seed = ! empty($suffixParts)
            ? ($baseSku.'-'.implode('-', $suffixParts))
            : $baseSku;

        $uniqueCandidate = $seed;
        $counter = 2;
        while (ProductVariant::query()->where('sku_code', $uniqueCandidate)->exists()) {
            $uniqueCandidate = "{$seed}-{$counter}";
            $counter++;
        }

        return $uniqueCandidate;
    }

    private function buildVariantLabel(?ProductVariant $variant): string
    {
        if (! $variant) {
            return 'N/A';
        }

        $parts = array_filter([
            trim((string) ($variant->color ?? '')),
            trim((string) ($variant->size ?? '')),
            trim((string) ($variant->age ?? '')),
        ]);

        return ! empty($parts) ? implode(' / ', $parts) : 'Default Variant';
    }

    private function createOrUpdateDiscrepancyAlert(
        Grn $grn,
        GrnItem $item,
        float $orderedQty,
        float $receivedQty,
        float $deltaQty
    ): void {
        $severity = abs($deltaQty) >= max(1, ($orderedQty * 0.2)) ? 'critical' : 'warning';
        $sku = (string) ($item->sku ?: 'N/A');
        $productName = (string) ($item->product->name ?? $item->description ?? 'Product');

        StockAlert::updateOrCreate(
            [
                'warehouse_id' => $grn->warehouse_id,
                'product_id' => $item->product_id,
                'alert_type' => 'grn_discrepancy',
                'status' => 'active',
            ],
            [
                'severity' => $severity,
                'current_quantity' => $receivedQty,
                'threshold_quantity' => $orderedQty,
                'message' => "GRN discrepancy: {$productName} ({$sku}) ordered {$orderedQty}, received {$receivedQty}.",
                'created_by' => auth()->id(),
                'resolved_by' => null,
                'resolved_at' => null,
            ]
        );
    }

    public function destroy($id)
    {
        $grn = Grn::findOrFail($id);

        if ($grn->status !== 'draft') {
            Toastr::error('Only draft GRNs can be deleted', 'Error');

            return redirect()->back();
        }

        $grn->delete();
        Toastr::success('GRN deleted successfully', 'Success');

        return redirect()->route('admin.grn.index');
    }

    public function print($id)
    {
        $grn = Grn::with(['warehouse', 'supplier', 'items.product', 'receiver', 'approver'])->findOrFail($id);

        return view('backEnd.grn.print', compact('grn'));
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson() || $request->wantsJson() || $request->ajax();
    }
}
