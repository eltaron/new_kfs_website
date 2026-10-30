<?php

namespace Database\Seeders;

use Modules\Tourism\Models\CityGuideCategory;
use Illuminate\Database\Seeder;

class CityGuideCategorySeeder extends Seeder
{
    public const CATEGORIES = [
        'فنادق' => 'fas fa-bed',
        'مستشفيات حكومية' => 'far fa-hospital',
        'مستشفيات خاصة' => 'fas fa-hospital-user',
        'نوادي و متنزهات' => 'fas fa-tree',
        'مواقف للمواصلات العامة' => 'fas fa-bus-alt',
        'بنوك' => 'fas fa-university',
        'مكاتب بريد' => 'fas fa-envelope',
        'مطاعم' => 'fas fa-utensils',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name => $icon) {
            CityGuideCategory::updateOrCreate(['name' => $name], ['icon_class' => $icon]);
        }
    }
}
