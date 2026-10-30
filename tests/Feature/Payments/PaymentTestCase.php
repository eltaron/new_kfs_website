<?php

namespace Tests\Feature\Payments;

use Modules\Gis\Models\GisServiceType;
use Modules\Gis\Models\GisSubmission;
use Modules\Gis\Models\GisSubService;
use Modules\Citizen\Models\Service;
use Modules\Citizen\Models\ServiceSubmission;
use Modules\Payments\Models\Transaction;
use App\Models\User;
use App\Services\Payments\Fake\FakeGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Shared fixtures for the unified payment tests. The gateway is the
 * FakeGateway (config services.efinance.driver = fake in testing): the
 * callback RequestObject is base64(json) instead of RSA/AES.
 */
abstract class PaymentTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.efinance.driver' => 'fake', 'services.efinance.sender_id' => 'TEST-SENDER']);
    }

    protected function makeUser(): User
    {
        return User::factory()->create([
            'national_id' => (string) random_int(10000000000000, 29999999999999),
            'status' => 'approved',
        ]);
    }

    protected function makeGisSubmission(User $owner, string $amount = '250.00'): GisSubmission
    {
        $type = GisServiceType::create(['name' => 'نوع اختبار']);
        $service = GisSubService::create([
            'gis_service_type_id' => $type->id,
            'name' => 'خدمة مكانية اختبارية',
            'slug' => 'test-' . Str::random(10),
            'terms_conditions' => '-',
            'requirements' => '-',
            'base_price' => $amount,
        ]);

        return GisSubmission::create([
            'user_id' => $owner->id,
            'gis_sub_service_id' => $service->id,
            'request_type' => 'new',
            'status' => 'received',
            'payment_status' => 'pending',
            'total_amount' => $amount,
            'applicant_info' => ['type' => 'owner'],
            'address_info' => [],
            'form_data' => [],
            'attachments' => [],
        ]);
    }

    protected function makeServiceSubmission(User $owner, string $amount = '100.00'): ServiceSubmission
    {
        $service = Service::create([
            'title' => 'خدمة اختبار',
            'title_line_1' => 'خدمة',
            'title_line_2' => 'اختبار',
            'description' => '-',
        ]);

        return ServiceSubmission::create([
            'service_id' => $service->id,
            'user_id' => $owner->id,
            'submitted_data' => [],
            'status' => 'awaiting_payment',
            'payment_status' => 'pending',
            'total_amount' => $amount,
        ]);
    }

    protected function initiate($payable): Transaction
    {
        return app(PaymentService::class)->initiate($payable)->transaction;
    }

    /** Silent gateway callback body for a transaction. */
    protected function callbackBody(Transaction $tx, array $payload = [], ?string $sender = null): array
    {
        $payload = array_merge([
            'SenderRequestNumber' => $tx->sender_request_number,
            'PaymentRequestNumber' => 'PRN-' . Str::random(8),
            'AuthorizationCode' => 'AUTH' . random_int(1000, 9999),
            'ResponseCode' => '000',
            'PaymentRequestTotalAmount' => (string) $tx->amount,
            'CollectionFeesAmount' => '0.00',
            'TransactionNumber' => 'TXN-' . Str::random(10),
        ], $payload);

        return [
            'SenderID' => $sender ?? 'TEST-SENDER',
            'SenderRequestNumber' => $tx->sender_request_number,
            'RequestObject' => FakeGateway::encodeCallback($payload),
        ];
    }
}
