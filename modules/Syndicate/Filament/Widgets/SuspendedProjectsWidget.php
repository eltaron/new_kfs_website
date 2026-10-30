<?php

namespace Modules\Syndicate\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Modules\Syndicate\Filament\PanelContext;
use Modules\Syndicate\Filament\Resources\ProjectResource;
use Modules\Syndicate\Models\Project;

/** مشروعات موقوفة بدون مهندس مشرف — للمتابعة الميدانية لإيقاف الأعمال. */
class SuspendedProjectsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'مشروعات موقوفة - بدون إشراف (يجب إيقاف الأعمال)';

    public static function canView(): bool
    {
        return PanelContext::userCanUsePanel() && Project::where('work_status', Project::WORK_SUSPENDED)->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Project::query()->where('work_status', Project::WORK_SUSPENDED)->latest('suspended_at'))
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('كود المشروع'),
                Tables\Columns\TextColumn::make('owner_name')->label('المالك'),
                Tables\Columns\TextColumn::make('center')->label('المركز'),
                Tables\Columns\TextColumn::make('address')->label('العنوان')->limit(50),
                Tables\Columns\TextColumn::make('suspended_at')->label('موقوف منذ')->since()->color('danger'),
            ])
            ->recordUrl(fn (Project $r) => ProjectResource::getUrl('view', ['record' => $r]))
            ->paginated([5, 10, 25]);
    }
}
