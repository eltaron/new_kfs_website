<?php

namespace Database\Seeders;

use Modules\RemovalGovernance\Models\RemovalOrder;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * قرارات الإزالة في كل مراحل سير العمل (9 مراحل × 3 قرارات) مع المستخدم المسند إليه حسب الدور:
 *  created → engineering_review → spatial_pending → spatial_dimensioned → pdf_ready
 *  → systems_processing → local_unit_approval → visa_pending → completed
 * المفتاح الثابت: stop_order_number = DEMO-SO-NNN (آمن للتكرار).
 */
class RemovalOrderDemoSeeder extends Seeder
{
    public const PER_STAGE = 3;

    public function run(): void
    {
        DemoData::seed(9009);
        $u = fn (string $local) => User::where('email', DemoData::email($local))->value('id');
        $people = [
            'field' => $u('gis.field'),
            'tech' => $u('gis.org.tech'),
            'engineer' => $u('gis.org.engineer'),
            'org_manager' => $u('gis.org.manager'),
            'dept_head' => $u('gis.dept.head'),
            'center_manager' => $u('gis.center.manager'),
            'changes_manager' => $u('gis.changes.manager'),
            'changes_member' => $u('gis.changes.member'),
            'systems' => $u('gis.systems'),
            'unit_manager' => $u('gis.unit.manager'),
            'governor_office' => $u('gis.governor.office'),
        ];
        if (! $people['field']) {
            $this->command?->warn('RemovalOrderDemoSeeder: حسابات GIS غير موجودة — شغّل UserSeeder أولاً.');

            return;
        }

        $stages = array_keys(RemovalOrder::stages());
        $n = 0;
        foreach ($stages as $stageIndex => $stage) {
            for ($k = 1; $k <= self::PER_STAGE; $k++) {
                $n++;
                $center = DemoData::centerNames()[$n % 13];
                $village = DemoData::pick(DemoData::VILLAGES[$center]);
                [$lat, $lng] = DemoData::coords($center);
                $licensed = $n % 3 === 0;
                $created = now()->subDays(120 - $n * 3);
                $len = mt_rand(8, 25);
                $wid = mt_rand(6, 15);
                $reached = fn (string $s) => $stageIndex >= array_search($s, $stages, true);

                $data = [
                    'violation_type' => $licensed ? 'licensed_violation' : 'new_violation',
                    'license_number' => $licensed ? 'LIC-' . mt_rand(1000, 9999) : null,
                    'license_date' => $licensed ? $created->copy()->subYears(2)->toDateString() : null,
                    'licensed_works' => $licensed ? 'أرضي + 3 أدوار علوية' : null,
                    'center' => $center,
                    'local_unit' => 'الوحدة المحلية ' . $village,
                    'street' => 'شارع ' . DemoData::pick(['الجمهورية', 'المدارس', 'الجامع', 'البحر', 'المحطة']),
                    'violation_area' => $village,
                    'district' => $village,
                    'owner_name' => DemoData::name(false),
                    'owner_national_id' => DemoData::nationalId(false),
                    'owner_center' => $center,
                    'owner_unit' => $village,
                    'owner_street' => 'شارع ' . DemoData::pick(['السلام', 'النصر', 'الشهداء']),
                    'owner_district' => $village,
                    'owner_governorate' => 'كفر الشيخ',
                    'engineer_name' => 'م/ ' . DemoData::name(false),
                    'engineer_national_id' => DemoData::nationalId(false),
                    'contractor_name' => DemoData::name(false),
                    'contractor_national_id' => DemoData::nationalId(false),
                    'violation_plot' => 'قطعة رقم ' . mt_rand(1, 300),
                    'violation_dimensions' => "{$len} × {$wid} م",
                    'border_north' => 'شارع بعرض 6م', 'border_south' => 'ملك الغير', 'border_east' => 'ملك الغير', 'border_west' => 'مصرف',
                    'dimension_north' => (string) $wid, 'dimension_south' => (string) $wid, 'dimension_east' => (string) $len, 'dimension_west' => (string) $len,
                    'area' => $len * $wid,
                    'engineer_description' => 'بناء ' . ($licensed ? 'أدوار زائدة عن الترخيص' : 'بدون ترخيص على أرض زراعية'),
                    'violation_cost' => $len * $wid * mt_rand(150, 400),
                    'violation_works' => $licensed ? 'إقامة دور رابع وخامس بالمخالفة للترخيص' : 'إقامة أعمدة وأسقف خرسانية دون ترخيص',
                    'stop_order_date' => $created->toDateString(),
                    'violation_report_number' => 'VR-' . (2026) . '-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                    'violation_report_file' => $stageIndex > 0 || $k > 1 ? DemoData::pdf('removal-orders', 'report-' . $n) : null,
                    'report_date' => $created->toDateString(),
                    'announcement_date' => $created->copy()->addDays(2)->toDateString(),
                    'photo_file' => DemoData::image('removal-orders', 'photo-' . ($n % 6), 1000, 700, 'VIOLATION PHOTO (DEMO)'),
                    'sketch_file' => DemoData::image('removal-orders', 'sketch-' . ($n % 4), 1000, 700, 'SITE SKETCH (DEMO)'),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'stage' => $stage,
                    'status' => match (true) {
                        $stage === 'created' => 'قيد الإعداد',
                        $stage === 'engineering_review' => 'قيد المراجعة',
                        $stage === 'completed' => 'تم التنفيذ',
                        default => 'قيد التنفيذ',
                    },
                    'created_by' => $k % 2 ? $people['field'] : $people['tech'],
                    'engineering_engineer_id' => $reached('engineering_review') ? $people['engineer'] : null,
                    'spatial_manager_id' => $reached('spatial_pending') ? $people['changes_manager'] : null,
                    'spatial_member_id' => $reached('spatial_dimensioned') ? $people['changes_member'] : null,
                    'spatial_data' => $reached('spatial_dimensioned') ? ['lat' => $lat, 'lng' => $lng, 'area_m2' => $len * $wid, 'parcel' => 'P-' . mt_rand(100, 999)] : null,
                    'pdf_file' => $reached('pdf_ready') ? DemoData::pdf('removal-orders', 'spatial-' . $n) : null,
                    'systems_specialist_id' => $reached('systems_processing') ? $people['systems'] : null,
                    'governor_office_id' => $reached('visa_pending') ? $people['governor_office'] : null,
                    'visa_file' => $stage === 'completed' ? DemoData::pdf('removal-orders', 'visa-' . $n) : null,
                    'governor_decision_number' => $stage === 'completed' ? (string) (1200 + $n) : null,
                    'governor_decision_date' => $stage === 'completed' ? $created->copy()->addDays(30)->toDateString() : null,
                    'citizen_notification_time' => $stage === 'completed' ? $created->copy()->addDays(32) : null,
                    'citizen_notification_file' => $stage === 'completed' ? DemoData::pdf('removal-orders', 'citizen-notice-' . $n) : null,
                    'police_notification_time' => $stage === 'completed' ? $created->copy()->addDays(33) : null,
                    'police_notification_file' => $stage === 'completed' ? DemoData::pdf('removal-orders', 'police-notice-' . $n) : null,
                    'review_notes' => $stage === 'engineering_review' ? 'مراجعة الأبعاد مع الطبيعة' : null,
                    'payment_status' => $stage === 'completed' ? ($k === 1 ? 'paid' : 'pending') : 'unpaid',
                    'paid_at' => $stage === 'completed' && $k === 1 ? $created->copy()->addDays(40) : null,
                    'created_at' => $created,
                    'updated_at' => $created->copy()->addDays($stageIndex * 3),
                ];

                [$assignedTo, $assignedBy] = match ($stage) {
                    'created' => [$people['engineer'], $people['field']],
                    'engineering_review' => [$people['dept_head'], $people['engineer']],
                    'spatial_pending' => [$people['changes_manager'], $people['dept_head']],
                    'spatial_dimensioned' => [$people['changes_member'], $people['changes_manager']],
                    'pdf_ready' => [$people['center_manager'], $people['changes_member']],
                    'systems_processing' => [$people['systems'], $people['center_manager']],
                    'local_unit_approval' => [$people['unit_manager'], $people['systems']],
                    'visa_pending' => [$people['governor_office'], $people['unit_manager']],
                    default => [null, $people['governor_office']],
                };
                $data['assigned_to'] = $assignedTo;
                $data['assigned_by'] = $assignedBy;

                $order = RemovalOrder::firstOrNew(['stop_order_number' => sprintf('DEMO-SO-%03d', $n)]);
                $order->forceFill($data)->save();
            }
        }

        $this->command?->info("Seeded {$n} removal orders across " . count($stages) . ' stages.');
    }
}
