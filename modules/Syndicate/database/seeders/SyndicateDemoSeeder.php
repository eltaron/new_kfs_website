<?php

// المسار modules/Syndicate/database/seeders بأحرف صغيرة؛ مساحة الاسم مطابقة له حرفياً
// ليعمل التحميل التلقائي (PSR-4) على خوادم Linux الحساسة لحالة الأحرف.
namespace Modules\Syndicate\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Syndicate\Models\Engineer;
use Modules\Syndicate\Models\IntegrationAccount;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;
use Modules\Syndicate\Services\SupervisionService;
use Modules\Syndicate\Support\Actor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * بيانات تجريبية لمنظومة الإشراف الهندسي + أدوار "نقابة المهندسين" و"مجلس المدينة".
 *
 *   php artisan db:seed --class="Modules\Syndicate\database\seeders\SyndicateDemoSeeder"
 *
 * آمن للتكرار: الأدوار findOrCreate، والبيانات التجريبية تُتخطى إن وُجدت مسبقاً.
 * يمر كل شيء عبر SupervisionService لتكون الحالات والخط الزمني متسقة.
 */
class SyndicateDemoSeeder extends Seeder
{
    public function run(SupervisionService $service): void
    {
        $this->seedRoles();

        if (Engineer::where('syndicate_number', 'KFS-10231')->exists()) {
            $this->command?->info('بيانات النقابة التجريبية موجودة مسبقاً — تم تخطيها.');

            return;
        }

        // لا نرسل بريداً / SMS حقيقياً أثناء التعبئة.
        $original = config('syndicate.notifications');
        config(['syndicate.notifications.mail' => false, 'syndicate.notifications.sms' => false, 'syndicate.notifications.webpush' => false]);

        try {
            $this->seedDemoData($service);
        } finally {
            config(['syndicate.notifications' => $original]);
        }

        IntegrationAccount::firstOrCreate(
            ['name' => 'نقابة المهندسين - فرعية كفر الشيخ'],
            ['description' => 'حساب التكامل الرسمي (أنشئ التوكن بالأمر php artisan syndicate:token)', 'is_active' => true],
        );

        $this->command?->info('تمت تعبئة بيانات نقابة المهندسين التجريبية.');
    }

    protected function seedRoles(): void
    {
        foreach ([config('syndicate.roles.syndicate', 'نقابة المهندسين'), config('syndicate.roles.council', 'مجلس المدينة')] as $role) {
            Role::findOrCreate($role, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function seedDemoData(SupervisionService $service): void
    {
        $service->syncEngineers([
            ['syndicate_number' => 'KFS-10231', 'national_id' => '27805121500311', 'name' => 'م. أحمد عبد الحميد السيد', 'phone' => '01001234501', 'email' => 'a.elsayed@example.com', 'specialization' => 'مدني', 'grade' => 'استشاري', 'registration_number' => 'ENG-55120', 'office_name' => 'المكتب الهندسي للإنشاءات', 'office_registration_number' => 'OFF-2201'],
            ['syndicate_number' => 'KFS-11874', 'national_id' => '28402031500458', 'name' => 'م. منى إبراهيم الشربيني', 'phone' => '01112345602', 'email' => 'mona.sherbiny@example.com', 'specialization' => 'معماري', 'grade' => 'أول', 'registration_number' => 'ENG-61877'],
            ['syndicate_number' => 'KFS-09652', 'national_id' => '27011251500732', 'name' => 'م. محمود فتحي غنيم', 'phone' => '01223456703', 'specialization' => 'مدني', 'grade' => 'استشاري', 'registration_number' => 'ENG-48890', 'office_name' => 'دار غنيم للاستشارات الهندسية', 'office_registration_number' => 'OFF-1975'],
            ['syndicate_number' => 'KFS-12530', 'national_id' => '29006081500914', 'name' => 'م. خالد سمير النجار', 'phone' => '01554567804', 'specialization' => 'مدني', 'grade' => 'ممارس', 'registration_number' => 'ENG-70315'],
            ['syndicate_number' => 'KFS-08817', 'name' => 'م. سامي رمضان عيد', 'specialization' => 'مدني', 'grade' => 'أول', 'membership_status' => 'suspended'],
        ]);

        $council = Actor::system();
        $syndicate = new Actor('system', null, 'موظف النقابة (بيانات تجريبية)');
        $owner = fn (Project $p) => ['name' => $p->owner_name, 'national_id' => $p->owner_national_id, 'phone' => $p->owner_phone, 'email' => $p->owner_email];
        $engineer = fn (string $number) => Engineer::where('syndicate_number', $number)->first()->only([
            'syndicate_number', 'name', 'national_id', 'phone', 'email', 'specialization', 'registration_number', 'office_name', 'office_registration_number',
        ]);

        // 1) مشروع نشط تحت الإشراف — دسوق
        $p1 = $service->registerProject([
            'owner_name' => 'عبد الرحمن محمد الدسوقي', 'owner_national_id' => '27503101500215', 'owner_phone' => '01098765401',
            'owner_address' => 'شارع الجيش - دسوق', 'address' => 'قطعة 14 حوض الجزيرة - امتداد شارع البحر', 'center' => 'دسوق',
            'village' => 'حي أول', 'plot_number' => '14', 'license_number' => 'DSQ-2026-0412', 'license_date' => '2026-03-02',
            'license_authority' => 'مجلس مدينة دسوق', 'works_type' => 'execution', 'execution_sub_type' => 'creation',
            'works_description' => 'إنشاء عمارة سكنية أرضي + 4 أدوار متكررة', 'works_value' => 2450000,
        ], $council);
        $r1 = $service->submitRequest($p1, SyndicateRequest::TYPE_SUPERVISION, $owner($p1), SyndicateRequest::BY_CITIZEN, $council);
        $service->markUnderReview($r1, $syndicate);
        $service->issueSupervisionCertificate($r1, $engineer('KFS-10231'), $syndicate);

        // 2) مشروع موقوف بدون إشراف (أُلغي الإشراف بطلب من المهندس) — كفر الشيخ
        $p2 = $service->registerProject([
            'owner_name' => 'سعاد عبد الله البلتاجي', 'owner_national_id' => '28207221500678', 'owner_phone' => '01287654302',
            'address' => 'شارع الشهيد أحمد عصمت - خلف مجمع المصالح', 'center' => 'كفر الشيخ', 'plot_number' => '7/ب',
            'license_number' => 'KFS-2026-1177', 'license_date' => '2026-01-18', 'license_authority' => 'مجلس مدينة كفر الشيخ',
            'works_type' => 'execution', 'execution_sub_type' => 'elevation', 'works_description' => 'تعلية دورين فوق مبنى قائم', 'works_value' => 780000,
        ], $council);
        $r2 = $service->submitRequest($p2, SyndicateRequest::TYPE_SUPERVISION, $owner($p2), SyndicateRequest::BY_CITIZEN, $council);
        $c2 = $service->issueSupervisionCertificate($r2, $engineer('KFS-11874'), $syndicate);
        $r2c = $service->submitRequest($p2, SyndicateRequest::TYPE_CANCELLATION, [
            'name' => $c2->engineer_name, 'national_id' => $c2->engineer_national_id, 'phone' => $c2->engineer_phone,
        ], SyndicateRequest::BY_ENGINEER, $council, 'counter', 'مخالفة المالك للرسومات المعتمدة ورفضه تعليمات المهندس المشرف');
        $service->issueCancellationCertificate($r2c, $syndicate);

        // 3) مشروع عاد للعمل بمهندس جديد بعد الإيقاف — فوه
        $p3 = $service->registerProject([
            'owner_name' => 'إبراهيم السيد عوض', 'owner_national_id' => '26912051500491', 'owner_phone' => '01012340003',
            'address' => 'طريق فوه - مطوبس بجوار مدرسة الشهيد', 'center' => 'فوه', 'village' => 'الشيخ سالم',
            'license_number' => 'FWH-2025-0981', 'license_date' => '2025-11-09', 'license_authority' => 'مجلس مدينة فوه',
            'works_type' => 'execution', 'execution_sub_type' => 'creation', 'works_description' => 'إنشاء منزل ريفي أرضي + دور', 'works_value' => 640000,
        ], $council);
        $r3 = $service->submitRequest($p3, SyndicateRequest::TYPE_SUPERVISION, $owner($p3), SyndicateRequest::BY_CITIZEN, $council);
        $service->issueSupervisionCertificate($r3, $engineer('KFS-12530'), $syndicate);
        $r3c = $service->submitRequest($p3, SyndicateRequest::TYPE_CANCELLATION, $owner($p3), SyndicateRequest::BY_CITIZEN, $council,
            'counter', 'رغبة المالك في تغيير المهندس المشرف');
        $service->issueCancellationCertificate($r3c, $syndicate);
        $r3n = $service->submitRequest($p3, SyndicateRequest::TYPE_SUPERVISION, $owner($p3), SyndicateRequest::BY_CITIZEN, $council);
        $service->issueSupervisionCertificate($r3n, $engineer('KFS-09652'), $syndicate);

        // 4) طلب إشراف جديد بانتظار النقابة — بيلا
        $p4 = $service->registerProject([
            'owner_name' => 'هالة مصطفى قنديل', 'owner_national_id' => '29101011500857', 'owner_phone' => '01145678905',
            'address' => 'شارع المحطة - برج النور', 'center' => 'بيلا', 'license_number' => 'BLA-2026-0233', 'license_date' => '2026-09-21',
            'license_authority' => 'مجلس مدينة بيلا', 'works_type' => 'execution', 'execution_sub_type' => 'creation',
            'works_description' => 'إنشاء مبنى تجاري إداري أرضي + 3 أدوار', 'works_value' => 3100000,
        ], $council);
        $service->submitRequest($p4, SyndicateRequest::TYPE_SUPERVISION, $owner($p4), SyndicateRequest::BY_CITIZEN, $council);

        // 5) طلب هدم قيد المراجعة — الحامول
        $p5 = $service->registerProject([
            'owner_name' => 'ورثة المرحوم حسن عبد العاطي', 'owner_national_id' => '25806301500144', 'owner_phone' => '01067891206',
            'address' => 'عزبة الشال - بجوار الجمعية الزراعية', 'center' => 'الحامول', 'license_number' => 'HML-2026-0057',
            'license_date' => '2026-08-30', 'license_authority' => 'مجلس مدينة الحامول', 'works_type' => 'demolition',
            'works_description' => 'هدم مبنى قديم آيل للسقوط (أرضي + دور)', 'works_value' => 95000,
        ], $council);
        $r5 = $service->submitRequest($p5, SyndicateRequest::TYPE_SUPERVISION, $owner($p5), SyndicateRequest::BY_COUNCIL, $council);
        $service->markUnderReview($r5, $syndicate);

        // 6) طلب إلغاء إشراف مفتوح على المشروع النشط (1) — يظهر لدى النقابة
        $service->submitRequest($p1->fresh(), SyndicateRequest::TYPE_CANCELLATION, $owner($p1), SyndicateRequest::BY_CITIZEN, $council,
            'counter', 'خلاف تعاقدي على أتعاب الإشراف');
    }
}
