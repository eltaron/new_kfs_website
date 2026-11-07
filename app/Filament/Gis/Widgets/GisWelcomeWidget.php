<?php

namespace App\Filament\Gis\Widgets;

use Filament\Widgets\Widget;

class GisWelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.gis-welcome-widget';
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        $dashboardRoles = [
            'super_admin',
            'Admin',
            'مدير المركز',
            'رؤوساء الاقسام',
        ];
        return !$user->hasAnyRole($dashboardRoles);
    }

    protected function getWelcomeData(): array
    {
        $user = auth()->user();
        $role = $user->roles->first()?->name ?? 'default';

        $welcomeMap = [
            'فني التنظيم' => [
                'title' => 'أهلاً بك يا فني التنظيم 🛠️',
                'message' => 'من هنا يمكنك إضافة قرارات الإزالة الجديدة ومتابعة حالة الطلبات.',
                'color' => 'warning',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'quickLinks' => [
                    ['label' => 'إضافة قرار إزالة', 'url' => '/gis/add-removal-order', 'icon' => 'heroicon-o-plus-circle'],
                    ['label' => 'قرارات الإزالة الخاصة بي', 'url' => '/gis/my-removal-orders', 'icon' => 'heroicon-o-document-text'],
                ],
            ],
            'العضو الميداني' => [
                'title' => 'أهلاً بك أيها العضو الميداني 🏗️',
                'message' => 'من هنا يمكنك إضافة قرارات إزالة جديدة ومتابعة قراراتك ورفع المحاضر.',
                'color' => 'success',
                'icon' => 'heroicon-o-eye',
                'quickLinks' => [
                    ['label' => 'إضافة قرار إزالة جديد', 'url' => '/gis/add-removal-order', 'icon' => 'heroicon-o-plus-circle'],
                    ['label' => 'قرارات الإزالة', 'url' => '/gis/my-removal-orders', 'icon' => 'heroicon-o-document-text'],
                ],
            ],
            'مهندس التنظيم' => [
                'title' => 'أهلاً بك يا مهندس التنظيم 📐',
                'message' => 'بوابة متابعة الطلبات الهندسية واعتماد المعاينات الميدانية.',
                'color' => 'info',
                'icon' => 'heroicon-o-pencil-square',
                'quickLinks' => [
                    ['label' => 'جملة الطلبات', 'url' => '/gis/removal-orders', 'icon' => 'heroicon-o-document-magnifying-glass'],
                ],
            ],
            'مدير التنظيم' => [
                'title' => 'أهلاً بك يا مدير التنظيم 📋',
                'message' => 'نظام متابعة الأداء وإدارة سير العمل في قسم التنظيم.',
                'color' => 'primary',
                'icon' => 'heroicon-o-clipboard-document-check',
                'quickLinks' => [
                    ['label' => 'جملة الطلبات', 'url' => '/gis/removal-orders', 'icon' => 'heroicon-o-document-magnifying-glass'],
                ],
            ],
            'مدير المتغيرات' => [
                'title' => 'أهلاً بك يا مدير المتغيرات 🗺️',
                'message' => 'إدارة البعد المكاني لقرارات الإزالة وتعيين أعضاء المتغيرات.',
                'color' => 'info',
                'icon' => 'heroicon-o-map',
                'quickLinks' => [
                    ['label' => 'سير العمل', 'url' => '/gis/workflow-dashboard', 'icon' => 'heroicon-o-arrow-path'],
                ],
            ],
            'عضو المتغيرات' => [
                'title' => 'أهلاً بك يا عضو المتغيرات 📍',
                'message' => 'إضافة البعد المكاني للقرارات المحالة إليك.',
                'color' => 'success',
                'icon' => 'heroicon-o-map-pin',
                'quickLinks' => [
                    ['label' => 'سير العمل', 'url' => '/gis/workflow-dashboard', 'icon' => 'heroicon-o-arrow-path'],
                ],
            ],
            'أخصائي النظم' => [
                'title' => 'أهلاً بك يا أخصائي النظم 💻',
                'message' => 'رفع ملفات PDF للقرارات المكتملة مكانياً.',
                'color' => 'primary',
                'icon' => 'heroicon-o-document-arrow-up',
                'quickLinks' => [
                    ['label' => 'سير العمل', 'url' => '/gis/workflow-dashboard', 'icon' => 'heroicon-o-arrow-path'],
                ],
            ],
            'مكتب المحافظ' => [
                'title' => 'أهلاً بك — مكتب المحافظ 📜',
                'message' => 'رفع التأشيرة الختامية على القرارات النهائية.',
                'color' => 'warning',
                'icon' => 'heroicon-o-check-badge',
                'quickLinks' => [
                    ['label' => 'سير العمل', 'url' => '/gis/workflow-dashboard', 'icon' => 'heroicon-o-arrow-path'],
                ],
            ],
            'default' => [
                'title' => 'أهلاً بك في بوابة كفر الشيخ الجيومكانية 🌟',
                'message' => 'من هنا يمكنك الوصول للخدمات المخصصة لدورك الوظيفي.',
                'color' => 'primary',
                'icon' => 'heroicon-o-globe-alt',
                'quickLinks' => array_values(array_filter([
                    ['label' => 'الملف الشخصي', 'url' => '/gis/profile', 'icon' => 'heroicon-o-user-circle'],
                    ['label' => 'جملة الطلبات', 'url' => '/gis/removal-orders', 'icon' => 'heroicon-o-document-magnifying-glass'],
                    // صفحة الخدمات التفصيلية تظهر فقط عند إتاحة الخدمات المكانية
                    \App\Support\EServices::gisServicesOpen()
                        ? ['label' => 'الخدمات التفصيلية', 'url' => '/gis/gis-sub-services', 'icon' => 'heroicon-o-adjustments-vertical']
                        : null,
                ])),
            ],
        ];

        return $welcomeMap[$role] ?? $welcomeMap['default'];
    }

    protected function getViewData(): array
    {
        return [
            'welcomeData' => $this->getWelcomeData(),
        ];
    }
}
