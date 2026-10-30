<?php

namespace Database\Seeders;

use Modules\Content\Models\DirectoryEntry;
use Modules\Content\Models\FamousPeople;
use Modules\Content\Models\GovernorateDetail;
use Modules\Content\Models\Official;
use Modules\Content\Models\OfficialRole;
use Modules\Estidama\Models\PartnerLogo;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * المسؤولون (أسماء نائبة — لا أسماء حقيقية)، المشاهير (نائبة)، بيانات المحافظة، شعارات الشركاء، الدليل.
 */
class GovernorateContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->officials();
        $this->famousPeople();
        $this->governorateDetails();
        $this->partnerLogos();
        $this->directory();
    }

    private function officials(): void
    {
        $rows = [
            // [name, role, current, start, end]
            ['السيد المحافظ', 'governor', true, 2024, null],
            ['نائب المحافظ', 'deputy_governor', true, 2024, null],
            ['السكرتير العام للمحافظة', 'secretary_general', true, 2023, null],
            ['السكرتير العام المساعد', 'assistant_secretary_general', true, 2023, null],
            ['محافظ سابق (1)', 'governor', false, 2019, 2024],
            ['محافظ سابق (2)', 'governor', false, 2016, 2019],
            ['محافظ سابق (3)', 'governor', false, 2013, 2016],
            ['سكرتير عام سابق', 'secretary_general', false, 2018, 2023],
        ];
        foreach ($rows as $i => [$name, $role, $current, $start, $end]) {
            $official = Official::updateOrCreate(['name' => $name], [
                'image' => DemoData::image('officials', 'official-' . ($i + 1), 600, 750, 'KAFR EL-SHEIKH OFFICIAL'),
                'bio' => '<p>بيانات تجريبية — تُستبدل بالسيرة الذاتية الرسمية.</p>',
            ]);
            OfficialRole::updateOrCreate(['official_id' => $official->id, 'role_name' => $role, 'start_year' => $start], [
                'is_current' => $current,
                'end_year' => $end,
            ]);
        }
    }

    private function famousPeople(): void
    {
        $cats = ['العلوم والتكنولوجيا', 'القيادات الدينية', 'القيادات السياسية', 'الآداب والعلوم الإنسانية', 'ريادة الأعمال', 'الرياضة واللياقة البدنية', 'الصحافة والإعلام', 'الفنون'];
        foreach ($cats as $i => $cat) {
            for ($k = 1; $k <= 2; $k++) {
                $n = $i * 2 + $k;
                FamousPeople::updateOrCreate(['name' => "شخصية بارزة ({$n})"], [
                    'category' => $cat,
                    'bio' => "من أبناء محافظة كفر الشيخ المتميزين في مجال {$cat}. (بيانات تجريبية)",
                    'image' => DemoData::image('famous', 'famous-' . $n, 600, 600, 'KFS NOTABLE'),
                    'sort_order' => $n,
                ]);
            }
        }
    }

    private function governorateDetails(): void
    {
        $rows = [
            'history' => ['تاريخ المحافظة', 'fas fa-landmark', '<p>تقع محافظة كفر الشيخ في شمال دلتا النيل، وتطل على البحر المتوسط بساحل يمتد لأكثر من 100 كم، وتضم بحيرة البرلس إحدى أهم البحيرات الشمالية. أُنشئت المحافظة رسمياً عام 1949 وكانت تُعرف بمديرية الفؤادية.</p>'],
            'naming' => ['سبب التسمية', 'fas fa-signature', '<p>ترجع التسمية إلى الشيخ "طلحة التلمساني" الذي استقر بالمدينة، فعُرفت بكفر الشيخ طلحة ثم اختُصرت إلى كفر الشيخ.</p>'],
            'vision' => ['الرؤية', 'fas fa-eye', '<p>محافظة رائدة في الاقتصاد الأزرق والزراعة الحديثة والخدمات الحكومية الرقمية، توفر حياة كريمة لمواطنيها.</p>'],
            'mission' => ['الرسالة', 'fas fa-bullseye', '<p>تقديم خدمات حكومية متكاملة وشفافة، وتحفيز الاستثمار، وتحقيق التنمية المتوازنة بين المراكز والقرى.</p>'],
            'geography' => ['الموقع الجغرافي', 'fas fa-globe-africa', '<p>تحدها شمالاً البحر المتوسط، وجنوباً محافظة الغربية، وشرقاً فرع دمياط ومحافظة الدقهلية، وغرباً فرع رشيد ومحافظة البحيرة.</p>'],
            'economy' => ['الأنشطة الاقتصادية', 'fas fa-industry', '<p>الزراعة (الأرز والقطن وبنجر السكر)، الاستزراع السمكي، الصناعات الغذائية، والسياحة الشاطئية في بلطيم.</p>'],
        ];
        foreach ($rows as $key => [$title, $icon, $content]) {
            GovernorateDetail::updateOrCreate(['key' => $key], ['title' => $title, 'icon' => $icon, 'content' => $content]);
        }
    }

    private function partnerLogos(): void
    {
        $rows = ['بوابة مصر الرقمية' => 'https://digital.gov.eg', 'وزارة التنمية المحلية' => 'https://www.mld.gov.eg', 'رئاسة مجلس الوزراء' => 'https://www.cabinet.gov.eg',
            'هيئة الاستثمار' => 'https://www.gafi.gov.eg', 'جامعة كفر الشيخ' => 'https://www.kfs.edu.eg', 'حياة كريمة' => 'https://www.hayakarima.com'];
        $i = 0;
        foreach ($rows as $name => $link) {
            PartnerLogo::updateOrCreate(['name' => $name], ['logo' => DemoData::image('partners', 'logo-' . (++$i), 400, 240, 'PARTNER'), 'link' => $link]);
        }
    }

    private function directory(): void
    {
        $rows = [
            ['أرقام الطوارئ', 'الإسعاف', '123', 'fas fa-ambulance'],
            ['أرقام الطوارئ', 'شرطة النجدة', '122', 'fas fa-shield-alt'],
            ['أرقام الطوارئ', 'المطافئ', '180', 'fas fa-fire-extinguisher'],
            ['أرقام الطوارئ', 'طوارئ الكهرباء', '121', 'fas fa-bolt'],
            ['أرقام الطوارئ', 'طوارئ الغاز', '129', 'fas fa-burn'],
            ['أرقام الطوارئ', 'طوارئ المياه والصرف', '125', 'fas fa-tint'],
            ['ديوان عام المحافظة', 'منظومة الشكاوى الحكومية', '16528', 'fas fa-headset'],
            ['ديوان عام المحافظة', 'غرفة العمليات المركزية', '0473220000', 'fas fa-phone-alt'],
            ['ديوان عام المحافظة', 'المركز التكنولوجي لخدمة المواطنين', '0473230000', 'fas fa-laptop'],
        ];
        foreach (DemoData::centerNames() as $k => $center) {
            $rows[] = ['الوحدات المحلية', "الوحدة المحلية لمركز ومدينة {$center}", '047' . str_pad((string) (3100000 + $k * 1111), 7, '0', STR_PAD_LEFT), 'fas fa-city'];
        }
        foreach ($rows as $i => [$cat, $name, $phone, $icon]) {
            DirectoryEntry::updateOrCreate(['name' => $name], ['category' => $cat, 'phone_number' => $phone, 'icon_class' => $icon, 'order' => $i + 1]);
        }
    }
}
