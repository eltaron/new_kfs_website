<?php

namespace Modules\SiteValidity\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Modules\SiteValidity\Models\CentreSchedule;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Services\WorkflowException;
use Modules\SiteValidity\Support\SvSchedule;
use Modules\SiteValidity\Support\SvStatus;

/** صفة مقدم الطلب، نوع الصلاحية، تعديل الاستمارة، مدير التنظيم، أيام المعاينة وتأكيد المواطن للموعد. */
class NewFlowTest extends SiteValidityTestCase
{
    public function test_contract_is_optional_and_agent_and_extension_files_are_conditional(): void
    {
        $citizen = $this->citizen();
        $this->actingAs($citizen);

        // بدون عقد ملكية: مقبول
        $this->post(route('site-validity.store'), $this->validPayload($citizen, [
            'contract' => null, 'applicant_capacity' => 'owner', 'construction_type' => 'new',
        ]))->assertSessionHasNoErrors();

        // وكيل بدون توكيل / تعلية بدون موقف الأدوار: مرفوض
        $this->post(route('site-validity.store'), $this->validPayload($citizen, [
            'applicant_capacity' => 'agent', 'construction_type' => 'extension',
        ]))->assertSessionHasErrors(['poa', 'floors']);

        $this->post(route('site-validity.store'), $this->validPayload($citizen, [
            'applicant_capacity' => 'agent', 'construction_type' => 'extension',
            'poa' => UploadedFile::fake()->create('poa.pdf', 40, 'application/pdf'),
            'floors' => UploadedFile::fake()->create('floors.pdf', 40, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        $r = SiteValidityRequest::query()->where('user_id', $citizen->id)->latest('id')->firstOrFail();
        $this->assertSame('agent', $r->applicant_capacity);
        $this->assertSame('extension', $r->construction_type);
        $this->assertSame(1, $r->files()->where('kind', RequestFile::KIND_POA)->count());
        $this->assertSame(1, $r->files()->where('kind', RequestFile::KIND_FLOORS)->count());
    }

    public function test_engineer_edits_inspection_with_logged_time_and_existing_height(): void
    {
        [$r, $geo, $eng] = $this->approvedExtension();
        $this->travel(1)->days();

        $this->wf()->markGeoInspection($r, $geo);
        $this->wf()->submitEngineerInspection($r, $eng, [], null, '9.5'); // كل البنود اختيارية
        $this->assertSame(SvStatus::INSPECTED, $r->status);
        $this->assertEquals(9.5, (float) $r->existing_building_height);
        $this->assertNull($r->inspection_updated_at);

        $this->wf()->editEngineerInspection($r, $eng, ['has_boundaries' => 'yes'], 'تعديل', '12');
        $this->assertNotNull($r->inspection_updated_at);
        $this->assertEquals(12.0, (float) $r->existing_building_height);
        $event = $r->events()->where('action', 'edit_inspection')->firstOrFail();
        $this->assertContains('ارتفاع المبنى القائم', $event->meta['changed']);
        $this->assertFalse($event->visibleToCitizen());

        $this->expectException(WorkflowException::class);
        $this->wf()->editEngineerInspection($r, $eng, ['has_boundaries' => 'yes'], 'تعديل', '12'); // لا تغيير
    }

    public function test_director_return_sends_back_to_engineer_with_reason(): void
    {
        $citizen = $this->citizen();
        $geo = $this->geo();
        $eng = $this->engineer();
        $director = $this->director();
        $r = $this->submitted($citizen);
        $this->toAttachmentsUploaded($r, $geo, $eng);

        $this->wf()->complete($r, $eng, UploadedFile::fake()->create('final.pdf', 20, 'application/pdf'));
        $this->assertSame(SvStatus::FINAL_UPLOADED, $r->status);
        $this->assertFalse($this->wf()->can($r, $eng, 'director_approve'));
        $this->assertFalse($this->wf()->can($r, $director, 'reject'));

        $this->wf()->directorReturn($r, $director, 'يرجى مراجعة حدود الموقع في الملف');
        $this->assertSame(SvStatus::ATTACHMENTS_UPLOADED, $r->status);
        $this->assertSame('يرجى مراجعة حدود الموقع في الملف', $r->events()->get()->last()->notes);
        $this->assertTrue($this->wf()->can($r, $eng, 'complete'));
    }

    public function test_citizen_confirms_or_picks_an_available_day_and_engineer_can_reschedule(): void
    {
        [$r, $geo, $eng, $citizen] = $this->approvedExtension();
        $this->assertSame(SiteValidityRequest::CONFIRMATION_PENDING, $r->appointment_confirmation);

        $dates = SvSchedule::availableDates($r);
        $this->assertNotEmpty($dates);
        foreach (array_keys($dates) as $date) {
            $this->assertContains(\Carbon\Carbon::parse($date)->dayOfWeek, [0, 3]);
        }

        // يوم غير متاح: مرفوض
        $this->actingAs($citizen)->post(route('site-validity.requests.appointment.reschedule', $r), ['date' => now()->addYear()->toDateString()])
            ->assertSessionHasErrors('appointment');

        $pick = array_key_first($dates);
        $this->actingAs($citizen)->post(route('site-validity.requests.appointment.reschedule', $r), ['date' => $pick])
            ->assertRedirect(route('site-validity.requests.show', $r));
        $r->refresh();
        $this->assertSame($pick . ' 09:30', $r->appointment_at->format('Y-m-d H:i'));
        $this->assertSame(SiteValidityRequest::CONFIRMATION_ACCEPTED, $r->appointment_confirmation);
        $this->assertFalse($this->wf()->can($r, $citizen, 'confirm_appointment'));

        // المهندس يرفض الموعد ويختار يوماً آخر ← بانتظار تأكيد المواطن من جديد
        $other = array_keys(SvSchedule::availableDates($r))[0];
        $this->wf()->engineerReschedule($r, $eng, $other);
        $this->assertSame(SiteValidityRequest::CONFIRMATION_PENDING, $r->appointment_confirmation);
        $this->actingAs($citizen)->post(route('site-validity.requests.appointment.confirm', $r))->assertRedirect();
        $this->assertSame(SiteValidityRequest::CONFIRMATION_ACCEPTED, $r->refresh()->appointment_confirmation);
    }

    public function test_payment_completion_is_paid_online_and_returns_the_request_automatically(): void
    {
        $citizen = $this->citizen();
        $geo = $this->geo();
        $eng = $this->engineer();
        $r = $this->submitted($citizen);
        $this->toInspected($r, $geo, $eng);

        $refused = fn (callable $fn) => rescue($fn, fn () => true, false) === true;
        $this->assertTrue($refused(fn () => $this->wf()->requestCompletion($r, $geo, 'رسوم رفع مساحي إضافي', [], 'payment', 0)), 'amount required');

        $this->wf()->requestCompletion($r, $geo, 'رسوم رفع مساحي إضافي', [], 'payment', '250');
        $c = $r->openCompletion();
        $this->assertTrue($c->isPayment());
        $this->assertSame('250.00', $c->payableAmount());
        $this->assertTrue($c->canBePaid());
        $this->assertFalse($this->wf()->can($r, $citizen, 'submit_completion'), 'payment completion closes by paying, not replying');
        $this->assertSame('site_validity_completion', $c->getMorphClass());

        $this->actingAs($citizen)->get(route('site-validity.requests.show', $r))
            ->assertOk()->assertSee('سداد رسوم')->assertSee('250.00');

        $c->markPaid((object) ['amount' => '250.00', 'sender_request_number' => 'EF-123']);
        $r->refresh();
        $this->assertSame(SvStatus::INSPECTED, $r->status); // يعود للمركز الجيومكاني لاستكمال رفع المرفقات
        $this->assertNotNull($c->fresh()->replied_at);
        $this->assertSame('EF-123', $c->fresh()->payment_reference);
        $this->assertSame('submit_completion', $r->events()->get()->last()->action);

        $c->fresh()->markPaid((object) ['amount' => '250.00', 'sender_request_number' => 'EF-123']); // idempotent
        $this->assertSame(1, $r->events()->where('action', 'submit_completion')->count());
    }

    /** @return array{0: SiteValidityRequest, 1: \App\Models\User, 2: \App\Models\User, 3: \App\Models\User} */
    private function approvedExtension(): array
    {
        $citizen = $this->citizen();
        $geo = $this->geo();
        $eng = $this->engineer();
        $r = $this->submitted($citizen);
        $r->forceFill(['construction_type' => SiteValidityRequest::CONSTRUCTION_EXTENSION])->save();
        CentreSchedule::query()->updateOrCreate(['center_id' => $r->center_id], ['weekdays' => [0, 3], 'start_time' => '09:30', 'daily_capacity' => 5]);

        $this->wf()->setAppointment($r, $geo, now()->addDay());
        $this->wf()->approve($r, $eng);

        return [$r, $geo, $eng, $citizen];
    }
}
