<?php

namespace Database\Seeders;

use Modules\Investment\Models\CenterGuide;
use Modules\Citizen\Models\Complaint;
use Modules\Citizen\Models\ContactMessage;
use Modules\Citizen\Models\EmergencyReport;
use Modules\Content\Models\Employee;
use Modules\Content\Models\ExamResult;
use Modules\Citizen\Models\ServiceSurvey;
use Modules\Citizen\Models\Suggestion;
use App\Models\User;
use Database\Factories\ComplaintFactory;
use Database\Factories\ContactMessageFactory;
use Database\Factories\EmergencyReportFactory;
use Database\Factories\EmployeeFactory;
use Database\Factories\ServiceSurveyFactory;
use Database\Factories\SuggestionFactory;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * تواصل المواطنين: شكاوى، مقترحات، بلاغات طوارئ، رسائل تواصل، استبيانات رضا،
 * + الموظفون، دليل المراكز (center_guides)، نتائج الامتحانات، طلبات تعديل المخطط التفصيلي.
 *
 * آمن للتكرار: كل جدول يُكمَّل حتى العدد المستهدف فقط.
 */
class CitizenRequestsSeeder extends Seeder
{
    public function run(): void
    {
        DemoData::seed(7007);

        $this->topUp(Complaint::class, 30, fn ($n) => ComplaintFactory::new()->count($n)->create());
        $this->topUp(Suggestion::class, 20, fn ($n) => SuggestionFactory::new()->count($n)->create());
        $this->topUp(ContactMessage::class, 20, fn ($n) => ContactMessageFactory::new()->count($n)->create());
        $this->topUp(EmergencyReport::class, 18, fn ($n) => EmergencyReportFactory::new()->count($n)->create());
        $this->topUp(ServiceSurvey::class, 60, fn ($n) => ServiceSurveyFactory::new()->count($n)->create());

        // ربط بعض الشكاوى/المقترحات بالمواطنين التجريبيين (العلاقة عبر national_id)
        $citizens = User::where('email', 'like', 'citizen%@demo.kfs.invalid')->take(10)->get();
        foreach (Complaint::orderBy('id')->take(10)->get() as $i => $complaint) {
            if (isset($citizens[$i])) {
                $complaint->update(['national_id' => $citizens[$i]->national_id, 'name' => $citizens[$i]->name, 'phone' => $citizens[$i]->phone]);
            }
        }
        foreach (Suggestion::orderBy('id')->take(5)->get() as $i => $s) {
            if (isset($citizens[$i])) {
                $s->update(['national_id' => $citizens[$i]->national_id, 'name' => $citizens[$i]->name]);
            }
        }

        // الموظفون: بعض المواطنين التجريبيين موظفون (isEmployee) + موظفون آخرون
        foreach ($citizens->take(5) as $c) {
            Employee::updateOrCreate(['national_id' => $c->national_id], ['name' => $c->name]);
        }
        $this->topUp(Employee::class, 25, fn ($n) => EmployeeFactory::new()->count($n)->create());

        $this->centerGuides();
        $this->examResults();
        $this->planModifications($citizens);
    }

    private function topUp(string $model, int $target, callable $make): void
    {
        $missing = $target - $model::count();
        if ($missing > 0) {
            $make($missing);
        }
    }

    private function centerGuides(): void
    {
        foreach (array_slice(DemoData::centerNames(), 0, 8) as $i => $center) {
            $pop = mt_rand(250000, 700000);
            CenterGuide::updateOrCreate(['city' => $center], [
                'main_activity' => DemoData::pick(['الزراعة', 'الصيد والاستزراع السمكي', 'التجارة', 'الصناعات الغذائية']),
                'other_activities' => 'الحرف اليدوية، تربية الماشية، التجارة',
                'head' => 'رئيس مركز ومدينة ' . $center,
                'contact_person' => DemoData::name(false),
                'phone' => DemoData::phone(),
                'projects_count' => mt_rand(10, 60),
                'investment_size' => mt_rand(50, 900) . ' مليون جنيه',
                'permanent_workers' => mt_rand(500, 4000),
                'seasonal_workers' => mt_rand(200, 2000),
                'total_population' => $pop,
                'males_count' => (int) ($pop * 0.51),
                'females_count' => (int) ($pop * 0.49),
                'growth_rate' => '1.' . mt_rand(5, 9) . '%',
                'water_stations' => mt_rand(3, 15),
                'water_coverage' => mt_rand(85, 99) . '%',
                'sewage_coverage' => mt_rand(40, 85) . '%',
                'electricity_coverage' => '100%',
                'gas_coverage' => mt_rand(20, 70) . '%',
                'primary_schools' => mt_rand(40, 120),
                'prep_schools' => mt_rand(20, 60),
                'secondary_schools' => mt_rand(5, 20),
                'hospitals_count' => mt_rand(1, 4),
                'health_units' => mt_rand(10, 35),
                'youth_centers' => mt_rand(10, 40),
                'roads_total_km' => mt_rand(150, 600),
                'swot_strengths' => 'موقع متميز، أراضٍ زراعية خصبة، أيدٍ عاملة.',
                'swot_weaknesses' => 'نقص تغطية الصرف الصحي ببعض القرى.',
                'swot_opportunities' => 'الاستزراع السمكي والصناعات الغذائية.',
                'swot_threats' => 'التعديات على الأراضي الزراعية.',
                'attachments' => [],
                'status' => ['pending', 'reviewed', 'approved', 'rejected'][$i % 4],
                'admin_notes' => $i % 4 === 3 ? 'برجاء استكمال بيانات قطاع الصحة.' : null,
            ]);
        }
    }

    private function examResults(): void
    {
        if (ExamResult::count() >= 60) {
            return;
        }
        $subjects = ['اللغة العربية', 'اللغة الإنجليزية', 'الرياضيات', 'العلوم', 'الدراسات الاجتماعية'];
        for ($i = 1; $i <= 60; $i++) {
            $marks = [];
            foreach ($subjects as $s) {
                $marks[] = ['name' => $s, 'grade' => mt_rand(25, 60), 'max' => 60];
            }
            $total = array_sum(array_column($marks, 'grade'));
            $center = DemoData::pick(DemoData::centerNames());
            ExamResult::updateOrCreate(['seat_number' => (string) (150000 + $i)], [
                'student_name' => DemoData::name(),
                'school' => 'مدرسة ' . DemoData::pick(DemoData::VILLAGES[$center]) . ' الإعدادية',
                'adminstration' => 'إدارة ' . $center . ' التعليمية',
                'academic_year' => '2025/2026',
                'total_grade' => $total,
                'subjects' => $marks,
                'status' => $total >= 150 ? 'ناجح' : 'دور ثان',
                'notes' => null,
            ]);
        }
    }

    private function planModifications($citizens): void
    {
        if ($citizens->isEmpty() || DB::table('detailed_plan_modifications')->count() >= 6) {
            return;
        }
        foreach ($citizens->take(6) as $i => $c) {
            DB::table('detailed_plan_modifications')->insert([
                'user_id' => $c->id,
                'plan_number' => 'DP-' . (2024 + $i % 2) . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'plan_name' => 'المخطط التفصيلي لمدينة ' . DemoData::pick(DemoData::centerNames()),
                'location' => 'بلوك ' . mt_rand(1, 40),
                'modification_reason' => 'تعديل عرض شارع / تغيير استخدام قطعة من سكني إلى خدمي',
                'description' => 'طلب تجريبي',
                'attachments' => json_encode([]),
                'status' => ['pending', 'under_review', 'approved', 'rejected', 'pending', 'approved'][$i],
                'admin_notes' => null,
                'created_at' => DemoData::pastDate(60),
                'updated_at' => now(),
            ]);
        }
    }
}
