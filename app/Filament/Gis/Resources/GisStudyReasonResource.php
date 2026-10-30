<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\GisStudyReasonResource\Pages;
use Modules\Gis\Models\GisStudyReason;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GisStudyReasonResource extends Resource
{
    protected static ?string $model = GisStudyReason::class;

    // إعدادات المظهر والقوائم في لوحة GIS
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'أسباب إعادة الدراسة';
    protected static ?string $modelLabel = 'سبب إعادة دراسة';
    protected static ?string $pluralModelLabel = 'قائمة أسباب إعادة الدراسة';
    protected static ?int $navigationSort = 5;
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
     * بناء نموذج الإضافة والتعديل
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('إضافة سبب جديد')
                    ->description('أدخل مسمى واضحاً للسبب الذي قد يظهر للمواطن في قائمة طلبات إعادة الدراسة.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('نص السبب (عربي)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true) // منع تكرار نفس السبب
                            ->placeholder('مثال: وجود اختلاف في مساحة الرفع المساحي عن العقود المرفقة'),
                    ]),
            ]);
    }

    /**
     * بناء جدول العرض مع التركيز على البحث والفلترة
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('السبب المسجل')
                    ->searchable() // تفعيل السيرش العام والخاص بهذا العمود
                    ->sortable()
                    ->weight('bold')
                    ->wrap(), // للسماح للنصوص الطويلة بالنزول لسطر جديد

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false), // إظهاره كعمود اختياري
            ])

            // خيارات البحث المتقدمة
            ->persistSearchInSession() // حفظ البحث حتى بعد مغادرة الصفحة

            ->filters([
                // فلتر حسب تاريخ الإضافة (اختياري للتقارير)
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) $indicators[] = 'من ' . $data['from'];
                        if ($data['until'] ?? null) $indicators[] = 'إلى ' . $data['until'];
                        return $indicators;
                    })
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
            ->emptyStateHeading('لا توجد أسباب مسجلة')
            ->emptyStateDescription('أضف أسباباً مثل (نقص مرفقات، تعديل إحداثيات، إلخ) لتظهر في فورم المواطن.');
    }

    /**
     * تفعيل ميزة البحث العام (Global Search) من شريط البحث في أعلى لوحة التحكم بالكامل
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGisStudyReasons::route('/'),
            'create' => Pages\CreateGisStudyReason::route('/create'),
            'edit' => Pages\EditGisStudyReason::route('/{record}/edit'),
        ];
    }
}
