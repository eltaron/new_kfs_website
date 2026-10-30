<?php

namespace App\Providers\Filament;


use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Relations\Relation;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Support\Filament\KfsBranding;

class EstidamaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // KFS identity (docs/kfs-design-system.md) — gold primary for Estidama.
        return KfsBranding::apply($panel, primary: KfsBranding::GOLD)
            ->id('estidama_panal')
            ->path('estidama_panal')
            ->login()
            ->brandName('استدامة — KFS')
            ->icons([
                'provider' => FontAwesomeIconProvider::class,
            ])
            ->discoverResources(in: app_path('Filament/Estidama/Resources'), for: 'App\\Filament\\Estidama\\Resources')
            ->discoverPages(in: app_path('Filament/Estidama/Pages'), for: 'App\\Filament\\Estidama\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Estidama/Widgets'), for: 'App\\Filament\\Estidama\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
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
            ->plugins([
                FilamentShieldPlugin::make()
            ])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'إدارة التدريب',
                'المحتوى التعليمي',
                'إدارة الامتحانات',
                'التقارير والتحليلات',
                'إعدادات التدريب',
                'إدارة المحتوى',
            ])
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])

            ->databaseNotifications()
            ->profile();
    }

    public function boot(): void
    {
        // Paid program enrollments go through the unified payment service:
        // route('payments.pay', ['type' => 'program_enrollment', 'id' => ...]).
        Relation::morphMap(['program_enrollment' => \Modules\Estidama\Models\Enrollment::class]);
    }
}
