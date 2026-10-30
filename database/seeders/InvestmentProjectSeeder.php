<?php

namespace Database\Seeders;

use Modules\Investment\Models\Investment;
use Modules\Investment\Models\Project;
use Illuminate\Database\Seeder;

class InvestmentProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define Investment Opportunities
        $healthcare = Investment::firstOrCreate(
            ['title' => 'قطاع الرعاية الصحية'],
            ['thumbnail' => 'investments/healthcare.jpg', 'description' => 'فرص استثمارية في تطوير وتشغيل المرافق الصحية.']
        );

        $hospitality = Investment::firstOrCreate(
            ['title' => 'قطاع الفنادق والضيافة'],
            ['thumbnail' => 'investments/hospitality.jpg', 'description' => 'مشاريع سياحية وفندقية واعدة.']
        );

        $services = Investment::firstOrCreate(
            ['title' => 'قطاع الخدمات المتنوعة'],
            ['thumbnail' => 'investments/services.jpg', 'description' => 'مشاريع خدمية متنوعة مثل محطات الوقود والمراكز التجارية.']
        );

        $realEstate = Investment::firstOrCreate(
            ['title' => 'قطاع التشييد والبناء والعقارات'],
            ['thumbnail' => 'investments/real_estate.jpg', 'description' => 'مشاريع سكنية وتجارية وإدارية متنوعة.']
        );

        $retail = Investment::firstOrCreate(
            ['title' => 'قطاع تجارة التجزئة'],
            ['thumbnail' => 'investments/retail.jpg', 'description' => 'فرص لإنشاء مراكز تجارية حديثة.']
        );

        $foodIndustry = Investment::firstOrCreate(
            ['title' => 'قطاع الصناعات الغذائية'],
            ['thumbnail' => 'investments/food_industry.jpg', 'description' => 'مشاريع صناعية في مجال الأغذية.']
        );


        // 2. Define and Link Projects to their Investment Opportunity
        $projects = [
            // Healthcare Projects
            ['name' => 'إدارة وتشغيل وتطوير مركز أورام كفر الشيخ', 'investment_id' => $healthcare->id],
            ['name' => 'إدارة وتشغيل وتطوير مستشفى مطوبس المركزي', 'investment_id' => $healthcare->id],

            // Hospitality Projects
            ['name' => 'إقامة فندق سياحي على مسار العائلة المقدسة بجوار الجامعة', 'investment_id' => $hospitality->id],

            // Services Projects
            ['name' => 'إقامة محطة وقود ومراكز خدمة سيارات بجوار كوبري دسوق العلوي', 'investment_id' => $services->id],

            // Real Estate Projects
            ['name' => 'إقامة مشروع تجاري - إداري - سكني (1)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع تجاري - إداري - سكني (2)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (1)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (10)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (2)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (3)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (4)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (5)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (6)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (7)', 'investment_id' => $realEstate->id],
            ['name' => 'إقامة مشروع سكني / تجاري / إداري على وقف قوله الخيري (9)', 'investment_id' => $realEstate->id],
            ['name' => 'اقامة مشروع سكني تجاري', 'investment_id' => $realEstate->id],
            ['name' => 'اقامة مول تجاري وسكني', 'investment_id' => $realEstate->id],
            ['name' => 'اقامة مشروع سكني - تجاري - خدمي على أرض 185 فدان', 'investment_id' => $realEstate->id],
            ['name' => 'اقامة مشروع سكني - تجاري على أرض الميكنة الزراعية في قلين', 'investment_id' => $realEstate->id],
            ['name' => 'اقامة مشروع سكني تجاري على أرض الميكنة الزراعية في دسوق', 'investment_id' => $realEstate->id],

            // Retail Projects
            ['name' => 'اقامة مركز تجاري (1)', 'investment_id' => $retail->id],
            ['name' => 'اقامة مركز تجاري (2)', 'investment_id' => $retail->id],
            ['name' => 'اقامة مركز تجاري (3)', 'investment_id' => $retail->id],
            ['name' => 'اقامة مركز تجاري (4)', 'investment_id' => $retail->id],

            // Food Industry Projects
            ['name' => 'مصنع النخيل للصناعات الغذائية للمشاركة او الايجار', 'investment_id' => $foodIndustry->id, 'is_highlighted' => true],
        ];

        foreach ($projects as $projectData) {
            Project::firstOrCreate(
                ['name' => $projectData['name'], 'investment_id' => $projectData['investment_id']],
                [
                    'type' => 'image',
                    'thumbnail' => 'projects/placeholder.jpg', // Default placeholder
                    'is_highlighted' => $projectData['is_highlighted'] ?? false
                ]
            );
        }
    }
}
