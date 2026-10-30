<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdvertisingGuideDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Citizen\Models\Service;
use Modules\Investment\Models\Advertisement;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * دليل الإعلانات (/advertising-guide): الصفحة، نقطة البيانات JSON، الإضافة/التعديل/الحذف والصلاحيات،
 * التحقق من المدخلات، تحديد معدل الطلبات، والبيانات التجريبية.
 */
class AdvertisingGuideTest extends TestCase
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

    protected function makeAd(array $attrs = []): Advertisement
    {
        return Advertisement::create(array_merge([
            'street_name' => 'شارع اختبار ' . Str::random(6),
            'lat' => 31.1107, 'lng' => 30.9388,
            'type' => 'بيلبورد', 'height' => 7.5, 'size' => '6×3 م',
            'description' => 'وصف تجريبي', 'status' => 'active', 'user_id' => null,
        ], $attrs));
    }

    protected function validPayload(array $over = []): array
    {
        return array_merge([
            'street_name' => 'شارع الجيش', 'lat' => 31.1102, 'lng' => 30.9401,
            'type' => 'شاشة LED', 'height' => 5, 'size' => '4×3 م', 'description' => 'أمام مجمع المصالح',
        ], $over);
    }

    /* ---------------- page ---------------- */

    public function test_guest_sees_redesigned_page_with_stats_and_login_prompt(): void
    {
        $this->makeAd(['street_name' => 'شارع صفحة الاختبار']);
        $active = Advertisement::where('status', 'active')->count();

        $this->get(route('advertising.guide'))
            ->assertOk()
            ->assertSee('kfs-page-hero', false)
            ->assertSee('دليل الإعلانات')
            ->assertSee('id="adgMap"', false)
            ->assertSee('id="adgFilters"', false)
            ->assertSee('data-count="' . $active . '"', false)
            ->assertSee('سجّل الدخول لإضافة موقع')
            ->assertSee(route('login'), false)
            ->assertDontSee('id="adgFormModal"', false)
            ->assertDontSee('أضف موقعاً إعلانياً');

        // No "add" trigger element is rendered for guests (the JS selector string itself is fine).
        $this->assertDoesNotMatchRegularExpression('/<[a-z]+[^>]*\sdata-adg-add[\s>]/', $this->get(route('advertising.guide'))->getContent());
    }

    public function test_signed_in_user_gets_add_form_with_same_field_names_and_csrf(): void
    {
        $this->actingAs($this->makeUser());

        $html = $this->get(route('advertising.guide'))->assertOk()->getContent();

        $this->assertStringContainsString('id="adgFormModal"', $html);
        $this->assertStringContainsString('data-adg-add', $html);
        $this->assertStringContainsString('name="_token"', $html);
        foreach (['street_name', 'type', 'height', 'size', 'description', 'lat', 'lng'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, $field);
        }
        // Type catalogue is rendered into the select.
        foreach (Advertisement::TYPES as $value => $meta) {
            $this->assertStringContainsString('<option value="' . e($value) . '">' . e($meta['label']) . '</option>', $html);
        }
        $this->assertStringNotContainsString('سجّل الدخول لإضافة موقع', $html);
    }

    public function test_page_links_to_the_advertising_licence_e_service(): void
    {
        $service = Service::find('019dd3ed-4573-7255-b27f-16fe7d4cc744') ?? Service::create([
            'id' => '019dd3ed-4573-7255-b27f-16fe7d4cc744', 'parent_id' => null,
            'title' => 'ترخيص الإعلانات', 'title_line_1' => '', 'title_line_2' => '', 'description' => 'x',
        ]);

        $this->get(route('advertising.guide'))
            ->assertOk()
            ->assertSee(route('services.show', $service->id), false)
            ->assertSee('ترخيص الإعلانات');
    }

    public function test_page_renders_empty_state_when_there_are_no_points(): void
    {
        Advertisement::query()->delete(); // rolled back by DatabaseTransactions

        $this->get(route('advertising.guide'))
            ->assertOk()
            ->assertSee('لا توجد مواقع إعلانية مسجلة بعد')
            ->assertSee('data-count="0"', false);

        $this->getJson('/advertising-guide/points')->assertOk()->assertExactJson([]);
    }

    public function test_page_keeps_client_side_escaping_for_every_user_field(): void
    {
        $html = $this->get(route('advertising.guide'))->assertOk()->getContent();

        $this->assertStringContainsString('const escHtml', $html);
        foreach (['ad.street_name', 'ad.description', 'ad.size', "ad.area || '—'", 'm.label', 'street', 's'] as $expr) {
            $this->assertStringContainsString('${escHtml(' . $expr . ')}', $html, $expr);
        }
        // No raw interpolation of user-supplied fields into markup.
        $this->assertDoesNotMatchRegularExpression('/\$\{ad\.(street_name|description|size|type|area)\}/', $html);
    }

    /* ---------------- JSON data ---------------- */

    public function test_points_endpoint_returns_only_active_points_with_area_and_manage_flags(): void
    {
        $owner = $this->makeUser();
        $mine = $this->makeAd(['user_id' => $owner->id, 'lat' => 31.1330, 'lng' => 30.6480]);   // دسوق
        $other = $this->makeAd(['lat' => 31.5530, 'lng' => 31.0890]);                          // بلطيم
        $hidden = $this->makeAd(['status' => 'inactive']);

        $guest = collect($this->getJson('/advertising-guide/points')->assertOk()
            ->assertHeader('Cache-Control')->json())->keyBy('id');

        $this->assertTrue($guest->has($mine->id));
        $this->assertFalse($guest->has($hidden->id));
        $this->assertSame('دسوق', $guest[$mine->id]['area']);
        $this->assertSame('بلطيم', $guest[$other->id]['area']);
        $this->assertFalse($guest[$mine->id]['can_manage']);
        $this->assertArrayNotHasKey('user_id', $guest[$mine->id]);
        $this->assertEqualsCanonicalizing(
            ['id', 'street_name', 'lat', 'lng', 'type', 'height', 'size', 'description', 'area', 'can_manage'],
            array_keys($guest[$mine->id])
        );

        $asOwner = collect($this->actingAs($owner)->getJson('/advertising-guide/points')->json())->keyBy('id');
        $this->assertTrue($asOwner[$mine->id]['can_manage']);
        $this->assertFalse($asOwner[$other->id]['can_manage']);

        $asAdmin = collect($this->actingAs($this->makeUser('Admin'))->getJson('/advertising-guide/points')->json())->keyBy('id');
        $this->assertTrue($asAdmin[$mine->id]['can_manage']);
        $this->assertTrue($asAdmin[$other->id]['can_manage']);
    }

    /* ---------------- store ---------------- */

    public function test_signed_in_user_can_add_a_point(): void
    {
        $user = $this->makeUser();

        $res = $this->actingAs($user)->postJson('/advertising-guide/points', $this->validPayload())->assertOk();

        $this->assertDatabaseHas('advertisements', [
            'id' => $res->json('id'), 'street_name' => 'شارع الجيش', 'type' => 'شاشة LED',
            'size' => '4×3 م', 'status' => 'active', 'user_id' => $user->id,
        ]);
        $this->assertTrue(collect($this->getJson('/advertising-guide/points')->json())->contains('id', $res->json('id')));
    }

    public function test_store_validates_required_ranges_and_types(): void
    {
        $this->actingAs($this->makeUser());

        $this->postJson('/advertising-guide/points', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['street_name', 'type', 'lat', 'lng']);

        $this->postJson('/advertising-guide/points', $this->validPayload(['lat' => 95, 'lng' => 'abc', 'height' => -1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng', 'height']);

        $this->postJson('/advertising-guide/points', $this->validPayload(['description' => str_repeat('أ', 1001)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_store_and_update_reject_markup(): void
    {
        $user = $this->makeUser();
        $ad = $this->makeAd(['user_id' => $user->id]);
        $this->actingAs($user);

        $bad = ['street_name' => '<img src=x onerror=alert(1)>', 'type' => 'لافتة<script>', 'size' => '"><svg onload=alert(1)>', 'description' => '<b>x</b>'];

        $this->postJson('/advertising-guide/points', $this->validPayload($bad))
            ->assertStatus(422)->assertJsonValidationErrors(array_keys($bad));
        $this->putJson("/advertising-guide/points/{$ad->id}", $this->validPayload($bad))
            ->assertStatus(422)->assertJsonValidationErrors(array_keys($bad));

        $this->assertDatabaseMissing('advertisements', ['street_name' => $bad['street_name']]);
        $this->assertSame($ad->street_name, $ad->fresh()->street_name);
    }

    public function test_guest_cannot_write(): void
    {
        $ad = $this->makeAd();

        $this->postJson('/advertising-guide/points', $this->validPayload())->assertUnauthorized();
        $this->putJson("/advertising-guide/points/{$ad->id}", $this->validPayload())->assertUnauthorized();
        $this->deleteJson("/advertising-guide/points/{$ad->id}")->assertUnauthorized();

        $this->assertSame('active', $ad->fresh()->status);
    }

    /* ---------------- update / destroy authorization ---------------- */

    public function test_only_owner_or_admin_can_update(): void
    {
        $owner = $this->makeUser();
        $ad = $this->makeAd(['user_id' => $owner->id]);

        $this->actingAs($this->makeUser())
            ->putJson("/advertising-guide/points/{$ad->id}", $this->validPayload(['street_name' => 'محاولة دخيل']))
            ->assertForbidden();
        $this->assertNotSame('محاولة دخيل', $ad->fresh()->street_name);

        $this->actingAs($owner)
            ->putJson("/advertising-guide/points/{$ad->id}", $this->validPayload(['street_name' => 'شارع المالك']))
            ->assertOk()->assertJsonPath('street_name', 'شارع المالك');

        $this->actingAs($this->makeUser('Admin'))
            ->putJson("/advertising-guide/points/{$ad->id}", $this->validPayload(['street_name' => 'شارع الإدارة']))
            ->assertOk();
        $this->assertSame('شارع الإدارة', $ad->fresh()->street_name);
        $this->assertSame($owner->id, $ad->fresh()->user_id); // ownership is never reassigned by an edit
    }

    public function test_only_owner_or_admin_can_delete_and_delete_hides_the_point(): void
    {
        $owner = $this->makeUser();
        $ad = $this->makeAd(['user_id' => $owner->id]);
        $ownerless = $this->makeAd(['user_id' => null]);

        $this->actingAs($this->makeUser())->deleteJson("/advertising-guide/points/{$ad->id}")->assertForbidden();
        $this->actingAs($owner)->deleteJson("/advertising-guide/points/{$ownerless->id}")->assertForbidden();
        $this->assertSame('active', $ad->fresh()->status);

        $this->actingAs($owner)->deleteJson("/advertising-guide/points/{$ad->id}")->assertOk();
        $this->assertSame('inactive', $ad->fresh()->status);
        $this->assertFalse(collect($this->getJson('/advertising-guide/points')->json())->contains('id', $ad->id));

        $this->actingAs($this->makeUser('Admin'))->deleteJson("/advertising-guide/points/{$ownerless->id}")->assertOk();
        $this->assertSame('inactive', $ownerless->fresh()->status);
    }

    public function test_writes_are_rate_limited(): void
    {
        $this->actingAs($this->makeUser());

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/advertising-guide/points', [])->assertStatus(422);
        }
        $this->postJson('/advertising-guide/points', $this->validPayload())->assertStatus(429);
    }

    /* ---------------- demo seeder ---------------- */

    public function test_demo_seeder_is_idempotent_and_uses_demo_owners(): void
    {
        $streets = array_column(AdvertisingGuideDemoSeeder::STREETS, 0);

        $this->seed(AdvertisingGuideDemoSeeder::class);
        $first = Advertisement::whereIn('street_name', $streets)->count();
        $this->seed(AdvertisingGuideDemoSeeder::class);
        $second = Advertisement::whereIn('street_name', $streets)->count();

        $this->assertSame($first, $second);
        $this->assertGreaterThanOrEqual(AdvertisingGuideDemoSeeder::expectedCount(), $first);
        $this->assertGreaterThanOrEqual(60, AdvertisingGuideDemoSeeder::expectedCount());
        $this->assertGreaterThanOrEqual(12, count($streets));

        $seeded = Advertisement::whereIn('street_name', $streets)->get();
        $this->assertGreaterThanOrEqual(6, $seeded->pluck('type')->unique()->count());
        $this->assertTrue($seeded->every(fn ($a) => isset(Advertisement::TYPES[$a->type])));
        // Every seeded point is inside the governorate's bounding box.
        $this->assertTrue($seeded->every(fn ($a) => $a->lat > 30.95 && $a->lat < 31.65 && $a->lng > 30.45 && $a->lng < 31.35));

        $ownerEmails = User::whereIn('id', $seeded->pluck('user_id')->filter()->unique())->pluck('email');
        if ($ownerEmails->isNotEmpty()) {
            $this->assertTrue($ownerEmails->every(fn ($e) => str_ends_with($e, '@demo.kfs.invalid')));
        }
    }
}
