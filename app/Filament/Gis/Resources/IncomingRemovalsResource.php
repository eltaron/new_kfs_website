<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\IncomingRemovalsResource\Pages;
use Modules\RemovalGovernance\Models\RemovalOrder;
use Modules\Gis\Models\GisMarkaz;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IncomingRemovalsResource extends Resource
{
    protected static ?string $model = RemovalOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static ?string $navigationLabel = 'الوارد الجديد';
    protected static ?string $modelLabel = 'قرار وارد';
    protected static ?string $pluralModelLabel = 'القرارات الواردة الجديدة';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool { return false; }

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAnyRole([
            'super_admin', 'Admin',
            'مدير المركز', 'مدير الادارة الهندسية',
            'رؤوساء الاقسام', 'مهندس التنظيم', 'مدير التنظيم',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function(Builder $q) {
                $q->where('status', 'قيد الإعداد')
                  ->orWhere('stage', RemovalOrder::STAGE_CREATED);
            });
    }

    public static function form(Form $form): Form
    {
        return RemovalOrderResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stop_order_number')
                    ->label('المسلسل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('owner_name')
                    ->label('الاسم (المالك)')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('local_unit')
                    ->label('الوحدة المحلية')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('violation_works')
                    ->label('الأعمال المخالفة')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->violation_works),

                Tables\Columns\TextColumn::make('status')
                    ->label('موقف التعديل')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'تم التنفيذ' ? 'تم التعديل/الإزالة' : 'قيد التعديل')
                    ->color(fn($state) => $state === 'تم التنفيذ' ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الورود')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center')
                    ->label('تصفية بالمركز')
                    ->options(GisMarkaz::cachedOptions()),
            ])
            ->actions([
                // زر استعراض مشروع القرار (الطباعة)
                Tables\Actions\Action::make('print_decision')
                    ->label('استعراض مشروع القرار')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn(RemovalOrder $record) => route('gis.removal.print', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make()->label('معاينة'),
                Tables\Actions\EditAction::make()->label('تعديل'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'قيد الإعداد')->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomingRemovals::route('/'),
            'create' => Pages\CreateIncomingRemovals::route('/create'),
            'edit' => Pages\EditIncomingRemovals::route('/{record}/edit'),
        ];
    }
}
