<?php

namespace Database\Seeders;

use Modules\Content\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const CATEGORIES = [
        'news' => ['أخبار المحافظة', 'آخر أخبار ديوان عام المحافظة والمراكز والمدن.'],
        'events' => ['فعاليات ومناسبات', 'الفعاليات والاحتفالات والمؤتمرات داخل المحافظة.'],
        'achievements' => ['إنجازات الدولة', 'المشروعات القومية والإنجازات التنموية بالمحافظة.'],
        'decisions' => ['قرارات المحافظ', 'القرارات والتوجيهات الرسمية.'],
        'health' => ['الصحة', 'أخبار القطاع الصحي والقوافل الطبية.'],
        'education' => ['التعليم', 'أخبار المدارس والجامعة ونتائج الامتحانات.'],
        'agriculture' => ['الزراعة والثروة السمكية', 'أخبار الزراعة والاستزراع السمكي وبحيرة البرلس.'],
        'investment-news' => ['الاستثمار', 'أخبار المناطق الصناعية والفرص الاستثمارية.'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $slug => [$name, $desc]) {
            // Category::creating يفرض slug من الاسم، والصفحة الرئيسية تعتمد slugs ثابتة (events / achievements)،
            // لذلك نبحث بالـ slug أو الاسم ثم نثبت الـ slug بعد الإنشاء.
            $category = Category::where('slug', $slug)->first()
                ?? Category::where('name', $name)->first()
                ?? Category::create(['name' => $name, 'description' => $desc]);
            $category->forceFill(['slug' => $slug, 'name' => $name, 'description' => $desc])->save();
        }
    }
}
