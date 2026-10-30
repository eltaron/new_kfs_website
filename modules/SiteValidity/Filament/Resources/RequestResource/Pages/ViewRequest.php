<?php

namespace Modules\SiteValidity\Filament\Resources\RequestResource\Pages;

use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\SiteValidity\Filament\Resources\RequestResource;
use Modules\SiteValidity\Models\AttachmentType;
use Modules\SiteValidity\Models\InspectionField;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Services\FileStore;
use Modules\SiteValidity\Services\WorkflowException;
use Modules\SiteValidity\Services\WorkflowService;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvStatus;

/**
 * شاشة الطلب + «شاشة المعاينة»: الأزرار تظهر حسب الدور والحالة (WorkflowService::can)
 * وكل تنفيذ يمر عبر WorkflowService (التحقق، الخط الزمني، الإشعارات).
 */
class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    public function getTitle(): string
    {
        return 'الطلب ' . $this->getRecord()->number;
    }

    public function getSubheading(): ?string
    {
        return SvStatus::label($this->getRecord()->status);
    }

    protected function wf(): WorkflowService
    {
        return app(WorkflowService::class);
    }

    protected function req(): SiteValidityRequest
    {
        /** @var SiteValidityRequest */
        return $this->getRecord();
    }

    protected function allowed(string $action): bool
    {
        return $this->wf()->can($this->req(), auth()->user(), $action);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('set_appointment')
                ->label(fn () => $this->req()->appointment_at ? 'تعديل موعد المعاينة' : 'تحديد موعد المعاينة')
                ->icon('heroicon-o-calendar-days')->color('primary')
                ->visible(fn () => $this->allowed('set_appointment'))
                ->fillForm(fn () => ['appointment_at' => $this->req()->appointment_at, 'notes' => $this->req()->appointment_notes])
                ->form([
                    Forms\Components\DateTimePicker::make('appointment_at')->label('موعد المعاينة')
                        ->required()->seconds(false)->minDate(now()->startOfDay())
                        ->helperText(fn () => ($s = \Modules\SiteValidity\Support\SvSchedule::forCentre($this->req()->center_id))
                            ? 'أيام معاينات مركز ' . $this->req()->center_name . ': ' . $s->daysLabel() . ' — من ' . substr((string) $s->start_time, 0, 5)
                            : 'لم تُحدد أيام معاينة لهذا المركز بعد («أيام المعاينة للمراكز»).'),
                    Forms\Components\Textarea::make('notes')->label('ملاحظات للمواطن (مكان التجمع، المستندات الأصلية...)')->maxLength(500)->rows(2),
                ])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->setAppointment($this->req(), auth()->user(), $data['appointment_at'], $data['notes'] ?? null),
                    'تم حفظ موعد المعاينة وإخطار المواطن ومهندس التنظيم.'
                )),

            Action::make('approve')
                ->label('موافقة')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn () => $this->allowed('approve'))
                ->requiresConfirmation()
                ->modalDescription('اعتماد الطلب وموعد المعاينة.')
                ->form([Forms\Components\Textarea::make('notes')->label('ملاحظات (اختياري)')->maxLength(500)->rows(2)])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->approve($this->req(), auth()->user(), $data['notes'] ?? null),
                    'تمت الموافقة على الطلب.'
                )),

            Action::make('engineer_reschedule')
                ->label('رفض الموعد واختيار يوم آخر')->icon('heroicon-o-calendar')->color('warning')
                ->visible(fn () => $this->allowed('engineer_reschedule'))
                ->modalDescription(fn () => 'الأيام المتاحة لمعاينات مركز ' . $this->req()->center_name . ' (من «أيام المعاينة للمراكز»). يُعتمد الطلب بالموعد الجديد ويُطلب من المواطن تأكيده.')
                ->form(fn () => [
                    Forms\Components\Radio::make('date')->label('اليوم المناسب')->required()
                        ->options(\Modules\SiteValidity\Support\SvSchedule::availableDates($this->req()))
                        ->helperText(fn () => \Modules\SiteValidity\Support\SvSchedule::availableDates($this->req()) === [] ? 'لا توجد أيام متاحة لهذا المركز — يحددها المركز الجيومكاني من «أيام المعاينة للمراكز».' : null),
                    Forms\Components\Textarea::make('notes')->label('سبب تغيير الموعد (اختياري)')->maxLength(500)->rows(2),
                ])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->engineerReschedule($this->req(), auth()->user(), (string) $data['date'], $data['notes'] ?? null),
                    'تم تغيير موعد المعاينة وإخطار المواطن لتأكيده.'
                )),

            Action::make('geo_inspection')
                ->label('تمت المعاينة (المركز الجيومكاني)')->icon('heroicon-o-map')->color('success')
                ->visible(fn () => $this->allowed('geo_inspection'))
                ->requiresConfirmation()
                ->form([Forms\Components\Textarea::make('notes')->label('ملاحظات المعاينة (اختياري)')->maxLength(1000)->rows(2)])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->markGeoInspection($this->req(), auth()->user(), $data['notes'] ?? null),
                    'تم تسجيل المعاينة.'
                )),

            Action::make('engineer_inspection')
                ->label('استمارة المعاينة — تمت المعاينة')->icon('heroicon-o-clipboard-document-check')->color('success')
                ->visible(fn () => $this->allowed('engineer_inspection'))
                ->modalWidth('3xl')
                ->form(fn () => $this->inspectionSchema())
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->submitEngineerInspection($this->req(), auth()->user(), (array) ($data['answers'] ?? []), $data['notes'] ?? null, $data['existing_building_height'] ?? null),
                    'تم حفظ استمارة المعاينة.'
                )),

            Action::make('edit_inspection')
                ->label('تعديل استمارة المعاينة')->icon('heroicon-o-pencil-square')->color('gray')
                ->visible(fn () => $this->allowed('edit_inspection'))
                ->modalWidth('3xl')
                ->modalDescription(fn () => $this->req()->inspection_updated_at
                    ? 'آخر تعديل: ' . $this->req()->inspection_updated_at->format('Y-m-d H:i') . ' — يُسجل كل تعديل في الخط الزمني.'
                    : 'يُسجل وقت التعديل والبنود المعدلة في الخط الزمني.')
                ->fillForm(fn () => [
                    'answers' => collect((array) $this->req()->inspection_data)->mapWithKeys(fn ($row) => [$row['key'] ?? '' => $row['value'] ?? null])->all(),
                    'notes' => $this->req()->inspection_notes,
                    'existing_building_height' => $this->req()->existing_building_height,
                ])
                ->form(fn () => $this->inspectionSchema())
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->editEngineerInspection($this->req(), auth()->user(), (array) ($data['answers'] ?? []), $data['notes'] ?? null, $data['existing_building_height'] ?? null),
                    'تم تعديل استمارة المعاينة وتسجيل وقت التعديل.'
                )),

            Action::make('upload_attachments')
                ->label('رفع المرفقات')->icon('heroicon-o-arrow-up-tray')->color('primary')
                ->visible(fn () => $this->allowed('upload_attachments'))
                ->modalWidth('3xl')
                ->form(fn () => $this->attachmentsSchema())
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->uploadAttachments($this->req(), auth()->user(), array_filter((array) ($data['files'] ?? [])), $data['notes'] ?? null),
                    'تم رفع المرفقات وإحالة الطلب لمهندس التنظيم.'
                )),

            Action::make('request_completion')
                ->label('استيفاء')->icon('heroicon-o-arrow-uturn-left')->color('warning')
                ->visible(fn () => $this->allowed('request_completion'))
                ->modalDescription(fn () => $this->req()->status === SvStatus::INSPECTED
                    ? 'يعود الطلب للمواطن بالمطلوب، ثم يرجع إليك لاستكمال رفع المرفقات بعد إرسال الاستيفاء.'
                    : 'يعود الطلب للمواطن بالمطلوب، ثم يرجع إليك بعد إرسال الاستيفاء.')
                ->form([
                    Forms\Components\Radio::make('type')->label('نوع الاستيفاء')->required()->inline()
                        ->options(\Modules\SiteValidity\Models\Completion::TYPES)->default(\Modules\SiteValidity\Models\Completion::TYPE_DOCUMENTS)->live(),
                    Forms\Components\TextInput::make('amount')->label('المبلغ المطلوب سداده')->numeric()->minValue(1)->maxValue(1000000)->suffix('ج.م')
                        ->required(fn (Forms\Get $get) => $get('type') === \Modules\SiteValidity\Models\Completion::TYPE_PAYMENT)
                        ->visible(fn (Forms\Get $get) => $get('type') === \Modules\SiteValidity\Models\Completion::TYPE_PAYMENT)
                        ->helperText('يسدده المواطن إلكترونياً من صفحة الطلب، ويعود الطلب إليك تلقائياً بعد السداد.'),
                    Forms\Components\Textarea::make('notes')->label(fn (Forms\Get $get) => $get('type') === \Modules\SiteValidity\Models\Completion::TYPE_PAYMENT ? 'سبب الرسوم (يظهر للمواطن)' : 'المطلوب من المواطن')
                        ->required()->minLength(5)->maxLength(2000)->rows(3),
                    Forms\Components\TagsInput::make('items')->label('قائمة المستندات/البيانات المطلوبة (اختياري)')
                        ->visible(fn (Forms\Get $get) => $get('type') !== \Modules\SiteValidity\Models\Completion::TYPE_PAYMENT)
                        ->placeholder('اكتب واضغط Enter')->suggestions(['صورة عقد مسجل', 'صورة بطاقة سارية', 'كروكي معتمد من مهندس', 'إيصال سداد الضريبة العقارية']),
                ])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->requestCompletion($this->req(), auth()->user(), (string) $data['notes'], (array) ($data['items'] ?? []),
                        (string) ($data['type'] ?? \Modules\SiteValidity\Models\Completion::TYPE_DOCUMENTS), $data['amount'] ?? null),
                    'تم إرسال طلب الاستيفاء للمواطن.'
                )),

            Action::make('complete')
                ->label('رفع الملف النهائي (لاعتماد مدير التنظيم)')->icon('heroicon-o-document-arrow-up')->color('success')
                ->modalDescription('يُرسل الملف لمدير التنظيم لاعتماد الصلاحية — لا يظهر للمواطن قبل الاعتماد.')
                ->visible(fn () => $this->allowed('complete'))
                ->form([
                    $this->upload('final')->label('الملف النهائي')->required()->multiple(false)
                        ->maxSize((int) config('site-validity.final_upload.max_kb'))
                        ->storeFileNamesIn('final_name')
                        ->helperText('المسموح: ' . implode('، ', (array) config('site-validity.final_upload.extensions'))),
                    Forms\Components\Textarea::make('notes')->label('ملاحظات الانتهاء (تظهر للمواطن)')->maxLength(1000)->rows(2),
                ])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->complete($this->req(), auth()->user(), (string) $data['final'], $data['notes'] ?? null, isset($data['final_name']) && is_string($data['final_name']) ? $data['final_name'] : null),
                    'تم رفع الملف النهائي وإرساله لمدير التنظيم للاعتماد.'
                )),

            Action::make('director_approve')
                ->label('اعتماد الصلاحية وإنهاء الطلب')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn () => $this->allowed('director_approve'))
                ->requiresConfirmation()
                ->modalDescription('يُنهى الطلب ويُتاح الملف النهائي للمواطن مع كود استلام المعاملة من المركز التكنولوجي.')
                ->form([Forms\Components\Textarea::make('notes')->label('ملاحظات (اختياري)')->maxLength(1000)->rows(2)])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->directorApprove($this->req(), auth()->user(), $data['notes'] ?? null),
                    'تم اعتماد الصلاحية وإنهاء الطلب.'
                )),

            Action::make('director_return')
                ->label('رفض وإعادة للمهندس')->icon('heroicon-o-arrow-uturn-right')->color('danger')
                ->visible(fn () => $this->allowed('director_return'))
                ->form([Forms\Components\Textarea::make('notes')->label('سبب الرفض (يظهر للمهندس)')->required()->minLength(5)->maxLength(2000)->rows(3)])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->directorReturn($this->req(), auth()->user(), (string) $data['notes']),
                    'تم رفض الطلب وإعادته لمهندس التنظيم مع السبب.'
                )),

            Action::make('reject')
                ->label('رفض')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => $this->allowed('reject'))
                ->requiresConfirmation()
                ->form([Forms\Components\Textarea::make('reason')->label('سبب الرفض (يظهر للمواطن)')->required()->minLength(5)->maxLength(1000)])
                ->action(fn (array $data) => $this->run(
                    fn () => $this->wf()->reject($this->req(), auth()->user(), (string) $data['reason']),
                    'تم رفض الطلب.'
                )),

            Action::make('set_location')
                ->label(fn () => $this->req()->hasLocation() ? 'تعديل موقع الأرض' : 'تحديد موقع الأرض')
                ->icon('heroicon-o-map-pin')->color('gray')
                ->visible(fn () => ! $this->req()->isClosed() && (SvAccess::isAdmin(auth()->user()) || SvAccess::isGeo(auth()->user()) || SvAccess::isEngineer(auth()->user())))
                ->modalHeading(fn () => 'موقع الأرض — ' . $this->req()->locationLabel())
                ->modalDescription('اضغط على الخريطة (صور فضائية) في مكان الأرض، أو استخدم «موقعي الحالي» أثناء المعاينة الميدانية.')
                ->modalWidth('4xl')
                ->fillForm(fn () => ['latitude' => $this->req()->latitude, 'longitude' => $this->req()->longitude])
                ->form(fn () => CreateRequest::locationFields($this->req()->center_name, required: true))
                ->action(function (array $data) {
                    $r = $this->req();
                    $old = $r->hasLocation() ? sprintf('%.6f, %.6f', $r->latitude, $r->longitude) : null;
                    $r->forceFill(['latitude' => (float) $data['latitude'], 'longitude' => (float) $data['longitude']])->save();
                    $this->wf()->log($r, auth()->user(), 'set_location', $r->status, $r->status, null, array_filter([
                        'location' => sprintf('%.6f, %.6f', $r->latitude, $r->longitude),
                        'previous' => $old,
                    ]));
                    Notification::make()->success()->title('تم حفظ موقع الأرض.')->send();
                }),

            Actions\ActionGroup::make([
                Action::make('assign')
                    ->label('إسناد')->icon('heroicon-o-user-plus')
                    ->visible(fn () => $this->allowed('assign'))
                    ->fillForm(fn () => ['geo_user_id' => $this->req()->geo_user_id, 'engineer_user_id' => $this->req()->engineer_user_id])
                    ->form([
                        Forms\Components\Select::make('geo_user_id')->label('حساب المركز الجيومكاني')
                            ->options(fn () => $this->usersFor(SvAccess::GEO))->placeholder('كل حسابات المركز'),
                        Forms\Components\Select::make('engineer_user_id')->label('مهندس التنظيم')
                            ->options(fn () => $this->usersFor(SvAccess::ENGINEER))->placeholder('كل المهندسين'),
                    ])
                    ->action(fn (array $data) => $this->run(
                        fn () => $this->wf()->assign($this->req(), auth()->user(), $data['geo_user_id'] ?? null, $data['engineer_user_id'] ?? null),
                        'تم الإسناد.'
                    )),
                Action::make('override')
                    ->label('تعديل الحالة إدارياً')->icon('heroicon-o-adjustments-horizontal')->color('danger')
                    ->visible(fn () => $this->allowed('override'))
                    ->form([
                        Forms\Components\Select::make('status')->label('الحالة الجديدة')->options(SvStatus::options())->required(),
                        Forms\Components\Textarea::make('notes')->label('السبب (إلزامي — يُسجل في سجل التدقيق)')->required()->minLength(5)->maxLength(1000),
                    ])
                    ->action(fn (array $data) => $this->run(
                        fn () => $this->wf()->override($this->req(), auth()->user(), (string) $data['status'], (string) $data['notes']),
                        'تم تعديل الحالة.'
                    )),
                Actions\EditAction::make()->label('تعديل البيانات')
                    ->visible(fn () => SvAccess::isAdmin(auth()->user())),
            ])->label('إدارة')->icon('heroicon-o-cog-6-tooth')->color('gray')->button()
                ->visible(fn () => SvAccess::isAdmin(auth()->user())),
        ];
    }

    /** @return array<\Filament\Forms\Components\Component> */
    protected function inspectionSchema(): array
    {
        $fields = InspectionField::query()->active()->get()->map(function (InspectionField $f) {
            $name = 'answers.' . $f->key;
            $component = match ($f->type) {
                'yes_no' => Forms\Components\Radio::make($name)->options(['yes' => 'نعم', 'no' => 'لا'])->inline(),
                'number' => Forms\Components\TextInput::make($name)->numeric(),
                'select' => Forms\Components\Select::make($name)->options(array_combine($f->optionList(), $f->optionList()) ?: []),
                'textarea' => Forms\Components\Textarea::make($name)->rows(3)->maxLength(3000)->columnSpanFull(),
                default => Forms\Components\TextInput::make($name)->maxLength(500),
            };

            return $component->label($f->label)->required($f->is_required)->helperText($f->help_text);
        })->all();

        if ($this->req()->construction_type === SiteValidityRequest::CONSTRUCTION_EXTENSION) {
            $fields[] = Forms\Components\TextInput::make('existing_building_height')->label('ارتفاع المبنى القائم (متر)')
                ->numeric()->minValue(0)->maxValue(300)->step(0.01)->suffix('م')
                ->helperText('طلب تعلية — ارتفاع المبنى القائم حالياً على الطبيعة.');
        }

        return [
            Forms\Components\Section::make('بيانات الموقع')->columns(2)->schema($fields ?: [
                Forms\Components\Placeholder::make('none')->content('لا توجد بنود معاينة مفعلة — يضيفها مسؤول الخدمة من «بنود المعاينة».'),
            ]),
            Forms\Components\Textarea::make('notes')->label('ملاحظات المهندس')->maxLength(2000)->rows(3),
        ];
    }

    /** @return array<\Filament\Forms\Components\Component> */
    protected function attachmentsSchema(): array
    {
        $existing = $this->req()->files()->where('kind', RequestFile::KIND_ATTACHMENT)->pluck('attachment_type_id')->all();

        $fields = AttachmentType::query()->active()->get()->map(function (AttachmentType $t) use ($existing) {
            $already = in_array($t->getKey(), $existing, false);

            return $this->upload('files.' . $t->getKey())
                ->label($t->name . ($t->is_required ? '' : ' (اختياري)'))
                ->multiple()->maxFiles(10)
                ->maxSize((int) $t->max_size_kb)
                ->required($t->is_required && ! $already)
                ->helperText(trim(($t->description ? $t->description . ' — ' : '') . 'المسموح: ' . implode('، ', $t->allowedExtensions()) . ($already ? ' — (تم رفعه سابقاً)' : '')));
        })->all();

        return [
            ...$fields,
            Forms\Components\Textarea::make('notes')->label('ملاحظات')->maxLength(1000)->rows(2),
        ];
    }

    protected function upload(string $name): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($name)
            ->disk((string) config('site-validity.disk', 'local'))
            ->directory(FileStore::TMP_DIR)
            ->visibility('private')
            ->downloadable(false)
            ->previewable(false)
            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => Str::random(32) . '.' . strtolower((string) $file->getClientOriginalExtension()));
    }

    /** @return array<string, string> */
    protected function usersFor(string $role): array
    {
        try {
            return User::role($role)->orderBy('name')->pluck('name', 'id')->all();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function run(callable $operation, string $success): void
    {
        try {
            $operation();
        } catch (WorkflowException $e) {
            Notification::make()->danger()->title('تعذر تنفيذ الإجراء')->body($e->getMessage())->persistent()->send();

            return;
        }

        Notification::make()->success()->title($success)->send();
        $this->redirect(RequestResource::getUrl('view', ['record' => $this->req()]));
    }
}
