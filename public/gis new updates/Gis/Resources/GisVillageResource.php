<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\GisVillageResource\Pages;
use App\Models\GisVillage;
use App\Models\GisMarkaz;
use App\Models\GisShiakha;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GisVillageResource extends Resource
{
    protected static ?string $model = GisVillage::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationLabel = 'القرى والعزب  (الشياخات)';
    protected static ?string $modelLabel = 'قرية أو عزبة';
    protected static ?string $pluralModelLabel = 'قاعدة بيانات القرى والعزب';
    protected static ?int $navigationSort = 3;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('التوطين الجغرافي للمنطقة')
                    ->description('قم بتحديد التبعية الإدارية بدقة لضمان ظهور القرية في نتائج البحث الجغرافي للمواطنين.')
                    ->schema([
                        // 1. اختيار المركز (لا يحفظ في جدول القرى ولكن يستخدم للفلترة)
                        Forms\Components\Select::make('gis_markaz_id')
                            ->label('المركز الرئيسي')
                            ->options(GisMarkaz::cachedIdOptions())
                            ->searchable()
                            ->live()
                            ->required()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn(callable $set) => $set('gis_shiakha_id', null)),

                        // 2. اختيار الوحدة المحلية (مرتبط بالمركز المختار)
                        Forms\Components\Select::make('gis_shiakha_id')
                            ->label('الوحدة المحلية / الشياخة')
                            ->placeholder('اختر الوحدة المحلية أولاً')
                            ->options(function (callable $get) {
                                $markazId = $get('gis_markaz_id');
                                if (!$markazId) {
                                    return [];
                                }
                                return GisShiakha::where('gis_markaz_id', $markazId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->preload(),

                        // 3. اسم القرية
                        Forms\Components\TextInput::make('name')
                            ->label('اسم القرية / العزبة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: عزبة خضر'),

                        // 4. التصنيف
                        Forms\Components\Toggle::make('is_ezba')
                            ->label('تصنيف كعزبة / نجع')
                            ->helperText('اترك الخيار غير مفعل إذا كانت المنطقة "قرية رئيسية"')
                            ->onIcon('heroicon-m-home')
                            ->offIcon('heroicon-m-building-office-2'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المنطقة')
                    ->searchable(isIndividual: true) // تفعيل بحث فردي لهذا العمود
                    ->sortable()
                    ->weight('bold'),

                // عرض التبعية (الوحدة والمركز)
                Tables\Columns\TextColumn::make('shiakha.name')
                    ->label('الوحدة المحلية')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shiakha.markaz.name')
                    ->label('المركز')
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                // أيقونة توضح النوع
                Tables\Columns\IconColumn::make('is_ezba')
                    ->label('نوع المنطقة')
                    ->boolean()
                    ->trueIcon('heroicon-o-home')
                    ->falseIcon('heroicon-o-building-office')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // التحكم في عدد الصفوف والترتيب
            ->defaultPaginationPageOption(25) // عرض 25 صف افتراضياً
            ->defaultSort('name', 'asc')

            ->filters([
                // 1. فلتر المراكز (مركز ثقل الفلترة)
                Tables\Filters\SelectFilter::make('markaz')
                    ->label('فرز بالمركز')
                    ->relationship('shiakha.markaz', 'name')
                    ->searchable()
                    ->preload(),

                // 2. فلتر الوحدات المحلية
                Tables\Filters\SelectFilter::make('shiakha')
                    ->label('فرز بالوحدة المحلية')
                    ->relationship('shiakha', 'name')
                    ->searchable()
                    ->preload(),

                // 3. فلتر القرى/العزب
                Tables\Filters\TernaryFilter::make('is_ezba')
                    ->label('التصنيف النوعي')
                    ->trueLabel('عزب ونجوع فقط')
                    ->falseLabel('قرى رئيسية فقط')
                    ->placeholder('الكل'),
            ])

            // البحث العام عبر كافة الحقول
            ->persistSearchInSession()

            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد قرى مضافة بعد')
            ->emptyStateDescription('ابدأ بربط القرى والعزب بالوحدات المحلية الخاصة بها لمناطق المحافظة.');
    }

    /**
     * ميزة البحث العالمي (Global Search) من شريط البحث في أعلى اللوحة
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'shiakha.name', 'shiakha.markaz.name'];
    }

    /**
     * لإظهار تفاصيل أكثر في نتائج البحث العالمي
     */
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'المركز' => $record->shiakha->markaz->name,
            'الوحدة المحلية' => $record->shiakha->name,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGisVillages::route('/'),
            'create' => Pages\CreateGisVillage::route('/create'),
            'edit' => Pages\EditGisVillage::route('/{record}/edit'),
        ];
    }
}
