<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * حسابات تجريبية: حساب لكل دور في كل لوحة + 50 مواطناً.
 *
 * كلمة المرور: من DEMO_PASSWORD في .env، وإلا تُولد عشوائياً وتُطبع مرة واحدة في مخرجات الأمر.
 * قائمة الحسابات (البريد + الدور فقط، بدون كلمات مرور) تُكتب في storage/app/private/demo-accounts.txt
 */
class UserSeeder extends Seeder
{
    /** [local-part, الاسم, الدور, المركز] */
    public const STAFF = [
        ['super.admin', 'مدير النظام', 'super_admin', 'كفر الشيخ'],
        ['admin', 'مسؤول البوابة', 'Admin', 'كفر الشيخ'],
        // لوحة الإدارة
        ['media', 'مسئول الإعلام', 'مسئول اعلام', 'كفر الشيخ'],
        ['investment', 'مسئول الاستثمار', 'مسئول استثمار ', 'كفر الشيخ'],
        ['tourism', 'مسئول السياحة', 'مسئول سياحة', 'كفر الشيخ'],
        ['tech.center', 'مسئول المركز التكنولوجي', 'مسئول المركز التكنولوجي', 'كفر الشيخ'],
        ['planning', 'مسئول التخطيط الاستراتيجي', 'مسئول التخطيط الاستراتيجي', 'كفر الشيخ'],
        ['statistics', 'إدارة الإحصاء والنشر الإلكتروني', 'إدارة الاحصاء والتقارير والنشر الالكترونى', 'كفر الشيخ'],
        ['ads', 'إدارة الإعلانات', 'إدارة الاعلانات', 'كفر الشيخ'],
        ['finance', 'الإدارة المالية', 'الإدارة المالية', 'كفر الشيخ'],
        ['viewer', 'مستعرض بيانات', 'مستعرض بيانات', 'كفر الشيخ'],
        // لوحة GIS
        ['gis.center.manager', 'مدير مركز كفر الشيخ', 'مدير المركز', 'كفر الشيخ'],
        ['gis.city.head', 'رئيس مركز ومدينة دسوق', 'رئيس المركز والمدينة', 'دسوق'],
        ['gis.eng.director', 'مدير الإدارة الهندسية', 'مدير الادارة الهندسية', 'كفر الشيخ'],
        ['gis.dept.head', 'رئيس قسم التنظيم', 'رؤوساء الاقسام', 'كفر الشيخ'],
        ['gis.org.manager', 'مدير التنظيم', 'مدير التنظيم', 'كفر الشيخ'],
        ['gis.org.engineer', 'مهندس التنظيم', 'مهندس التنظيم', 'كفر الشيخ'],
        ['gis.org.tech', 'فني التنظيم', 'فني التنظيم', 'كفر الشيخ'],
        ['gis.field', 'العضو الميداني', 'العضو الميداني', 'كفر الشيخ'],
        ['gis.unit.manager', 'مدير الوحدة الفرعية', 'مدير الوحدة الفرعية', 'كفر الشيخ'],
        ['gis.unit.entry', 'مدخل بيانات الوحدة الفرعية', 'مدخل البيانات بالوحدة الفرعية', 'كفر الشيخ'],
        ['gis.analyst', 'محلل النظم', 'محللي النظم', 'كفر الشيخ'],
        ['gis.systems', 'أخصائي النظم', 'أخصائي النظم', 'كفر الشيخ'],
        ['gis.support', 'الدعم الإداري', 'الدعم الاداري', 'كفر الشيخ'],
        ['gis.changes.manager', 'مدير المتغيرات', 'مدير المتغيرات', 'كفر الشيخ'],
        ['gis.changes.member', 'عضو المتغيرات', 'عضو المتغيرات', 'كفر الشيخ'],
        ['gis.governor.office', 'مكتب المحافظ', 'مكتب المحافظ', 'كفر الشيخ'],
        ['gis.deputy', 'النائب المختص بمتابعة المنظومة', 'النائب المختص بمتابعة المنظومة طبقا لكشف الحضور', 'كفر الشيخ'],
        // استدامة
        ['estidama.admin', 'مدير منصة استدامة', 'estidama_admin', 'كفر الشيخ'],
        ['estidama.trainer', 'مدرب استدامة', 'estidama_trainer', 'كفر الشيخ'],
        // نقابة المهندسين / مجلس المدينة (Modules\Syndicate)
        ['syndicate', 'نقابة المهندسين - فرعية كفر الشيخ', 'نقابة المهندسين', 'كفر الشيخ'],
        ['council', 'مجلس مدينة كفر الشيخ', 'مجلس المدينة', 'كفر الشيخ'],
    ];

    public const CITIZEN_COUNT = 50;

    public function run(): void
    {
        DemoData::seed(1001);
        $password = DemoData::password();
        $lines = [];

        foreach (self::STAFF as [$local, $name, $roleName, $center]) {
            $role = Role::findOrCreate($roleName, 'web');
            $user = $this->upsert($local, $name, $password, [
                'job_title' => trim($roleName),
                'center' => $center,
                'status' => 'approved',
            ]);
            $user->assignRole($role);
            $lines[] = sprintf('%-40s  %-10s  %s', DemoData::email($local), 'staff', trim($roleName));
        }

        for ($i = 1; $i <= self::CITIZEN_COUNT; $i++) {
            $female = DemoData::chance(45);
            $center = DemoData::pick(DemoData::centerNames());
            $this->upsert(sprintf('citizen%02d', $i), DemoData::name($female), $password, [
                'center' => $center,
                'address' => $center . ' - ' . DemoData::pick(DemoData::VILLAGES[$center]),
                'job_title' => DemoData::pick(['موظف', 'مهندس', 'معلم', 'محاسب', 'طبيب', 'صاحب عمل حر', 'طالب', 'محامٍ']),
                'status' => $i % 17 === 0 ? 'pending' : 'approved',
            ], $female);
            $lines[] = sprintf('%-40s  %-10s  %s', DemoData::email(sprintf('citizen%02d', $i)), 'citizen', 'مواطن');
        }

        $this->writeAccountsFile($lines);

        $this->command?->info('Demo accounts: ' . count($lines) . ' (list: storage/app/private/demo-accounts.txt)');
        if (DemoData::passwordWasGenerated()) {
            $this->command?->warn('DEMO_PASSWORD غير مضبوط — كلمة مرور مولدة لهذا التشغيل لكل الحسابات التجريبية: ' . $password);
        } else {
            $this->command?->info('كلمة مرور الحسابات التجريبية مأخوذة من DEMO_PASSWORD.');
        }
    }

    private function upsert(string $local, string $name, string $password, array $extra, ?bool $female = null): User
    {
        $email = DemoData::email($local);
        $user = User::where('email', $email)->first() ?? new User(['email' => $email]);
        $user->fill(array_merge([
            'name' => $name,
            'phone' => $user->phone ?: DemoData::phone(),
            'national_id' => $user->national_id ?: DemoData::nationalId($female),
            'password' => $password,
        ], $extra));
        $user->email = $email;
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }

    private function writeAccountsFile(array $lines): void
    {
        $path = storage_path('app/private/demo-accounts.txt');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        $header = [
            'KFS demo accounts — generated ' . now()->toDateTimeString() . ' (' . app()->environment() . ', DB: ' . config('database.connections.' . config('database.default') . '.database') . ')',
            'Password: value of DEMO_PASSWORD in .env, or the one printed by `php artisan db:seed` output (not stored here).',
            str_repeat('-', 90),
        ];
        file_put_contents($path, implode(PHP_EOL, array_merge($header, $lines)) . PHP_EOL);
    }
}
