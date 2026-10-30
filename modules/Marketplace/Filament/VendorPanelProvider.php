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
use Modules\Marketplace\Filament\Vendor\Pages\StoreProfile;
use Modules\Marketplace\Filament\Vendor\Pages\VendorDashboard;
use Modules\Marketplace\Filament\Vendor\Resources\OrderItemResource;
use Modules\Marketplace\Filament\Vendor\Resources\PayoutResource;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource;
use Modules\Marketplace\Filament\Vendor\Widgets\VendorStatsWidget;
use Modules\Marketplace\Http\Middleware\AuthenticateMarketplacePanel;

/**
 * /vendor — لوحة البائع (approved marketplace vendors only).
 */
class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return KfsBranding::apply($panel)
            ->id('vendor')
            ->path('vendor')
            ->brandName('لوحة البائع — متجر ذوي الهمم')
            ->resources([
                ProductResource::class,
                OrderItemResource::class,
                PayoutResource::class,
            ])
            ->pages([
                VendorDashboard::class,
                StoreProfile::class,
            ])
            ->widgets([
                VendorStatsWidget::class,
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
                MenuItem::make()->label('صفحة متجري')->icon('heroicon-o-eye')
                    ->url(fn (): string => ($v = \Modules\Marketplace\Support\MarketplaceAccess::currentVendor()) ? route('market.vendors.show', $v->slug) : route('market.home')),
            ])
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->renderHook('panels::head.end', fn (): string => '<style>'.self::accessibilityCss().'</style>');
    }

    /** Larger touch targets & strong focus rings in both marketplace panels. */
    public static function accessibilityCss(): string
    {
        return <<<'CSS'
            .fi-btn, .fi-icon-btn, .fi-sidebar-item-button, .fi-ta-actions a, .fi-ta-actions button { min-height: 44px; }
            .fi-input, .fi-select-input { min-height: 44px; font-size: 1rem; }
            :is(a, button, input, select, textarea, [tabindex]):focus-visible { outline: 3px solid #D8B03E !important; outline-offset: 2px; }
            body { font-size: 1.02rem; }
        CSS;
    }
}
