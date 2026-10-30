<?php

namespace Modules\Syndicate\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Services\SupervisionService;
use Modules\Syndicate\Support\Actor;
use Modules\Syndicate\Support\SyndicateException;

class SyndicateWebController extends Controller
{
    /** صفحة التحقق العامة من الشهادات. */
    public function verifyForm(Request $request): View|RedirectResponse
    {
        if ($request->filled('number')) {
            $request->validate(['number' => ['string', 'max:40', 'regex:/^[A-Za-z0-9\-]+$/']]);

            return redirect()->route('syndicate.verify.show', ['number' => strtoupper(trim($request->query('number')))]);
        }

        return view('syndicate::web.verify');
    }

    public function verify(Request $request, string $number): View
    {
        $certificate = Certificate::with(['project', 'cancels', 'cancellation'])
            ->where('number', strtoupper($number))->first();

        $full = $certificate && $certificate->verification_code
            && hash_equals((string) $certificate->verification_code, (string) $request->query('c', ''));

        return view('syndicate::web.verify', [
            'number' => strtoupper($number),
            'certificate' => $certificate,
            'full' => $full,
        ]);
    }

    /** مشروعات وشهادات المواطن / المهندس الحالي. */
    public function myProjects(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $projects = Project::visibleToCitizen($user)
            ->with(['activeCertificate', 'certificates', 'requests', 'events' => fn ($q) => $q->limit(15)])
            ->latest('updated_at')->get();

        return view('syndicate::web.my-projects', [
            'user' => $user,
            'projects' => $projects,
        ]);
    }

    /** تقديم طلب إلكتروني (إشراف لمشروع موقوف/جديد أو إلغاء إشراف) لمشروع يخص المستخدم. */
    public function submitOnline(Request $request, Project $project, SupervisionService $service): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(Project::visibleToCitizen($user)->whereKey($project->getKey())->exists(), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in([SyndicateRequest::TYPE_SUPERVISION, SyndicateRequest::TYPE_CANCELLATION])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $isOwner = $project->owner_user_id === $user->getKey() || ($user->national_id && $user->national_id === $project->owner_national_id);
        $requestedBy = $isOwner ? SyndicateRequest::BY_CITIZEN : SyndicateRequest::BY_ENGINEER;

        try {
            $created = $service->submitRequest($project, $data['type'], [
                'name' => $user->name,
                'national_id' => $user->national_id,
                'phone' => $user->phone,
                'email' => $user->email,
                'user_id' => $user->getKey(),
            ], $requestedBy, Actor::fromUser($user), 'online', $data['reason'] ?? null);
        } catch (SyndicateException $e) {
            return back()->withErrors(['syndicate' => $e->getMessage()]);
        }

        return back()->with('syndicate_success', 'تم تسجيل طلبكم برقم المعاملة ' . $created->transaction_number . '. سيظهر لدى نقابة المهندسين ومجلس المدينة.');
    }

    /** نسخة الطباعة (PDF عبر طباعة المتصفح) مع رمز QR للتحقق. */
    public function print(Request $request, string $number): View
    {
        $certificate = Certificate::with(['project', 'cancels', 'request'])->where('number', strtoupper($number))->firstOrFail();

        /** @var User $user */
        $user = $request->user();
        abort_unless($this->canPrint($user, $certificate), 403);

        return view('syndicate::print.certificate', ['certificate' => $certificate]);
    }

    protected function canPrint(User $user, Certificate $certificate): bool
    {
        $roles = config('syndicate.roles');
        if ($user->hasAnyRole(array_merge([$roles['syndicate'], $roles['council']], $roles['council_extra'], $roles['admins']))) {
            return true;
        }

        return filled($user->national_id)
            && in_array($user->national_id, [$certificate->owner_national_id, $certificate->engineer_national_id], true);
    }
}
