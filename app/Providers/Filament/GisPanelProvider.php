<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use App\Support\Filament\KfsBranding;

class GisPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return KfsBranding::apply($panel)
            ->id('gis')
            ->path('gis')
            ->login()
            ->homeUrl('/gis')
            ->brandName('بوابة كفر الشيخ الجيومكانية')
            // 1-2. الألوان والخطوط والشعار: هوية KFS الموحدة (App\Support\Filament\KfsBranding)
            ->discoverResources(in: app_path('Filament/Gis/Resources'), for: 'App\\Filament\\Gis\\Resources')
            ->resources([
                \App\Filament\Gis\Resources\RoleResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Gis/Pages'), for: 'App\\Filament\\Gis\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Gis\Pages\RemovalReportsPage::class,
            ])

            // 3. تسجيل الودجات (إحصائيات الإنجاز والخرائط)
            ->discoverWidgets(in: app_path('Filament/Gis/Widgets'), for: 'App\\Filament\\Gis\\Widgets')
            ->widgets([
                \App\Filament\Gis\Widgets\GisWelcomeWidget::class,
                \App\Filament\Gis\Widgets\GisPerformanceWidget::class,
                \App\Filament\Gis\Widgets\RemovalStatsWidget::class,
                \App\Filament\Gis\Widgets\ViolationChart::class,
                \App\Filament\Gis\Widgets\GisStatsOverview::class,
                \App\Filament\Gis\Widgets\RequestsStatusChart::class,
            ])


            // 5. إعدادات الأمان والبحث والواجهة
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
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->profile(\App\Filament\Gis\Pages\GisEditProfile::class)
            ->darkMode(true) // دعم الوضع الليلي والنهاري تلقائياً
            ->plugins([
                FilamentShieldPlugin::make()
            ])

            // 6. حقن تصميم الـ Tabs المخصص (مربعات كبيرة + أيقونات + عداد بارز)
            ->renderHook(
                'panels::head.end',
                fn (): string => '<style>' . file_get_contents(resource_path('css/gis-tabs.css')) . '</style>'
            )

            // 7. إضافة معلومات المستخدم في أعلى الشريط الجانبي
            ->renderHook(
                'panels::sidebar.nav.start',
                fn (): string => Blade::render('
                    @php $user = auth()->user(); @endphp
                    <div class="fi-sidebar-user-info border-b border-gray-200 dark:border-gray-700 px-4 py-4">
                        {{-- expanded state --}}
                        <div x-show="$store.sidebar.isOpen"
                             x-transition:enter="lg:transition lg:delay-100"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-600 dark:bg-primary-500 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate leading-tight">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate leading-tight mt-0.5">{{ $user->email }}</p>
                                </div>
                            </div>
                        </div>
                        {{-- collapsed state --}}
                        <div x-show="!$store.sidebar.isOpen"
                             class="flex justify-center">
                            <div class="w-9 h-9 rounded-full bg-primary-600 dark:bg-primary-500 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                        </div>
                    </div>
                ')
            )

            // 8. حقن كود الجافا سكريبت الخاص بالـ GPS (هام جداً لقرارات الإزالة الميدانية)
            ->renderHook(
                'panels::body.end',
                fn (): string => '
                    <script>
                        function getUserLocation() {
                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(function(position) {
                                    // ملء الحقول في نموذج Filament
                                    const latInput = document.getElementById("lat_input");
                                    const lngInput = document.getElementById("lng_input");

                                    if (latInput && lngInput) {
                                        latInput.value = position.coords.latitude;
                                        lngInput.value = position.coords.longitude;

                                        // تنبيه المواطن/الموظف بالنجاح
                                        alert("تم التقاط إحداثيات الموقع الحالية بنجاح");

                                        // إرسال حدث لتحديث البيانات في Alpine.js إذا لزم الأمر
                                        latInput.dispatchEvent(new Event("input"));
                                        lngInput.dispatchEvent(new Event("input"));
                                    }
                                }, function(error) {
                                    alert("خطأ: يرجى تفعيل الـ GPS والسماح للمتصفح بالوصول لموقعك.");
                                }, { enableHighAccuracy: true });
                            } else {
                                alert("عفراً، متصفحك لا يدعم خاصية تحديد الموقع.");
                            }
                        }
                    </script>
                '
            );
    }
}