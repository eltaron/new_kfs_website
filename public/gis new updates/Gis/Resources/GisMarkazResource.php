<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\GisMarkazResource\Pages;
use App\Models\GisMarkaz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GisMarkazResource extends Resource
{
    // ربط الموديل
    protected static ?string $model = GisMarkaz::class;

    // إعدادات واجهة المستخدم والقوائم
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'االمراكز والمدن ';
    protected static ?string $modelLabel = 'مركز ';
    protected static ?string $pluralModelLabel = 'المراكز والمناطق';
    protected static ?int $navigationSort = 1;
    // protected static ?string $navigationGroup = 'الخدمات المكانية والمساحية';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام']);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
    public static function canCreate(): bool
    {
        return static::canAccess();
    }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess();
    }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess();
    }
    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess();
    }

    /**
     * نموذج الإضافة والتعديل
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المركز')
                    ->description('أدخل البيانات الأساسية لمركز المحافظة كما هي مسجلة في نظام المعلومات الجغرافية.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المركز')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('مثال: مركز مطوبس'),

                        Forms\Components\TextInput::make('gis_code')
                            ->label('الكود الجغرافي (GIS Code)')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->placeholder('D1501')
                            ->helperText('الكود الفريد المخصص للمركز في قواعد البيانات المكانية.'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * جدول العرض
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المركز')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('gis_code')
                    ->label('الكود الجغرافي')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                // عرض عدد الوحدات المحلية (الشياخات) المرتبطة بهذا المركز آلياً
                Tables\Columns\TextColumn::make('shiakhas_count')
                    ->label('عدد الوحدات المحلية')
                    ->counts('shiakhas')
                    ->icon('heroicon-o-building-office')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // يمكنك إضافة فلاتر هنا مستقبلاً
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد مراكز مضافة')
            ->emptyStateDescription('ابدأ بإضافة أول مركز إداري للمحافظة من هنا.');
    }

    public static function getRelations(): array
    {
        return [
            // يمكن ربط الشياخات هنا كـ Relation Manager مستقبلاً
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGisMarkazs::route('/'),
            'create' => Pages\CreateGisMarkaz::route('/create'),
            'edit' => Pages\EditGisMarkaz::route('/{record}/edit'),
        ];
    }
}
