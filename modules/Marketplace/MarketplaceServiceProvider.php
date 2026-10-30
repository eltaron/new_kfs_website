<?php

namespace Modules\Marketplace;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Marketplace\Console\ReleaseExpiredReservations;
use Modules\Marketplace\Models\MarketCategory;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketPayout;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketReview;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Policies\MarketAdminOnlyPolicy;
use Modules\Marketplace\Policies\MarketOrderItemPolicy;
use Modules\Marketplace\Policies\MarketOrderPolicy;
use Modules\Marketplace\Policies\MarketPayoutPolicy;
use Modules\Marketplace\Policies\MarketProductPolicy;
use Modules\Marketplace\Policies\MarketVendorPolicy;

/**
 * متجر ذوي الهمم — multi-vendor marketplace for citizens with disabilities.
 *
 *   /store        public storefront (routes/web.php)
 *   /vendor       Filament panel for approved vendors
 *   /store-admin  Filament panel for marketplace admins
 */
class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/marketplace.php', 'marketplace');

        $this->app->register(Filament\VendorPanelProvider::class);
        $this->app->register(Filament\StoreAdminPanelProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'marketplace');

        // Payable alias for route('payments.pay', ['type' => 'market_order', ...]).
        Relation::morphMap([
            (string) config('marketplace.payable_alias', 'market_order') => MarketOrder::class,
        ]);

        Gate::policy(MarketVendor::class, MarketVendorPolicy::class);
        Gate::policy(MarketProduct::class, MarketProductPolicy::class);
        Gate::policy(MarketOrder::class, MarketOrderPolicy::class);
        Gate::policy(MarketOrderItem::class, MarketOrderItemPolicy::class);
        Gate::policy(MarketPayout::class, MarketPayoutPolicy::class);
        Gate::policy(MarketCategory::class, MarketAdminOnlyPolicy::class);
        Gate::policy(MarketReview::class, MarketAdminOnlyPolicy::class);

        View::composer('marketplace::*', function ($view) {
            $css = public_path('modules/marketplace/marketplace.css');
            $view->with('marketCssUrl', asset('modules/marketplace/marketplace.css').'?v='.(is_file($css) ? filemtime($css) : '1'));
        });

        if ($this->app->runningInConsole()) {
            $this->commands([ReleaseExpiredReservations::class]);
            $this->publishes([
                __DIR__.'/resources/assets' => public_path('modules/marketplace'),
            ], 'marketplace-assets');
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('market:release-reservations')->everyTenMinutes()->withoutOverlapping();
        });

        if (file_exists(__DIR__.'/routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/routes/web.php');
        }
        if (file_exists(__DIR__.'/routes/api.php')) {
            Route::prefix('api')->middleware('api')->group(__DIR__.'/routes/api.php');
        }
    }
}
