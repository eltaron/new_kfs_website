<?php

namespace Modules\Syndicate\Listeners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Syndicate\Events\SupervisionCancelled;
use Modules\Syndicate\Events\SupervisionCertificateIssued;
use Modules\Syndicate\Events\SyndicateRequestStatusChanged;
use Modules\Syndicate\Events\SyndicateRequestSubmitted;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Notifications\SupervisionCancelledNotification;
use Modules\Syndicate\Notifications\SupervisionCertificateIssuedNotification;
use Modules\Syndicate\Notifications\SupervisionRestoredNotification;
use Modules\Syndicate\Notifications\SyndicateRequestNotification;
use Modules\Syndicate\Notifications\SyndicateNotification;
use Modules\Syndicate\Services\RecipientResolver;

/** يوزع الإشعارات على الأطراف عند كل حدث نطاق. فشل قناة لا يُفشل العملية. */
class SendSyndicateNotifications
{
    public function __construct(protected RecipientResolver $recipients)
    {
    }

    public function onRequestSubmitted(SyndicateRequestSubmitted $event): void
    {
        $request = $event->request->loadMissing('project');

        $this->send($this->recipients->syndicateStaff(), new SyndicateRequestNotification($request, 'syndicate', 'submitted'));
        $this->send($this->recipients->applicant($request), new SyndicateRequestNotification($request, 'applicant', 'submitted'));
    }

    public function onRequestStatusChanged(SyndicateRequestStatusChanged $event): void
    {
        if (! in_array($event->to, [SyndicateRequest::STATUS_REJECTED, SyndicateRequest::STATUS_UNDER_REVIEW], true)) {
            return;
        }

        $request = $event->request->loadMissing('project');
        $kind = $event->to === SyndicateRequest::STATUS_REJECTED ? 'rejected' : 'under_review';

        $this->send($this->recipients->applicant($request), new SyndicateRequestNotification($request, 'applicant', $kind));
        if ($kind === 'rejected') {
            $this->send($this->recipients->council(), new SyndicateRequestNotification($request, 'council', $kind));
        }
    }

    public function onSupervisionIssued(SupervisionCertificateIssued $event): void
    {
        $certificate = $event->certificate->loadMissing(['project', 'request', 'engineer']);
        $project = $certificate->project;
        $request = $certificate->request;
        $previous = $event->previousCertificate;

        $isReturn = $event->previousWorkStatus === Project::WORK_SUSPENDED || $previous !== null;

        if (! $isReturn) {
            foreach (['owner' => $this->recipients->owner($project), 'engineer' => $this->recipients->engineer($certificate), 'council' => $this->recipients->council()] as $audience => $to) {
                $this->send($to, new SupervisionCertificateIssuedNotification($project, $certificate, $request, $audience));
            }

            return;
        }

        // عودة المشروع للعمل / تغيير المهندس: الجميع على نفس الخط الزمني بما فيهم المهندس السابق.
        $wasSuspended = $event->previousWorkStatus === Project::WORK_SUSPENDED;
        $audiences = [
            'council' => $this->recipients->council(),
            'owner' => $this->recipients->owner($project),
            'engineer' => $this->recipients->engineer($certificate),
            'previous_engineer' => $previous && $previous->engineer_syndicate_number !== $certificate->engineer_syndicate_number
                ? $this->recipients->engineer($previous) : collect(),
        ];

        foreach ($audiences as $audience => $to) {
            $this->send($to, new SupervisionRestoredNotification($project, $certificate, $request, $audience, $previous, $wasSuspended));
        }
    }

    public function onSupervisionCancelled(SupervisionCancelled $event): void
    {
        $cancellation = $event->cancellation->loadMissing(['project', 'request']);
        $project = $cancellation->project;

        foreach (['owner' => $this->recipients->owner($project), 'engineer' => $this->recipients->engineer($event->cancelled), 'council' => $this->recipients->council()] as $audience => $to) {
            $this->send($to, new SupervisionCancelledNotification($project, $cancellation, $cancellation->request, $audience, $event->cancelled));
        }
    }

    protected function send(Collection $recipients, SyndicateNotification $notification): void
    {
        foreach ($recipients as $recipient) {
            try {
                Notification::send($recipient, clone $notification);
            } catch (\Throwable $e) {
                Log::error('[syndicate] notification failed: ' . $e->getMessage(), ['notification' => $notification::class]);
            }
        }
    }
}
