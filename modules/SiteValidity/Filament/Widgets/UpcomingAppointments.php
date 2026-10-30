<?php

namespace Modules\SiteValidity\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Modules\SiteValidity\Filament\Resources\RequestResource;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvStatus;

/** جدول المعاينات القادمة والمتأخرة (بديل التقويم). */
class UpcomingAppointments extends TableWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'مواعيد المعاينة القادمة والمتأخرة';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SiteValidityRequest::query()
                    ->visibleTo(auth()->user())
                    ->whereIn('status', [SvStatus::APPOINTMENT_SET, SvStatus::APPROVED])
                    ->whereNotNull('appointment_at')
                    ->orderBy('appointment_at')
            )
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('لا توجد مواعيد معاينة مجدولة')
            ->columns([
                Tables\Columns\TextColumn::make('appointment_at')->label('الموعد')->dateTime('l Y-m-d H:i')
                    ->color(fn (SiteValidityRequest $r) => $r->appointment_at->isPast() ? 'danger' : ($r->appointment_at->isToday() ? 'warning' : null))
                    ->description(fn (SiteValidityRequest $r) => $r->appointment_at->isPast() ? 'متأخر' : $r->appointment_at->diffForHumans()),
                Tables\Columns\TextColumn::make('number')->label('الطلب')->weight('bold'),
                Tables\Columns\TextColumn::make('applicant_name')->label('مقدم الطلب')->limit(24),
                Tables\Columns\TextColumn::make('center_name')->label('الموقع')
                    ->formatStateUsing(fn ($state, SiteValidityRequest $r) => $r->locationLabel()),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => SvStatus::label($state))->color(fn ($state) => SvStatus::color($state)),
                Tables\Columns\TextColumn::make('marks')->label('علامات المعاينة')
                    ->state(fn (SiteValidityRequest $r) => ($r->geo_inspected_at ? '✓' : '○') . ' الجيومكاني · ' . ($r->engineer_inspected_at ? '✓' : '○') . ' المهندس'),
            ])
            ->recordUrl(fn (SiteValidityRequest $r) => RequestResource::getUrl('view', ['record' => $r]));
    }
}
