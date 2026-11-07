<?php

namespace App\Filament\Gis\Resources;

use App\Filament\Gis\Resources\NewGisSubmissionsResource\Pages;
use Modules\Gis\Models\GisSubmission;
use Modules\Gis\Models\GisMarkaz;
use Modules\Gis\Models\GisShiakha;
use Modules\Gis\Models\GisVillage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NewGisSubmissionsResource extends Resource
{
    protected static ?string $model = GisSubmission::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationGroup = 'الخدمات المكانية والمساحية';
    protected static ?string $navigationLabel = 'الوارد اليومي';
    protected static ?string $modelLabel = 'طلب جديد';
    protected static ?string $pluralModelLabel = 'طلبات الخدمات الجديدة';
    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        // صفحات طلبات الخدمات المكانية تظهر فقط عند «إتاحة التقديم على الخدمات المكانية» (الإعدادات العامة)
        if (! \App\Support\EServices::gisServicesOpen()) {
            return false;
        }
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام', 'مدير الادارة الهندسية', 'مدير التنظيم', 'محللي النظم', 'مدخل البيانات بالوحدة الفرعية']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('شاشة المراجعة الفنية')->tabs([

                // TAB 1: الإدارة والمواعيد
                Forms\Components\Tabs\Tab::make('مراجعة المواعيد')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('serial_number')->label('رقم المسلسل')->prefix('SN-'),
                            Forms\Components\DateTimePicker::make('inspection_date')->label('موعد المعاينة الميدانية'),
                            Forms\Components\Toggle::make('is_inspection_confirmed')->label('تأكيد الموعد للمواطن')->inline(false)->onColor('success'),
                        ]),
                        Forms\Components\Select::make('status')
                            ->label('حالة مراجعة الطلب')
                            ->options([
                                'received' => 'تحت المراجعة',
                                'processing' => 'جاري المعاينة',
                                'completed' => 'تم الاعتماد',
                                'rejected' => 'مرفوض إدارياً',
                            ])->required()->native(false),
                    ]),

                // TAB 2: تعديل بيانات المواطن (التابة الجديدة المطلوبة)
                Forms\Components\Tabs\Tab::make('تعديل بيانات الطلب الأصلية')
                    ->schema([
                        Forms\Components\Section::make('هوية مقدم الطلب')
                            ->description('تعديل البيانات التعريفية التي سجلها المواطن')
                            ->schema([
                                Forms\Components\TextInput::make('applicant_info.name')
                                    ->label('اسم مقدم الطلب'),
                                Forms\Components\Select::make('applicant_info.type')
                                    ->label('صفة المتقدم')
                                    ->options([
                                        'owner' => 'المالك الأصيل',
                                        'agent' => 'وكيل بموجب توكيل',
                                    ])->native(false),
                                Forms\Components\TextInput::make('applicant_info.agent_name')
                                    ->label('اسم الوكيل (إن وجد)'),
                            ])->columns(3),

                        Forms\Components\Section::make('الموقع الجغرافي المسجل')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('address_info.markaz_id')
                                        ->label('المركز')
                                        ->options(GisMarkaz::cachedIdOptions())
                                        ->live()
                                        ->afterStateUpdated(fn(Set $set) => $set('address_info.shiakha_id', null)),

                                    Forms\Components\Select::make('address_info.shiakha_id')
                                        ->label('الوحدة المحلية')
                                        ->options(fn(Get $get) => GisShiakha::where('gis_markaz_id', $get('address_info.markaz_id'))->pluck('name', 'id'))
                                        ->live()
                                        ->afterStateUpdated(fn(Set $set) => $set('address_info.village_id', null)),

                                    Forms\Components\Select::make('address_info.village_id')
                                        ->label('القرية / العزبة')
                                        ->options(fn(Get $get) => GisVillage::where('gis_shiakha_id', $get('address_info.shiakha_id'))->pluck('name', 'id')),
                                ]),
                                Forms\Components\Textarea::make('address_info.details')
                                    ->label('تفاصيل العنوان')->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('البيانات الفنية المرفوعة')
                            ->schema([
                                Forms\Components\KeyValue::make('form_data')
                                    ->label('إجابات الحقول الديناميكية')
                                    ->helperText('يمكنك تعديل القيم الفنية المسجلة (المساحة، عدد النقاط، إلخ) مباشرة من هنا.'),
                            ]),
                    ]),

                // TAB 3: الاشتراطات والحدود
                Forms\Components\Tabs\Tab::make('الاشتراطات والحدود')
                    ->icon('heroicon-o-map')
                    ->schema([
                        Forms\Components\Section::make('الاشتراطات التخطيطية للموقع')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('urban_planning.planned_height')->label('الارتفاع المقرر (م)')->numeric(),
                                    Forms\Components\TextInput::make('urban_planning.building_percentage')->label('نسبة البناء (%)')->suffix('%')->numeric(),
                                    Forms\Components\Select::make('urban_planning.planned_usage')
                                        ->label('الاستخدامات المقررة')
                                        ->options([
                                            'residential' => 'سكني',
                                            'commercial' => 'تجاري',
                                            'industrial' => 'صناعي',
                                            'mixed' => 'متعدد (سكني تجاري)',
                                            'other' => 'اخري',
                                        ])->native(false),
                                ]),
                                Forms\Components\Section::make('الاشتراطات والضوابط المعتمدة')
                                    ->schema([
                                        // الحقول الرقمية السابقة (الارتفاع والنسبة والاستخدام) تظل هنا..

                                        // 1. الحقل الجديد الأول
                                        Forms\Components\RichEditor::make('urban_planning.building_requirements')
                                            ->label('الاشتراطات البنائية العامة')
                                            ->placeholder('اكتب هنا الضوابط الإنشائية والبنائية...')
                                            ->columnSpanFull(),

                                        // 2. الحقل الجديد الثاني
                                        Forms\Components\RichEditor::make('urban_planning.supreme_council_requirements')
                                            ->label('اشتراطات صادرة من المجلس الأعلى للتخطيط والتنمية العمرانية')
                                            ->placeholder('اكتب هنا أي اشتراطات سيادية أو خاصة بالمجلس الأعلى...')
                                            ->columnSpanFull(),

                                        // حقل ملاحظات المراجع العام (يبقى كما هو للاستخدام الإداري الداخلي)
                                        Forms\Components\RichEditor::make('admin_notes')
                                            ->label('ملاحظات إضافية للمراجع العام')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Grid::make(2)->schema([
                            self::getBorderSection('الحد البحري (الشمال)', 'north'),
                            self::getBorderSection('الحد القبلي (الجنوب)', 'south'),
                            self::getBorderSection('الحد الشرقي', 'east'),
                            self::getBorderSection('الحد الغربي', 'west'),
                        ]),
                    ]),

                // TAB 4: الخريطة الرقمية
                Forms\Components\Tabs\Tab::make('الخريطة الرقمية (Web Map)')
                    ->icon('heroicon-o-globe-asia-australia')
                    ->schema([
                        Forms\Components\TextInput::make('web_map_url')->label('رابط WebMap للموقع')->suffixIcon('heroicon-m-globe-alt'),
                        Forms\Components\View::make('filament.gis.components.map-preview')->visible(fn($record) => $record && $record->web_map_url),
                    ]),
            ])->columnSpanFull()
        ]);
    }

    private static function getBorderSection($label, $key)
    {
        return Forms\Components\Section::make($label)->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make("borders.$key.description")->label('الوصف'),
                Forms\Components\Select::make("borders.$key.facade_type")
                    ->label('نوع الواجهة')
                    ->options(['none' => 'لا يوجد (جار)', 'front' => 'واجهة أمامية', 'back' => 'واجهة خلفية', 'side' => 'واجهة جانبية'])->native(false),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make("borders.$key.length")->label('طول الضلع (م)')->numeric(),
                Forms\Components\TextInput::make("borders.$key.street_width")->label('عرض الشارع')->numeric(),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Toggle::make("borders.$key.has_setback")->label('يوجد ارتداد')->live(),
                Forms\Components\Toggle::make("borders.$key.has_overhang")->label('يوجد بروز')->live(),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make("borders.$key.setback_amount")->label('مقدار الارتداد (م)')->numeric()->visible(fn(Get $get) => $get("borders.$key.has_setback")),
                Forms\Components\TextInput::make("borders.$key.overhang_amount")->label('مقدار البروز (م)')->numeric()->visible(fn(Get $get) => $get("borders.$key.has_overhang")),
            ]),
        ])->collapsible()->collapsed();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')->label('المسلسل')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('اسم المواطن')->searchable(),
                Tables\Columns\TextColumn::make('address_info.unit_name')->label('الوحدة المحلية'),
                Tables\Columns\TextColumn::make('status')->label('مراجعة الطلب')->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'received' => 'وارد جديد',
                        'processing' => 'تحت الفحص',
                        default => 'أرشيف'
                    })
                    ->color(fn($state) => match ($state) {
                        'received' => 'danger',
                        'processing' => 'warning',
                        default => 'success'
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل مجمع'),
                Tables\Actions\Action::make('print')->label('طباعة')->icon('heroicon-o-printer')->color('success')
                    ->url(fn($record) => route('gis.print', $record))->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewGisSubmissions::route('/'),
            'create' => Pages\CreateNewGisSubmissions::route('/create'),
            'edit' => Pages\EditNewGisSubmissions::route('/{record}/edit'),
        ];
    }
}
