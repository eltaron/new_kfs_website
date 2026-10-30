<?php

namespace Tests\Feature\Payments;

use App\Events\PaymentCompleted;
use Modules\Citizen\Models\ServiceSubmission;
use Modules\Payments\Models\Transaction;
use Illuminate\Support\Facades\Event;

class PaymentCallbackTest extends PaymentTestCase
{
    public function test_gis_callback_marks_the_gis_submission_paid_and_not_an_unrelated_service_submission(): void
    {
        Event::fake([PaymentCompleted::class]);
        $owner = $this->makeUser();
        $gis = $this->makeGisSubmission($owner);
        // A generic submission that the old (int) uuid lookup could have hit.
        $other = $this->makeServiceSubmission($this->makeUser());

        $tx = $this->initiate($gis);
        $this->assertSame('gis_submission', $tx->transactionable_type);
        $this->assertSame(Transaction::STATUS_PENDING, $tx->status);

        $this->post('/efinance/callback', $this->callbackBody($tx))
            ->assertOk()->assertHeader('Response_Code', '000');

        $this->assertSame('paid', $gis->fresh()->payment_status);
        $this->assertNotNull($gis->fresh()->paid_at);
        $this->assertSame(Transaction::STATUS_COMPLETED, $tx->fresh()->status);
        $this->assertNotNull($tx->fresh()->authorization_code);
        $this->assertFalse($other->fresh()->isPaid());
        Event::assertDispatchedTimes(PaymentCompleted::class, 1);
    }

    public function test_legacy_gis_callback_url_uses_the_same_handler(): void
    {
        $gis = $this->makeGisSubmission($this->makeUser());
        $tx = $this->initiate($gis);

        $this->post('/efinance/gis/callback', $this->callbackBody($tx))->assertOk();

        $this->assertTrue($gis->fresh()->isPaid());
    }

    public function test_callback_is_idempotent(): void
    {
        Event::fake([PaymentCompleted::class]);
        $submission = $this->makeServiceSubmission($this->makeUser());
        $tx = $this->initiate($submission);
        $body = $this->callbackBody($tx, ['AuthorizationCode' => 'AUTH1111']);

        $this->post('/efinance/callback', $body)->assertOk();
        $paidAt = $submission->fresh()->paid_at;
        $this->post('/efinance/callback', $body)->assertOk();

        $submission->refresh();
        $this->assertSame('paid', $submission->payment_status);
        $this->assertSame('paid', $submission->status);
        $this->assertEquals($paidAt, $submission->paid_at);
        $this->assertSame(1, Transaction::where('transactionable_id', (string) $submission->id)
            ->where('transactionable_type', 'service_submission')->count());
        Event::assertDispatchedTimes(PaymentCompleted::class, 1);
    }

    public function test_late_failure_does_not_overwrite_a_completed_payment(): void
    {
        $submission = $this->makeServiceSubmission($this->makeUser());
        $tx = $this->initiate($submission);

        $this->post('/efinance/callback', $this->callbackBody($tx))->assertOk();
        $this->post('/efinance/callback', $this->callbackBody($tx, ['AuthorizationCode' => '', 'ResponseCode' => '051']))->assertOk();

        $this->assertSame(Transaction::STATUS_COMPLETED, $tx->fresh()->status);
        $this->assertTrue($submission->fresh()->isPaid());
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        Event::fake([PaymentCompleted::class]);
        $submission = $this->makeServiceSubmission($this->makeUser(), '100.00');
        $tx = $this->initiate($submission);

        $this->post('/efinance/callback', $this->callbackBody($tx, ['PaymentRequestTotalAmount' => '1.00']))->assertOk();

        $tx->refresh();
        $this->assertSame(Transaction::STATUS_FAILED, $tx->status);
        $this->assertSame('amount_mismatch', $tx->failure_reason);
        $this->assertFalse($submission->fresh()->isPaid());
        Event::assertNotDispatched(PaymentCompleted::class);
    }

    public function test_gateway_total_including_collection_fees_is_accepted(): void
    {
        $submission = $this->makeServiceSubmission($this->makeUser(), '100.00');
        $tx = $this->initiate($submission);

        $this->post('/efinance/callback', $this->callbackBody($tx, [
            'PaymentRequestTotalAmount' => '105.50',
            'CollectionFeesAmount' => '5.50',
        ]))->assertOk();

        $this->assertTrue($submission->fresh()->isPaid());
        $this->assertSame('5.50', (string) $tx->fresh()->collection_fees);
    }

    public function test_sender_id_mismatch_is_ignored(): void
    {
        $submission = $this->makeServiceSubmission($this->makeUser());
        $tx = $this->initiate($submission);

        $this->post('/efinance/callback', $this->callbackBody($tx, [], 'SOMEONE-ELSE'))->assertOk();

        $this->assertSame(Transaction::STATUS_PENDING, $tx->fresh()->status);
        $this->assertFalse($submission->fresh()->isPaid());
    }

    public function test_declined_response_code_marks_the_attempt_failed(): void
    {
        $submission = $this->makeServiceSubmission($this->makeUser());
        $tx = $this->initiate($submission);

        $this->post('/efinance/callback', $this->callbackBody($tx, ['ResponseCode' => '051']))->assertOk();

        $this->assertSame(Transaction::STATUS_FAILED, $tx->fresh()->status);
        $this->assertFalse($submission->fresh()->isPaid());
        $this->assertSame('failed', $submission->fresh()->payment_status);
    }

    public function test_unknown_sender_request_number_changes_nothing(): void
    {
        $this->post('/efinance/callback', [
            'SenderID' => 'TEST-SENDER',
            'SenderRequestNumber' => 'deadbeefdeadbeef',
            'RequestObject' => base64_encode('{}'),
        ])->assertOk();

        $this->assertSame(0, Transaction::where('sender_request_number', 'deadbeefdeadbeef')->count());
    }

    public function test_initiate_reuses_the_open_pending_transaction(): void
    {
        $submission = $this->makeServiceSubmission($this->makeUser());

        $first = $this->initiate($submission);
        $second = $this->initiate(ServiceSubmission::find($submission->id));

        $this->assertSame($first->id, $second->id);
    }

    public function test_initiate_supersedes_a_pending_transaction_when_the_amount_changed(): void
    {
        $submission = $this->makeServiceSubmission($this->makeUser(), '100.00');
        $first = $this->initiate($submission);

        $submission->forceFill(['total_amount' => '150.00'])->save();
        $second = $this->initiate($submission->fresh());

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(Transaction::STATUS_EXPIRED, $first->fresh()->status);
        $this->assertSame('150.00', (string) $second->amount);
    }

    public function test_expire_pending_command(): void
    {
        $submission = $this->makeServiceSubmission($this->makeUser());
        $tx = $this->initiate($submission);
        $tx->forceFill(['expires_at' => now()->subHour()])->save();

        $this->artisan('payments:expire-pending')->assertSuccessful();

        $this->assertSame(Transaction::STATUS_EXPIRED, $tx->fresh()->status);
    }
}
