<?php

namespace Modules\Syndicate\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Syndicate\Http\ApiResponse;
use Modules\Syndicate\Http\Transformers;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Services\SupervisionService;
use Modules\Syndicate\Support\Actor;
use Modules\Syndicate\Support\SyndicateException;

/**
 * واجهة برمجة نقابة المهندسين — api/syndicate/v1
 * التوثيق: modules/Syndicate/docs/openapi.yaml
 */
class SyndicateApiController extends Controller
{
    public function __construct(protected SupervisionService $service)
    {
    }

    /** GET requests/{transaction_number} */
    public function showRequest(string $transactionNumber): JsonResponse
    {
        $request = $this->findRequest($transactionNumber);
        if (! $request) {
            return $this->notFound('request_not_found', 'لا توجد معاملة بهذا الرقم.');
        }

        return ApiResponse::data(Transformers::request($request));
    }

    /** GET requests?status=&type=&per_page= */
    public function listRequests(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'status' => ['nullable', Rule::in(array_keys(SyndicateRequest::statuses()))],
            'type' => ['nullable', Rule::in(array_keys(SyndicateRequest::types()))],
            'center' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [], ['status' => 'الحالة', 'type' => 'النوع']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $paginator = SyndicateRequest::query()
            ->with(['project.activeCertificate', 'targetCertificate', 'certificate'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->query('center'), fn ($q, $c) => $q->whereHas('project', fn ($p) => $p->where('center', $c)))
            ->latest('id')
            ->paginate((int) ($request->query('per_page') ?: 25))
            ->withQueryString();

        return ApiResponse::paginated($paginator, fn (SyndicateRequest $r) => Transformers::request($r, false));
    }

    /** POST requests/{transaction_number}/status — under_review | approved | rejected */
    public function updateRequestStatus(Request $request, string $transactionNumber): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['under_review', 'approved', 'rejected'])],
            'reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000'],
        ], ['reason.required_if' => 'سبب الرفض مطلوب عند رفض الطلب.']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $model = $this->findRequest($transactionNumber);
        if (! $model) {
            return $this->notFound('request_not_found', 'لا توجد معاملة بهذا الرقم.');
        }

        return $this->guard(function () use ($request, $model) {
            $actor = Actor::current();
            $updated = match ($request->input('status')) {
                'under_review' => $this->service->markUnderReview($model, $actor),
                'approved' => $this->service->approve($model, $actor),
                'rejected' => $this->service->reject($model, (string) $request->input('reason'), $actor),
            };

            return ApiResponse::data(Transformers::request($this->findRequest($updated->transaction_number)));
        });
    }

    /** POST certificates — إصدار شهادة إشراف لطلب إشراف */
    public function issueSupervision(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_number' => ['required', 'string', 'max:40'],
            'engineer' => ['required', 'array'],
            'engineer.syndicate_number' => ['required', 'string', 'max:50'],
            'engineer.national_id' => ['nullable', 'digits:14'],
            'engineer.name' => ['nullable', 'string', 'max:255'],
            'engineer.phone' => ['nullable', 'string', 'max:30'],
            'engineer.email' => ['nullable', 'email', 'max:255'],
            'engineer.specialization' => ['nullable', 'string', 'max:100'],
            'engineer.registration_number' => ['nullable', 'string', 'max:50'],
            'engineer.address' => ['nullable', 'string', 'max:255'],
            'office' => ['nullable', 'array'],
            'office.name' => ['nullable', 'string', 'max:255'],
            'office.registration_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'transaction_number' => 'رقم المعاملة',
            'engineer.syndicate_number' => 'رقم عضوية النقابة',
            'engineer.national_id' => 'الرقم القومي للمهندس',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $model = $this->findRequest((string) $request->input('transaction_number'));
        if (! $model) {
            return $this->notFound('request_not_found', 'لا توجد معاملة بهذا الرقم.');
        }

        return $this->guard(function () use ($request, $model) {
            $engineer = (array) $request->input('engineer', []);
            $engineer['office_name'] = $request->input('office.name');
            $engineer['office_registration_number'] = $request->input('office.registration_number');

            $certificate = $this->service->issueSupervisionCertificate($model, $engineer, Actor::current(), $request->input('notes'));

            return ApiResponse::data(Transformers::certificate($certificate->fresh(['project', 'request', 'cancels'])), 201, [],
                ['Location' => route('syndicate.api.certificates.show', $certificate->number)]);
        });
    }

    /** POST cancellations/{transaction_number}/certificate — إصدار شهادة إلغاء إشراف */
    public function issueCancellation(Request $request, string $transactionNumber): JsonResponse
    {
        $validator = Validator::make($request->all(), ['notes' => ['nullable', 'string', 'max:2000']]);
        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $model = $this->findRequest($transactionNumber);
        if (! $model || $model->type !== SyndicateRequest::TYPE_CANCELLATION) {
            return $this->notFound('request_not_found', 'لا يوجد طلب إلغاء إشراف بهذا الرقم.');
        }

        return $this->guard(function () use ($request, $model) {
            $certificate = $this->service->issueCancellationCertificate($model, Actor::current(), $request->input('notes'));

            return ApiResponse::data(Transformers::certificate($certificate->fresh(['project', 'request', 'cancels'])), 201,
                ['project_work_status' => Project::WORK_SUSPENDED],
                ['Location' => route('syndicate.api.certificates.show', $certificate->number)]);
        });
    }

    /** GET certificates/{number} */
    public function showCertificate(string $number): JsonResponse
    {
        $certificate = Certificate::with(['project.activeCertificate', 'request', 'cancels'])->where('number', $number)->first();
        if (! $certificate) {
            return $this->notFound('certificate_not_found', 'لا توجد شهادة بهذا الرقم.');
        }

        $data = Transformers::certificate($certificate);
        if ($certificate->project) {
            $data['included'] = [Transformers::project($certificate->project)];
        }

        return ApiResponse::data($data);
    }

    /** GET projects/{id}/timeline */
    public function timeline(string $id): JsonResponse
    {
        $project = ctype_digit($id) ? Project::with('activeCertificate')->find($id) : Project::with('activeCertificate')->where('code', $id)->first();
        if (! $project) {
            return $this->notFound('project_not_found', 'لا يوجد مشروع بهذا المعرف.');
        }

        $events = $project->events()->with(['request', 'certificate'])->get();

        return ApiResponse::data(
            $events->map(fn ($e) => Transformers::event($e))->values(),
            200,
            ['project' => Transformers::project($project)],
        );
    }

    /** POST engineers/sync — مزامنة دليل المهندسين */
    public function syncEngineers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'engineers' => ['required', 'array', 'min:1', 'max:500'],
            'engineers.*.syndicate_number' => ['required', 'string', 'max:50'],
            'engineers.*.name' => ['required', 'string', 'max:255'],
            'engineers.*.national_id' => ['nullable', 'digits:14'],
            'engineers.*.phone' => ['nullable', 'string', 'max:30'],
            'engineers.*.email' => ['nullable', 'email'],
            'engineers.*.specialization' => ['nullable', 'string', 'max:100'],
            'engineers.*.grade' => ['nullable', 'string', 'max:50'],
            'engineers.*.registration_number' => ['nullable', 'string', 'max:50'],
            'engineers.*.office_name' => ['nullable', 'string', 'max:255'],
            'engineers.*.office_registration_number' => ['nullable', 'string', 'max:50'],
            'engineers.*.membership_status' => ['nullable', Rule::in(['active', 'suspended', 'expired'])],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        // Only validated keys: never user_id / synced_at from the client (mass assignment).
        $count = $this->service->syncEngineers($validator->validated()['engineers']);

        return ApiResponse::data(['type' => 'engineer-sync', 'attributes' => ['synced' => $count]]);
    }

    // ─────────────────────────────

    protected function findRequest(string $transactionNumber): ?SyndicateRequest
    {
        return SyndicateRequest::with(['project.activeCertificate', 'targetCertificate', 'certificate'])
            ->where('transaction_number', strtoupper(trim($transactionNumber)))
            ->first();
    }

    protected function guard(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (SyndicateException $e) {
            return ApiResponse::error($e->status, $e->errorCode, 'تعذر تنفيذ العملية', $e->getMessage());
        }
    }

    protected function notFound(string $code, string $detail): JsonResponse
    {
        return ApiResponse::error(404, $code, 'غير موجود', $detail);
    }
}
