<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Smoke tests for the public site redesign (KFS design system + PWA + a11y + voice).
 * Works on a seeded or empty kfs_testing database.
 */
class PublicPagesTest extends TestCase
{
    use DatabaseTransactions;

    /** Main public GET routes (name => parameters). */
    public static function publicRoutes(): array
    {
        return [
            'home' => ['home'],
            'about' => ['about'],
            'about.governor' => ['about.governor'],
            'posts.index' => ['posts.index'],
            'services.index' => ['services.index'],
            'projects.index' => ['projects.index'],
            'investments.index' => ['investments.index'],
            'landmarks.index' => ['landmarks.index'],
            'contact.index' => ['contact.index'],
            'directory.index' => ['directory.index'],
            'requests.index' => ['requests.index'],
            'suggestions.create' => ['suggestions.create'],
            'surveys.service.create' => ['surveys.service.create'],
            'governorate.map' => ['governorate.map'],
            'search' => ['search'],
            'login' => ['login'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_public_page_renders_with_design_system_shell(string $name): void
    {
        if (! Route::has($name)) {
            $this->markTestSkipped("Route [$name] is not registered.");
        }

        $response = $this->get(route($name));

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('manifest.webmanifest', false);
        $response->assertSee('class="kfs-skip-link" href="#main-content"', false);
        $response->assertSee('id="main-content"', false);
        $response->assertSee('bootstrap.rtl.min.css', false);
        $response->assertSee('css/kfs-ds.css', false);
        $response->assertSee('js/kfs-a11y.js', false);
        $response->assertSee('js/kfs-voice.js', false);
        $response->assertSee('id="kfsVoiceRoutes"', false);
        $response->assertSee('data-sw=', false);
        $response->assertDontSee('name="kfs-auth"', false);
    }

    public function test_guest_public_page_is_marked_cacheable_for_the_service_worker(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="kfs-cache" content="public">', false);
    }

    public function test_login_page_is_never_marked_cacheable(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('name="kfs-cache"', false);
    }

    public function test_authenticated_pages_flag_auth_and_are_not_cacheable(): void
    {
        try {
            $user = User::factory()->create();
        } catch (\Throwable $e) {
            $this->markTestSkipped('User factory unavailable: ' . $e->getMessage());
        }

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="kfs-auth" content="1">', false)
            ->assertDontSee('name="kfs-cache"', false);
    }

    public function test_voice_route_map_uses_real_routes(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<script type="application/json" id="kfsVoiceRoutes">(.+?)</script>#s', $html);
        preg_match('#<script type="application/json" id="kfsVoiceRoutes">(.+?)</script>#s', $html, $m);
        $map = json_decode($m[1], true);

        $this->assertIsArray($map);
        $this->assertSame(route('home'), $map['home'] ?? null);
        $this->assertSame(route('services.index'), $map['services'] ?? null);
        $this->assertSame(route('search'), $map['search'] ?? null);
    }

    public function test_pwa_static_files_exist_and_are_valid(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertIsArray($manifest);
        $this->assertSame('بوابة محافظة كفر الشيخ', $manifest['name']);
        $this->assertSame('كفر الشيخ', $manifest['short_name']);
        $this->assertSame('rtl', $manifest['dir']);
        $this->assertSame('ar', $manifest['lang']);
        $this->assertSame('#26302D', $manifest['theme_color']);
        $this->assertSame('#FFFDE1', $manifest['background_color']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertStringContainsString('source=pwa', $manifest['start_url']);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path($icon['src']));
        }
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));

        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('CACHE_VERSION', $sw);
        $this->assertStringContainsString('offline.html', $sw);
        foreach (['admin', 'gis', 'estidama_panal', 'livewire', 'payments', 'logout', 'api'] as $path) {
            $this->assertStringContainsString("'$path'", $sw);
        }
        $this->assertFileExists(public_path('offline.html'));
    }
}
