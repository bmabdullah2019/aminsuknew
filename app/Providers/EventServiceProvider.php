<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\OrderPlaced::class => [
            \App\Listeners\ReserveStockOnOrderPlaced::class,
        ],
        \App\Events\PurchaseOrderReceived::class => [
            \App\Listeners\PostSupplierLedgerOnReceive::class,
        ],
        \App\Events\PurchaseReturnApproved::class => [
            \App\Listeners\UpdateInventoryOnPurchaseReturnApproved::class,
            \App\Listeners\UpdateSupplierAgingOnPurchaseReturnApproved::class,
            \App\Listeners\UpdateDashboardOnPurchaseReturnApproved::class,
        ],
        \App\Events\Accounts\AccountHeadUpdated::class => [
            \App\Listeners\Accounts\RebuildAccountTree::class,
        ],
        \App\Events\OrderStatusUpdated::class => [
            \App\Listeners\ProcessOrderPurchaseTracking::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
