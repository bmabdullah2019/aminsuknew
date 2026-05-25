<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Age;
use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Color;
use App\Models\Product;
use App\Models\Productage;
use App\Models\Productcolor;
use App\Models\Productimage;
use App\Models\Productsize;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\ShippingProfile;
use App\Models\Size;
use App\Models\StockMovement;
use App\Models\Subcategory;
use App\Models\VariantImage;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockService;
use App\Services\VariantAttributeService;
use DB;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Str;
use Toastr;

class ProductController extends Controller
{
    protected $stockService;

    protected VariantAttributeService $variantAttributeService;

    public function __construct(StockService $stockService, VariantAttributeService $variantAttributeService)
    {
        $this->stockService = $stockService;
        $this->variantAttributeService = $variantAttributeService;

        $this->middleware('permission:product-list|product-create|product-edit|product-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:product-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:product-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:product-delete', ['only' => ['destroy']]);
    }

    public function getSubcategory(Request $request)
    {
        $subcategory = DB::table('subcategories')
            ->where('category_id', $request->category_id)
            ->pluck('subcategoryName', 'id');

        return response()->json($subcategory);
    }

    public function getChildcategory(Request $request)
    {
        $childcategory = DB::table('childcategories')
            ->where('subcategory_id', $request->subcategory_id)
            ->pluck('childcategoryName', 'id');

        return response()->json($childcategory);
    }

    public function index(Request $request)
    {
        if ($request->keyword) {
            $data = Product::orderBy('id', 'DESC')->where('name', 'LIKE', '%'.$request->keyword.'%')->with('image', 'category')->paginate(50);
        } else {
            $data = Product::orderBy('id', 'DESC')->with('image', 'category')->paginate(50);
        }

        return view('backEnd.product.index', compact('data'));
    }

    public function create(Request $request)
    {
        $productType = strtolower((string) $request->get('product_type', 'simple'));
        if (! in_array($productType, ['simple', 'variable'], true)) {
            $productType = 'simple';
        }

        $selectedCategoryId = old('category_id', $request->get('category_id'));
        $selectedSubcategoryId = old('subcategory_id', $request->get('subcategory_id'));

        // Get categories with their relationships
        $categories = Category::where('parent_id', 0)
            ->where('status', 1)
            ->with('childrenCategories')
            ->orderBy('id')
            ->get();

        $brands = Brand::where('status', 1)->get();
        $colors = Color::where('status', '1')->get();
        $sizes = Size::where('status', '1')->get();
        $ages = Age::where('status', '1')->get();
        $catalogAttributes = CatalogAttribute::query()
            ->active()
            ->with(['values' => function ($query) {
                $query->active()->orderBy('sort_order')->orderBy('value');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subcategories = collect();
        $childcategories = collect();

        if (! empty($selectedCategoryId)) {
            $subcategories = Subcategory::where('category_id', (int) $selectedCategoryId)
                ->where('status', 1)
                ->orderBy('subcategoryName')
                ->get(['id', 'subcategoryName']);
        }

        if (! empty($selectedSubcategoryId)) {
            $childcategories = Childcategory::where('subcategory_id', (int) $selectedSubcategoryId)
                ->where('status', 1)
                ->orderBy('childcategoryName')
                ->get(['id', 'childcategoryName']);
        }

        $shippingProfiles = $this->shippingProfilesForForm();

        return view('backEnd.product.create', compact(
            'categories',
            'brands',
            'colors',
            'sizes',
            'ages',
            'catalogAttributes',
            'productType',
            'subcategories',
            'childcategories',
            'shippingProfiles'
        ));
    }

    public function store(Request $request)
    {
        $productType = strtolower((string) $request->get('product_type', 'simple'));
        if (! in_array($productType, ['simple', 'variable'], true)) {
            $productType = 'simple';
        }
        $isVariableProduct = $productType === 'variable';
        if ($isVariableProduct) {
            $request->merge([
                'variants' => $this->activeSubmittedVariants($request->input('variants', [])),
            ]);
        }

        $validationRules = [
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'subcategory_id' => 'nullable|integer',
            'childcategory_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'purchase_price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'new_price' => $isVariableProduct ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'required|string',
            'initial_stock' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|image|max:4096',
            'gallery_existing' => 'nullable|array',
            'gallery_existing.*' => 'nullable|string|max:500',
        ] + $this->shippingValidationRules();

        if ($isVariableProduct) {
            $validationRules['selected_attribute_ids'] = 'required|array|min:1';
            $validationRules['selected_attribute_ids.*'] = 'required|integer|exists:catalog_attributes,id';
            $validationRules['variants'] = 'required|array|min:1';
            $validationRules['variants.*.attribute_value_map'] = 'required|array|min:1';
            $validationRules['variants.*.attribute_value_map.*'] = 'nullable|integer|exists:catalog_attribute_values,id';
            $validationRules['variants.*.sku'] = 'nullable|string|max:100';
            $validationRules['variants.*.sku_code'] = 'nullable|string|max:100';
            $validationRules['variants.*.price'] = 'required|numeric|min:0';
            $validationRules['variants.*.stock'] = 'nullable|numeric|min:0';
            $validationRules['variants.*.images'] = 'nullable|array';
            $validationRules['variants.*.images.*'] = 'nullable|image|max:4096';
            $validationRules['variants.*.existing_images'] = 'nullable|array';
            $validationRules['variants.*.existing_images.*'] = 'nullable|string|max:500';
            $validationRules['variants.*.primary_image_index'] = 'nullable|integer|min:0';
        }

        $this->validate($request, $validationRules);

        if (! $isVariableProduct && (float) $request->new_price <= (float) $request->purchase_price) {
            Toastr::error('Selling price must be higher than purchase price', 'Validation Error');

            return redirect()->back()->withInput();
        }

        $lastId = Product::max('id') ?? 0;
        $newId = $lastId + 1;

        $input = $request->except([
            'image',
            'thumbnail',
            'files',
            'proSize',
            'proColor',
            'proAge',
            'initial_stock',
            'product_type',
            'variants',
            'gallery_existing',
            'selected_attribute_ids',
            'shipping_type',
            'shipping_profile_id',
            'fixed_shipping_cost',
            'weight',
            'length',
            'width',
            'height',
            'is_physical',
        ]);

        $baseSlug = $this->generateSlug($request->name);
        $input['slug'] = $this->ensureUniqueSlug($baseSlug, $newId);
        $input['sku'] = $this->generateUniqueSKU($request->name, $request->category_id);

        if ($isVariableProduct) {
            $firstVariantPrice = collect($request->input('variants', []))
                ->pluck('price')
                ->filter(fn ($price) => is_numeric($price))
                ->map(fn ($price) => (float) $price)
                ->first();
            $input['new_price'] = $firstVariantPrice ?? (float) ($request->new_price ?? 0);
        } else {
            $input['new_price'] = (float) $request->new_price;
        }

        $input['old_price'] = $request->filled('old_price')
            ? (float) $request->old_price
            : (float) $input['new_price'];
        $input['pro_video'] = $this->getYouTubeVideoId($request->pro_video);
        $input['status'] = $request->status ? 1 : 0;
        $input['topsale'] = $request->topsale ? 1 : 0;
        $input['feature_product'] = $request->feature_product ? 1 : 0;
        $input['product_code'] = 'P'.str_pad($newId, 4, '0', STR_PAD_LEFT);
        $input['has_variant'] = $isVariableProduct;
        $input = array_merge($input, $this->shippingInput($request));

        $storedPublicPaths = [];

        try {
            DB::beginTransaction();

            if ($thumbnailFile = $this->firstValidUploadedImage($request->file('thumbnail'))) {
                $thumbnailPath = $this->storeUploadedImageFile($thumbnailFile, 'products/thumbnails');
                $this->syncStorageFile($thumbnailPath);
                $input['thumbnail'] = $thumbnailPath;
                $storedPublicPaths[] = $thumbnailPath;
            }

            $createdProduct = Product::create($input);

            if (! $isVariableProduct) {
                if ($request->has('proSize')) {
                    $createdProduct->sizes()->attach($request->proSize);
                }
                if ($request->has('proColor')) {
                    $createdProduct->colors()->attach($request->proColor);
                }
                if ($request->has('proAge')) {
                    $createdProduct->ages()->attach($request->proAge);
                }
            }

            $hasLegacyGalleryUpload = false;
            if ($request->hasFile('image')) {
                $legacyImages = $this->validUploadedImages($request->file('image'));
                if (! empty($legacyImages)) {
                    $this->handleImageUploads($legacyImages, $createdProduct->id, $createdProduct->name);
                    $hasLegacyGalleryUpload = true;
                }
            }

            $hasExistingGallerySelection = $this->attachExistingGalleryImages(
                $createdProduct,
                $request->input('gallery_existing', [])
            );
            $hasLegacyGalleryUpload = $hasLegacyGalleryUpload || $hasExistingGallerySelection;

            // Keep backward compatibility with screens that still read from `productimages`.
            // If only thumbnail is provided, save it as the primary product image as well.
            if (
                ! $hasLegacyGalleryUpload &&
                ! empty($input['thumbnail']) &&
                ! Productimage::where('product_id', $createdProduct->id)->exists()
            ) {
                Productimage::create([
                    'product_id' => $createdProduct->id,
                    'image' => $this->toLegacyImageDatabasePath((string) $input['thumbnail']),
                ]);
            }

            if ($isVariableProduct) {
                $this->saveColorVariantsWithImages($createdProduct, $request, $storedPublicPaths);
            } elseif ((float) $request->initial_stock > 0) {
                $this->createInitialStock($createdProduct, (float) $request->initial_stock);
            }

            $this->logProductChange($createdProduct->id, 'created', 'Product created', $input);
            DB::commit();
        } catch (ValidationException $exception) {
            DB::rollBack();

            foreach (array_unique($storedPublicPaths) as $path) {
                if (! empty($path)) {
                    $this->deleteStoredAsset($path);
                }
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            DB::rollBack();

            foreach (array_unique($storedPublicPaths) as $path) {
                if (! empty($path)) {
                    $this->deleteStoredAsset($path);
                }
            }

            report($exception);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save product. Please try again.']);
        }

        Toastr::success('Success', $isVariableProduct ? 'Variable product created successfully' : 'Product created successfully');

        return redirect()
            ->route('admin.products.index')
            ->with('success', $isVariableProduct ? 'Variable product created successfully' : 'Product created successfully');
    }

    public function edit($id)
    {
        $edit_data = Product::with([
            'images',
            'productVariants.variantImages',
            'productVariants.variantAttributeValues.attribute',
            'productVariants.variantAttributeValues.value',
        ])->findOrFail($id);
        $categories = Category::where('parent_id', '=', '0')
            ->where('status', 1)
            ->with('childrenCategories')
            ->select('id', 'name', 'status')
            ->get();
        $categoryId = $edit_data->category_id;
        $subcategoryId = $edit_data->subcategory_id;
        $subcategory = Subcategory::where('category_id', '=', $categoryId)->select('id', 'subcategoryName', 'status')->get();
        $childcategory = Childcategory::where('subcategory_id', '=', $subcategoryId)->select('id', 'childcategoryName', 'status')->get();
        $brands = Brand::where('status', '1')->select('id', 'name', 'status')->get();
        $totalsizes = Size::where('status', 1)->get();
        $totalcolors = Color::where('status', 1)->get();
        $totalages = Age::where('status', 1)->get();
        $selectcolors = Productcolor::where('product_id', $id)->get();
        $selectsizes = Productsize::where('product_id', $id)->get();
        $selectages = Productage::where('product_id', $id)->get();
        $productVariants = ProductVariant::where('product_id', $id)
            ->with([
                'variantImages',
                'variantAttributeValues.attribute',
                'variantAttributeValues.value',
            ])
            ->orderBy('id')
            ->get();

        if (! $edit_data->has_variant && $productVariants->isNotEmpty()) {
            $edit_data->has_variant = true;
        }
        $catalogAttributes = CatalogAttribute::query()
            ->active()
            ->with(['values' => function ($query) {
                $query->active()->orderBy('sort_order')->orderBy('value');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Get warehouse stock information - relationship now filters by product_variant_id IS NULL automatically
        $warehouseStocks = \App\Models\WarehouseStock::where('product_id', $id)
            ->whereNull('product_variant_id')
            ->with('warehouse')
            ->get();
        $shippingProfiles = $this->shippingProfilesForForm();

        return view('backEnd.product.edit', compact(
            'edit_data',
            'categories',
            'subcategory',
            'childcategory',
            'brands',
            'selectcolors',
            'selectsizes',
            'totalsizes',
            'totalcolors',
            'selectages',
            'totalages',
            'warehouseStocks',
            'productVariants',
            'catalogAttributes',
            'shippingProfiles'
        ));
    }

    public function price_edit()
    {
        $products = DB::table('products')->select('id', 'name', 'status', 'old_price', 'new_price')->where('status', 1)->get();

        return view('backEnd.product.price_edit', compact('products'));
    }

    public function price_update(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required',
            'new_price' => 'required|numeric|min:0',
        ]);

        $product = Product::findOrFail($request->product_id);
        $product->new_price = $request->new_price;
        $product->old_price = $request->old_price ?: $product->new_price;
        $product->save();

        Toastr::success('Product price updated successfully', 'Success');

        return redirect()->back();
    }

    /**
     * API method for searching products (used in stock management)
     */
    public function apiSearch(Request $request)
    {
        try {
            $query = trim((string) $request->get('q', ''));
            $limit = (int) $request->get('limit', 10);
            $limit = max(1, min($limit, 50));

            $isNumeric = ctype_digit($query);
            if ($query === '' || (! $isNumeric && mb_strlen($query) < 2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Query too short',
                    'data' => [],
                ]);
            }

            $contains = "%{$query}%";
            $startsWith = "{$query}%";

            $productsQuery = Product::where('status', 1)
                ->where(function ($q) use ($query, $contains, $isNumeric) {
                    if ($isNumeric) {
                        $q->orWhere('id', (int) $query);
                    }

                    $q->orWhere('name', 'like', $contains)
                        ->orWhere('sku', 'like', $contains)
                        ->orWhere('product_code', 'like', $contains);
                })
                ->select('id', 'name', 'sku', 'product_code', 'new_price');

            // Relevance ordering: exact SKU/code/id first, then prefix matches, then contains.
            $productsQuery->orderByRaw(
                'CASE '
                .'WHEN sku = ? THEN 0 '
                .'WHEN product_code = ? THEN 1 '
                .'WHEN id = ? THEN 2 '
                .'WHEN name LIKE ? THEN 3 '
                .'WHEN sku LIKE ? THEN 4 '
                .'WHEN product_code LIKE ? THEN 5 '
                .'ELSE 6 END',
                [
                    $query,
                    $query,
                    $isNumeric ? (int) $query : -1,
                    $startsWith,
                    $startsWith,
                    $startsWith,
                ]
            );

            $products = $productsQuery
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'product_code' => $product->product_code,
                        'new_price' => $product->new_price,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);

        } catch (\Exception $e) {
            \Log::error('Product search API error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Search failed: '.$e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $productType = strtolower((string) $request->get('product_type', $product->has_variant ? 'variable' : 'simple'));
        if (! in_array($productType, ['simple', 'variable'], true)) {
            $productType = $product->has_variant ? 'variable' : 'simple';
        }
        $isVariableProduct = $productType === 'variable';
        if ($isVariableProduct) {
            $request->merge([
                'variants' => $this->activeSubmittedVariants($request->input('variants', [])),
            ]);
        }

        $validationRules = [
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'subcategory_id' => 'nullable|integer',
            'childcategory_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'purchase_price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'new_price' => $isVariableProduct ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'required|string',
            'thumbnail' => 'nullable|image|max:4096',
            'gallery_existing' => 'nullable|array',
            'gallery_existing.*' => 'nullable|string|max:500',
        ] + $this->shippingValidationRules();

        if ($isVariableProduct) {
            $validationRules['selected_attribute_ids'] = 'required|array|min:1';
            $validationRules['selected_attribute_ids.*'] = 'required|integer|exists:catalog_attributes,id';
            $validationRules['variants'] = 'required|array|min:1';
            $validationRules['variants.*.id'] = 'nullable|integer';
            $validationRules['variants.*.attribute_value_map'] = 'required|array|min:1';
            $validationRules['variants.*.attribute_value_map.*'] = 'nullable|integer|exists:catalog_attribute_values,id';
            $validationRules['variants.*.sku'] = 'nullable|string|max:100';
            $validationRules['variants.*.sku_code'] = 'nullable|string|max:100';
            $validationRules['variants.*.price'] = 'required|numeric|min:0';
            $validationRules['variants.*.stock'] = 'nullable|numeric|min:0';
            $validationRules['variants.*.images'] = 'nullable|array';
            $validationRules['variants.*.images.*'] = 'nullable|image|max:4096';
            $validationRules['variants.*.existing_images'] = 'nullable|array';
            $validationRules['variants.*.existing_images.*'] = 'nullable|string|max:500';
            $validationRules['variants.*.primary_image_index'] = 'nullable|integer|min:0';
        }

        $this->validate($request, $validationRules);

        if (! $isVariableProduct && (float) $request->new_price <= (float) $request->purchase_price) {
            Toastr::error('Selling price must be higher than purchase price', 'Validation Error');

            return redirect()->back()->withInput();
        }

        $storedPublicPaths = [];
        $previousThumbnailPath = null;

        $input = $request->except([
            'image',
            'thumbnail',
            'variants',
            'proSize',
            'proColor',
            'proAge',
            'product_type',
            'gallery_existing',
            'selected_attribute_ids',
            'shipping_type',
            'shipping_profile_id',
            'fixed_shipping_cost',
            'weight',
            'length',
            'width',
            'height',
            'is_physical',
        ]);
        $input['slug'] = $this->ensureUniqueSlug($this->generateSlug($request->name), $request->id);
        $input['status'] = $request->boolean('status');
        $input['topsale'] = $request->boolean('topsale');
        $input['flashsale'] = $request->boolean('flashsale');
        $input['feature_product'] = $request->has('feature_product')
            ? (int) $request->boolean('feature_product')
            : (int) $product->feature_product;
        $input['pro_video'] = $this->getYouTubeVideoId($request->pro_video);
        $input['has_variant'] = $isVariableProduct;

        if ($isVariableProduct) {
            $firstVariantPrice = collect($request->input('variants', []))
                ->pluck('price')
                ->filter(fn ($price) => is_numeric($price))
                ->map(fn ($price) => (float) $price)
                ->first();
            $input['new_price'] = $firstVariantPrice ?? (float) ($request->new_price ?? $product->new_price ?? 0);
        } else {
            $input['new_price'] = (float) $request->new_price;
        }

        $input['old_price'] = $request->filled('old_price')
            ? (float) $request->old_price
            : (float) $input['new_price'];
        $input = array_merge($input, $this->shippingInput($request));

        $thumbnailFile = $this->firstValidUploadedImage($request->file('thumbnail'));
        if ($thumbnailFile) {
            $thumbnailPath = $this->storeUploadedImageFile($thumbnailFile, 'products/thumbnails');
            $this->syncStorageFile($thumbnailPath);
            $input['thumbnail'] = $thumbnailPath;
            $storedPublicPaths[] = $thumbnailPath;
            $previousThumbnailPath = (string) $product->getRawOriginal('thumbnail');

            // Backward compatibility for Productimage table (no is_primary column — use first gallery row)
        }

        try {
            DB::beginTransaction();

            $product->update($input);

            if ($thumbnailFile) {
                // Only sync to Productimage if there's no gallery yet, 
                // or if the existing first image is already a thumbnail (legacy sync)
                $pi = Productimage::where('product_id', $product->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();
                
                if ($pi && strpos($pi->image, 'thumbnails/') !== false) {
                    $pi->update(['image' => $this->toLegacyImageDatabasePath((string) $product->thumbnail)]);
                } elseif (!$pi) {
                     Productimage::create([
                        'product_id' => $product->id,
                        'image' => $this->toLegacyImageDatabasePath((string) $product->thumbnail),
                        'alt_text' => $product->name,
                        'sort_order' => 1,
                    ]);
                }
            }

            if ($isVariableProduct) {
                $this->syncDynamicVariantsForUpdate($product, $request, $storedPublicPaths);

                if ($request->hasFile('image')) {
                    $legacyImages = $this->validUploadedImages($request->file('image'));
                    if (! empty($legacyImages)) {
                        $this->handleImageUploads($legacyImages, $product->id, $product->name);
                    }
                }

                $this->attachExistingGalleryImages(
                    $product,
                    $request->input('gallery_existing', [])
                );
            } else {
                if ($request->has('proSize')) {
                    $product->sizes()->sync($request->proSize);
                }
                if ($request->has('proColor')) {
                    $product->colors()->sync($request->proColor);
                }
                if ($request->has('proAge')) {
                    $product->ages()->sync($request->proAge);
                }

                if ($request->hasFile('image')) {
                    $legacyImages = $this->validUploadedImages($request->file('image'));
                    if (! empty($legacyImages)) {
                        $this->handleImageUploads($legacyImages, $product->id, $product->name);
                    }
                }

                $this->attachExistingGalleryImages(
                    $product,
                    $request->input('gallery_existing', [])
                );
            }

            DB::commit();
        } catch (ValidationException $exception) {
            DB::rollBack();

            foreach (array_unique($storedPublicPaths) as $path) {
                if (! empty($path)) {
                    $this->deleteStoredAsset($path);
                }
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            DB::rollBack();

            foreach (array_unique($storedPublicPaths) as $path) {
                if (! empty($path)) {
                    $this->deleteStoredAsset($path);
                }
            }

            report($exception);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update product. Please try again.']);
        }

        if ($previousThumbnailPath !== null && $previousThumbnailPath !== (string) $product->getRawOriginal('thumbnail')) {
            $this->deleteStoredAsset($previousThumbnailPath);
        }

        Toastr::success('Success', $isVariableProduct ? 'Variable product updated successfully' : 'Product updated successfully');

        return redirect()->route('admin.products.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Product::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Product::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Product::findOrFail($request->hidden_id);

        if (! $delete_data) {
            Toastr::error('Product not found', 'Error');

            return redirect()->back();
        }

        $filesToDelete = [];

        try {
            DB::transaction(function () use ($delete_data, &$filesToDelete) {
                // Collect file paths first; delete files only after DB transaction succeeds.
                $images = Productimage::where('product_id', $delete_data->id)->get();
                foreach ($images as $image) {
                    if (! empty($image->image)) {
                        $filesToDelete[] = $image->image;
                    }
                    if (! empty($image->webp_image)) {
                        $filesToDelete[] = $image->webp_image;
                    }
                }

                Productimage::where('product_id', $delete_data->id)->delete();
                $productPriceModel = 'App\\Models\\Productprice';
                if (class_exists($productPriceModel)) {
                    $productPriceModel::where('product_id', $delete_data->id)->delete();
                }
                Productcolor::where('product_id', $delete_data->id)->delete();
                Productsize::where('product_id', $delete_data->id)->delete();
                Productage::where('product_id', $delete_data->id)->delete();

                // Keep many-to-many tables clean for products without FK blockers.
                $delete_data->sizes()->detach();
                $delete_data->colors()->detach();
                $delete_data->ages()->detach();

                // Defensive cascade for variable products. The 2026_05_24 FK
                // migration also enforces this at the DB level, but keeping the
                // application-level cleanup means we never depend on InnoDB or
                // older schemas that lack the FKs and would otherwise leave
                // orphan variants. When MySQL later reissues this product's
                // auto-increment id, those orphans would re-attach to the new
                // product and surface as "phantom variants with wrong images".
                $variantIds = \App\Models\ProductVariant::where('product_id', $delete_data->id)
                    ->pluck('id')
                    ->all();
                if (! empty($variantIds)) {
                    \App\Models\ProductVariantAttributeValue::whereIn('product_variant_id', $variantIds)->delete();
                    \App\Models\VariantImage::whereIn('product_variant_id', $variantIds)->delete();
                    if (Schema::hasTable('warehouse_stocks')) {
                        \DB::table('warehouse_stocks')->whereIn('product_variant_id', $variantIds)->delete();
                    }
                    if (Schema::hasTable('inventories')) {
                        \DB::table('inventories')->whereIn('product_variant_id', $variantIds)->delete();
                    }
                    \App\Models\ProductVariant::whereIn('id', $variantIds)->delete();
                }

                $delete_data->delete();
            });

            foreach (array_unique($filesToDelete) as $path) {
                $this->deleteStoredAsset((string) $path);
            }

            Toastr::success('Success', 'Data delete successfully');
        } catch (\Illuminate\Database\QueryException $e) {
            // FK dependency exists (e.g. stock_movements/order history) -> archive instead of delete.
            if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
                $delete_data->status = 0;
                $delete_data->save();
                Toastr::warning('Product has transaction history, so it was set inactive instead of deleted.', 'Delete blocked');
            } else {
                throw $e;
            }
        }

        return redirect()->back();
    }

    public function imgdestroy(Request $request)
    {
        $delete_data = Productimage::findOrFail($request->id);

        if (! $delete_data) {
            Toastr::error('Image not found', 'Error');

            return redirect()->back();
        }

        $this->deleteStoredAsset((string) $delete_data->getRawOriginal('image'));
        $this->deleteStoredAsset((string) $delete_data->getRawOriginal('webp_image'));

        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');

        return redirect()->back();
    }

    public function pricedestroy(Request $request)
    {
        $productPriceModel = 'App\\Models\\Productprice';
        if (! class_exists($productPriceModel)) {
            Toastr::warning('Product price model is not available in this build.', 'Unavailable');

            return redirect()->back();
        }

        $delete_data = $productPriceModel::findOrFail($request->id);
        if (! $delete_data) {
            Toastr::error('Price entry not found', 'Error');

            return redirect()->back();
        }

        $delete_data->delete();
        Toastr::success('Success', 'Product price delete successfully');

        return redirect()->back();
    }

    public function update_deals(Request $request)
    {
        $products = Product::whereIn('id', $request->input('product_ids'))->update(['topsale' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Hot deals product status change']);
    }

    public function update_feature(Request $request)
    {
        $products = Product::whereIn('id', $request->input('product_ids'))->update(['feature_product' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Feature product status change']);
    }

    public function update_status(Request $request)
    {
        $products = Product::whereIn('id', $request->input('product_ids'))->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Product status change successfully']);
    }

    public function getYouTubeVideoId($input)
    {
        if (empty($input)) {
            return null;
        }

        $input = (string) $input;

        // Check if the input is a valid YouTube video ID (11 characters long)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
            return $input; // Return the ID directly if it's valid
        }

        // Regular expression to match YouTube video URLs
        $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';

        // Execute the regex pattern
        preg_match($pattern, $input, $matches);

        // Check if a match was found and return the video ID or null
        return isset($matches[1]) ? $matches[1] : null;
    }

    /**
     * Generate SEO-friendly slug from product name
     */
    private function generateSlug($name)
    {
        return Str::slug($name);
    }

    /**
     * Ensure slug uniqueness with incremental suffix
     */
    private function ensureUniqueSlug($baseSlug, $productId, $counter = 1)
    {
        $slug = $baseSlug;
        if ($counter > 1) {
            $slug = $baseSlug.'-'.$counter;
        }

        $exists = Product::where('slug', $slug)
            ->where('id', '!=', $productId)
            ->exists();

        if ($exists) {
            return $this->ensureUniqueSlug($baseSlug, $productId, $counter + 1);
        }

        return $slug;
    }

    /**
     * Generate unique SKU with category prefix
     */
    private function generateUniqueSKU($productName, $categoryId, $attempt = 1)
    {
        $category = Category::find($categoryId);
        $categoryPrefix = $this->buildSkuCategoryPrefix($category?->name);
        $namePart = $this->buildSkuNamePart($productName);

        $baseSKU = $categoryPrefix.'-'.$namePart;
        if ($attempt > 1) {
            $baseSKU .= '-'.$attempt;
        }

        // Check uniqueness
        $exists = Product::where('sku', $baseSKU)->exists();

        if ($exists) {
            return $this->generateUniqueSKU($productName, $categoryId, $attempt + 1);
        }

        return $baseSKU;
    }

    /**
     * Persist variable product variants and their image gallery.
     * Each variant gets one color option and multiple images.
     */
    private function saveColorVariantsWithImages(Product $product, Request $request, array &$storedPublicPaths = []): void
    {
        $variants = $request->input('variants', []);
        $selectedAttributeIds = collect($request->input('selected_attribute_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($selectedAttributeIds)) {
            throw ValidationException::withMessages([
                'selected_attribute_ids' => 'Select at least one catalog attribute for variant products.',
            ]);
        }

        $seenCombinations = [];
        $variantGalleryForProduct = [];

        foreach ($variants as $index => $variantPayload) {
            $attributeValueMap = collect($variantPayload['attribute_value_map'] ?? [])
                ->mapWithKeys(fn ($valueId, $attributeId) => [(int) $attributeId => (int) $valueId]);

            $requestedValueIds = [];
            foreach ($selectedAttributeIds as $attributeId) {
                $selectedValueId = (int) ($attributeValueMap[$attributeId] ?? 0);
                if ($selectedValueId <= 0) {
                    throw ValidationException::withMessages([
                        "variants.$index.attribute_value_map.$attributeId" => 'Select value for every selected attribute.',
                    ]);
                }

                $requestedValueIds[] = $selectedValueId;
            }

            $normalizedValueIds = $this->variantAttributeService->normalizeValueIds($requestedValueIds);
            if (count($normalizedValueIds) !== count($selectedAttributeIds)) {
                throw ValidationException::withMessages([
                    "variants.$index.attribute_value_map" => 'Each selected attribute must use a unique value.',
                ]);
            }

            $valueRows = $this->variantAttributeService->getValueRowsByIds($normalizedValueIds);
            if ($valueRows->count() !== count($normalizedValueIds)) {
                throw ValidationException::withMessages([
                    "variants.$index.attribute_value_map" => 'One or more selected attribute values are invalid.',
                ]);
            }

            $rowAttributeIds = $valueRows->pluck('attribute_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            $expectedAttributeIds = $selectedAttributeIds;
            sort($rowAttributeIds);
            sort($expectedAttributeIds);

            if ($rowAttributeIds !== $expectedAttributeIds) {
                throw ValidationException::withMessages([
                    "variants.$index.attribute_value_map" => 'Selected values must match the selected attributes.',
                ]);
            }

            $combinationKey = $this->variantAttributeService->buildCombinationKey($normalizedValueIds);
            if (in_array($combinationKey, $seenCombinations, true)) {
                throw ValidationException::withMessages([
                    "variants.$index.attribute_value_map" => 'Duplicate variant combination is not allowed.',
                ]);
            }
            $seenCombinations[] = $combinationKey;

            $axes = $this->variantAttributeService->resolveLegacyAxesFromValueRows($valueRows);
            $color = $axes['color'];
            $size = $axes['size'];
            $age = $axes['age'];

            $requestedSku = trim((string) ($variantPayload['sku'] ?? $variantPayload['sku_code'] ?? ''));
            $skuCode = $requestedSku !== ''
                ? $this->ensureUniqueVariantSku($requestedSku)
                : $this->generateAutoVariantSkuFromValueRows($product, $valueRows, $index + 1);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku_code' => $skuCode,
                'combination_key' => $combinationKey,
                'color' => $color,
                'size' => $size,
                'age' => $age,
                'price' => (float) ($variantPayload['price'] ?? 0),
                'stock' => 0,
                'cost_price' => (float) ($request->purchase_price ?? 0),
                'status' => 'active',
            ]);

            foreach ($valueRows as $valueRow) {
                ProductVariantAttributeValue::query()->create([
                    'product_variant_id' => (int) $variant->id,
                    'catalog_attribute_id' => (int) $valueRow->attribute_id,
                    'catalog_attribute_value_id' => (int) $valueRow->value_id,
                ]);
            }

            $uploadedImages = $this->validUploadedImages($request->file("variants.$index.images", []));
            $existingImages = collect((array) ($variantPayload['existing_images'] ?? []))
                ->map(fn ($path) => $this->normalizeGalleryAssetPath((string) $path))
                ->filter()
                ->unique(fn ($path) => $this->galleryAssetUniqueKey((string) $path))
                ->values()
                ->all();

            if (empty($uploadedImages) && empty($existingImages)) {
                throw ValidationException::withMessages([
                    "variants.$index.images" => 'At least one uploaded or existing image is required for each variant.',
                ]);
            }

            $orderedKeptPaths = [];
            $imageOrder = 0;

            foreach ($uploadedImages as $imageIndex => $uploadedImage) {
                $storedPath = $this->storeUploadedImageFile($uploadedImage, 'products/variants');
                $this->syncStorageFile($storedPath);
                $storedPublicPaths[] = $storedPath;
                $imageOrder++;

                VariantImage::create([
                    'product_variant_id' => $variant->id,
                    'image_path' => $storedPath,
                    'is_primary' => false, // resolved deterministically below
                    'sort_order' => $imageOrder,
                ]);

                $orderedKeptPaths[] = $storedPath;
                $variantGalleryForProduct[] = $storedPath;
            }

            foreach ($existingImages as $existingPath) {
                $imageOrder++;
                VariantImage::create([
                    'product_variant_id' => $variant->id,
                    'image_path' => $existingPath,
                    'is_primary' => false,
                    'sort_order' => $imageOrder,
                ]);

                $orderedKeptPaths[] = $existingPath;

                $normalizedExisting = ltrim((string) $existingPath, '/');
                if (\Illuminate\Support\Str::startsWith($normalizedExisting, 'storage/')) {
                    $normalizedExisting = \Illuminate\Support\Str::after($normalizedExisting, 'storage/');
                }

                $variantGalleryForProduct[] = $normalizedExisting;
            }

            // Decide primary deterministically: explicit picker first, else first row.
            $requestedPrimaryIndex = $variantPayload['primary_image_index'] ?? null;
            $primaryImagePath = (
                $requestedPrimaryIndex !== null
                && is_numeric($requestedPrimaryIndex)
                && isset($orderedKeptPaths[(int) $requestedPrimaryIndex])
            )
                ? $orderedKeptPaths[(int) $requestedPrimaryIndex]
                : ($orderedKeptPaths[0] ?? null);

            if ($primaryImagePath === null) {
                throw ValidationException::withMessages([
                    "variants.$index.images" => 'At least one valid image is required for each variant.',
                ]);
            }

            $variant->variantImages()
                ->where('image_path', $primaryImagePath)
                ->update(['is_primary' => true]);

            // Keep backward compatibility for screens that still read `product_variants.image`.
            $variant->image = $this->toLegacyImageDatabasePath((string) $primaryImagePath);
            $variant->save();

            // Fallback: If parent product has no thumbnail, use the first variant's primary image.
            if (empty($product->thumbnail) && $index === 0) {
                $product->update(['thumbnail' => $primaryImagePath]);
            }

            // Fallback: If parent product has no gallery entries, create one from the first variant's primary image.
            // This ensures the product is visible in listings and the details page slider.
            if ($index === 0 && ! Productimage::where('product_id', $product->id)->exists()) {
                Productimage::create([
                    'product_id' => $product->id,
                    'image' => $this->toLegacyImageDatabasePath((string) $primaryImagePath),
                    'alt_text' => $product->name,
                    'sort_order' => 1,
                ]);
            }
        }

        // Keep product-level gallery in sync with all uploaded variant images.
        // This prevents detail pages from missing variant photos when legacy gallery rows are sparse.
        if (! empty($variantGalleryForProduct)) {
            $existingImageMap = Productimage::where('product_id', $product->id)
                ->get(['id', 'image', 'sort_order'])
                ->mapWithKeys(function ($row) {
                    $normalized = $this->galleryAssetUniqueKey((string) $row->getRawOriginal('image'));

                    return [$normalized => true];
                })
                ->all();

            $maxSortOrder = (int) Productimage::where('product_id', $product->id)->max('sort_order');

            foreach (array_values(array_unique($variantGalleryForProduct)) as $storedPath) {
                $candidatePath = $this->toLegacyImageDatabasePath((string) $storedPath);
                $candidateKey = $this->galleryAssetUniqueKey($candidatePath);
                if (isset($existingImageMap[$candidateKey])) {
                    continue;
                }

                $maxSortOrder++;
                Productimage::create([
                    'product_id' => $product->id,
                    'image' => $candidatePath,
                    'alt_text' => $product->name,
                    'sort_order' => $maxSortOrder,
                ]);
                $existingImageMap[$candidateKey] = true;
            }
        }

        $this->syncProductLevelVariantStock($product);
    }

    private function activeSubmittedVariants($variants): array
    {
        // IMPORTANT: keep the original submitted array keys.
        //
        // $request->file("variants.$index.images") reads from the $_FILES superglobal,
        // which Laravel does NOT re-key when you merge() text input. If we collapsed
        // the keys with ->values() here, every saveColorVariantsWithImages() /
        // syncDynamicVariantsForUpdate() iteration whose original index had a gap
        // would pull files belonging to a different variant — causing the classic
        // "wrong image attached to wrong variant" bug after any row deletion or
        // _active=0 toggle. Filter only; never re-index.
        return collect(is_array($variants) ? $variants : [])
            ->filter(function ($variantPayload) {
                if (! is_array($variantPayload)) {
                    return false;
                }

                // Default to active when the flag is absent so legacy/edge submissions
                // are not silently dropped.
                return (string) ($variantPayload['_active'] ?? '1') === '1';
            })
            ->all();
    }

    private function syncDynamicVariantsForUpdate(Product $product, Request $request, array &$storedPublicPaths = []): void
    {
        $variants = $request->input('variants', []);
        $selectedAttributeIds = collect($request->input('selected_attribute_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($selectedAttributeIds)) {
            throw ValidationException::withMessages([
                'selected_attribute_ids' => 'Select at least one catalog attribute for variant products.',
            ]);
        }

        $submittedIds = collect($variants)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $removedVariantsQuery = ProductVariant::query()->where('product_id', $product->id);
        if (! empty($submittedIds)) {
            $removedVariantsQuery->whereNotIn('id', $submittedIds);
        }

        foreach ($removedVariantsQuery->get() as $removedVariant) {
            try {
                $removedVariant->delete();
            } catch (\Throwable $exception) {
                $removedVariant->update(['status' => 'inactive']);
            }
        }

        $seenCombinations = [];
        $variantGalleryForProduct = [];

        foreach ($variants as $index => $variantPayload) {
            $variantId = (int) ($variantPayload['id'] ?? 0);
            $variant = null;
            if ($variantId > 0) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->with('variantImages')
                    ->find($variantId);

                if (! $variant) {
                    throw ValidationException::withMessages([
                        "variants.$index.id" => 'Invalid variant reference supplied.',
                    ]);
                }
            }

            $attributeValueMap = collect($variantPayload['attribute_value_map'] ?? [])
                ->mapWithKeys(fn ($valueId, $attributeId) => [(int) $attributeId => (int) $valueId]);

            $requestedValueIds = [];
            foreach ($selectedAttributeIds as $attributeId) {
                $selectedValueId = (int) ($attributeValueMap[$attributeId] ?? 0);
                if ($selectedValueId <= 0) {
                    throw ValidationException::withMessages([
                        "variants.$index.attribute_value_map.$attributeId" => 'Select value for every selected attribute.',
                    ]);
                }

                $requestedValueIds[] = $selectedValueId;
            }

            $normalizedValueIds = $this->variantAttributeService->normalizeValueIds($requestedValueIds);
            if (count($normalizedValueIds) !== count($selectedAttributeIds)) {
                throw ValidationException::withMessages([
                    "variants.$index.attribute_value_map" => 'Each selected attribute must use a unique value.',
                ]);
            }

            $valueRows = $this->variantAttributeService->getValueRowsByIds($normalizedValueIds);
            if ($valueRows->count() !== count($normalizedValueIds)) {
                throw ValidationException::withMessages([
                    "variants.$index.attribute_value_map" => 'One or more selected attribute values are invalid.',
                ]);
            }

            $rowAttributeIds = $valueRows->pluck('attribute_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            $expectedAttributeIds = $selectedAttributeIds;
            sort($rowAttributeIds);
            sort($expectedAttributeIds);

            if ($rowAttributeIds !== $expectedAttributeIds) {
                throw ValidationException::withMessages([
                    "variants.$index.attribute_value_map" => 'Selected values must match the selected attributes.',
                ]);
            }

            $combinationKey = $this->variantAttributeService->buildCombinationKey($normalizedValueIds);
            if (in_array($combinationKey, $seenCombinations, true)) {
                throw ValidationException::withMessages([
                    "variants.$index.attribute_value_map" => 'Duplicate variant combination is not allowed.',
                ]);
            }
            $seenCombinations[] = $combinationKey;

            $axes = $this->variantAttributeService->resolveLegacyAxesFromValueRows($valueRows);
            $requestedSku = trim((string) ($variantPayload['sku'] ?? $variantPayload['sku_code'] ?? ''));
            $skuCode = $requestedSku !== ''
                ? $this->ensureUniqueVariantSku($requestedSku, 1, $variant?->id)
                : $this->generateAutoVariantSkuFromValueRows($product, $valueRows, $index + 1, $variant?->id);

            if (! $variant) {
                $variant = new ProductVariant;
                $variant->product_id = $product->id;
            }

            $variantData = [
                'sku_code' => $skuCode,
                'combination_key' => $combinationKey,
                'color' => $axes['color'],
                'size' => $axes['size'],
                'age' => $axes['age'],
                'price' => (float) ($variantPayload['price'] ?? 0),
                'cost_price' => (float) ($request->purchase_price ?? 0),
                'status' => 'active',
            ];
            if (! $variant->exists) {
                $variantData['stock'] = 0;
            }
            $variant->fill($variantData);
            $variant->save();

            $variant->variantAttributeValues()->delete();
            foreach ($valueRows as $valueRow) {
                ProductVariantAttributeValue::query()->create([
                    'product_variant_id' => (int) $variant->id,
                    'catalog_attribute_id' => (int) $valueRow->attribute_id,
                    'catalog_attribute_value_id' => (int) $valueRow->value_id,
                ]);
            }

            $uploadedImages = $this->validUploadedImages($request->file("variants.$index.images", []));
            $existingImages = collect((array) ($variantPayload['existing_images'] ?? []))
                ->map(fn ($path) => $this->normalizeGalleryAssetPath((string) $path))
                ->filter()
                ->unique(fn ($path) => $this->galleryAssetUniqueKey((string) $path))
                ->values();

            if ($existingImages->isEmpty() && $variant->relationLoaded('variantImages')) {
                $existingImages = $variant->variantImages
                    ->map(fn ($image) => $this->normalizeGalleryAssetPath((string) ($image->getRawOriginal('image_path') ?? $image->image_path ?? '')))
                    ->filter()
                    ->unique(fn ($path) => $this->galleryAssetUniqueKey((string) $path))
                    ->values();
            }

            if (empty($uploadedImages) && $existingImages->isEmpty()) {
                throw ValidationException::withMessages([
                    "variants.$index.images" => 'At least one uploaded or existing image is required for each variant.',
                ]);
            }

            // ----------------------------------------------------------------
            // Diff-based variant image sync.
            //
            // Previous implementation wiped every variant_images row and
            // re-inserted them, which (a) lost the user's chosen primary on
            // every save, (b) churned IDs/sort_orders, and (c) destroyed all
            // images if `existing_images` failed to post.
            //
            // New behaviour:
            //   - Capture the previous primary image path.
            //   - Insert newly uploaded files (always appended, never primary
            //     unless explicitly chosen via primary_image_index).
            //   - updateOrCreate existing rows so IDs are preserved and only
            //     sort_order is updated.
            //   - Delete only rows whose path is no longer present in the
            //     submitted set.
            //   - Honour an explicit `primary_image_index` from the payload;
            //     fall back to the previous primary; finally to the first row.
            // ----------------------------------------------------------------

            $previousPrimaryPath = optional(
                $variant->variantImages()->where('is_primary', true)->first()
            )->getRawOriginal('image_path');

            $orderedKeptPaths = [];
            $imageOrder = 0;

            foreach ($uploadedImages as $uploadedImage) {
                $storedPath = $this->storeUploadedImageFile($uploadedImage, 'products/variants');
                $this->syncStorageFile($storedPath);
                $storedPublicPaths[] = $storedPath;
                $imageOrder++;

                VariantImage::create([
                    'product_variant_id' => $variant->id,
                    'image_path' => $storedPath,
                    'is_primary' => false, // primary is set in a single pass below
                    'sort_order' => $imageOrder,
                ]);

                $orderedKeptPaths[] = $storedPath;
                $variantGalleryForProduct[] = $storedPath;
            }

            foreach ($existingImages as $existingPath) {
                $imageOrder++;

                VariantImage::updateOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'image_path' => $existingPath,
                    ],
                    [
                        'sort_order' => $imageOrder,
                        // is_primary intentionally left as-is here; it is
                        // re-evaluated in the single pass below.
                    ]
                );

                $orderedKeptPaths[] = $existingPath;

                $normalizedExisting = ltrim((string) $existingPath, '/');
                if (Str::startsWith($normalizedExisting, 'storage/')) {
                    $normalizedExisting = Str::after($normalizedExisting, 'storage/');
                }
                $variantGalleryForProduct[] = $normalizedExisting;
            }

            // Remove only the images the user actually deleted from the form.
            if (! empty($orderedKeptPaths)) {
                $variant->variantImages()
                    ->whereNotIn('image_path', $orderedKeptPaths)
                    ->delete();
            }

            // Decide the primary image in a single, deterministic pass.
            $requestedPrimaryIndex = $variantPayload['primary_image_index'] ?? null;
            $primaryImagePath = null;

            if ($requestedPrimaryIndex !== null
                && is_numeric($requestedPrimaryIndex)
                && isset($orderedKeptPaths[(int) $requestedPrimaryIndex])
            ) {
                $primaryImagePath = $orderedKeptPaths[(int) $requestedPrimaryIndex];
            } elseif ($previousPrimaryPath !== null && in_array($previousPrimaryPath, $orderedKeptPaths, true)) {
                // Preserve the previously-flagged primary if it still exists.
                $primaryImagePath = $previousPrimaryPath;
            } else {
                $primaryImagePath = $orderedKeptPaths[0] ?? null;
            }

            if ($primaryImagePath === null) {
                throw ValidationException::withMessages([
                    "variants.$index.images" => 'At least one valid image is required for each variant.',
                ]);
            }

            // Apply primary in two queries (atomic per-variant): clear all,
            // then flag the chosen one. Avoids drifting flags across rows.
            $variant->variantImages()->update(['is_primary' => false]);
            $variant->variantImages()
                ->where('image_path', $primaryImagePath)
                ->update(['is_primary' => true]);

            $variant->image = $this->toLegacyImageDatabasePath((string) $primaryImagePath);
            $variant->save();

            if (empty($product->thumbnail) && $index === 0) {
                $product->update(['thumbnail' => $primaryImagePath]);
            }

            if ($index === 0 && ! Productimage::where('product_id', $product->id)->exists()) {
                Productimage::create([
                    'product_id' => $product->id,
                    'image' => $this->toLegacyImageDatabasePath((string) $primaryImagePath),
                    'alt_text' => $product->name,
                    'sort_order' => 1,
                ]);
            }
        }

        if (! empty($variantGalleryForProduct)) {
            $existingImageMap = Productimage::where('product_id', $product->id)
                ->get(['id', 'image', 'sort_order'])
                ->mapWithKeys(function ($row) {
                    $normalized = $this->galleryAssetUniqueKey((string) $row->getRawOriginal('image'));

                    return [$normalized => true];
                })
                ->all();

            $maxSortOrder = (int) Productimage::where('product_id', $product->id)->max('sort_order');

            foreach (array_values(array_unique($variantGalleryForProduct)) as $storedPath) {
                $candidatePath = $this->toLegacyImageDatabasePath((string) $storedPath);
                $candidateKey = $this->galleryAssetUniqueKey($candidatePath);
                if (isset($existingImageMap[$candidateKey])) {
                    continue;
                }

                $maxSortOrder++;
                Productimage::create([
                    'product_id' => $product->id,
                    'image' => $candidatePath,
                    'alt_text' => $product->name,
                    'sort_order' => $maxSortOrder,
                ]);
                $existingImageMap[$candidateKey] = true;
            }
        }

        $this->syncProductLevelVariantStock($product);
    }

    private function validUploadedImages($uploadedImages): array
    {
        if ($uploadedImages instanceof \Illuminate\Http\UploadedFile) {
            $uploadedImages = [$uploadedImages];
        }

        if (! is_array($uploadedImages)) {
            return [];
        }

        $validImages = [];
        foreach (\Illuminate\Support\Arr::flatten($uploadedImages) as $uploadedImage) {
            if (! $uploadedImage instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $realPath = (string) ($uploadedImage->getRealPath() ?: $uploadedImage->getPathname() ?: '');
            if ($realPath === '' || ! $uploadedImage->isValid()) {
                continue;
            }

            $validImages[] = $uploadedImage;
        }

        return $validImages;
    }

    private function firstValidUploadedImage($uploadedImage): ?\Illuminate\Http\UploadedFile
    {
        return $this->validUploadedImages($uploadedImage)[0] ?? null;
    }

    private function storeUploadedImageFile(\Illuminate\Http\UploadedFile $uploadedImage, string $directory): string
    {
        $directory = trim($directory, '/');
        $extension = strtolower((string) ($uploadedImage->getClientOriginalExtension() ?: $uploadedImage->extension() ?: 'jpg'));
        $filename = Str::random(40).'.'.$extension;
        $path = $directory.'/'.$filename;

        try {
            $storedPath = $uploadedImage->storeAs($directory, $filename, 'public');
            if (is_string($storedPath) && $storedPath !== '') {
                return $storedPath;
            }
        } catch (\ValueError $exception) {
            // Some local PHP uploads report an empty realpath while pathname is still readable.
        }

        $sourcePath = (string) ($uploadedImage->getPathname() ?: $uploadedImage->getRealPath() ?: '');
        if ($sourcePath === '' || ! is_readable($sourcePath)) {
            throw new \RuntimeException('Uploaded image temp file is not readable.');
        }

        Storage::disk('public')->makeDirectory($directory);
        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Unable to read uploaded image temp file.');
        }

        try {
            Storage::disk('public')->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $path;
    }

    private function generateAutoVariantSkuFromValueRows(Product $product, Collection $valueRows, int $serial, ?int $ignoreVariantId = null): string
    {
        $parts = $valueRows
            ->pluck('value')
            ->map(fn ($value) => Str::upper((string) Str::slug((string) $value, '-')))
            ->filter()
            ->take(3)
            ->values()
            ->all();

        $baseSku = implode('-', array_filter([
            $product->sku ?: $product->product_code ?: ('P'.$product->id),
            ...$parts,
            'V'.$serial,
        ]));

        return $this->ensureUniqueVariantSku($baseSku, 1, $ignoreVariantId);
    }

    private function ensureUniqueVariantSku(string $rawSku, int $attempt = 1, ?int $ignoreVariantId = null): string
    {
        $baseSku = Str::upper(Str::slug($rawSku, '-'));
        if ($baseSku === '') {
            $baseSku = 'VAR';
        }

        $candidate = $attempt === 1
            ? $baseSku
            : $baseSku.'-'.$attempt;

        $exists = ProductVariant::where('sku_code', $candidate)
            ->when($ignoreVariantId, fn ($query) => $query->where('id', '!=', $ignoreVariantId))
            ->exists();

        if ($exists) {
            return $this->ensureUniqueVariantSku($baseSku, $attempt + 1, $ignoreVariantId);
        }

        return $candidate;
    }

    private function generateAutoVariantSku(Product $product, string $color, ?string $size, ?string $age, int $serial, ?int $ignoreVariantId = null): string
    {
        $base = implode('-', array_filter([
            $product->sku ?: $product->product_code ?: ('P'.$product->id),
            $color,
            $size,
            $age,
            'V'.$serial,
        ]));

        return $this->ensureUniqueVariantSku($base, 1, $ignoreVariantId);
    }

    private function ensureUniqueVariantCombinationKey(int $productId, string $baseKey, int $attempt = 1): string
    {
        $normalized = Str::slug($baseKey);
        if ($normalized === '') {
            $normalized = 'variant';
        }

        $candidate = $attempt === 1
            ? $normalized
            : $normalized.'-'.$attempt;

        $exists = ProductVariant::where('product_id', $productId)
            ->where('combination_key', $candidate)
            ->exists();

        if ($exists) {
            return $this->ensureUniqueVariantCombinationKey($productId, $normalized, $attempt + 1);
        }

        return $candidate;
    }

    /**
     * Build a safe category prefix (ASCII only) for SKU generation.
     */
    private function buildSkuCategoryPrefix($categoryName)
    {
        $ascii = Str::ascii((string) $categoryName);
        $normalized = (string) preg_replace('/[^A-Za-z0-9]/', '', $ascii);
        $prefix = strtoupper(substr($normalized, 0, 2));

        if ($prefix === '') {
            return 'PR';
        }

        if (strlen($prefix) === 1) {
            return $prefix.'X';
        }

        return $prefix;
    }

    /**
     * Build a safe product-name segment (ASCII initials) for SKU generation.
     */
    private function buildSkuNamePart($productName)
    {
        $ascii = Str::ascii((string) $productName);
        $tokens = preg_split('/[^A-Za-z0-9]+/', $ascii, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $initials = '';
        foreach ($tokens as $token) {
            $initials .= strtoupper(substr($token, 0, 1));
            if (strlen($initials) >= 4) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'ITEM';
    }

    /**
     * Handle image uploads with compression and alt-text
     */
    private function handleImageUploads($images, $productId, $productName)
    {
        $images = $this->validUploadedImages($images);
        if (empty($images)) {
            return;
        }

        // Normalize existing sort order to keep previously uploaded images first.
        $existingImages = Productimage::where('product_id', $productId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'sort_order']);

        $nextSortOrder = 0;
        foreach ($existingImages as $existingImage) {
            $nextSortOrder++;
            if ((int) $existingImage->sort_order !== $nextSortOrder) {
                Productimage::whereKey($existingImage->id)->update(['sort_order' => $nextSortOrder]);
            }
        }

        foreach (array_values($images) as $key => $image) {
            $sourcePath = (string) ($image->getRealPath() ?: $image->getPathname() ?: '');
            if ($sourcePath === '') {
                continue;
            }

            $sortOrder = $nextSortOrder + $key + 1;

            // Generate deterministic filenames for optimized and fallback paths
            $extension = strtolower($image->getClientOriginalExtension() ?: 'jpg');
            $baseFilename = time().'-'.$key.'-'.Str::limit(Str::slug($productName), 50, '');
            $jpgFilename = $baseFilename.'.jpg';
            $webpFilename = $baseFilename.'.webp';
            $originalFilename = $baseFilename.'.'.$extension;

            $uploadPath = 'products/gallery';
            $webpPath = 'products/gallery/webp';
            Storage::disk('public')->makeDirectory($uploadPath);
            Storage::disk('public')->makeDirectory($webpPath);

            // Auto-compress based on file size
            $quality = 90;
            $filesize = (int) ($image->getSize() ?? 0);
            if ($filesize > 2000000) { // > 2MB
                $quality = 75;
            } elseif ($filesize > 1000000) { // > 1MB
                $quality = 80;
            }

            $dbImagePath = $uploadPath.'/'.$jpgFilename;
            $dbWebpPath = $webpPath.'/'.$webpFilename;

            // Try to process image with native GD - save as JPG and WebP
            $jpgSaved = ImageHelper::saveAsJpg(
                $sourcePath,
                storage_path('app/public/'.$dbImagePath),
                $quality
            );
            $webpSaved = ImageHelper::processAndSaveWebp(
                $sourcePath,
                storage_path('app/public/'.$dbWebpPath),
                $quality
            );

            // If GD processing failed, use fallback method to store the original file
            if (! $jpgSaved) {
                $dbImagePath = $this->storeUploadedImageFile($image, $uploadPath);
                $dbWebpPath = null;
            } elseif (! $webpSaved) {
                $dbWebpPath = null;
            }

            // Save to database with alt-text
            $imagePath = 'storage/'.ltrim((string) $dbImagePath, '/');
            $webpPath = $dbWebpPath ? 'storage/'.ltrim((string) $dbWebpPath, '/') : null;

            Productimage::create([
                'product_id' => $productId,
                'image' => $imagePath,
                'webp_image' => $webpPath,
                'alt_text' => $this->generateAltText($productName, $key),
                'sort_order' => $sortOrder,
            ]);

            // Sync files from storage/app/public to public/storage for web accessibility
            $this->syncStorageFile(ltrim((string) $dbImagePath, '/'));
            if ($dbWebpPath) {
                $this->syncStorageFile(ltrim((string) $dbWebpPath, '/'));
            }
        }
    }

    private function attachExistingGalleryImages(Product $product, $selectedPaths): bool
    {
        $paths = collect(is_array($selectedPaths) ? $selectedPaths : [])
            ->map(fn ($path) => $this->normalizeGalleryAssetPath((string) $path))
            ->filter()
            ->unique()
            ->values();

        if ($paths->isEmpty()) {
            return false;
        }

        $existing = Productimage::where('product_id', $product->id)
            ->pluck('image')
            ->map(fn ($path) => $this->galleryAssetUniqueKey((string) $path))
            ->filter()
            ->all();

        $existingMap = array_fill_keys($existing, true);
        $sortOrder = (int) Productimage::where('product_id', $product->id)->max('sort_order');
        $createdAny = false;

        foreach ($paths as $path) {
            $pathKey = $this->galleryAssetUniqueKey((string) $path);
            if (isset($existingMap[$pathKey])) {
                continue;
            }

            $sortOrder++;
            Productimage::create([
                'product_id' => $product->id,
                'image' => $path,
                'alt_text' => $product->name,
                'sort_order' => $sortOrder,
            ]);
            $existingMap[$pathKey] = true;
            $createdAny = true;
        }

        return $createdAny;
    }

    private function normalizeGalleryAssetPath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, ['storage/', 'public/'])) {
            return $normalized;
        }

        if (Str::startsWith($normalized, 'uploads/')) {
            return 'public/'.$normalized;
        }

        return 'storage/'.$normalized;
    }

    private function galleryAssetUniqueKey(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');

        if (Str::startsWith($normalized, 'public/storage/')) {
            $normalized = Str::after($normalized, 'public/storage/');
        } elseif (Str::startsWith($normalized, ['public/', 'storage/'])) {
            $normalized = preg_replace('#^(public|storage)/#i', '', $normalized) ?: $normalized;
        }

        return Str::lower($normalized);
    }

    private function toLegacyImageDatabasePath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, ['http://', 'https://', 'data:', 'public/', 'storage/'])) {
            return $normalized;
        }

        if (Str::startsWith($normalized, 'uploads/')) {
            return 'public/'.$normalized;
        }

        return 'storage/'.$normalized;
    }

    private function shippingProfilesForForm(): Collection
    {
        if (! Schema::hasTable('shipping_profiles')) {
            return collect();
        }

        return ShippingProfile::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'is_default']);
    }

    /**
     * @return array<string, string>
     */
    private function shippingValidationRules(): array
    {
        return [
            'shipping_type' => 'nullable|in:weight_based,fixed_rate,free_shipping,digital',
            'shipping_profile_id' => 'nullable|required_if:shipping_type,weight_based|integer|exists:shipping_profiles,id',
            'fixed_shipping_cost' => 'nullable|required_if:shipping_type,fixed_rate|numeric|min:0',
            'weight' => 'nullable|required_if:shipping_type,weight_based|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingInput(Request $request): array
    {
        $type = $request->input('shipping_type');
        $type = in_array($type, ['weight_based', 'fixed_rate', 'free_shipping', 'digital'], true) ? $type : null;

        $payload = [
            'shipping_type' => $type,
            'shipping_profile_id' => null,
            'fixed_shipping_cost' => null,
            'weight' => null,
            'length' => null,
            'width' => null,
            'height' => null,
            'is_physical' => $type !== 'digital',
        ];

        if ($type === 'weight_based') {
            $payload['shipping_profile_id'] = $request->filled('shipping_profile_id') ? (int) $request->shipping_profile_id : null;
            $payload['weight'] = $request->filled('weight') ? (float) $request->weight : null;
            $payload['length'] = $request->filled('length') ? (float) $request->length : null;
            $payload['width'] = $request->filled('width') ? (float) $request->width : null;
            $payload['height'] = $request->filled('height') ? (float) $request->height : null;
        }

        if ($type === 'fixed_rate') {
            $payload['fixed_shipping_cost'] = $request->filled('fixed_shipping_cost')
                ? (int) round((float) $request->fixed_shipping_cost)
                : null;
        }

        return $payload;
    }

    private function deleteStoredAsset(string $path): void
    {
        $normalizedPath = trim(str_replace('\\', '/', $path));
        if ($normalizedPath === '' || preg_match('/^https?:\/\//i', $normalizedPath)) {
            return;
        }

        $normalizedPath = ltrim($normalizedPath, '/');
        $storageDiskPath = $normalizedPath;
        $publicFiles = [];

        if (Str::startsWith($normalizedPath, 'public/storage/')) {
            $storageDiskPath = Str::after($normalizedPath, 'public/storage/');
            $publicFiles[] = base_path($normalizedPath);
        } elseif (Str::startsWith($normalizedPath, 'storage/')) {
            $storageDiskPath = Str::after($normalizedPath, 'storage/');
            $publicFiles[] = base_path('public/storage/'.$storageDiskPath);
        } elseif (Str::startsWith($normalizedPath, 'public/')) {
            $storageDiskPath = null;
            $publicFiles[] = base_path($normalizedPath);
        } else {
            $publicFiles[] = base_path('public/storage/'.$storageDiskPath);
        }

        if (! empty($storageDiskPath)) {
            Storage::disk('public')->delete($storageDiskPath);
        }

        foreach (array_unique($publicFiles) as $publicPath) {
            if (is_file($publicPath)) {
                @unlink($publicPath);
            }
        }
    }

    /**
     * Generate SEO-friendly alt text for images
     */
    private function generateAltText($productName, $index)
    {
        $suffixes = ['', ' - front view', ' - side view', ' - back view', ' - detail view'];

        return trim($productName.($suffixes[$index] ?? ' - view '.($index + 1)));
    }

    /**
     * Create initial stock for a newly created product
     */
    private function createInitialStock(Product $product, float $quantity)
    {
        try {
            $warehouse = $this->resolveDefaultWarehouse();

            // Calculate unit cost and total value
            $unitCost = (float) ($product->purchase_price ?? 0);
            $totalValue = $quantity * $unitCost;

            // Create or update warehouse stock record
            $warehouseStock = WarehouseStock::updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                ],
                [
                    'sku' => $product->sku ?? $product->product_code ?? ('SKU-'.$product->id),
                    'physical_quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'reorder_point' => 5,
                    'reorder_quantity' => 0,
                    'average_cost' => $unitCost,
                    'total_value' => $totalValue,
                ]
            );

            // Create stock movement record
            StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'type' => 'initial_stock',
                'reference_type' => 'product_creation',
                'reference_id' => $product->id,
                'quantity' => (float) $quantity,
                'unit_cost' => $unitCost,
                'balance_after' => (float) $quantity,
                'notes' => "Initial stock added during product creation: {$quantity} units",
                'created_by' => auth()->id() ?? 1,
            ]);

            // Clear stock cache
            \Cache::forget("stock_summary_{$warehouse->id}_{$product->id}");
            \Cache::forget("stock_balance_{$warehouse->id}_{$product->id}");
            \Cache::forget("product_stock_{$product->id}");

            \Log::info("Initial stock created for product {$product->id}", [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
                'warehouse_stock_id' => $warehouseStock->id,
                'unit_cost' => $unitCost,
                'total_value' => $totalValue,
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to create initial stock for product {$product->id}", [
                'error' => $e->getMessage(),
                'quantity' => $quantity,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function syncProductLevelVariantStock(Product $product): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (! $schema->hasTable('inventories') || ! $schema->hasTable('warehouse_stock')) {
            return;
        }

        $variantIds = ProductVariant::query()
            ->where('product_id', $product->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if (empty($variantIds)) {
            return;
        }

        $aggregates = DB::table('inventories')
            ->whereIn('product_variant_id', $variantIds)
            ->groupBy('warehouse_id')
            ->select('warehouse_id')
            ->selectRaw('COALESCE(SUM(quantity_available), 0) AS physical_quantity')
            ->selectRaw('COALESCE(SUM(quantity_reserved), 0) AS reserved_quantity')
            ->when(
                $schema->hasColumn('inventories', 'total_value'),
                fn ($query) => $query->selectRaw('COALESCE(SUM(total_value), 0) AS total_value'),
                fn ($query) => $query->selectRaw('0 AS total_value')
            )
            ->get();

        foreach ($aggregates as $aggregate) {
            $warehouseId = $aggregate->warehouse_id !== null ? (int) $aggregate->warehouse_id : null;
            $physicalQuantity = (float) ($aggregate->physical_quantity ?? 0);
            $reservedQuantity = (float) ($aggregate->reserved_quantity ?? 0);
            $availableQuantity = max(0, $physicalQuantity - $reservedQuantity);
            $totalValue = (float) ($aggregate->total_value ?? 0);
            $averageCost = $physicalQuantity > 0 && $totalValue > 0
                ? round($totalValue / $physicalQuantity, 4)
                : (float) ($product->purchase_price ?? 0);

            $keys = [
                'warehouse_id' => $warehouseId,
                'product_id' => $product->id,
                'product_variant_id' => null,
            ];

            $values = [
                'physical_quantity' => $physicalQuantity,
                'reserved_quantity' => $reservedQuantity,
                'available_quantity' => $availableQuantity,
            ];

            if ($schema->hasColumn('warehouse_stock', 'branch_id')) {
                $values['branch_id'] = $warehouseId
                    ? (int) (Warehouse::query()->whereKey($warehouseId)->value('branch_id') ?? 0)
                    : null;
            }
            if ($schema->hasColumn('warehouse_stock', 'sku')) {
                $values['sku'] = $product->sku ?? $product->product_code ?? ('SKU-'.$product->id);
            }
            if ($schema->hasColumn('warehouse_stock', 'reorder_point')) {
                $values['reorder_point'] = 0;
            }
            if ($schema->hasColumn('warehouse_stock', 'reorder_quantity')) {
                $values['reorder_quantity'] = 0;
            }
            if ($schema->hasColumn('warehouse_stock', 'average_cost')) {
                $values['average_cost'] = $averageCost;
            }
            if ($schema->hasColumn('warehouse_stock', 'total_value')) {
                $values['total_value'] = $totalValue > 0 ? $totalValue : ($physicalQuantity * $averageCost);
            }

            if (
                $this->tableIdRequiresManualValue('warehouse_stock')
                && ! DB::table('warehouse_stock')->where($keys)->exists()
            ) {
                $values['id'] = $this->nextTableId('warehouse_stock');
            }

            DB::table('warehouse_stock')->updateOrInsert($keys, $values);
        }

        \Cache::forget("product_stock_{$product->id}");
    }

    private function tableIdRequiresManualValue(string $table): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        if (! DB::connection()->getSchemaBuilder()->hasColumn($table, 'id')) {
            return false;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE 'id'");

        return $column && ! str_contains(strtolower((string) $column->Extra), 'auto_increment');
    }

    private function nextTableId(string $table): int
    {
        return ((int) DB::table($table)->max('id')) + 1;
    }

    private function resolveDefaultWarehouse(): ?Warehouse
    {
        if (! DB::connection()->getSchemaBuilder()->hasTable('warehouses')) {
            return null;
        }

        $warehouse = Warehouse::active()->main()->first() ?? Warehouse::active()->first();
        if ($warehouse) {
            return $warehouse;
        }

        return Warehouse::create([
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
            'type' => 'main',
            'address' => 'Default Address',
            'city' => 'Default City',
            'country' => 'Default Country',
            'is_active' => true,
            'opening_date' => now(),
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Log product changes for audit trail
     */
    private function logProductChange($productId, $action, $description, $changes = [])
    {
        // Create product_change_logs table migration would be needed
        // For now, we'll use Laravel's logging
        \Log::info("Product Change: {$action}", [
            'product_id' => $productId,
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Sync product stock with warehouse system
     * Called when products are created or updated
     */
    private function syncWarehouseStock(int $productId, float $newStock, ?float $unitCost = null, float $oldStock = 0): void
    {
        try {
            // Get main warehouse
            $warehouse = Warehouse::active()->main()->first() ?? Warehouse::active()->first();

            if (! $warehouse) {
                \Log::warning('No active warehouse found for stock sync', ['product_id' => $productId]);

                return;
            }

            $product = Product::find($productId);
            if (! $product) {
                \Log::error('Product not found for stock sync', ['product_id' => $productId]);

                return;
            }

            // Use purchase price as unit cost if not provided
            $unitCost = $unitCost ?? $product->purchase_price ?? 0;

            // Calculate stock difference
            $stockDifference = $newStock - $oldStock;

            if ($stockDifference == 0) {
                return; // No change needed
            }

            // Get current warehouse stock
            $warehouseStock = WarehouseStock::where('warehouse_id', $warehouse->id)
                ->where('product_id', $productId)
                ->first();

            if (! $warehouseStock) {
                // Create new warehouse stock record
                if ($newStock > 0) {
                    $warehouseStock = WarehouseStock::create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $productId,
                        'sku' => $product->sku ?? $product->product_code ?? ('SKU-'.$productId),
                        'physical_quantity' => $newStock,
                        'available_quantity' => $newStock,
                        'reserved_quantity' => 0,
                        'reorder_point' => 5,
                        'reorder_quantity' => 0,
                        'average_cost' => $unitCost,
                        'total_value' => $newStock * $unitCost,
                    ]);

                    // Record initial stock movement
                    StockMovement::create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $productId,
                        'type' => 'initial_stock',
                        'reference_type' => 'product_sync',
                        'reference_id' => $productId,
                        'quantity' => $newStock,
                        'unit_cost' => $unitCost,
                        'balance_after' => $newStock,
                        'notes' => "Stock synced during product creation/update: {$newStock} units",
                        'created_by' => auth()->id(),
                    ]);
                }
            } else {
                // Update existing stock
                $oldPhysicalQuantity = $warehouseStock->physical_quantity;
                $warehouseStock->physical_quantity = $newStock;
                $warehouseStock->available_quantity = max(0, $newStock - $warehouseStock->reserved_quantity);
                $warehouseStock->average_cost = $unitCost;
                $warehouseStock->total_value = $newStock * $unitCost;
                $warehouseStock->save();

                // Record adjustment movement
                StockMovement::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $productId,
                    'type' => $stockDifference > 0 ? 'adjustment_in' : 'adjustment_out',
                    'reference_type' => 'product_sync',
                    'reference_id' => $productId,
                    'quantity' => $stockDifference,
                    'unit_cost' => $unitCost,
                    'balance_after' => $newStock,
                    'notes' => "Stock synced during product update: {$oldPhysicalQuantity} → {$newStock} units",
                    'created_by' => auth()->id(),
                ]);
            }

            // Clear stock cache
            \Cache::forget("stock_summary_{$warehouse->id}_{$productId}");
            \Cache::forget("stock_balance_{$warehouse->id}_{$productId}");

            \Log::info('Warehouse stock synced for product', [
                'product_id' => $productId,
                'warehouse_id' => $warehouse->id,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'difference' => $stockDifference,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to sync warehouse stock', [
                'product_id' => $productId,
                'new_stock' => $newStock,
                'old_stock' => $oldStock,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync a file from storage/app/public to public/storage for web accessibility
     * This ensures files uploaded to the public disk are accessible via web
     */
    private function syncStorageFile(string $storagePath): void
    {
        try {
            // Convert storage/app/public path to public/storage path
            $sourceFile = storage_path('app/public/'.$storagePath);
            $publicFile = base_path('public/storage/'.$storagePath);

            if (file_exists($sourceFile) && ! file_exists($publicFile)) {
                // Ensure directory exists
                $publicDir = dirname($publicFile);
                if (! is_dir($publicDir)) {
                    @mkdir($publicDir, 0755, true);
                }
                // Copy file
                @copy($sourceFile, $publicFile);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to sync storage file', [
                'path' => $storagePath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
