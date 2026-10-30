<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.welcome-widget';
    protected int | string | array $columnSpan = 'full'; 
    // إخفاء الـ widget عن الـ super_admin
    public static function canView(): bool
    {
        return auth()->check() && !auth()->user()->hasRole('super_admin');
    }
    
    // الحصول على بيانات الترحيب حسب الدور
    protected function getWelcomeData(): array
    {
        $user = Auth::user();
        $role = $user->roles->first()?->name ?? 'default';
        
        $welcomeMap = [
            'مسئول اعلام' => [
                'title' => 'أهلاً بك يا مسئول الإعلام 📢',
                'message' => 'من هنا يمكنك إدارة المحتوى الإعلامي ونشر الأخبار ومتابعة الفعاليات.',
                'color' => 'warning',
                'icon' => 'heroicon-o-megaphone',
                'quickLinks' => [
                    ['label' => 'الأحداث والفعاليات', 'url' => '/admin/events', 'icon' => 'heroicon-o-calendar'],
                    ['label' => 'الأخبار والمقالات', 'url' => '/admin/posts', 'icon' => 'heroicon-o-newspaper'],
                ],
            ],
            
            'investment_officer' => [
                'title' => 'أهلاً بك يا مسئول الاستثمار 💼',
                'message' => 'منصتك لمتابعة فرص الاستثمار ودراسات الجدوى.',
                'color' => 'success',
                'icon' => 'heroicon-o-briefcase',
                'quickLinks' => [
                    ['label' => 'مشاريع الاستثمار', 'url' => '/admin/investment-projects', 'icon' => 'heroicon-o-chart-bar'],
                    ['label' => 'المستثمرين', 'url' => '/admin/investors', 'icon' => 'heroicon-o-users'],
                ],
            ],
            
            'tourism_officer' => [
                'title' => 'أهلاً بك يا مسئول السياحة 🏖️',
                'message' => 'بوابة تطوير المناطق السياحية والترويج للمحافظة.',
                'color' => 'info',
                'icon' => 'heroicon-o-map',
                'quickLinks' => [
                    ['label' => 'المعالم السياحية', 'url' => '/admin/tourism-landmarks', 'icon' => 'heroicon-o-map-pin'],
                    ['label' => 'الفعاليات', 'url' => '/admin/tourism-events', 'icon' => 'heroicon-o-ticket'],
                ],
            ],
            
            'field_member' => [
                'title' => 'أهلاً بك يا عضو ميداني 🚶♂️',
                'message' => 'أداتك الميدانية لرفع التقارير والتقاط الإحداثيات.',
                'color' => 'primary',
                'icon' => 'heroicon-o-location-marker',
                'quickLinks' => [
                    ['label' => 'التفتيش الميداني', 'url' => '/admin/field-inspections', 'icon' => 'heroicon-o-clipboard-check'],
                    ['label' => 'الخرائط', 'url' => '/gis', 'icon' => 'heroicon-o-globe'],
                ],
            ],
            
            'statistics_manager' => [
                'title' => 'أهلاً بك يا مدير الإحصاء 📊',
                'message' => 'مركزك لإعداد التقارير الإحصائية وتحليل البيانات.',
                'color' => 'primary',
                'icon' => 'heroicon-o-chart-bar',
                'quickLinks' => [
                    ['label' => 'التقارير', 'url' => '/admin/reports', 'icon' => 'heroicon-o-document-report'],
                    ['label' => 'الإحصائيات', 'url' => '/admin/statistics', 'icon' => 'heroicon-o-presentation-chart'],
                ],
            ],
            
            'default' => [
                'title' => 'أهلاً بك في نظام كفر الشيخ الرقمية 🌟',
                'message' => 'من هنا يمكنك الوصول للخدمات المخصصة لدورك الوظيفي.',
                'color' => 'primary',
                'icon' => 'heroicon-o-home',
                'quickLinks' => [
                    ['label' => 'ملفي الشخصي', 'url' => '/admin/profile', 'icon' => 'heroicon-o-user-circle'],
                    ['label' => 'المساعدة', 'url' => '/admin/help-page', 'icon' => 'heroicon-o-question-mark-circle'],
                ],
            ],
        ];
        
        return $welcomeMap[$role] ?? $welcomeMap['default'];
    }
    
    // ✅ دي الطريقة الصح لتمرير البيانات للـ view
    protected function getViewData(): array
    {
        return [
            'welcomeData' => $this->getWelcomeData(),
        ];
    }
}