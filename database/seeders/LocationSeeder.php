<?php

namespace Database\Seeders;

use Modules\Tourism\Models\CityGuideCategory;
use Modules\Tourism\Models\Location;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    private const PLACES = [
        'فنادق' => ['فندق الجامعة', 'فندق النيل كفر الشيخ', 'فندق بلطيم السياحي'],
        'مستشفيات حكومية' => ['مستشفى كفر الشيخ العام', 'مستشفى دسوق العام', 'مستشفى الجامعة', 'مستشفى بيلا المركزي'],
        'مستشفيات خاصة' => ['مستشفى الشفاء التخصصي', 'مستشفى السلام الدولي'],
        'نوادي و متنزهات' => ['نادي كفر الشيخ الرياضي', 'حديقة الطفل', 'ممشى أهل مصر'],
        'مواقف للمواصلات العامة' => ['موقف كفر الشيخ الجديد', 'موقف دسوق', 'محطة قطار كفر الشيخ'],
        'بنوك' => ['البنك الأهلي - فرع كفر الشيخ', 'بنك مصر - فرع دسوق'],
        'مكاتب بريد' => ['مكتب بريد كفر الشيخ الرئيسي', 'مكتب بريد بلطيم'],
        'مطاعم' => ['مطعم أسماك البرلس', 'مطعم الريف المصري'],
    ];

    public function run(): void
    {
        DemoData::seed(3004);
        $cats = CityGuideCategory::pluck('id', 'name');
        foreach (self::PLACES as $cat => $places) {
            if (! isset($cats[$cat])) {
                continue;
            }
            foreach ($places as $name) {
                $center = str_contains($name, 'دسوق') ? 'دسوق' : (str_contains($name, 'بلطيم') || str_contains($name, 'البرلس') ? 'بلطيم' : (str_contains($name, 'بيلا') ? 'بيلا' : 'كفر الشيخ'));
                [$lat, $lng] = DemoData::coords($center);
                Location::updateOrCreate(['name' => $name], ['city_guide_category_id' => $cats[$cat], 'latitude' => $lat, 'longitude' => $lng]);
            }
        }
    }
}
