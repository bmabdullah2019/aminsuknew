<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\CreatePage;
use App\Models\EcomPixel;
use App\Models\GeneralSetting;
use App\Models\GoogleTagManager;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\SocialMedia;
use App\Models\WarehouseStock;
use App\Services\FacebookCatalogFeedService;
use App\Services\Licensing\LicensedDomainGuard;
use App\Support\StorefrontCache;
use Closure;
use Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\FileViewFinder;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    private const STORE_CACHE_MINUTES_SHORT = 5;

    private const STORE_CACHE_MINUTES_DEFAULT = 10;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app(LicensedDomainGuard::class)->enforce();

        $this->registerStorefrontCacheInvalidators();

        $this->app->booted(function () {
            $this->pruneInvalidViewNamespaces();
        });

        if (app()->environment('testing')) {
            return;
        }

        try {
            $shurjopay = $this->rememberStorefrontCache(
                'storefront:payment-gateway:shurjopay:v1',
                self::STORE_CACHE_MINUTES_SHORT,
                static fn () => PaymentGateway::where(['status' => 1, 'type' => 'shurjopay'])->first()
            );
            if ($shurjopay) {
                Config::set(['shurjopay.apiCredentials.username' => $shurjopay->username]);
                Config::set(['shurjopay.apiCredentials.password' => $shurjopay->password]);
                Config::set(['shurjopay.apiCredentials.prefix' => $shurjopay->prefix]);
                Config::set(['shurjopay.apiCredentials.return_url' => $shurjopay->success_url]);
                Config::set(['shurjopay.apiCredentials.cancel_url' => $shurjopay->return_url]);
                Config::set(['shurjopay.apiCredentials.base_url' => $shurjopay->base_url]);
            }

            // --- Shared by both admin and frontend ---
            $generalsetting = $this->rememberStorefrontCache(
                'storefront:settings:general:v1',
                self::STORE_CACHE_MINUTES_SHORT,
                static fn () => GeneralSetting::where('status', 1)->limit(1)->first()
            );
            view()->share('generalsetting', $generalsetting);

            $neworder = $this->rememberStorefrontCache(
                'storefront:orders:pending-count:v1',
                1,
                static fn () => Order::where('order_status', '1')->count()
            );
            view()->share('neworder', $neworder);

            $pendingorder = $this->rememberStorefrontCache(
                'storefront:orders:pending-list:v1',
                1,
                static fn () => Order::where('order_status', '1')->latest()->limit(9)->get()
            );
            view()->share('pendingorder', $pendingorder);

            $orderstatus = $this->rememberStorefrontCache(
                'storefront:order-status:all:v1',
                self::STORE_CACHE_MINUTES_DEFAULT,
                static fn () => OrderStatus::get()
            );
                        view()->share('orderstatus', $orderstatus);

            $contact = $this->rememberStorefrontCache(
                'storefront:contact:active:v1',
                self::STORE_CACHE_MINUTES_SHORT,
                static fn () => Contact::where('status', 1)->first()
            );
            view()->share('contact', $contact);

            // --- Storefront-only data: skip for admin panel requests ---
            $requestPath = request()->path();
            $isAdminRequest = str_starts_with($requestPath, 'admin') || str_starts_with($requestPath, 'aminsuk/admin');

            if (! $isAdminRequest) {
                $sidebarBanners = collect();
                $sidebarBannerCategoryId = (int) ($generalsetting->sidebar_banner_category_id ?? 0);
                if ($sidebarBannerCategoryId > 0) {
                    $sidebarBanners = $this->rememberStorefrontCache(
                        'storefront:banners:sidebar:category-'.$sidebarBannerCategoryId.':v1',
                        self::STORE_CACHE_MINUTES_DEFAULT,
                        static fn () => Banner::query()
                            ->where('status', 1)
                            ->where('category_id', $sidebarBannerCategoryId)
                            ->select('id', 'image', 'link')
                            ->orderByDesc('id')
                            ->limit(4)
                            ->get()
                    );
                }
                view()->share('sidebarBanners', $sidebarBanners);

                $sidecategories = $this->rememberStorefrontCache(
                    'storefront:categories:side:v1',
                    self::STORE_CACHE_MINUTES_DEFAULT,
                    static fn () => Category::where('parent_id', '=', '0')
                        ->where('status', 1)
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('id', 'ASC')
                        ->select('id', 'name', 'slug', 'status', 'image', 'sort_order')
                        ->get()
                );
                view()->share('sidecategories', $sidecategories);

                $menucategories = $this->rememberStorefrontCache(
                    'storefront:categories:menu:v1',
                    self::STORE_CACHE_MINUTES_DEFAULT,
                    static fn () => Category::where('status', 1)
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('id', 'ASC')
                        ->with([
                            'subcategories' => function ($query) {
                                $query->orderBy('sort_order', 'ASC')
                                    ->orderBy('id', 'ASC')
                                    ->with([
                                        'childcategories' => function ($childQuery) {
                                            $childQuery->orderBy('sort_order', 'ASC')
                                                ->orderBy('id', 'ASC');
                                        },
                                    ]);
                            },
                        ])
                        ->select('id', 'name', 'slug', 'status', 'image', 'sort_order')
                        ->get()
                );
                view()->share('menucategories', $menucategories);



                $socialicons = $this->rememberStorefrontCache(
                    'storefront:social-icons:active:v1',
                    self::STORE_CACHE_MINUTES_DEFAULT,
                    static fn () => SocialMedia::where('status', 1)->get()
                );
                view()->share('socialicons', $socialicons);

                $pagesCollection = $this->rememberStorefrontCache(
                    'storefront:pages:active:v1',
                    self::STORE_CACHE_MINUTES_DEFAULT,
                    static fn () => CreatePage::where('status', 1)->get()
                );

                $pages = $pagesCollection->take(3)->values();
                view()->share('pages', $pages);

                $pagesright = $pagesCollection->slice(1, 5)->values();
                view()->share('pagesright', $pagesright);

                $cmnmenu = $pagesCollection;
                view()->share('cmnmenu', $cmnmenu);

                $hotDealMenuProducts = $this->rememberStorefrontCache(
                    'storefront:header:hot-deals:v1',
                    self::STORE_CACHE_MINUTES_DEFAULT,
                    static fn () => Product::where('status', 1)
                        ->where('topsale', 1)
                        ->select('id', 'name', 'slug', 'new_price', 'old_price')
                        ->with('image')
                        ->orderBy('id', 'DESC')
                        ->get()
                );
                view()->share('hotDealMenuProducts', $hotDealMenuProducts);

                $brands = $this->rememberStorefrontCache(
                    'storefront:brands:active:v1',
                    self::STORE_CACHE_MINUTES_DEFAULT,
                    static fn () => Brand::where('status', 1)->get()
                );
                view()->share('brands', $brands);

                view()->share('activePixelCodes', EcomPixel::getActiveCodesCached());
                view()->share('activeGtmCodes', GoogleTagManager::getActiveCodesCached());
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function rememberStorefrontCache(string $key, int $minutes, Closure $resolver)
    {
        return Cache::remember(StorefrontCache::versionedKey($key), now()->addMinutes($minutes), $resolver);
    }

    private function registerStorefrontCacheInvalidators(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $bustStorefrontCache = static function (): void {
            StorefrontCache::bumpVersion();
        };

        $bustFacebookCatalogFeed = static function (): void {
            Cache::forget(FacebookCatalogFeedService::CACHE_KEY);
        };

        GeneralSetting::saved($bustStorefrontCache);
        GeneralSetting::deleted($bustStorefrontCache);

        Banner::saved($bustStorefrontCache);
        Banner::deleted($bustStorefrontCache);

        Category::saved($bustStorefrontCache);
        Category::deleted($bustStorefrontCache);
        Category::saved($bustFacebookCatalogFeed);
        Category::deleted($bustFacebookCatalogFeed);

        Brand::saved($bustStorefrontCache);
        Brand::deleted($bustStorefrontCache);
        Brand::saved($bustFacebookCatalogFeed);
        Brand::deleted($bustFacebookCatalogFeed);

        SocialMedia::saved($bustStorefrontCache);
        SocialMedia::deleted($bustStorefrontCache);

        Contact::saved($bustStorefrontCache);
        Contact::deleted($bustStorefrontCache);

        CreatePage::saved($bustStorefrontCache);
        CreatePage::deleted($bustStorefrontCache);

        Product::saved($bustStorefrontCache);
        Product::deleted($bustStorefrontCache);
        Product::saved($bustFacebookCatalogFeed);
        Product::deleted($bustFacebookCatalogFeed);

        Productimage::saved($bustFacebookCatalogFeed);
        Productimage::deleted($bustFacebookCatalogFeed);

        WarehouseStock::saved($bustFacebookCatalogFeed);
        WarehouseStock::deleted($bustFacebookCatalogFeed);

        OrderStatus::saved($bustStorefrontCache);
        OrderStatus::deleted($bustStorefrontCache);

        PaymentGateway::saved($bustStorefrontCache);
        PaymentGateway::deleted($bustStorefrontCache);
    }

    /**
     * Some vendor packages register view namespaces pointing to directories
     * that may not exist in certain package builds. This breaks `view:cache`.
     */
    private function pruneInvalidViewNamespaces(): void
    {
        try {
            $finder = view()->getFinder();
            if (! $finder instanceof FileViewFinder) {
                return;
            }

            foreach ($finder->getHints() as $namespace => $paths) {
                $validPaths = array_values(array_filter($paths, static fn ($path) => is_dir($path)));
                if ($validPaths !== $paths) {
                    $finder->replaceNamespace($namespace, $validPaths);
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
