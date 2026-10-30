<?php

namespace Database\Seeders;

use Modules\Content\Models\Partner;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['بوابة مصر الرقمية', 'https://digital.gov.eg', 'منصة الخدمات الحكومية الرقمية الموحدة.'],
            ['بوابة الأزهر الإلكترونية', 'https://www.azhar.eg', 'البوابة الرسمية للأزهر الشريف.'],
            ['دار الإفتاء المصرية', 'https://www.dar-alifta.org', 'الفتاوى والخدمات الشرعية.'],
            ['منظومة الشكاوى الحكومية الموحدة', 'https://www.shakwa.eg', 'تقديم ومتابعة الشكاوى الحكومية.'],
            ['البوابة القومية للتصالح', 'https://www.mld.gov.eg', 'متابعة طلبات التصالح في مخالفات البناء.'],
            ['جامعة كفر الشيخ', 'https://www.kfs.edu.eg', 'الموقع الرسمي لجامعة كفر الشيخ.'],
        ];
        foreach ($rows as $i => [$name, $link, $desc]) {
            Partner::updateOrCreate(['name' => $name], ['link' => $link, 'description' => $desc, 'image' => DemoData::image('partners', 'partner-' . ($i + 1), 400, 400, 'PARTNER')]);
        }
    }
}
