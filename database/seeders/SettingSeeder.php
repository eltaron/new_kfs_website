<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Models\Setting;
use Database\Seeders\Support\DemoData;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $logo = self::brandingLogo();

        $settings = [
            // Basic Site Info
            'site_logo_header' => $logo,
            'site_logo_footer' => $logo,
            'site_favicon' => $logo,
            'site_name' => 'البوابة الإلكترونية لمحافظة كفر الشيخ',
            'site_description' => 'البوابة الرسمية لمحافظة كفر الشيخ: أخبار المحافظة، الخدمات الإلكترونية، الاستثمار، السياحة والمنظومة المكانية.',

            // Social Media Links
            'social_facebook' => 'https://facebook.com',
            'social_twitter' => 'https://twitter.com',
            'social_instagram' => 'https://instagram.com',
            'social_youtube' => 'https://youtube.com',
            'social_tiktok' => 'https://tiktok.com',

            // Hero Section Texts
            'hero_main_title' => 'محافظة كفر الشيخ',
            'hero_subtitle' => 'نحو مستقبل أفضل <span class="highlight">لمواطنينا</span>',
            'hero_search_placeholder' => 'اكتشف خدمات ومشروعات المحافظة...',

            // Events Section Texts
            'events_title' => 'أهم الأحداث',
            'events_subtitle' => 'تابع آخر الفعاليات والقرارات الرسمية داخل محافظة كفر الشيخ',

            // Stats Section Texts
            'stats_title' => 'المحافظة في أرقام',
            'stats_subtitle' => 'نظرة سريعة على أبرز الإحصائيات والمعلومات الأساسية للمحافظة',

            // News Section Texts
            'news_title' => 'آخر الأخبار',
            'news_subtitle' => 'تحديثات يومية تسلط الضوء على آخر أخبار وأنشطة المحافظة.',
            'news_view_all_link_text' => 'عرض كل الأخبار',

            // Services Section Texts
            'services_title' => 'خدمات المحافظة',
            'services_subtitle' => 'مجموعة من الخدمات الموجهة لخدمة مواطني كفر الشيخ ودعم احتياجاتهم اليومية.',
            'services_view_all_link_text' => 'عرض جميع الخدمات',

            // Investment Section Texts
            'investment_title' => '<span class="highlight">الاستثمار</span> في محافظة كفر الشيخ',
            'investment_subtitle' => 'اكتشف أبرز المناطق والفرص الاستثمارية الواعدة في مختلف قطاعات المحافظة.',
            'investment_view_all_link_text' => 'عرض الفرص الاستثمارية',

            // Tourism Section Texts
            'tourism_title' => 'أهم <span class="highlight">المعالم السياحية</span> في المحافظة',
            'tourism_subtitle' => 'اكتشف أبرز المواقع التاريخية والطبيعية التي تميز محافظة كفر الشيخ.',
            'tourism_tagline_image' => 'images/tagline.svg',
            'tourism_view_all_link_text' => 'استكشف المعالم',

            // Projects Section Texts
            'projects_title' => 'المشروعات',

            // Achievements Section Texts
            'achievements_title' => 'إنجازات الدولة المصرية <span class="highlight">بالمحافظة</span>',
            'achievements_description' => 'قامت الدولة المصرية بعدة إنجازات في محافظة كفرالشيخ حيث تم تنفيذ العديد من المشروعات القومية التي تسهم في التنمية الاقتصادية والاجتماعية للمنطقة.',
            'achievements_button_text' => 'استكشف أهم الإنجازات',

            // Partner Apps Section Texts
            'apps_title' => 'مواقع وتطبيقات <span class="highlight">تهمك</span>',
            'apps_subtitle' => 'لقد حظينا بمتعة العمل مع مؤسسات حكومية رائدة في مجالها، وهذه بعض منها فقط',

            // City Guide Section Texts
            'city_guide_title' => 'دليل العاصمة',

            // Secondary hero / pages
            'secondary_hero_title' => 'خدماتك الحكومية أصبحت أقرب',
            'secondary_hero_description' => 'قدّم طلبك إلكترونياً وتابع حالته وادفع الرسوم من مكانك دون الحاجة لزيارة الوحدة المحلية.',
            'secondary_hero_button_text' => 'ابدأ الآن',
            'secondary_hero_button_link' => '/services',
            'governor_page_title' => 'كلمة السيد المحافظ',
            'governor_page_content' => '<p>مرحباً بكم في البوابة الإلكترونية لمحافظة كفر الشيخ. نعمل معاً على تقديم خدمات حكومية رقمية ميسّرة وشفافة لكل مواطن، ودعم مسيرة التنمية في جميع المراكز والقرى.</p><p>(نص تجريبي — يستبدل بالكلمة الرسمية)</p>',
            'governor_page_image' => DemoData::image('branding', 'governor-placeholder', 800, 1000, 'KAFR EL-SHEIKH GOVERNOR'),
            'estidama_hero_title' => 'استدامة للتدريب والتطوير',
            'estidama_infrastructure' => 'قاعات تدريب مجهزة، معامل حاسب آلي، ومنصة تعلم إلكتروني متكاملة.',
            'estidama_logo_white' => $logo,
            // Footer Info
            'footer_description' => 'البوابة الإلكترونية لمحافظة كفر الشيخ.',
            'copyright_text' => 'محافظة كفر الشيخ - جميع الحقوق محفوظة ©',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /** ينسخ شعار المحافظة إلى قرص public (demo/branding) ليعمل مع Storage::url. */
    public static function brandingLogo(): string
    {
        $target = storage_path('app/public/demo/branding/logo.png');
        if (! is_file($target) && is_file(public_path('images/logo.png'))) {
            @mkdir(dirname($target), 0775, true);
            copy(public_path('images/logo.png'), $target);
        }

        return is_file($target) ? 'demo/branding/logo.png' : DemoData::image('branding', 'logo', 400, 400, 'KFS');
    }
}
