<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Smoke-tests every registered resource page and custom page of the admin and gis
 * panels: no page may answer with a server error for a super_admin (and, for gis,
 * no page may crash for users of the main GIS roles).
 */
class PanelSmokeTest extends TestCase
{
    use DatabaseTransactions;

    /** Main GIS roles (Arabic names, as stored in `roles`). */
    protected const GIS_ROLES = [
        'Admin',
        'مدير المركز',
        'مدير الادارة الهندسية',
        'مهندس التنظيم',
        'مدير التنظيم',
        'فني التنظيم',
        'مدير الوحدة الفرعية',
        'العضو الميداني',
        'مدخل البيانات بالوحدة الفرعية',
        'مدير المتغيرات',
        'عضو المتغيرات',
        'مكتب المحافظ',
        'رئيس المركز والمدينة',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        config(['security.two_factor.require_for_staff' => false]);
    }

    protected function makeUser(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'national_id' => (string) random_int(10000000000000, 29999999999999),
            'status' => 'approved',
        ], $attributes));

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    public function test_admin_panel_pages_render_for_super_admin(): void
    {
        $this->assertPanelRenders('admin', $this->makeUser('super_admin'));
    }

    public function test_gis_panel_pages_render_for_super_admin(): void
    {
        $this->assertPanelRenders('gis', $this->makeUser('super_admin'));
    }

    public function test_gis_panel_pages_do_not_crash_for_gis_roles(): void
    {
        $failures = [];
        foreach (self::GIS_ROLES as $role) {
            $user = $this->makeUser($role);
            $failures = array_merge($failures, $this->collectFailures('gis', $user, allowForbidden: true, prefix: "[{$role}] "));
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_inactive_users_cannot_access_panels(): void
    {
        $user = $this->makeUser('super_admin', ['status' => 'rejected']);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('gis')));

        $user->status = 'approved';
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_key_tables_render_with_livewire(): void
    {
        $user = $this->makeUser('super_admin');
        $this->actingAs($user);

        $components = [
            'admin' => [
                \App\Filament\Resources\UserResource\Pages\ListUsers::class,
                \App\Filament\Resources\ComplaintResource\Pages\ListComplaints::class,
                \App\Filament\Resources\TourBookingResource\Pages\ListTourBookings::class,
                \App\Filament\Pages\Dashboard::class,
            ],
            'gis' => [
                \App\Filament\Gis\Resources\RemovalOrderResource\Pages\ListRemovalOrders::class,
                \App\Filament\Gis\Resources\RemovalOrdersAdminResource\Pages\ListRemovalOrdersAdmin::class,
                \App\Filament\Gis\Resources\GisSubmissionResource\Pages\ListGisSubmissions::class,
                \App\Filament\Gis\Pages\Dashboard::class,
            ],
        ];

        foreach ($components as $panelId => $classes) {
            Filament::setCurrentPanel(Filament::getPanel($panelId));
            foreach ($classes as $class) {
                if (! class_exists($class)) {
                    continue;
                }
                Livewire::test($class)->assertSuccessful();
            }
        }
    }

    // ------------------------------------------------------------------

    protected function assertPanelRenders(string $panelId, User $user): void
    {
        $failures = $this->collectFailures($panelId, $user);
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    /**
     * @return list<string>
     */
    protected function collectFailures(string $panelId, User $user, bool $allowForbidden = false, string $prefix = ''): array
    {
        $panel = Filament::getPanel($panelId);
        Filament::setCurrentPanel($panel);
        $this->actingAs($user);

        $failures = [];
        foreach ($this->panelUrls($panel) as $label => $url) {
            $failure = $this->hit($url, $allowForbidden);
            if ($failure !== null) {
                $failures[] = $prefix . "{$label} ({$url}): {$failure}";
            }
        }

        return $failures;
    }

    /**
     * @return array<string, string>
     */
    protected function panelUrls(Panel $panel): array
    {
        $urls = [];
        $panelId = $panel->getId();

        foreach ($panel->getPages() as $page) {
            try {
                $urls[class_basename($page)] = $page::getUrl(panel: $panelId);
            } catch (\Throwable $e) {
                $urls[class_basename($page) . ' [url]'] = 'ERROR:' . $e->getMessage();
            }
        }

        foreach ($panel->getResources() as $resource) {
            $record = null;
            foreach (array_keys($resource::getPages()) as $name) {
                $label = class_basename($resource) . '::' . $name;
                try {
                    $needsRecord = in_array($name, ['edit', 'view'], true)
                        || str_contains($resource::getPages()[$name]->getPage(), 'Edit')
                        || str_contains($resource::getPages()[$name]->getPage(), 'View');
                    if ($needsRecord) {
                        // Prefer a record inside the resource's own scope (getEloquentQuery), otherwise
                        // edit/view of a scoped resource (e.g. EventResource, PendingRemovals) is a legitimate 404.
                        $record ??= $resource::getEloquentQuery()->first() ?? $this->recordFor($resource::getModel());
                        if ($record && ! $resource::getEloquentQuery()->whereKey($record->getKey())->exists()) {
                            $record = null;
                            continue;
                        }
                        if (! $record) {
                            continue;
                        }
                        $urls[$label] = $resource::getUrl($name, ['record' => $record], panel: $panelId);
                    } else {
                        $urls[$label] = $resource::getUrl($name, panel: $panelId);
                    }
                } catch (\Throwable $e) {
                    $urls[$label . ' [url]'] = 'ERROR:' . get_class($e) . ': ' . $e->getMessage();
                }
            }
        }

        return $urls;
    }

    protected function hit(string $url, bool $allowForbidden): ?string
    {
        if (str_starts_with($url, 'ERROR:')) {
            return substr($url, 6);
        }

        $this->resetLivewireRequestState();

        $response = $this->get($url);
        $status = $response->getStatusCode();

        if ($status >= 500) {
            $e = $response->exception;

            return "HTTP {$status}" . ($e ? ' ' . get_class($e) . ': ' . Str::limit($e->getMessage(), 400) . ' @ ' . $e->getFile() . ':' . $e->getLine() : '');
        }
        if ($status === 403 && ! $allowForbidden) {
            return 'HTTP 403 for super_admin';
        }
        if ($status === 404 && ! $allowForbidden) {
            return 'HTTP 404';
        }

        return null;
    }

    /**
     * Many requests run inside one PHP process here, unlike production where every request
     * starts fresh. When a Livewire page aborts in mount() (e.g. 403 for a role), Livewire never
     * pops its redirector stack and leaves its own Redirector bound as 'redirect', so a later
     * page that redirects in mount() crashes ("Redirector could not be converted to int").
     * Reset that per-request state so each GET behaves like a fresh request.
     */
    protected function resetLivewireRequestState(): void
    {
        $laravelRedirector = new \Illuminate\Routing\Redirector($this->app['url']);
        if ($this->app->bound('session.store')) {
            $laravelRedirector->setSession($this->app['session.store']);
        }

        \Livewire\Features\SupportRedirects\SupportRedirects::$redirectorCacheStack = [];
        $this->app->instance('redirect', $laravelRedirector);
        Livewire::flushState();
    }

    /**
     * Find or create a record so edit/view pages can be exercised.
     */
    protected function recordFor(string $modelClass): ?Model
    {
        /** @var Model $model */
        $model = new $modelClass();

        if ($existing = $modelClass::query()->first()) {
            return $existing;
        }

        if (method_exists($modelClass, 'factory')) {
            try {
                return $modelClass::factory()->create();
            } catch (\Throwable) {
                // fall back to a minimal insert
            }
        }

        return $this->minimalInsert($model);
    }

    protected function minimalInsert(Model $model): ?Model
    {
        $table = $model->getTable();
        if (! Schema::hasTable($table)) {
            return null;
        }

        $columns = DB::select(
            'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        $row = [];
        foreach ($columns as $col) {
            $name = $col->COLUMN_NAME;
            if (str_contains($col->EXTRA, 'auto_increment')) {
                continue;
            }
            if ($name === $model->getKeyName() && ! $model->getIncrementing()) {
                $row[$name] = (string) Str::uuid();
                continue;
            }
            if ($col->IS_NULLABLE === 'YES' || $col->COLUMN_DEFAULT !== null) {
                continue;
            }
            $row[$name] = match (true) {
                in_array($col->DATA_TYPE, ['int', 'bigint', 'smallint', 'tinyint', 'mediumint', 'decimal', 'double', 'float'], true) => 1,
                in_array($col->DATA_TYPE, ['date'], true) => now()->toDateString(),
                in_array($col->DATA_TYPE, ['datetime', 'timestamp'], true) => now()->toDateTimeString(),
                $col->DATA_TYPE === 'time' => '10:00:00',
                $col->DATA_TYPE === 'json' => '[]',
                $col->DATA_TYPE === 'enum' => trim(explode(',', substr($col->COLUMN_TYPE, 5, -1))[0], "'"),
                str_ends_with($name, '_id') && $col->DATA_TYPE === 'char' => (string) Str::uuid(),
                default => 'اختبار',
            };
        }
        if ($model->usesTimestamps()) {
            $row['created_at'] ??= now();
            $row['updated_at'] ??= now();
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table($table)->insert($row);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Throwable) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return null;
        }

        return $model->newQuery()->latest($model->getKeyName())->first();
    }
}
