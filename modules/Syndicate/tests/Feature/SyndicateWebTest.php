<?php

namespace Modules\Syndicate\tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Syndicate\Models\SyndicateRequest;
use Tests\TestCase;

/** الصفحات العامة: التحقق من الشهادة، الاستعلام العام، لوحة المواطن، الطباعة، وصلاحيات اللوحات. */
class SyndicateWebTest extends TestCase
{
    use DatabaseTransactions, SyndicateFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_public_verification_page_masks_data_unless_qr_code_is_present(): void
    {
        [$project, $certificate] = $this->activeProject(['name' => 'م. التحقق']);

        $this->get(route('syndicate.verify.show', $certificate->number))
            ->assertOk()
            ->assertSee('شهادة إشراف سارية')
            ->assertSee('م. التحقق')
            ->assertDontSee($project->owner_national_id);

        $this->get($certificate->verificationUrl())
            ->assertOk()
            ->assertSee($project->owner_national_id);

        $this->get(route('syndicate.verify.show', 'SYN-SUP-1999-000000'))->assertOk()->assertSee('لا توجد شهادة بهذا الرقم');
        $this->get(route('syndicate.verify.form', ['number' => strtolower($certificate->number)]))
            ->assertRedirect(route('syndicate.verify.show', $certificate->number));
    }

    public function test_citizen_lookup_by_national_id_certificate_and_transaction_number(): void
    {
        [$project, $certificate] = $this->activeProject();

        $this->get(route('citizen-lookup.search', ['national_id' => $project->owner_national_id]))
            ->assertOk()->assertSee($project->code)->assertSee($certificate->number)->assertSee('نشط - تحت الإشراف');

        $this->get(route('citizen-lookup.search', ['q' => $certificate->number]))
            ->assertRedirect(route('syndicate.verify.show', $certificate->number));

        $pending = $this->submit($this->makeProject());
        $this->get(route('citizen-lookup.search', ['q' => $pending->transaction_number]))
            ->assertOk()->assertSee('مقدم');

        $this->get(route('citizen-lookup.search', ['q' => '00000000000000']))->assertOk()->assertSee('لم يتم العثور على نتائج');
        $this->get(route('citizen-lookup.search', ['q' => 'bad value!']))->assertSessionHasErrors('q');
        $this->get(route('citizen-lookup'))->assertOk();
    }

    public function test_my_projects_requires_login_and_lists_owner_projects_with_online_cancellation(): void
    {
        $owner = User::factory()->create(['national_id' => $this->nid()]);
        [$project] = $this->activeProject([], ['owner_national_id' => $owner->national_id]);

        $this->get(route('syndicate.my-projects'))->assertRedirect();

        $this->actingAs($owner)->get(route('syndicate.my-projects'))
            ->assertOk()->assertSee($project->code)->assertSee('طلب إلغاء الإشراف');

        $this->actingAs($owner)->post(route('syndicate.my-projects.requests', $project), ['type' => 'cancellation', 'reason' => 'تغيير المهندس'])
            ->assertRedirect()->assertSessionHas('syndicate_success');

        $request = SyndicateRequest::where('project_id', $project->getKey())->where('type', 'cancellation')->firstOrFail();
        $this->assertSame('online', $request->channel);
        $this->assertSame(SyndicateRequest::BY_CITIZEN, $request->requested_by);

        // مستخدم آخر لا يستطيع التقديم على مشروع لا يخصه
        $stranger = User::factory()->create(['national_id' => $this->nid()]);
        $this->actingAs($stranger)->post(route('syndicate.my-projects.requests', $project), ['type' => 'supervision'])->assertForbidden();
    }

    public function test_print_view_is_restricted_to_parties_and_staff(): void
    {
        $owner = User::factory()->create(['national_id' => $this->nid()]);
        [, $certificate] = $this->activeProject([], ['owner_national_id' => $owner->national_id]);

        $this->actingAs(User::factory()->create(['national_id' => $this->nid()]))
            ->get($certificate->printUrl())->assertForbidden();

        $this->actingAs($owner)->get($certificate->printUrl())
            ->assertOk()->assertSee($certificate->number)->assertSee('<svg', false);

        $this->actingAs($this->userWithRole(config('syndicate.roles.council')))
            ->get($certificate->printUrl())->assertOk();
    }

    public function test_panels_are_restricted_by_role(): void
    {
        $syndicate = $this->userWithRole(config('syndicate.roles.syndicate'));
        $council = $this->userWithRole(config('syndicate.roles.council'));
        $citizen = User::factory()->create();

        $panels = \Filament\Facades\Filament::getPanels();
        $this->assertTrue($syndicate->canAccessPanel($panels['syndicate']));
        $this->assertFalse($syndicate->canAccessPanel($panels['council']));
        $this->assertTrue($council->canAccessPanel($panels['council']));
        $this->assertFalse($council->canAccessPanel($panels['syndicate']));
        $this->assertFalse($citizen->canAccessPanel($panels['syndicate']));
        $this->assertFalse($citizen->canAccessPanel($panels['council']));

        $this->actingAs($citizen)->get('/syndicate/requests')->assertForbidden();
    }

    public function test_admin_legacy_resource_is_read_only_overview(): void
    {
        [, $certificate] = $this->activeProject();
        $this->actingAs($this->userWithRole('super_admin'));

        $this->get('/admin/syndicate-certificates')->assertOk()->assertSee($certificate->number);
        $this->get('/admin/syndicate-certificates/'.$certificate->getKey())->assertOk();
        $this->assertFalse(\App\Filament\Resources\SyndicateCertificateResource::canCreate());
        $this->assertFalse(\App\Filament\Resources\SyndicateCertificateResource::canEdit($certificate));
    }

    public function test_panel_pages_render_for_staff(): void
    {
        [$project, $certificate] = $this->activeProject();
        $request = $this->submit($project, SyndicateRequest::TYPE_CANCELLATION);

        $this->actingAs($this->userWithRole(config('syndicate.roles.council')));
        $this->get('/council')->assertOk();
        $this->get('/council/projects')->assertOk()->assertSee($project->code);
        $this->get('/council/projects/'.$project->getKey())->assertOk()->assertSee('نشط - تحت الإشراف')->assertSee('الخط الزمني للمشروع');
        $this->get('/council/requests/create')->assertOk();
        $this->get('/council/certificates/'.$certificate->getKey())->assertOk()->assertSee($certificate->number);

        $this->actingAs($this->userWithRole(config('syndicate.roles.syndicate')));
        $this->get('/syndicate')->assertOk();
        $this->get('/syndicate/requests')->assertOk()->assertSee($request->transaction_number);
        $this->get('/syndicate/requests/'.$request->getKey())->assertOk()->assertSee('إصدار شهادة إلغاء إشراف');
        $this->get('/syndicate/engineers')->assertOk();
        $this->get('/syndicate/requests/create')->assertForbidden();
    }
}
