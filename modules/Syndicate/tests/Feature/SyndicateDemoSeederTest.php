<?php

namespace Modules\Syndicate\tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Syndicate\database\seeders\SyndicateDemoSeeder;
use Modules\Syndicate\Models\Engineer;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyndicateDemoSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_demo_seeder_creates_roles_and_consistent_lifecycle_data_and_is_idempotent(): void
    {
        Notification::fake();
        if (Engineer::where('syndicate_number', 'KFS-10231')->exists()) {
            $this->markTestSkipped('بيانات العرض موجودة مسبقاً في قاعدة الاختبار.');
        }

        $this->seed(SyndicateDemoSeeder::class);

        $this->assertTrue(Role::where('name', 'نقابة المهندسين')->exists());
        $this->assertTrue(Role::where('name', 'مجلس المدينة')->exists());

        $byOwner = fn (string $nid) => Project::where('owner_national_id', $nid)->firstOrFail();
        $this->assertSame(Project::WORK_ACTIVE, $byOwner('27503101500215')->work_status);
        $this->assertSame(Project::WORK_SUSPENDED, $byOwner('28207221500678')->work_status);
        $this->assertSame(Project::WORK_ACTIVE, $byOwner('26912051500491')->work_status);
        $this->assertSame(Project::WORK_PENDING, $byOwner('29101011500857')->work_status);
        $this->assertTrue($byOwner('27503101500215')->requests()->where('type', 'cancellation')->open()->exists());
        $this->assertSame(SyndicateRequest::STATUS_UNDER_REVIEW, $byOwner('25806301500144')->requests()->first()->status);

        $count = Project::count();
        $this->seed(SyndicateDemoSeeder::class);
        $this->assertSame($count, Project::count(), 'إعادة التشغيل لا تكرر البيانات');
    }
}
