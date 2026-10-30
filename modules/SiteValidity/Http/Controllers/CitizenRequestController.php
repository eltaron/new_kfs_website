<?php

namespace Modules\SiteValidity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\SiteValidity\Http\Requests\StoreSiteValidityRequest;
use Modules\SiteValidity\Http\Requests\SubmitCompletionRequest;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Models\Geo\Village;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Services\WorkflowException;
use Modules\SiteValidity\Services\WorkflowService;
use Modules\SiteValidity\Support\SvSchedule;
use Modules\SiteValidity\Support\SvSettings;
use Modules\SiteValidity\Support\SvStatus;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** صفحات المواطن لخدمة «طلب صلاحية الموقع». */
class CitizenRequestController extends Controller
{
    public function __construct(protected WorkflowService $workflow)
    {
    }

    public function landing(): View
    {
        return view('sitevalidity::web.landing', [
            'types' => ServiceType::query()->active()->get(),
            'instructions' => SvSettings::lines('instructions'),
            'documents' => SvSettings::lines('required_documents'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('sitevalidity::web.apply', [
            'user' => $request->user(),
            'types' => ServiceType::query()->active()->get(),
            'centres' => Centre::query()->active()->get(['id', 'name']),
            'documents' => SvSettings::lines('required_documents'),
            'upload' => (array) config('site-validity.citizen_upload'),
        ]);
    }

    public function villages(int $centre): JsonResponse
    {
        $villages = Village::query()->active()->where('center_id', $centre)->get(['id', 'name', 'type']);

        return response()->json($villages->map(fn (Village $v) => [
            'id' => $v->id,
            'name' => $v->name,
            'type' => $v->typeLabel(),
        ])->values());
    }

    public function store(StoreSiteValidityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $centre = Centre::query()->findOrFail($data['center_id']);
        $village = Village::query()->findOrFail($data['village_id']);

        try {
            $svRequest = $this->workflow->submit($request->user(), [
                'service_type_id' => (int) $data['service_type_id'],
                'construction_type' => $data['construction_type'],
                'applicant_capacity' => $data['applicant_capacity'],
                'applicant_name' => trim($data['applicant_name']),
                'national_id' => $data['national_id'],
                'phone' => $data['phone'],
                'center_id' => $centre->id,
                'village_id' => $village->id,
                'center_name' => $centre->name,
                'village_name' => $village->name,
                'address' => trim($data['address']),
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
            ], [
                RequestFile::KIND_SKETCH => $request->file('sketch'),
                RequestFile::KIND_CONTRACT => $request->file('contract'),
                RequestFile::KIND_ID_CARD => $request->file('id_card'),
                RequestFile::KIND_POA => $request->file('poa'),
                RequestFile::KIND_FLOORS => $request->file('floors'),
            ]);
        } catch (WorkflowException $e) {
            return back()->withInput()->withErrors(['request' => $e->getMessage()]);
        }

        return redirect()->route('site-validity.requests.success', $svRequest);
    }

    public function success(Request $request, SiteValidityRequest $svRequest): View
    {
        $this->ensureOwner($request, $svRequest);

        return view('sitevalidity::web.success', ['svRequest' => $svRequest->load('serviceType')]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $requests = SiteValidityRequest::query()
            ->where('user_id', $user->getKey())
            ->with('serviceType')
            ->latest('id')
            ->paginate(10);

        return view('sitevalidity::web.index', [
            'user' => $user,
            'requests' => $requests,
            'pendingCompletion' => SiteValidityRequest::query()->where('user_id', $user->getKey())->where('status', SvStatus::COMPLETION_REQUESTED)->count(),
        ]);
    }

    public function show(Request $request, SiteValidityRequest $svRequest): View
    {
        $this->ensureOwner($request, $svRequest);
        $svRequest->load(['serviceType', 'completions', 'events', 'files.attachmentType']);

        return view('sitevalidity::web.show', [
            'user' => $request->user(),
            'svRequest' => $svRequest,
            'files' => $svRequest->files->whereIn('kind', RequestFile::CITIZEN_VISIBLE)
                ->reject(fn (RequestFile $f) => $f->kind === RequestFile::KIND_FINAL && ! $svRequest->finalReleased())->values(),
            'events' => $svRequest->events->filter->visibleToCitizen()->values(),
            'openCompletion' => $svRequest->completions->first(fn ($c) => $c->replied_at === null),
            'canReply' => $this->workflow->can($svRequest, $request->user(), 'submit_completion'),
            'canConfirmAppointment' => $canConfirm = $this->workflow->can($svRequest, $request->user(), 'confirm_appointment'),
            'availableDates' => $canConfirm ? SvSchedule::availableDates($svRequest) : [],
            'paymentRequired' => SvSettings::paymentRequired(),
        ]);
    }

    public function completion(SubmitCompletionRequest $request, SiteValidityRequest $svRequest): RedirectResponse
    {
        $this->ensureOwner($request, $svRequest);

        try {
            $this->workflow->submitCompletion($svRequest, $request->user(), (string) $request->validated('reply'), $request->file('files', []));
        } catch (WorkflowException $e) {
            return back()->withInput()->withErrors(['reply' => $e->getMessage()]);
        }

        return redirect()->route('site-validity.requests.show', $svRequest)->with('success', 'تم إرسال الاستيفاء إلى مهندس التنظيم.');
    }

    public function confirmAppointment(Request $request, SiteValidityRequest $svRequest): RedirectResponse
    {
        $this->ensureOwner($request, $svRequest);

        try {
            $this->workflow->confirmAppointment($svRequest, $request->user());
        } catch (WorkflowException $e) {
            return back()->withErrors(['appointment' => $e->getMessage()]);
        }

        return redirect()->route('site-validity.requests.show', $svRequest)->with('success', 'تم تأكيد موعد المعاينة. يرجى التواجد بالموقع في الموعد.');
    }

    /** رفض الموعد واختيار يوم من الأيام المتاحة لمركز الطلب. */
    public function rescheduleAppointment(Request $request, SiteValidityRequest $svRequest): RedirectResponse
    {
        $this->ensureOwner($request, $svRequest);
        $date = (string) $request->validate(
            ['date' => ['required', 'date_format:Y-m-d']],
            ['date.required' => 'اختر يوماً من الأيام المتاحة.', 'date.date_format' => 'اليوم المختار غير صحيح.'],
        )['date'];

        try {
            $this->workflow->citizenReschedule($svRequest, $request->user(), $date);
        } catch (WorkflowException $e) {
            return back()->withErrors(['appointment' => $e->getMessage()]);
        }

        return redirect()->route('site-validity.requests.show', $svRequest)
            ->with('success', 'تم تغيير موعد المعاينة إلى ' . $svRequest->appointment_at?->format('Y-m-d H:i') . ' وإخطار الموظفين.');
    }

    /** تنزيل ملف مصرح (المواطن صاحب الطلب أو الموظفون حسب RequestFilePolicy). */
    public function download(Request $request, RequestFile $file): StreamedResponse
    {
        abort_unless(Gate::allows('view', $file), 404);

        $disk = Storage::disk((string) config('site-validity.disk', 'local'));
        abort_if(str_contains($file->path, '..') || ! $disk->exists($file->path), 404);

        $name = $file->original_name ?: basename($file->path);
        $inline = $file->isImage() || $file->mime === 'application/pdf';

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
        ];

        return $inline && ! $request->boolean('download')
            ? $disk->response($file->path, $name, $headers)
            : $disk->download($file->path, $name, $headers);
    }

    /** المالك فقط — 404 لغيره حتى لا يُكشف وجود الطلب (IDOR). */
    protected function ensureOwner(Request $request, SiteValidityRequest $svRequest): void
    {
        abort_unless($svRequest->isOwnedBy($request->user()), 404);
    }
}
