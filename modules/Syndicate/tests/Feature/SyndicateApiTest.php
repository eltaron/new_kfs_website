<?php

namespace Modules\Syndicate\tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Modules\Syndicate\Models\ApiLog;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\IntegrationAccount;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Tests\TestCase;

class SyndicateApiTest extends TestCase
{
    use DatabaseTransactions, SyndicateFixtures;

    protected IntegrationAccount $account;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['syndicate.api.allowed_ips' => []]);

        $this->account = IntegrationAccount::create(['name' => 'نقابة اختبار '.uniqid(), 'is_active' => true]);
        $this->token = $this->account->createToken('test', ['syndicate'])->plainTextToken;
    }

    /** طلب API بتوكن معين (يعيد تهيئة الحراس بين الطلبات داخل نفس الاختبار). */
    protected function api(string $method, string $uri, array $data = [], ?string $token = null, array $headers = []): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $headers = array_merge(['Accept' => 'application/json'], $headers);
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer '.($token ?? $this->token);
        }

        return $this->json($method, '/api/syndicate/v1/'.ltrim($uri, '/'), $data, $headers);
    }

    public function test_requests_without_token_are_rejected_and_audited(): void
    {
        $before = ApiLog::count();

        $this->api('GET', 'requests', token: '')
            ->assertStatus(401)
            ->assertJsonPath('errors.0.code', 'unauthenticated');

        $this->assertSame($before + 1, ApiLog::count());
        $log = ApiLog::latest('id')->first();
        $this->assertSame(401, $log->status_code);
        $this->assertSame('unauthenticated', $log->error_code);
    }

    public function test_token_without_syndicate_ability_is_forbidden(): void
    {
        $weak = $this->account->createToken('weak', ['read-only'])->plainTextToken;

        $this->api('GET', 'requests', token: $weak)
            ->assertStatus(403)
            ->assertJsonPath('errors.0.code', 'missing_ability');
    }

    public function test_inactive_account_and_ip_allow_list_are_enforced(): void
    {
        config(['syndicate.api.allowed_ips' => ['10.20.30.0/24']]);
        $this->api('GET', 'requests')->assertStatus(403)->assertJsonPath('errors.0.code', 'ip_not_allowed');

        config(['syndicate.api.allowed_ips' => ['127.0.0.1']]);
        $this->api('GET', 'requests')->assertOk();

        $this->account->update(['is_active' => false]);
        $this->api('GET', 'requests')->assertStatus(403)->assertJsonPath('errors.0.code', 'forbidden');
    }

    public function test_lookup_request_by_transaction_number(): void
    {
        $project = $this->makeProject();
        $request = $this->submit($project);

        $this->api('GET', 'requests/'.strtolower($request->transaction_number))
            ->assertOk()
            ->assertJsonPath('data.type', 'requests')
            ->assertJsonPath('data.id', $request->transaction_number)
            ->assertJsonPath('data.attributes.status', 'submitted')
            ->assertJsonPath('data.included.0.attributes.code', $project->code)
            ->assertJsonPath('data.included.0.attributes.owner.national_id', $project->owner_national_id);

        $this->api('GET', 'requests/SYN-REQ-1999-999999')
            ->assertNotFound()
            ->assertJsonPath('errors.0.code', 'request_not_found');

        $log = ApiLog::latest('id')->first();
        $this->assertSame($this->account->getKey(), $log->integration_account_id);
        $this->assertSame('syndicate.api.requests.show', $log->route_name);
    }

    public function test_list_requests_filtered_by_status_and_validates_filter(): void
    {
        $open = $this->submit($this->makeProject());

        $this->api('GET', 'requests?status=submitted&type=supervision&per_page=100')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['page' => ['current', 'total']], 'links'])
            ->assertJsonFragment(['transaction_number' => $open->transaction_number]);

        $this->api('GET', 'requests?status=completed&per_page=100')
            ->assertOk()
            ->assertJsonMissing(['transaction_number' => $open->transaction_number]);

        $this->api('GET', 'requests?status=bogus')
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/status');
    }

    public function test_full_lifecycle_over_api_with_idempotency(): void
    {
        $project = $this->makeProject();
        $request = $this->submit($project);

        $body = [
            'transaction_number' => $request->transaction_number,
            'engineer' => ['syndicate_number' => 'API-'.uniqid(), 'name' => 'م. عبر الواجهة', 'national_id' => $this->nid(), 'phone' => '01011112222'],
            'office' => ['name' => 'مكتب الواجهة', 'registration_number' => 'OFF-9'],
        ];
        $key = ['Idempotency-Key' => 'issue-'.uniqid()];

        // إصدار شهادة الإشراف
        $first = $this->api('POST', 'certificates', $body, headers: $key)
            ->assertCreated()
            ->assertHeader('Location')
            ->assertJsonPath('data.attributes.kind', 'supervision')
            ->assertJsonPath('data.attributes.status', 'active')
            ->assertJsonPath('data.attributes.office.name', 'مكتب الواجهة');
        $number = $first->json('data.id');
        $this->assertMatchesRegularExpression('/^SYN-SUP-\d{4}-\d{6}$/', $number);

        // إعادة نفس الطلب بنفس المفتاح ⇒ نفس الاستجابة دون شهادة ثانية
        $this->api('POST', 'certificates', $body, headers: $key)
            ->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $number);
        $this->assertSame(1, Certificate::where('request_id', $request->getKey())->count());

        // نفس المفتاح بجسم مختلف ⇒ 422
        $this->api('POST', 'certificates', array_merge($body, ['notes' => 'مختلف']), headers: $key)
            ->assertStatus(422)->assertJsonPath('errors.0.code', 'idempotency_key_reused');

        // بدون مفتاح ⇒ قاعدة العمل تمنع الإصدار المكرر
        $this->api('POST', 'certificates', $body)->assertStatus(409)->assertJsonPath('errors.0.code', 'invalid_state');

        $this->api('GET', 'certificates/'.$number)
            ->assertOk()
            ->assertJsonPath('data.attributes.engineer.name', 'م. عبر الواجهة')
            ->assertJsonPath('data.included.0.attributes.work_status', 'active');

        // طلب إلغاء بمجلس المدينة ثم إصدار شهادة الإلغاء عبر الواجهة
        $cancel = $this->submit($project, SyndicateRequest::TYPE_CANCELLATION, SyndicateRequest::BY_ENGINEER);
        $this->api('GET', 'requests/'.$cancel->transaction_number)
            ->assertOk()->assertJsonPath('data.relationships.target_certificate.data.id', $number);

        $this->api('POST', 'cancellations/'.$cancel->transaction_number.'/certificate', ['notes' => 'بناءً على طلب المهندس'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.kind', 'cancellation')
            ->assertJsonPath('data.relationships.cancels.data.id', $number)
            ->assertJsonPath('meta.project_work_status', 'suspended');

        $this->assertSame(Project::WORK_SUSPENDED, $project->fresh()->work_status);
        $this->api('GET', 'certificates/'.$number)->assertJsonPath('data.attributes.status', 'cancelled');

        // طلب إشراف جديد ⇒ عودة للنشاط
        $again = $this->submit($project);
        $this->api('POST', 'certificates', array_merge($body, ['transaction_number' => $again->transaction_number]))->assertCreated();
        $this->assertSame(Project::WORK_ACTIVE, $project->fresh()->work_status);

        // الخط الزمني
        $timeline = $this->api('GET', 'projects/'.$project->getKey().'/timeline')
            ->assertOk()
            ->assertJsonPath('meta.project.attributes.work_status', 'active');
        $types = collect($timeline->json('data'))->pluck('attributes.type');
        $this->assertTrue($types->contains('work_suspended'));
        $this->assertTrue($types->contains('work_resumed'));
        $this->api('GET', 'projects/'.$project->code.'/timeline')->assertOk();
    }

    public function test_cancellation_endpoint_rejects_supervision_requests_and_validation_errors_are_pointed(): void
    {
        $request = $this->submit($this->makeProject());

        $this->api('POST', 'cancellations/'.$request->transaction_number.'/certificate')
            ->assertNotFound()->assertJsonPath('errors.0.code', 'request_not_found');

        $this->api('POST', 'certificates', ['transaction_number' => $request->transaction_number, 'engineer' => ['national_id' => '123']])
            ->assertStatus(422)
            ->assertJsonFragment(['pointer' => '/engineer/syndicate_number'])
            ->assertJsonFragment(['pointer' => '/engineer/national_id']);
    }

    public function test_request_status_endpoint_and_engineer_sync(): void
    {
        $request = $this->submit($this->makeProject());

        $this->api('POST', 'requests/'.$request->transaction_number.'/status', ['status' => 'under_review'])
            ->assertOk()->assertJsonPath('data.attributes.status', 'under_review');
        $this->api('POST', 'requests/'.$request->transaction_number.'/status', ['status' => 'rejected'])
            ->assertStatus(422);
        $this->api('POST', 'requests/'.$request->transaction_number.'/status', ['status' => 'rejected', 'reason' => 'نقص مستندات'])
            ->assertOk()->assertJsonPath('data.attributes.rejection_reason', 'نقص مستندات');

        $n = 'SYNC-'.uniqid();
        $this->api('POST', 'engineers/sync', ['engineers' => [['syndicate_number' => $n, 'name' => 'م. مزامن', 'membership_status' => 'active']]])
            ->assertOk()->assertJsonPath('data.attributes.synced', 1);
        $this->assertDatabaseHas('syndicate_engineers', ['syndicate_number' => $n, 'name' => 'م. مزامن']);
    }

    public function test_token_command_creates_account_with_syndicate_ability(): void
    {
        $name = 'حساب أمر '.uniqid();

        $this->artisan('syndicate:token', ['name' => $name, '--ips' => '127.0.0.1,10.0.0.0/8', '--expires' => 30])
            ->expectsOutputToContain('احفظ التوكن')
            ->assertSuccessful();

        $account = IntegrationAccount::where('name', $name)->firstOrFail();
        $this->assertSame(['127.0.0.1', '10.0.0.0/8'], $account->allowed_ips);
        $token = $account->tokens()->first();
        $this->assertSame(['syndicate'], $token->abilities);
        $this->assertNotNull($token->expires_at);

        $this->artisan('syndicate:token', ['name' => $name, '--ips' => 'not-an-ip'])->assertFailed();
    }
}
