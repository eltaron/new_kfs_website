<?php

namespace Modules\Marketplace\Filament;

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use App\Support\Filament\KfsBranding;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Marketplace\Filament\Admin\Pages\MarketDashboard;
use Modules\Marketplace\Filament\Admin\Pages\MarketReports;
use Modules\Marketplace\Filament\Admin\Pages\MarketSettingsPage;
use Modules\Marketplace\Filament\Admin\Resources\CategoryResource;
use Modules\Marketplace\Filament\Admin\Resources\OrderResource;
use Modules\Marketplace\Filament\Admin\Resources\PayoutResource;
use Modules\Marketplace\Filament\Admin\Resources\ProductResource;
use Modules\Marketplace\Filament\Admin\Resources\ReviewResource;
use Modules\Marketplace\Filament\Admin\Resources\VendorResource;
use Modules\Marketplace\Filament\Admin\Widgets\MarketStatsWidget;
use Modules\Marketplace\Http\Middleware\AuthenticateMarketplacePanel;

/**
 * /store-admin — إدارة متجر ذوي الهمم (super_admin / Admin / مشرف المتجر).
 */
class StoreAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return KfsBranding::apply($panel)
            ->id('store-admin')
            ->path('store-admin')
            ->brandName('إدارة متجر ذوي الهمم')
            ->resources([
                VendorResource::class,
                ProductResource::class,
                CategoryResource::class,
                OrderResource::class,
                PayoutResource::class,
                ReviewResource::class,
            ])
            ->pages([
                MarketDashboard::class,
                MarketReports::class,
                MarketSettingsPage::class,
                \App\Filament\Security\Pages\TwoFactorAuthentication::class,
            ])
            ->widgets([
                MarketStatsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateMarketplacePanel::class,
            ])
            ->authMiddleware([\App\Http\Middleware\EnsureTwoFactorIsVerified::class.':panel'], isPersistent: true)
            ->userMenuItems([
                MenuItem::make()->label('زيارة المتجر')->icon('heroicon-o-building-storefront')
                    ->url(fn (): string => route('market.home')),
                MenuItem::make()->label('المصادقة الثنائية')->icon('heroicon-o-shield-check')
                    ->url(fn (): string => \App\Filament\Security\Pages\TwoFactorAuthentication::getUrl()),
            ])
            ->navigationGroups(['البائعون والمنتجات', 'الطلبات والمالية', 'الإعدادات'])
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(true)
            ->renderHook('panels::head.end', fn (): string => '<style>'.VendorPanelProvider::accessibilityCss().'</style>');
    }
}
