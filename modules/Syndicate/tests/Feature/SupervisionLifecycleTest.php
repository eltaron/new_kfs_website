<?php

namespace Modules\Syndicate\tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Engineer;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateEvent;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Support\SyndicateException;
use Tests\TestCase;

/** آلة الحالات: إشراف ⇒ نشط ⇒ إلغاء ⇒ موقوف ⇒ إشراف جديد ⇒ نشط. */
class SupervisionLifecycleTest extends TestCase
{
    use DatabaseTransactions, SyndicateFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_full_lifecycle_supervision_cancellation_suspension_and_restoration(): void
    {
        $year = now()->format('Y');
        $project = $this->makeProject();
        $this->assertSame(Project::WORK_PENDING, $project->work_status);
        $this->assertMatchesRegularExpression('/^SYN-PRJ-'.$year.'-\d{6}$/', $project->code);

        // 1) طلب إشراف برقم معاملة فريد
        $request = $this->submit($project);
        $this->assertMatchesRegularExpression('/^SYN-REQ-'.$year.'-\d{6}$/', $request->transaction_number);
        $this->assertSame(SyndicateRequest::STATUS_SUBMITTED, $request->status);

        // 2) النقابة تراجع ثم تصدر شهادة الإشراف
        $this->service()->markUnderReview($request, $this->actor());
        $first = $this->service()->issueSupervisionCertificate($request, $this->engineerData(['name' => 'م. الأول']), $this->actor());

        $this->assertMatchesRegularExpression('/^SYN-SUP-'.$year.'-\d{6}$/', $first->number);
        $this->assertSame(Certificate::STATUS_ACTIVE, $first->status);
        $this->assertSame(SyndicateRequest::STATUS_COMPLETED, $request->fresh()->status);
        $this->assertSame($first->getKey(), $request->fresh()->certificate_id);
        $project->refresh();
        $this->assertSame(Project::WORK_ACTIVE, $project->work_status);
        $this->assertSame($first->getKey(), $project->active_certificate_id);
        $this->assertNotNull(Engineer::where('syndicate_number', $first->engineer_syndicate_number)->first());
        $this->assertSame($project->owner_national_id, $first->owner_national_id, 'لقطة بيانات المشروع على الشهادة');

        // 3) طلب إلغاء من المهندس برقم معاملة خاص
        $cancel = $this->submit($project, SyndicateRequest::TYPE_CANCELLATION, SyndicateRequest::BY_ENGINEER);
        $this->assertMatchesRegularExpression('/^SYN-CRQ-'.$year.'-\d{6}$/', $cancel->transaction_number);
        $this->assertSame($first->getKey(), $cancel->target_certificate_id);

        // 4) شهادة الإلغاء ⇒ المشروع موقوف - بدون إشراف
        $cancellation = $this->service()->issueCancellationCertificate($cancel, $this->actor());
        $this->assertMatchesRegularExpression('/^SYN-CAN-'.$year.'-\d{6}$/', $cancellation->number);
        $this->assertSame(Certificate::KIND_CANCELLATION, $cancellation->kind);
        $this->assertSame($first->getKey(), $cancellation->cancels_certificate_id);
        $this->assertSame(Certificate::STATUS_CANCELLED, $first->fresh()->status);
        $this->assertNotNull($first->fresh()->cancelled_at);

        $project->refresh();
        $this->assertSame(Project::WORK_SUSPENDED, $project->work_status);
        $this->assertSame('موقوف - بدون إشراف', $project->work_status_label);
        $this->assertNotNull($project->suspended_at);
        $this->assertNull($project->active_certificate_id);

        // 5) شهادة إشراف جديدة لنفس المشروع ⇒ عودة للنشاط
        $second = $this->service()->issueSupervisionCertificate($this->submit($project), $this->engineerData(['name' => 'م. الثاني']), $this->actor());
        $project->refresh();
        $this->assertSame(Project::WORK_ACTIVE, $project->work_status);
        $this->assertNull($project->suspended_at);
        $this->assertSame($second->getKey(), $project->active_certificate_id);
        $this->assertSame(Certificate::STATUS_CANCELLED, $first->fresh()->status, 'الملغاة تبقى ملغاة');

        // الخط الزمني يحتوي كل المراحل بالترتيب
        $types = $project->events()->reorder('id')->pluck('type')->all();
        foreach ([SyndicateEvent::PROJECT_REGISTERED, SyndicateEvent::REQUEST_SUBMITTED, SyndicateEvent::REQUEST_UNDER_REVIEW,
            SyndicateEvent::SUPERVISION_ISSUED, SyndicateEvent::CANCELLATION_ISSUED, SyndicateEvent::WORK_SUSPENDED,
            SyndicateEvent::WORK_RESUMED] as $type) {
            $this->assertContains($type, $types);
        }
        $this->assertLessThan(array_search(SyndicateEvent::WORK_RESUMED, $types), array_search(SyndicateEvent::WORK_SUSPENDED, $types));
    }

    public function test_only_one_active_supervision_certificate_per_project(): void
    {
        [$project, $first] = $this->activeProject();

        // طلب إشراف جديد على مشروع نشط (تغيير المهندس) ⇒ القديمة "مستبدلة"
        $second = $this->service()->issueSupervisionCertificate($this->submit($project), $this->engineerData(), $this->actor());

        $this->assertSame(Certificate::STATUS_SUPERSEDED, $first->fresh()->status);
        $this->assertSame(1, Certificate::where('project_id', $project->getKey())->where('kind', 'supervision')->where('status', 'active')->count());
        $this->assertSame($second->getKey(), $project->fresh()->active_certificate_id);
    }

    public function test_cancellation_request_requires_an_active_certificate(): void
    {
        $project = $this->makeProject();

        $this->expectException(SyndicateException::class);
        $this->submit($project, SyndicateRequest::TYPE_CANCELLATION);
    }

    public function test_duplicate_open_request_of_same_type_is_rejected(): void
    {
        $project = $this->makeProject();
        $this->submit($project);

        try {
            $this->submit($project);
            $this->fail('كان يجب رفض الطلب المكرر.');
        } catch (SyndicateException $e) {
            $this->assertSame('open_request_exists', $e->errorCode);
        }
    }

    public function test_certificate_cannot_be_issued_twice_for_the_same_request(): void
    {
        $project = $this->makeProject();
        $request = $this->submit($project);
        $this->service()->issueSupervisionCertificate($request, $this->engineerData(), $this->actor());

        $this->expectException(SyndicateException::class);
        $this->service()->issueSupervisionCertificate($request, $this->engineerData(), $this->actor());
    }

    public function test_engineer_with_inactive_membership_cannot_be_assigned(): void
    {
        $engineer = Engineer::create(['syndicate_number' => 'SUSP-'.uniqid(), 'name' => 'م. موقوف', 'membership_status' => 'suspended']);
        $request = $this->submit($this->makeProject());

        try {
            $this->service()->issueSupervisionCertificate($request, ['syndicate_number' => $engineer->syndicate_number], $this->actor());
            $this->fail('كان يجب رفض المهندس الموقوف.');
        } catch (SyndicateException $e) {
            $this->assertSame('engineer_not_active', $e->errorCode);
        }
        $this->assertSame(SyndicateRequest::STATUS_SUBMITTED, $request->fresh()->status, 'لم يتغير شيء (rollback)');
        $this->assertSame(Project::WORK_PENDING, $request->project->fresh()->work_status);
    }

    public function test_rejected_request_cannot_receive_certificate_and_engineer_cannot_request_supervision(): void
    {
        $project = $this->makeProject();
        $request = $this->submit($project);
        $this->service()->reject($request, 'مستندات ناقصة', $this->actor());
        $this->assertSame('مستندات ناقصة', $request->fresh()->rejection_reason);

        try {
            $this->service()->issueSupervisionCertificate($request, $this->engineerData(), $this->actor());
            $this->fail();
        } catch (SyndicateException $e) {
            $this->assertSame('invalid_state', $e->errorCode);
        }

        $this->expectException(SyndicateException::class);
        $this->submit($project, SyndicateRequest::TYPE_SUPERVISION, SyndicateRequest::BY_ENGINEER);
    }

    public function test_legacy_certificate_model_alias_still_works(): void
    {
        [, $certificate] = $this->activeProject();

        $legacy = \App\Models\SyndicateCertificate::find($certificate->getKey());
        $this->assertNotNull($legacy);
        $this->assertSame('تنفيذ', $legacy->type_label);
        $this->assertSame('إنشاء', $legacy->sub_type_label);
    }
}
