<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\GisServiceTypeResource\Pages;
use Modules\Gis\Models\GisServiceType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GisServiceTypeResource extends Resource
{
    protected static ?string $model = GisServiceType::class;

    // إعدادات الواجهة والقوائم
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationLabel = 'فئات الخدمات';
    protected static ?string $modelLabel = 'فئة خدمة';
    protected static ?string $pluralModelLabel = 'فئات الخدمات الجيومكانية';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'الخدمات المكانية والمساحية';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام']);
    }

    /**
     * باني نموذج الإدخال (Form)
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الأساسية للفئة')
                    ->description('قم بتعريف القسم الرئيسي الذي ستندرج تحته الخدمات التفصيلية.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الفئة الرئيسي')
                            ->required()
                            ->placeholder('مثال: خدمات الرفع المساحي المتميزة')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('icon')
                            ->label('أيقونة القسم (FontAwesome)')
                            ->placeholder('fas fa-map')
                            ->helperText('استخدم كلاسات Font Awesome لتظهر في واجهة الموقع.')
                            ->columnSpan(1),

                        Forms\Components\RichEditor::make('description')
                            ->label('وصف القسم')
                            ->placeholder('اكتب نبذة مختصرة عن نوعية الخدمات في هذه الفئة...')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    /**
     * باني جدول العرض (Table)
     */
    public static function table(Table $table): Table
    {
        return $table
            // تمكين البحث العام في كل الأعمدة المحددة بـ searchable()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الفئة الاستراتيجية')
                    ->searchable() // تفعيل السيرش في اسم الفئة
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('icon')
                    ->label('الأيقونة المستعملة')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                // عداد ذكي: يعرض كم خدمة فرعية موجودة داخل هذه الفئة فورياً
                Tables\Columns\TextColumn::make('sub_services_count')
                    ->label('عدد الخدمات المدرجة')
                    ->counts('subServices') // جلب العدد من علاقة الـ HasMany في الموديل
                    ->sortable()
                    ->icon('heroicon-o-list-bullet')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            // البحث العام - سيظهر مربع البحث في أعلى الجدول آلياً بفضل searchable()
            ->persistSearchInSession()

            ->filters([
                // يمكن إضافة فلتر مخصص للبحث عن الفئات التي لا تحتوي على خدمات بعد
                Tables\Filters\TernaryFilter::make('has_services')
                    ->label('هل تحتوي على خدمات؟')
                    ->placeholder('الكل')
                    ->trueLabel('بها خدمات')
                    ->falseLabel('فئة فارغة')
                    ->queries(
                        true: fn(Builder $query) => $query->has('subServices'),
                        false: fn(Builder $query) => $query->doesntHave('subServices'),
                    ),
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
            ->emptyStateHeading('لا يوجد فئات خدمات حالياً')
            ->emptyStateDescription('ابدأ بإنشاء أول فئة رئيسية (مثل الرفع المساحي) لتبدأ بعدها في إضافة الخدمات التابعة لها.');
    }

    /**
     * تمكين ميزة البحث العام (Global Search) من أعلى الصفحة في اللوحة
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
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
            'index' => Pages\ListGisServiceTypes::route('/'),
            'create' => Pages\CreateGisServiceType::route('/create'),
            'edit' => Pages\EditGisServiceType::route('/{record}/edit'),
        ];
    }
}
