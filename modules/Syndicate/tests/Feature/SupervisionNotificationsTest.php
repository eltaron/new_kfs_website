<?php

namespace Modules\Syndicate\tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Modules\Syndicate\Channels\LogSmsGateway;
use Modules\Syndicate\Channels\SmsChannel;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Notifications\SupervisionCancelledNotification;
use Modules\Syndicate\Notifications\SupervisionCertificateIssuedNotification;
use Modules\Syndicate\Notifications\SupervisionRestoredNotification;
use Modules\Syndicate\Notifications\SyndicateRequestNotification;
use Tests\TestCase;

class SupervisionNotificationsTest extends TestCase
{
    use DatabaseTransactions, SyndicateFixtures;

    protected User $owner;
    protected User $engineerUser;
    protected User $councilUser;
    protected User $syndicateUser;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->owner = User::factory()->create(['national_id' => $this->nid(), 'phone' => '01012345678']);
        $this->engineerUser = User::factory()->create(['national_id' => $this->nid()]);
        $this->councilUser = $this->userWithRole(config('syndicate.roles.council'));
        $this->syndicateUser = $this->userWithRole(config('syndicate.roles.syndicate'));
    }

    public function test_request_submission_notifies_syndicate_staff_and_applicant(): void
    {
        $project = $this->makeProject(['owner_national_id' => $this->owner->national_id]);
        $this->assertSame($this->owner->getKey(), $project->owner_user_id, 'الربط بحساب المواطن بالرقم القومي');

        $this->submit($project);

        Notification::assertSentTo($this->syndicateUser, SyndicateRequestNotification::class, fn ($n) => $n->audience === 'syndicate');
        Notification::assertSentTo($this->owner, SyndicateRequestNotification::class, fn ($n) => $n->audience === 'applicant');
    }

    public function test_citizen_engineer_and_council_are_notified_through_full_lifecycle(): void
    {
        $project = $this->makeProject(['owner_national_id' => $this->owner->national_id]);

        // صدور الإشراف ⇒ المالك + المهندس + المجلس
        $first = $this->service()->issueSupervisionCertificate($this->submit($project),
            $this->engineerData(['national_id' => $this->engineerUser->national_id]), $this->actor());

        Notification::assertSentTo($this->owner, SupervisionCertificateIssuedNotification::class,
            fn ($n, $channels) => $n->audience === 'owner' && $n->certificate->is($first) && in_array('database', $channels, true));
        Notification::assertSentTo($this->engineerUser, SupervisionCertificateIssuedNotification::class, fn ($n) => $n->audience === 'engineer');
        Notification::assertSentTo($this->councilUser, SupervisionCertificateIssuedNotification::class, fn ($n) => $n->audience === 'council');

        // قنوات المالك: قاعدة بيانات + بريد + SMS (لديه هاتف)
        Notification::assertSentTo($this->owner, SupervisionCertificateIssuedNotification::class,
            fn ($n, $channels) => in_array('mail', $channels, true) && in_array(SmsChannel::class, $channels, true));

        // الإلغاء ⇒ الجميع يُخطر بوجوب إيقاف الأعمال
        $cancel = $this->submit($project, SyndicateRequest::TYPE_CANCELLATION, SyndicateRequest::BY_ENGINEER);
        $this->service()->issueCancellationCertificate($cancel, $this->actor());

        foreach ([[$this->owner, 'owner'], [$this->engineerUser, 'engineer'], [$this->councilUser, 'council']] as [$user, $audience]) {
            Notification::assertSentTo($user, SupervisionCancelledNotification::class,
                fn ($n) => $n->audience === $audience && str_contains($n->title(), 'إيقاف الأعمال'));
        }

        // مهندس جديد ⇒ المجلس + المالك + الجديد + السابق على نفس المشروع
        $newEngineer = User::factory()->create(['national_id' => $this->nid()]);
        $this->service()->issueSupervisionCertificate($this->submit($project),
            $this->engineerData(['national_id' => $newEngineer->national_id]), $this->actor());

        Notification::assertSentTo($this->councilUser, SupervisionRestoredNotification::class, fn ($n) => $n->audience === 'council' && $n->wasSuspended);
        Notification::assertSentTo($this->owner, SupervisionRestoredNotification::class, fn ($n) => $n->audience === 'owner' && $n->project->is($project));
        Notification::assertSentTo($newEngineer, SupervisionRestoredNotification::class, fn ($n) => $n->audience === 'engineer');
        Notification::assertSentTo($this->engineerUser, SupervisionRestoredNotification::class,
            fn ($n) => $n->audience === 'previous_engineer' && $n->previousCertificate?->is($first));
    }

    public function test_recipients_without_portal_account_are_notified_on_demand_by_mail_and_sms(): void
    {
        $project = $this->makeProject(['owner_email' => 'no-account@example.test', 'owner_phone' => '01099990000']);

        $this->service()->issueSupervisionCertificate($this->submit($project), $this->engineerData(), $this->actor());

        Notification::assertSentOnDemand(SupervisionCertificateIssuedNotification::class,
            function ($n, $channels, AnonymousNotifiable $notifiable) {
                return $n->audience === 'owner'
                    && $notifiable->routeNotificationFor('mail') === 'no-account@example.test'
                    && in_array(SmsChannel::class, $channels, true);
            });
    }

    public function test_database_payload_and_sms_channel_render(): void
    {
        [$project, $certificate] = $this->activeProject();
        $notification = new SupervisionCancelledNotification($project, $certificate, null, 'council', $certificate);

        $payload = $notification->toDatabase($this->councilUser);
        $this->assertSame('danger', $payload['status']);
        $this->assertSame('filament', $payload['format']);
        $this->assertSame($project->code, $payload['syndicate']['project_code']);
        $this->assertStringContainsString('/council/projects/'.$project->getKey(), $notification->url($this->councilUser));

        LogSmsGateway::$sent = [];
        app(SmsChannel::class)->send((new AnonymousNotifiable())->route('sms', '01055554444'), $notification);
        $this->assertSame('01055554444', LogSmsGateway::$sent[0]['to'] ?? null);
        $this->assertStringContainsString('إيقاف الأعمال', LogSmsGateway::$sent[0]['message']);
    }

    public function test_outbound_webhook_is_signed_when_enabled(): void
    {
        config(['syndicate.webhook' => ['enabled' => true, 'url' => 'https://syndicate.example.test/hook', 'secret' => 's3cret', 'timeout' => 5]]);
        Http::fake();

        $this->submit($this->makeProject());

        Http::assertSent(function ($request) {
            $expected = 'sha256='.hash_hmac('sha256', $request->body(), 's3cret');

            return $request->url() === 'https://syndicate.example.test/hook'
                && $request->header('X-Syndicate-Signature')[0] === $expected
                && $request['event'] === 'request.submitted';
        });
    }
}
