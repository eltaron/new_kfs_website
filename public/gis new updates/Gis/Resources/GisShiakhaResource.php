<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\GisShiakhaResource\Pages;
use App\Models\GisShiakha;
use App\Models\GisMarkaz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GisShiakhaResource extends Resource
{
    protected static ?string $model = GisShiakha::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'الوحدات المحلية';
    protected static ?string $modelLabel = 'وحدة محلية';
    protected static ?string $pluralModelLabel = 'الوحدات المحلية ';
    protected static ?int $navigationSort = 2;
    // protected static ?string $navigationGroup = 'الخدمات المكانية والمساحية';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز',  'رؤوساء الاقسام']);
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
                Forms\Components\Section::make('تفاصيل الوحدة المحلية')
                    ->schema([
                        // اختيار المركز مع ميزة البحث والتحميل المسبق
                        Forms\Components\Select::make('gis_markaz_id')
                            ->label('المركز التابع له')
                            ->relationship('markaz', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('اسم الوحدة / الشياخة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: الوحدة المحلية ببرج مغيزل'),

                        Forms\Components\TextInput::make('shiakha_code')
                            ->label('كود الشياخة (GIS)')
                            ->maxLength(50)
                            ->placeholder('D15XXXX'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // 1. الأعمدة
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الوحدة/الشياخة')
                    ->searchable() // تفعيل البحث في هذا العمود
                    ->sortable()
                    ->weight('bold'),

                // عرض اسم المركز المرتبط
                Tables\Columns\TextColumn::make('markaz.name')
                    ->label('المركز الرئيسي')
                    ->searchable() // تمكين البحث في اسم المركز بداخل صفحة الشياخات
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('shiakha_code')
                    ->label('الكود')
                    ->toggleable() // إمكانية إخفاء/إظهار العمود
                    ->searchable(),

                // عداد ذكي لعدد القرى المضافة لهذه الوحدة
                Tables\Columns\TextColumn::make('villages_count')
                    ->label('عدد القرى/العزب')
                    ->counts('villages')
                    ->sortable()
                    ->icon('heroicon-o-home'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // 2. الفلترة (Filter)
            ->filters([
                // فلتر سريع لاختيار وحدات مركز معين فقط
                Tables\Filters\SelectFilter::make('markaz')
                    ->label('تصفية حسب المركز')
                    ->relationship('markaz', 'name')
                    ->searchable() // يجعل الفلتر قابلاً للبحث
                    ->preload(),
            ])

            // 3. خيارات عرض الجدول والبحث العام
            ->persistSearchInSession() // حفظ جملة البحث حتى عند التنقل بين الصفحات
            ->defaultSort('name')      // الترتيب الافتراضي أبجدياً

            // 4. الإجراءات (Actions)
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد بيانات متاحة')
            ->emptyStateDescription('يمكنك إضافة وحدات محلية وشياخات وربطها بالمراكز من هنا.');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGisShiakhas::route('/'),
            'create' => Pages\CreateGisShiakha::route('/create'),
            'edit' => Pages\EditGisShiakha::route('/{record}/edit'),
        ];
    }
}
