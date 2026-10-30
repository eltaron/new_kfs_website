<?php

namespace Tests\Feature;

use Modules\Investment\Models\Advertisement;
use Modules\Content\Models\Comment;
use Modules\Citizen\Models\Complaint;
use Modules\Gis\Models\GisSubmission;
use Modules\Content\Models\Post;
use App\Models\User;
use App\Services\Security\TwoFactorAuthenticator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RateLimiter::clear('login');
        Cache::flush();
    }

    protected function makeUser(array $attributes = [], ?string $role = 'citizen'): User
    {
        $user = User::factory()->create(array_merge([
            'national_id' => (string) random_int(10000000000000, 29999999999999),
            'status' => 'approved',
        ], $attributes));

        if ($role) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    protected function enableTwoFactor(User $user): string
    {
        $secret = app(TwoFactorAuthenticator::class)->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => json_encode(['AAAAA-BBBBB', 'CCCCC-DDDDD']),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }

    protected function otp(string $secret): string
    {
        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    /* ---------------------------- login + status ---------------------------- */

    public function test_approved_citizen_without_2fa_logs_in_directly(): void
    {
        $user = $this->makeUser();

        $this->post('/login', ['national_id' => $user->national_id, 'password' => 'password'])
            ->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_and_rejected_citizens_cannot_log_in(): void
    {
        foreach (['pending', 'rejected', 'inactive'] as $status) {
            $user = $this->makeUser(['status' => $status]);

            $this->post('/login', ['national_id' => $user->national_id, 'password' => 'password'])
                ->assertSessionHasErrors('national_id');

            $this->assertGuest();
        }
    }

    public function test_pending_staff_account_is_not_locked_out(): void
    {
        $user = $this->makeUser(['status' => 'pending'], 'Admin');

        $this->post('/login', ['national_id' => $user->national_id, 'password' => 'password'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_throttled(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['national_id' => $user->national_id, 'password' => 'wrong']);
        }

        $this->post('/login', ['national_id' => $user->national_id, 'password' => 'password'])
            ->assertStatus(429);
    }

    /* ---------------------------- 2FA challenge ---------------------------- */

    public function test_user_with_2fa_must_pass_challenge_after_password(): void
    {
        $user = $this->makeUser();
        $secret = $this->enableTwoFactor($user);

        $this->post('/login', ['national_id' => $user->national_id, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('التحقق الثنائي');

        $this->post(route('two-factor.challenge.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('two-factor.challenge.store'), ['code' => $this->otp($secret)])
            ->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->get(route('citizen.dashboard'))->assertOk();
    }

    public function test_totp_code_cannot_be_replayed(): void
    {
        $user = $this->makeUser();
        $secret = $this->enableTwoFactor($user);
        $service = app(TwoFactorAuthenticator::class);
        $code = $this->otp($secret);

        $this->assertTrue($service->verifyUserCode($user, $code));
        $this->assertFalse($service->verifyUserCode($user, $code));
    }

    public function test_recovery_code_logs_in_once(): void
    {
        $user = $this->makeUser();
        $this->enableTwoFactor($user);

        $this->post('/login', ['national_id' => $user->national_id, 'password' => 'password']);
        $this->post(route('two-factor.challenge.store'), ['recovery_code' => 'aaaaa-bbbbb'])
            ->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->assertSame(['CCCCC-DDDDD'], app(TwoFactorAuthenticator::class)->recoveryCodes($user->fresh()));
    }

    public function test_challenge_without_pending_login_redirects_to_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_authenticated_session_without_verification_is_gated(): void
    {
        $user = $this->makeUser();
        $secret = $this->enableTwoFactor($user);

        $this->actingAs($user)->get(route('citizen.dashboard'))
            ->assertRedirect(route('two-factor.verify'));

        $this->post(route('two-factor.verify.store'), ['code' => $this->otp($secret)])
            ->assertRedirect();

        $this->get(route('citizen.dashboard'))->assertOk();
    }

    public function test_filament_panel_requires_2fa_verification(): void
    {
        $user = $this->makeUser([], 'super_admin');
        $this->enableTwoFactor($user);

        $this->actingAs($user)->get('/admin')->assertRedirect(route('two-factor.verify'));
    }

    public function test_staff_2fa_requirement_flag_forces_setup_in_panel(): void
    {
        config(['security.two_factor.require_for_staff' => true]);
        $user = $this->makeUser([], 'super_admin');

        $this->actingAs($user)->get('/admin')
            ->assertRedirect(route('filament.admin.pages.two-factor-authentication'));

        $this->get(route('filament.admin.pages.two-factor-authentication'))->assertOk();

        config(['security.two_factor.require_for_staff' => false]);
        $this->get('/admin')->assertOk();
    }

    /* ---------------------------- settings ---------------------------- */

    public function test_user_can_enable_confirm_and_disable_2fa(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $this->get(route('two-factor.settings'))->assertOk();

        $this->post(route('two-factor.enable'), ['password' => 'wrong'])->assertSessionHasErrors('password');

        $this->post(route('two-factor.enable'), ['password' => 'password'])
            ->assertRedirect(route('two-factor.settings'));

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->get(route('two-factor.settings'))->assertOk()->assertSee('<svg', false);

        $this->post(route('two-factor.confirm'), ['code' => '123456'])->assertSessionHasErrors('code');

        $this->post(route('two-factor.confirm'), ['code' => $this->otp($user->two_factor_secret)])
            ->assertSessionHas('two_factor_recovery_codes');

        $user->refresh();
        $this->assertTrue(TwoFactorAuthenticator::isEnabled($user));
        $this->assertCount(8, app(TwoFactorAuthenticator::class)->recoveryCodes($user));

        // Secret is encrypted at rest and hidden from serialization.
        $raw = \DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame($user->two_factor_secret, $raw);
        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());

        $codes = app(TwoFactorAuthenticator::class)->recoveryCodes($user);
        $this->delete(route('two-factor.disable'), ['password' => 'password', 'code' => $codes[0]])
            ->assertRedirect(route('two-factor.settings'));

        $this->assertFalse(TwoFactorAuthenticator::isEnabled($user->fresh()));
    }

    /* ---------------------------- hardening ---------------------------- */

    public function test_security_headers_are_sent(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy');
        $this->assertStringContainsString('microphone=(self)', $response->headers->get('Permissions-Policy'));
        $this->assertTrue($response->headers->has('Content-Security-Policy-Report-Only'));
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_center_guides_requires_auth_and_valid_files(): void
    {
        $this->post(route('center-guides.store'), ['city' => 'x'])->assertRedirect(route('login'));

        $user = $this->makeUser();
        $this->actingAs($user)
            ->postJson(route('center-guides.store'), [
                'city' => 'دسوق',
                'status' => 'approved',
                'gis_maps' => [UploadedFile::fake()->create('shell.php', 10, 'application/x-php')],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('gis_maps.0');
    }

    public function test_comment_cannot_self_approve_via_mass_assignment(): void
    {
        $comment = new Comment(['author_name' => 'a', 'author_email' => 'a@b.c', 'content' => 'x', 'is_approved' => true]);

        $this->assertNull($comment->is_approved);
        $this->assertFalse((new Post())->isFillable('id'));
    }

    public function test_gis_submission_gate_blocks_other_citizens(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $submission = new GisSubmission();
        $submission->user_id = $owner->id;

        $this->assertTrue(Gate::forUser($owner)->allows('view-gis-submission', $submission));
        $this->assertFalse(Gate::forUser($other)->allows('view-gis-submission', $submission));
    }

    public function test_advertisement_gate_requires_owner_or_staff(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $admin = $this->makeUser([], 'Admin');
        $ad = new Advertisement();
        $ad->user_id = $owner->id;

        $this->assertTrue(Gate::forUser($owner)->allows('manage-advertisement', $ad));
        $this->assertFalse(Gate::forUser($other)->allows('manage-advertisement', $ad));
        $this->assertTrue(Gate::forUser($admin)->allows('manage-advertisement', $ad));
    }

    public function test_dashboard_monitor_requires_auth_and_role(): void
    {
        $this->get(route('dashboard-monitor'))->assertRedirect(route('login'));
        $this->actingAs($this->makeUser())->get(route('dashboard-monitor'))->assertForbidden();
    }

    public function test_secure_files_are_only_served_to_authorized_users(): void
    {
        \Storage::fake('local');
        $owner = $this->makeUser();
        $other = $this->makeUser();

        $path = \App\Support\SecureUploads::store(UploadedFile::fake()->image('id.jpg'), 'complaints');
        $complaint = Complaint::create([
            'name' => 'x', 'email' => 'x@y.z', 'phone' => '01000000000',
            'national_id' => $owner->national_id, 'subject' => 's', 'message' => 'm',
            'attachment' => $path,
        ]);

        $url = \App\Support\SecureUploads::url($complaint, 'attachment');

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk();
    }
}
