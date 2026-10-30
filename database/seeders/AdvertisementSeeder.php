<?php

namespace Database\Seeders;

use Modules\Investment\Models\Advertisement;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/** حصر الإعلانات بالشوارع (لوحة الإعلانات). آمن للتكرار: المفتاح اسم الشارع + الوصف. */
class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', DemoData::email('ads'))->value('id');
        $ads = [
            ['شارع سعد زغلول', 31.111, 30.940, 'لافتة', 4.5, '3×2 متر', 'لوحة إعلانية كبيرة على واجهة مبنى تجاري'],
            ['شارع الجيش', 31.115, 30.945, 'لافتة مضيئة', 6.0, '4×3 متر', 'لوحة نيون على سطح مبنى'],
            ['شارع الجمهورية', 31.108, 30.938, 'لافتة', 3.0, '2×1.5 متر', 'إعلان تجاري لمحل ملابس'],
            ['شارع بورسعيد', 31.120, 30.950, 'يوني بول', 5.0, '3×2 متر', 'لوحة إعلانية عند التقاطع'],
            ['شارع السويس', 31.105, 30.935, 'لافتة', 4.0, '3×1.5 متر', 'إعلان دوائي عند صيدلية'],
            ['ميدان المحطة', 31.118, 30.948, 'بيلبورد', 8.0, '6×3 متر', 'لوحة ضخمة فوق محطة القطار'],
            ['شارع أحمد عرابي', 31.113, 30.942, 'لافتة', 3.5, '2×1 متر', 'إعلان مطعم'],
            ['شارع السكة الحديد', 31.116, 30.947, 'شاشة LED', 5.5, '4×2 متر', 'شاشة إعلانية لشركة اتصالات'],
            ['طريق دسوق - كفر الشيخ', 31.125, 30.800, 'بيلبورد', 9.0, '8×4 متر', 'لوحة طرق سريعة'],
            ['كورنيش بلطيم', 31.553, 31.089, 'لافتة', 4.0, '3×2 متر', 'إعلان منتجع سياحي'],
        ];

        foreach ($ads as $i => [$street, $lat, $lng, $type, $height, $size, $desc]) {
            Advertisement::updateOrCreate(['street_name' => $street, 'description' => $desc], [
                'lat' => $lat, 'lng' => $lng, 'type' => $type, 'height' => $height, 'size' => $size,
                'status' => $i % 4 === 3 ? 'inactive' : 'active',
                'user_id' => $owner,
            ]);
        }

        $this->command?->info('Seeded ' . count($ads) . ' advertisements');
    }
}
