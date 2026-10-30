<?php

namespace Tests\Feature;

use App\Filament\Gis\Resources\UserResource as GisUserResource;
use App\Filament\Resources\UserResource as AdminUserResource;
use App\Models\User;
use App\Rules\SafeUpload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression tests for the follow-up adversarial security audit (2026-10-07):
 * user-management privilege escalation, upload name tricks, PII in logs and
 * direct HTTP access to legacy sensitive uploads.
 */
class SecurityFollowupAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();
    }

    protected function makeUser(array $roles): User
    {
        $user = User::factory()->create([
            'national_id' => (string) random_int(10000000000000, 29999999999999),
            'status' => 'approved',
        ]);

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }
        if ($roles) {
            $user->assignRole($roles);
        }

        return $user->fresh();
    }

    /* ---------- Privilege escalation through the GIS staff manager ---------- */

    public function test_gis_manager_cannot_resolve_super_admin_or_mixed_role_accounts(): void
    {
        $manager = $this->makeUser(['مدير المركز']);
        $gisStaff = $this->makeUser(['فني التنظيم']);
        $superAdmin = $this->makeUser(['super_admin']);
        $mixed = $this->makeUser(['فني التنظيم', 'Admin']);

        $this->actingAs($manager);
        $ids = GisUserResource::getEloquentQuery()->pluck('id')->map(fn ($id) => (string) $id);

        $this->assertContains((string) $gisStaff->id, $ids);
        $this->assertNotContains((string) $superAdmin->id, $ids);
        $this->assertNotContains((string) $mixed->id, $ids, 'A GIS role must not expose an Admin account to GIS managers.');

        $this->actingAs($superAdmin);
        $this->assertTrue(GisUserResource::getEloquentQuery()->whereKey($mixed->id)->exists());
    }

    public function test_gis_manager_gets_404_on_super_admin_edit_page(): void
    {
        $manager = $this->makeUser(['مدير المركز']);
        $manager->givePermissionTo(Permission::findOrCreate('update_user', 'web'), Permission::findOrCreate('view_any_user', 'web'));
        $superAdmin = $this->makeUser(['super_admin']);
        $gisStaff = $this->makeUser(['فني التنظيم']);

        $this->actingAs($manager);

        $this->get(GisUserResource::getUrl('edit', ['record' => $superAdmin], panel: 'gis'))->assertNotFound();
        $this->get(GisUserResource::getUrl('edit', ['record' => $gisStaff], panel: 'gis'))->assertOk();
    }

    public function test_admin_cannot_see_or_grant_super_admin(): void
    {
        $admin = $this->makeUser(['Admin']);
        $superAdmin = $this->makeUser(['super_admin']);
        $citizen = $this->makeUser(['citizen']);

        $this->actingAs($admin);
        $ids = AdminUserResource::getEloquentQuery()->pluck('id')->map(fn ($id) => (string) $id);
        $this->assertNotContains((string) $superAdmin->id, $ids);
        $this->assertContains((string) $citizen->id, $ids);
        $this->assertFalse(AdminUserResource::actorIsSuperAdmin());

        $this->actingAs($superAdmin);
        $this->assertTrue(AdminUserResource::getEloquentQuery()->whereKey($superAdmin->id)->exists());
    }

    public function test_admin_form_rejects_granting_super_admin_role(): void
    {
        $admin = $this->makeUser(['Admin']);
        $admin->givePermissionTo(Permission::findOrCreate('update_user', 'web'), Permission::findOrCreate('view_any_user', 'web'));
        $citizen = $this->makeUser(['citizen']);
        $superRole = Role::findOrCreate('super_admin', 'web');

        $this->actingAs($admin);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        \Livewire\Livewire::test(\App\Filament\Resources\UserResource\Pages\EditUser::class, ['record' => $citizen->getKey()])
            ->fillForm(['roles' => [$superRole->id]])
            ->call('save')
            ->assertHasFormErrors(['roles']);

        $this->assertFalse($citizen->fresh()->hasRole('super_admin'));
    }

    /* ---------- Upload names normalised by Windows / NTFS ---------- */

    public function test_safe_upload_rejects_windows_normalised_script_names(): void
    {
        foreach (['shell.php::$DATA', 'shell.php ', 'shell.phtml...', 'logo.png.php .'] as $name) {
            $file = UploadedFile::fake()->create($name, 1, 'image/png');
            $this->assertTrue(
                Validator::make(['f' => $file], ['f' => [new SafeUpload()]])->fails(),
                "'{$name}' must be rejected"
            );
        }

        $ok = UploadedFile::fake()->create('report.cmd-notes.pdf', 10, 'application/pdf');
        $this->assertFalse(Validator::make(['f' => $ok], ['f' => [new SafeUpload()]])->fails());
    }

    /* ---------- PII must not be written to log files ---------- */

    public function test_voice_complaint_does_not_log_complaint_text(): void
    {
        Log::spy();

        $this->postJson('/api/voice-complaint', [
            'name' => 'مواطن تجريبي',
            'subject' => 'شكوى',
            'message' => 'SECRET-COMPLAINT-TEXT 29901011234567',
        ])->assertOk();

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context = []) {
            $this->assertStringNotContainsString('SECRET-COMPLAINT-TEXT', $message . json_encode($context, JSON_UNESCAPED_UNICODE));

            return true;
        });
    }

    /* ---------- Direct HTTP access to legacy sensitive uploads (Apache) ---------- */

    public function test_public_storage_blocks_sensitive_directories_and_is_deployed(): void
    {
        $htaccess = (string) file_get_contents(storage_path('app/public/.htaccess'));
        foreach (['complaints', 'emergency_reports', 'national_ids', 'applications', 'marketplace/vendor-documents'] as $dir) {
            $this->assertStringContainsString($dir, $htaccess);
        }
        $this->assertStringContainsString('Require all denied', $htaccess);
        $this->assertMatchesRegularExpression('/FilesMatch "\\\\\.\(php/', $htaccess);

        // storage/app/public/.gitignore ignores everything: the rules must be whitelisted to ship.
        $this->assertStringContainsString('!.htaccess', (string) file_get_contents(storage_path('app/public/.gitignore')));
    }
}
