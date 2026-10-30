<?php

namespace Modules\Syndicate\tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Syndicate\Filament\Resources\ProjectResource\Pages\CreateProject;
use Modules\Syndicate\Filament\Resources\ProjectResource\Pages\ViewProject;
use Modules\Syndicate\Filament\Resources\RequestResource\Pages\CreateRequest;
use Modules\Syndicate\Filament\Resources\RequestResource\Pages\ListRequests;
use Modules\Syndicate\Filament\Resources\RequestResource\Pages\ViewRequest;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Tests\TestCase;

/** سير العمل من داخل لوحتي Filament (مجلس المدينة ← النقابة) عبر Livewire. */
class SyndicatePanelActionsTest extends TestCase
{
    use DatabaseTransactions, SyndicateFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    protected function asCouncil(): void
    {
        $this->actingAs($this->userWithRole(config('syndicate.roles.council')));
        Filament::setCurrentPanel(Filament::getPanel('council'));
    }

    protected function asSyndicate(): void
    {
        $this->actingAs($this->userWithRole(config('syndicate.roles.syndicate')));
        Filament::setCurrentPanel(Filament::getPanel('syndicate'));
    }

    public function test_council_registers_project_and_request_then_syndicate_issues_and_cancels(): void
    {
        $this->asCouncil();
        $nid = $this->nid();

        Livewire::test(CreateProject::class)
            ->fillForm([
                'owner_name' => 'مالك من اللوحة', 'owner_national_id' => $nid, 'center' => 'بيلا',
                'address' => 'شارع اللوحة', 'works_type' => 'execution', 'execution_sub_type' => 'creation',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $project = Project::where('owner_national_id', $nid)->firstOrFail();
        $this->assertSame(Project::WORK_PENDING, $project->work_status);

        Livewire::test(CreateRequest::class)
            ->fillForm([
                'project_id' => $project->getKey(), 'type' => SyndicateRequest::TYPE_SUPERVISION,
                'requested_by' => SyndicateRequest::BY_CITIZEN, 'applicant_name' => 'مالك من اللوحة', 'applicant_national_id' => $nid,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $request = SyndicateRequest::where('project_id', $project->getKey())->firstOrFail();
        $this->assertSame('counter', $request->channel);

        // النقابة: إصدار شهادة الإشراف من صفحة الطلب
        $this->asSyndicate();
        Livewire::test(ListRequests::class)->assertCanSeeTableRecords([$request]);

        Livewire::test(ViewRequest::class, ['record' => $request->getKey()])
            ->assertActionVisible('issueSupervision')
            ->assertActionHidden('issueCancellation')
            ->callAction('markUnderReview')
            ->assertHasNoActionErrors();
        $this->assertSame(SyndicateRequest::STATUS_UNDER_REVIEW, $request->fresh()->status);

        Livewire::test(ViewRequest::class, ['record' => $request->getKey()])
            ->callAction('issueSupervision', data: [
                'syndicate_number' => 'PNL-'.uniqid(), 'name' => 'م. من اللوحة', 'national_id' => $this->nid(),
                'office_name' => 'مكتب اللوحة',
            ])
            ->assertHasNoActionErrors();
        $certificate = Certificate::where('request_id', $request->getKey())->firstOrFail();
        $this->assertSame('مكتب اللوحة', $certificate->office_name);
        $this->assertSame(Project::WORK_ACTIVE, $project->fresh()->work_status);

        // المجلس: تسجيل طلب إلغاء من صفحة المشروع
        $this->asCouncil();
        Livewire::test(ViewProject::class, ['record' => $project->getKey()])
            ->assertActionVisible('registerCancellationRequest')
            ->callAction('registerCancellationRequest', data: [
                'requested_by' => SyndicateRequest::BY_CITIZEN, 'applicant_name' => 'مالك من اللوحة', 'reason' => 'تغيير المهندس',
            ])
            ->assertHasNoActionErrors();
        $cancel = SyndicateRequest::where('project_id', $project->getKey())->where('type', 'cancellation')->firstOrFail();

        // النقابة: إصدار شهادة الإلغاء ⇒ موقوف
        $this->asSyndicate();
        Livewire::test(ViewRequest::class, ['record' => $cancel->getKey()])
            ->callAction('issueCancellation', data: ['notes' => 'بناء على طلب المالك'])
            ->assertHasNoActionErrors();
        $this->assertSame(Project::WORK_SUSPENDED, $project->fresh()->work_status);

        // المجلس يرى المشروع موقوفاً ويستطيع تسجيل طلب إشراف جديد
        $this->asCouncil();
        $this->get('/council/projects/'.$project->getKey())->assertOk()->assertSee('يجب إيقاف جميع الأعمال');
        Livewire::test(ViewProject::class, ['record' => $project->getKey()])
            ->assertActionHidden('registerCancellationRequest')
            ->assertActionVisible('registerSupervisionRequest');
    }

    public function test_business_rule_violation_is_shown_and_nothing_changes(): void
    {
        $request = $this->submit($this->makeProject());
        $this->service()->reject($request, 'سبب', $this->actor());

        $this->asSyndicate();
        Livewire::test(ViewRequest::class, ['record' => $request->getKey()])
            ->assertActionHidden('issueSupervision')
            ->assertActionHidden('reject');

        $this->asCouncil();
        Livewire::test(ViewRequest::class, ['record' => $request->getKey()])
            ->assertActionHidden('issueSupervision');
    }
}
