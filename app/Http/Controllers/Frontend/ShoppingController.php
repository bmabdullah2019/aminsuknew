<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Session;
use Toastr;

class ShoppingController extends Controller
{
    public function addTocartGet($id, Request $request)
    {
        $qty = 1;
        $productInfo = Product::with('image')->findOrFail($id);

        $resolvedVariant = null;
        if ((bool) ($productInfo->has_variant ?? false)) {
            $activeVariants = ProductVariant::query()
                ->where('product_id', (int) $productInfo->id)
                ->where('status', 'active')
                ->get(['id']);

            // Listing quick-add cannot pick among multiple variants safely.
            if ($activeVariants->count() !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please choose the required variant attributes from the product details page before adding to cart.',
                ], 422);
            }

            $resolvedVariant = ProductVariant::find((int) $activeVariants->first()->id);
            $availableStock = $resolvedVariant
                ? $this->getAvailableVariantStock((int) $resolvedVariant->id)
                : 0;
        } else {
            $availableStock = $this->getAvailableStock((int) $productInfo->id);
        }

        if ($availableStock < $qty) {
            return response()->json([
                'success' => false,
                'message' => 'Product is out of stock',
            ], 422);
        }

        $imagePath = $this->resolvePreferredImagePath($productInfo, $resolvedVariant);

        $warehouseId = Session::get('warehouse_id');
        if (! $warehouseId) {
            $warehouse = Warehouse::main()->active()->first() ?? Warehouse::active()->first();
            $warehouseId = $warehouse?->id;
            if ($warehouseId) {
                Session::put('warehouse_id', $warehouseId);
            }
        }

        $cartinfo = Cart::instance('shopping')->add(['id' => $productInfo->id, 'name' => $productInfo->name, 'qty' => $qty, 'price' => $productInfo->new_price,
            'options' => [
                'image' => $imagePath,
                'old_price' => $productInfo->old_price,
                'slug' => $productInfo->slug,
                'purchase_price' => $productInfo->purchase_price,
                'product_size' => null,
                'product_color' => null,
                'pro_unit' => $productInfo->pro_unit,
                'product_variant_id' => $resolvedVariant?->id,
                'variant_id' => $resolvedVariant?->id,
                'warehouse_id' => $warehouseId,
            ]]);

        $data = Cart::instance('shopping')->content();

        return $this->renderCartResponse($request, $data);
    }

    public function cart_store(Request $request)
    {
        $isAjaxRequest = $request->ajax() || $request->wantsJson();
        $product = Product::where(['id' => $request->id])->first();
        if (! $product) {
            if ($isAjaxRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }
            Toastr::error('Product not found', 'Error!');

            return redirect()->back();
        }

        $requestedQty = max(1, (int) ($request->qty ?? 1));
        $resolvedVariant = null;

        if ((bool) ($product->has_variant ?? false)) {
            $resolvedVariant = $this->resolveRequestedVariant($product, $request);
            if (! $resolvedVariant) {
                if ($isAjaxRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select an available variant before adding to cart.',
                    ], 422);
                }
                Toastr::error('Please select an available variant before adding to cart.', 'Variant Required');

                return redirect()->back();
            }

            $availableStock = $this->getAvailableVariantStock((int) $resolvedVariant->id);
            if ($availableStock < $requestedQty) {
                $variantLabel = trim(implode(' / ', array_filter([
                    (string) ($resolvedVariant->color ?? ''),
                    (string) ($resolvedVariant->size ?? ''),
                ])));
                $variantLabel = $variantLabel !== '' ? $variantLabel : 'selected variant';
                if ($isAjaxRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => "{$variantLabel} is out of stock",
                    ], 422);
                }
                Toastr::error("{$variantLabel} is out of stock", 'Stock Out');

                return redirect()->back();
            }
        } else {
            $availableStock = $this->getAvailableStock((int) $product->id);
            if ($availableStock < $requestedQty) {
                if ($isAjaxRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product is out of stock',
                    ], 422);
                }
                Toastr::error('Product is out of stock', 'Stock Out');

                return redirect()->back();
            }
        }

        $warehouseId = Session::get('warehouse_id');
        if (! $warehouseId) {
            $warehouse = Warehouse::main()->active()->first() ?? Warehouse::active()->first();
            $warehouseId = $warehouse?->id;
            if ($warehouseId) {
                Session::put('warehouse_id', $warehouseId);
            }
        }

        Cart::instance('shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => $requestedQty,
            'price' => $product->new_price,
            'options' => [
                'slug' => $product->slug,
                'image' => $this->resolvePreferredImagePath($product, $resolvedVariant),
                'old_price' => $product->old_price,
                'purchase_price' => $product->purchase_price,
                'product_size' => $request->product_size ?: null,
                'product_color' => $request->product_color ?: null,
                'pro_unit' => $request->pro_unit ?: $product->pro_unit,
                'product_variant_id' => $resolvedVariant?->id ?: ($request->product_variant_id ?: null),
                'variant_id' => $resolvedVariant?->id ?: ($request->variant_id ?: null),
                'warehouse_id' => $warehouseId,
            ],
        ]);

        if ($isAjaxRequest) {
            $data = Cart::instance('shopping')->content();

            return $this->renderCartResponse($request, $data);
        }

        Toastr::success('Product successfully add to cart', 'Success!');

        return redirect()->route('cart.show');

    }

    public function cart_update(Request $request)
    {
        // Get the row ID of the cart item
        $rowId = $request->id;
        // Fetch the current cart item using the row ID
        $cartItem = Cart::instance('shopping')->get($rowId);
        if ($cartItem) {
            // Update the options for the cart item
            Cart::instance('shopping')->update($rowId, [
                'options' => [
                    'product_size' => $request->product_size ?: $cartItem->options->product_size, // Use new size or keep existing
                    'product_color' => $request->product_color ?: $cartItem->options->product_color, // Use new color or keep existing
                    'slug' => $cartItem->options->slug, // Keep existing slug
                    'image' => $cartItem->options->image, // Keep existing image
                    'old_price' => $cartItem->options->old_price, // Keep existing old price
                    'purchase_price' => $cartItem->options->purchase_price, // Keep existing purchase price
                    'pro_unit' => $cartItem->options->pro_unit, // Keep existing pro unit
                    'product_variant_id' => $cartItem->options->product_variant_id ?? null,
                    'variant_id' => $cartItem->options->variant_id ?? null,
                    'warehouse_id' => $cartItem->options->warehouse_id ?? Session::get('warehouse_id'),
                ],
            ]);
        }

        $data = Cart::instance('shopping')->content();

        return $this->renderCartResponse($request, $data);
    }

    public function cart_remove(Request $request)
    {
        $remove = Cart::instance('shopping')->update($request->id, 0);
        $data = Cart::instance('shopping')->content();

        return $this->renderCartResponse($request, $data);
    }

    public function cart_increment(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        $qty = $item->qty + 1;
        $increment = Cart::instance('shopping')->update($request->id, $qty);
        $data = Cart::instance('shopping')->content();

        return $this->renderCartResponse($request, $data);
    }

    public function cart_decrement(Request $request)
    {
        $item = Cart::instance('shopping')->get($request->id);
        $qty = $item->qty - 1;
        $decrement = Cart::instance('shopping')->update($request->id, $qty);
        $data = Cart::instance('shopping')->content();

        return $this->renderCartResponse($request, $data);
    }

    public function cart_count(Request $request)
    {
        $data = Cart::instance('shopping')->count();

        return view('frontEnd.layouts.ajax.cart_count', compact('data'));
    }

    public function mobilecart_qty(Request $request)
    {
        $data = Cart::instance('shopping')->count();

        return view('frontEnd.layouts.ajax.mobilecart_qty', compact('data'));
    }

    public function cart_show(Request $request)
    {
        $data = Cart::instance('shopping')->content();

        return view('frontEnd.layouts.pages.cart', compact('data'));
    }

    public function compareStore(Request $request)
    {
        $productId = (int) $request->input('id', 0);
        if ($productId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid product selected for comparison.',
            ], 422);
        }

        $product = Product::query()
            ->where('status', 1)
            ->with(['image', 'category'])
            ->find($productId);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $compareCart = Cart::instance('compare');

        $alreadyInCompare = $compareCart->content()->contains(function ($item) use ($productId) {
            return (int) $item->id === $productId;
        });

        if ($alreadyInCompare) {
            return response()->json([
                'success' => true,
                'message' => 'Product already exists in comparison list.',
                'count' => $compareCart->count(),
            ]);
        }

        if ($compareCart->count() >= 4) {
            return response()->json([
                'success' => false,
                'message' => 'You can compare up to 4 products at a time.',
                'count' => $compareCart->count(),
            ], 422);
        }

        $compareCart->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => 1,
            'price' => (float) $product->new_price,
            'options' => [
                'slug' => $product->slug,
                'image' => $product->display_image,
                'old_price' => (float) ($product->old_price ?? 0),
                'category' => optional($product->category)->name,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to comparison list.',
            'count' => $compareCart->count(),
        ]);
    }

    public function compareRemove(Request $request)
    {
        $rowId = (string) $request->input('row_id', '');
        if ($rowId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Comparison row id is required.',
            ], 422);
        }

        $compareCart = Cart::instance('compare');
        if (! $compareCart->get($rowId)) {
            return response()->json([
                'success' => false,
                'message' => 'Compared product not found.',
            ], 404);
        }

        $compareCart->update($rowId, 0);

        return response()->json([
            'success' => true,
            'message' => 'Product removed from comparison list.',
            'count' => $compareCart->count(),
        ]);
    }

    public function compareClear()
    {
        Cart::instance('compare')->destroy();

        return response()->json([
            'success' => true,
            'message' => 'Comparison list cleared.',
            'count' => 0,
        ]);
    }

    public function compareCount()
    {
        $data = Cart::instance('compare')->count();

        return view('frontEnd.layouts.ajax.compare_count', compact('data'));
    }

    public function compareShow()
    {
        $compareItems = Cart::instance('compare')->content();
        $productIds = $compareItems->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with([
                'image',
                'category',
                'procolors.color',
                'prosizes.size',
                'ages',
            ])
            ->get()
            ->keyBy('id');

        $items = $compareItems->map(function ($item) use ($products) {
            return [
                'row_id' => $item->rowId,
                'cart' => $item,
                'product' => $products->get((int) $item->id),
            ];
        })->filter(fn ($entry) => $entry['product'] !== null)->values();

        return view('frontEnd.layouts.pages.compare', compact('items'));
    }

    public function changeProduct(Request $request)
    {
        $productId = (int) $request->input('id');
        $product = Product::with('image')->find($productId);

        if ($product) {
            $warehouseId = Session::get('warehouse_id');
            if (! $warehouseId) {
                $warehouse = Warehouse::main()->active()->first() ?? Warehouse::active()->first();
                $warehouseId = $warehouse?->id;
                if ($warehouseId) {
                    Session::put('warehouse_id', $warehouseId);
                }
            }

            if ($this->isCampaignCartRequest($request)) {
                $selected = filter_var($request->input('selected', true), FILTER_VALIDATE_BOOLEAN);
                $cart = Cart::instance('shopping');
                $matchingRows = $cart->content()->filter(function ($item) use ($productId) {
                    return (int) $item->id === $productId;
                });

                if ($selected) {
                    if ($matchingRows->isEmpty()) {
                        $cart->add([
                            'id' => $product->id,
                            'name' => $product->name,
                            'qty' => 1,
                            'price' => $product->new_price,
                            'options' => [
                                'slug' => $product->slug,
                                'image' => $product->display_image,
                                'old_price' => $product->old_price,
                                'purchase_price' => $product->purchase_price,
                                'product_variant_id' => null,
                                'variant_id' => null,
                                'warehouse_id' => $warehouseId,
                            ],
                        ]);
                    }
                } else {
                    $matchingRows->each(function ($item) use ($cart) {
                        $cart->update($item->rowId, 0);
                    });
                }

                $data = $cart->content();

                return $this->renderCartResponse($request, $data);
            }

            Cart::instance('shopping')->destroy();

            Cart::instance('shopping')->add([
                'id' => $product->id,
                'name' => $product->name,
                'qty' => 1,
                'price' => $product->new_price,
                'options' => [
                    'slug' => $product->slug,
                    'image' => $product->display_image,
                    'old_price' => $product->old_price,
                    'purchase_price' => $product->purchase_price,
                    'product_variant_id' => null,
                    'variant_id' => null,
                    'warehouse_id' => $warehouseId,
                ],
            ]);

            $data = Cart::instance('shopping')->content();

            return $this->renderCartResponse($request, $data);
        }

        return response()->json(['success' => false, 'message' => 'Product not found.']);
    }

    private function renderCartResponse(Request $request, $data)
    {
        if ($this->isCampaignCartRequest($request)) {
            return view('frontEnd.components.campaign.cart-table', compact('data'));
        }

        return view('frontEnd.layouts.ajax.cart', compact('data'));
    }

    private function isCampaignCartRequest(Request $request): bool
    {
        return (string) $request->input('context', '') === 'campaign';
    }

    private function getAvailableStock(int $productId): float
    {
        return (float) WarehouseStock::query()
            ->where('product_id', $productId)
            ->sum('available_quantity');
    }

    private function getAvailableVariantStock(int $variantId): float
    {
        $warehouseId = Session::get('warehouse_id');
        if (! $warehouseId) {
            $warehouseId = optional(Warehouse::main()->active()->first() ?? Warehouse::active()->first())->id;
            if ($warehouseId) {
                Session::put('warehouse_id', (int) $warehouseId);
            }
        }

        if ($warehouseId) {
            $availableStock = (float) (Inventory::query()
                ->where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->selectRaw('SUM(CASE WHEN (quantity_available - quantity_reserved) > 0 THEN (quantity_available - quantity_reserved) ELSE 0 END) AS total_available')
                ->value('total_available') ?? 0);

            $warehouseStock = (float) (WarehouseStock::query()
                ->where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->selectRaw('SUM(CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END) AS total_available')
                ->value('total_available') ?? 0);

            return max($availableStock, $warehouseStock);
        }

        $availableStock = (float) (Inventory::query()
            ->where('product_variant_id', $variantId)
            ->selectRaw('SUM(CASE WHEN (quantity_available - quantity_reserved) > 0 THEN (quantity_available - quantity_reserved) ELSE 0 END) AS total_available')
            ->value('total_available') ?? 0);

        if ($availableStock > 0) {
            return $availableStock;
        }

        $warehouseStock = (float) (WarehouseStock::query()
            ->where('product_variant_id', $variantId)
            ->selectRaw('SUM(CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END) AS total_available')
            ->value('total_available') ?? 0);

        if ($warehouseStock > 0) {
            return $warehouseStock;
        }

        return 0;
    }

    private function resolveRequestedVariant(Product $product, Request $request): ?ProductVariant
    {
        $productId = (int) $product->id;

        $explicitVariantId = (int) ($request->product_variant_id ?: $request->variant_id ?: 0);
        if ($explicitVariantId > 0) {
            $variant = ProductVariant::query()
                ->whereKey($explicitVariantId)
                ->where('product_id', $productId)
                ->where('status', 'active')
                ->first();
            if ($variant) {
                return $variant;
            }
        }

        $size = trim((string) $request->product_size);
        $color = trim((string) $request->product_color);

        if ($size !== '' || $color !== '') {
            $query = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('status', 'active');

            if ($size !== '') {
                $query->where('size', $size);
            }
            if ($color !== '') {
                $query->where('color', $color);
            }

            $matched = $query->first();
            if ($matched) {
                return $matched;
            }
        }

        $singleVariant = ProductVariant::query()
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->limit(2)
            ->get();

        return $singleVariant->count() === 1 ? $singleVariant->first() : null;
    }

    private function resolvePreferredImagePath(Product $product, ?ProductVariant $variant = null): string
    {
        if ($variant) {
            $variant->loadMissing(['primaryVariantImage', 'variantImages']);
            $variantCandidates = [
                $variant->primaryVariantImage?->image_path,
                $variant->variantImages->first()?->image_path,
                $variant->image,
            ];

            foreach ($variantCandidates as $candidate) {
                $normalizedVariantPath = $this->normalizeImagePath((string) $candidate);
                if ($normalizedVariantPath === '') {
                    continue;
                }

                if (Str::startsWith($normalizedVariantPath, ['http://', 'https://', 'data:']) || is_file(base_path($normalizedVariantPath))) {
                    return $normalizedVariantPath;
                }
            }
        }

        return $product->display_image;
    }

    private function normalizeImagePath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, ['http://', 'https://', 'data:'])) {
            return $normalized;
        }

        if (Str::startsWith($normalized, 'public/')) {
            return $normalized;
        }

        if (Str::startsWith($normalized, 'storage/')) {
            return 'public/' . $normalized;
        }

        if (Str::startsWith($normalized, 'uploads/')) {
            return 'public/' . $normalized;
        }

        return 'public/storage/' . $normalized;
    }

    /**
     * Apply coupon code to shopping cart
     */
    public function applyCoupon(Request $request)
    {
        $couponCode = trim((string) ($request->coupon_code ?? ''));

        if (empty($couponCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon code cannot be empty',
            ], 400);
        }

        // TODO: Implement coupon validation logic
        // For now, return an error message
        return response()->json([
            'success' => false,
            'message' => 'Coupon feature is not yet implemented',
        ], 400);
    }
}
