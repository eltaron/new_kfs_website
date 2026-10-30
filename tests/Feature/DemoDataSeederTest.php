<?php

namespace Tests\Feature;

use Modules\Gis\Models\GisSubmission;
use Modules\Content\Models\Post;
use Modules\RemovalGovernance\Models\RemovalOrder;
use Modules\Payments\Models\Transaction;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * يتحقق من البيانات التجريبية (php artisan db:seed --env=testing --force) ومن أن الصفحات تعرضها.
 * يُتخطى إن لم تكن قاعدة الاختبار قد عُبئت بعد.
 */
class DemoDataSeederTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! User::where('email', DemoData::email('super.admin'))->exists()) {
            $this->markTestSkipped('Demo data not seeded in kfs_testing (run: php artisan db:seed --env=testing --force).');
        }
    }

    public function test_demo_data_covers_all_workflow_states(): void
    {
        $this->assertGreaterThanOrEqual(40, Post::count());
        $this->assertSame(50, User::where('email', 'like', 'citizen%@demo.kfs.invalid')->count());

        $this->assertEqualsCanonicalizing(
            ['received', 'processing', 'completed', 'rejected'],
            GisSubmission::distinct()->pluck('status')->all()
        );
        $this->assertCount(count(RemovalOrder::stages()), RemovalOrder::distinct()->pluck('stage'));
        $this->assertTrue(Transaction::where('status', Transaction::STATUS_COMPLETED)->exists());
        $this->assertTrue(Transaction::where('status', Transaction::STATUS_PENDING)->exists());
    }

    public function test_every_staff_demo_account_has_its_role(): void
    {
        foreach (\Database\Seeders\UserSeeder::STAFF as [$local, , $role]) {
            $user = User::where('email', DemoData::email($local))->first();
            $this->assertNotNull($user, $local);
            $this->assertTrue($user->hasRole($role), "{$local} should have role {$role}");
        }
    }

    public function test_public_pages_render_demo_content(): void
    {
        $post = Post::where('slug', 'like', 'kfs-demo-%')->where('is_published', true)->where('is_featured', true)->first();

        $this->get('/')->assertOk()->assertSee($post->title, false);
        foreach (['/posts', '/services', '/landmarks', '/investments', '/directory', '/about-us', '/hayah-karima-projects'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    /** كل صفحات القوائم في اللوحات الثلاث تُعرض بالبيانات التجريبية دون أخطاء. */
    public function test_panel_list_pages_render_with_demo_data(): void
    {
        $pages = ['admin/advertisements', 'admin/bookings', 'admin/categories', 'admin/centers', 'admin/city-guide-categories', 'admin/city-villages',
            'admin/comments', 'admin/complaints', 'admin/contact-messages', 'admin/directory-entries', 'admin/emergency-reports', 'admin/employees',
            'admin/events', 'admin/exam-results', 'admin/famous-people', 'admin/governorate-infos', 'admin/hayah-karima-projects', 'admin/hero-sliders',
            'admin/investment-messages', 'admin/investment-plans', 'admin/investment-projects', 'admin/investments', 'admin/landmarks', 'admin/locations',
            'admin/markazs', 'admin/national-projects', 'admin/official-roles', 'admin/officials', 'admin/partners', 'admin/posts', 'admin/services',
            'admin/service-submissions', 'admin/service-surveys', 'admin/shiakhas', 'admin/statistics', 'admin/suggestions', 'admin/tour-bookings',
            'admin/transactions', 'admin/users', 'admin/villages',
            'gis/gis-markazs', 'gis/gis-service-types', 'gis/gis-shiakhas', 'gis/gis-study-reasons', 'gis/gis-submissions', 'gis/gis-sub-services',
            'gis/gis-villages', 'gis/new-gis-submissions', 'gis/pending-gis-submissions', 'gis/removal-orders-admins', 'gis/users',
            'estidama_panal/training-applications', 'estidama_panal/training-centers', 'estidama_panal/training-programs', 'estidama_panal/estidama-events',
            'syndicate', 'syndicate/projects', 'syndicate/requests', 'syndicate/certificates', 'syndicate/engineers',
            'council', 'council/projects', 'council/requests', 'council/certificates',
            'store-admin', 'store-admin/vendors', 'store-admin/products', 'store-admin/orders', 'store-admin/categories',
            'store-admin/payouts', 'store-admin/reviews', 'store-admin/reports', 'store-admin/settings'];

        $admin = User::where('email', DemoData::email('super.admin'))->first();
        $this->actingAs($admin);
        $failures = [];
        foreach ($pages as $page) {
            $status = $this->get('/' . $page)->status();
            if ($status !== 200) {
                $failures[] = "{$page} → {$status}";
            }
        }

        $this->assertSame([], $failures);
    }

    public static function panelAccounts(): array
    {
        return [
            'super admin → admin' => ['super.admin', '/admin'],
            'media officer → admin' => ['media', '/admin'],
            'center manager → gis' => ['gis.center.manager', '/gis'],
            'field member → gis' => ['gis.field', '/gis'],
            'estidama admin → estidama' => ['estidama.admin', '/estidama_panal'],
            'engineers syndicate → syndicate' => ['syndicate', '/syndicate'],
            'city council → council' => ['council', '/council'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('panelAccounts')]
    public function test_panel_opens_for_role_account(string $local, string $url): void
    {
        $response = $this->actingAs(User::where('email', DemoData::email($local))->first())->get($url);

        $this->assertSame(200, $response->status(), "{$local} {$url} -> " . $response->headers->get('Location'));
    }
}
