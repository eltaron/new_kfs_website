<?php

namespace Modules\SiteValidity\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\SiteValidity\Events\RequestTransitioned;
use Modules\SiteValidity\Models\AttachmentType;
use Modules\SiteValidity\Models\Completion;
use Modules\SiteValidity\Models\InspectionField;
use Modules\SiteValidity\Models\RequestEvent;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvSchedule;
use Modules\SiteValidity\Support\SvSettings;
use Modules\SiteValidity\Support\SvStatus;

/**
 * آلة الحالات الوحيدة لطلبات صلاحية الموقع: كل انتقال يمر من هنا،
 * ويُتحقق فيه من (الدور × الحالة الحالية × قواعد العمل)، ويُسجل في الخط الزمني،
 * ثم يُطلق RequestTransitioned (الإشعارات) بعد اعتماد المعاملة.
 *
 * المسار:
 *   submitted ──(المركز الجيومكاني: موعد)──▶ appointment_set ──(المهندس: موافقة)──▶ approved
 *   approved ──(الجيومكاني «تمت المعاينة» + المهندس «استمارة المعاينة» — الاثنان معاً)──▶ inspected
 *   inspected ──(الجيومكاني: رفع المرفقات المطلوبة)──▶ attachments_uploaded
 *   inspected ──(الجيومكاني: استيفاء)──▶ completion_requested ──(المواطن)──▶ inspected (يعود للجيومكاني لاستكمال الرفع)
 *   attachments_uploaded | completion_submitted ──(المهندس: استيفاء)──▶ completion_requested ──(المواطن)──▶ completion_submitted
 *   attachments_uploaded | completion_submitted ──(المهندس: تم الانتهاء + الملف النهائي)──▶ completed
 *   appointment_set | approved | attachments_uploaded | completion_submitted ──(المهندس: رفض)──▶ rejected
 *   مسؤول الخدمة: يستطيع تنفيذ أي إجراء للموظفين + تعديل الحالة إدارياً (override) + الإسناد.
 */
class WorkflowService
{
    /** action => [roles allowed (admin implicit), from statuses] */
    public const TRANSITIONS = [
        'set_appointment' => [[SvAccess::GEO], [SvStatus::SUBMITTED, SvStatus::APPOINTMENT_SET, SvStatus::APPROVED]],
        'approve' => [[SvAccess::ENGINEER], [SvStatus::APPOINTMENT_SET]],
        // رفض المهندس للموعد: يختار يوماً متاحاً من أيام المركز (يُعد موافقة بالموعد الجديد)
        'engineer_reschedule' => [[SvAccess::ENGINEER], [SvStatus::APPOINTMENT_SET, SvStatus::APPROVED]],
        // المواطن: قبول موعد المعاينة بعد موافقة المهندس، أو رفضه واختيار يوم متاح
        'confirm_appointment' => [['citizen'], [SvStatus::APPROVED]],
        'citizen_reschedule' => [['citizen'], [SvStatus::APPROVED]],
        'reject' => [[SvAccess::ENGINEER], [SvStatus::APPOINTMENT_SET, SvStatus::APPROVED, SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_SUBMITTED]],
        'geo_inspection' => [[SvAccess::GEO], [SvStatus::APPROVED]],
        'engineer_inspection' => [[SvAccess::ENGINEER], [SvStatus::APPROVED]],
        // تعديل استمارة المعاينة بعد حفظها (حتى صدور القرار) — يُسجل وقت التعديل والبنود المعدلة
        'edit_inspection' => [[SvAccess::ENGINEER], [SvStatus::APPROVED, SvStatus::INSPECTED, SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_REQUESTED, SvStatus::COMPLETION_SUBMITTED, SvStatus::FINAL_UPLOADED]],
        'upload_attachments' => [[SvAccess::GEO], [SvStatus::INSPECTED]],
        'request_completion' => [[SvAccess::GEO, SvAccess::ENGINEER], [SvStatus::INSPECTED, SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_SUBMITTED]],
        // المهندس يرفع الملف النهائي ← مدير التنظيم يعتمد (ينهي الطلب) أو يرفض فيعود للمهندس مع السبب
        'complete' => [[SvAccess::ENGINEER], [SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_SUBMITTED]],
        'director_approve' => [[SvAccess::DIRECTOR], [SvStatus::FINAL_UPLOADED]],
        'director_return' => [[SvAccess::DIRECTOR], [SvStatus::FINAL_UPLOADED]],
        'submit_completion' => [['citizen'], [SvStatus::COMPLETION_REQUESTED]],
        'override' => [[], []],
        'assign' => [[], []],
    ];

    public function __construct(protected FileStore $files)
    {
    }

    // ───────────────────────────────────────────── permissions

    /** هل يستطيع المستخدم تنفيذ الإجراء على الطلب الآن؟ (يُستخدم لإظهار الأزرار وفي الحماية). */
    public function can(SiteValidityRequest $request, ?User $user, string $action): bool
    {
        if ($user === null || ! isset(self::TRANSITIONS[$action])) {
            return false;
        }

        [$roles, $from] = self::TRANSITIONS[$action];

        if ($action === 'submit_completion') {
            return $request->isOwnedBy($user) && in_array($request->status, $from, true)
                && ! $request->openCompletion()?->isPayment(); // استيفاء الرسوم يُغلق بالسداد لا بالرد
        }
        if (in_array($action, ['confirm_appointment', 'citizen_reschedule'], true)) {
            return $request->isOwnedBy($user) && in_array($request->status, $from, true)
                && $request->appointment_confirmation === SiteValidityRequest::CONFIRMATION_PENDING
                && $request->geo_inspected_at === null && $request->engineer_inspected_at === null;
        }
        if (in_array($action, ['override', 'assign'], true)) {
            return SvAccess::isAdmin($user);
        }

        $roleOk = SvAccess::isAdmin($user)
            || (in_array(SvAccess::GEO, $roles, true) && SvAccess::isGeo($user))
            || (in_array(SvAccess::ENGINEER, $roles, true) && SvAccess::isEngineer($user))
            || (in_array(SvAccess::DIRECTOR, $roles, true) && SvAccess::isDirector($user));

        if (! $roleOk || ! in_array($request->status, $from, true)) {
            return false;
        }

        return match ($action) {
            // إعادة الجدولة ممكنة فقط قبل تسجيل أي علامة معاينة.
            'set_appointment', 'engineer_reschedule' => $request->geo_inspected_at === null && $request->engineer_inspected_at === null,
            'geo_inspection' => $request->geo_inspected_at === null,
            'engineer_inspection' => $request->engineer_inspected_at === null,
            'edit_inspection' => $request->engineer_inspected_at !== null,
            // الجيومكاني يطلب الاستيفاء أثناء رفع المرفقات فقط، والمهندس بعدها (مسؤول الخدمة في المرحلتين).
            'request_completion' => SvAccess::isAdmin($user)
                || ($request->status === SvStatus::INSPECTED ? SvAccess::isGeo($user) : SvAccess::isEngineer($user)),
            default => true,
        };
    }

    /** @return list<string> الإجراءات المتاحة للمستخدم على الطلب. */
    public function availableActions(SiteValidityRequest $request, ?User $user): array
    {
        return array_values(array_filter(array_keys(self::TRANSITIONS), fn ($a) => $this->can($request, $user, $a)));
    }

    protected function authorize(SiteValidityRequest $request, User $user, string $action): void
    {
        if ($this->can($request, $user, $action)) {
            return;
        }

        [$roles, $from] = self::TRANSITIONS[$action] ?? [[], []];
        if ($from !== [] && ! in_array($request->status, $from, true)) {
            throw new WorkflowException('لا يمكن تنفيذ هذا الإجراء والطلب في حالة «' . SvStatus::label($request->status) . '».');
        }

        throw new WorkflowException('ليست لديك صلاحية تنفيذ هذا الإجراء على هذا الطلب.');
    }

    // ───────────────────────────────────────────── citizen submission

    /**
     * @param  array{service_type_id:int, construction_type?:string, applicant_capacity?:string, applicant_name:string, national_id:string, phone:?string, center_id:int, village_id:int, center_name:string, village_name:string, address:?string, notes:?string}  $data
     * @param  array{sketch: UploadedFile|string, contract?: UploadedFile|string|null, id_card: UploadedFile|string, poa?: UploadedFile|string|null, floors?: UploadedFile|string|null}  $files
     *         عقد الملكية اختياري؛ التوكيل إلزامي للوكيل؛ موقف الأدوار القائمة إلزامي للتعلية.
     */
    /**
     * @param  User|null  $enteredBy  موظف المركز التكنولوجي عند الإدخال من اللوحة (null = المواطن نفسه من البوابة)
     */
    public function submit(User $citizen, array $data, array $files, ?User $enteredBy = null): SiteValidityRequest
    {
        $actor = $enteredBy ?? $citizen;

        $type = ServiceType::query()->whereKey($data['service_type_id'] ?? 0)->where('is_active', true)->first()
            ?? throw new WorkflowException('نوع الخدمة غير متاح.');

        $data['applicant_capacity'] ??= SiteValidityRequest::CAPACITY_OWNER;
        $data['construction_type'] ??= SiteValidityRequest::CONSTRUCTION_NEW;
        if (! isset(SiteValidityRequest::CAPACITIES[$data['applicant_capacity']])) {
            throw new WorkflowException('صفة مقدم الطلب غير صحيحة.');
        }
        if (! isset(SiteValidityRequest::CONSTRUCTION_TYPES[$data['construction_type']])) {
            throw new WorkflowException('نوع الصلاحية غير صحيح.');
        }

        $required = [RequestFile::KIND_SKETCH, RequestFile::KIND_ID_CARD];
        if ($data['applicant_capacity'] === SiteValidityRequest::CAPACITY_AGENT) {
            $required[] = RequestFile::KIND_POA;
        }
        if ($data['construction_type'] === SiteValidityRequest::CONSTRUCTION_EXTENSION) {
            $required[] = RequestFile::KIND_FLOORS;
        }
        // ملفات لا تخص الحالة (توكيل لمالك / موقف أدوار لإنشاء جديد) تُتجاهل
        $allowed = array_merge($required, [RequestFile::KIND_CONTRACT]);
        $files = array_filter(array_intersect_key($files, array_flip($allowed)));

        $upload = (array) config('site-validity.citizen_upload');
        foreach ($required as $kind) {
            if (empty($files[$kind])) {
                throw new WorkflowException('الملف «' . RequestFile::KIND_LABELS[$kind] . '» مطلوب.');
            }
        }
        foreach ($files as $kind => $file) {
            $this->files->validate($file, $upload['extensions'], (int) $upload['max_kb'], RequestFile::KIND_LABELS[$kind]);
        }

        $request = DB::transaction(function () use ($citizen, $actor, $data, $files, $type) {
            $request = new SiteValidityRequest(collect($data)->only([
                'service_type_id', 'construction_type', 'applicant_name', 'national_id', 'phone', 'applicant_capacity',
                'center_id', 'village_id', 'center_name', 'village_name', 'address', 'notes', 'latitude', 'longitude',
            ])->all());
            $fee = (float) $type->fee > 0 ? $type->fee : SvSettings::defaultFee();
            $request->forceFill([
                'user_id' => $citizen->getKey(),
                'created_by' => $actor->getKey(),
                'status' => SvStatus::SUBMITTED,
                'fee' => $fee,
                'submitted_at' => now(),
            ])->save();

            $request->forceFill(['number' => sprintf('SV-%s-%06d', now()->format('Y'), $request->getKey())])->save();

            foreach (RequestFile::CITIZEN_KINDS as $kind) {
                if (! empty($files[$kind])) {
                    $this->files->store($request, $files[$kind], $kind, $actor);
                }
            }

            $this->log($request, $actor, 'submitted', null, SvStatus::SUBMITTED, null, array_filter([
                'service' => $type->name,
                'construction_type' => $request->constructionTypeLabel(),
                'capacity' => $request->capacityLabel(),
                'fee' => (string) $fee,
                'entered_by' => $actor === $citizen ? null : 'المركز التكنولوجي — ' . $actor->name,
            ]));

            return $request;
        });

        RequestTransitioned::dispatch($request, 'submitted', null, SvStatus::SUBMITTED, $actor, null);

        return $request;
    }

    // ───────────────────────────────────────────── geospatial centre

    public function setAppointment(SiteValidityRequest $request, User $actor, CarbonInterface|string $at, ?string $notes = null): SiteValidityRequest
    {
        $at = $at instanceof CarbonInterface ? Carbon::instance($at) : Carbon::parse($at);

        return $this->transition($request, $actor, 'set_appointment', function (SiteValidityRequest $r) use ($at, $notes) {
            if ($at->lessThanOrEqualTo(now())) {
                throw new WorkflowException('يجب أن يكون موعد المعاينة في المستقبل.');
            }
            if (SvSettings::paymentRequired() && ! $r->isPaid() && (float) $r->fee > 0) {
                throw new WorkflowException('إعداد «السداد قبل المعالجة» مفعل: لا يمكن تحديد موعد قبل سداد المواطن للرسوم.');
            }

            $isReschedule = $r->status !== SvStatus::SUBMITTED;
            $r->forceFill([
                'appointment_at' => $at,
                'appointment_notes' => $this->clean($notes),
                'status' => $r->status === SvStatus::SUBMITTED ? SvStatus::APPOINTMENT_SET : $r->status,
                // تغيير الموعد بعد موافقة المهندس يحتاج تأكيد المواطن من جديد
                'appointment_confirmation' => $r->status === SvStatus::APPROVED ? SiteValidityRequest::CONFIRMATION_PENDING : null,
                'appointment_confirmed_at' => null,
            ]);

            return [$isReschedule ? 'reschedule' : 'set_appointment', ['appointment_at' => $at->format('Y-m-d H:i')]];
        }, $notes);
    }

    public function markGeoInspection(SiteValidityRequest $request, User $actor, ?string $notes = null): SiteValidityRequest
    {
        return $this->transition($request, $actor, 'geo_inspection', function (SiteValidityRequest $r) use ($actor, $notes) {
            $this->assertAppointmentReached($r, $actor);
            $r->forceFill(['geo_inspected_at' => now(), 'geo_inspection_notes' => $this->clean($notes)]);
            if ($r->engineer_inspected_at !== null) {
                $r->status = SvStatus::INSPECTED;
            }

            return ['geo_inspection', []];
        }, $notes);
    }

    /**
     * @param  array<string, UploadedFile|string|list<UploadedFile|string>>  $filesByType  attachment_type_id|key => file(s)
     */
    public function uploadAttachments(SiteValidityRequest $request, User $actor, array $filesByType, ?string $notes = null): SiteValidityRequest
    {
        return $this->transition($request, $actor, 'upload_attachments', function (SiteValidityRequest $r) use ($actor, $filesByType) {
            $types = AttachmentType::query()->active()->get();
            $byKey = [];
            foreach ($types as $t) {
                $byKey['id:' . $t->getKey()] = $t;
                $byKey['key:' . $t->key] = $t;
            }

            $normalized = [];
            foreach ($filesByType as $typeKey => $files) {
                $type = $byKey['id:' . $typeKey] ?? $byKey['key:' . $typeKey] ?? throw new WorkflowException('نوع مرفق غير معروف.');
                foreach (array_filter(is_array($files) ? array_values($files) : [$files]) as $file) {
                    $this->files->validate($file, $type->allowedExtensions(), (int) $type->max_size_kb, $type->name);
                    $normalized[] = [$type, $file];
                }
            }

            $existing = $r->files()->where('kind', RequestFile::KIND_ATTACHMENT)->pluck('attachment_type_id')->all();
            $provided = array_map(fn ($pair) => $pair[0]->getKey(), $normalized);
            $missing = $types->filter(fn (AttachmentType $t) => $t->is_required
                && ! in_array($t->getKey(), $existing, false) && ! in_array($t->getKey(), $provided, false));
            if ($missing->isNotEmpty()) {
                throw new WorkflowException('المرفقات المطلوبة غير مكتملة: ' . $missing->pluck('name')->implode('، ') . '.');
            }

            foreach ($normalized as [$type, $file]) {
                $this->files->store($r, $file, RequestFile::KIND_ATTACHMENT, $actor, $type->getKey());
            }

            $r->forceFill(['attachments_uploaded_at' => now(), 'status' => SvStatus::ATTACHMENTS_UPLOADED]);

            return ['upload_attachments', ['files' => count($normalized)]];
        }, $notes);
    }

    // ───────────────────────────────────────────── planning engineer

    public function approve(SiteValidityRequest $request, User $actor, ?string $notes = null): SiteValidityRequest
    {
        return $this->transition($request, $actor, 'approve', function (SiteValidityRequest $r) {
            $r->forceFill([
                'approved_at' => now(),
                'status' => SvStatus::APPROVED,
                'appointment_confirmation' => SiteValidityRequest::CONFIRMATION_PENDING, // بانتظار قبول المواطن للموعد
                'appointment_confirmed_at' => null,
            ]);

            return ['approve', []];
        }, $notes);
    }

    /** المهندس يرفض الموعد ويختار يوماً من الأيام المتاحة للمركز — الطلب يُعتمد بالموعد الجديد وينتظر قبول المواطن. */
    public function engineerReschedule(SiteValidityRequest $request, User $actor, string $date, ?string $notes = null): SiteValidityRequest
    {
        return $this->transition($request, $actor, 'engineer_reschedule', function (SiteValidityRequest $r) use ($date) {
            $at = SvSchedule::resolve($r, $date) ?? throw new WorkflowException('اليوم المختار غير متاح لمعاينات هذا المركز — اختر يوماً من الأيام المتاحة.');
            $r->forceFill([
                'appointment_at' => $at,
                'approved_at' => $r->approved_at ?? now(),
                'status' => SvStatus::APPROVED,
                'appointment_confirmation' => SiteValidityRequest::CONFIRMATION_PENDING,
                'appointment_confirmed_at' => null,
            ]);

            return ['engineer_reschedule', ['appointment_at' => $at->format('Y-m-d H:i')]];
        }, $notes);
    }

    /** المواطن يقبل موعد المعاينة. */
    public function confirmAppointment(SiteValidityRequest $request, User $citizen): SiteValidityRequest
    {
        return $this->transition($request, $citizen, 'confirm_appointment', function (SiteValidityRequest $r) {
            $r->forceFill(['appointment_confirmation' => SiteValidityRequest::CONFIRMATION_ACCEPTED, 'appointment_confirmed_at' => now()]);

            return ['confirm_appointment', ['appointment_at' => $r->appointment_at?->format('Y-m-d H:i')]];
        }, null);
    }

    /** المواطن يرفض الموعد ويختار يوماً من الأيام المتاحة لمركز الطلب — يُعد موعداً مؤكداً ويُخطر الموظفون. */
    public function citizenReschedule(SiteValidityRequest $request, User $citizen, string $date): SiteValidityRequest
    {
        return $this->transition($request, $citizen, 'citizen_reschedule', function (SiteValidityRequest $r) use ($date) {
            $at = SvSchedule::resolve($r, $date) ?? throw new WorkflowException('اليوم المختار غير متاح — اختر يوماً من الأيام المتاحة.');
            $old = $r->appointment_at?->format('Y-m-d H:i');
            $r->forceFill([
                'appointment_at' => $at,
                'appointment_confirmation' => SiteValidityRequest::CONFIRMATION_ACCEPTED,
                'appointment_confirmed_at' => now(),
            ]);

            return ['citizen_reschedule', ['from' => $old, 'appointment_at' => $at->format('Y-m-d H:i')]];
        }, null);
    }

    public function reject(SiteValidityRequest $request, User $actor, string $reason): SiteValidityRequest
    {
        $reason = $this->clean($reason);
        if ($reason === null || mb_strlen($reason) < 5) {
            throw new WorkflowException('يرجى كتابة سبب الرفض (5 أحرف على الأقل).');
        }

        return $this->transition($request, $actor, 'reject', function (SiteValidityRequest $r) use ($reason) {
            $r->forceFill(['rejected_at' => now(), 'rejection_reason' => $reason, 'status' => SvStatus::REJECTED]);

            return ['reject', []];
        }, $reason);
    }

    /**
     * @param array<string, mixed> $answers key => value لكل بند نشط في استمارة المعاينة
     * @param mixed $existingHeight ارتفاع المبنى القائم بالمتر (طلبات التعلية فقط)
     */
    public function submitEngineerInspection(SiteValidityRequest $request, User $actor, array $answers, ?string $notes = null, mixed $existingHeight = null): SiteValidityRequest
    {
        return $this->transition($request, $actor, 'engineer_inspection', function (SiteValidityRequest $r) use ($actor, $answers, $notes, $existingHeight) {
            $this->assertAppointmentReached($r, $actor);

            $r->forceFill($this->inspectionPayload($r, $answers, $notes, $existingHeight) + ['engineer_inspected_at' => now()]);
            if ($r->geo_inspected_at !== null) {
                $r->status = SvStatus::INSPECTED;
            }

            return ['engineer_inspection', []];
        }, $notes);
    }

    /** تعديل الاستمارة بعد حفظها: يُحدّث inspection_updated_at ويُسجل في الخط الزمني البنود التي تغيرت. */
    public function editEngineerInspection(SiteValidityRequest $request, User $actor, array $answers, ?string $notes = null, mixed $existingHeight = null): SiteValidityRequest
    {
        return $this->transition($request, $actor, 'edit_inspection', function (SiteValidityRequest $r) use ($answers, $notes, $existingHeight) {
            $before = $this->inspectionValues($r);
            $r->forceFill($this->inspectionPayload($r, $answers, $notes, $existingHeight));
            $after = $this->inspectionValues($r);

            $changed = array_values(array_map(fn ($k) => $after[$k]['label'] ?? $before[$k]['label'] ?? $k,
                array_filter(array_keys($before + $after), fn ($k) => ($before[$k]['value'] ?? null) !== ($after[$k]['value'] ?? null))));
            if ($changed === []) {
                throw new WorkflowException('لم يتم تغيير أي بيانات في الاستمارة.');
            }
            $r->forceFill(['inspection_updated_at' => now()]);

            return ['edit_inspection', ['changed' => $changed]];
        }, null);
    }

    /** يتحقق من الإجابات ويبني بيانات الاستمارة (snapshot للبنود + ملاحظات + ارتفاع المبنى القائم). */
    protected function inspectionPayload(SiteValidityRequest $r, array $answers, ?string $notes, mixed $existingHeight): array
    {
        $fields = InspectionField::query()->active()->get();
        $rules = [];
        $names = [];
        foreach ($fields as $field) {
            $rules[$field->key] = $field->rules();
            $names[$field->key] = $field->label;
        }
        $isExtension = $r->construction_type === SiteValidityRequest::CONSTRUCTION_EXTENSION;
        if ($isExtension) {
            $answers['__existing_height'] = $existingHeight === '' ? null : $existingHeight;
            $rules['__existing_height'] = ['nullable', 'numeric', 'between:0,300'];
            $names['__existing_height'] = 'ارتفاع المبنى القائم';
        }
        $validator = Validator::make($answers, $rules, \Modules\SiteValidity\Support\ValidationMessages::all(), $names);
        if ($validator->fails()) {
            throw new WorkflowException('استمارة المعاينة غير مكتملة: ' . $validator->errors()->first(), $validator->errors()->toArray());
        }
        $valid = $validator->validated();

        return [
            'inspection_data' => $fields->map(fn (InspectionField $f) => [
                'key' => $f->key,
                'label' => $f->label,
                'type' => $f->type,
                'value' => isset($valid[$f->key]) ? (is_scalar($valid[$f->key]) ? (string) $valid[$f->key] : null) : null,
            ])->values()->all(),
            'inspection_notes' => $this->clean($notes),
            'existing_building_height' => $isExtension && isset($valid['__existing_height']) ? round((float) $valid['__existing_height'], 2) : null,
        ];
    }

    /** @return array<string, array{label: string, value: ?string}> قيم الاستمارة للمقارنة قبل/بعد التعديل. */
    protected function inspectionValues(SiteValidityRequest $r): array
    {
        $values = [];
        foreach ((array) $r->inspection_data as $row) {
            $values[$row['key'] ?? ''] = ['label' => (string) ($row['label'] ?? $row['key'] ?? ''), 'value' => isset($row['value']) && $row['value'] !== '' ? (string) $row['value'] : null];
        }
        $values['__notes'] = ['label' => 'ملاحظات المهندس', 'value' => $r->inspection_notes ?: null];
        $values['__existing_height'] = ['label' => 'ارتفاع المبنى القائم', 'value' => $r->existing_building_height !== null ? number_format((float) $r->existing_building_height, 2, '.', '') : null];

        return $values;
    }

    /** @param list<string> $items مستندات/بيانات مطلوبة (اختياري) */
    /**
     * @param list<string> $items مستندات/بيانات مطلوبة (اختياري — لاستيفاء المستندات)
     * @param string $type Completion::TYPE_DOCUMENTS | Completion::TYPE_PAYMENT
     * @param mixed $amount المبلغ المطلوب سداده (لاستيفاء الرسوم)
     */
    public function requestCompletion(SiteValidityRequest $request, User $actor, string $notes, array $items = [], string $type = Completion::TYPE_DOCUMENTS, mixed $amount = null): SiteValidityRequest
    {
        $notes = $this->clean($notes);
        if ($notes === null || mb_strlen($notes) < 5) {
            throw new WorkflowException('يرجى كتابة المطلوب من المواطن (5 أحرف على الأقل).');
        }
        if (! isset(Completion::TYPES[$type])) {
            throw new WorkflowException('نوع الاستيفاء غير صحيح.');
        }
        if ($type === Completion::TYPE_PAYMENT) {
            if (! is_numeric($amount) || (float) $amount <= 0 || (float) $amount > 1000000) {
                throw new WorkflowException('يرجى إدخال مبلغ صحيح لرسوم الاستيفاء.');
            }
            $amount = round((float) $amount, 2);
            $items = [];
        } else {
            $amount = null;
        }
        $items = array_values(array_filter(array_map(fn ($i) => $this->clean(is_array($i) ? ($i['item'] ?? '') : (string) $i), $items)));

        return $this->transition($request, $actor, 'request_completion', function (SiteValidityRequest $r) use ($actor, $notes, $items, $type, $amount) {
            $round = (int) $r->completion_rounds + 1;
            $completion = new Completion(['round' => $round, 'type' => $type, 'amount' => $amount, 'notes' => $notes, 'requested_items' => array_map(fn ($i) => Str::limit($i, 250, ''), $items)]);
            $completion->forceFill([
                'request_id' => $r->getKey(),
                'requested_by' => $actor->getKey(),
                'requested_at' => now(),
            ])->save();

            $r->forceFill(['completion_rounds' => $round, 'status' => SvStatus::COMPLETION_REQUESTED]);

            return ['request_completion', array_filter(['round' => $round, 'type' => $type, 'amount' => $amount, 'items' => $items])];
        }, $notes);
    }

    public function complete(SiteValidityRequest $request, User $actor, UploadedFile|string $finalFile, ?string $notes = null, ?string $originalName = null): SiteValidityRequest
    {
        $cfg = (array) config('site-validity.final_upload');

        return $this->transition($request, $actor, 'complete', function (SiteValidityRequest $r) use ($actor, $finalFile, $notes, $cfg, $originalName) {
            $this->files->validate($finalFile, $cfg['extensions'], (int) $cfg['max_kb'], 'الملف النهائي');
            $this->files->store($r, $finalFile, RequestFile::KIND_FINAL, $actor, null, null, $originalName);
            $r->forceFill(['final_notes' => $this->clean($notes), 'status' => SvStatus::FINAL_UPLOADED]);

            return ['upload_final', []];
        }, $notes);
    }

    /** مدير التنظيم: اعتماد الصلاحية — ينتهي الطلب ويُتاح الملف النهائي للمواطن. */
    public function directorApprove(SiteValidityRequest $request, User $actor, ?string $notes = null): SiteValidityRequest
    {
        return $this->transition($request, $actor, 'director_approve', function (SiteValidityRequest $r) {
            if ($r->finalFile() === null) {
                throw new WorkflowException('لا يوجد ملف نهائي مرفوع لاعتماده.');
            }
            $r->forceFill(['completed_at' => now(), 'status' => SvStatus::COMPLETED, 'pickup_code' => $r->pickup_code ?? $this->pickupCode()]);

            return ['complete', ['pickup_code' => $r->pickup_code]];
        }, $notes);
    }

    /** رفض مدير التنظيم: لا يُغلق الطلب — يعود لمهندس التنظيم مع السبب ليعالجه ويرفع ملفاً نهائياً جديداً. */
    public function directorReturn(SiteValidityRequest $request, User $actor, string $notes): SiteValidityRequest
    {
        $notes = $this->clean($notes);
        if ($notes === null || mb_strlen($notes) < 5) {
            throw new WorkflowException('يرجى كتابة سبب الرفض للمهندس (5 أحرف على الأقل).');
        }

        return $this->transition($request, $actor, 'director_return', function (SiteValidityRequest $r) {
            $r->forceFill(['status' => SvStatus::ATTACHMENTS_UPLOADED]);

            return ['director_return', []];
        }, $notes);
    }

    // ───────────────────────────────────────────── citizen completion reply

    /** @param list<UploadedFile> $files */
    public function submitCompletion(SiteValidityRequest $request, User $citizen, string $reply, array $files = []): SiteValidityRequest
    {
        $reply = $this->clean($reply);
        if ($reply === null || mb_strlen($reply) < 3) {
            throw new WorkflowException('يرجى كتابة رد الاستيفاء.');
        }
        $upload = (array) config('site-validity.citizen_upload');
        foreach ($files as $file) {
            $this->files->validate($file, $upload['extensions'], (int) $upload['max_kb'], 'مستند الاستيفاء');
        }

        return $this->transition($request, $citizen, 'submit_completion', function (SiteValidityRequest $r) use ($citizen, $reply, $files) {
            $completion = $r->openCompletion() ?? throw new WorkflowException('لا يوجد طلب استيفاء مفتوح.');
            $completion->forceFill(['reply' => $reply, 'replied_at' => now()])->save();
            foreach ($files as $file) {
                $this->files->store($r, $file, RequestFile::KIND_COMPLETION, $citizen, null, $completion->getKey());
            }
            // استيفاء مرحلة رفع المرفقات يعود للمركز الجيومكاني، وما بعدها لمهندس التنظيم.
            $r->status = $r->attachments_uploaded_at === null ? SvStatus::INSPECTED : SvStatus::COMPLETION_SUBMITTED;

            return ['submit_completion', ['round' => $completion->round, 'files' => count($files)]];
        }, $reply);
    }

    /**
     * تأكيد سداد رسوم الاستيفاء (من Completion::markPaid داخل معاملة الدفع): يُغلق الاستيفاء
     * ويعود الطلب لمن طلبه — كما في submitCompletion، لكن بدون فاعل بشري (منظومة الدفع).
     */
    public function settleCompletionPayment(Completion $completion): void
    {
        [$fresh, $from] = DB::transaction(function () use ($completion) {
            /** @var SiteValidityRequest $r */
            $r = SiteValidityRequest::query()->whereKey($completion->request_id)->lockForUpdate()->firstOrFail();
            $from = $r->status;
            if (! $completion->isOpen() || $from !== SvStatus::COMPLETION_REQUESTED) {
                return [null, $from]; // سداد متأخر بعد تعديل إداري للحالة: يُسجل الدفع فقط
            }

            $note = 'تم سداد رسوم الاستيفاء ' . number_format((float) ($completion->paid_amount ?? $completion->amount), 2) . ' ج.م إلكترونياً.';
            $completion->forceFill(['reply' => $note, 'replied_at' => now()])->save();
            $r->status = $r->attachments_uploaded_at === null ? SvStatus::INSPECTED : SvStatus::COMPLETION_SUBMITTED;
            $r->save();

            $event = $this->log($r, null, 'submit_completion', $from, $r->status, $note, ['round' => $completion->round, 'paid' => (string) $completion->paid_amount]);
            $event->forceFill(['actor_name' => 'منظومة الدفع الإلكتروني'])->save();

            return [$r, $from];
        });

        if ($fresh !== null) {
            RequestTransitioned::dispatch($fresh, 'submit_completion', $from, $fresh->status, null, null);
        }
    }

    // ───────────────────────────────────────────── admin

    public function override(SiteValidityRequest $request, User $actor, string $status, string $notes): SiteValidityRequest
    {
        if (! in_array($status, SvStatus::all(), true)) {
            throw new WorkflowException('حالة غير معروفة.');
        }
        $notes = $this->clean($notes);
        if ($notes === null || mb_strlen($notes) < 5) {
            throw new WorkflowException('يرجى توضيح سبب التعديل الإداري.');
        }

        return $this->transition($request, $actor, 'override', function (SiteValidityRequest $r) use ($status) {
            $r->status = $status;
            match ($status) {
                SvStatus::COMPLETED => $r->completed_at ??= now(),
                SvStatus::REJECTED => $r->rejected_at ??= now(),
                default => null,
            };

            return ['override', []];
        }, $notes);
    }

    public function assign(SiteValidityRequest $request, User $actor, ?string $geoUserId, ?string $engineerUserId): SiteValidityRequest
    {
        $geo = $geoUserId ? User::query()->whereKey($geoUserId)->first() : null;
        $eng = $engineerUserId ? User::query()->whereKey($engineerUserId)->first() : null;
        if (($geoUserId && ! SvAccess::isGeo($geo)) || ($engineerUserId && ! SvAccess::isEngineer($eng))) {
            throw new WorkflowException('المستخدم المختار لا يملك الدور المطلوب.');
        }

        return $this->transition($request, $actor, 'assign', function (SiteValidityRequest $r) use ($geo, $eng) {
            $r->forceFill(['geo_user_id' => $geo?->getKey(), 'engineer_user_id' => $eng?->getKey()]);

            return ['assign', ['geo' => $geo?->name, 'engineer' => $eng?->name]];
        }, null);
    }

    /** تسجيل تعديل إداري للبيانات من صفحة التحرير في اللوحة. */
    public function logAdminEdit(SiteValidityRequest $request, User $actor, array $changed): void
    {
        if ($changed === []) {
            return;
        }
        $this->log($request, $actor, 'admin_edit', $request->status, $request->status, 'الحقول: ' . implode('، ', $changed), ['fields' => $changed]);
    }

    // ───────────────────────────────────────────── internals

    /**
     * @param  callable(SiteValidityRequest): array{0:string,1:array}  $apply  يعدّل الطلب ويعيد [action-label, meta]
     */
    protected function transition(SiteValidityRequest $request, User $actor, string $action, callable $apply, ?string $notes): SiteValidityRequest
    {
        [$fresh, $from, $logged] = DB::transaction(function () use ($request, $actor, $action, $apply, $notes) {
            /** @var SiteValidityRequest $locked */
            $locked = SiteValidityRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $actor, $action);

            $from = $locked->status;
            [$logged, $meta] = $apply($locked);
            $locked->save();

            $this->log($locked, $actor, $logged, $from, $locked->status, $this->clean($notes), $meta);

            return [$locked, $from, $logged];
        });

        $request->setRawAttributes($fresh->getAttributes(), true);

        RequestTransitioned::dispatch($fresh, $logged, $from, $fresh->status, $actor, $this->clean($notes));

        return $fresh;
    }

    protected function assertAppointmentReached(SiteValidityRequest $r, User $actor): void
    {
        if ($r->appointment_at === null) {
            throw new WorkflowException('لم يُحدد موعد المعاينة بعد.');
        }
        if (SvSettings::enforceAppointmentTime() && ! SvAccess::isAdmin($actor) && $r->appointment_at->copy()->startOfDay()->isFuture()) {
            throw new WorkflowException('لا يمكن تسجيل المعاينة قبل يوم الموعد (' . $r->appointment_at->format('Y-m-d H:i') . ').');
        }
    }

    public function log(SiteValidityRequest $request, ?User $actor, string $action, ?string $from, ?string $to, ?string $notes = null, array $meta = []): RequestEvent
    {
        $event = new RequestEvent([
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'notes' => $notes,
            'meta' => $meta ?: null,
            'actor_name' => $actor?->name,
            'actor_role' => SvAccess::roleKey($actor) ?? 'system',
        ]);
        $event->request_id = $request->getKey();
        $event->user_id = $actor?->getKey();
        $event->created_at = now();
        $event->save();

        return $event;
    }

    /** كود استلام فريد بحروف وأرقام سهلة القراءة (بدون O/0/I/1). */
    protected function pickupCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = substr($code, 0, 4) . '-' . substr($code, 4);
        } while (SiteValidityRequest::query()->where('pickup_code', $code)->exists());

        return $code;
    }

    /** نص عادي فقط: إزالة الوسوم والمسافات الزائدة. */
    protected function clean(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }
        $text = trim(strip_tags($text));

        return $text === '' ? null : Str::limit($text, 3000, '');
    }
}
