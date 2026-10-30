<?php

namespace Tests\Feature;

use App\Filament\Security\Pages\TwoFactorAuthentication;
use Modules\Investment\Models\Advertisement;
use App\Models\User;
use App\Rules\SafeUpload;
use App\Services\Security\TwoFactorAuthenticator;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression tests for the adversarial security re-audit (2026-10-07).
 */
class SecurityReauditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();
    }

    protected function makeUser(?string $role = 'citizen'): User
    {
        $user = User::factory()->create([
            'national_id' => (string) random_int(10000000000000, 29999999999999),
            'status' => 'approved',
        ]);

        if ($role) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    /* ---------- Stored XSS: advertising guide points (public map) ---------- */

    public function test_advertising_point_with_markup_is_rejected(): void
    {
        $this->actingAs($this->makeUser());

        $payload = [
            'street_name' => '<img src=x onerror=alert(document.cookie)>',
            'lat' => 31.1, 'lng' => 30.9,
            'type' => 'لافتة<script>alert(1)</script>',
            'size' => '3x4"><svg onload=alert(1)>',
            'description' => '<b>x</b>',
        ];

        $this->postJson('/advertising-guide/points', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['street_name', 'type', 'size', 'description']);

        $this->assertDatabaseMissing('advertisements', ['street_name' => $payload['street_name']]);

        // A clean point is still accepted.
        $this->postJson('/advertising-guide/points', [
            'street_name' => 'شارع الجيش', 'lat' => 31.1, 'lng' => 30.9, 'type' => 'لافتة',
        ])->assertOk();
    }

    public function test_advertising_guide_escapes_point_fields_client_side(): void
    {
        $html = $this->get(route('advertising.guide'))->assertOk()->getContent();

        $this->assertStringContainsString('const escHtml', $html);
        $this->assertStringContainsString('${escHtml(ad.street_name)}', $html);
        $this->assertStringContainsString('${escHtml(ad.description)}', $html);
        $this->assertStringNotContainsString('> ${ad.street_name}</p>', $html);
        $this->assertStringNotContainsString('${ad.description}</p>', $html);
    }

    /* ---------- Uploads: SafeUpload on every Filament FileUpload ---------- */

    protected function passes(UploadedFile $file): bool
    {
        return Validator::make(['f' => $file], ['f' => [new SafeUpload()]])->passes();
    }

    public function test_safe_upload_rejects_active_content(): void
    {
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent(
            'logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        )));
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent(
            'logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect/></svg>'
        )));
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent('page.html', '<html><script>x</script></html>')));
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent('shell.php.jpg', "\xFF\xD8\xFF<?php system(\$_GET['c']);")));
        $this->assertFalse($this->passes(UploadedFile::fake()->createWithContent('x.phtml', '<?php echo 1;')));
    }

    public function test_safe_upload_accepts_normal_files(): void
    {
        $this->assertTrue($this->passes(UploadedFile::fake()->image('photo.png')));
        $this->assertTrue($this->passes(UploadedFile::fake()->createWithContent(
            'logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10" fill="#B88920"/><text x="1" y="8" font-family="Tajawal">كفر الشيخ</text></svg>'
        )));
        $this->assertTrue($this->passes(UploadedFile::fake()->create('plan.pdf', 20, 'application/pdf')));
    }

    public function test_every_filament_file_upload_carries_safe_upload_rule(): void
    {
        $upload = FileUpload::make('image')->image()->acceptedFileTypes(['image/svg+xml']);

        $rules = (new \ReflectionProperty($upload, 'rules'))->getValue($upload);
        $found = collect($rules)->contains(function ($entry) {
            $rule = $entry[0] ?? null;

            return $rule instanceof \Closure && $rule() instanceof SafeUpload;
        });

        $this->assertTrue($found, 'FileUpload::configureUsing must add the SafeUpload rule.');
    }

    public function test_public_storage_refuses_html_and_sandboxes_svg(): void
    {
        $htaccess = (string) @file_get_contents(storage_path('app/public/.htaccess'));
        $this->assertStringContainsString('html?|xhtml', $htaccess);
        $this->assertStringContainsString('sandbox', $htaccess);

        $webConfig = (string) file_get_contents(public_path('web.config'));
        $this->assertStringContainsString('<add fileExtension=".html" allowed="false" />', $webConfig);
        $this->assertStringContainsString('<add fileExtension=".js" allowed="false" />', $webConfig);
    }

    /* ---------- 2FA: Filament page must not reset a confirmed secret ---------- */

    public function test_filament_enable_does_not_silently_disable_confirmed_2fa(): void
    {
        $user = $this->makeUser('super_admin');
        $secret = app(TwoFactorAuthenticator::class)->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => json_encode(['AAAAA-BBBBB']),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(TwoFactorAuthentication::class)
            ->set('password', 'password')
            ->call('enable');

        $user->refresh();
        $this->assertTrue(TwoFactorAuthenticator::isEnabled($user), '2FA must stay enabled without a code.');
        $this->assertSame($secret, $user->two_factor_secret);
    }

    /* ---------- Payments: fake gateway (public secret) never in production ---------- */

    public function test_fake_payment_gateway_is_refused_in_production(): void
    {
        config(['services.efinance.driver' => 'fake']);
        $this->assertInstanceOf(\App\Services\Payments\Fake\FakeGateway::class, app(\App\Services\Payments\Contracts\PaymentGateway::class));

        $original = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $this->assertInstanceOf(
                \App\Services\Payments\EFinance\EFinanceGateway::class,
                app(\App\Services\Payments\Contracts\PaymentGateway::class)
            );
        } finally {
            $this->app['env'] = $original;
        }
    }

    /* ---------- Demo data: no well-known password on marketplace vendors ---------- */

    public function test_marketplace_demo_seeder_has_no_fixed_password(): void
    {
        $source = (string) file_get_contents(base_path('modules/Marketplace/database/seeders/MarketplaceDemoSeeder.php'));

        $this->assertStringNotContainsString("Hash::make('password')", $source);
        $this->assertStringContainsString("env('DEMO_PASSWORD')", $source);
    }

    public function test_env_example_keeps_demo_seed_off(): void
    {
        $env = (string) file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^ALLOW_DEMO_SEED=false$/m', $env);
        $this->assertMatchesRegularExpression('/^SYNDICATE_API_ALLOWED_IPS=/m', $env);
    }
}
