<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Citizen\Models\Complaint;
use Modules\Citizen\Models\ContactMessage;
use Modules\Citizen\Models\EmergencyReport;
use Modules\Estidama\Models\Enrollment;
use Modules\Gis\Models\GisSubmission;
use Modules\Citizen\Models\ServiceSubmission;
use Modules\Citizen\Models\Suggestion;
use Modules\Estidama\Models\TrainingApplication;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvSettings;
use Modules\SiteValidity\Support\SvStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $data = $this->getAllData();
        $data['chartData'] = $this->getChartData($data['userId'], $this->identityVerified($data['user']) ? $data['nationalId'] : '__no_match__');
        $data['activeNav'] = 'overview';

        return view('citizen.dashboard.index', $data);
    }

    public function services()
    {
        $data = $this->getAllData();
        $data['activeNav'] = 'services';

        return view('citizen.dashboard.services', $data);
    }

    public function gis()
    {
        $data = $this->getAllData();
        $data['activeNav'] = 'gis';

        return view('citizen.dashboard.gis', $data);
    }

    public function estidama()
    {
        $data = $this->getAllData();
        $data['activeNav'] = 'estidama';

        return view('citizen.dashboard.estidama', $data);
    }

    public function monitoring()
    {
        $data = $this->getAllData();
        $data['activeNav'] = 'monitoring';

        return view('citizen.dashboard.monitoring', $data);
    }

    public function fulfillment()
    {
        $data = $this->getAllData();
        $data['activeNav'] = 'fulfillment';

        return view('citizen.dashboard.fulfillment', $data);
    }

    public function show($id)
    {
        $submission = ServiceSubmission::with('service')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return view('citizen.submissions.show', compact('submission'));
    }

    private function getAllData(): array
    {
        $user = Auth::user();
        $nationalId = $user->national_id;
        $userEmail = $user->email;
        $userId = $user->id;

        // SECURITY / KNOWN LIMITATION: complaints, suggestions, training applications,
        // contact messages and emergency reports have no user_id column; they are
        // linked only by the national_id / email typed into public forms, so anyone
        // can file a record under another person's identifier. We therefore only
        // surface identifier-matched records to accounts whose identity was verified
        // by staff (status = approved) or staff accounts, and never to pending ones.
        // A proper fix is to add a user_id foreign key to those tables.
        $identityVerified = $this->identityVerified($user);
        $matchNid = $identityVerified && filled($nationalId) ? $nationalId : '__no_match__';
        $matchEmail = $identityVerified && filled($userEmail) ? $userEmail : '__no_match__';

        $complaints = Complaint::where('national_id', $matchNid)->latest()->get();
        $suggestions = Suggestion::where('national_id', $matchNid)->latest()->get();
        $serviceSubmissions = ServiceSubmission::where('user_id', $userId)->with('service')->latest()->get();
        $gisSubmissions = GisSubmission::where('user_id', $userId)->with('subService')->latest()->get();
        $enrollments = Enrollment::where('user_id', $userId)->with('trainingProgram')->latest()->get();
        $trainingApplications = TrainingApplication::where('national_id', $matchNid)->with('trainingProgram')->latest()->get();
        $contactMessages = ContactMessage::where('email', $matchEmail)->latest()->get();
        $emergencyReports = EmergencyReport::where('reporter_national_id', $matchNid)->latest()->get();
        $fulfillmentItems = $this->getUserFulfillments($userId);
        $stats = $this->getStats($serviceSubmissions, $gisSubmissions, $enrollments, $trainingApplications, $complaints, $suggestions, $contactMessages, $emergencyReports);

        return [
            'user' => $user,
            'nationalId' => $nationalId,
            'userId' => $userId,
            'complaints' => $complaints,
            'suggestions' => $suggestions,
            'serviceSubmissions' => $serviceSubmissions,
            'gisSubmissions' => $gisSubmissions,
            'enrollments' => $enrollments,
            'trainingApplications' => $trainingApplications,
            'contactMessages' => $contactMessages,
            'emergencyReports' => $emergencyReports,
            'fulfillmentItems' => $fulfillmentItems,
            'fulfillmentCount' => $fulfillmentItems->count(),
            'stats' => $stats,
        ];
    }
    /**
     * Whether identifier-matched records (national_id / email) may be shown.
     */
    private function identityVerified($user): bool
    {
        return $user->status === 'approved'
            || \App\Services\Security\TwoFactorAuthenticator::isStaff($user);
    }

    private function getStats($serviceSubmissions, $gisSubmissions, $enrollments, $trainingApplications, $complaints, $suggestions, $contactMessages, $emergencyReports): array
    {
        return [
            'services' => $serviceSubmissions->count(),
            'gis' => $gisSubmissions->count(),
            'estidama' => $enrollments->count() + $trainingApplications->count(),
            'complaints' => $complaints->count(),
            'suggestions' => $suggestions->count(),
            'contactMessages' => $contactMessages->count(),
            'emergencyReports' => $emergencyReports->count(),
        ];
    }

    private function getUserFulfillments($userId): Collection
    {
        $items = collect();

        ServiceSubmission::where('user_id', $userId)
            ->with('service')
            ->latest()
            ->get()
            ->each(function ($item) use ($items) {
                $action = match ($item->status) {
                    'pending' => 'مراجعة بيانات',
                    'awaiting_payment' => 'دفع رسوم',
                    'paid' => 'مكتمل',
                    'completed' => 'مكتمل',
                    'rejected' => 'إعادة تقديم',
                    default => '—',
                };
                if ($action !== 'مكتمل' && $action !== '—') {
                    $items->push((object) [
                        'id' => $item->id,
                        'source' => 'خدمة عامة',
                        'type' => 'service',
                        'reference' => $item->service?->name ?? '#' . $item->id,
                        'status' => $item->status,
                        'fulfillment_action' => $action,
                        'fulfillment_status' => $item->fulfillment_status,
                        'fulfillment_action_type' => $item->fulfillment_action,
                        'fulfillment_reason' => $item->fulfillment_reason,
                        'fulfillment_data_fields' => $item->fulfillment_data_fields,
                        'created_at' => $item->created_at,
                    ]);
                }
            });

        GisSubmission::where('user_id', $userId)
            ->with('subService')
            ->latest()
            ->get()
            ->each(function ($item) use ($items) {
                $action = match ($item->status) {
                    'received' => 'مراجعة بيانات',
                    'processing' => $item->payment_status === 'paid' ? 'مستندات ناقصة' : 'دفع رسوم',
                    'completed' => 'مكتمل',
                    'rejected' => 'إعادة تقديم',
                    default => '—',
                };
                if ($action !== 'مكتمل' && $action !== '—') {
                    $items->push((object) [
                        'id' => $item->id,
                        'source' => 'خدمات مكانية',
                        'type' => 'gis',
                        'reference' => $item->subService?->name ?? '#' . $item->id,
                        'status' => $item->status,
                        'fulfillment_action' => $action,
                        'fulfillment_status' => $item->fulfillment_status,
                        'fulfillment_action_type' => $item->fulfillment_action,
                        'fulfillment_reason' => $item->fulfillment_reason,
                        'fulfillment_data_fields' => $item->fulfillment_data_fields,
                        'created_at' => $item->created_at,
                    ]);
                }
            });

        // صلاحية الموقع: استيفاء مطلوب من المواطن، أو رسوم لم تُسدد بعد (إن كان السداد إلزامياً)
        if (class_exists(SiteValidityRequest::class) && \Illuminate\Support\Facades\Schema::hasTable('sv_requests')) {
            $paymentRequired = SvSettings::paymentRequired();

            SiteValidityRequest::where('user_id', $userId)
                ->whereNotIn('status', [SvStatus::COMPLETED, SvStatus::REJECTED])
                ->with('serviceType')
                ->latest()
                ->get()
                ->each(function (SiteValidityRequest $item) use ($items, $paymentRequired) {
                    $action = match (true) {
                        $item->status === SvStatus::COMPLETION_REQUESTED => 'استيفاء مطلوب',
                        $paymentRequired && $item->canBePaid() => 'دفع رسوم',
                        default => null,
                    };
                    if ($action === null) {
                        return;
                    }
                    $items->push((object) [
                        'id' => $item->id,
                        'source' => 'صلاحية الموقع',
                        'type' => 'site_validity',
                        'reference' => trim(($item->serviceType?->name ?? 'طلب صلاحية موقع') . ' — ' . ($item->number ?: '#' . $item->id)),
                        'status' => $item->status,
                        'fulfillment_action' => $action,
                        'fulfillment_status' => null,
                        'fulfillment_action_type' => null,
                        'fulfillment_reason' => $action === 'استيفاء مطلوب' ? $item->openCompletion()?->notes : null,
                        'fulfillment_data_fields' => null,
                        'amount' => $item->payableAmount(),
                        'created_at' => $item->submitted_at ?? $item->created_at,
                    ]);
                });
        }

        return $items->sortByDesc('created_at');
    }

    private function getChartData($userId, $nationalId): array
    {
        $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));

        $serviceData = $days->map(fn($day) =>
            ServiceSubmission::where('user_id', $userId)->whereDate('created_at', $day)->count()
        );

        $gisData = $days->map(fn($day) =>
            GisSubmission::where('user_id', $userId)->whereDate('created_at', $day)->count()
        );

        $complaintsData = $days->map(fn($day) =>
            Complaint::where('national_id', $nationalId)->whereDate('created_at', $day)->count()
        );

        return [
            'labels' => $days->map(fn($day) => now()->parse($day)->format('d/m'))->values()->toArray(),
            'services' => $serviceData->values()->toArray(),
            'gis' => $gisData->values()->toArray(),
            'complaints' => $complaintsData->values()->toArray(),
        ];
    }
}
