<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\GisSubServiceResource\Pages;
use Modules\Gis\Models\GisSubService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms\Get;

class GisSubServiceResource extends Resource
{
    protected static ?string $model = GisSubService::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';
    protected static ?string $navigationLabel = 'الخدمات التفصيلية';
    protected static ?string $modelLabel = 'خدمة فرعية';
    protected static ?string $pluralModelLabel = 'خدمات النظم الجيومكانية';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = 'الخدمات المكانية والمساحية';

    public static function canAccess(): bool
    {
        // صفحات طلبات الخدمات المكانية تظهر فقط عند «إتاحة التقديم على الخدمات المكانية» (الإعدادات العامة)
        if (! \App\Support\EServices::gisServicesOpen()) {
            return false;
        }
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'مدير الادارة الهندسية', 'رؤوساء الاقسام']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('MainTabs')->tabs([

                    // 1. تابة المعلومات الأساسية
                    Forms\Components\Tabs\Tab::make('بيانات الخدمة المبدئية')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Forms\Components\Select::make('gis_service_type_id')
                                ->relationship('serviceType', 'name')
                                ->label('تتبع قسم رئيسي')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\TextInput::make('name')
                                ->label('اسم الخدمة (مثلاً: رخصة بناء)')
                                ->required()
                                ->lazy()
                                ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state, '-'))),

                            Forms\Components\TextInput::make('slug')
                                ->label('المعرف الرابط (Slug)')
                                ->required()
                                ->unique(ignoreRecord: true),

                            Forms\Components\RichEditor::make('description')
                                ->label('وصف الخدمة')
                                ->placeholder('وصف مختصر يظهر للمواطن')
                                ->required()->columnSpanFull(),

                            Forms\Components\TextInput::make('video_url')
                                ->label('رابط الفيديو الإرشادي (YouTube)')
                                ->url()
                                ->columnSpanFull(),
                        ])->columns(2),

                    // 2. تابة محرك التسعير (المحدثة لإضافة منطق الفدان الزائد)
                    Forms\Components\Tabs\Tab::make('نظام التسعير والرسوم')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Forms\Components\Select::make('pricing_type')
                                ->label('طريقة حساب تكلفة الخدمة')
                                ->options([
                                    'fixed'   => 'سعر ثابت (مقطوع)',
                                    'formula' => 'معادلة (سعر المتر + رسوم ثابتة)',
                                    'tiered'  => 'نظام الشرائح (حسب المساحة/العدد)',
                                ])
                                ->default('fixed')
                                ->live()
                                ->required(),

                            Forms\Components\TextInput::make('base_price')
                                ->label('المقابل المالي (السعر الأساسي)')
                                ->numeric()
                                ->prefix('EGP')
                                ->default(0)
                                ->required(),

                            // --- إعدادات المعادلات ---
                            Forms\Components\Grid::make(2)
                                ->visible(fn(Get $get) => $get('pricing_type') === 'formula')
                                ->schema([
                                    Forms\Components\TextInput::make('pricing_settings.multiplier')
                                        ->label('سعر المتر المربع')
                                        ->numeric()->prefix('EGP'),
                                    Forms\Components\TextInput::make('pricing_settings.variable')
                                        ->label('اسم الحقل المتغير')
                                        ->default('area_m2'),
                                ]),

                            // --- إعدادات الشرائح ---
                            Forms\Components\Section::make('جدول الشرائح الأساسي')
                                ->visible(fn(Get $get) => $get('pricing_type') === 'tiered')
                                ->schema([
                                    Forms\Components\Repeater::make('pricing_settings.tiers')
                                        ->label('الشرائح')
                                        ->schema([
                                            Forms\Components\TextInput::make('max')->label('إلى مساحة (م2)')->numeric()->required(),
                                            Forms\Components\TextInput::make('price')->label('السعر')->numeric()->required(),
                                        ])->columns(2)->addActionLabel('أضف شريحة'),
                                ]),

                            // --- الجزء الجديد: منطق الزيادة للمساحات الكبيرة (أكثر من فدان) ---
                            Forms\Components\Section::make('قواعد المساحات الزائدة (المساحات الكبرى)')
                                ->description('يستخدم هذا الجزء لإضافة مبالغ إضافية عند تخطي مساحة معينة (مثال: أكثر من فدان)')
                                ->visible(fn(Get $get) => $get('pricing_type') === 'tiered')
                                ->schema([
                                    Forms\Components\Toggle::make('pricing_settings.has_overflow')
                                        ->label('تفعيل حساب الزيادة للمساحات الكبيرة')
                                        ->live(),

                                    Forms\Components\Grid::make(3)
                                        ->visible(fn(Get $get) => $get('pricing_settings.has_overflow'))
                                        ->schema([
                                            Forms\Components\TextInput::make('pricing_settings.overflow_threshold')
                                                ->label('الحد الذي تبدأ منه الزيادة (م2)')
                                                ->numeric()
                                                ->placeholder('مثال: 4200')
                                                ->helperText('المساحة التي بعدها يبدأ حساب الزيادة'),

                                            Forms\Components\TextInput::make('pricing_settings.overflow_price')
                                                ->label('مبلغ الزيادة')
                                                ->numeric()
                                                ->prefix('EGP')
                                                ->placeholder('مثال: 2000'),

                                            Forms\Components\TextInput::make('pricing_settings.overflow_unit_size')
                                                ->label('لكل وحدة مساحة قدرها (م2)')
                                                ->numeric()
                                                ->default(4200)
                                                ->helperText('مثال: 4200 لحساب الزيادة لكل فدان إضافي'),
                                        ]),
                                ]),

                            // إعدادات النقاط الزائدة
                            Forms\Components\Section::make('إعدادات النقاط الزائدة')
                                ->visible(fn(Get $get) => $get('pricing_type') === 'tiered')
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('pricing_settings.point_threshold')->label('عدد النقاط المسموح به')->numeric(),
                                        Forms\Components\TextInput::make('pricing_settings.point_extra')->label('سعر النقطة الإضافية')->numeric(),
                                    ]),
                                ])->collapsed(),
                        ]),

                    // 3. تابة المتطلبات
                    Forms\Components\Tabs\Tab::make('المتطلبات والضوابط')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            Forms\Components\RichEditor::make('requirements')->label('المستندات المطلوبة')->required(),
                            Forms\Components\RichEditor::make('terms_conditions')->label('الشروط والأحكام')->required(),
                        ]),

                    // 4. تابة باني النموذج
                    Forms\Components\Tabs\Tab::make('حقول نموذج الطلب')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            Forms\Components\Repeater::make('dynamic_fields')
                                ->label('عناصر النموذج')
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('label')->label('عنوان السؤال')->required(),
                                        Forms\Components\TextInput::make('name')->label('المعرف (EN)')->required(),
                                        Forms\Components\Select::make('type')->label('النوع')
                                            ->options(['text' => 'نص', 'number' => 'رقم', 'select' => 'اختيارات', 'file' => 'ملف', 'db_select' => 'قاعدة بيانات'])
                                            ->required()->live(),
                                    ]),
                                    Forms\Components\Textarea::make('options')->label('الخيارات')->visible(fn(Get $get) => $get('type') === 'select'),
                                    Forms\Components\Select::make('table')->label('الجدول المصدر')->options(['markazs' => 'المراكز', 'shiakhas' => 'الشياخات', 'villages' => 'القرى'])
                                        ->visible(fn(Get $get) => $get('type') === 'db_select'),
                                    Forms\Components\Toggle::make('is_required')->label('إلزامي')->default(true),
                                ])
                                ->itemLabel(fn($state) => $state['label'] ?? 'حقل جديد')
                                ->collapsible()->collapsed()->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الخدمة')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('serviceType.name')->label('التصنيف')->badge()->color('warning'),
                Tables\Columns\TextColumn::make('pricing_type')->label('نظام التسعير')->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'fixed' => 'ثابت',
                        'formula' => 'معادلة',
                        'tiered' => 'شرائح',
                        default => $state
                    }),
                Tables\Columns\TextColumn::make('base_price')->label('السعر الأساسي')->money('EGP')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGisSubServices::route('/'),
            'create' => Pages\CreateGisSubService::route('/create'),
            'edit' => Pages\EditGisSubService::route('/{record}/edit'),
        ];
    }
}
