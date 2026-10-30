<?php

namespace Modules\Syndicate\Filament\Panels;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use App\Support\Filament\KfsBranding;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Syndicate\Filament\Resources\CertificateResource;
use Modules\Syndicate\Filament\Resources\EngineerResource;
use Modules\Syndicate\Filament\Resources\ProjectResource;
use Modules\Syndicate\Filament\Resources\RequestResource;
use Modules\Syndicate\Filament\Widgets\SupervisionStatsWidget;
use Modules\Syndicate\Filament\Widgets\SuspendedProjectsWidget;

/** إعداد مشترك للوحتي النقابة ومجلس المدينة (نفس طبقات الأمان المستخدمة في لوحة GIS). */
abstract class BasePanelProvider extends PanelProvider
{
    protected function configure(Panel $panel, string $id, string $brand, string $primary = KfsBranding::CYAN): Panel
    {
        $panel = KfsBranding::apply($panel, primary: $primary)
            ->id($id)
            ->path($id)
            ->login()
            ->brandName($brand)
            ->resources(array_values(array_filter([
                ProjectResource::class,
                RequestResource::class,
                CertificateResource::class,
                $id === 'syndicate' ? EngineerResource::class : null,
            ])))
            ->pages([Dashboard::class])
            ->widgets([
                SupervisionStatsWidget::class,
                SuspendedProjectsWidget::class,
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
            ->authMiddleware([Authenticate::class])
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->darkMode(true);

        // نفس بوابة المصادقة الثنائية المطبقة على باقي اللوحات (إن كانت متاحة).
        if (class_exists(\App\Http\Middleware\EnsureTwoFactorIsVerified::class)) {
            $panel->authMiddleware([\App\Http\Middleware\EnsureTwoFactorIsVerified::class . ':panel'], isPersistent: true);
        }
        if (class_exists(\App\Filament\Security\Pages\TwoFactorAuthentication::class)) {
            $panel->pages([\App\Filament\Security\Pages\TwoFactorAuthentication::class])
                ->userMenuItems([
                    MenuItem::make()
                        ->label('المصادقة الثنائية')
                        ->icon('heroicon-o-shield-check')
                        ->url(fn (): string => \App\Filament\Security\Pages\TwoFactorAuthentication::getUrl()),
                ]);
        }

        return $panel
            ->renderHook('panels::page.start', fn (): string => view('syndicate::filament.suspended-alert')->render())
            ->renderHook('panels::user-menu.before', fn (): string => '<a href="' . e(route('syndicate.verify.form')) . '" target="_blank" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:underline px-2">التحقق من شهادة</a>');
    }
}
