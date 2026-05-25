<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\Accounts\AccountSettingsController;
use App\Http\Controllers\Admin\Accounts\FiscalYearController;
use App\Http\Controllers\Admin\Accounts\OpeningBalanceController;
use App\Http\Controllers\Admin\Accounts\PaymentHeadMappingController;
use App\Http\Controllers\Admin\Accounts\ReportController as AccountsReportController;
use App\Http\Controllers\Admin\Accounts\SubsidiaryController;
use App\Http\Controllers\Admin\Accounts\VoucherController;
use App\Http\Controllers\Admin\AdjustmentController;
use App\Http\Controllers\Admin\AgeController;
use App\Http\Controllers\Admin\ApiIntegrationController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\BannerCategoryController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CashAccountController;
use App\Http\Controllers\Admin\CatalogAttributeController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChildcategoryController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\CreatePageController;
use App\Http\Controllers\Admin\CustomerManageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Controllers\Admin\GrnController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\PartialOrderController as AdminPartialOrderController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PixelsController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ProfitLossController;
use App\Http\Controllers\Admin\Reports\ReportsHubController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ShippingChargeController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\SocialMediaController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TagManagerController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Frontend\BkashController;
use App\Http\Controllers\Frontend\CustomerController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Frontend\ShoppingController;
use App\Http\Controllers\Frontend\ShurjopayControllers;
use App\Http\Controllers\Frontend\TrackingFallbackController;
use App\Http\Controllers\PartialOrderController;
use App\Modules\Reports\Controllers\FinancialReportController;
use App\Modules\Reports\Controllers\PerformanceReportController;
use App\Modules\Reports\Controllers\PurchaseReportController;
use App\Modules\Reports\Controllers\SalesReportController;
use App\Modules\Reports\Controllers\StockMovementController;
use App\Modules\Reports\Controllers\StockReportController;
use Illuminate\Support\Facades\Route;

Auth::routes();

Route::get('feeds/facebook-catalog.xml', [FrontendController::class, 'facebookCatalogFeed'])->name('feeds.facebook-catalog');

Route::group(['namespace' => 'Frontend', 'middleware' => ['ipcheck', 'check_refer']], function () {
    Route::get('/', [FrontendController::class, 'index'])->name('home');
    Route::get('category/{category}', [FrontendController::class, 'category'])->name('category');

    Route::get('subcategory/{subcategory}', [FrontendController::class, 'subcategory'])->name('subcategory');

    Route::get('products/{slug}', [FrontendController::class, 'products'])->name('products');

    Route::get('hot-deals', [FrontendController::class, 'hotdeals'])->name('hotdeals');
    Route::get('flash-sales', [FrontendController::class, 'flashsales'])->name('flashsales');
    Route::get('shop', [FrontendController::class, 'shop'])->name('shop');
    Route::get('blog', [FrontendController::class, 'blog'])->name('blog');
    Route::get('livesearch', [FrontendController::class, 'livesearch'])->name('livesearch');
    Route::get('api/livesearch', [FrontendController::class, 'apiLivesearch'])->name('api.livesearch');
    Route::get('search', [FrontendController::class, 'search'])->name('search');
    Route::get('product/{id}', [FrontendController::class, 'details'])->name('product');
    Route::get('quick-view', [FrontendController::class, 'quickview'])->name('quickview');
    Route::get('/shipping-charge', [FrontendController::class, 'shipping_charge'])->name('shipping.charge');
    Route::match(['get', 'post'], 'site/contact-us', [FrontendController::class, 'contact'])->name('contact');
    Route::get('/page/{slug}', [FrontendController::class, 'page'])->name('page');
    Route::get('districts', [FrontendController::class, 'districts'])->name('districts');
    Route::get('/campaign/{slug}', [FrontendController::class, 'campaign'])->name('campaign');
    Route::get('/offer', [FrontendController::class, 'offers'])->name('offers');

    // cart route
    Route::post('cart/store', [ShoppingController::class, 'cart_store'])->name('cart.store');
    Route::post('compare/store', [ShoppingController::class, 'compareStore'])->name('compare.store');
    Route::post('compare/remove', [ShoppingController::class, 'compareRemove'])->name('compare.remove');
    Route::post('compare/clear', [ShoppingController::class, 'compareClear'])->name('compare.clear');
    Route::get('compare/count', [ShoppingController::class, 'compareCount'])->name('compare.count');
    Route::get('compare', [ShoppingController::class, 'compareShow'])->name('compare.show');

    Route::get('/add-to-cart/{id}/{qty}', [ShoppingController::class, 'addTocartGet']);

    Route::get('shop/cart', [ShoppingController::class, 'cart_show'])->name('cart.show');
    Route::get('cart/remove', [ShoppingController::class, 'cart_remove'])->name('cart.remove');
    Route::get('cart/count', [ShoppingController::class, 'cart_count'])->name('cart.count');
    Route::get('mobilecart/count', [ShoppingController::class, 'mobilecart_qty'])->name('mobile.cart.count');
    Route::get('cart/decrement', [ShoppingController::class, 'cart_decrement'])->name('cart.decrement');

    Route::get('cart/increment', [ShoppingController::class, 'cart_increment'])->name('cart.increment');
    Route::get('/cart/change-product', [ShoppingController::class, 'changeProduct'])->name('cart.changeProduct');
    Route::get('cart/update', [ShoppingController::class, 'cart_update'])->name('cart.update');
    Route::post('cart/apply-coupon', [ShoppingController::class, 'applyCoupon'])->name('coupon.apply');

    // Partial Checkout Routes
    Route::middleware(['web', 'guest.tracker'])->group(function () {
        Route::post('/partial-checkout/save', [PartialOrderController::class, 'save'])->name('partial.checkout.save')->middleware('throttle:60,1');
        Route::get('/partial-checkout/load', [PartialOrderController::class, 'load'])->name('partial.checkout.load');
    });

    Route::post('/api/tracking/fallback', [TrackingFallbackController::class, 'fallback'])
        ->name('tracking.fallback')
        ->middleware('throttle:60,1');

});

Route::group(['prefix' => 'customer', 'namespace' => 'Frontend', 'middleware' => ['ipcheck', 'check_refer']], function () {
    Route::get('/login', [CustomerController::class, 'login'])->name('customer.login');
    Route::post('/signin', [CustomerController::class, 'signin'])->middleware('throttle:customer-login')->name('customer.signin');
    Route::get('/register', [CustomerController::class, 'register'])->name('customer.register');
    Route::post('/store', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('/verify', [CustomerController::class, 'verify'])->name('customer.verify');
    Route::post('/verify-account', [CustomerController::class, 'account_verify'])->middleware('throttle:otp-verify')->name('customer.account.verify');
    Route::post('/resend-otp', [CustomerController::class, 'resendotp'])->middleware('throttle:otp-resend')->name('customer.resendotp');
    Route::post('/logout', [CustomerController::class, 'logout'])->name('customer.logout');
    Route::post('/post/review', [CustomerController::class, 'review'])->name('customer.review');
    Route::get('/forgot-password', [CustomerController::class, 'forgot_password'])->name('customer.forgot.password');
    Route::post('/forgot-verify', [CustomerController::class, 'forgot_verify'])->middleware('throttle:forgot-otp-request')->name('customer.forgot.verify');
    Route::get('/forgot-password/reset', [CustomerController::class, 'forgot_reset'])->name('customer.forgot.reset');
    Route::post('/forgot-password/store', [CustomerController::class, 'forgot_store'])->middleware('throttle:forgot-otp-verify')->name('customer.forgot.store');
    Route::post('/forgot-password/resendotp', [CustomerController::class, 'forgot_resend'])->middleware('throttle:otp-resend')->name('customer.forgot.resendotp');
    Route::get('/checkout', [CustomerController::class, 'checkout'])->name('customer.checkout');
    Route::post('/checkout/otp/request', [CustomerController::class, 'request_checkout_otp'])->middleware('throttle:checkout-otp')->name('customer.checkout.otp.request');
    Route::post('/checkout/otp/verify', [CustomerController::class, 'verify_checkout_otp'])->middleware('throttle:checkout-otp')->name('customer.checkout.otp.verify');
    Route::post('/order-save', [CustomerController::class, 'order_save'])->middleware('throttle:checkout-orders')->name('customer.ordersave');
    Route::get('/order-success/{id}', [CustomerController::class, 'order_success'])->name('customer.order_success');

    Route::get('/order-track', [CustomerController::class, 'order_track'])->name('customer.order_track');
    Route::get('/order-track/result', [CustomerController::class, 'order_track_result'])->middleware('throttle:order-tracking')->name('customer.order_track_result');

});
// customer auth
Route::group(['prefix' => 'customer', 'namespace' => 'Frontend', 'middleware' => ['customer', 'ipcheck', 'check_refer']], function () {

    Route::get('/account', [CustomerController::class, 'account'])->name('customer.account');

    Route::get('/orders', [CustomerController::class, 'orders'])->name('customer.orders');
    Route::get('/invoice', [CustomerController::class, 'invoice'])->name('customer.invoice');
    Route::get('/invoice/order-note', [CustomerController::class, 'order_note'])->name('customer.order_note');
    Route::get('/profile-edit', [CustomerController::class, 'profile_edit'])->name('customer.profile_edit');
    Route::post('/profile-update', [CustomerController::class, 'profile_update'])->name('customer.profile_update');
    Route::get('/change-password', [CustomerController::class, 'change_pass'])->name('customer.change_pass');
    Route::post('/password-update', [CustomerController::class, 'password_update'])->name('customer.password_update');

});

Route::group(['namespace' => 'Frontend', 'middleware' => ['ipcheck', 'check_refer']], function () {

    Route::get('bkash/checkout-url/pay', [BkashController::class, 'pay'])->name('url-pay');
    Route::any('bkash/checkout-url/create', [BkashController::class, 'create'])->name('url-create');
    Route::get('bkash/checkout-url/callback', [BkashController::class, 'callback'])->middleware('throttle:payment-callbacks')->name('url-callback');
    Route::post('webhooks/bkash/callback', [BkashController::class, 'webhook'])->middleware('throttle:payment-callbacks')->name('webhook.bkash.callback');
    Route::get('/payment-success', [ShurjopayControllers::class, 'payment_success'])->middleware('throttle:payment-callbacks')->name('payment_success');
    Route::post('/webhooks/shurjopay/callback', [ShurjopayControllers::class, 'webhook'])->middleware('throttle:payment-callbacks')->name('webhook.shurjopay.callback');
    Route::get('/payment-cancel', [ShurjopayControllers::class, 'payment_cancel'])->name('payment_cancel');

});

// unathenticate admin route
Route::group(['namespace' => 'Admin', 'prefix' => 'admin', 'middleware' => ['auth', 'ipcheck', 'check_refer']], function () {
    Route::get('locked', [DashboardController::class, 'locked'])->name('locked');
    Route::post('unlocked', [DashboardController::class, 'unlocked'])->name('unlocked');
});

// ajax route
Route::get('/ajax-product-subcategory', [ProductController::class, 'getSubcategory']);
Route::get('/ajax-product-childcategory', [ProductController::class, 'getChildcategory']);
Route::post('/api/shipping/calculate', [\App\Http\Controllers\Api\ShippingCalculationController::class, 'calculate'])->name('api.shipping.calculate');

// auth route
Route::group(['namespace' => 'Admin', 'middleware' => ['auth', 'lock', 'check_refer'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('change-password', [DashboardController::class, 'changepassword'])->name('change_password');
    Route::post('new-password', [DashboardController::class, 'newpassword'])->name('new_password');

    // POS System
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('pos/products', [PosController::class, 'getProducts'])->name('pos.products');
    Route::get('pos/customers', [PosController::class, 'searchCustomers'])->name('pos.customers');
    Route::post('pos/complete-sale', [PosController::class, 'completeSale'])->name('pos.complete-sale');
    Route::post('pos/calculate-shipping', [PosController::class, 'calculateShipping'])->name('pos.calculate-shipping');

    // users route
    Route::get('users/manage', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users/save', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::post('users/update', [UserController::class, 'update'])->name('users.update');
    Route::post('users/inactive', [UserController::class, 'inactive'])->name('users.inactive');
    Route::post('users/active', [UserController::class, 'active'])->name('users.active');
    Route::post('users/destroy', [UserController::class, 'destroy'])->name('users.destroy');

    // roles
    Route::get('roles/manage', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/{id}/show', [RoleController::class, 'show'])->name('roles.show');
    Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('roles/save', [RoleController::class, 'store'])->name('roles.store');
    Route::get('roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::post('roles/update', [RoleController::class, 'update'])->name('roles.update');
    Route::post('roles/destroy', [RoleController::class, 'destroy'])->name('roles.destroy');

    // permissions
    Route::get('permissions/manage', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('permissions/{id}/show', [PermissionController::class, 'show'])->name('permissions.show');
    Route::get('permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
    Route::post('permissions/save', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('permissions/{id}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::post('permissions/update', [PermissionController::class, 'update'])->name('permissions.update');
    Route::post('permissions/destroy', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // categories
    Route::get('categories/manage', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{id}/show', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('categories/save', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::post('categories/update', [CategoryController::class, 'update'])->name('categories.update');
    Route::post('categories/front-view-order', [CategoryController::class, 'updateFrontViewOrder'])->name('categories.front-view-order');
    Route::post('categories/inactive', [CategoryController::class, 'inactive'])->name('categories.inactive');
    Route::post('categories/active', [CategoryController::class, 'active'])->name('categories.active');
    Route::post('categories/destroy', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Subcategories
    Route::get('subcategories/manage', [SubcategoryController::class, 'index'])->name('subcategories.index');
    Route::get('subcategories/{id}/show', [SubcategoryController::class, 'show'])->name('subcategories.show');
    Route::get('subcategories/create', [SubcategoryController::class, 'create'])->name('subcategories.create');
    Route::post('subcategories/save', [SubcategoryController::class, 'store'])->name('subcategories.store');
    Route::get('subcategories/{id}/edit', [SubcategoryController::class, 'edit'])->name('subcategories.edit');
    Route::post('subcategories/update', [SubcategoryController::class, 'update'])->name('subcategories.update');
    Route::post('subcategories/inactive', [SubcategoryController::class, 'inactive'])->name('subcategories.inactive');
    Route::post('subcategories/active', [SubcategoryController::class, 'active'])->name('subcategories.active');
    Route::post('subcategories/destroy', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');

    // Childcategories
    Route::get('childcategories/manage', [ChildcategoryController::class, 'index'])->name('childcategories.index');
    Route::get('childcategories/{id}/show', [ChildcategoryController::class, 'show'])->name('childcategories.show');
    Route::get('childcategories/create', [ChildcategoryController::class, 'create'])->name('childcategories.create');
    Route::post('childcategories/save', [ChildcategoryController::class, 'store'])->name('childcategories.store');
    Route::get('childcategories/{id}/edit', [ChildcategoryController::class, 'edit'])->name('childcategories.edit');
    Route::post('childcategories/update', [ChildcategoryController::class, 'update'])->name('childcategories.update');
    Route::post('childcategories/inactive', [ChildcategoryController::class, 'inactive'])->name('childcategories.inactive');
    Route::post('childcategories/active', [ChildcategoryController::class, 'active'])->name('childcategories.active');
    Route::post('childcategories/destroy', [ChildcategoryController::class, 'destroy'])->name('childcategories.destroy');

    // paymentgeteway
    Route::get('paymentgeteway/manage', [ApiIntegrationController::class, 'pay_manage'])->name('paymentgeteway.manage');
    Route::post('paymentgeteway/save', [ApiIntegrationController::class, 'pay_update'])->name('paymentgeteway.update');

    // smsgeteway
    Route::get('smsgeteway/manage', [ApiIntegrationController::class, 'sms_manage'])->name('smsgeteway.manage');
    Route::post('smsgeteway/save', [ApiIntegrationController::class, 'sms_update'])->name('smsgeteway.update');

    // courierapi
    Route::get('courierapi/manage', [ApiIntegrationController::class, 'courier_manage'])->name('courierapi.manage');
    Route::post('courierapi/save', [ApiIntegrationController::class, 'courier_update'])->name('courierapi.update');

    Route::get('fraud-checker-api/manage', [ApiIntegrationController::class, 'fraud_checker_manage'])->name('fraud-checker-api.manage');
    Route::post('fraud-checker-api/save', [ApiIntegrationController::class, 'fraud_checker_update'])->name('fraud-checker-api.update');
    Route::post('fraud-checker-api/test', [ApiIntegrationController::class, 'fraud_checker_test'])->name('fraud-checker-api.test');
    Route::get('fraud-checker/detailed', [ApiIntegrationController::class, 'fraud_checker_detailed'])->name('fraud-checker.detailed');

    // Steadfast Courier Full Integration
    Route::prefix('steadfast')->name('steadfast.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\SteadfastController::class, 'dashboard'])->name('dashboard');
        Route::get('/check-status', [\App\Http\Controllers\Admin\SteadfastController::class, 'checkStatus'])->name('check-status');
        Route::post('/check-status', [\App\Http\Controllers\Admin\SteadfastController::class, 'checkStatus'])->name('check-status.post');
        Route::get('/return-requests', [\App\Http\Controllers\Admin\SteadfastController::class, 'returnRequests'])->name('return-requests');
        Route::post('/return-requests', [\App\Http\Controllers\Admin\SteadfastController::class, 'returnRequests'])->name('return-requests.create');
        Route::get('/payments', [\App\Http\Controllers\Admin\SteadfastController::class, 'payments'])->name('payments');
        Route::get('/payments/{id}', [\App\Http\Controllers\Admin\SteadfastController::class, 'payment'])->name('payments.show');
        Route::post('/payments/{id}/sync-accounting', [\App\Http\Controllers\Admin\SteadfastController::class, 'syncPayment'])->name('payments.sync-accounting');
        Route::get('/police-stations', [\App\Http\Controllers\Admin\SteadfastController::class, 'policeStations'])->name('police-stations');
        Route::post('/sync-status', [\App\Http\Controllers\Admin\SteadfastController::class, 'syncOrderStatus'])->name('sync-status');
    });

    // attribute
    Route::get('orderstatus/manage', [OrderStatusController::class, 'index'])->name('orderstatus.index');
    Route::get('orderstatus/{id}/show', [OrderStatusController::class, 'show'])->name('orderstatus.show');
    Route::get('orderstatus/create', [OrderStatusController::class, 'create'])->name('orderstatus.create');
    Route::post('orderstatus/save', [OrderStatusController::class, 'store'])->name('orderstatus.store');
    Route::get('orderstatus/{id}/edit', [OrderStatusController::class, 'edit'])->name('orderstatus.edit');
    Route::post('orderstatus/update', [OrderStatusController::class, 'update'])->name('orderstatus.update');
    Route::post('orderstatus/inactive', [OrderStatusController::class, 'inactive'])->name('orderstatus.inactive');
    Route::post('orderstatus/active', [OrderStatusController::class, 'active'])->name('orderstatus.active');
    Route::post('orderstatus/destroy', [OrderStatusController::class, 'destroy'])->name('orderstatus.destroy');

    // pixels
    Route::get('pixels/manage', [PixelsController::class, 'index'])->name('pixels.index');
    Route::get('pixels/{id}/show', [PixelsController::class, 'show'])->name('pixels.show');
    Route::get('pixels/create', [PixelsController::class, 'create'])->name('pixels.create');
    Route::post('pixels/save', [PixelsController::class, 'store'])->name('pixels.store');
    Route::get('pixels/{id}/edit', [PixelsController::class, 'edit'])->name('pixels.edit');
    Route::post('pixels/update', [PixelsController::class, 'update'])->name('pixels.update');
    Route::post('pixels/inactive', [PixelsController::class, 'inactive'])->name('pixels.inactive');
    Route::post('pixels/active', [PixelsController::class, 'active'])->name('pixels.active');
    Route::post('pixels/destroy', [PixelsController::class, 'destroy'])->name('pixels.destroy');

    // tag manager
    Route::get('tag-manager/manage', [TagManagerController::class, 'index'])->name('tagmanagers.index');
    Route::get('tag-manager/{id}/show', [TagManagerController::class, 'show'])->name('tagmanagers.show');
    Route::get('tag-manager/create', [TagManagerController::class, 'create'])->name('tagmanagers.create');
    Route::post('tag-manager/save', [TagManagerController::class, 'store'])->name('tagmanagers.store');
    Route::get('tag-manager/{id}/edit', [TagManagerController::class, 'edit'])->name('tagmanagers.edit');
    Route::post('tag-manager/update', [TagManagerController::class, 'update'])->name('tagmanagers.update');
    Route::post('tag-manager/inactive', [TagManagerController::class, 'inactive'])->name('tagmanagers.inactive');
    Route::post('tag-manager/active', [TagManagerController::class, 'active'])->name('tagmanagers.active');
    Route::post('tag-manager/destroy', [TagManagerController::class, 'destroy'])->name('tagmanagers.destroy');

    // attribute
    Route::get('brands/manage', [BrandController::class, 'index'])->name('brands.index');
    Route::get('brands/{id}/show', [BrandController::class, 'show'])->name('brands.show');
    Route::get('brands/create', [BrandController::class, 'create'])->name('brands.create');
    Route::post('brands/save', [BrandController::class, 'store'])->name('brands.store');
    Route::get('brands/{id}/edit', [BrandController::class, 'edit'])->name('brands.edit');
    Route::post('brands/update', [BrandController::class, 'update'])->name('brands.update');
    Route::post('brands/inactive', [BrandController::class, 'inactive'])->name('brands.inactive');
    Route::post('brands/active', [BrandController::class, 'active'])->name('brands.active');
    Route::post('brands/destroy', [BrandController::class, 'destroy'])->name('brands.destroy');

    // color
    Route::get('color/manage', [ColorController::class, 'index'])->name('colors.index');
    Route::get('color/{id}/show', [ColorController::class, 'show'])->name('colors.show');
    Route::get('color/create', [ColorController::class, 'create'])->name('colors.create');
    Route::post('color/save', [ColorController::class, 'store'])->name('colors.store');
    Route::get('color/{id}/edit', [ColorController::class, 'edit'])->name('colors.edit');
    Route::post('color/update', [ColorController::class, 'update'])->name('colors.update');
    Route::post('color/inactive', [ColorController::class, 'inactive'])->name('colors.inactive');
    Route::post('color/active', [ColorController::class, 'active'])->name('colors.active');
    Route::post('color/destroy', [ColorController::class, 'destroy'])->name('colors.destroy');

    // size
    Route::get('size/manage', [SizeController::class, 'index'])->name('sizes.index');
    Route::get('size/{id}/show', [SizeController::class, 'show'])->name('sizes.show');
    Route::get('size/create', [SizeController::class, 'create'])->name('sizes.create');
    Route::post('size/save', [SizeController::class, 'store'])->name('sizes.store');
    Route::get('size/{id}/edit', [SizeController::class, 'edit'])->name('sizes.edit');
    Route::post('size/update', [SizeController::class, 'update'])->name('sizes.update');
    Route::post('size/inactive', [SizeController::class, 'inactive'])->name('sizes.inactive');
    Route::post('size/active', [SizeController::class, 'active'])->name('sizes.active');
    Route::post('size/destroy', [SizeController::class, 'destroy'])->name('sizes.destroy');

    // age
    Route::get('age/manage', [AgeController::class, 'index'])->name('ages.index');
    Route::get('age/{id}/show', [AgeController::class, 'show'])->name('ages.show');
    Route::get('age/create', [AgeController::class, 'create'])->name('ages.create');
    Route::post('age/save', [AgeController::class, 'store'])->name('ages.store');
    Route::get('age/{id}/edit', [AgeController::class, 'edit'])->name('ages.edit');
    Route::post('age/update', [AgeController::class, 'update'])->name('ages.update');
    Route::post('age/inactive', [AgeController::class, 'inactive'])->name('ages.inactive');
    Route::post('age/active', [AgeController::class, 'active'])->name('ages.active');
    Route::post('age/destroy', [AgeController::class, 'destroy'])->name('ages.destroy');

    // global catalog attribute system
    Route::get('catalog-attributes/manage', [CatalogAttributeController::class, 'index'])->name('catalog-attributes.index');
    Route::get('catalog-attributes/create', [CatalogAttributeController::class, 'create'])->name('catalog-attributes.create');
    Route::post('catalog-attributes/store', [CatalogAttributeController::class, 'store'])->name('catalog-attributes.store');
    Route::get('catalog-attributes/{id}/edit', [CatalogAttributeController::class, 'edit'])->name('catalog-attributes.edit');
    Route::put('catalog-attributes/{id}/update', [CatalogAttributeController::class, 'update'])->name('catalog-attributes.update');
    Route::delete('catalog-attributes/{id}/delete', [CatalogAttributeController::class, 'destroy'])->name('catalog-attributes.destroy');
    Route::get('catalog-attributes/{id}/values', [CatalogAttributeController::class, 'values'])->name('catalog-attributes.values');
    Route::post('catalog-attributes/{id}/values/store', [CatalogAttributeController::class, 'valueStore'])->name('catalog-attributes.values.store');
    Route::put('catalog-attributes/{id}/values/{valueId}/update', [CatalogAttributeController::class, 'valueUpdate'])->name('catalog-attributes.values.update');
    Route::delete('catalog-attributes/{id}/values/{valueId}/delete', [CatalogAttributeController::class, 'valueDestroy'])->name('catalog-attributes.values.destroy');

    // product
    Route::get('products/manage', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{id}/show', [ProductController::class, 'show'])->name('products.show');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products/save', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::post('products/update', [ProductController::class, 'update'])->name('products.update');
    Route::post('products/inactive', [ProductController::class, 'inactive'])->name('products.inactive');
    Route::post('products/active', [ProductController::class, 'active'])->name('products.active');
    Route::post('products/destroy', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::delete('products/image/destroy', [ProductController::class, 'imgdestroy'])->name('products.image.destroy');
    Route::get('products/price/destroy', [ProductController::class, 'pricedestroy'])->name('products.price.destroy');
    Route::get('products/update-deals', [ProductController::class, 'update_deals'])->name('products.update_deals');
    Route::get('products/update-feature', [ProductController::class, 'update_feature'])->name('products.update_feature');
    Route::get('products/update-status', [ProductController::class, 'update_status'])->name('products.update_status');
    Route::get('products/price-edit', [ProductController::class, 'price_edit'])->name('products.price_edit');
    Route::post('products/price-update', [ProductController::class, 'price_update'])->name('products.price_update');
    Route::get('products/{id}/variants', [ProductVariantController::class, 'edit'])->name('products.variants.edit');
    Route::post('products/{id}/variants/update', [ProductVariantController::class, 'update'])->name('products.variants.update');

    // campaign
    Route::get('campaign/manage', [CampaignController::class, 'index'])->name('campaign.index');
    Route::get('campaign/{id}/show', [CampaignController::class, 'show'])->name('campaign.show');
    Route::get('campaign/create', [CampaignController::class, 'create'])->name('campaign.create');
    Route::post('campaign/save', [CampaignController::class, 'store'])->name('campaign.store');
    Route::get('campaign/{id}/edit', [CampaignController::class, 'edit'])->name('campaign.edit');
    Route::post('campaign/update', [CampaignController::class, 'update'])->name('campaign.update');
    Route::post('campaign/inactive', [CampaignController::class, 'inactive'])->name('campaign.inactive');
    Route::post('campaign/active', [CampaignController::class, 'active'])->name('campaign.active');
    Route::post('campaign/destroy', [CampaignController::class, 'destroy'])->name('campaign.destroy');
    Route::get('campaign/image/destroy', [CampaignController::class, 'imgdestroy'])->name('campaign.image.destroy');
    Route::post('campaign/gallery/delete', [CampaignController::class, 'deleteGalleryImages'])->name('campaign.gallery.delete');

    // settings route
    Route::get('settings/manage', [GeneralSettingController::class, 'index'])->name('settings.index');
    Route::get('settings/create', [GeneralSettingController::class, 'create'])->name('settings.create');
    Route::post('settings/save', [GeneralSettingController::class, 'store'])->name('settings.store');
    Route::get('settings/{id}/edit', [GeneralSettingController::class, 'edit'])->name('settings.edit');
    Route::post('settings/update', [GeneralSettingController::class, 'update'])->name('settings.update');
    Route::post('settings/inactive', [GeneralSettingController::class, 'inactive'])->name('settings.inactive');
    Route::post('settings/active', [GeneralSettingController::class, 'active'])->name('settings.active');
    Route::post('settings/destroy', [GeneralSettingController::class, 'destroy'])->name('settings.destroy');

    // settings route
    Route::get('social-media/manage', [SocialMediaController::class, 'index'])->name('socialmedias.index');
    Route::get('social-media/create', [SocialMediaController::class, 'create'])->name('socialmedias.create');
    Route::post('social-media/save', [SocialMediaController::class, 'store'])->name('socialmedias.store');
    Route::get('social-media/{id}/edit', [SocialMediaController::class, 'edit'])->name('socialmedias.edit');
    Route::post('social-media/update', [SocialMediaController::class, 'update'])->name('socialmedias.update');
    Route::post('social-media/inactive', [SocialMediaController::class, 'inactive'])->name('socialmedias.inactive');
    Route::post('social-media/active', [SocialMediaController::class, 'active'])->name('socialmedias.active');
    Route::post('social-media/destroy', [SocialMediaController::class, 'destroy'])->name('socialmedias.destroy');

    // contact route
    Route::get('contact/manage', [ContactController::class, 'index'])->name('contact.index');
    Route::get('contact/create', [ContactController::class, 'create'])->name('contact.create');
    Route::post('contact/save', [ContactController::class, 'store'])->name('contact.store');
    Route::get('contact/{id}/edit', [ContactController::class, 'edit'])->name('contact.edit');
    Route::post('contact/update', [ContactController::class, 'update'])->name('contact.update');
    Route::post('contact/inactive', [ContactController::class, 'inactive'])->name('contact.inactive');
    Route::post('contact/active', [ContactController::class, 'active'])->name('contact.active');
    Route::post('contact/destroy', [ContactController::class, 'destroy'])->name('contact.destroy');

    // banner category route
    Route::get('banner-category/manage', [BannerCategoryController::class, 'index'])->name('banner_category.index');
    Route::get('banner-category/create', [BannerCategoryController::class, 'create'])->name('banner_category.create');
    Route::post('banner-category/save', [BannerCategoryController::class, 'store'])->name('banner_category.store');
    Route::get('banner-category/{id}/edit', [BannerCategoryController::class, 'edit'])->name('banner_category.edit');
    Route::post('banner-category/update', [BannerCategoryController::class, 'update'])->name('banner_category.update');
    Route::post('banner-category/inactive', [BannerCategoryController::class, 'inactive'])->name('banner_category.inactive');
    Route::post('banner-category/active', [BannerCategoryController::class, 'active'])->name('banner_category.active');
    Route::post('banner-category/destroy', [BannerCategoryController::class, 'destroy'])->name('banner_category.destroy');

    // banner  route
    Route::get('banner/manage', [BannerController::class, 'index'])->name('banners.index');
    Route::get('banner/create', [BannerController::class, 'create'])->name('banners.create');
    Route::post('banner/save', [BannerController::class, 'store'])->name('banners.store');
    Route::get('banner/{id}/edit', [BannerController::class, 'edit'])->name('banners.edit');
    Route::post('banner/update', [BannerController::class, 'update'])->name('banners.update');
    Route::post('banner/inactive', [BannerController::class, 'inactive'])->name('banners.inactive');
    Route::post('banner/active', [BannerController::class, 'active'])->name('banners.active');
    Route::post('banner/destroy', [BannerController::class, 'destroy'])->name('banners.destroy');

    // contact route
    Route::get('page/manage', [CreatePageController::class, 'index'])->name('pages.index');
    Route::get('page/create', [CreatePageController::class, 'create'])->name('pages.create');
    Route::post('page/save', [CreatePageController::class, 'store'])->name('pages.store');
    Route::get('page/{id}/edit', [CreatePageController::class, 'edit'])->name('pages.edit');
    Route::post('page/update', [CreatePageController::class, 'update'])->name('pages.update');
    Route::post('page/inactive', [CreatePageController::class, 'inactive'])->name('pages.inactive');
    Route::post('page/active', [CreatePageController::class, 'active'])->name('pages.active');
    Route::post('page/destroy', [CreatePageController::class, 'destroy'])->name('pages.destroy');

    // Pos route
    Route::get('order/create', [OrderController::class, 'order_create'])->name('order.create');
    Route::post('order/store', [OrderController::class, 'order_store'])->name('order.store');
    Route::get('order/cart-add', [OrderController::class, 'cart_add'])->name('order.cart_add');
    Route::get('order/cart-content', [OrderController::class, 'cart_content'])->name('order.cart_content');
    Route::get('order/cart-increment', [OrderController::class, 'cart_increment'])->name('order.cart_increment');
    Route::get('order/cart-decrement', [OrderController::class, 'cart_decrement'])->name('order.cart_decrement');
    Route::get('order/cart-remove', [OrderController::class, 'cart_remove'])->name('order.cart_remove');
    Route::get('order/cart-product-discount', [OrderController::class, 'product_discount'])->name('order.product_discount');
    Route::get('order/cart-details', [OrderController::class, 'cart_details'])->name('order.cart_details');
    Route::get('order/cart-shipping', [OrderController::class, 'cart_shipping'])->name('order.cart_shipping');
    Route::get('order/cart-clear', [OrderController::class, 'cart_clear'])->name('order.cart_clear');
    Route::get('order/cart/update', [OrderController::class, 'cart_update'])->name('order.cart.update');
    Route::get('order/customer-lookup', [OrderController::class, 'customer_lookup'])->name('order.customer_lookup');

    // Order route
    Route::get('order/{slug}', [OrderController::class, 'index'])->name('orders');
    Route::get('order/edit/{invoice_id}', [OrderController::class, 'order_edit'])->name('order.edit');
    Route::post('order/update', [OrderController::class, 'order_update'])->name('order.update');
    Route::get('order/invoice/{invoice_id}', [OrderController::class, 'invoice'])->name('order.invoice');
    Route::get('order/invoice/{invoice_id}/pdf', [OrderController::class, 'invoicePdf'])->name('order.invoice.pdf');
    Route::get('order/process/{invoice_id}', [OrderController::class, 'process'])->name('order.process');
    Route::post('order/change', [OrderController::class, 'order_process'])->name('order_change');
    Route::post('order/destroy', [OrderController::class, 'destroy'])->name('order.destroy');
    Route::post('order-assign', [OrderController::class, 'order_assign'])->name('order.assign');
    Route::post('order-status', [OrderController::class, 'order_status'])->name('order.status');
    Route::delete('order-bulk-destroy', [OrderController::class, 'bulk_destroy'])->name('order.bulk_destroy');
    Route::get('order-print', [OrderController::class, 'order_print'])->name('order.order_print');
    Route::get('bulk-courier/{slug}', [OrderController::class, 'bulk_courier'])->name('bulk_courier');
    Route::get('stock-report', [OrderController::class, 'stock_report'])->name('stock_report');
    Route::get('order-report', [OrderController::class, 'order_report'])->name('order_report');
    Route::get('purchase-report', [ReportsController::class, 'purchaseReport'])->name('purchase_report');
    Route::get('order-pathao', [OrderController::class, 'order_pathao'])->name('order.pathao');
    Route::get('/pathao-city', [OrderController::class, 'pathaocity'])->name('pathaocity');
    Route::get('/pathao-zone', [OrderController::class, 'pathaozone'])->name('pathaozone');

    // Partial Orders routes
    Route::get('partial-orders', [AdminPartialOrderController::class, 'index'])->name('partial-orders.index');
    Route::get('partial-orders/{id}', [AdminPartialOrderController::class, 'show'])->name('partial-orders.show');
    Route::post('partial-orders/{id}/convert', [AdminPartialOrderController::class, 'convert'])->name('partial-orders.convert');
    Route::delete('partial-orders/{id}', [AdminPartialOrderController::class, 'delete'])->name('partial-orders.delete');
    Route::post('partial-orders/bulk-delete', [AdminPartialOrderController::class, 'bulkDelete'])->name('partial-orders.bulk-delete');

    // Order route
    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('review/pending', [ReviewController::class, 'pending'])->name('reviews.pending');
    Route::post('review/inactive', [ReviewController::class, 'inactive'])->name('reviews.inactive');
    Route::post('review/active', [ReviewController::class, 'active'])->name('reviews.active');
    Route::get('review/create', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('review/save', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('review/{id}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::post('review/update', [ReviewController::class, 'update'])->name('reviews.update');
    Route::post('review/destroy', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // flavor  route
    Route::get('shipping-charge/manage', [ShippingChargeController::class, 'index'])->name('shippingcharges.index');
    Route::get('shipping-charge/create', [ShippingChargeController::class, 'create'])->name('shippingcharges.create');
    Route::post('shipping-charge/save', [ShippingChargeController::class, 'store'])->name('shippingcharges.store');
    Route::get('shipping-charge/{id}/edit', [ShippingChargeController::class, 'edit'])->name('shippingcharges.edit');
    Route::post('shipping-charge/update', [ShippingChargeController::class, 'update'])->name('shippingcharges.update');
    Route::post('shipping-charge/inactive', [ShippingChargeController::class, 'inactive'])->name('shippingcharges.inactive');
    Route::post('shipping-charge/active', [ShippingChargeController::class, 'active'])->name('shippingcharges.active');
    Route::post('shipping-charge/destroy', [ShippingChargeController::class, 'destroy'])->name('shippingcharges.destroy');

    // ========== SHIPPING MANAGEMENT (New Engine) ==========
    Route::prefix('shipping')->name('shipping.')->group(function () {
        // Shipping Profiles
        Route::get('profiles', [\App\Http\Controllers\Admin\ShippingProfileController::class, 'index'])->name('profiles.index');
        Route::get('profiles/create', [\App\Http\Controllers\Admin\ShippingProfileController::class, 'create'])->name('profiles.create');
        Route::post('profiles/store', [\App\Http\Controllers\Admin\ShippingProfileController::class, 'store'])->name('profiles.store');
        Route::get('profiles/{id}/edit', [\App\Http\Controllers\Admin\ShippingProfileController::class, 'edit'])->name('profiles.edit');
        Route::post('profiles/{id}/update', [\App\Http\Controllers\Admin\ShippingProfileController::class, 'update'])->name('profiles.update');
        Route::delete('profiles/{id}', [\App\Http\Controllers\Admin\ShippingProfileController::class, 'destroy'])->name('profiles.destroy');

        // Shipping Zones
        Route::get('zones', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'index'])->name('zones.index');
        Route::get('zones/create', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'create'])->name('zones.create');
        Route::post('zones/store', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'store'])->name('zones.store');
        Route::get('zones/{id}/edit', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'edit'])->name('zones.edit');
        Route::post('zones/{id}/update', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'update'])->name('zones.update');
        Route::delete('zones/{id}', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'destroy'])->name('zones.destroy');
        Route::post('zones/{zone}/areas', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'syncAreas'])->name('zones.sync-areas');

        // Shipping Rates
        Route::get('rates', [\App\Http\Controllers\Admin\ShippingRateController::class, 'index'])->name('rates.index');
        Route::get('rates/create', [\App\Http\Controllers\Admin\ShippingRateController::class, 'create'])->name('rates.create');
        Route::post('rates/store', [\App\Http\Controllers\Admin\ShippingRateController::class, 'store'])->name('rates.store');
        Route::get('rates/{id}/edit', [\App\Http\Controllers\Admin\ShippingRateController::class, 'edit'])->name('rates.edit');
        Route::post('rates/{id}/update', [\App\Http\Controllers\Admin\ShippingRateController::class, 'update'])->name('rates.update');
        Route::delete('rates/{id}', [\App\Http\Controllers\Admin\ShippingRateController::class, 'destroy'])->name('rates.destroy');
    });

    // backend customer route
    Route::get('customer', [CustomerManageController::class, 'index'])->name('customers.index');
    Route::get('customer/manage', [CustomerManageController::class, 'index'])->name('customers.manage');
    Route::get('customer/{id}/edit', [CustomerManageController::class, 'edit'])->name('customers.edit');
    Route::post('customer/update', [CustomerManageController::class, 'update'])->name('customers.update');
    Route::post('customer/inactive', [CustomerManageController::class, 'inactive'])->name('customers.inactive');
    Route::post('customer/active', [CustomerManageController::class, 'active'])->name('customers.active');
    Route::get('customer/profile', [CustomerManageController::class, 'profile'])->name('customers.profile');
    Route::post('customer/adminlog', [CustomerManageController::class, 'adminlog'])->name('customers.adminlog');
    Route::get('customer/ip-block', [CustomerManageController::class, 'ip_block'])->name('customers.ip_block');
    Route::post('customer/ip-store', [CustomerManageController::class, 'ipblock_store'])->name('customers.ipblock.store');
    Route::post('customer/ip-update', [CustomerManageController::class, 'ipblock_update'])->name('customers.ipblock.update');
    Route::post('customer/ip-destroy', [CustomerManageController::class, 'ipblock_destroy'])->name('customers.ipblock.destroy');
    Route::get('customer/phone-block', [CustomerManageController::class, 'phone_block'])->name('customers.phone_block');
    Route::post('customer/phone-store', [CustomerManageController::class, 'phoneblock_store'])->name('customers.phoneblock.store');
    Route::post('customer/phone-update', [CustomerManageController::class, 'phoneblock_update'])->name('customers.phoneblock.update');
    Route::post('customer/phone-toggle', [CustomerManageController::class, 'phoneblock_toggle'])->name('customers.phoneblock.toggle');
    Route::post('customer/phone-destroy', [CustomerManageController::class, 'phoneblock_destroy'])->name('customers.phoneblock.destroy');

    // Warehouse Management Routes
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::get('/create', [WarehouseController::class, 'create'])->name('create');
        Route::post('/store', [WarehouseController::class, 'store'])->name('store');
        Route::get('/{id}', [WarehouseController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [WarehouseController::class, 'edit'])->name('edit');
        Route::match(['post', 'put'], '/{id}/update', [WarehouseController::class, 'update'])->name('update');
        Route::post('/{id}/destroy', [WarehouseController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/activate', [WarehouseController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [WarehouseController::class, 'deactivate'])->name('deactivate');
    });

    // ========== SIMPLIFIED INVENTORY MANAGEMENT ==========
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // 1. View Inventory (All stock across warehouses)
        Route::get('/', [InventoryController::class, 'index'])->name('index');

        // 2. Add Stock (Goods Receipt - GRN)
        Route::get('/add-stock', [InventoryController::class, 'addStock'])->name('add-stock');
        Route::post('/add-stock', [InventoryController::class, 'storeAddStock'])->name('store-add-stock');

        // 3. Adjust Stock (Fix Counting Errors)
        Route::get('/adjust-stock', [InventoryController::class, 'adjustStock'])->name('adjust-stock');
        Route::post('/adjust-stock', [InventoryController::class, 'storeAdjustStock'])->name('store-adjust-stock');

        // 4. Transfer Stock (Between Warehouses)
        Route::get('/transfer-stock', [InventoryController::class, 'transferStock'])->name('transfer-stock');
        Route::post('/transfer-stock', [InventoryController::class, 'storeTransferStock'])->name('store-transfer-stock');

        // 5. View History (Stock Movements / Audit Trail)
        Route::get('/history', [InventoryController::class, 'history'])->name('history');

        // API Endpoints
        Route::get('/api/product-stock', [InventoryController::class, 'getProductStock'])->name('get-product-stock');
        Route::get('/api/warehouse-products', [InventoryController::class, 'getWarehouseProducts'])->name('api.warehouse-products');
        Route::get('/api/product-variants', [InventoryController::class, 'getProductVariants'])->name('api.product-variants');
        Route::get('/api/search-products', [InventoryController::class, 'searchProductsForGrn'])->name('api.search-products');
        Route::post('/api/quick-adjust', [InventoryController::class, 'quickAdjust'])->name('quick-adjust');
    });

    // GRN (Goods Receipt Note) Routes
    Route::prefix('grn')->name('grn.')->group(function () {
        Route::get('/', [GrnController::class, 'index'])->name('index');
        Route::get('/api/search-products', [GrnController::class, 'searchProducts'])->name('api.search-products');
        Route::get('/create', [GrnController::class, 'create'])->name('create');
        Route::post('/store', [GrnController::class, 'store'])->name('store');
        Route::get('/{id}/data', [GrnController::class, 'data'])->whereNumber('id')->name('data');
        Route::get('/{id}', [GrnController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/edit', [GrnController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::match(['post', 'put'], '/{id}/update', [GrnController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [GrnController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::post('/{id}/approve', [GrnController::class, 'approve'])->whereNumber('id')->name('approve');
        Route::get('/{id}/print', [GrnController::class, 'print'])->whereNumber('id')->name('print');
    });

    // Warehouse Transfer Routes
    Route::prefix('transfer')->name('transfer.')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/create', [TransferController::class, 'create'])->name('create');
        Route::post('/store', [TransferController::class, 'store'])->name('store');
        Route::get('/warehouse-products', [TransferController::class, 'warehouseProducts'])->name('warehouse-products');
        Route::get('/{id}/edit', [TransferController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [TransferController::class, 'update'])->whereNumber('id')->name('update');
        Route::get('/{id}', [TransferController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/approve', [TransferController::class, 'approve'])->whereNumber('id')->name('approve');
        Route::post('/{id}/reject', [TransferController::class, 'reject'])->whereNumber('id')->name('reject');
        Route::post('/{id}/dispatch', [TransferController::class, 'dispatch'])->whereNumber('id')->name('dispatch');
        Route::post('/{id}/receive', [TransferController::class, 'receive'])->whereNumber('id')->name('receive');
        Route::post('/{id}/complete', [TransferController::class, 'complete'])->whereNumber('id')->name('complete');
        Route::post('/{id}/cancel', [TransferController::class, 'cancel'])->whereNumber('id')->name('cancel');
        Route::delete('/{id}', [TransferController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Warehouse Stock Routes
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [StockController::class, 'balance'])->name('index');
        Route::get('/inventory', [StockController::class, 'inventory'])->name('inventory');
        Route::get('/inventory-data', [StockController::class, 'getInventoryData'])->name('inventory-data');
        Route::get('/balance', [StockController::class, 'balance'])->name('balance');
        Route::get('/movements', [StockController::class, 'movements'])->name('movements');
        Route::get('/api/search-products', [StockController::class, 'searchProductsForMovements'])->name('api.search-products');
        Route::get('/alerts', [StockController::class, 'alerts'])->name('alerts');
        Route::get('/alerts/data', [StockController::class, 'getAlertsData'])->name('alerts.data');
        Route::post('/alerts/{id}/resolve', [StockController::class, 'resolveAlert'])->whereNumber('id')->name('alerts.resolve');
        Route::get('/dead-stock', [StockController::class, 'deadStock'])->name('dead-stock');
        Route::get('/audit', [StockController::class, 'audit'])->name('audit');
        Route::get('/set', [StockController::class, 'setForm'])->name('set');
        Route::get('/api/product-stock', [StockController::class, 'getProductStock'])->name('get-product-stock');
        Route::get('/api/product-variants', [StockController::class, 'getProductVariants'])->name('api.product-variants');
        Route::post('/quick-adjust', [StockController::class, 'quickAdjust'])->name('quick-adjust');
        Route::post('/bulk-adjust', [StockController::class, 'bulkAdjust'])->name('bulk-adjust');
        Route::get('/bulk-products', [StockController::class, 'bulkProducts'])->name('bulk-products');
        Route::get('/{warehouseId}/{productId}', [StockController::class, 'show'])
            ->whereNumber('warehouseId')
            ->whereNumber('productId')
            ->name('show');
        Route::get('/{warehouseId}/{productId}/edit', [StockController::class, 'edit'])
            ->whereNumber('warehouseId')
            ->whereNumber('productId')
            ->name('edit');
        Route::match(['post', 'put'], '/{warehouseId}/{productId}/update', [StockController::class, 'update'])
            ->whereNumber('warehouseId')
            ->whereNumber('productId')
            ->name('update');
    });

    // Stock Adjustment Management Routes
    Route::prefix('adjustment')->name('adjustment.')->group(function () {
        Route::get('/', [AdjustmentController::class, 'index'])->name('index');
        Route::get('/create', [AdjustmentController::class, 'create'])->name('create');
        Route::post('/store', [AdjustmentController::class, 'store'])->name('store');
        Route::get('/{id}', [AdjustmentController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/edit', [AdjustmentController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [AdjustmentController::class, 'update'])->whereNumber('id')->name('update');
        Route::post('/{id}/approve', [AdjustmentController::class, 'approve'])->whereNumber('id')->name('approve');
        Route::delete('/{id}', [AdjustmentController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Stock Loss Management Routes
    Route::prefix('loss')->name('loss.')->group(function () {
        Route::get('/', [AdjustmentController::class, 'lossIndex'])->name('index');
        Route::get('/create', [AdjustmentController::class, 'lossCreate'])->name('create');
        Route::post('/store', [AdjustmentController::class, 'lossStore'])->name('store');
        Route::get('/{id}', [AdjustmentController::class, 'lossShow'])->whereNumber('id')->name('show');
        Route::get('/{id}/edit', [AdjustmentController::class, 'lossEdit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [AdjustmentController::class, 'lossUpdate'])->whereNumber('id')->name('update');
        Route::post('/{id}/approve', [AdjustmentController::class, 'lossApprove'])->whereNumber('id')->name('approve');
        Route::delete('/{id}', [AdjustmentController::class, 'lossDestroy'])->whereNumber('id')->name('destroy');
    });

    // Supplier Management Routes
    Route::prefix('supplier')->name('supplier.')->group(function () {
        // Supplier CRUD
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/create', [SupplierController::class, 'create'])->name('create');
        Route::post('/store', [SupplierController::class, 'store'])->name('store');
        Route::get('/adjustments', [SupplierController::class, 'adjustmentsOverview'])->name('adjustments.index');
        Route::post('/adjustments/store', [SupplierController::class, 'storeAdjustment'])->name('adjustments.store');
        Route::get('/reports', [SupplierController::class, 'reports'])->name('reports');
        Route::get('/payments/overview', [SupplierController::class, 'paymentsOverview'])->name('payments.overview');
        Route::post('/payments/record', [SupplierController::class, 'storeOverviewPayment'])->name('payments.record');
        Route::get('/purchase-returns/overview', [SupplierController::class, 'purchaseReturnsOverview'])->name('purchase-returns.overview');
        Route::post('/purchase-returns/record', [SupplierController::class, 'storeOverviewPurchaseReturn'])->name('purchase-returns.record');
        Route::get('/{supplier}/data', [SupplierController::class, 'data'])->whereNumber('supplier')->name('data');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->whereNumber('supplier')->name('show');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->whereNumber('supplier')->name('edit');
        Route::post('/{supplier}/update', [SupplierController::class, 'update'])->whereNumber('supplier')->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->whereNumber('supplier')->name('destroy');

        // Opening Balance
        Route::post('/{supplier}/opening-balance', [SupplierController::class, 'setOpeningBalance'])->whereNumber('supplier')->name('opening-balance');

        // Ledger
        Route::get('/{supplier}/ledger', [SupplierController::class, 'ledger'])->whereNumber('supplier')->name('ledger');

        // Payments
        Route::get('/{supplier}/payments', [SupplierController::class, 'payments'])->whereNumber('supplier')->name('payments');
        Route::get('/{supplier}/payments/create', [SupplierController::class, 'createPayment'])->whereNumber('supplier')->name('payments.create');
        Route::post('/{supplier}/payments/store', [SupplierController::class, 'storePayment'])->whereNumber('supplier')->name('payments.store');

        // Purchase Returns
        Route::get('/{supplier}/purchase-returns', [SupplierController::class, 'purchaseReturns'])->whereNumber('supplier')->name('purchase-returns');
        Route::get('/{supplier}/purchase-returns/data', [SupplierController::class, 'purchaseReturnFormData'])->whereNumber('supplier')->name('purchase-returns.data');
        Route::get('/{supplier}/purchase-returns/create', [SupplierController::class, 'createPurchaseReturn'])->whereNumber('supplier')->name('purchase-returns.create');
        Route::post('/{supplier}/purchase-returns/store', [SupplierController::class, 'storePurchaseReturn'])->whereNumber('supplier')->name('purchase-returns.store');
        Route::post('/{supplier}/purchase-returns/{purchaseReturn}/approve', [SupplierController::class, 'approvePurchaseReturn'])->whereNumber('supplier')->whereNumber('purchaseReturn')->name('purchase-returns.approve');
        Route::post('/{supplier}/purchase-returns/{purchaseReturn}/complete', [SupplierController::class, 'completePurchaseReturn'])->whereNumber('supplier')->whereNumber('purchaseReturn')->name('purchase-returns.complete');
    });

    // ========== FINANCE MODULE ROUTES ==========
    Route::prefix('finance')->name('finance.')->middleware('permission:view-finance-module')->group(function () {
        // Purchase Returns Management
        Route::prefix('purchase-returns')->name('purchase-returns.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Finance\PurchaseReturnController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Finance\PurchaseReturnController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Finance\PurchaseReturnController::class, 'store'])->name('store');
            Route::get('/{purchaseReturn}', [\App\Http\Controllers\Finance\PurchaseReturnController::class, 'show'])->name('show');
            Route::get('/{purchaseReturn}/edit', [\App\Http\Controllers\Finance\PurchaseReturnController::class, 'edit'])->name('edit');
            Route::put('/{purchaseReturn}', [\App\Http\Controllers\Finance\PurchaseReturnController::class, 'update'])->name('update');
            Route::delete('/{purchaseReturn}', [\App\Http\Controllers\Finance\PurchaseReturnController::class, 'destroy'])->name('destroy');
            Route::post('/{purchaseReturn}/approve', [\App\Http\Controllers\Finance\PurchaseReturnController::class, 'approve'])->name('approve');
        });

        // Finance Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Finance\FinanceDashboardController::class, 'index'])->name('dashboard');

        // Profit & Loss Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/profit-loss', [\App\Http\Controllers\Finance\ProfitLossController::class, 'index'])->name('profit-loss');
            Route::get('/supplier-aging', [\App\Http\Controllers\Finance\SupplierAgingController::class, 'index'])->name('supplier-aging');
            Route::get('/customer-receivables', [\App\Http\Controllers\Finance\CustomerReceivableController::class, 'index'])->name('customer-receivables');
            Route::get('/cash-flow', [\App\Http\Controllers\Finance\CashFlowController::class, 'index'])->name('cash-flow');
        });
    });

    // Branch Management Routes
    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->name('index');
        Route::get('/create', [BranchController::class, 'create'])->name('create');
        Route::post('/store', [BranchController::class, 'store'])->name('store');
        Route::get('/{branch}/edit', [BranchController::class, 'edit'])->whereNumber('branch')->name('edit');
        Route::match(['post', 'put'], '/{branch}', [BranchController::class, 'update'])->whereNumber('branch')->name('update');
    });

    // ========== ACCOUNTS MODULE (Full Hierarchical Double-Entry) ==========
    Route::prefix('accounts')->name('accounts.')->middleware('permission:accounts-view|accounts-create|accounts-edit|accounts-delete|accounts-reports|accounts-approve|accounts-year-closing')->group(function () {
        // Chart of Accounts (Tree CRUD)
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::post('/store', [AccountController::class, 'store'])->name('store');
        Route::post('/destroy', [AccountController::class, 'destroy'])->name('destroy');
        Route::post('/getTree', [AccountController::class, 'getTree'])->name('getTree');
        Route::get('/getNewCode/{parentId}', [AccountController::class, 'getNewCode'])->whereNumber('parentId')->name('getNewCode');
        Route::get('/getHeadList', [AccountController::class, 'getHeadList'])->name('getHeadList');
        Route::post('/getSubsidiaryList', [AccountController::class, 'getSubsidiaryList'])->name('getSubsidiaryList');

        // Preserved legacy reports
        Route::get('/supplier-payables', [AccountController::class, 'supplierPayablesReport'])->name('supplier-payables');
        Route::get('/customer-receivables', [AccountController::class, 'customerReceivablesReport'])->name('customer-receivables');

        // Voucher Management
        Route::prefix('voucher')->name('voucher.')->group(function () {
            Route::get('/', [VoucherController::class, 'index'])->name('index');
            Route::get('/create', [VoucherController::class, 'create'])->name('create');
            Route::post('/store', [VoucherController::class, 'store'])->name('store');
            Route::get('/{id}/data', [VoucherController::class, 'data'])->whereNumber('id')->name('data');
            Route::get('/{id}', [VoucherController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{id}/edit', [VoucherController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('/destroy', [VoucherController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/approve', [VoucherController::class, 'approve'])->whereNumber('id')->name('approve');
            Route::post('/{id}/reject', [VoucherController::class, 'reject'])->whereNumber('id')->name('reject');
        });

        // Subsidiary Management
        Route::prefix('subsidiary')->name('subsidiary.')->group(function () {
            Route::get('/', [SubsidiaryController::class, 'index'])->name('index');
            Route::get('/create', [SubsidiaryController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [SubsidiaryController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('/store', [SubsidiaryController::class, 'store'])->name('store');
            Route::post('/destroy', [SubsidiaryController::class, 'destroy'])->name('destroy');
        });

        // Opening Balance
        Route::prefix('opening-balance')->name('opening-balance.')->group(function () {
            Route::get('/', [OpeningBalanceController::class, 'index'])->name('index');
            Route::post('/store', [OpeningBalanceController::class, 'store'])->name('store');
        });

        // Payment to Ledger Head Mapping
        Route::get('/settings', [AccountSettingsController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [AccountSettingsController::class, 'update'])->name('settings.update');
        Route::get('/payment-head-mappings', [PaymentHeadMappingController::class, 'index'])->name('payment-head-mappings.index');
        Route::post('/payment-head-mappings', [PaymentHeadMappingController::class, 'update'])->name('payment-head-mappings.update');

        // Fiscal Year
        Route::prefix('fiscal-year')->name('fiscal-year.')->group(function () {
            Route::get('/', [FiscalYearController::class, 'index'])->name('index');
            Route::get('/create', [FiscalYearController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [FiscalYearController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('/store', [FiscalYearController::class, 'store'])->name('store');
            Route::post('/destroy', [FiscalYearController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/close', [FiscalYearController::class, 'close'])->whereNumber('id')->name('close');
        });

        // Financial Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [AccountsReportController::class, 'index'])->name('index');
            Route::get('/ledger', [AccountsReportController::class, 'ledger'])->name('ledger');
            Route::get('/ledger-report', [AccountsReportController::class, 'ledgerReport'])->name('ledger-report');
            Route::get('/trial-balance', [AccountsReportController::class, 'trialBalance'])->name('trial-balance');
            Route::get('/trial-balance-report', [AccountsReportController::class, 'trialBalanceReport'])->name('trial-balance-report');
            Route::get('/balance-sheet', [AccountsReportController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('/balance-sheet-report', [AccountsReportController::class, 'balanceSheetReport'])->name('balance-sheet-report');
            Route::get('/income-statement', [AccountsReportController::class, 'incomeStatement'])->name('income-statement');
            Route::get('/income-statement-report', [AccountsReportController::class, 'incomeStatementReport'])->name('income-statement-report');
            Route::get('/cash-flow', [AccountsReportController::class, 'cashFlow'])->name('cash-flow');
            Route::get('/cash-flow-report', [AccountsReportController::class, 'cashFlowReport'])->name('cash-flow-report');
            Route::get('/top-sheet', [AccountsReportController::class, 'topSheet'])->name('top-sheet');
            Route::get('/top-sheet-report', [AccountsReportController::class, 'topSheetReport'])->name('top-sheet-report');
            Route::get('/voucher-statement', [AccountsReportController::class, 'voucherStatement'])->name('voucher-statement');
            Route::get('/voucher-statement-report', [AccountsReportController::class, 'voucherStatementReport'])->name('voucher-statement-report');
            Route::get('/reconciliation', [AccountsReportController::class, 'reconciliation'])->name('reconciliation');

            // Friendly aliases with standard accounting names
            Route::get('/general-ledger', [AccountsReportController::class, 'ledger'])->name('general-ledger');
            Route::get('/general-ledger-report', [AccountsReportController::class, 'ledgerReport'])->name('general-ledger-report');
            Route::get('/profit-loss', [AccountsReportController::class, 'incomeStatement'])->name('profit-loss');
            Route::get('/profit-loss-report', [AccountsReportController::class, 'incomeStatementReport'])->name('profit-loss-report');
            Route::get('/cash-flow-statement', [AccountsReportController::class, 'cashFlow'])->name('cash-flow-statement');
            Route::get('/cash-flow-statement-report', [AccountsReportController::class, 'cashFlowReport'])->name('cash-flow-statement-report');
            Route::get('/account-group-summary', [AccountsReportController::class, 'topSheet'])->name('account-group-summary');
            Route::get('/account-group-summary-report', [AccountsReportController::class, 'topSheetReport'])->name('account-group-summary-report');
            Route::get('/voucher-register', [AccountsReportController::class, 'voucherStatement'])->name('voucher-register');
            Route::get('/voucher-register-report', [AccountsReportController::class, 'voucherStatementReport'])->name('voucher-register-report');
        });
    });

    // Journal Entry Routes
    Route::prefix('journal')->name('journal.')->group(function () {
        Route::get('/', [JournalController::class, 'index'])->name('index');
        Route::get('/create', [JournalController::class, 'create'])->name('create');
        Route::post('/store', [JournalController::class, 'store'])->name('store');
        Route::get('/{journal}', [JournalController::class, 'show'])->whereNumber('journal')->name('show');
    });

    // Cash Account Routes
    Route::prefix('cash-accounts')->name('cash-accounts.')->group(function () {
        Route::get('/', [CashAccountController::class, 'index'])->name('index');
        Route::get('/create', [CashAccountController::class, 'create'])->name('create');
        Route::post('/store', [CashAccountController::class, 'store'])->name('store');
        Route::get('/{cashAccount}/edit', [CashAccountController::class, 'edit'])->whereNumber('cashAccount')->name('edit');
        Route::match(['post', 'put'], '/{cashAccount}', [CashAccountController::class, 'update'])->whereNumber('cashAccount')->name('update');
    });

    // Bank Account Routes
    Route::prefix('bank-accounts')->name('bank-accounts.')->group(function () {
        Route::get('/', [BankAccountController::class, 'index'])->name('index');
        Route::get('/create', [BankAccountController::class, 'create'])->name('create');
        Route::post('/store', [BankAccountController::class, 'store'])->name('store');
        Route::get('/{bankAccount}/edit', [BankAccountController::class, 'edit'])->whereNumber('bankAccount')->name('edit');
        Route::match(['post', 'put'], '/{bankAccount}', [BankAccountController::class, 'update'])->whereNumber('bankAccount')->name('update');
    });

    // Expense Management Routes
    Route::prefix('expense')->name('expense.')->group(function () {
        // Expense list & create
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/create', [ExpenseController::class, 'create'])->name('create');
        Route::post('/store', [ExpenseController::class, 'store'])->name('store');

        // Bulk operations
        Route::post('/bulk-approve', [ExpenseController::class, 'bulkApprove'])->name('bulk-approve');

        // Reports & analytics
        Route::get('/daily-summary', [ExpenseController::class, 'dailySummary'])->name('daily-summary');
        Route::get('/activity-log', [ExpenseController::class, 'activityLog'])->name('activity-log');
        Route::get('/export', [ExpenseController::class, 'export'])->name('export');

        // Expense CRUD
        Route::get('/{expense}', [ExpenseController::class, 'show'])->whereNumber('expense')->name('show');
        Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->whereNumber('expense')->name('edit');
        Route::put('/{expense}', [ExpenseController::class, 'update'])->whereNumber('expense')->name('update');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->whereNumber('expense')->name('destroy');

        // Approval workflow
        Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])->whereNumber('expense')->name('approve');
        Route::post('/{expense}/reject', [ExpenseController::class, 'reject'])->whereNumber('expense')->name('reject');
        Route::post('/{expense}/mark-paid', [ExpenseController::class, 'markAsPaid'])->whereNumber('expense')->name('mark-paid');
    });

    // Expense Categories Management
    Route::prefix('expense-categories')->name('expense-category.')->group(function () {
        Route::get('/', [ExpenseCategoryController::class, 'index'])->name('index');
        Route::get('/create', [ExpenseCategoryController::class, 'create'])->name('create');
        Route::post('/store', [ExpenseCategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [ExpenseCategoryController::class, 'edit'])->whereNumber('category')->name('edit');
        Route::put('/{category}', [ExpenseCategoryController::class, 'update'])->whereNumber('category')->name('update');
        Route::delete('/{category}', [ExpenseCategoryController::class, 'destroy'])->whereNumber('category')->name('delete');
    });

    // Profit & Loss Management Routes
    Route::prefix('profit-loss')->name('profit-loss.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [ProfitLossController::class, 'dashboard'])->name('dashboard');

        // Reports
        Route::get('/reports', [ProfitLossController::class, 'reports'])->name('reports');
        Route::get('/trends', [ProfitLossController::class, 'trends'])->name('trends');
        Route::get('/product-wise', [ProfitLossController::class, 'productWise'])->name('product-wise');
        Route::get('/warehouse-wise', [ProfitLossController::class, 'warehouseWise'])->name('warehouse-wise');
        Route::get('/inventory-valuation', [ProfitLossController::class, 'inventoryValuation'])->name('inventory-valuation');
        Route::get('/costing-comparison', [ProfitLossController::class, 'costingComparison'])->name('costing-comparison');
        Route::get('/export', [ProfitLossController::class, 'export'])->name('export');

        // Loss Entries Management
        Route::get('/losses', [ProfitLossController::class, 'losses'])->name('losses');
        Route::get('/losses/create', [ProfitLossController::class, 'createLoss'])->name('create-loss');
        Route::post('/losses/store', [ProfitLossController::class, 'storeLoss'])->name('store-loss');
        Route::get('/losses/{loss}', [ProfitLossController::class, 'showLoss'])->name('show-loss');
        Route::post('/losses/{loss}/approve', [ProfitLossController::class, 'approveLoss'])->name('approve-loss');
        Route::post('/losses/{loss}/reject', [ProfitLossController::class, 'rejectLoss'])->name('reject-loss');
        Route::post('/losses/{loss}/reject', [ProfitLossController::class, 'rejectLoss'])->name('reject-loss');
    });

    // Finance Analytical Reports & Dashboards
    Route::get('/finance/dashboard', [\App\Http\Controllers\Finance\FinanceDashboardController::class, 'index'])->name('finance.dashboard');
    Route::get('/finance/supplier-aging', [\App\Http\Controllers\Finance\SupplierAgingController::class, 'index'])->name('finance.supplier-aging');
    Route::get('/finance/customer-receivables', [\App\Http\Controllers\Finance\CustomerReceivableController::class, 'index'])->name('finance.customer-receivables');
    Route::prefix('cash-flow')->name('cash-flow.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Finance\CashFlowController::class, 'index'])->name('dashboard');
        Route::get('/export', [\App\Http\Controllers\Finance\CashFlowController::class, 'export'])->name('export');
    });

    // Return Management Routes
    Route::prefix('returns')->name('returns.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [ReturnController::class, 'dashboard'])->name('dashboard');

        // Analytics & Reporting
        Route::get('/analytics', [ReturnController::class, 'analytics'])->name('analytics');
        Route::get('/export', [ReturnController::class, 'export'])->name('export');

        // Returns CRUD
        Route::get('/', [ReturnController::class, 'index'])->name('index');
        Route::get('/create', [ReturnController::class, 'create'])->name('create');
        Route::post('/', [ReturnController::class, 'store'])->name('store');
        Route::get('/search-orders', [ReturnController::class, 'searchOrders'])->name('search-orders');
        Route::get('/{return}', [ReturnController::class, 'show'])->name('show');
        Route::get('/{return}/edit', [ReturnController::class, 'edit'])->name('edit');
        Route::put('/{return}', [ReturnController::class, 'update'])->name('update');

        // Return Actions
        Route::post('/{return}/approve', [ReturnController::class, 'approve'])->name('approve');
        Route::post('/{return}/reject', [ReturnController::class, 'reject'])->name('reject');
        Route::post('/{return}/process', [ReturnController::class, 'process'])->name('process');
        Route::post('/{return}/complete', [ReturnController::class, 'complete'])->name('complete');
        Route::post('/{return}/cancel', [ReturnController::class, 'cancel'])->name('cancel');

        // Bulk Operations
        Route::post('/bulk-action', [ReturnController::class, 'bulkAction'])->name('bulk-action');
    });

    // ========== REFACTORED REPORTING MODULE ROUTES ==========
    Route::prefix('reports-new')->name('reports-new.')->group(function () {
        // Sales Reports
        Route::get('/sales', [SalesReportController::class, 'index'])->name('sales');

        // Purchase Reports
        Route::get('/purchase', [PurchaseReportController::class, 'index'])->name('purchase');

        // Stock Reports
        Route::get('/stock', [StockReportController::class, 'index'])->name('stock');
        Route::get('/low-stock', [StockReportController::class, 'lowStock'])->name('low-stock');
        Route::get('/stock-movement', [StockMovementController::class, 'index'])->name('stock-movement');

        // Financial Reports
        Route::get('/expenses', [FinancialReportController::class, 'expenses'])->name('expenses');
        Route::get('/supplier-due', [FinancialReportController::class, 'supplierDue'])->name('supplier-due');
        Route::get('/customer-due', [FinancialReportController::class, 'customerDue'])->name('customer-due');
        Route::get('/returns', [FinancialReportController::class, 'returns'])->name('returns');

        // Performance Reports
        Route::get('/warehouse-pl', [PerformanceReportController::class, 'warehousePL'])->name('warehouse-pl');
        Route::get('/product-pl', [PerformanceReportController::class, 'productPL'])->name('product-pl');
        Route::get('/inventory-valuation', [PerformanceReportController::class, 'inventoryValuation'])->name('inventory-valuation');
        Route::get('/costing-comparison', [PerformanceReportController::class, 'costingComparison'])->name('costing-comparison');
    });

    // ========== REPORTS HUB (Requested dynamic reports) ==========
    Route::prefix('reports')->name('reports.')->group(function () {
        // Helpers / redirects
        Route::get('/inventory-summary', [ReportsHubController::class, 'inventorySummary'])->name('inventory-summary');

        // Daily report
        Route::get('/daily', [ReportsHubController::class, 'daily'])->name('daily');
        Route::get('/daily/print', [ReportsHubController::class, 'dailyPrint'])->name('daily.print');

        // Month-wise sales comparative
        Route::get('/month-wise-sales-comparative', [ReportsHubController::class, 'monthWiseSalesComparative'])->name('month-wise-sales-comparative');
        Route::get('/month-wise-sales-comparative/print', [ReportsHubController::class, 'monthWiseSalesComparativePrint'])->name('month-wise-sales-comparative.print');

        // Purchase Return Statement
        Route::get('/purchase-returns', [ReportsHubController::class, 'purchaseReturnStatement'])->name('purchase-returns');
        Route::get('/purchase-returns/print', [ReportsHubController::class, 'purchaseReturnStatementPrint'])->name('purchase-returns.print');

        // Supplier Ledger
        Route::get('/supplier-ledger', [ReportsHubController::class, 'supplierLedger'])->name('supplier-ledger');
        Route::get('/supplier-ledger/print', [ReportsHubController::class, 'supplierLedgerPrint'])->name('supplier-ledger.print');

        // Bill Payment Statement
        Route::get('/bill-payments', [ReportsHubController::class, 'billPaymentStatement'])->name('bill-payments');
        Route::get('/bill-payments/print', [ReportsHubController::class, 'billPaymentStatementPrint'])->name('bill-payments.print');

        // Sales Return Statement
        Route::get('/sales-returns', [ReportsHubController::class, 'salesReturnStatement'])->name('sales-returns');
        Route::get('/sales-returns/print', [ReportsHubController::class, 'salesReturnStatementPrint'])->name('sales-returns.print');

        // Damage Report
        Route::get('/damage', [ReportsHubController::class, 'damageReport'])->name('damage');
        Route::get('/damage/print', [ReportsHubController::class, 'damageReportPrint'])->name('damage.print');

        // Customer Ledger
        Route::get('/customer-ledger', [ReportsHubController::class, 'customerLedger'])->name('customer-ledger');
        Route::get('/customer-ledger/print', [ReportsHubController::class, 'customerLedgerPrint'])->name('customer-ledger.print');

        // Money Receipt
        Route::get('/money-receipt', [ReportsHubController::class, 'moneyReceipt'])->name('money-receipt');
        Route::get('/money-receipt/{payment}/print', [ReportsHubController::class, 'moneyReceiptPrint'])
            ->whereNumber('payment')
            ->name('money-receipt.print');
    });

});
