<?php

namespace App\Filament\Gis\Resources\GisSubmissionResource\Pages;

use App\Filament\Gis\Resources\GisSubmissionResource;
use App\Models\GisSubmission;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGisSubmissions extends ListRecords
{
    protected static string $resource = GisSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // يمكن إضافة زر لإصدار تقارير هنا
        ];
    }

    /**
     * تعريف التبويبات العلوية لفرز الطلبات
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('كافة المعاملات'),

            'new' => Tab::make('الوارد الجديد')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'received'))
                ->badge(GisSubmission::where('status', 'received')->count())
                ->badgeColor('danger'),

            'processing' => Tab::make('قيد المراجعة الفنية')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'processing'))
                ->badge(GisSubmission::where('status', 'processing')->count())
                ->badgeColor('info'),

            'completed' => Tab::make('المعاملات المكتملة')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'completed'))
                ->badge(GisSubmission::where('status', 'completed')->count())
                ->badgeColor('success'),
        ];
    }
}
