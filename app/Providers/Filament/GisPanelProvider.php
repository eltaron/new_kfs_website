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
                // لوحة GIS المخصصة (ودجات حسب الدور) بدل لوحة Filament الافتراضية
                \App\Filament\Gis\Pages\Dashboard::class,
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
            // ترتيب مجموعات الشريط الجانبي: حوكمة القرارات أولاً
            ->navigationGroups([
                'حوكمة قرارات الإزالة',
                'البيانات المرجعية',
                'الخدمات المكانية والمساحية',
                'منظومة إدارة الخدمات',
                'إعدادات النظام والأمان',
                'إدارة الوصول',
            ])
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
            // 6b. هوية لوحة GIS: لوحة مسار القرارات + تبويبات المراحل كمسار مرقّم + جدول أوضح
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="stylesheet" href="' . asset('css/kfs-gis-panel.css') . '?v=' . @filemtime(public_path('css/kfs-gis-panel.css')) . '">'
            )

            // 7. بطاقة المستخدم أعلى الشريط الجانبي (الاسم + الدور + المركز)
            ->renderHook(
                'panels::sidebar.nav.start',
                fn (): string => Blade::render('
                    @php
                        $user = auth()->user();
                        $role = $user?->roles->pluck("name")->reject(fn ($r) => $r === "super_admin")->first()
                            ?? ($user?->hasRole("super_admin") ? "مدير النظام" : null);
                        $initial = mb_substr(trim(preg_replace("/^[^\/]*\//u", "", (string) $user?->name)), 0, 1);
                    @endphp
                    @if ($user)
                        <div class="kgs-user">
                            <div class="kgs-user__card" x-show="$store.sidebar.isOpen">
                                <span class="kgs-user__avatar">{{ $initial }}</span>
                                <span class="kgs-user__body">
                                    <strong class="kgs-user__name">{{ $user->name }}</strong>
                                    @if ($role)<span class="kgs-user__role">{{ $role }}</span>@endif
                                </span>
                            </div>
                            <div class="kgs-user__meta" x-show="$store.sidebar.isOpen">
                                <span><x-filament::icon icon="heroicon-m-map-pin" class="kgs-i" /> {{ $user->center ?: "كل المراكز" }}</span>
                            </div>
                            <div class="kgs-user__mini" x-show="! $store.sidebar.isOpen" title="{{ $user->name }}">
                                <span class="kgs-user__avatar">{{ $initial }}</span>
                            </div>
                        </div>
                    @endif
                ')
            )
            // 7b. تذييل الشريط الجانبي
            ->renderHook(
                'panels::sidebar.footer',
                fn (): string => Blade::render('
                    <div class="kgs-foot" x-show="$store.sidebar.isOpen">
                        <span class="kgs-foot__mark"></span>
                        <span>منظومة حوكمة قرارات الإزالة<br><small>محافظة كفر الشيخ</small></span>
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