<?php

namespace App\Filament\Gis\Pages;

use Modules\RemovalGovernance\Models\RemovalOrder;
use Modules\Gis\Models\GisMarkaz;
use Modules\Gis\Models\GisShiakha;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class AddRemovalOrder extends Page implements HasForms
{

    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationGroup = 'حوكمة قرارات الإزالة';
    protected static ?string $navigationLabel = 'إضافة قرار إزالة جديد';
    protected static ?string $title = 'نموذج تسجيل مخالفة قرار إزالة';
    protected static ?int $navigationSort = 0; // ليكون أول عنصر في المجموعة

    protected static string $view = 'filament.gis.pages.add-removal-order';
    // 1. هذه الدالة تتحكم في إظهار الرابط في القائمة الجانبية (Sidebar)
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            'super_admin',
            "مدير المركز",
            "مهندس التنظيم",
            "مدير التنظيم",
            'فني التنظيم',
            'العضو الميداني'
        ]);
    }

    // 2. هذه الدالة تتحكم في صلاحية الدخول للصفحة (تمنع الدخول عبر الرابط المباشر)
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([
            'super_admin',
            'مدير المركز',
            'فني التنظيم',
            'العضو الميداني'
        ]);
    }
    // مصفوفة البيانات التي سيتم ربطها بالفورم
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // المرحلة 1: نوع المخالفة
                    Wizard\Step::make('نوع المخالفة')
                        ->schema([
                            Select::make('violation_type')
                                ->label('اختر نوع المخالفة')
                                ->options([
                                    'new_violation' => 'مخالفة بناء بدون ترخيص',
                                    // 'licensed_violation' => 'مخالفة شروط ترخيص',
                                ])->required()->live(),

                            // Section::make('بيانات الترخيص')
                            //     ->visible(fn(Get $get) => $get('violation_type') === 'licensed_violation')
                            //     ->schema([
                            //         TextInput::make('license_number')->label('رقم الترخيص')->required(),
                            //         DatePicker::make('license_date')->label('تاريخ الترخيص')->required(),
                            //     ])->columns(2),
                        ]),

                    // المرحلة 2: بيانات الموقع والمخالف
                    Wizard\Step::make('الموقع والمخالف')
                        ->schema([
                            Section::make('الموقع')->schema([
                                Grid::make(3)->schema([
                                    Select::make('center')->label('المركز')->options(GisMarkaz::cachedOptions())->required()->live(),
                                    Select::make('local_unit')->label('الوحدة المحلية')->options(fn(Get $get) => GisShiakha::whereHas('markaz', fn($q) => $q->where('name', $get('center')))->pluck('name', 'name'))->nullable(),
                                ]),
                                TextInput::make('street')->label('العنوان')->placeholder('أدخل العنوان بالكامل (شارع، حي، رقم العقار)')->required(),
                            ]),
                            Section::make('بيانات المخالف')->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('owner_name')->label('بيانات المالك او من يمثله قانونا')->required(),
                                    TextInput::make('owner_national_id')->label('الرقم القومي')->numeric()->rule('digits:14')->required(),
                                ]),
                            ]),
                            Section::make('المهندس والمقاول')->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('engineer_name')->label('اسم المهندس المسؤول'),
                                    TextInput::make('engineer_national_id')->label('الرقم القومي للمهندس')->numeric()->rule('digits:14'),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('contractor_name')->label('اسم المقاول'),
                                    TextInput::make('contractor_national_id')->label('الرقم القومي للمقاول')->numeric()->rule('digits:14'),
                                ]),
                            ]),
                        ]),

                    // المرحلة 3: المعاينة والقرارات
                    Wizard\Step::make('المعاينة والقرارات')
                        ->schema([
                            Section::make('وصف المخالفة')->schema([
                                RichEditor::make('violation_works')->label('وصف الأعمال المخالفة')->required(),
                            ]),
                            Section::make('الحدود والأبعاد')->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('border_north')->label('الحد الشمالي'),
                                    TextInput::make('dimension_north')->label('البعد الشمالي'),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('border_south')->label('الحد الجنوبي'),
                                    TextInput::make('dimension_south')->label('البعد الجنوبي'),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('border_east')->label('الحد الشرقي'),
                                    TextInput::make('dimension_east')->label('البعد الشرقي'),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('border_west')->label('الحد الغربي'),
                                    TextInput::make('dimension_west')->label('البعد الغربي'),
                                ]),
                            ]),
                            Section::make('المرفقات')->schema([
                                FileUpload::make('photo_file')->label('صورة المخالفة')->image()->required()
                                    ->extraAttributes(['x-init' => '$watch(\'state\', () => getUserLocation())']),
                                FileUpload::make('stop_order_file')->label('محضر الإيقاف (PDF)')->acceptedFileTypes(['application/pdf'])->nullable(),
                            ]),
                            Section::make('الموقع الجغرافي')->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('latitude')->label('خط العرض')->readonly()->id('lat_input'),
                                    TextInput::make('longitude')->label('خط الطول')->readonly()->id('lng_input'),
                                ]),
                            ]),
                            Section::make('بيانات القرار')->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('stop_order_number')->label('رقم قرار الإيقاف'),
                                    DatePicker::make('stop_order_date')->label('تاريخ قرار الإيقاف'),
                                ]),
                                Grid::make(2)->schema([
                                    DatePicker::make('announcement_date')->label('تاريخ إعلان المواطن'),
                                ]),
                            ]),
                        ]),
                ])
                    ->submitAction(view('filament.gis.components.submit-button')) // زر الحفظ النهائي
                    ->statePath('data'),
            ]);
    }

    /**
     * دالة حفظ البيانات النهائية
     */
    public function create()
    {
        $formData = $this->form->getState()['data'];
        $formData['created_by'] = auth()->id();
        $formData['stage'] = RemovalOrder::STAGE_CREATED;
        $formData['local_unit'] ??= 'غير محدد';
        $formData['violation_area'] ??= 'غير محدد';
        $formData['district'] ??= 'غير محدد';
        $formData['violation_plot'] ??= 'غير محدد';
        $formData['violation_dimensions'] ??= 'غير محدد';
        $formData['violation_cost'] ??= 0;
        $formData['owner_center'] ??= $formData['center'] ?? 'غير محدد';
        $formData['owner_unit'] ??= $formData['local_unit'] ?? 'غير محدد';
        $formData['owner_street'] ??= $formData['street'] ?? 'غير محدد';
        $formData['owner_district'] ??= 'غير محدد';
        $formData['owner_governorate'] ??= 'كفر الشيخ';
        $formData['engineer_national_id'] ??= 'غير محدد';
        $formData['contractor_national_id'] ??= 'غير محدد';
        $formData['violation_report_number'] ??= 'غير محدد';
        $formData['report_date'] ??= now()->toDateString();

        RemovalOrder::create($formData);

        Notification::make()
            ->title('تم حفظ قرار الإزالة بنجاح')
            ->success()
            ->send();

        return redirect()->to('/gis/my-removal-orders');
    }
}
