<?php

namespace Modules\SiteValidity\tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Notifications\SiteValidityNotification;
use Modules\SiteValidity\Support\SvStatus;

/** صفحات المواطن: الهبوط، التقديم، التحقق، الحماية من IDOR. */
class CitizenPortalTest extends SiteValidityTestCase
{
    public function test_landing_page_is_public_and_shows_settings_text(): void
    {
        $this->get(route('site-validity.landing'))
            ->assertOk()
            ->assertSee('طلب صلاحية الموقع')
            ->assertSee('خدمة اختبار');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('site-validity.apply'))->assertRedirect(route('login'));
        $this->get(route('site-validity.requests.index'))->assertRedirect(route('login'));
        $this->post(route('site-validity.store'), [])->assertRedirect(route('login'));
    }

    public function test_apply_form_prefills_account_data(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->get(route('site-validity.apply'))
            ->assertOk()
            ->assertSee($citizen->national_id)
            ->assertSee('الكروكي')
            ->assertSee('صورة عقد الملكية');
    }

    public function test_citizen_submits_valid_request_and_gets_number(): void
    {
        $citizen = $this->citizen();
        $geo = $this->geo();

        $response = $this->actingAs($citizen)->post(route('site-validity.store'), $this->validPayload($citizen));

        $request = SiteValidityRequest::query()->where('user_id', $citizen->id)->latest('id')->firstOrFail();
        $response->assertRedirect(route('site-validity.requests.success', $request));

        $this->assertMatchesRegularExpression('/^SV-\d{4}-\d{6}$/', $request->number);
        $this->assertSame(SvStatus::SUBMITTED, $request->status);
        $this->assertSame('500.00', $request->payableAmount());
        $this->assertCount(3, $request->files);
        foreach ($request->files as $file) {
            Storage::disk('local')->assertExists($file->path);
            $this->assertStringStartsWith('site-validity/' . $request->id . '/', $file->path);
        }
        $this->assertSame(['submitted'], $request->events->pluck('action')->all());

        Notification::assertSentTo($geo, SiteValidityNotification::class);
        Notification::assertSentTo($citizen, SiteValidityNotification::class);

        $this->actingAs($citizen)->get(route('site-validity.requests.success', $request))->assertOk()->assertSee($request->number);
        $this->actingAs($citizen)->get(route('site-validity.requests.index'))->assertOk()->assertSee($request->number);
        $this->actingAs($citizen)->get(route('site-validity.requests.show', $request))->assertOk()->assertSee($request->number)->assertSee('سداد');
    }

    public function test_validation_rejects_invalid_data(): void
    {
        $citizen = $this->citizen();
        [$centre] = $this->location();
        $otherVillage = \Modules\SiteValidity\Models\Geo\Village::query()->where('center_id', '!=', $centre->id)->value('id');

        $this->actingAs($citizen)->post(route('site-validity.store'), $this->validPayload($citizen, [
            'national_id' => '123',
            'phone' => '999',
            'village_id' => $otherVillage,
            'agree' => null,
            'service_type_id' => 999999,
        ]))->assertSessionHasErrors(['national_id', 'phone', 'village_id', 'agree', 'service_type_id']);

        $this->assertSame(0, SiteValidityRequest::query()->where('user_id', $citizen->id)->count());
    }

    public function test_xss_payloads_are_rejected(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->post(route('site-validity.store'), $this->validPayload($citizen, [
            'applicant_name' => '<script>alert(1)</script>',
            'address' => '<img src=x onerror=alert(1)>',
            'notes' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors(['applicant_name', 'address', 'notes']);

        $this->assertSame(0, SiteValidityRequest::query()->where('user_id', $citizen->id)->count());
    }

    public function test_unsafe_uploads_are_rejected(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->post(route('site-validity.store'), $this->validPayload($citizen, [
            'sketch' => UploadedFile::fake()->create('shell.php.jpg', 10, 'image/jpeg'),
            'contract' => UploadedFile::fake()->createWithContent('page.html', '<html><script>alert(1)</script></html>'),
            'id_card' => UploadedFile::fake()->create('virus.exe', 10),
        ]))->assertSessionHasErrors(['sketch', 'contract', 'id_card']);

        $this->assertSame(0, SiteValidityRequest::query()->where('user_id', $citizen->id)->count());
    }

    public function test_citizen_cannot_view_other_citizens_requests_or_files(): void
    {
        $owner = $this->citizen();
        $intruder = $this->citizen();
        $request = $this->submitted($owner);
        $file = $request->files()->first();

        $this->actingAs($intruder)->get(route('site-validity.requests.show', $request))->assertNotFound();
        $this->actingAs($intruder)->get(route('site-validity.requests.success', $request))->assertNotFound();
        $this->actingAs($intruder)->get(route('site-validity.files.download', $file))->assertNotFound();
        $this->actingAs($intruder)->post(route('site-validity.requests.completion', $request), ['reply' => 'محاولة'])->assertNotFound();
        $this->actingAs($intruder)->get(route('site-validity.requests.index'))->assertOk()->assertDontSee($request->number);

        // المالك يستطيع التنزيل (مع ترويسات الحماية).
        $this->actingAs($owner)->get(route('site-validity.files.download', $file))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_villages_endpoint_returns_only_villages_of_centre(): void
    {
        [$centre, $village] = $this->location();

        $json = $this->getJson(route('site-validity.villages', $centre->id))->assertOk()->json();

        $this->assertNotEmpty($json);
        $this->assertContains($village->id, array_column($json, 'id'));
        $this->assertSame(
            \Modules\SiteValidity\Models\Geo\Village::query()->whereIn('id', array_column($json, 'id'))->distinct()->pluck('center_id')->all(),
            [$centre->id]
        );
    }

    public function test_internal_attachments_are_hidden_from_citizen(): void
    {
        $citizen = $this->citizen();
        $r = $this->submitted($citizen);
        $this->toAttachmentsUploaded($r, $this->geo(), $this->engineer());
        $attachment = $r->files()->where('kind', RequestFile::KIND_ATTACHMENT)->firstOrFail();

        $this->actingAs($citizen)->get(route('site-validity.files.download', $attachment))->assertNotFound();
        $this->actingAs($this->geo())->get(route('site-validity.files.download', $attachment))->assertOk();
    }
}
