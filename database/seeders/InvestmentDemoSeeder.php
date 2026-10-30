<?php

namespace Database\Seeders;

use Modules\Investment\Models\HayahKarimaProject;
use Modules\Investment\Models\Investment;
use Modules\Investment\Models\InvestmentMessage;
use Modules\Investment\Models\InvestmentPlan;
use Modules\Investment\Models\Project;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * الاستثمار: الفرص (من InvestmentProjectSeeder) + المشروعات القومية وحياة كريمة + الخطط + رسائل المستثمرين.
 */
class InvestmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        DemoData::seed(4004);
        $this->call(InvestmentProjectSeeder::class);

        // صور/أوصاف للفرص والمشروعات التي أُنشئت بصور غير موجودة
        foreach (Investment::orderBy('created_at')->get() as $i => $inv) {
            $updates = ['order' => $inv->order ?: $i + 1, 'type' => $inv->type ?? ($i % 2 ? 2 : 1)];
            if (! $inv->thumbnail || ! Storage::disk('public')->exists($inv->thumbnail)) {
                $updates['thumbnail'] = DemoData::image('investments', 'investment-' . ($i + 1));
            }
            if (! $inv->description) {
                $updates['description'] = '<p>فرص استثمارية واعدة بمحافظة كفر الشيخ مع حوافز وتسهيلات للمستثمرين.</p>';
            }
            $inv->update($updates);
        }

        foreach (Project::whereNotNull('investment_id')->orderBy('name')->get() as $i => $project) {
            $updates = ['category' => $project->category ?: 'استثماري'];
            if (! $project->thumbnail || ! Storage::disk('public')->exists($project->thumbnail)) {
                $updates['thumbnail'] = DemoData::image('projects', 'investment-project-' . ($i + 1));
            }
            if (! $project->description) {
                $center = DemoData::pick(DemoData::centerNames());
                [$lat, $lng] = DemoData::coords($center);
                $updates += [
                    'description' => "<p>فرصة استثمارية بمركز {$center} — تتوفر المرافق الأساسية والطرق، ويمكن التقدم بطلب عبر بوابة الاستثمار.</p>",
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
            }
            $project->update($updates);
        }

        $this->nationalProjects();
        $this->hayahKarima();
        $this->plans();
        $this->messages();
    }

    private function nationalProjects(): void
    {
        $rows = [
            ['قومي', 'محور كفر الشيخ - بلطيم الحر', 'مشروع طريق حر يربط عاصمة المحافظة بالساحل الشمالي.'],
            ['قومي', 'مشروع الاستزراع السمكي ببركة غليون', 'أكبر مشروعات الاستزراع السمكي في الشرق الأوسط على مساحة 4 آلاف فدان.'],
            ['قومي', 'محطة معالجة صرف صحي دسوق', 'محطة بطاقة 60 ألف م³/يوم لخدمة مدينة دسوق وقراها.'],
            ['قومي', 'تطوير ميناء البرلس للصيد', 'رفع كفاءة الميناء وإنشاء أرصفة جديدة وثلاجات حفظ.'],
            ['قومي', 'المنطقة الصناعية بمطوبس', 'منطقة صناعية مرفقة لجذب الصناعات الغذائية والتحويلية.'],
            ['حياة كريمة', 'تطوير قرى مركز الحامول', 'مياه شرب وصرف صحي وطرق ووحدات صحية ضمن المرحلة الأولى.'],
            ['حياة كريمة', 'تطوير قرى مركز سيدي سالم', 'مجمعات خدمات حكومية ومدارس ومراكز شباب.'],
            ['حياة كريمة', 'تطوير قرى مركز بيلا', 'تبطين الترع وتوصيل الغاز الطبيعي للمنازل.'],
        ];
        foreach ($rows as $i => [$cat, $name, $desc]) {
            $center = DemoData::pick(DemoData::centerNames());
            [$lat, $lng] = DemoData::coords($center);
            $project = Project::firstOrNew(['name' => $name]);
            $project->fill([
                'category' => $cat,
                'type' => $cat === 'حياة كريمة' && $i === 5 ? 'logo' : 'image',
                'thumbnail' => DemoData::image('projects', 'national-' . ($i + 1)),
                'description' => "<p>{$desc}</p>",
                'latitude' => $lat,
                'longitude' => $lng,
                'is_highlighted' => $i < 2,
            ]);
            $project->slug ??= 'demo-national-' . ($i + 1);
            $project->save();
        }
    }

    private function hayahKarima(): void
    {
        $rows = [
            ['مياه الشرب', 'fas fa-tint', 82], ['الصرف الصحي', 'fas fa-water', 64], ['الطرق والكباري', 'fas fa-road', 71],
            ['الصحة', 'fas fa-clinic-medical', 88], ['التعليم', 'fas fa-school', 76], ['الغاز الطبيعي', 'fas fa-fire', 55],
            ['الشباب والرياضة', 'fas fa-futbol', 69], ['الكهرباء', 'fas fa-bolt', 91],
        ];
        foreach ($rows as $i => [$name, $icon, $progress]) {
            HayahKarimaProject::updateOrCreate(['slug' => 'hayah-karima-' . ($i + 1)], [
                'sector_name' => $name,
                'icon' => $icon,
                'progress' => $progress,
                'image' => DemoData::image('hayah-karima', 'sector-' . ($i + 1)),
                'description' => "<p>تنفيذ مشروعات قطاع {$name} في قرى المرحلة الأولى من مبادرة حياة كريمة بمراكز الحامول وسيدي سالم وبيلا وقلين، بنسبة إنجاز {$progress}%.</p>",
            ]);
        }
    }

    private function plans(): void
    {
        foreach (['2023/2024', '2024/2025', '2025/2026', '2026/2027'] as $i => $range) {
            InvestmentPlan::updateOrCreate(['year_range' => $range], [
                'file_path' => DemoData::pdf('investment-plans', 'plan-' . ($i + 1)),
                'final_file_path' => $i < 3 ? DemoData::pdf('investment-plans', 'plan-final-' . ($i + 1)) : null,
            ]);
        }
    }

    private function messages(): void
    {
        if (InvestmentMessage::count() >= 12) {
            return;
        }
        $subjects = ['الاستفسار عن أرض بالمنطقة الصناعية', 'طلب تخصيص قطعة أرض لمصنع أعلاف', 'فرصة الفندق السياحي ببلطيم', 'شروط الاستثمار في الاستزراع السمكي', 'مشروع مول تجاري بدسوق', 'الحوافز الاستثمارية المتاحة'];
        for ($i = 0; $i < 12; $i++) {
            InvestmentMessage::create([
                'name' => DemoData::name(false),
                'email' => 'investor' . ($i + 1) . '@example.com',
                'phone' => DemoData::phone(),
                'company_name' => DemoData::pick(['شركة الدلتا للاستثمار', 'مجموعة النيل الصناعية', 'شركة البرلس للأسماك', null, 'الشركة المصرية للأغذية']),
                'subject' => DemoData::pick($subjects),
                'message' => 'نرغب في الحصول على تفاصيل الفرصة الاستثمارية والإجراءات المطلوبة ومواعيد المعاينة.',
                'status' => ['new', 'read', 'replied'][$i % 3],
                'created_at' => DemoData::pastDate(90),
            ]);
        }
    }
}
