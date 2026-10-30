<?php

namespace Modules\Syndicate\Filament\Resources\ProjectResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Syndicate\Filament\Resources\ProjectResource;
use Modules\Syndicate\Models\Project;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('تسجيل مشروع جديد'),
        ];
    }

    public function getTabs(): array
    {
        $counts = Project::query()->selectRaw('work_status, count(*) as c')->groupBy('work_status')->pluck('c', 'work_status');

        $tabs = ['all' => Tab::make('الكل')];
        foreach (Project::workStatuses() as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge((string) ($counts[$status] ?? 0))
                ->badgeColor(Project::workStatusColors()[$status] ?? 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('work_status', $status));
        }

        return $tabs;
    }
}
