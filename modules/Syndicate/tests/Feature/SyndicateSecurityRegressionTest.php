<?php

namespace Modules\Syndicate\tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Syndicate\Models\Engineer;
use Modules\Syndicate\Models\IntegrationAccount;
use Tests\TestCase;

/**
 * Security re-audit: the integration API must not mass-assign fields that are
 * not part of the documented payload (e.g. linking an engineer to any user).
 */
class SyndicateSecurityRegressionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_engineer_sync_ignores_user_id_and_unvalidated_fields(): void
    {
        Notification::fake();
        config(['syndicate.api.allowed_ips' => []]);

        $account = IntegrationAccount::create(['name' => 'نقابة اختبار '.uniqid(), 'is_active' => true]);
        $token = $account->createToken('test', ['syndicate'])->plainTextToken;

        $victim = User::factory()->create(['national_id' => (string) random_int(10000000000000, 29999999999999)]);
        $number = 'SEC-'.random_int(100000, 999999);

        $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
            ->postJson('/api/syndicate/v1/engineers/sync', [
                'engineers' => [[
                    'syndicate_number' => $number,
                    'name' => 'م. اختبار الأمان',
                    'user_id' => $victim->getKey(),
                    'synced_at' => '2000-01-01 00:00:00',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.attributes.synced', 1);

        $engineer = Engineer::where('syndicate_number', $number)->firstOrFail();
        $this->assertNull($engineer->user_id, 'user_id must never come from the API payload.');
        $this->assertTrue($engineer->synced_at->isToday());
    }
}
