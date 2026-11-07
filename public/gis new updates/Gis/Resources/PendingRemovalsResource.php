<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\PendingRemovalsResource\Pages;
use App\Models\RemovalOrder;
use App\Models\GisMarkaz;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PendingRemovalsResource extends Resource
{
    protected static ?string $model = RemovalOrder::class; // نستخدم نفس الموديل الأساسي

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static ?string $navigationLabel = 'قيد المراجعة';
    protected static ?string $modelLabel = 'قرار قيد الفحص';
    protected static ?string $pluralModelLabel = 'قرارات قيد المراجعة الفنية';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool { return false; }

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAnyRole([
            'super_admin', 'Admin',
            'مدير المركز', 'رؤوساء الاقسام',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function(Builder $q) {
                $q->where('status', 'قيد المراجعة')
                  ->orWhere('stage', RemovalOrder::STAGE_ENGINEERING_REVIEW);
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
                Tables\Columns\TextColumn::make('id')->label('كود القرار')->sortable(),
                Tables\Columns\TextColumn::make('owner_name')->label('اسم المالك')->searchable(),
                Tables\Columns\TextColumn::make('center')->label('المركز')->badge()->color('info'),
                Tables\Columns\TextColumn::make('engineer_name')->label('المهندس المسؤول')->searchable(),
                Tables\Columns\TextColumn::make('updated_at')->label('آخر تحديث')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('center')->label('المركز')->options(GisMarkaz::cachedOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('تفاصيل الفحص'),
                Tables\Actions\EditAction::make()->label('تحديث القرار'),
            ])
            ->persistSearchInSession();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'قيد المراجعة')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingRemovals::route('/'),
            'create' => Pages\CreatePendingRemovals::route('/create'),
            'edit' => Pages\EditPendingRemovals::route('/{record}/edit'),
        ];
    }
}
