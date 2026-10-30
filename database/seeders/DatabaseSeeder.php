<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * يملأ البوابة كاملة ببيانات تجريبية واقعية (عربية) بترتيب الاعتماديات.
 *
 *   php artisan db:seed --force                 (kfs — تطوير)
 *   php artisan db:seed --env=testing --force   (kfs_testing)
 *
 * - غير هدّام: لا truncate ولا حذف؛ كل seeder يعتمد updateOrCreate أو "أكمل حتى العدد المستهدف"،
 *   فإعادة التشغيل لا تكرر البيانات.
 * - في production يُنفذ فقط الأدوار والصلاحيات والإعدادات، إلا إذا ضُبط ALLOW_DEMO_SEED=true.
 * - كلمة مرور الحسابات التجريبية: DEMO_PASSWORD أو كلمة مولدة تُطبع في المخرجات.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            SettingSeeder::class,
        ]);

        if (app()->environment('production') && ! filter_var(env('ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('Production: تم تخطي البيانات التجريبية (اضبط ALLOW_DEMO_SEED=true للسماح).');

            return;
        }

        $this->call([
            // 1) الحسابات والتقسيم الإداري
            UserSeeder::class,
            CenterCityVillageSeeder::class,

            // 2) محتوى البوابة العام
            StatisticSeeder::class,
            HeroSliderSeeder::class,
            CategorySeeder::class,
            PostSeeder::class,
            GovernorateContentSeeder::class,
            PartnerSeeder::class,
            CityGuideCategorySeeder::class,
            LocationSeeder::class,
            LandmarkSeeder::class,
            InvestmentDemoSeeder::class,
            AdvertisementSeeder::class,
            AdvertisingGuideDemoSeeder::class,

            // 3) الخدمات وطلبات المواطنين
            ServiceSeeder::class,
            ServiceSubmissionSeeder::class,
            CitizenRequestsSeeder::class,
            TourismBookingSeeder::class,

            // 4) المنظومة المكانية وقرارات الإزالة
            GisDemoSeeder::class,
            RemovalOrderDemoSeeder::class,

            // 5) استدامة (الأساسيات؛ التفاصيل في LmsDemoSeeder)
            TrainingBasicsSeeder::class,
        ]);

        // 6) seeders الوحدات/الوكلاء الآخرين — تُستدعى فقط إن وُجدت
        foreach ([
            'Database\\Seeders\\LmsDemoSeeder',
            'Modules\\Syndicate\\database\\seeders\\SyndicateDemoSeeder',
            'Modules\\Syndicate\\Database\\Seeders\\SyndicateDemoSeeder',
            'Modules\\Marketplace\\database\\seeders\\MarketplaceDemoSeeder',
            'Modules\\Marketplace\\Database\\Seeders\\MarketplaceDemoSeeder',
            'Modules\\SiteValidity\\database\\seeders\\SiteValidityDemoSeeder',
        ] as $class) {
            // أسماء الأصناف في PHP غير حساسة لحالة الأحرف — لا نستدعي نفس الـ seeder مرتين
            if (class_exists($class) && ! isset($called[$key = strtolower((new \ReflectionClass($class))->getName())])) {
                $called[$key] = true;
                $this->call($class);
            }
        }

        // 7) المعاملات المالية أخيراً (تعتمد على كل العناصر المدفوعة أعلاه)
        $this->call(TransactionDemoSeeder::class);
    }
}
