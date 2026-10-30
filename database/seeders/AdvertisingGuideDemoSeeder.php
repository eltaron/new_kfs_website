<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;
use Modules\Investment\Models\Advertisement;

/**
 * الدليل الإرشادي للإعلانات — بيانات تجريبية واقعية لشوارع ومحاور حقيقية بمراكز محافظة كفر الشيخ.
 *
 * - آمن للتكرار وغير هدّام: المفتاح (الشارع + خط العرض + خط الطول) بإحداثيات حتمية (بذرة ثابتة)،
 *   فإعادة التشغيل تحدّث نفس الصفوف ولا تضيف صفوفاً جديدة ولا تحذف شيئاً.
 * - كل النقاط مملوكة لحسابات تجريبية (@demo.kfs.invalid): إدارة الإعلانات + عدد من المواطنين التجريبيين
 *   (citizen01..04) حتى تظهر أزرار «تعديل/حذف» للمواطن على نقاطه فقط.
 *
 *   php artisan db:seed --class=AdvertisingGuideDemoSeeder --force
 */
class AdvertisingGuideDemoSeeder extends Seeder
{
    /** [street, centre, from [lat,lng], to [lat,lng], points, profile, landmarks] */
    public const STREETS = [
        ['شارع الجيش', 'كفر الشيخ', [31.1032, 30.9352], [31.1188, 30.9446], 9, 'city', ['مبنى ديوان عام المحافظة', 'ميدان المحطة', 'مجمع المصالح الحكومية', 'نادي كفر الشيخ الرياضي']],
        ['شارع صلاح سالم', 'كفر الشيخ', [31.1078, 30.9292], [31.1152, 30.9334], 6, 'city', ['مستشفى كفر الشيخ العام', 'مدرسة الثانوية بنين', 'مسجد الاستاد']],
        ['شارع شكري القوتلي', 'كفر الشيخ', [31.1124, 30.9418], [31.1094, 30.9482], 5, 'city', ['البنك الأهلي', 'مكتب البريد', 'سوق الخضار']],
        ['شارع الشهيد أحمد عبد الرازق', 'كفر الشيخ', [31.1056, 30.9431], [31.1012, 30.9486], 5, 'city', ['جامعة كفر الشيخ', 'المدينة الجامعية', 'موقف الجامعة']],
        ['الطريق الدائري - كفر الشيخ', 'كفر الشيخ', [31.0948, 30.9246], [31.1006, 30.9556], 6, 'highway', ['تقاطع طريق سخا', 'مدخل المنطقة الصناعية', 'محطة الوقود']],
        ['طريق كفر الشيخ - بلطيم الدولي', 'كفر الشيخ', [31.1262, 30.9512], [31.1618, 30.9708], 7, 'highway', ['مدخل المدينة الشمالي', 'كوبري المشاة', 'قرية الشين', 'مزلقان السكة الحديد']],
        ['شارع الشيخ إبراهيم الدسوقي', 'دسوق', [31.1338, 30.6458], [31.1296, 30.6516], 7, 'city', ['مسجد سيدي إبراهيم الدسوقي', 'ساحة المسجد', 'سوق دسوق', 'مجلس مدينة دسوق']],
        ['كورنيش النيل - دسوق', 'دسوق', [31.1402, 30.6436], [31.1248, 30.6418], 6, 'corniche', ['مرسى المعدية', 'حديقة الكورنيش', 'نادي دسوق']],
        ['طريق دسوق - كفر الشيخ', 'دسوق', [31.1334, 30.6612], [31.1236, 30.7982], 6, 'highway', ['مدخل دسوق الشرقي', 'محلة أبو علي', 'كوبري قلين']],
        ['الطريق الدولي الساحلي - بلطيم', 'بلطيم', [31.5478, 31.0302], [31.5604, 31.1488], 7, 'highway', ['مدخل مصيف بلطيم', 'كوبري المشاة الدولي', 'محطة رسوم بلطيم', 'بوابة الكثبان']],
        ['شارع البحر - مصيف بلطيم', 'بلطيم', [31.5846, 31.0868], [31.5872, 31.1034], 5, 'corniche', ['شاطئ بلطيم', 'ممشى المصيف', 'ميدان الصيادين']],
        ['شارع الجمهورية - سيدي سالم', 'سيدي سالم', [31.2742, 30.7826], [31.2688, 30.7902], 5, 'city', ['مجلس مدينة سيدي سالم', 'موقف السيارات', 'المستشفى المركزي']],
        ['كورنيش النيل - فوه', 'فوه', [31.2104, 30.5448], [31.1978, 30.5474], 5, 'corniche', ['مسجد أبو النجاة', 'مصنع الكليم', 'الشارع الأثري']],
        ['شارع المحطة - بيلا', 'بيلا', [31.1768, 31.2186], [31.1718, 31.2248], 4, 'city', ['محطة قطار بيلا', 'مجلس المدينة', 'السوق التجاري']],
        ['شارع الجيش - مطوبس', 'مطوبس', [31.2918, 30.5128], [31.2872, 30.5188], 4, 'city', ['مجلس مدينة مطوبس', 'مرسى رشيد', 'المستشفى المركزي']],
        ['طريق قلين - كفر الشيخ', 'قلين', [31.0498, 30.8462], [31.0802, 30.9046], 5, 'highway', ['مدخل قلين', 'مركز الفخار', 'قرية شباس عمير']],
        ['شارع بورسعيد - الحامول', 'الحامول', [31.3138, 31.1472], [31.3092, 31.1534], 4, 'city', ['مجلس مدينة الحامول', 'سوق الحامول', 'مدرسة الحامول الثانوية']],
        ['شارع الجلاء - الرياض', 'الرياض', [31.2392, 30.9438], [31.2348, 30.9496], 4, 'city', ['مجلس مدينة الرياض', 'المستشفى المركزي', 'موقف الرياض']],
        ['طريق برج البرلس الساحلي', 'برج البرلس', [31.5702, 30.9762], [31.5748, 31.0148], 4, 'highway', ['ميناء الصيد', 'بوغاز البرلس', 'مدخل برج البرلس']],
    ];

    /** type => [sizes, height range, weight per profile city|highway|corniche] */
    protected const TYPE_SPECS = [
        'لافتة'       => [['2×1 م', '3×1.5 م', '3×2 م', '4×1 م'], [3.0, 5.0], [30, 4, 14]],
        'لافتة مضيئة' => [['1.2×1.8 م', '2×3 م', '1.5×2.5 م'], [2.5, 3.5], [18, 0, 18]],
        'بيلبورد'     => [['6×3 م', '8×4 م', '12×4 م'], [6.0, 10.0], [6, 34, 10]],
        'يوني بول'    => [['10×5 م (وجهان)', '12×4 م (وجهان)', '14×5 م (ثلاثة أوجه)'], [12.0, 16.0], [2, 26, 6]],
        'ميجا كوم'    => [['3×4 م', '4×6 م'], [3.0, 4.5], [14, 4, 16]],
        'شاشة LED'    => [['4×3 م', '6×3 م', '5×2.5 م'], [5.0, 8.0], [10, 8, 12]],
        'جداري'       => [['8×6 م', '10×8 م', '6×4 م'], [4.0, 12.0], [8, 2, 4]],
        'كوبري'       => [['12×2 م', '15×2.5 م'], [6.0, 7.0], [0, 18, 4]],
        'باص'         => [['1.2×1.8 م', '1.5×2 م'], [0.5, 1.0], [12, 4, 16]],
    ];

    protected const FEATURES = [
        'وجه واحد مع إضاءة ليلية',
        'وجهان متقابلان مع إضاءة LED',
        'هيكل معدني مجلفن مثبت على قاعدة خرسانية',
        'إطار ألومنيوم وطباعة فينيل مقاومة للأشعة',
        'مرئي لحركة السير في الاتجاهين',
        'متاح للحجز — يُراجع الترخيص قبل التركيب',
        'على مسافة آمنة من الإشارات المرورية',
    ];

    protected const SIDES = ['على الجانب الأيمن من', 'على الجانب الأيسر من', 'في الجزيرة الوسطى من', 'عند تقاطع'];

    public function run(): void
    {
        DemoData::seed(4207);

        $owners = User::whereIn('email', [
            DemoData::email('ads'),
            DemoData::email('citizen01'), DemoData::email('citizen02'),
            DemoData::email('citizen03'), DemoData::email('citizen04'),
        ])->pluck('id', 'email');
        $staffOwner = $owners[DemoData::email('ads')] ?? null;
        $citizens = collect(['citizen01', 'citizen02', 'citizen03', 'citizen04'])
            ->map(fn ($c) => $owners[DemoData::email($c)] ?? null)->filter()->values()->all();

        $profiles = ['city' => 0, 'highway' => 1, 'corniche' => 2];
        $n = 0;
        $created = 0;

        foreach (self::STREETS as [$street, $centre, $from, $to, $count, $profile, $landmarks]) {
            for ($i = 0; $i < $count; $i++) {
                $t = $count > 1 ? $i / ($count - 1) : 0.5;
                // Spread along the street axis + a small deterministic lateral offset (a few metres).
                $lat = round($from[0] + ($to[0] - $from[0]) * $t + mt_rand(-12, 12) / 100000, 7);
                $lng = round($from[1] + ($to[1] - $from[1]) * $t + mt_rand(-12, 12) / 100000, 7);

                $type = $this->pickType($profiles[$profile]);
                [$sizes, [$hMin, $hMax]] = self::TYPE_SPECS[$type];
                $size = DemoData::pick($sizes);
                $height = round($hMin + mt_rand(0, (int) (($hMax - $hMin) * 10)) / 10, 1);
                $landmark = $landmarks[$i % count($landmarks)];
                $label = Advertisement::TYPES[$type]['label'];
                $description = sprintf('%s %s %s بالقرب من %s — %s.', $label, DemoData::pick(self::SIDES), $street, $landmark, DemoData::pick(self::FEATURES));

                $owner = ($n % 6 === 2 && $citizens) ? $citizens[intdiv($n, 6) % count($citizens)] : $staffOwner;
                // ~6% of points are archived (inactive) so the admin panel shows both states; the public map hides them.
                $status = $n % 17 === 9 ? 'inactive' : 'active';

                $ad = Advertisement::updateOrCreate(
                    ['street_name' => $street, 'lat' => $lat, 'lng' => $lng],
                    ['type' => $type, 'height' => $height, 'size' => $size, 'description' => $description, 'status' => $status, 'user_id' => $owner]
                );
                $created += $ad->wasRecentlyCreated ? 1 : 0;
                $n++;
            }
        }

        $this->command?->info("AdvertisingGuideDemoSeeder: {$n} points across " . count(self::STREETS) . " streets ({$created} new).");
    }

    protected function pickType(int $profile): string
    {
        $total = 0;
        foreach (self::TYPE_SPECS as $spec) {
            $total += $spec[2][$profile];
        }
        $r = mt_rand(1, $total);
        foreach (self::TYPE_SPECS as $type => $spec) {
            $r -= $spec[2][$profile];
            if ($r <= 0) {
                return $type;
            }
        }

        return 'لافتة';
    }

    /** Total number of points this seeder maintains. */
    public static function expectedCount(): int
    {
        return array_sum(array_column(self::STREETS, 4));
    }
}
