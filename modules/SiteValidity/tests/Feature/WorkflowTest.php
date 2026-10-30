<?php

namespace Modules\SiteValidity\tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Notifications\SiteValidityNotification;
use Modules\SiteValidity\Services\WorkflowException;
use Modules\SiteValidity\Support\SvSettings;
use Modules\SiteValidity\Support\SvStatus;

/** آلة الحالات: المسار الكامل لكل دور، الانتقالات الممنوعة، حلقة الاستيفاء، الملف النهائي. */
class WorkflowTest extends SiteValidityTestCase
{
    public function test_full_happy_path_through_every_role(): void
    {
        $citizen = $this->citizen();
        $other = $this->citizen();
        $geo = $this->geo();
        $eng = $this->engineer();
        $r = $this->submitted($citizen);

        // 1) المركز الجيومكاني يحدد الموعد → يظهر لمهندس التنظيم
        $this->assertSame(['set_appointment'], $this->wf()->availableActions($r, $geo));
        $this->assertSame([], $this->wf()->availableActions($r, $eng));
        $this->wf()->setAppointment($r, $geo, now()->addDays(3)->setTime(11, 0), 'أحضر أصل العقد');
        $this->assertSame(SvStatus::APPOINTMENT_SET, $r->status);
        Notification::assertSentTo($eng, SiteValidityNotification::class);

        // 2) المهندس يوافق
        $this->assertContains('approve', $this->wf()->availableActions($r, $eng));
        $this->wf()->approve($r, $eng);
        $this->assertSame(SvStatus::APPROVED, $r->status);

        // 3) شاشة المعاينة: لا يمكن قبل يوم الموعد
        try {
            $this->wf()->markGeoInspection($r, $geo);
            $this->fail('Inspection before appointment day must be refused.');
        } catch (WorkflowException $e) {
            $this->assertStringContainsString('قبل يوم الموعد', $e->getMessage());
        }

        $this->travel(3)->days();
        $this->wf()->markGeoInspection($r, $geo, 'تمت المعاينة');
        $this->assertSame(SvStatus::APPROVED, $r->status, 'one mark is not enough');
        $this->wf()->submitEngineerInspection($r, $eng, $this->validAnswers(), 'مطابق');
        $this->assertSame(SvStatus::INSPECTED, $r->status, 'both marks → inspected');
        $this->assertNotEmpty($r->inspection_data);
        $this->assertCount(\Modules\SiteValidity\Models\InspectionField::query()->active()->count(), $r->inspection_data);
        $this->assertSame('yes', collect($r->inspection_data)->firstWhere('type', 'yes_no')['value']);

        // 4) الجيومكاني يرفع المرفقات (الرفع المساحي + الكاد)
        $this->wf()->uploadAttachments($r, $geo, $this->validAttachments(), 'مرفق');
        $this->assertSame(SvStatus::ATTACHMENTS_UPLOADED, $r->status);

        // 5) المهندس يطلب استيفاء → المواطن يرد عبر البوابة → يعود للمهندس
        $this->wf()->requestCompletion($r, $eng, 'برجاء إرفاق عقد مسجل', ['عقد مسجل']);
        $this->assertSame(SvStatus::COMPLETION_REQUESTED, $r->status);
        Notification::assertSentTo($citizen, SiteValidityNotification::class, fn ($n) => $n->title === 'مطلوب استيفاء');

        $this->actingAs($citizen)->get(route('site-validity.requests.show', $r))->assertOk()->assertSee('برجاء إرفاق عقد مسجل');
        $this->actingAs($citizen)->post(route('site-validity.requests.completion', $r), [
            'reply' => 'تم إرفاق العقد المسجل',
            'files' => [UploadedFile::fake()->create('registered.pdf', 30, 'application/pdf')],
        ])->assertRedirect(route('site-validity.requests.show', $r));
        $r->refresh();
        $this->assertSame(SvStatus::COMPLETION_SUBMITTED, $r->status);
        $this->assertSame(1, $r->files()->where('kind', RequestFile::KIND_COMPLETION)->count());

        // 6) المهندس: رفع الملف النهائي ← بانتظار اعتماد مدير التنظيم (لا يظهر للمواطن بعد)
        $director = $this->director();
        $this->wf()->complete($r, $eng, UploadedFile::fake()->create('final.pdf', 60, 'application/pdf'), 'معتمد');
        $this->assertSame(SvStatus::FINAL_UPLOADED, $r->status);
        $this->assertNull($r->completed_at);
        $final = $r->finalFile();
        $this->assertNotNull($final);
        $this->actingAs($citizen)->get(route('site-validity.files.download', $final))->assertNotFound();

        // 7) مدير التنظيم يعتمد ← منتهي
        $this->wf()->directorApprove($r, $director);
        $this->assertSame(SvStatus::COMPLETED, $r->status);
        $this->assertNotNull($r->pickup_code);

        // الملف النهائي: المواطن والمركز الجيومكاني فقط (ومن قام بالرفع) — لا مواطن آخر
        $this->actingAs($citizen)->get(route('site-validity.files.download', $final))->assertOk();
        $this->actingAs($geo)->get(route('site-validity.files.download', $final))->assertOk();
        $this->actingAs($other)->get(route('site-validity.files.download', $final))->assertNotFound();
        $this->actingAs($citizen)->get(route('site-validity.requests.show', $r))->assertOk()->assertSee('تنزيل الملف النهائي');

        // الخط الزمني الكامل
        $this->assertSame(
            ['submitted', 'set_appointment', 'approve', 'geo_inspection', 'engineer_inspection', 'upload_attachments', 'request_completion', 'submit_completion', 'upload_final', 'complete'],
            $r->events()->pluck('action')->all()
        );
        $last = $r->events()->get()->last();
        $this->assertSame(SvStatus::FINAL_UPLOADED, $last->from_status);
        $this->assertSame(SvStatus::COMPLETED, $last->to_status);
        $this->assertSame($director->id, $last->user_id);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $citizen = $this->citizen();
        $geo = $this->geo();
        $eng = $this->engineer();
        $r = $this->submitted($citizen);

        $refused = function (callable $fn) {
            try {
                $fn();
            } catch (WorkflowException) {
                return true;
            }

            return false;
        };

        $this->assertTrue($refused(fn () => $this->wf()->approve($r, $eng)), 'engineer cannot approve before appointment');
        $this->assertTrue($refused(fn () => $this->wf()->setAppointment($r, $eng, now()->addDay())), 'engineer cannot set appointment');
        $this->assertTrue($refused(fn () => $this->wf()->setAppointment($r, $citizen, now()->addDay())), 'citizen cannot set appointment');
        $this->assertTrue($refused(fn () => $this->wf()->setAppointment($r, $geo, now()->subDay())), 'appointment must be in the future');
        $this->assertTrue($refused(fn () => $this->wf()->complete($r, $eng, UploadedFile::fake()->create('f.pdf', 5))), 'engineer cannot complete before attachments');

        $this->wf()->setAppointment($r, $geo, now()->addDay());
        $this->assertTrue($refused(fn () => $this->wf()->approve($r, $geo)), 'geo cannot approve');
        $this->wf()->approve($r, $eng);
        $this->travel(1)->days();
        $this->assertTrue($refused(fn () => $this->wf()->uploadAttachments($r, $geo, $this->validAttachments())), 'no attachments before inspection');
        $this->assertTrue($refused(fn () => $this->wf()->submitEngineerInspection($r, $eng, ['has_boundaries' => 'maybe'])), 'invalid inspection value');
        $this->assertTrue($refused(fn () => $this->wf()->submitEngineerInspection($r, $geo, $this->validAnswers())), 'geo cannot fill engineer form');
        $this->assertTrue($refused(fn () => $this->wf()->markGeoInspection($r, $eng)), 'engineer cannot mark geo inspection');

        $this->wf()->markGeoInspection($r, $geo);
        $this->assertTrue($refused(fn () => $this->wf()->markGeoInspection($r, $geo)), 'cannot mark twice');
        $this->assertTrue($refused(fn () => $this->wf()->complete($r, $eng, UploadedFile::fake()->create('f.pdf', 5))), 'cannot complete during inspection');
        $this->wf()->submitEngineerInspection($r, $eng, $this->validAnswers());

        $this->assertTrue($refused(fn () => $this->wf()->uploadAttachments($r, $geo, [])), 'required attachment types must be uploaded');
        $this->assertTrue($refused(fn () => $this->wf()->uploadAttachments($r, $geo, collect($this->validAttachments())->map(fn () => [UploadedFile::fake()->create('evil.php', 1)])->all())), 'unsafe CAD upload');
        $this->assertTrue($refused(fn () => $this->wf()->requestCompletion($r, $eng, 'قبل المرفقات')), 'no completion before attachments');
        $this->assertTrue($refused(fn () => $this->wf()->submitCompletion($r, $citizen, 'رد')), 'citizen cannot reply without a request');

        $this->assertSame(SvStatus::INSPECTED, $r->refresh()->status);
    }

    public function test_completion_loop_can_repeat_and_keeps_history(): void
    {
        $citizen = $this->citizen();
        $geo = $this->geo();
        $eng = $this->engineer();
        $r = $this->toAttachmentsUploaded($this->submitted($citizen), $geo, $eng);

        foreach ([1, 2] as $round) {
            $this->wf()->requestCompletion($r, $eng, "مطلوب رقم {$round}", ["بند {$round}"]);
            $this->assertSame(SvStatus::COMPLETION_REQUESTED, $r->status);
            $this->assertFalse($this->wf()->can($r, $eng, 'complete'), 'engineer waits for the citizen');
            $this->wf()->submitCompletion($r, $citizen, "رد {$round}");
            $this->assertSame(SvStatus::COMPLETION_SUBMITTED, $r->status);
        }

        $this->assertSame(2, $r->refresh()->completion_rounds);
        $this->assertSame([1, 2], $r->completions()->pluck('round')->all());
        $this->assertSame(['رد 1', 'رد 2'], $r->completions()->pluck('reply')->all());
        $this->assertSame(2, $r->events()->where('action', 'request_completion')->count());
        $this->assertSame(2, $r->events()->where('action', 'submit_completion')->count());
    }

    public function test_geo_can_request_completion_while_uploading_attachments(): void
    {
        $citizen = $this->citizen();
        $geo = $this->geo();
        $eng = $this->engineer();
        $r = $this->toInspected($this->submitted($citizen), $geo, $eng);

        $this->assertTrue($this->wf()->can($r, $geo, 'request_completion'));
        $this->assertFalse($this->wf()->can($r, $eng, 'request_completion'), 'engineer waits for the attachments');

        $this->wf()->requestCompletion($r, $geo, 'برجاء إرفاق كروكي معتمد', ['كروكي معتمد']);
        $this->assertSame(SvStatus::COMPLETION_REQUESTED, $r->status);
        $this->assertFalse($this->wf()->can($r, $geo, 'upload_attachments'), 'geo waits for the citizen');

        $this->wf()->submitCompletion($r, $citizen, 'تم إرفاق الكروكي');
        $this->assertSame(SvStatus::INSPECTED, $r->status, 'back to the geo center to finish uploading');

        $this->wf()->uploadAttachments($r, $geo, $this->validAttachments());
        $this->assertSame(SvStatus::ATTACHMENTS_UPLOADED, $r->status);
        $this->assertFalse($this->wf()->can($r, $geo, 'request_completion'), 'after uploading only the engineer requests completion');
        $this->assertTrue($this->wf()->can($r, $eng, 'request_completion'));
    }

    public function test_payment_required_setting_blocks_appointment_until_paid(): void
    {
        SvSettings::set('payment_required', '1');
        $geo = $this->geo();
        $r = $this->submitted();

        $this->expectException(WorkflowException::class);
        $this->wf()->setAppointment($r, $geo, now()->addDay());
    }

    public function test_payment_not_required_by_default(): void
    {
        $r = $this->submitted();
        $this->wf()->setAppointment($r, $this->geo(), now()->addDay());

        $this->assertSame(SvStatus::APPOINTMENT_SET, $r->status);
        $this->assertFalse($r->isPaid());
    }

    public function test_reject_requires_reason_and_notifies_citizen(): void
    {
        $citizen = $this->citizen();
        $geo = $this->geo();
        $eng = $this->engineer();
        $r = $this->submitted($citizen);
        $this->wf()->setAppointment($r, $geo, now()->addDay());

        try {
            $this->wf()->reject($r, $eng, '');
            $this->fail('empty reason');
        } catch (WorkflowException) {
        }

        $this->wf()->reject($r, $eng, 'خارج الحيز العمراني');
        $this->assertSame(SvStatus::REJECTED, $r->status);
        Notification::assertSentTo($citizen, SiteValidityNotification::class, fn ($n) => $n->title === 'تم رفض الطلب');
        $this->assertSame([], $this->wf()->availableActions($r, $eng));
    }

    public function test_admin_can_override_and_reassign_with_audit(): void
    {
        $admin = $this->admin();
        $geo = $this->geo();
        $eng = $this->engineer();
        $r = $this->submitted();

        $this->wf()->assign($r, $admin, $geo->id, $eng->id);
        $this->assertSame($geo->id, $r->geo_user_id);

        $this->wf()->override($r, $admin, SvStatus::APPROVED, 'تصحيح إداري بعد مراجعة الملف');
        $this->assertSame(SvStatus::APPROVED, $r->status);
        $event = $r->events()->where('action', 'override')->firstOrFail();
        $this->assertSame(SvStatus::SUBMITTED, $event->from_status);
        $this->assertSame('site_validity_admin', $event->actor_role);

        $this->expectException(WorkflowException::class);
        $this->wf()->override($r, $geo, SvStatus::COMPLETED, 'ليس مسؤولاً عن الخدمة');
    }

    public function test_engineer_does_not_see_requests_before_appointment(): void
    {
        $eng = $this->engineer();
        $r = $this->submitted();

        $this->assertFalse($eng->can('view', $r));
        $this->assertTrue($this->geo()->can('view', $r));

        $this->wf()->setAppointment($r, $this->geo(), now()->addDay());
        $this->assertTrue($eng->can('view', $r->refresh()));
    }
}
