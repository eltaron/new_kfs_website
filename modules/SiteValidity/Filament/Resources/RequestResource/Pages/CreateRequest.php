<?php

namespace Modules\SiteValidity\Filament\Resources\RequestResource\Pages;

use App\Models\User;
use App\Rules\ValidNationalID;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\SiteValidity\Filament\Resources\RequestResource;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Models\Geo\Village;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Services\WorkflowException;
use Modules\SiteValidity\Services\WorkflowService;

/**
 * إدخال طلب جديد من اللوحة (المركز التكنولوجي / مسؤول الخدمة) نيابةً عن المواطن.
 * نفس قواعد نموذج البوابة (StoreSiteValidityRequest) ونفس WorkflowService::submit().
 * صاحب الطلب: حساب المواطن صاحب الرقم القومي إن وُجد (فيتابعه من البوابة)، وإلا حساب من أدخله.
 */
class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected static ?string $title = 'إضافة طلب صلاحية موقع';

    protected static bool $canCreateAnother = false;

    public function form(Form $form): Form
    {
        $upload = (array) config('site-validity.citizen_upload');
        $mimes = collect($upload['extensions'] ?? ['jpg', 'jpeg', 'png', 'pdf'])
            ->map(fn (string $ext) => $ext === 'pdf' ? 'application/pdf' : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext))
            ->unique()->values()->all();
        $file = fn (string $name, string $label, bool|\Closure $required = true) => Forms\Components\FileUpload::make($name)->label($label)
            ->required($required)
            ->storeFiles(false) // يُمرَّر الملف المؤقت إلى FileStore (القرص الخاص + فحص SafeUpload)
            ->acceptedFileTypes($mimes)
            ->maxSize((int) ($upload['max_kb'] ?? 5120))
            ->helperText('PDF أو صورة — حتى ' . (int) (($upload['max_kb'] ?? 5120) / 1024) . ' ميجابايت.');

        return $form->schema([
            Forms\Components\Section::make('بيانات مقدم الطلب')
                ->icon('heroicon-o-user')
                ->description('إن كان للمواطن حساب على البوابة بنفس الرقم القومي يُربط الطلب به ويتابعه من «طلباتي».')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('applicant_name')->label('اسم مقدم الطلب (رباعي)')->required()
                        ->minLength(6)->maxLength(120)->regex('/^[\p{L}\p{M}\s\.\-\']+$/u')
                        ->validationMessages(['regex' => 'الاسم يجب أن يحتوي على حروف فقط.']),
                    Forms\Components\TextInput::make('national_id')->label('الرقم القومي')->required()
                        ->length(14)->rule(new ValidNationalID())
                        ->live(onBlur: true)
                        ->hint(fn (?string $state) => strlen((string) $state) === 14 && User::query()->where('national_id', $state)->exists() ? 'له حساب على البوابة' : null)
                        ->hintColor('success')->hintIcon(fn (?string $state) => strlen((string) $state) === 14 && User::query()->where('national_id', $state)->exists() ? 'heroicon-m-check-badge' : null),
                    Forms\Components\TextInput::make('phone')->label('رقم المحمول')->required()->tel()
                        ->regex('/^01[0125][0-9]{8}$/')
                        ->validationMessages(['regex' => 'رقم محمول مصري من 11 رقماً يبدأ بـ 01.']),
                    Forms\Components\Radio::make('applicant_capacity')->label('صفة مقدم الطلب')->required()->inline()
                        ->options(SiteValidityRequest::CAPACITIES)->default(SiteValidityRequest::CAPACITY_OWNER)->live(),
                ]),

            Forms\Components\Section::make('الخدمة وموقع الأرض')
                ->icon('heroicon-o-map-pin')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('service_type_id')->label('نوع الخدمة')->required()->native(false)
                        ->options(fn () => ServiceType::query()->active()->pluck('name', 'id'))
                        ->helperText(fn ($state) => ($type = $state ? ServiceType::find($state) : null) ? 'الرسوم: ' . number_format((float) $type->fee, 2) . ' ج.م' : null)
                        ->live(),
                    Forms\Components\Radio::make('construction_type')->label('نوع الصلاحية')->required()->inline()
                        ->options(SiteValidityRequest::CONSTRUCTION_TYPES)->default(SiteValidityRequest::CONSTRUCTION_NEW)->live(),
                    Forms\Components\Select::make('center_id')->label('المركز')->required()->searchable()->native(false)
                        ->options(fn () => Centre::query()->active()->pluck('name', 'id'))
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('village_id', null)),
                    Forms\Components\Select::make('village_id')->label('المدينة / القرية')->required()->searchable()->native(false)
                        ->options(fn (Forms\Get $get) => $get('center_id')
                            ? Village::query()->active()->where('center_id', $get('center_id'))->pluck('name', 'id')
                            : [])
                        ->disabled(fn (Forms\Get $get) => ! $get('center_id'))
                        ->placeholder(fn (Forms\Get $get) => $get('center_id') ? 'اختر المدينة أو القرية' : 'اختر المركز أولاً'),
                    Forms\Components\TextInput::make('address')->label('عنوان الموقع بالتفصيل')->required()
                        ->minLength(5)->maxLength(300)->columnSpanFull()
                        ->notRegex('/<[^>]*>|javascript\s*:/i'),
                    Forms\Components\Textarea::make('notes')->label('ملاحظات')->maxLength(1000)->rows(2)->columnSpanFull()
                        ->notRegex('/<[^>]*>|javascript\s*:/i'),
                ]),

            Forms\Components\Section::make('موقع الأرض على الخريطة')
                ->icon('heroicon-o-map')
                ->description('اختياري — يظهر الطلب كنقطة على «خريطة الطلبات». يمكن تحديده لاحقاً أثناء المعاينة.')
                ->collapsible()
                ->schema(static::locationFields()),

            Forms\Components\Section::make('المستندات المطلوبة')
                ->icon('heroicon-o-paper-clip')
                ->columns(3)
                ->schema([
                    $file('sketch', RequestFile::KIND_LABELS[RequestFile::KIND_SKETCH]),
                    $file('contract', RequestFile::KIND_LABELS[RequestFile::KIND_CONTRACT] . ' (اختياري)', false),
                    $file('id_card', RequestFile::KIND_LABELS[RequestFile::KIND_ID_CARD]),
                    $file('poa', RequestFile::KIND_LABELS[RequestFile::KIND_POA], fn (Forms\Get $get) => $get('applicant_capacity') === SiteValidityRequest::CAPACITY_AGENT)
                        ->visible(fn (Forms\Get $get) => $get('applicant_capacity') === SiteValidityRequest::CAPACITY_AGENT),
                    $file('floors', RequestFile::KIND_LABELS[RequestFile::KIND_FLOORS], fn (Forms\Get $get) => $get('construction_type') === SiteValidityRequest::CONSTRUCTION_EXTENSION)
                        ->visible(fn (Forms\Get $get) => $get('construction_type') === SiteValidityRequest::CONSTRUCTION_EXTENSION),
                ]),

            Forms\Components\Checkbox::make('agree')->label('تم مراجعة أصول المستندات مع المواطن والتأكد من صحة البيانات')->accepted(),
        ]);
    }

    /**
     * حقلا الإحداثيات (مخفيان) + منتقي الخريطة — مشتركة مع إجراء «تحديد موقع الأرض».
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    public static function locationFields(?string $centreName = null, bool $required = false): array
    {
        [$minLat, $maxLat, $minLng, $maxLng] = \Modules\SiteValidity\Models\SiteValidityRequest::KFS_BOUNDS;

        return [
            Forms\Components\Hidden::make('latitude')->rules(['nullable', 'numeric', "between:{$minLat},{$maxLat}", 'required_with:longitude'])
                ->required($required)->validationMessages(['required' => 'حدد موقع الأرض على الخريطة.', 'between' => 'الموقع خارج حدود المحافظة.']),
            Forms\Components\Hidden::make('longitude')->rules(['nullable', 'numeric', "between:{$minLng},{$maxLng}", 'required_with:latitude']),
            Forms\Components\ViewField::make('location_picker')->hiddenLabel()->dehydrated(false)
                ->view('sitevalidity::filament.location-picker', ['centreName' => $centreName, 'watchCentre' => $centreName === null]),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $staff = auth()->user();
        $centre = Centre::query()->findOrFail($data['center_id']);
        $village = Village::query()->where('center_id', $centre->id)->findOrFail($data['village_id']);
        $owner = User::query()->where('national_id', $data['national_id'])->first() ?? $staff;

        try {
            return app(WorkflowService::class)->submit($owner, [
                'service_type_id' => (int) $data['service_type_id'],
                'construction_type' => $data['construction_type'] ?? SiteValidityRequest::CONSTRUCTION_NEW,
                'applicant_capacity' => $data['applicant_capacity'] ?? SiteValidityRequest::CAPACITY_OWNER,
                'applicant_name' => trim($data['applicant_name']),
                'national_id' => $data['national_id'],
                'phone' => $data['phone'],
                'center_id' => $centre->id,
                'village_id' => $village->id,
                'center_name' => $centre->name,
                'village_name' => $village->name,
                'address' => trim($data['address']),
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'latitude' => filled($data['latitude'] ?? null) ? (float) $data['latitude'] : null,
                'longitude' => filled($data['longitude'] ?? null) ? (float) $data['longitude'] : null,
            ], [
                RequestFile::KIND_SKETCH => $this->uploaded($data['sketch'] ?? null),
                RequestFile::KIND_CONTRACT => $this->uploaded($data['contract'] ?? null),
                RequestFile::KIND_ID_CARD => $this->uploaded($data['id_card'] ?? null),
                RequestFile::KIND_POA => $this->uploaded($data['poa'] ?? null),
                RequestFile::KIND_FLOORS => $this->uploaded($data['floors'] ?? null),
            ], $staff);
        } catch (WorkflowException $e) {
            Notification::make()->danger()->title('تعذر حفظ الطلب')->body($e->getMessage())->send();

            throw new Halt();
        }
    }

    /** حالة FileUpload مع storeFiles(false) قد تكون الملف نفسه أو مصفوفة [uuid => ملف]. */
    private function uploaded(mixed $state): ?UploadedFile
    {
        $file = is_array($state) ? Arr::first($state) : $state;

        return $file instanceof UploadedFile ? $file : null;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()->success()
            ->title('تم تسجيل الطلب ' . $this->record->number)
            ->body($this->record->user_id === auth()->id()
                ? 'لا يوجد حساب للمواطن على البوابة — الطلب مسجل باسم المركز التكنولوجي.'
                : 'تم ربط الطلب بحساب المواطن على البوابة.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
