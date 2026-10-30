<?php

namespace Modules\Syndicate\tests\Feature;

use App\Models\User;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Services\SupervisionService;
use Modules\Syndicate\Support\Actor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** بيانات مشتركة لاختبارات وحدة النقابة (تعمل داخل DatabaseTransactions على kfs_testing). */
trait SyndicateFixtures
{
    protected function service(): SupervisionService
    {
        return app(SupervisionService::class);
    }

    protected function actor(): Actor
    {
        return new Actor('system', null, 'اختبار');
    }

    /** رقم قومي فريد لكل استدعاء لتجنب التعارض مع بيانات موجودة. */
    protected function nid(): string
    {
        static $i = 0;

        return '29'.str_pad((string) ((int) (microtime(true) * 1000) % 1000000000 + (++$i)), 12, '0', STR_PAD_LEFT);
    }

    protected function makeProject(array $overrides = []): Project
    {
        return $this->service()->registerProject(array_merge([
            'owner_name' => 'مالك اختبار',
            'owner_national_id' => $this->nid(),
            'owner_phone' => '01000000001',
            'owner_email' => 'owner'.uniqid().'@example.test',
            'address' => 'شارع الاختبار - قطعة 5',
            'center' => 'دسوق',
            'license_number' => 'TST-'.uniqid(),
            'works_type' => 'execution',
            'execution_sub_type' => 'creation',
            'works_value' => 100000,
        ], $overrides), $this->actor());
    }

    protected function submit(Project $project, string $type = SyndicateRequest::TYPE_SUPERVISION, string $by = SyndicateRequest::BY_CITIZEN): SyndicateRequest
    {
        return $this->service()->submitRequest($project->fresh(), $type, [
            'name' => $project->owner_name,
            'national_id' => $project->owner_national_id,
            'phone' => $project->owner_phone,
            'email' => $project->owner_email,
        ], $by, $this->actor(), 'counter', $type === SyndicateRequest::TYPE_CANCELLATION ? 'سبب اختبار' : null);
    }

    protected function engineerData(array $overrides = []): array
    {
        return array_merge([
            'syndicate_number' => 'T-'.uniqid(),
            'name' => 'م. مهندس اختبار',
            'national_id' => $this->nid(),
            'phone' => '01100000002',
            'email' => 'eng'.uniqid().'@example.test',
            'specialization' => 'مدني',
            'office_name' => 'مكتب اختبار',
            'office_registration_number' => 'OFF-1',
        ], $overrides);
    }

    /** مشروع نشط بشهادة إشراف سارية. @return array{0: Project, 1: Certificate} */
    protected function activeProject(array $engineer = [], array $project = []): array
    {
        $p = $this->makeProject($project);
        $certificate = $this->service()->issueSupervisionCertificate($this->submit($p), $this->engineerData($engineer), $this->actor());

        return [$p->fresh(), $certificate];
    }

    protected function userWithRole(string $role, array $attributes = []): User
    {
        Role::findOrCreate($role, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
