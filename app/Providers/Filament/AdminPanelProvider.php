<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Dashboard as CustomDashboard;
use App\Support\Filament\KfsBranding;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return KfsBranding::apply($panel)
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('محافظة كفر الشيخ — لوحة الإدارة')
            ->icons([
                'provider' => FontAwesomeIconProvider::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')

           ->pages([
                CustomDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // \App\Filament\Widgets\WelcomeWidget::class,
                // \App\Filament\Widgets\MainStatsOverview::class,
                // // \App\Filament\Widgets\FinancialReportStats::class,
                // \App\Filament\Widgets\ServicesChart::class,
                // \App\Filament\Widgets\RecentActivityWidget::class,
                // \App\Filament\Widgets\ComplaintsMonitorWidget::class,
                // \App\Filament\Widgets\FulfillmentWidget::class,
                // \App\Filament\Widgets\StatsOverview::class,
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // Security: Google Authenticator 2FA gate + setup page (see config/security.php)
            ->authMiddleware([\App\Http\Middleware\EnsureTwoFactorIsVerified::class . ':panel'], isPersistent: true)
            ->pages([\App\Filament\Security\Pages\TwoFactorAuthentication::class])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('المصادقة الثنائية')
                    ->icon('heroicon-o-shield-check')
                    ->url(fn (): string => \App\Filament\Security\Pages\TwoFactorAuthentication::getUrl()),
            ])

            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->profile()
            ->homeUrl('/admin');
    }
}
