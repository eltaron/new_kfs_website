<?php

namespace App\Filament\Widgets;

use Modules\Gis\Models\GisSubmission;
use Modules\Citizen\Models\ServiceSubmission;
use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\TrainingApplication;
use Modules\Citizen\Models\Complaint;
use Modules\Citizen\Models\Suggestion;
use Modules\Citizen\Models\ContactMessage;
use Modules\Citizen\Models\EmergencyReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MainStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('خدمات مكانية (GIS)', GisSubmission::count())
                ->description('إجمالي الطلبات الجيومكانية')
                ->descriptionIcon('heroicon-m-map')
                ->color('info'),

            Stat::make('خدمات عامة', ServiceSubmission::count())
                ->description('طلبات الخدمات المقدمة')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('كورسات استدامة', Enrollment::count() + TrainingApplication::count())
                ->description('المسجلين في البرامج التدريبية')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('شكاوي واردة', Complaint::count())
                ->description('إجمالي الشكاوي المسجلة')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('مقترحات', Suggestion::count())
                ->description('المقترحات المقدمة')
                ->descriptionIcon('heroicon-m-light-bulb')
                ->color('warning'),

            Stat::make('رسائل تواصل', ContactMessage::count())
                ->description('رسائل تواصل معنا')
                ->descriptionIcon('heroicon-m-chat-bubble-left-ellipsis')
                ->color('gray'),

            Stat::make('بلاغات طوارئ', EmergencyReport::count())
                ->description('بلاغات الطوارئ الميدانية')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('danger'),
        ];
    }
}
