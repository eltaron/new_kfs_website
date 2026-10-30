<?php

namespace Modules\SiteValidity\tests\Feature;

use App\Contracts\Payable;
use App\Services\Payments\Fake\FakeGateway;
use App\Services\Payments\PayableResolver;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Str;
use Modules\SiteValidity\database\seeders\SiteValidityDemoSeeder;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvStatus;

/** تكامل الدفع الموحد (Payable / e-Finance) وتكرار تشغيل الـ seeder. */
class PaymentAndSeederTest extends SiteValidityTestCase
{
    public function test_request_is_a_payable_registered_in_the_morph_map(): void
    {
        $citizen = $this->citizen();
        $r = $this->submitted($citizen);

        $this->assertInstanceOf(Payable::class, $r);
        $this->assertSame('site_validity_request', $r->getMorphClass());
        $this->assertTrue($r->is(app(PayableResolver::class)->find('site_validity_request', $r->id)));
        $this->assertSame('500.00', $r->payableAmount());
        $this->assertTrue($r->payableOwner()->is($citizen));
        $this->assertStringContainsString($r->number, $r->payableDescription());
        $this->assertTrue($r->canBePaid());
        $this->assertSame(route('payments.pay', ['type' => 'site_validity_request', 'id' => $r->id]), $r->paymentUrl());
    }

    public function test_only_the_owner_may_start_payment(): void
    {
        config(['services.efinance.driver' => 'fake', 'services.efinance.sender_id' => 'TEST-SENDER']);
        $r = $this->submitted();

        $this->actingAs($this->citizen())->get($r->paymentUrl())->assertForbidden();
    }

    public function test_gateway_callback_marks_the_request_paid(): void
    {
        config(['services.efinance.driver' => 'fake', 'services.efinance.sender_id' => 'TEST-SENDER']);
        $r = $this->submitted();

        $tx = app(PaymentService::class)->initiate($r)->transaction;
        $this->assertSame('500.00', (string) $tx->amount);

        $this->post(route('efinance.callback'), [
            'SenderID' => 'TEST-SENDER',
            'SenderRequestNumber' => $tx->sender_request_number,
            'RequestObject' => FakeGateway::encodeCallback([
                'SenderRequestNumber' => $tx->sender_request_number,
                'PaymentRequestNumber' => 'PRN-' . Str::random(8),
                'AuthorizationCode' => 'AUTH1234',
                'ResponseCode' => '000',
                'PaymentRequestTotalAmount' => (string) $tx->amount,
                'CollectionFeesAmount' => '0.00',
                'TransactionNumber' => 'TXN-' . Str::random(10),
            ]),
        ])->assertOk();

        $r->refresh();
        $this->assertTrue($r->isPaid());
        $this->assertNotNull($r->paid_at);
        $this->assertSame('500.00', (string) $r->paid_amount);
        $this->assertFalse($r->canBePaid());
        $this->assertSame(1, $r->events()->where('action', 'paid')->count());
        $this->assertSame(SvStatus::SUBMITTED, $r->status, 'payment never changes the workflow state');
    }

    public function test_mark_paid_is_idempotent(): void
    {
        $r = $this->submitted();
        $tx = (object) ['amount' => '500.00', 'sender_request_number' => 'SV-T-1'];

        $r->markPaid(new class($tx) {
            public function __construct(private object $d) {}
            public function __get($k) { return $this->d->{$k} ?? null; }
            public function getKey() { return 'tx-1'; }
        });
        $first = $r->paid_at;
        $r->markPaid(new class {
            public $amount = '999.00';
            public function getKey() { return 'tx-2'; }
        });

        $this->assertEquals($first, $r->refresh()->paid_at);
        $this->assertSame('500.00', (string) $r->paid_amount);
    }

    public function test_demo_seeder_is_idempotent_and_covers_every_state(): void
    {
        $this->seed(SiteValidityDemoSeeder::class);
        $counts = [
            SiteValidityRequest::query()->count(),
            \Modules\SiteValidity\Models\RequestFile::query()->count(),
            \Modules\SiteValidity\Models\RequestEvent::query()->count(),
            \App\Models\User::query()->where('email', 'like', 'sv.%@demo.kfs.invalid')->count(),
        ];

        $this->seed(SiteValidityDemoSeeder::class);
        $this->assertSame($counts, [
            SiteValidityRequest::query()->count(),
            \Modules\SiteValidity\Models\RequestFile::query()->count(),
            \Modules\SiteValidity\Models\RequestEvent::query()->count(),
            \App\Models\User::query()->where('email', 'like', 'sv.%@demo.kfs.invalid')->count(),
        ]);

        $demo = SiteValidityRequest::query()->where('number', 'like', 'SV-2026-9%');
        $this->assertSame(17, (clone $demo)->count());
        $this->assertEqualsCanonicalizing(SvStatus::all(), (clone $demo)->distinct()->pluck('status')->all());
        $this->assertSame(count(SiteValidityDemoSeeder::ACCOUNTS), $counts[3]);
        $this->assertTrue(\App\Models\User::query()->where('email', 'sv.director@demo.kfs.invalid')->first()->hasRole('planning_director'));
        $this->assertGreaterThan(0, \Modules\SiteValidity\Models\CentreSchedule::query()->count());
        $this->assertTrue(\App\Models\User::query()->where('email', 'sv.geo@demo.kfs.invalid')->first()->hasRole('geo_center'));
    }
}
