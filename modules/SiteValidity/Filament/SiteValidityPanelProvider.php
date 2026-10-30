<?php

namespace Modules\SiteValidity\Filament;

use App\Support\Filament\KfsBranding;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * لوحة خدمة «طلب صلاحية الموقع»: /site-validity
 * الأدوار: site_validity_admin · geo_center · planning_engineer · super_admin (User::canAccessPanel).
 */
class SiteValidityPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = KfsBranding::apply($panel, primary: KfsBranding::OLIVE)
            ->id('site-validity')
            ->path('site-validity')
            ->login()
            ->brandName('صلاحية الموقع — محافظة كفر الشيخ')
            ->resources([
                Resources\RequestResource::class,
                Resources\ServiceTypeResource::class,
                Resources\InspectionFieldResource::class,
                Resources\AttachmentTypeResource::class,
                Resources\StaffUserResource::class,
            ])
            ->pages([
                Dashboard::class,
                Pages\RequestsMap::class,
                Pages\AppointmentsCalendar::class,
                Pages\InspectionDaysPage::class,
                Pages\SettingsPage::class,
            ])
            ->widgets([
                Widgets\SvStatsOverview::class,
                Widgets\UpcomingAppointments::class,
                Widgets\StatusChart::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('الطلبات'),
                NavigationGroup::make('إعدادات الخدمة')->collapsed(false),
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
            // نفس بوابة المصادقة الثنائية (Google Authenticator) المطبقة على باقي اللوحات.
            ->authMiddleware([\App\Http\Middleware\EnsureTwoFactorIsVerified::class . ':panel'], isPersistent: true)
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->darkMode(true);

        if (class_exists(\App\Filament\Security\Pages\TwoFactorAuthentication::class)) {
            $panel->pages([\App\Filament\Security\Pages\TwoFactorAuthentication::class])
                ->userMenuItems([
                    MenuItem::make()
                        ->label('المصادقة الثنائية')
                        ->icon('heroicon-o-shield-check')
                        ->url(fn (): string => \App\Filament\Security\Pages\TwoFactorAuthentication::getUrl()),
                ]);
        }

        $panel->renderHook(PanelsRenderHook::HEAD_END, function (): string {
            $v = fn (string $path) => e(asset($path) . '?v=' . (is_file(public_path($path)) ? filemtime(public_path($path)) : '1'));

            // تبويبات الطلبات + الخرائط (خريطة الطلبات ومنتقي موقع الأرض — يلزم تحميلها مسبقاً لأن النوافذ المنبثقة لا تنفذ السكربتات)
            return '<link rel="stylesheet" href="' . $v('css/sv-panel.css') . '">'
                . '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">'
                . '<link rel="stylesheet" href="' . $v('css/sv-location-picker.css') . '">'
                . '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>'
                . '<script src="' . $v('js/sv-location-picker.js') . '" defer></script>';
        });

        return $panel->renderHook('panels::user-menu.before', fn (): string => '<a href="' . e(route('site-validity.landing')) . '" target="_blank" rel="noopener" class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:underline px-2">صفحة الخدمة للمواطن</a>');
    }
}
