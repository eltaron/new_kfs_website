<?php

namespace App\Filament\Gis\Resources\RemovalOrderResource\Pages;

use App\Filament\Gis\Resources\RemovalOrderResource;
use App\Models\RemovalOrder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRemovalOrders extends ListRecords
{
    protected static string $resource = RemovalOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make()->label('إضافة قرار إزالة جديد'),
        ];
    }

    private function scopeByCenter(): Builder
    {
        $query = RemovalOrder::query();
        $user = auth()->user();
        if ($user->hasRole('مهندس التنظيم') && $user->center) {
            $query->where('center', $user->center);
        }
        return $query;
    }

    private function countByStage(string $stage): int
    {
        return (clone $this->scopeByCenter())->where('stage', $stage)->count();
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('كافة القرارات')
                ->icon('heroicon-m-rectangle-stack'),

            'new' => Tab::make('قرارات جديدة')
                ->icon('heroicon-m-sparkles')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_CREATED))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_CREATED))
                ->badgeColor('warning'),

            'engineering_review' => Tab::make('مراجعة التنظيم')
                ->icon('heroicon-m-pencil-square')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_ENGINEERING_REVIEW))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_ENGINEERING_REVIEW))
                ->badgeColor('warning'),

            'spatial_pending' => Tab::make('تحديد مسؤول البعد المكاني')
                ->icon('heroicon-m-cursor-arrow-rays')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_SPATIAL_PENDING))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_SPATIAL_PENDING))
                ->badgeColor('info'),

            'spatial_dimensioned' => Tab::make('إضافة البعد المكاني')
                ->icon('heroicon-m-map')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_SPATIAL_DIMENSIONED))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_SPATIAL_DIMENSIONED))
                ->badgeColor('primary'),

            'pdf_ready' => Tab::make('توجية المركز الرئيسي')
                ->icon('heroicon-m-building-office-2')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_PDF_READY))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_PDF_READY))
                ->badgeColor('secondary'),

            'systems_processing' => Tab::make('التحليل المكاني')
                ->icon('heroicon-m-cog-6-tooth')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_SYSTEMS_PROCESSING))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_SYSTEMS_PROCESSING))
                ->badgeColor('purple'),

            'local_unit_approval' => Tab::make('الاعتماد من الوحدة المحلية')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL))
                ->badgeColor('teal'),
            'gis_pending' => Tab::make('التوجية للأعتماد')
                ->icon('heroicon-m-academic-cap')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_GIS_PENDING))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_GIS_PENDING))
                ->badgeColor('purple'),

            
            'visa_pending' => Tab::make('الاعتماد من السلطة المختصة')
                ->icon('heroicon-m-academic-cap')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_VISA_PENDING))
                ->badge(fn() => $this->countByStage(RemovalOrder::STAGE_VISA_PENDING))
                ->badgeColor('secondary'),

            'completed_pending_legal' => Tab::make('بانتظار الاجرائات القانونية')
                ->icon('heroicon-m-clock')
                // completed لكن مرفعش اخطار المواطن او الشرطة او الـ 1%
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_COMPLETED)
                    ->whereRaw("(citizen_notification_file IS NULL OR citizen_notification_file = ''
                        OR police_notification_file IS NULL OR police_notification_file = ''
                        OR notification_to_the_revenue_department_file IS NULL OR notification_to_the_revenue_department_file = '')"))
                ->badge(fn() => RemovalOrder::where('stage', RemovalOrder::STAGE_COMPLETED)
                    ->whereRaw("(citizen_notification_file IS NULL OR citizen_notification_file = ''
                        OR police_notification_file IS NULL OR police_notification_file = ''
                        OR notification_to_the_revenue_department_file IS NULL OR notification_to_the_revenue_department_file = '')")
                    ->count())
                ->badgeColor('warning'),

            'completed' => Tab::make('منتهية')
                ->icon('heroicon-m-check-circle')
                // completed ورفع الإخطارات الثلاثة
                ->modifyQueryUsing(fn(Builder $query) => $query->where('stage', RemovalOrder::STAGE_COMPLETED)
                    ->whereRaw("(citizen_notification_file IS NOT NULL AND citizen_notification_file != ''
                        AND police_notification_file IS NOT NULL AND police_notification_file != ''
                        AND notification_to_the_revenue_department_file IS NOT NULL AND notification_to_the_revenue_department_file != '')"))
                ->badge(fn() => RemovalOrder::where('stage', RemovalOrder::STAGE_COMPLETED)
                    ->whereRaw("(citizen_notification_file IS NOT NULL AND citizen_notification_file != ''
                        AND police_notification_file IS NOT NULL AND police_notification_file != ''
                        AND notification_to_the_revenue_department_file IS NOT NULL AND notification_to_the_revenue_department_file != '')")
                    ->count())
                ->badgeColor('success'),
        ];
    }
}