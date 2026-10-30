<?php

namespace Modules\Syndicate\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Syndicate\Events\SupervisionCancelled;
use Modules\Syndicate\Events\SupervisionCertificateIssued;
use Modules\Syndicate\Events\SyndicateRequestStatusChanged;
use Modules\Syndicate\Events\SyndicateRequestSubmitted;

/**
 * إشعار صادر اختياري (Webhook) إلى نظام النقابة. يُفعل من config('syndicate.webhook.enabled').
 * التوقيع: X-Syndicate-Signature = sha256 HMAC لجسم الطلب بالمفتاح SYNDICATE_WEBHOOK_SECRET.
 */
class SendOutboundWebhook
{
    public function handle(object $event): void
    {
        $config = config('syndicate.webhook');
        if (empty($config['enabled']) || blank($config['url'] ?? null)) {
            return;
        }

        $payload = $this->payload($event);
        if (! $payload) {
            return;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            Http::timeout((int) ($config['timeout'] ?? 10))
                ->withHeaders([
                    'X-Syndicate-Event' => $payload['event'],
                    'X-Syndicate-Signature' => 'sha256=' . hash_hmac('sha256', $body, (string) ($config['secret'] ?? '')),
                ])
                ->withBody($body, 'application/json')
                ->post($config['url']);
        } catch (\Throwable $e) {
            Log::warning('[syndicate-webhook] ' . $e->getMessage());
        }
    }

    protected function payload(object $event): ?array
    {
        $data = match (true) {
            $event instanceof SyndicateRequestSubmitted => [
                'event' => 'request.submitted',
                'transaction_number' => $event->request->transaction_number,
                'type' => $event->request->type,
                'project_id' => $event->request->project_id,
            ],
            $event instanceof SyndicateRequestStatusChanged => [
                'event' => 'request.status_changed',
                'transaction_number' => $event->request->transaction_number,
                'from' => $event->from,
                'to' => $event->to,
            ],
            $event instanceof SupervisionCertificateIssued => [
                'event' => 'certificate.supervision_issued',
                'certificate_number' => $event->certificate->number,
                'project_id' => $event->certificate->project_id,
            ],
            $event instanceof SupervisionCancelled => [
                'event' => 'certificate.cancellation_issued',
                'certificate_number' => $event->cancellation->number,
                'cancelled_certificate_number' => $event->cancelled->number,
                'project_id' => $event->cancellation->project_id,
                'work_status' => 'suspended',
            ],
            default => null,
        };

        return $data ? $data + ['sent_at' => now()->toIso8601String()] : null;
    }
}
