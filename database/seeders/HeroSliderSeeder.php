<?php

namespace Database\Seeders;

use Modules\Content\Models\HeroSlider;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class HeroSliderSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            ['السيد المحافظ يتفقد أعمال تطوير مدخل مدينة كفر الشيخ', 'خطوة جديدة نحو تطوير البنية التحتية وتحسين المظهر الحضاري للمدينة.', '/news', 'قراءة المزيد'],
            ['منظومة الخدمات المكانية الإلكترونية', 'استخرج شهادة الموقف من الحيز العمراني وتابع طلبك إلكترونياً.', '/gis', 'ابدأ الطلب'],
            ['كفر الشيخ وجهة واعدة للاستثمار', 'فرص استثمارية في الصناعات الغذائية والاستزراع السمكي والسياحة الشاطئية.', '/investment', 'استكشف الفرص'],
            ['بحيرة البرلس وشواطئ بلطيم', 'اكتشف المقاصد السياحية والطبيعية في المحافظة.', '/landmarks', 'اكتشف المعالم'],
            ['مبادرة حياة كريمة في قرى المحافظة', 'تطوير شامل للبنية الأساسية والخدمات في قرى الريف.', '/hayah-karima', 'تعرف على المشروعات'],
        ];
        foreach ($slides as $i => [$title, $desc, $link, $text]) {
            HeroSlider::updateOrCreate(['title' => $title], [
                'description' => $desc,
                'media_path' => DemoData::image('hero', 'slide-' . ($i + 1), 1920, 900),
                'media_type' => 'image',
                'link_url' => $link,
                'link_text' => $text,
                'order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
