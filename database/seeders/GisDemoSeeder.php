<?php

namespace Database\Seeders;

use Modules\Payments\Models\FulfillmentFee;
use Modules\Gis\Models\GisMarkaz;
use Modules\Gis\Models\GisReport;
use Modules\Gis\Models\GisServiceType;
use Modules\Gis\Models\GisStudyReason;
use Modules\Gis\Models\GisSubmission;
use Modules\Gis\Models\GisSubService;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * المنظومة المكانية: أنواع الخدمات، الخدمات الفرعية بأنظمة التسعير الثلاثة (fixed/formula/tiered)،
 * أسباب إعادة الدراسة، أرشيف الشهادات، وطلبات المواطنين في كل حالات سير العمل.
 */
class GisDemoSeeder extends Seeder
{
    public const SUBMISSIONS = 48;

    public function run(): void
    {
        DemoData::seed(5005);
        $subServices = $this->services();
        $this->studyReasons();
        $this->certificatesArchive();
        $this->submissions($subServices);
        $this->reports();
    }

    /** @return array<int, GisSubService> */
    private function services(): array
    {
        $req = '<ul><li>صورة بطاقة الرقم القومي للمالك</li><li>سند الملكية أو عقد مسجل</li><li>توكيل رسمي في حالة الوكالة</li><li>كروكي للموقع</li></ul>';
        $terms = '<ul><li>تُحتسب الرسوم وفقاً للائحة المعتمدة من المحافظة.</li><li>المعاينة الميدانية خلال 7 أيام عمل من السداد.</li><li>البيانات المقدمة على مسئولية مقدم الطلب.</li></ul>';
        $area = ['label' => 'مساحة قطعة الأرض (م²)', 'name' => 'area_m2', 'type' => 'number', 'is_required' => true];
        $usage = ['label' => 'الاستخدام الحالي', 'name' => 'land_use', 'type' => 'select', 'options' => "سكني\nتجاري\nزراعي\nصناعي", 'is_required' => true];
        $deed = ['label' => 'صورة سند الملكية', 'name' => 'ownership_deed', 'type' => 'file', 'is_required' => false];

        $types = [
            ['خدمات الحيز العمراني', 'fas fa-city', 'تحديد موقف العقار من الحيز العمراني المعتمد والاشتراطات البنائية.', [
                ['شهادة بيان الموقف من الحيز العمراني', 'urban-boundary-certificate', 'tiered', 500, ['tiers' => [['max' => 175, 'price' => 500], ['max' => 500, 'price' => 900], ['max' => 1000, 'price' => 1500], ['max' => 4200, 'price' => 2500]], 'has_overflow' => true, 'overflow_threshold' => 4200, 'overflow_price' => 2000, 'overflow_unit_size' => 4200], [$area, $usage, $deed]],
                ['شهادة الاشتراطات البنائية', 'building-requirements-certificate', 'fixed', 750, null, [$area, $usage]],
            ]],
            ['خدمات الرفع المساحي', 'fas fa-drafting-compass', 'رفع مساحي دقيق بأجهزة GPS للأراضي والمباني.', [
                ['رفع مساحي لقطعة أرض', 'land-survey', 'formula', 300, ['multiplier' => 2.5, 'variable' => 'area_m2', 'fixed_extra' => 100], [$area, $deed]],
                ['تحديد إحداثيات نقاط', 'coordinates-points', 'tiered', 400, ['tiers' => [['max' => 1000, 'price' => 400], ['max' => 5000, 'price' => 800]], 'point_threshold' => 4, 'point_extra' => 75],
                    [$area, ['label' => 'عدد النقاط', 'name' => 'points_count', 'type' => 'number', 'is_required' => true]]],
            ]],
            ['خدمات البيانات والخرائط', 'fas fa-map', 'استخراج خرائط ونسخ من البيانات المكانية المعتمدة.', [
                ['خريطة مساحية مستخرجة', 'cadastral-map-extract', 'fixed', 250, null, [['label' => 'رقم القطعة', 'name' => 'plot_no', 'type' => 'text', 'is_required' => true]]],
                ['صورة جوية حديثة للموقع', 'aerial-image', 'fixed', 350, null, [['label' => 'وصف الموقع', 'name' => 'site_desc', 'type' => 'text', 'is_required' => true]]],
            ]],
        ];

        $out = [];
        foreach ($types as [$name, $icon, $desc, $subs]) {
            $type = GisServiceType::updateOrCreate(['name' => $name], ['icon' => $icon, 'description' => $desc]);
            foreach ($subs as [$sName, $slug, $pricing, $base, $settings, $fields]) {
                $out[] = GisSubService::updateOrCreate(['slug' => $slug], [
                    'gis_service_type_id' => $type->id,
                    'name' => $sName,
                    'description' => $sName . ' — خدمة إلكترونية من المنظومة المكانية لمحافظة كفر الشيخ.',
                    'requirements' => $req,
                    'terms_conditions' => $terms,
                    'dynamic_fields' => $fields,
                    'base_price' => $base,
                    'pricing_type' => $pricing,
                    'pricing_settings' => $settings,
                    'has_vat' => true,
                    'martyr_stamp_fee' => 5,
                    'sms_fee' => 10,
                ]);
            }
        }

        return $out;
    }

    private function studyReasons(): void
    {
        foreach (['اختلاف المساحة الفعلية عن المستندات', 'تعديل الحدود بين الجيران', 'تغير الاستخدام', 'خطأ في الإحداثيات', 'صدور قرار حيز عمراني جديد'] as $r) {
            GisStudyReason::firstOrCreate(['reason_text' => $r]);
        }
    }

    private function certificatesArchive(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            DB::table('gis_certificates_archive')->updateOrInsert(
                ['certificate_number' => sprintf('ARC-%d-%05d', 2020 + $i % 5, $i)],
                ['owner_name' => DemoData::name(), 'issue_date' => now()->subDays(400 + $i * 13)->toDateString(), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /** @param array<int, GisSubService> $subServices */
    private function submissions(array $subServices): void
    {
        $existing = GisSubmission::where('serial_number', 'like', 'DEMO-GIS-%')->count();
        if ($existing >= self::SUBMISSIONS) {
            return;
        }

        $citizens = User::where('email', 'like', 'citizen%@demo.kfs.invalid')->get();
        $staff = User::where('email', DemoData::email('gis.dept.head'))->first();
        $markazs = GisMarkaz::with('shiakhas.villages')->get();
        if ($citizens->isEmpty() || $markazs->isEmpty()) {
            $this->command?->warn('GisDemoSeeder: لا يوجد مواطنون أو مراكز — تم التخطي.');

            return;
        }

        $pricing = app(\App\Services\Payments\Pricing\GisPricing::class);
        // [status, payment_status, fulfillment_status]
        $scenarios = [
            ['received', 'pending', 'none'], ['received', 'paid', 'none'], ['processing', 'paid', 'none'],
            ['processing', 'paid', 'requested'], ['processing', 'paid', 'completed'], ['completed', 'paid', 'none'],
            ['completed', 'paid', 'completed'], ['rejected', 'paid', 'none'], ['rejected', 'failed', 'none'], ['received', 'failed', 'none'],
        ];

        for ($i = $existing + 1; $i <= self::SUBMISSIONS; $i++) {
            [$status, $pay, $fulfil] = $scenarios[($i - 1) % count($scenarios)];
            $user = $citizens[($i * 7) % $citizens->count()];
            $service = $subServices[$i % count($subServices)];
            $markaz = $markazs[$i % $markazs->count()];
            $shiakha = $markaz->shiakhas->first();
            $village = $shiakha?->villages->first();
            $formData = ['area_m2' => mt_rand(120, 6000), 'land_use' => DemoData::pick(['سكني', 'تجاري', 'زراعي']), 'points_count' => mt_rand(3, 10), 'plot_no' => (string) mt_rand(10, 999), 'site_desc' => 'بجوار المسجد الكبير'];
            $quote = $pricing->quote($service, $formData);
            $created = DemoData::pastDate(150, 1);
            [$lat, $lng] = DemoData::coords($markaz->name);

            $sub = GisSubmission::create([
                'user_id' => $user->id,
                'gis_sub_service_id' => $service->id,
                'serial_number' => sprintf('DEMO-GIS-%04d', $i),
                'request_type' => $i % 9 === 0 ? 'restudy' : ($i % 13 === 0 ? 'duplicate' : 'new'),
                'status' => $status,
                'payment_status' => $pay,
                'paid_at' => $pay === 'paid' ? $created->copy()->addHours(2) : null,
                'total_amount' => $quote['total'],
                'applicant_info' => ['type' => $i % 6 === 0 ? 'agent' : 'owner', 'name' => $user->name, 'agent_name' => $i % 6 === 0 ? DemoData::name(false) : null],
                'address_info' => ['markaz_id' => $markaz->id, 'shiakha_id' => $shiakha?->id, 'village_id' => $village?->id, 'details' => ($village?->name ?? $markaz->name) . ' - شارع ' . DemoData::pick(['الجمهورية', 'المحطة', 'البحر', 'سعد زغلول'])],
                'form_data' => $formData,
                'attachments' => ['disk' => 'public', 'id_front' => DemoData::image('gis-attachments', 'id-card-' . ($i % 10), 800, 500, 'NATIONAL ID (DEMO)')],
                'fulfillment_status' => $fulfil,
                'fulfillment_action' => $fulfil !== 'none' ? DemoData::pick(['fee', 'data']) : null,
                'fulfillment_reason' => $fulfil !== 'none' ? 'زيادة المساحة الفعلية عن المساحة المذكورة بالطلب' : null,
                'fulfillment_fee_amount' => $fulfil !== 'none' ? 250 : null,
                'fulfillment_requested_by' => $fulfil !== 'none' ? $staff?->id : null,
                'fulfillment_requested_at' => $fulfil !== 'none' ? $created->copy()->addDays(3) : null,
                'fulfillment_completed_at' => $fulfil === 'completed' ? $created->copy()->addDays(5) : null,
                'inspection_date' => in_array($status, ['processing', 'completed'], true) ? $created->copy()->addDays(4)->setTime(10, 0) : null,
                'is_inspection_confirmed' => $status === 'completed',
                'borders' => $status === 'completed' ? ['north' => 'شارع بعرض 8م', 'south' => 'ملك الغير', 'east' => 'مصرف', 'west' => 'ملك الغير'] : null,
                'urban_planning' => $status === 'completed' ? ['inside_boundary' => true, 'max_height' => '13 م', 'setback' => '2 م'] : null,
                'web_map_url' => null,
                'created_at' => $created,
                'updated_at' => $created->copy()->addDays(mt_rand(0, 6)),
            ]);

            if ($fulfil !== 'none') {
                FulfillmentFee::create([
                    'feeable_type' => 'gis_submission',
                    'feeable_id' => (string) $sub->id,
                    'user_id' => $user->id,
                    'amount' => 250,
                    'reason' => 'رسوم استيفاء: فرق مساحة',
                    'status' => $fulfil === 'completed' ? FulfillmentFee::STATUS_PAID : FulfillmentFee::STATUS_PENDING,
                    'requested_by' => $staff?->id,
                    'paid_at' => $fulfil === 'completed' ? $created->copy()->addDays(5) : null,
                    'created_at' => $created->copy()->addDays(3),
                ]);
            }
        }
    }

    private function reports(): void
    {
        $user = User::where('email', DemoData::email('gis.center.manager'))->first();
        if (! $user || GisReport::count() >= 3) {
            return;
        }
        foreach (['تقرير الطلبات الشهري', 'تقرير الإيرادات حسب المركز', 'تقرير قرارات الإزالة'] as $i => $t) {
            GisReport::create(['report_title' => $t, 'report_type' => ['submissions', 'revenue', 'removals'][$i], 'filters_applied' => ['from' => now()->subMonth()->toDateString(), 'to' => now()->toDateString()], 'file_path' => null, 'user_id' => $user->id]);
        }
    }
}
