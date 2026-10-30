<?php

namespace Modules\SiteValidity\tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\SiteValidity\Models\AttachmentType;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Models\Geo\Village;
use Modules\SiteValidity\Models\InspectionField;
use Modules\SiteValidity\Models\RequestFile;
use Modules\SiteValidity\Models\ServiceType;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Services\WorkflowService;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvSettings;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * أساس اختبارات خدمة صلاحية الموقع (kfs_testing داخل DatabaseTransactions،
 * القرص الخاص مزيف، الإشعارات مزيفة).
 */
abstract class SiteValidityTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
        SvSettings::forget();
        SvSettings::set('payment_required', '0');
        SvSettings::set('enforce_appointment_time', '1');

        foreach (SvAccess::serviceRoles() as $role) {
            Role::findOrCreate($role, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensureReferenceData();
    }

    /** تبديل المستخدم داخل نفس الاختبار (AuthenticateSession في اللوحة يربط الجلسة بكلمة مرور المستخدم السابق). */
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        return parent::actingAs($user, $guard);
    }

    protected function wf(): WorkflowService
    {
        return app(WorkflowService::class);
    }

    protected function nid(): string
    {
        static $i = 0;

        return '2' . str_pad((string) (((int) (microtime(true) * 1000) % 100000000) * 100 + (++$i % 100)), 13, '0', STR_PAD_LEFT);
    }

    protected function makeUser(?string $role = null, array $attributes = []): User
    {
        $user = User::query()->create(array_merge([
            'name' => 'مستخدم اختبار ' . Str::random(4),
            'email' => 'sv-' . Str::lower(Str::random(12)) . '@test.invalid',
            'password' => 'Password123!',
            'national_id' => $this->nid(),
            'phone' => '01012345678',
            'status' => 'approved',
        ], $attributes));

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    protected function citizen(): User
    {
        return $this->makeUser();
    }

    protected function geo(): User
    {
        return $this->makeUser(SvAccess::GEO);
    }

    protected function engineer(): User
    {
        return $this->makeUser(SvAccess::ENGINEER);
    }

    protected function director(): User
    {
        return $this->makeUser(SvAccess::DIRECTOR);
    }

    protected function admin(): User
    {
        return $this->makeUser(SvAccess::ADMIN);
    }

    protected function ensureReferenceData(): void
    {
        if (! ServiceType::query()->where('slug', 'test-permit')->exists()) {
            ServiceType::query()->create(['name' => 'خدمة اختبار', 'slug' => 'test-permit', 'fee' => 500, 'is_active' => true]);
        }
        if (InspectionField::query()->active()->doesntExist()) {
            InspectionField::query()->create(['key' => 'has_boundaries', 'label' => 'هل يوجد حدود؟', 'type' => 'yes_no', 'is_required' => false]);
            InspectionField::query()->create(['key' => 'has_projection', 'label' => 'هل يوجد بروز؟', 'type' => 'yes_no', 'is_required' => false]);
        }
        if (AttachmentType::query()->active()->where('is_required', true)->doesntExist()) {
            AttachmentType::query()->create(['key' => 'survey', 'name' => 'الرفع المساحي', 'extensions' => 'pdf,dwg', 'is_required' => true]);
            AttachmentType::query()->create(['key' => 'cad', 'name' => 'ملف الكاد', 'extensions' => 'dwg,dxf,zip', 'is_required' => true]);
        }
    }

    protected function serviceType(): ServiceType
    {
        return ServiceType::query()->where('slug', 'test-permit')->firstOrFail();
    }

    /** @return array{0: Centre, 1: Village} */
    protected function location(): array
    {
        $village = Village::query()->where('is_active', true)
            ->whereIn('center_id', Centre::query()->where('is_active', true)->select('id'))
            ->first();
        if (! $village) {
            $this->markTestSkipped('kfs_testing has no centres/villages (run CenterCityVillageSeeder).');
        }

        return [Centre::query()->findOrFail($village->center_id), $village];
    }

    protected function validPayload(User $citizen, array $overrides = []): array
    {
        [$centre, $village] = $this->location();

        return array_merge([
            'service_type_id' => $this->serviceType()->id,
            'applicant_name' => 'محمد أحمد علي السيد',
            'national_id' => $citizen->national_id ?: '29001011500111',
            'phone' => '01012345678',
            'center_id' => $centre->id,
            'village_id' => $village->id,
            'address' => 'شارع الجيش بجوار المسجد',
            'notes' => 'ملاحظة عادية',
            'sketch' => UploadedFile::fake()->image('sketch.png', 300, 200),
            'contract' => UploadedFile::fake()->create('contract.pdf', 120, 'application/pdf'),
            'id_card' => UploadedFile::fake()->image('id.jpg', 300, 200),
            'agree' => '1',
        ], $overrides);
    }

    /** يُنشئ طلباً عبر الخدمة مباشرة (بدون HTTP). */
    protected function submitted(?User $citizen = null): SiteValidityRequest
    {
        $citizen ??= $this->citizen();
        [$centre, $village] = $this->location();

        return $this->wf()->submit($citizen, [
            'service_type_id' => $this->serviceType()->id,
            'applicant_name' => 'مالك اختبار',
            'national_id' => $citizen->national_id,
            'phone' => '01012345678',
            'center_id' => $centre->id,
            'village_id' => $village->id,
            'center_name' => $centre->name,
            'village_name' => $village->name,
            'address' => 'عنوان اختبار',
            'notes' => null,
        ], [
            RequestFile::KIND_SKETCH => UploadedFile::fake()->image('s.png'),
            RequestFile::KIND_CONTRACT => UploadedFile::fake()->create('c.pdf', 50, 'application/pdf'),
            RequestFile::KIND_ID_CARD => UploadedFile::fake()->image('i.jpg'),
        ]);
    }

    /** إجابات صالحة لكل بنود المعاينة المفعلة. */
    protected function validAnswers(): array
    {
        return InspectionField::query()->active()->get()->mapWithKeys(fn (InspectionField $f) => [$f->key => match ($f->type) {
            'yes_no' => 'yes',
            'number' => '12',
            'select' => $f->optionList()[0] ?? null,
            default => 'نص اختبار',
        }])->all();
    }

    /** ملفات صالحة لكل أنواع المرفقات الإلزامية. */
    protected function validAttachments(): array
    {
        return AttachmentType::query()->active()->where('is_required', true)->get()->mapWithKeys(fn (AttachmentType $t) => [
            (string) $t->id => [UploadedFile::fake()->create($t->key . '.' . $t->allowedExtensions()[0], 40)],
        ])->all();
    }

    /** يقود الطلب حتى حالة «تمت المعاينة» (inspected). */
    protected function toInspected(SiteValidityRequest $r, User $geo, User $eng): SiteValidityRequest
    {
        $this->wf()->setAppointment($r, $geo, now()->addDays(2)->setTime(10, 0));
        $this->wf()->approve($r, $eng);
        $this->travel(2)->days();
        $this->wf()->markGeoInspection($r, $geo, 'تمت');
        $this->wf()->submitEngineerInspection($r, $eng, $this->validAnswers(), 'ملاحظات');

        return $r->refresh();
    }

    protected function toAttachmentsUploaded(SiteValidityRequest $r, User $geo, User $eng): SiteValidityRequest
    {
        $this->toInspected($r, $geo, $eng);
        $this->wf()->uploadAttachments($r, $geo, $this->validAttachments());

        return $r->refresh();
    }
}
