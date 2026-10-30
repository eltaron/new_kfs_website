<?php

namespace Modules\Syndicate\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Syndicate\Events\SupervisionCancelled;
use Modules\Syndicate\Events\SupervisionCertificateIssued;
use Modules\Syndicate\Events\SyndicateRequestStatusChanged;
use Modules\Syndicate\Events\SyndicateRequestSubmitted;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Engineer;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateEvent;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Support\Actor;
use Modules\Syndicate\Support\SyndicateException;

/**
 * آلة الحالات الخاصة بالإشراف على المواقع:
 *
 *  المشروع:  pending ──(شهادة إشراف)──▶ active ──(شهادة إلغاء)──▶ suspended ──(شهادة إشراف جديدة)──▶ active
 *  الطلب:    submitted ─▶ under_review ─▶ (approved) ─▶ completed | rejected
 *  الشهادة:  active ─▶ cancelled (بشهادة إلغاء) | superseded (بشهادة إشراف أحدث لنفس المشروع)
 *
 * قاعدة ثابتة: شهادة إشراف واحدة سارية فقط لكل مشروع. كل عملية داخل معاملة قاعدة بيانات
 * مع قفل الصفوف، والأحداث تُطلق بعد الـ commit (ShouldDispatchAfterCommit).
 */
class SupervisionService
{
    public function __construct(protected NumberGenerator $numbers)
    {
    }

    // ───────────────────────────── المشروعات ─────────────────────────────

    public function registerProject(array $data, ?Actor $actor = null): Project
    {
        $actor ??= Actor::system();
        $this->assertProjectData($data);

        return DB::transaction(function () use ($data, $actor) {
            $attributes = Arr::only($data, (new Project())->getFillable());
            unset($attributes['work_status'], $attributes['active_certificate_id'], $attributes['suspended_at'], $attributes['work_status_changed_at']);

            $project = Project::create(array_merge($attributes, [
                'code' => $this->numbers->project(),
                'owner_user_id' => $data['owner_user_id'] ?? $this->findUserIdByNationalId($data['owner_national_id']),
                'work_status' => Project::WORK_PENDING,
                'work_status_changed_at' => now(),
                'created_by' => $actor->type === 'user' ? $actor->id : null,
            ]));

            $this->recordEvent($project, SyndicateEvent::PROJECT_REGISTERED, 'تسجيل المشروع بمجلس المدينة',
                'تم تسجيل الموقع ' . $project->address . ' (' . $project->center . ') باسم المالك ' . $project->owner_name . '.', $actor);

            return $project;
        });
    }

    /** يعيد المشروع القائم لنفس المالك ونفس رقم الترخيص أو ينشئ مشروعاً جديداً. */
    public function findOrRegisterProject(array $data, ?Actor $actor = null): Project
    {
        if (filled($data['license_number'] ?? null) && filled($data['owner_national_id'] ?? null)) {
            $existing = Project::where('owner_national_id', $data['owner_national_id'])
                ->where('license_number', $data['license_number'])->first();
            if ($existing) {
                return $existing;
            }
        }

        return $this->registerProject($data, $actor);
    }

    // ───────────────────────────── الطلبات ─────────────────────────────

    /**
     * تسجيل طلب (إشراف / إلغاء إشراف) برقم معاملة فريد.
     *
     * @param  array{name:string,national_id?:?string,phone?:?string,email?:?string,user_id?:?string}  $applicant
     */
    public function submitRequest(
        Project $project,
        string $type,
        array $applicant,
        string $requestedBy = SyndicateRequest::BY_CITIZEN,
        ?Actor $actor = null,
        string $channel = 'counter',
        ?string $reason = null,
        ?string $notes = null,
    ): SyndicateRequest {
        $actor ??= Actor::system();

        if (! array_key_exists($type, SyndicateRequest::types())) {
            throw SyndicateException::invalid('نوع الطلب غير صحيح.', 'invalid_type');
        }
        if (! array_key_exists($requestedBy, SyndicateRequest::requesters())) {
            throw SyndicateException::invalid('جهة تقديم الطلب غير صحيحة.', 'invalid_requester');
        }
        if (blank($applicant['name'] ?? null)) {
            throw SyndicateException::invalid('اسم مقدم الطلب مطلوب.', 'applicant_required');
        }
        if (filled($applicant['national_id'] ?? null) && ! preg_match('/^\d{14}$/', (string) $applicant['national_id'])) {
            throw SyndicateException::invalid('الرقم القومي لمقدم الطلب يجب أن يكون 14 رقماً.', 'invalid_national_id');
        }
        if ($type === SyndicateRequest::TYPE_SUPERVISION && $requestedBy === SyndicateRequest::BY_ENGINEER) {
            // طلب الإشراف يقدمه المالك (أو المجلس نيابة عنه)؛ المهندس يقدم طلبات الإلغاء فقط.
            throw SyndicateException::invalid('طلب الإشراف يقدم من المالك أو مجلس المدينة.', 'invalid_requester');
        }

        $request = DB::transaction(function () use ($project, $type, $applicant, $requestedBy, $actor, $channel, $reason, $notes) {
            /** @var Project $project */
            $project = Project::whereKey($project->getKey())->lockForUpdate()->firstOrFail();

            if ($project->requests()->where('type', $type)->open()->exists()) {
                throw SyndicateException::conflict('يوجد طلب مفتوح من نفس النوع لهذا المشروع بالفعل.', 'open_request_exists');
            }

            $target = null;
            if ($type === SyndicateRequest::TYPE_CANCELLATION) {
                $target = $project->active_certificate_id ? Certificate::find($project->active_certificate_id) : null;
                if (! $target || ! $target->isActive()) {
                    throw SyndicateException::invalidState('لا توجد شهادة إشراف سارية على هذا المشروع لإلغائها.');
                }
            }

            $request = SyndicateRequest::create([
                'transaction_number' => $this->numbers->request($type),
                'type' => $type,
                'status' => SyndicateRequest::STATUS_SUBMITTED,
                'requested_by' => $requestedBy,
                'channel' => $channel,
                'project_id' => $project->getKey(),
                'applicant_name' => $applicant['name'],
                'applicant_national_id' => $applicant['national_id'] ?? null,
                'applicant_phone' => $applicant['phone'] ?? null,
                'applicant_email' => $applicant['email'] ?? null,
                'applicant_user_id' => $applicant['user_id'] ?? $this->findUserIdByNationalId($applicant['national_id'] ?? null),
                'target_certificate_id' => $target?->getKey(),
                'reason' => $reason,
                'notes' => $notes,
                'registered_by' => $actor->type === 'user' ? $actor->id : null,
            ]);

            $title = $type === SyndicateRequest::TYPE_SUPERVISION
                ? 'تقديم طلب إشراف على الموقع'
                : 'تقديم طلب إلغاء إشراف';

            $this->recordEvent($project, SyndicateEvent::REQUEST_SUBMITTED, $title,
                'رقم المعاملة ' . $request->transaction_number . ' — مقدم من: ' . (SyndicateRequest::requesters()[$requestedBy] ?? $requestedBy) . ' (' . $request->applicant_name . ').',
                $actor, $request, $target, ['channel' => $channel]);

            return $request;
        });

        event(new SyndicateRequestSubmitted($request));

        return $request;
    }

    public function markUnderReview(SyndicateRequest $request, Actor $actor): SyndicateRequest
    {
        return $this->transition($request, [SyndicateRequest::STATUS_SUBMITTED], SyndicateRequest::STATUS_UNDER_REVIEW, $actor,
            SyndicateEvent::REQUEST_UNDER_REVIEW, 'الطلب قيد المراجعة بنقابة المهندسين');
    }

    public function approve(SyndicateRequest $request, Actor $actor): SyndicateRequest
    {
        return $this->transition($request, [SyndicateRequest::STATUS_SUBMITTED, SyndicateRequest::STATUS_UNDER_REVIEW],
            SyndicateRequest::STATUS_APPROVED, $actor, SyndicateEvent::REQUEST_UNDER_REVIEW, 'اعتماد الطلب من نقابة المهندسين');
    }

    public function reject(SyndicateRequest $request, string $reason, Actor $actor): SyndicateRequest
    {
        if (blank($reason)) {
            throw SyndicateException::invalid('سبب الرفض مطلوب.', 'reason_required');
        }

        return $this->transition($request, SyndicateRequest::OPEN_STATUSES, SyndicateRequest::STATUS_REJECTED, $actor,
            SyndicateEvent::REQUEST_REJECTED, 'رفض الطلب', $reason);
    }

    // ───────────────────────────── الشهادات ─────────────────────────────

    /**
     * إصدار شهادة إشراف على الموقع لطلب إشراف مفتوح.
     *
     * @param  array  $engineer  syndicate_number, name, national_id, phone, email, specialization, registration_number,
     *                           address, office_name, office_registration_number
     */
    public function issueSupervisionCertificate(SyndicateRequest $request, array $engineer, Actor $actor, ?string $notes = null): Certificate
    {
        if (blank($engineer['syndicate_number'] ?? null)) {
            throw SyndicateException::invalid('رقم عضوية النقابة للمهندس مطلوب.', 'engineer_required');
        }

        [$certificate, $previousStatus, $previousCertificate] = DB::transaction(function () use ($request, $engineer, $actor, $notes) {
            /** @var SyndicateRequest $request */
            $request = SyndicateRequest::whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            if ($request->type !== SyndicateRequest::TYPE_SUPERVISION) {
                throw SyndicateException::invalidState('هذا الطلب ليس طلب إشراف.');
            }
            if (! $request->isOpen()) {
                throw SyndicateException::invalidState('لا يمكن إصدار شهادة لطلب حالته: ' . $request->status_label . '.');
            }

            /** @var Project $project */
            $project = Project::whereKey($request->project_id)->lockForUpdate()->firstOrFail();
            $previousStatus = $project->work_status;

            $directory = $this->resolveEngineer($engineer);
            if ($directory->membership_status !== 'active') {
                throw SyndicateException::conflict('عضوية المهندس بالنقابة غير سارية.', 'engineer_not_active');
            }

            // شهادة واحدة سارية فقط: أي شهادة سارية سابقة تصبح "مستبدلة".
            $previousCertificate = null;
            $currentActive = Certificate::where('project_id', $project->getKey())
                ->where('kind', Certificate::KIND_SUPERVISION)
                ->where('status', Certificate::STATUS_ACTIVE)
                ->lockForUpdate()->get();

            foreach ($currentActive as $old) {
                $old->update(['status' => Certificate::STATUS_SUPERSEDED, 'superseded_at' => now()]);
                $previousCertificate = $old;
                $this->recordEvent($project, SyndicateEvent::CERTIFICATE_SUPERSEDED, 'استبدال المهندس المشرف',
                    'الشهادة ' . $old->number . ' (م. ' . $old->engineer_name . ') أصبحت مستبدلة.', $actor, $request, $old);
            }

            if (! $previousCertificate && $previousStatus === Project::WORK_SUSPENDED) {
                // المهندس السابق = صاحب آخر شهادة ملغاة على المشروع.
                $previousCertificate = Certificate::where('project_id', $project->getKey())
                    ->where('kind', Certificate::KIND_SUPERVISION)
                    ->where('status', Certificate::STATUS_CANCELLED)
                    ->latest('cancelled_at')->latest('id')->first();
            }

            $certificate = Certificate::create(array_merge($this->projectSnapshot($project), [
                'number' => $this->numbers->certificate(Certificate::KIND_SUPERVISION),
                'kind' => Certificate::KIND_SUPERVISION,
                'status' => Certificate::STATUS_ACTIVE,
                'project_id' => $project->getKey(),
                'request_id' => $request->getKey(),
                'engineer_id' => $directory->getKey(),
                'engineer_name' => $directory->name,
                'engineer_national_id' => $directory->national_id,
                'engineer_syndicate_number' => $directory->syndicate_number,
                'engineer_department' => $directory->specialization,
                'engineer_registration_number' => $directory->registration_number,
                'engineer_address' => $engineer['address'] ?? null,
                'engineer_phone' => $directory->phone,
                'engineer_email' => $directory->email,
                'office_name' => $engineer['office_name'] ?? $directory->office_name,
                'office_registration_number' => $engineer['office_registration_number'] ?? $directory->office_registration_number,
                'issued_at' => now(),
                'issued_by' => $actor->reference(),
                'issued_by_name' => $actor->name,
                'verification_code' => $this->verificationCode(),
                'notes' => $notes,
            ]));

            $request->update([
                'status' => SyndicateRequest::STATUS_COMPLETED,
                'certificate_id' => $certificate->getKey(),
                'completed_at' => now(),
                'reviewed_by' => $request->reviewed_by ?? $actor->reference(),
                'reviewed_at' => $request->reviewed_at ?? now(),
            ]);

            $project->update([
                'active_certificate_id' => $certificate->getKey(),
                'work_status' => Project::WORK_ACTIVE,
                'work_status_changed_at' => now(),
                'suspended_at' => null,
            ]);

            $this->recordEvent($project, SyndicateEvent::SUPERVISION_ISSUED, 'صدور شهادة إشراف على الموقع',
                'الشهادة رقم ' . $certificate->number . ' — المهندس المشرف: ' . $certificate->engineer_name
                . ' (عضوية ' . $certificate->engineer_syndicate_number . ')'
                . ($certificate->office_name ? ' — المكتب: ' . $certificate->office_name : '') . '.',
                $actor, $request, $certificate);

            if ($previousStatus === Project::WORK_SUSPENDED) {
                $this->recordEvent($project, SyndicateEvent::WORK_RESUMED, 'عودة المشروع للعمل',
                    'أصبح للمشروع مهندس مشرف جديد ويمكن استئناف الأعمال.', $actor, $request, $certificate);
            }

            return [$certificate, $previousStatus, $previousCertificate];
        });

        event(new SupervisionCertificateIssued($certificate, $previousStatus, $previousCertificate));

        return $certificate;
    }

    /** إصدار شهادة إلغاء إشراف لطلب إلغاء مفتوح ⇒ إيقاف الأعمال بالموقع. */
    public function issueCancellationCertificate(SyndicateRequest $request, Actor $actor, ?string $notes = null): Certificate
    {
        [$cancellation, $cancelled] = DB::transaction(function () use ($request, $actor, $notes) {
            /** @var SyndicateRequest $request */
            $request = SyndicateRequest::whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            if ($request->type !== SyndicateRequest::TYPE_CANCELLATION) {
                throw SyndicateException::invalidState('هذا الطلب ليس طلب إلغاء إشراف.');
            }
            if (! $request->isOpen()) {
                throw SyndicateException::invalidState('لا يمكن إصدار شهادة لطلب حالته: ' . $request->status_label . '.');
            }

            /** @var Project $project */
            $project = Project::whereKey($request->project_id)->lockForUpdate()->firstOrFail();

            /** @var Certificate|null $target */
            $target = Certificate::whereKey($request->target_certificate_id ?: $project->active_certificate_id)->lockForUpdate()->first();
            if (! $target || ! $target->isActive() || (int) $project->active_certificate_id !== (int) $target->getKey()) {
                throw SyndicateException::invalidState('شهادة الإشراف المطلوب إلغاؤها لم تعد سارية.');
            }

            $cancellation = Certificate::create(array_merge($this->projectSnapshot($project), Arr::only($target->getAttributes(), [
                'engineer_id', 'engineer_name', 'engineer_national_id', 'engineer_syndicate_number', 'engineer_department',
                'engineer_registration_number', 'engineer_address', 'engineer_phone', 'engineer_email',
                'office_name', 'office_registration_number',
            ]), [
                'number' => $this->numbers->certificate(Certificate::KIND_CANCELLATION),
                'kind' => Certificate::KIND_CANCELLATION,
                'status' => Certificate::STATUS_ACTIVE,
                'project_id' => $project->getKey(),
                'request_id' => $request->getKey(),
                'cancels_certificate_id' => $target->getKey(),
                'issued_at' => now(),
                'issued_by' => $actor->reference(),
                'issued_by_name' => $actor->name,
                'verification_code' => $this->verificationCode(),
                'notes' => $notes ?? $request->reason,
            ]));

            $target->update(['status' => Certificate::STATUS_CANCELLED, 'cancelled_at' => now()]);

            $request->update([
                'status' => SyndicateRequest::STATUS_COMPLETED,
                'certificate_id' => $cancellation->getKey(),
                'completed_at' => now(),
                'reviewed_by' => $request->reviewed_by ?? $actor->reference(),
                'reviewed_at' => $request->reviewed_at ?? now(),
            ]);

            $project->update([
                'active_certificate_id' => null,
                'work_status' => Project::WORK_SUSPENDED,
                'work_status_changed_at' => now(),
                'suspended_at' => now(),
            ]);

            $this->recordEvent($project, SyndicateEvent::CANCELLATION_ISSUED, 'صدور شهادة إلغاء إشراف',
                'الشهادة رقم ' . $cancellation->number . ' بإلغاء شهادة الإشراف ' . $target->number . ' (م. ' . $target->engineer_name . ').',
                $actor, $request, $cancellation);

            $this->recordEvent($project, SyndicateEvent::WORK_SUSPENDED, 'إيقاف الأعمال - المشروع بدون مهندس مشرف',
                'يجب إيقاف جميع الأعمال بالموقع فوراً لحين صدور شهادة إشراف جديدة.', $actor, $request, $cancellation);

            return [$cancellation, $target->fresh()];
        });

        event(new SupervisionCancelled($cancellation, $cancelled));

        return $cancellation;
    }

    // ───────────────────────────── دليل المهندسين ─────────────────────────────

    /** مزامنة دليل المهندسين من النقابة (upsert برقم العضوية). يعيد عدد السجلات. */
    public function syncEngineers(array $rows): int
    {
        $count = 0;
        DB::transaction(function () use ($rows, &$count) {
            foreach ($rows as $row) {
                if (blank($row['syndicate_number'] ?? null) || blank($row['name'] ?? null)) {
                    continue;
                }
                $engineer = Engineer::firstOrNew(['syndicate_number' => $row['syndicate_number']]);
                $engineer->fill(array_filter(Arr::only($row, array_diff($engineer->getFillable(), ['user_id', 'synced_at'])), fn ($v) => $v !== null));
                $engineer->synced_at = now();
                if (! $engineer->user_id && filled($engineer->national_id)) {
                    $engineer->user_id = $this->findUserIdByNationalId($engineer->national_id);
                }
                $engineer->save();
                $count++;
            }
        });

        return $count;
    }

    // ───────────────────────────── أدوات داخلية ─────────────────────────────

    protected function transition(SyndicateRequest $request, array $from, string $to, Actor $actor, string $eventType, string $title, ?string $reason = null): SyndicateRequest
    {
        [$request, $old] = DB::transaction(function () use ($request, $from, $to, $actor, $eventType, $title, $reason) {
            /** @var SyndicateRequest $request */
            $request = SyndicateRequest::whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            $old = $request->status;

            if (! in_array($old, $from, true)) {
                throw SyndicateException::invalidState('لا يمكن نقل الطلب من حالة "' . $request->status_label . '" إلى "' . (SyndicateRequest::statuses()[$to] ?? $to) . '".');
            }

            $request->update(array_filter([
                'status' => $to,
                'reviewed_by' => $actor->reference(),
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ], fn ($v) => $v !== null));

            $this->recordEvent($request->project, $eventType, $title,
                'رقم المعاملة ' . $request->transaction_number . ($reason ? ' — السبب: ' . $reason : ''), $actor, $request);

            return [$request, $old];
        });

        event(new SyndicateRequestStatusChanged($request, $old, $to));

        return $request;
    }

    protected function resolveEngineer(array $data): Engineer
    {
        $engineer = Engineer::firstOrNew(['syndicate_number' => trim((string) $data['syndicate_number'])]);

        $map = [
            'name' => $data['name'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'specialization' => $data['specialization'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'office_name' => $data['office_name'] ?? null,
            'office_registration_number' => $data['office_registration_number'] ?? null,
        ];
        $engineer->fill(array_filter($map, fn ($v) => filled($v)));

        if (blank($engineer->name)) {
            throw SyndicateException::invalid('اسم المهندس مطلوب (غير مسجل في دليل المهندسين).', 'engineer_required');
        }
        if (filled($engineer->national_id) && ! preg_match('/^\d{14}$/', (string) $engineer->national_id)) {
            throw SyndicateException::invalid('الرقم القومي للمهندس يجب أن يكون 14 رقماً.', 'invalid_national_id');
        }

        $engineer->membership_status ??= 'active';
        if (! $engineer->user_id && filled($engineer->national_id)) {
            $engineer->user_id = $this->findUserIdByNationalId($engineer->national_id);
        }
        $engineer->save();

        return $engineer;
    }

    protected function projectSnapshot(Project $project): array
    {
        return [
            'type' => $project->works_type,
            'execution_sub_type' => $project->works_type === 'execution' ? $project->execution_sub_type : null,
            'owner_name' => $project->owner_name,
            'owner_address' => $project->owner_address,
            'owner_national_id' => $project->owner_national_id,
            'license_number' => $project->license_number,
            'license_date' => $project->license_date,
            'license_authority' => $project->license_authority,
            'project_address' => $project->address,
            'city' => $project->center,
            'governorate' => config('syndicate.governorate', 'كفر الشيخ'),
            'licensed_works_description' => $project->works_description,
            'works_value_numbers' => $project->works_value,
        ];
    }

    protected function assertProjectData(array $data): void
    {
        foreach (['owner_name' => 'اسم المالك', 'owner_national_id' => 'الرقم القومي للمالك', 'address' => 'عنوان الموقع', 'center' => 'المركز'] as $key => $label) {
            if (blank($data[$key] ?? null)) {
                throw SyndicateException::invalid($label . ' مطلوب.', 'project_invalid');
            }
        }
        if (! preg_match('/^\d{14}$/', (string) $data['owner_national_id'])) {
            throw SyndicateException::invalid('الرقم القومي للمالك يجب أن يكون 14 رقماً.', 'invalid_national_id');
        }
    }

    public function recordEvent(Project $project, string $type, string $title, ?string $description, Actor $actor,
        ?SyndicateRequest $request = null, ?Certificate $certificate = null, array $payload = []): SyndicateEvent
    {
        return SyndicateEvent::create(array_merge([
            'project_id' => $project->getKey(),
            'request_id' => $request?->getKey(),
            'certificate_id' => $certificate?->getKey(),
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'payload' => $payload ?: null,
        ], $actor->toEventAttributes()));
    }

    protected function findUserIdByNationalId(?string $nationalId): ?string
    {
        if (blank($nationalId)) {
            return null;
        }

        return User::where('national_id', $nationalId)->value('id');
    }

    protected function verificationCode(): string
    {
        return strtoupper(Str::random(10));
    }
}
