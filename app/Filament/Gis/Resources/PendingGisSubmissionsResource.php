<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\PendingGisSubmissionsResource\Pages;
use Modules\Gis\Models\GisSubmission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PendingGisSubmissionsResource extends Resource
{
    protected static ?string $model = GisSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'الخدمات المكانية والمساحية';
    protected static ?string $navigationLabel = 'قيد المراجعة';
    protected static ?string $modelLabel = 'طلب تحت المراجعة';
    protected static ?string $pluralModelLabel = 'طلبات قيد المراجعة الفنية';
    protected static ?int $navigationSort = 7;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام', 'مدير الادارة الهندسية', 'مدير التنظيم', 'محللي النظم']);
    }

    // تصفية البيانات لتظهر "قيد المراجعة" فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'processing');
    }

    public static function form(Form $form): Form
    {
        return GisSubmissionResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('المعاملة')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('user.name')->label('المواطن')->searchable(),
                Tables\Columns\TextColumn::make('subService.name')->label('الخدمة')->badge()->color('warning'),
                Tables\Columns\TextColumn::make('updated_at')->label('آخر تحديث')->since()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn() => 'تحت الفحص الفني')
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('تفاصيل المراجعة'),
                Tables\Actions\EditAction::make()->label('تحديث الحالة'),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'processing')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingGisSubmissions::route('/'),
            'create' => Pages\CreatePendingGisSubmissions::route('/create'),
            'edit' => Pages\EditPendingGisSubmissions::route('/{record}/edit'),
        ];
    }
}
