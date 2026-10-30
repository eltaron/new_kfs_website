<?php

namespace Tests\Feature\Payments;

use Modules\Tourism\Models\TourBooking;
use Modules\Payments\Models\Transaction;
use App\Services\Payments\Pricing\GisPricing;

class PaymentAuthorizationTest extends PaymentTestCase
{
    public function test_guest_is_sent_to_login_before_paying(): void
    {
        $submission = $this->makeServiceSubmission($this->makeUser());

        $this->get(route('payments.pay', ['type' => 'service_submission', 'id' => $submission->id]))
            ->assertRedirect(route('login'));
    }

    public function test_only_the_owner_can_start_a_payment(): void
    {
        $owner = $this->makeUser();
        $submission = $this->makeServiceSubmission($owner);

        $this->actingAs($this->makeUser())
            ->get(route('payments.pay', ['type' => 'service_submission', 'id' => $submission->id]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('payments.pay', ['type' => 'service_submission', 'id' => $submission->id]))
            ->assertOk()
            ->assertViewIs('services.efinance_redirect');

        $this->assertSame(1, Transaction::where('transactionable_type', 'service_submission')
            ->where('transactionable_id', (string) $submission->id)->count());
    }

    public function test_unknown_payable_type_is_not_found(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/payments/pay/user/1')
            ->assertNotFound();
    }

    public function test_return_page_is_restricted_to_the_owner(): void
    {
        $owner = $this->makeUser();
        $tx = $this->initiate($this->makeGisSubmission($owner));

        $this->actingAs($this->makeUser())->get(route('payments.return', $tx))->assertForbidden();
        $this->actingAs($owner)->get(route('payments.return', $tx))->assertOk();
    }

    public function test_return_accepts_a_cross_site_post(): void
    {
        $tx = $this->initiate($this->makeGisSubmission($this->makeUser()));

        $this->post(route('payments.return.post', $tx))->assertRedirect(route('payments.return', $tx));
    }

    public function test_receipt_requires_owner_and_completed_payment(): void
    {
        $owner = $this->makeUser();
        $submission = $this->makeServiceSubmission($owner);
        $tx = $this->initiate($submission);

        // Not completed yet: no receipt.
        $this->actingAs($owner)->get(route('receipt.print', $tx))->assertNotFound();

        $this->post('/efinance/callback', $this->callbackBody($tx))->assertOk();

        $this->actingAs($this->makeUser())->get(route('receipt.print', $tx))->assertForbidden();
        $this->actingAs($owner)->get(route('receipt.print', $tx))->assertOk();
    }

    public function test_success_pages_require_ownership_and_payment(): void
    {
        $owner = $this->makeUser();
        $gis = $this->makeGisSubmission($owner);

        $this->actingAs($this->makeUser())->get(route('gis.apply.success', $gis->id))->assertNotFound();
        $this->actingAs($owner)->get(route('gis.apply.success', $gis->id))->assertRedirect(route('citizen.dashboard.gis'));

        $submission = $this->makeServiceSubmission($owner);
        $this->actingAs($this->makeUser())->get(route('services.success', $submission->id))->assertForbidden();
        $this->actingAs($owner)->get(route('services.success', $submission->id))
            ->assertRedirect(route('citizen.submissions.show', $submission->id));
    }

    public function test_guest_tour_booking_requires_a_signed_link(): void
    {
        $booking = TourBooking::create([
            'company_name' => 'شركة اختبار',
            'tour_name' => 'رحلة',
            'trip_date' => now()->addWeek()->toDateString(),
            'trip_types' => [],
            'status' => 'pending',
        ]);
        $booking->forceFill(['total_cost' => '500.00'])->save();

        $this->get(route('payments.pay', ['type' => 'tour_booking', 'id' => $booking->id]))->assertForbidden();
        $this->get($booking->paymentUrl())->assertOk()->assertViewIs('services.efinance_redirect');
    }

    public function test_tour_booking_cost_is_never_taken_from_the_client(): void
    {
        $this->post(route('tour-bookings.store'), [
            'company_name' => 'شركة اختبار التسعير',
            'tour_name' => 'رحلة',
            'trip_date' => now()->addWeek()->toDateString(),
            'total_cost' => '1',
        ])->assertRedirect();

        $booking = TourBooking::where('company_name', 'شركة اختبار التسعير')->latest('id')->first();
        $this->assertNotNull($booking);
        $this->assertNotSame('1.00', (string) $booking->total_cost);
        $this->assertFalse($booking->canBePaid());
    }

    public function test_gis_pricing_never_goes_negative(): void
    {
        $service = $this->makeGisSubmission($this->makeUser(), '100.00')->subService;
        $service->forceFill([
            'pricing_type' => 'formula',
            'pricing_settings' => ['variable' => 'area_m2', 'multiplier' => 2],
            'has_vat' => false,
            'martyr_stamp_fee' => 0,
            'sms_fee' => 0,
        ])->save();

        $quote = app(GisPricing::class)->quote($service->fresh(), ['area_m2' => -1000]);

        $this->assertSame('100.00', $quote['total']);
    }
}
