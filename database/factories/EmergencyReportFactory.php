<?php

namespace Database\Factories;

use Modules\Citizen\Models\EmergencyReport;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmergencyReport> */
class EmergencyReportFactory extends Factory
{
    protected $model = EmergencyReport::class;

    public function definition(): array
    {
        $center = DemoData::pick(DemoData::centerNames());
        [$lat, $lng] = DemoData::coords($center);
        $type = DemoData::pick(['حريق', 'انهيار عقار', 'كسر ماسورة مياه رئيسية', 'سقوط عمود كهرباء', 'حادث طريق', 'غرق أرض زراعية']);
        $status = DemoData::pick(['new', 'dispatched', 'resolved']);
        $female = DemoData::chance(30);

        return [
            'reporter_name' => DemoData::name($female),
            'reporter_national_id' => DemoData::nationalId($female),
            'reporter_phone' => DemoData::phone(),
            'report_type' => $type,
            'location_type' => DemoData::pick(['مدينة', 'قرية']),
            'center' => $center,
            'area' => DemoData::pick(DemoData::VILLAGES[$center]),
            'location_description' => 'بجوار ' . DemoData::pick(['المسجد الكبير', 'مدرسة القرية', 'الوحدة الصحية', 'موقف السيارات']),
            'latitude' => $lat,
            'longitude' => $lng,
            'details' => 'بلاغ عن ' . $type . ' ويحتاج تدخلاً سريعاً.',
            'attachments' => [],
            'status' => $status,
            'admin_reply' => $status === 'new' ? null : 'تم إخطار غرفة العمليات وتحرك الفريق المختص.',
            'replied_at' => $status === 'new' ? null : now()->subDays(mt_rand(0, 10)),
            'created_at' => DemoData::pastDate(45),
        ];
    }
}
