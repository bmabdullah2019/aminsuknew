<?php

namespace App\Http\Controllers\Frontend;

use App\Domain\Checkout\CheckoutTotalsService;
use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Models\Age;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Color;
use App\Models\Contact;
use App\Models\CreatePage;
use App\Models\District;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\ShippingCharge;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\Warehouse;
use App\Services\PhoneBlockService;
use App\Services\StockEngine;
use App\Services\VariantAttributeService;
use App\Support\StorefrontCache;
use Auth;
use Brian2694\Toastr\Facades\Toastr;
use Cart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Session;
use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use App\Support\Money;
use Throwable;

class FrontendController extends Controller
{
    public function index()
    {
        $cacheKey = StorefrontCache::versionedKey('storefront:home:index:v3');

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $generalsetting = GeneralSetting::where('status', 1)->limit(1)->first();
        // return "Welcome to Kenakatar.com";
            $frontcategory = Category::where(['status' => 1])
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->select('id', 'name', 'image', 'slug', 'status', 'sort_order')
                ->get();

            $sliders = Banner::where(['status' => 1, 'category_id' => 1])
                ->select('id', 'image', 'link')
                ->get();

            $campaognads = Banner::where(['status' => 1, 'category_id' => 7])
                ->select('id', 'image', 'link')
                ->limit(1)
                ->get();

            $sliderbottomads = Banner::where(['status' => 1, 'category_id' => 5])
                ->select('id', 'image', 'link')
                ->limit(3)
                ->get();

            $footertopads = Banner::where(['status' => 1, 'category_id' => 6])
                ->select('id', 'image', 'link')
                ->limit(3)
                ->get();

            $homepageads = Banner::where(['status' => 1, 'category_id' => 10])
                ->select('id', 'image', 'link')
                ->limit(1)
                ->get();

            $homepageads2 = Banner::where(['status' => 1, 'category_id' => 11])
                ->select('id', 'image', 'link')
                ->limit(1)
                ->get();

            $hitdealsbaner = Banner::where(['status' => 1, 'category_id' => 9])
                ->select('id', 'image', 'link')
                ->limit(1)
                ->get();

            $flas_sales = Product::where(['status' => 1, 'flashsale' => 1])
                ->orderBy('id', 'DESC')
                ->select('id', 'name', 'slug', 'new_price', 'old_price', 'sold', 'has_variant')
                ->with('prosizes', 'procolors')
                ->limit(12)
                ->get();
        // return $hotdeal_top;
            $hotdeal_top = Product::where(['status' => 1, 'topsale' => 1])
                ->orderBy('id', 'DESC')
                ->select('id', 'name', 'slug', 'new_price', 'old_price', 'has_variant')
                ->with('prosizes', 'procolors')
                ->limit(12)
                ->get();
        // return $hotdeal_top;
            $hotdeal_bottom = Product::where(['status' => 1, 'topsale' => 1])
                ->select('id', 'name', 'slug', 'new_price', 'old_price', 'has_variant')
                ->skip(12)
                ->limit(12)
                ->get();

            if ($generalsetting && $generalsetting->show_category_wise_products) {
                $homeCategoryQuery = Category::where(['front_view' => 1, 'status' => 1]);
                if (Schema::hasColumn('categories', 'front_view_order')) {
                    $homeCategoryQuery->orderBy('front_view_order', 'ASC');
                }

                $homeproducts = $homeCategoryQuery
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->with(['products', 'products.image', 'products.prosizes', 'products.procolors'])
                    ->get()
                    ->map(function ($query) {
                        $query->setRelation('products', $query->products->take(8));

                        return $query;
                    });
            } else {
                $homeproducts = null;
            }

            $reviews = Banner::where(['status' => 1, 'category_id' => 8])
                ->select('id', 'image', 'link')
                ->limit(3)
                ->get();

            $footerBlogPage = CreatePage::where('status', 1)
                ->where(function ($query) {
                    $query->whereIn('slug', ['blog', 'blogs'])
                        ->orWhere('name', 'like', '%blog%');
                })
                ->orderByRaw("CASE WHEN slug = 'blog' THEN 0 WHEN slug = 'blogs' THEN 1 ELSE 2 END")
                ->orderBy('id', 'ASC')
                ->select('id', 'name', 'slug', 'description')
                ->first();

            if ($generalsetting && $generalsetting->show_all_products) {
                $all_products = Product::where(['status' => 1])
                    ->inRandomOrder()
                    ->select('id', 'name', 'slug', 'new_price', 'old_price', 'sold', 'has_variant')
                    ->with('prosizes', 'procolors')
                    ->limit(30)
                    ->get();
            } else {
                $all_products = null;
            }

            return compact(
                'sliders',
                'frontcategory',
                'hotdeal_top',
                'hotdeal_bottom',
                'homeproducts',
                'sliderbottomads',
                'footertopads',
                'homepageads2',
                'hitdealsbaner',
                'homepageads',
                'flas_sales',
                'campaognads',
                'reviews',
                'all_products',
                'generalsetting',
                'footerBlogPage'
            );
        });

        return view('frontEnd.layouts.pages.index', $payload);
    }

    public function hotdeals(Request $request)
    {

        $products = Product::where(['status' => 1, 'topsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'has_variant')
            ->with([
                'image',
                'prosizes',
                'procolors',
                'reviews' => function ($query) {
                    $query->select('id', 'product_id', 'ratting');
                },
            ])
            ->withCount('reviews');
        // return $request->sort;
        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if ($request->min_price && $request->max_price) {
            $products = $products->where('new_price', '>=', $request->min_price);
            $products = $products->where('new_price', '<=', $request->max_price);
        }
        $products = $products->paginate(36);

        return view('frontEnd.layouts.pages.hotdeals', [
            'products' => $products,
            'pageTitle' => 'Hot Deals',
            'showTimer' => true,
        ]);
    }

    public function shop(Request $request)
    {
        $products = Product::where(['status' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'has_variant')
            ->with([
                'image',
                'prosizes',
                'procolors',
                'reviews' => function ($query) {
                    $query->select('id', 'product_id', 'ratting');
                },
            ])
            ->withCount('reviews');
        // return $request->sort;
        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if ($request->min_price && $request->max_price) {
            $products = $products->where('new_price', '>=', $request->min_price);
            $products = $products->where('new_price', '<=', $request->max_price);
        }
        $products = $products->paginate(36);

        return view('frontEnd.layouts.pages.hotdeals', [
            'products' => $products,
            'pageTitle' => 'Shop',
            'showTimer' => false,
        ]);
    }

    public function flashsales(Request $request)
    {

        $products = Product::where(['status' => 1, 'flashsale' => 1])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'has_variant')
            ->with([
                'image',
                'prosizes',
                'procolors',
                'reviews' => function ($query) {
                    $query->select('id', 'product_id', 'ratting');
                },
            ])
            ->withCount('reviews');
        // return $request->sort;
        if ($request->sort == 1) {
            $products = $products->orderBy('created_at', 'desc');
        } elseif ($request->sort == 2) {
            $products = $products->orderBy('created_at', 'asc');
        } elseif ($request->sort == 3) {
            $products = $products->orderBy('new_price', 'desc');
        } elseif ($request->sort == 4) {
            $products = $products->orderBy('new_price', 'asc');
        } elseif ($request->sort == 5) {
            $products = $products->orderBy('name', 'asc');
        } elseif ($request->sort == 6) {
            $products = $products->orderBy('name', 'desc');
        } else {
            $products = $products->latest();
        }

        $min_price = $products->min('new_price');
        $max_price = $products->max('new_price');
        if ($request->min_price && $request->max_price) {
            $products = $products->where('new_price', '>=', $request->min_price);
            $products = $products->where('new_price', '<=', $request->max_price);
        }
        $products = $products->paginate(36);

        return view('frontEnd.layouts.pages.hotdeals', [
            'products' => $products,
            'pageTitle' => 'Flash Sales',
            'showTimer' => true,
        ]);
    }

    public function category($slug, Request $request)
    {
        $soldShow = $request->sold == 'show' ? true : false;
        $category = Category::where(['slug' => $slug, 'status' => 1])->with('subcategories')->firstOrFail();

        $products = Product::where(['status' => 1, 'category_id' => $category->id])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'sold', 'has_variant')
            ->with([
                'image',
                'prosizes',
                'procolors',
                'reviews' => function ($query) {
                    $query->select('id', 'product_id', 'ratting');
                },
            ]);
        $subcategories = Cache::remember(
            "storefront:category:{$category->id}:subcategories:v1",
            now()->addMinutes(30),
            function () use ($category) {
                return Subcategory::where('category_id', $category->id)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get();
            }
        );

        $selectedSubcategories = $this->normalizeIntegerArray($request->input('subcategory', []));
        $products = $products->when($selectedSubcategories, function ($query) use ($selectedSubcategories) {
            return $query->whereHas('subcategory', function ($subQuery) use ($selectedSubcategories) {
                $subQuery->whereIn('id', $selectedSubcategories);
            });
        });

        $attributeOptions = $this->resolveAttributeOptions((clone $products));
        $selectedAttributes = $this->extractSelectedAttributes($request);
        $products = $this->applyAttributeFilters($products, $selectedAttributes);

        [$min_price, $max_price] = $this->resolvePriceRange((clone $products));
        $products = $this->applyPriceFilter($products, $request, $min_price, $max_price);
        $products = $this->applySorting($products, (int) $request->input('sort'));
        $products = $products->paginate(24)->withQueryString();

        $selectedSizeIds = $selectedAttributes['sizes'];
        $selectedColorIds = $selectedAttributes['colors'];
        $selectedAgeIds = $selectedAttributes['ages'];
        $selectedBrandIds = $selectedAttributes['brands'];
        $sizeFilters = $attributeOptions['sizes'];
        $colorFilters = $attributeOptions['colors'];
        $ageFilters = $attributeOptions['ages'];
        $brandFilters = $attributeOptions['brands'];

        return view('frontEnd.layouts.pages.category', compact(
            'category',
            'products',
            'subcategories',
            'min_price',
            'max_price',
            'soldShow',
            'selectedSubcategories',
            'selectedSizeIds',
            'selectedColorIds',
            'selectedAgeIds',
            'selectedBrandIds',
            'sizeFilters',
            'colorFilters',
            'ageFilters',
            'brandFilters'
        ));
    }

    public function subcategory($slug, Request $request)
    {
        $soldShow = $request->sold == 'show' ? true : false;
        $subcategory = Subcategory::where(['slug' => $slug, 'status' => 1])->with('childcategories')->firstOrFail();
        $products = Product::where(['status' => 1, 'subcategory_id' => $subcategory->id])
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'subcategory_id', 'sold', 'has_variant')
            ->with([
                'image',
                'prosizes',
                'procolors',
                'reviews' => function ($query) {
                    $query->select('id', 'product_id', 'ratting');
                },
            ]);
        $childcategories = Cache::remember(
            "storefront:subcategory:{$subcategory->id}:childcategories:v1",
            now()->addMinutes(30),
            function () use ($subcategory) {
                return Childcategory::where('subcategory_id', $subcategory->id)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get();
            }
        );

        $selectedChildcategories = $this->normalizeIntegerArray($request->input('childcategory', []));
        $products = $products->when($selectedChildcategories, function ($query) use ($selectedChildcategories) {
            return $query->whereHas('childcategory', function ($subQuery) use ($selectedChildcategories) {
                $subQuery->whereIn('id', $selectedChildcategories);
            });
        });

        $attributeOptions = $this->resolveAttributeOptions((clone $products));
        $selectedAttributes = $this->extractSelectedAttributes($request);
        $products = $this->applyAttributeFilters($products, $selectedAttributes);

        [$min_price, $max_price] = $this->resolvePriceRange((clone $products));
        $products = $this->applyPriceFilter($products, $request, $min_price, $max_price);
        $products = $this->applySorting($products, (int) $request->input('sort'));
        $products = $products->paginate(24)->withQueryString();

        $impproducts = Cache::remember('storefront:impproducts:v1', now()->addMinutes(5), function () {
            return Product::where(['status' => 1, 'topsale' => 1])
                ->with('image')
                ->limit(6)
                ->select('id', 'name', 'slug')
                ->get();
        });

        $selectedSizeIds = $selectedAttributes['sizes'];
        $selectedColorIds = $selectedAttributes['colors'];
        $selectedAgeIds = $selectedAttributes['ages'];
        $selectedBrandIds = $selectedAttributes['brands'];
        $sizeFilters = $attributeOptions['sizes'];
        $colorFilters = $attributeOptions['colors'];
        $ageFilters = $attributeOptions['ages'];
        $brandFilters = $attributeOptions['brands'];

        return view('frontEnd.layouts.pages.subcategory', compact(
            'subcategory',
            'products',
            'impproducts',
            'childcategories',
            'max_price',
            'min_price',
            'soldShow',
            'selectedChildcategories',
            'selectedSizeIds',
            'selectedColorIds',
            'selectedAgeIds',
            'selectedBrandIds',
            'sizeFilters',
            'colorFilters',
            'ageFilters',
            'brandFilters'
        ));
    }

    public function products($slug, Request $request)
    {
        $soldShow = $request->sold == 'show' ? true : false;
        $childcategory = Childcategory::where(['slug' => $slug, 'status' => 1])->firstOrFail();
        $childcategories = Cache::remember(
            "storefront:childcategory:{$childcategory->subcategory_id}:siblings:v1",
            now()->addMinutes(30),
            function () use ($childcategory) {
                return Childcategory::where('subcategory_id', $childcategory->subcategory_id)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get();
            }
        );
        $products = Product::where(['status' => 1, 'childcategory_id' => $childcategory->id])->with('category')
            // products.stock column was dropped; keep selecting only real columns.
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'category_id', 'subcategory_id', 'childcategory_id', 'sold', 'has_variant')
            ->with([
                'image',
                'prosizes',
                'procolors',
                'reviews' => function ($query) {
                    $query->select('id', 'product_id', 'ratting');
                },
            ]);

        $attributeOptions = $this->resolveAttributeOptions((clone $products));
        $selectedAttributes = $this->extractSelectedAttributes($request);
        $products = $this->applyAttributeFilters($products, $selectedAttributes);

        [$min_price, $max_price] = $this->resolvePriceRange((clone $products));
        $products = $this->applyPriceFilter($products, $request, $min_price, $max_price);
        $products = $this->applySorting($products, (int) $request->input('sort'));
        $products = $products->paginate(24)->withQueryString();

        $impproducts = Cache::remember('storefront:impproducts:v1', now()->addMinutes(5), function () {
            return Product::where(['status' => 1, 'topsale' => 1])
                ->with('image')
                ->limit(6)
                ->select('id', 'name', 'slug')
                ->get();
        });

        $selectedSizeIds = $selectedAttributes['sizes'];
        $selectedColorIds = $selectedAttributes['colors'];
        $selectedAgeIds = $selectedAttributes['ages'];
        $selectedBrandIds = $selectedAttributes['brands'];
        $sizeFilters = $attributeOptions['sizes'];
        $colorFilters = $attributeOptions['colors'];
        $ageFilters = $attributeOptions['ages'];
        $brandFilters = $attributeOptions['brands'];

        return view('frontEnd.layouts.pages.childcategory', compact(
            'childcategory',
            'products',
            'impproducts',
            'min_price',
            'max_price',
            'childcategories',
            'soldShow',
            'selectedSizeIds',
            'selectedColorIds',
            'selectedAgeIds',
            'selectedBrandIds',
            'sizeFilters',
            'colorFilters',
            'ageFilters',
            'brandFilters'
        ));
    }

    private function normalizeIntegerArray($values): array
    {
        return collect((array) $values)
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function extractSelectedAttributes(Request $request): array
    {
        return [
            'sizes' => $this->normalizeIntegerArray($request->input('size', [])),
            'colors' => $this->normalizeIntegerArray($request->input('color', [])),
            'ages' => $this->normalizeIntegerArray($request->input('age', [])),
            'brands' => $this->normalizeIntegerArray($request->input('brand', [])),
        ];
    }

    private function applyAttributeFilters($query, array $selectedAttributes)
    {
        if (! empty($selectedAttributes['sizes'])) {
            $query->whereHas('prosizes', function ($sizeQuery) use ($selectedAttributes) {
                $sizeQuery->whereIn('size_id', $selectedAttributes['sizes']);
            });
        }

        if (! empty($selectedAttributes['colors'])) {
            $query->whereHas('procolors', function ($colorQuery) use ($selectedAttributes) {
                $colorQuery->whereIn('color_id', $selectedAttributes['colors']);
            });
        }

        if (! empty($selectedAttributes['ages'])) {
            $query->whereHas('ages', function ($ageQuery) use ($selectedAttributes) {
                $ageQuery->whereIn('ages.id', $selectedAttributes['ages']);
            });
        }

        if (! empty($selectedAttributes['brands'])) {
            $query->whereIn('brand_id', $selectedAttributes['brands']);
        }

        return $query;
    }

    private function applySorting($query, int $sort)
    {
        if ($sort === 1) {
            return $query->orderBy('created_at', 'desc');
        }
        if ($sort === 2) {
            return $query->orderBy('created_at', 'asc');
        }
        if ($sort === 3) {
            return $query->orderBy('new_price', 'desc');
        }
        if ($sort === 4) {
            return $query->orderBy('new_price', 'asc');
        }
        if ($sort === 5) {
            return $query->orderBy('name', 'asc');
        }
        if ($sort === 6) {
            return $query->orderBy('name', 'desc');
        }

        return $query->latest();
    }

    private function resolvePriceRange($query): array
    {
        $minPrice = (float) ($query->min('new_price') ?? 0);
        $maxPrice = (float) ($query->max('new_price') ?? 0);

        if ($maxPrice < $minPrice) {
            $maxPrice = $minPrice;
        }

        return [$minPrice, $maxPrice];
    }

    private function applyPriceFilter($query, Request $request, float $minPrice, float $maxPrice)
    {
        if (! $request->filled('min_price') || ! $request->filled('max_price')) {
            return $query;
        }

        $requestedMin = max($minPrice, (float) $request->input('min_price'));
        $requestedMax = min($maxPrice, (float) $request->input('max_price'));

        if ($requestedMax < $requestedMin) {
            $requestedMax = $requestedMin;
        }

        return $query
            ->where('new_price', '>=', $requestedMin)
            ->where('new_price', '<=', $requestedMax);
    }

    private function resolveAttributeOptions($query): array
    {
        $productIds = (clone $query)->pluck('products.id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return [
                'sizes' => collect(),
                'colors' => collect(),
                'ages' => collect(),
                'brands' => collect(),
            ];
        }

        $sizes = Size::query()
            ->select('sizes.id', 'sizes.sizeName')
            ->join('productsizes', 'productsizes.size_id', '=', 'sizes.id')
            ->whereIn('productsizes.product_id', $productIds)
            ->whereNotNull('sizes.sizeName')
            ->distinct()
            ->orderBy('sizes.sizeName', 'ASC')
            ->get();

        $colors = Color::query()
            ->select('colors.id', 'colors.colorName')
            ->join('productcolors', 'productcolors.color_id', '=', 'colors.id')
            ->whereIn('productcolors.product_id', $productIds)
            ->whereNotNull('colors.colorName')
            ->distinct()
            ->orderBy('colors.colorName', 'ASC')
            ->get();

        $ages = Age::query()
            ->select('ages.id', 'ages.ageName')
            ->join('productages', 'productages.age_id', '=', 'ages.id')
            ->whereIn('productages.product_id', $productIds)
            ->whereNotNull('ages.ageName')
            ->distinct()
            ->orderBy('ages.ageName', 'ASC')
            ->get();

        $brands = Brand::query()
            ->select('brands.id', 'brands.name')
            ->whereIn('brands.id', function ($sub) use ($productIds) {
                $sub->select('brand_id')
                    ->from('products')
                    ->whereIn('id', $productIds)
                    ->whereNotNull('brand_id')
                    ->where('brand_id', '>', 0)
                    ->distinct();
            })
            ->where('brands.status', 1)
            ->orderBy('brands.name', 'ASC')
            ->get();

        return [
            'sizes' => $sizes,
            'colors' => $colors,
            'ages' => $ages,
            'brands' => $brands,
        ];
    }

    public function details($slug)
    {
        $details = Product::where(['slug' => $slug, 'status' => 1])
            ->with([
                'image',
                'images',
                'category',
                'subcategory',
                'childcategory',
                'brand',
                'warehouseStocks',
                'productVariants.variantImages',
                'productVariants.primaryVariantImage',
            ])
            ->firstOrFail();
        $products = Product::where(['category_id' => $details->category_id, 'status' => 1])
            ->with('image')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'has_variant')
            ->get();
        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $reviews = Review::where('product_id', $details->id)->get();

        // Same warehouse scope as cart/checkout (avoid PDP showing summed stock across warehouses).
        $checkoutWarehouseId = Session::get('warehouse_id');
        if (! $checkoutWarehouseId) {
            $checkoutWarehouseId = optional(Warehouse::main()->active()->first() ?? Warehouse::active()->first())->id;
            if ($checkoutWarehouseId) {
                Session::put('warehouse_id', (int) $checkoutWarehouseId);
            }
        }

        $variantPayload = app(VariantAttributeService::class)->buildProductVariantPayload(
            $details,
            $checkoutWarehouseId ? (int) $checkoutWarehouseId : null
        );
        $variantPayload['variants'] = collect($variantPayload['variants'] ?? [])->values()->all();
        $variantPayload['attribute_groups'] = collect($variantPayload['attribute_groups'] ?? [])->values()->all();

        $variantGallery = $details->productVariants
            ->filter(function ($variant) use ($variantPayload) {
                return collect($variantPayload['variants'] ?? [])
                    ->contains(fn ($row) => (int) ($row['id'] ?? 0) === (int) $variant->id);
            })
            ->flatMap(function ($variant) {
                return $variant->variantImages->sortByDesc(function ($img) { return (int) (bool) $img->is_primary; })->values()->map(function ($variantImage) use ($variant) {
                    $path = trim((string) $variantImage->image_path);
                    if ($path !== '' && ! \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'data:', 'storage/', 'public/'])) {
                        $path = 'public/storage/'.ltrim($path, '/');
                    } elseif (\Illuminate\Support\Str::startsWith($path, 'storage/')) {
                        $path = 'public/' . $path;
                    }

                    return [
                        'src' => $path,
                        'variant_id' => (int) $variant->id,
                    ];
                });
            });

        $productGallery = $details->images->map(function ($image) {
            $path = (string) $image->image;
            if (\Illuminate\Support\Str::startsWith($path, 'storage/')) {
                $path = 'public/' . $path;
            }

            return [
                'src' => $path,
                'variant_id' => null,
            ];
        });

        $displayGallery = $variantGallery->isNotEmpty()
            ? $variantGallery
            : $productGallery;

        if ($displayGallery->isEmpty()) {
            $fallback = $details->display_image;
            if (! empty($fallback)) {
                $displayGallery->push([
                    'src' => (string) $fallback,
                    'variant_id' => null,
                ]);
            }
        }

        $displayGallery = $displayGallery
            ->filter(fn ($item) => ! empty($item['src']))
            ->unique(fn ($item) => (string) ($item['variant_id'] ?? 'product').'|'.(string) $item['src'])
            ->values();

        return view('frontEnd.layouts.pages.details', compact(
            'details',
            'products',
            'shippingcharge',
            'reviews',
            'variantPayload',
            'displayGallery'
        ));
    }

    public function quickview(Request $request)
    {
        $data['data'] = Product::where(['id' => $request->id, 'status' => 1])->with('images')->withCount('reviews')->first();
        $data = view('frontEnd.layouts.ajax.quickview', $data)->render();
        if ($data != '') {
            echo $data;
        }
    }

    public function livesearch(Request $request)
    {
        // ðŸ” 2. ENHANCED SEARCH SYSTEM
        $query = trim($request->keyword ?? '');
        $categoryId = $request->category;

        if (strlen($query) < 2 && ! $categoryId) {
            return view('frontEnd.layouts.ajax.search', ['products' => collect()]);
        }

        $cacheKey = 'storefront:livesearch:v1:'.md5((string) json_encode([
            'q' => strtolower($query),
            'category' => $categoryId,
            'warehouse' => (int) Session::get('warehouse_id', 0),
        ]));

        $products = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($query, $categoryId) {
            $productsQuery = Product::select('products.id', 'products.name', 'products.slug', 'products.new_price', 'products.old_price', 'products.sold')
                ->where('products.status', 1)
                ->with(['image', 'category', 'warehouseStocks'])
                ->when($query, function ($q) use ($query) {
                    return $q->where(function ($subQuery) use ($query) {
                        // Enhanced search with weighted priority
                        $subQuery->where('products.name', 'LIKE', "%{$query}%")
                            ->orWhere('products.sku', 'LIKE', "%{$query}%")
                            ->orWhere('products.product_code', 'LIKE', "%{$query}%")
                            ->orWhereHas('brand', function ($brandQuery) use ($query) {
                                $brandQuery->where('name', 'LIKE', "%{$query}%");
                            })
                            ->orWhereHas('category', function ($categoryQuery) use ($query) {
                                $categoryQuery->where('name', 'LIKE', "%{$query}%");
                            });
                    });
                })
                ->when($categoryId, function ($q) use ($categoryId) {
                    return $q->where('products.category_id', $categoryId);
                });

            $this->applyStockAwareSearchOrdering($productsQuery, $query);

            return $productsQuery
                ->limit(10)
                ->get();
        });

        // Log search for analytics (Phase 2)
        if ($query) {
            $this->logSearchQuery($query, $categoryId, 'live_search', count($products));
        }

        return view('frontEnd.layouts.ajax.search', compact('products'));
    }

    /**
     * JSON API for admin product search (uses frontend search logic)
     */
    public function apiLivesearch(Request $request)
    {
        // ðŸ” ADMIN SEARCH USING FRONTEND LOGIC
        $query = trim($request->keyword ?? '');
        $categoryId = $request->category;
        $limit = (int) ($request->limit ?? 15);
        $limit = max(1, min($limit, 50));

        if (strlen($query) < 2 && ! $categoryId) {
            return response()->json([
                'success' => false,
                'message' => 'Query too short',
                'data' => [],
            ]);
        }

        $cacheKey = 'storefront:api-livesearch:v1:'.md5((string) json_encode([
            'q' => strtolower($query),
            'category' => $categoryId,
            'limit' => $limit,
        ]));

        $products = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($query, $categoryId, $limit) {
            $productsQuery = Product::select('products.id', 'products.name', 'products.slug', 'products.new_price', 'products.old_price', 'products.sold', 'products.sku', 'products.product_code')
                ->where('products.status', 1)
                ->with(['image', 'category', 'warehouseStocks'])
                ->when($query, function ($q) use ($query) {
                    return $q->where(function ($subQuery) use ($query) {
                        // Enhanced search with weighted priority
                        $subQuery->where('products.name', 'LIKE', "%{$query}%")
                            ->orWhere('products.sku', 'LIKE', "%{$query}%")
                            ->orWhere('products.product_code', 'LIKE', "%{$query}%")
                            ->orWhereHas('brand', function ($brandQuery) use ($query) {
                                $brandQuery->where('name', 'LIKE', "%{$query}%");
                            })
                            ->orWhereHas('category', function ($categoryQuery) use ($query) {
                                $categoryQuery->where('name', 'LIKE', "%{$query}%");
                            });
                    });
                })
                ->when($categoryId, function ($q) use ($categoryId) {
                    return $q->where('products.category_id', $categoryId);
                });

            $this->applyStockAwareSearchOrdering($productsQuery, $query);

            return $productsQuery
                ->limit($limit)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'sku' => $product->sku,
                        'product_code' => $product->product_code,
                        'new_price' => $product->new_price,
                        'old_price' => $product->old_price,
                        'image' => asset($product->display_image),
                        'category_name' => $product->category?->name,
                        'has_stock' => $product->warehouseStocks->sum('available_quantity') > 0,
                    ];
                })
                ->values()
                ->all();
        });

        // Log search for analytics (Phase 2)
        if ($query) {
            $this->logSearchQuery($query, $categoryId, 'admin_live_search', count($products));
        }

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function search(Request $request)
    {
        // ðŸ” 2. ENHANCED SEARCH SYSTEM
        $query = trim($request->keyword ?? '');
        $categoryId = $request->category;
        $sort = $request->sort ?? 'relevance';
        $minPrice = $request->min_price;
        $maxPrice = $request->max_price;

        $productsQuery = Product::select('products.id', 'products.name', 'products.slug', 'products.new_price', 'products.old_price', 'products.sold', 'products.status')
            ->where('products.status', 1)
            ->with([
                'image',
                'category',
                'warehouseStocks',
                'prosizes',
                'procolors',
                'reviews' => function ($query) {
                    $query->select('id', 'product_id', 'ratting');
                },
            ])
            ->when($query, function ($q) use ($query) {
                return $q->where(function ($subQuery) use ($query) {
                    // Enhanced fuzzy search
                    $searchTerms = explode(' ', $query);
                    foreach ($searchTerms as $term) {
                        if (strlen($term) > 2) {
                            $subQuery->where(function ($termQuery) use ($term) {
                                $termQuery->where('products.name', 'LIKE', "%{$term}%")
                                    ->orWhere('products.sku', 'LIKE', "%{$term}%")
                                    ->orWhere('products.product_code', 'LIKE', "%{$term}%")
                                    ->orWhereHas('brand', function ($brandQuery) use ($term) {
                                        $brandQuery->where('name', 'LIKE', "%{$term}%");
                                    })
                                    ->orWhereHas('category', function ($categoryQuery) use ($term) {
                                        $categoryQuery->where('name', 'LIKE', "%{$term}%");
                                    });
                            });
                        }
                    }
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                return $q->where('products.category_id', $categoryId);
            })
            ->when($minPrice, function ($q) use ($minPrice) {
                return $q->where('products.new_price', '>=', $minPrice);
            })
            ->when($maxPrice, function ($q) use ($maxPrice) {
                return $q->where('products.new_price', '<=', $maxPrice);
            });

        // Enhanced sorting with stability
        switch ($sort) {
            case 'price_low':
                $productsQuery->orderBy('products.new_price', 'asc');
                break;
            case 'price_high':
                $productsQuery->orderBy('products.new_price', 'desc');
                break;
            case 'newest':
                $productsQuery->orderBy('products.created_at', 'desc');
                break;
            case 'rating':
                $productsQuery->withCount(['reviews as avg_rating' => function ($query) {
                    $query->selectRaw('AVG(ratting)');
                }])->orderBy('avg_rating', 'desc');
                break;
            case 'popular':
                $productsQuery->orderBy('products.sold', 'desc');
                break;
            case 'relevance':
            default:
                if ($query) {
                    $this->applyStockAwareSearchOrdering($productsQuery, $query, true);
                } else {
                    $productsQuery->orderBy('products.sold', 'desc');
                }
                break;
        }

        $products = $productsQuery->paginate(36)->withQueryString();
        $keyword = $request->keyword;

        // Log search for analytics (Phase 2)
        if ($query) {
            $this->logSearchQuery($query, $categoryId, 'full_search', $products->total());
        }

        // Get categories for filter sidebar
        $categories = Cache::remember('storefront:search:categories_with_counts:v1', now()->addMinutes(10), function () {
            return Category::where('status', 1)->withCount(['products' => function ($query) {
                $query->where('status', 1);
            }])->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get();
        });

        return view('frontEnd.layouts.pages.search', compact('products', 'keyword', 'categories'));
    }

    private function applyStockAwareSearchOrdering(Builder $productsQuery, string $searchQuery, bool $preferNewestAsTiebreaker = false): void
    {
        $stockSummary = DB::table('warehouse_stock')
            ->selectRaw('product_id, SUM(COALESCE(physical_quantity, 0) - COALESCE(reserved_quantity, 0)) as available_quantity')
            ->groupBy('product_id');

        $productsQuery->leftJoinSub($stockSummary, 'warehouse_stock_summary', function ($join) {
            $join->on('products.id', '=', 'warehouse_stock_summary.product_id');
        });

        $orderBySql = '
            CASE
                WHEN COALESCE(warehouse_stock_summary.available_quantity, 0) > 0 THEN 1
                ELSE 2
            END,
            CASE
                WHEN products.name LIKE ? THEN 1
                WHEN products.sku LIKE ? THEN 2
                WHEN products.product_code LIKE ? THEN 3
                ELSE 4
            END,
            products.sold DESC
        ';

        if ($preferNewestAsTiebreaker) {
            $orderBySql .= ',
            products.created_at DESC';
        }

        $productsQuery->orderByRaw($orderBySql, [
            "%{$searchQuery}%",
            "%{$searchQuery}%",
            "%{$searchQuery}%",
        ]);
    }

    /**
     * Log search queries for analytics (Phase 2 feature)
     */
    private function logSearchQuery($query, $categoryId, $searchType, $resultsCount)
    {
        // Store in cache for now, could be moved to database table later
        $searchKey = 'search_analytics_'.date('Y-m-d');
        $searches = Cache::get($searchKey, []);

        $searches[] = [
            'query' => $query,
            'category_id' => $categoryId,
            'type' => $searchType,
            'results_count' => $resultsCount,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'timestamp' => now(),
        ];

        Cache::put($searchKey, $searches, now()->addDays(7));
    }

    public function shipping_charge(Request $request)
    {
        $data = Cart::instance('shopping')->content();
        $shippingId = (int) $request->input('id', 0);

        if ($shippingId > 0) {
            try {
                $totals = app(CheckoutTotalsService::class)->calculateForShoppingCart(
                    $data,
                    $shippingId,
                    Money::fromMajor((float) Session::get('discount', 0))
                );

                Session::put('shipping', Money::toMajorFloat((int) $totals['shipping_minor']));
            } catch (Throwable $e) {
                report($e);

                $shipping = ShippingCharge::query()
                    ->where('id', $shippingId)
                    ->where('status', 1)
                    ->first();

                if (! $shipping) {
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'message' => 'Invalid shipping option selected.',
                        ], 422);
                    }

                    return $this->renderCartResponse($request, $data);
                }

                Session::put('shipping', (float) $shipping->amount);
            }
        } elseif (! Session::has('shipping')) {
            $defaultShipping = ShippingCharge::query()
                ->where('status', 1)
                ->orderBy('id')
                ->first();

            if ($defaultShipping) {
                Session::put('shipping', (float) $defaultShipping->amount);
            }
        }

        return $this->renderCartResponse($request, $data);
    }

    private function renderCartResponse(Request $request, $data)
    {
        if ((string) $request->input('context', '') === 'campaign') {
            return view('frontEnd.components.campaign.cart-table', compact('data'));
        }

        return view('frontEnd.layouts.ajax.cart', compact('data'));
    }

    public function contact(Request $request)
    {
        // Check if form data is present
        if ($request->has(['name', 'phone', 'email', 'subject', 'message'])) {
            // Validate input
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|numeric',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);

            // Prepare data for email
            $data = [
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
            ];

            // Send email
            $contact = Contact::where('status', 1)->first();
            if ($contact->email) {
                try {
                    Mail::to($contact->email)->send(new ContactMail($data));
                } catch (Exception $e) {
                    // Log the exception message
                    Log::error('Email sending failed: '.$e->getMessage());
                }
            }

            // Redirect to the same page with a success message in query parameters
            return redirect()->route('contact')->with('success', 'Your message has been sent successfully!');
        }

        // Load the contact form view with any success message
        return view('frontEnd.layouts.pages.contact');
    }

    public function blog()
    {
        $page = CreatePage::where('status', 1)
            ->where(function ($query) {
                $query->whereIn('slug', ['blog', 'blogs'])
                    ->orWhere('name', 'like', '%blog%');
            })
            ->orderByRaw("CASE WHEN slug = 'blog' THEN 0 WHEN slug = 'blogs' THEN 1 ELSE 2 END")
            ->orderBy('id', 'ASC')
            ->first();

        if (! $page) {
            return redirect()->route('home');
        }

        return view('frontEnd.layouts.pages.page', compact('page'));
    }

    public function page($slug)
    {
        $page = CreatePage::where('slug', $slug)->firstOrFail();

        return view('frontEnd.layouts.pages.page', compact('page'));
    }

    public function districts(Request $request)
    {
        $areas = District::where(['district' => $request->id])->pluck('area_name', 'id');

        return response()->json($areas);
    }

    public function campaign($slug)
    {
        $campaign_data = Campaign::where('slug', $slug)->with('images')->firstOrFail();

        $products = Product::query()
            ->where('status', 1)
            ->where(function ($query) use ($campaign_data) {
                $query->whereIn('id', function ($subQuery) use ($campaign_data) {
                    $subQuery->select('product_id')
                        ->from('campaign_product')
                        ->where('campaign_id', $campaign_data->id);
                });

                if (! empty($campaign_data->product_id)) {
                    $query->orWhere('id', (int) $campaign_data->product_id);
                }
            })
            ->with('image')
            ->get();

        $campaignProductIds = $products->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $activeCampaignSlug = (string) Session::get('active_campaign_slug', '');
        $cartHasForeignItems = Cart::instance('shopping')->content()->contains(function ($item) use ($campaignProductIds) {
            return ! in_array((int) $item->id, $campaignProductIds, true);
        });

        if ($activeCampaignSlug !== (string) $campaign_data->slug || $cartHasForeignItems) {
            Cart::instance('shopping')->destroy();
            Session::put('active_campaign_slug', (string) $campaign_data->slug);
        }

        if (Cart::instance('shopping')->count() <= 0 && $products->isNotEmpty()) {
            $this->addCampaignProductToCart($products->first());
        }

        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $select_charge = ShippingCharge::where('status', 1)->first();
        if ($select_charge) {
            $cartItems = Cart::instance('shopping')->content();
            if ($cartItems->isNotEmpty()) {
                try {
                    $totals = app(\App\Domain\Checkout\CheckoutTotalsService::class)->calculateForShoppingCart(
                        $cartItems,
                        $select_charge->id,
                        \App\Support\Money::fromMajor((float) Session::get('discount', 0))
                    );
                    Session::put('shipping', \App\Support\Money::toMajorFloat((int) $totals['shipping_minor']));
                } catch (\Throwable $e) {
                    Session::put('shipping', $select_charge->amount);
                }
            } else {
                Session::put('shipping', $select_charge->amount);
            }
        }

        return view('frontEnd.layouts.pages.campaign.campaign', compact('campaign_data', 'products', 'shippingcharge'));
    }

    private function addCampaignProductToCart(Product $product): void
    {
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
            'qty' => 1,
            'price' => $product->new_price,
            'options' => [
                'slug' => $product->slug,
                'image' => $product->display_image,
                'old_price' => $product->old_price,
                'purchase_price' => $product->purchase_price,
                'product_size' => null,
                'product_color' => null,
                'pro_unit' => $product->pro_unit,
                'product_variant_id' => null,
                'variant_id' => null,
                'warehouse_id' => $warehouseId,
            ],
        ]);
    }

    public function payment_success(Request $request)
    {
        $order_id = $request->order_id;
        $shurjopay_service = new ShurjopayController;
        $json = $shurjopay_service->verify($order_id);
        $data = json_decode($json);

        if ($data[0]->sp_code != 1000) {
            Toastr::error('Your payment failed, try again', 'Oops!');
            if ($data[0]->value1 == 'customer_payment') {
                return redirect()->route('home');
            } else {
                return redirect()->route('home');
            }
        }

        if ($data[0]->value1 == 'customer_payment') {
            $checkoutPhone = (string) (Auth::guard('customer')->user()->phone ?? $data[0]->phone_no ?? '');
            $phoneBlock = app(PhoneBlockService::class)->getActiveBlockForPhone($checkoutPhone);
            if ($phoneBlock) {
                Toastr::error('This phone number is blocked for new orders due to repeated cancellations.', 'Blocked');

                return redirect()->route('home');
            }

            // Best-effort warehouse assignment so stock reservation can work.
            $warehouseId = Session::get('warehouse_id');
            if (! $warehouseId) {
                $firstCart = Cart::instance('shopping')->content()->first();
                $warehouseId = $firstCart?->options?->warehouse_id;
            }
            if (! $warehouseId) {
                $warehouse = Warehouse::main()->active()->first() ?? Warehouse::active()->first();
                $warehouseId = $warehouse?->id;
            }
            if (! $warehouseId) {
                throw new \RuntimeException('Unable to determine warehouse for paid order.');
            }

            $order = DB::transaction(function () use ($data, $warehouseId): Order {
                $order = new Order;
                $order->invoice_id = $data[0]->id;
                $order->amount = $data[0]->amount;
                $order->customer_id = Auth::guard('customer')->user()->id;
                $order->order_status = $data[0]->bank_status;
                $order->warehouse_id = (int) $warehouseId;
                $order->save();

                $payment = new Payment;
                $payment->order_id = $order->id;
                $payment->customer_id = Auth::guard('customer')->user()->id;
                $payment->payment_method = 'shurjopay';
                $payment->amount = $order->amount;
                $payment->trx_id = $data[0]->bank_trx_id;
                $payment->sender_number = $data[0]->phone_no;
                $payment->payment_status = 'paid';
                $payment->save();

                foreach (Cart::instance('shopping')->content() as $cart) {
                    $orderDetails = new OrderDetails;
                    $orderDetails->order_id = $order->id;
                    $orderDetails->product_id = $cart->id;
                    $orderDetails->product_name = $cart->name;
                    $orderDetails->purchase_price = $cart->options->purchase_price;
                    $orderDetails->sale_price = $cart->price;
                    $orderDetails->qty = $cart->qty;
                    $orderDetails->warehouse_id = (int) $warehouseId;
                    $orderDetails->save();
                }

                app(StockEngine::class)->reserveForOrder($order);

                return $order;
            });

            Cart::instance('shopping')->destroy();
            Toastr::error('Thanks, Your payment send successfully', 'Success!');

            return redirect()->route('home');
        }

        Toastr::error('Something wrong, please try agian', 'Error!');

        return redirect()->route('home');
    }

    public function payment_cancel(Request $request)
    {
        $order_id = $request->order_id;
        $shurjopay_service = new ShurjopayController;
        $json = $shurjopay_service->verify($order_id);
        $data = json_decode($json);

        Toastr::error('Your payment cancelled', 'Cancelled!');
        if ($data[0]->sp_code != 1000) {
            if ($data[0]->value1 == 'customer_payment') {
                return redirect()->route('home');
            } else {
                return redirect()->route('home');
            }
        }
    }

    public function offers()
    {
        return view('frontEnd.layouts.pages.offers');
    }

    public function facebookCatalogFeed(Request $request)
    {
        $includeOutOfStock = $request->boolean('include_out_of_stock');
        $limit = min(max((int) $request->integer('limit', 5000), 1), 10000);

        $stockSubquery = DB::table('warehouse_stock')
            ->select('product_id')
            ->selectRaw('SUM(COALESCE(available_quantity, physical_quantity - reserved_quantity, 0)) as available_quantity')
            ->whereNull('product_variant_id')
            ->groupBy('product_id');

        $products = Product::query()
            ->withoutGlobalScopes()
            ->with(['image', 'brand', 'category'])
            ->leftJoinSub($stockSubquery, 'feed_stock', function ($join): void {
                $join->on('feed_stock.product_id', '=', 'products.id');
            })
            ->select('products.*')
            ->selectRaw('COALESCE(feed_stock.available_quantity, 0) as feed_available_quantity')
            ->where('products.status', 1)
            ->whereNotNull('products.slug')
            ->where(function (Builder $query): void {
                $query->where('products.new_price', '>', 0)
                    ->orWhere('products.new_price_minor', '>', 0);
            })
            ->when(! $includeOutOfStock, function (Builder $query): void {
                $query->whereRaw('COALESCE(feed_stock.available_quantity, 0) > 0');
            })
            ->when($request->filled('category_id'), function (Builder $query) use ($request): void {
                $query->where('products.category_id', (int) $request->input('category_id'));
            })
            ->when($request->filled('brand_id'), function (Builder $query) use ($request): void {
                $query->where('products.brand_id', (int) $request->input('brand_id'));
            })
            ->when($request->filled('product_ids'), function (Builder $query) use ($request): void {
                $ids = collect(explode(',', (string) $request->input('product_ids')))
                    ->map(fn (string $id): int => (int) trim($id))
                    ->filter(fn (int $id): bool => $id > 0)
                    ->values()
                    ->all();

                if (! empty($ids)) {
                    $query->whereIn('products.id', $ids);
                }
            })
            ->orderByDesc('products.id')
            ->limit($limit)
            ->get();

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $xml->startElement('channel');
        $xml->writeElement('title', config('app.name', 'Product Catalog'));
        $xml->writeElement('link', url('/'));
        $xml->writeElement('description', 'Facebook and Instagram product catalog feed');

        foreach ($products as $product) {
            $priceMinor = (int) ($product->new_price_minor ?? 0);
            $price = $priceMinor > 0 ? round($priceMinor / 100, 2) : round((float) $product->new_price, 2);
            $currency = strtoupper(trim((string) ($product->currency ?? 'BDT'))) ?: 'BDT';
            $brand = trim((string) optional($product->brand)->name) ?: config('app.name', 'Amins UK');
            $description = (string) ($product->short_description ?: $product->meta_description ?: $product->description ?: $product->name);
            $description = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($description))));
            $imagePath = $product->display_image;

            if (! Str::startsWith($imagePath, ['http://', 'https://'])) {
                $imagePath = ltrim(str_replace('\\', '/', $imagePath), '/');
                $imagePath = asset($imagePath);
            }

            $feedId = trim((string) ($product->sku ?: $product->product_code)) ?: 'product-'.$product->id;
            $mpn = trim((string) ($product->product_code ?: $product->sku ?: 'product-'.$product->id));

            $xml->startElement('item');
            $xml->writeElement('g:id', $feedId);
            $xml->writeElement('g:title', Str::limit(strip_tags((string) $product->name), 150, ''));
            $xml->writeElement('g:description', Str::limit($description !== '' ? $description : (string) $product->name, 5000, ''));
            $xml->writeElement('g:availability', (float) ($product->feed_available_quantity ?? 0) > 0 ? 'in stock' : 'out of stock');
            $xml->writeElement('g:condition', 'new');
            $xml->writeElement('g:price', number_format($price, 2, '.', '').' '.$currency);
            $xml->writeElement('g:link', route('product', ['id' => $product->slug]));
            $xml->writeElement('g:image_link', $imagePath);
            $xml->writeElement('g:brand', $brand);
            $xml->writeElement('g:mpn', $mpn);
            $xml->writeElement('g:identifier_exists', 'yes');
            $xml->writeElement('g:custom_label_0', optional($product->category)->name ?: 'General');
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}
