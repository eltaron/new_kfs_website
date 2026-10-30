<?php

namespace Modules\SiteValidity\tests\Feature;

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\SiteValidity\Filament\Resources\RequestResource\Pages\ListRequests;
use Modules\SiteValidity\Filament\Resources\RequestResource\Pages\ViewRequest;
use Modules\SiteValidity\Support\SvStatus;

/** لوحة /site-validity: من يدخل، ما يظهر لكل دور، وتنفيذ الإجراءات من الشاشة. */
class PanelAccessTest extends SiteValidityTestCase
{
    public function test_guest_is_redirected_to_panel_login_and_login_page_renders(): void
    {
        $this->get('/site-validity')->assertRedirect('/site-validity/login');
        $this->get('/site-validity/login')->assertOk();
    }

    public function test_each_service_role_can_open_the_panel_and_citizens_cannot(): void
    {
        foreach ([$this->admin(), $this->geo(), $this->engineer(), $this->makeUser('super_admin')] as $user) {
            $this->actingAs($user)->get('/site-validity')->assertOk();
            $this->actingAs($user)->get('/site-validity/requests')->assertOk();
        }

        $this->actingAs($this->citizen())->get('/site-validity')->assertForbidden();
        $this->actingAs($this->makeUser('planning_engineer', ['status' => 'inactive']))->get('/site-validity')->assertForbidden();
    }

    private const ADMIN_PAGES = ['/site-validity/service-types', '/site-validity/inspection-fields', '/site-validity/attachment-types', '/site-validity/staff', '/site-validity/settings'];

    public function test_admin_pages_render_for_service_admin(): void
    {
        $admin = $this->admin();
        foreach (self::ADMIN_PAGES as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
        $this->actingAs($admin)->get('/site-validity')->assertSee('/site-validity/settings', false);
    }

    /** يعمل في اختبار منفصل لأن Filament يبني قائمة التنقل مرة واحدة لكل تطبيق (أول طلب في الاختبار). */
    public function test_admin_only_pages_are_hidden_from_other_roles(): void
    {
        foreach ([$this->geo(), $this->engineer()] as $user) {
            $this->actingAs($user)->get('/site-validity')->assertOk()->assertDontSee('/site-validity/settings', false);
            foreach (self::ADMIN_PAGES as $page) {
                $this->actingAs($user)->get($page)->assertForbidden();
            }
        }
    }

    public function test_request_view_page_renders_for_staff_and_respects_engineer_scope(): void
    {
        $r = $this->submitted();

        $this->actingAs($this->geo())->get('/site-validity/requests/' . $r->id)->assertOk()->assertSee($r->number);
        $this->actingAs($this->admin())->get('/site-validity/requests/' . $r->id)->assertOk();
        $this->actingAs($this->admin())->get('/site-validity/requests/' . $r->id . '/edit')->assertOk();
        // المهندس لا يرى الطلب قبل تحديد الموعد
        $this->actingAs($this->engineer())->get('/site-validity/requests/' . $r->id)->assertNotFound();
        $this->actingAs($this->geo())->get('/site-validity/requests/' . $r->id . '/edit')->assertForbidden();
    }

    public function test_geo_sets_appointment_and_engineer_approves_from_the_panel(): void
    {
        $r = $this->submitted();
        $geo = $this->geo();
        $eng = $this->engineer();

        $this->actingAs($geo);
        Filament::setCurrentPanel(Filament::getPanel('site-validity'));
        Livewire::test(ListRequests::class)->assertCanSeeTableRecords([$r]);
        Livewire::test(ViewRequest::class, ['record' => $r->getRouteKey()])
            ->assertActionVisible('set_appointment')
            ->assertActionHidden('approve')
            ->callAction('set_appointment', ['appointment_at' => now()->addDays(2)->format('Y-m-d H:i:s'), 'notes' => 'موعد اختبار'])
            ->assertHasNoActionErrors();
        $this->assertSame(SvStatus::APPOINTMENT_SET, $r->refresh()->status);

        $this->actingAs($eng);
        Livewire::test(ViewRequest::class, ['record' => $r->getRouteKey()])
            ->assertActionHidden('set_appointment')
            ->assertActionVisible('approve')
            ->callAction('approve', ['notes' => null]);
        $this->assertSame(SvStatus::APPROVED, $r->refresh()->status);
    }

    public function test_engineer_fills_dynamic_inspection_form_from_the_panel(): void
    {
        $r = $this->submitted();
        $geo = $this->geo();
        $eng = $this->engineer();
        $this->wf()->setAppointment($r, $geo, now()->addDay());
        $this->wf()->approve($r, $eng);
        $this->travel(1)->days();

        $this->actingAs($eng);
        Filament::setCurrentPanel(Filament::getPanel('site-validity'));
        Livewire::test(ViewRequest::class, ['record' => $r->getRouteKey()])
            ->assertActionVisible('engineer_inspection')
            ->callAction('engineer_inspection', ['answers' => $this->validAnswers(), 'notes' => 'مطابق'])
            ->assertHasNoActionErrors();

        $r->refresh();
        $this->assertNotNull($r->engineer_inspected_at);
        $this->assertSame(SvStatus::APPROVED, $r->status);
    }
}
