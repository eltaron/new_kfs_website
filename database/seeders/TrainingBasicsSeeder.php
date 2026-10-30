<?php

namespace Database\Seeders;

use Modules\Estidama\Models\EstidamaEvent;
use Modules\Estidama\Models\TrainingApplication;
use Modules\Estidama\Models\TrainingCenter;
use Modules\Estidama\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

/**
 * أساسيات منصة استدامة: مراكز التدريب، برامج (مجانية/مدفوعة)، طلبات الالتحاق، فعاليات.
 * التفاصيل الكاملة (الوحدات، الدروس، الاختبارات، الشهادات) يملكها LmsDemoSeeder.
 */
class TrainingBasicsSeeder extends Seeder
{
    public function run(): void
    {
        DemoData::seed(1111);

        $centers = [];
        foreach ([['مركز استدامة الرئيسي - ديوان عام المحافظة', 'كفر الشيخ - شارع الجيش'], ['مركز التدريب التكنولوجي بدسوق', 'دسوق - مجلس المدينة'], ['قاعة التدريب ببلطيم', 'بلطيم - الوحدة المحلية']] as [$name, $address]) {
            $centers[] = TrainingCenter::updateOrCreate(['name' => $name], ['address' => $address]);
        }

        $trainer = User::where('email', DemoData::email('estidama.trainer'))->value('id');
        $programs = [
            ['التحول الرقمي في الخدمات الحكومية', false, 0, 'open', 'beginner'],
            ['أساسيات نظم المعلومات الجغرافية GIS', true, 450, 'open', 'intermediate'],
            ['إدارة المشروعات الاحترافية', true, 600, 'ongoing', 'advanced'],
            ['مهارات خدمة المواطنين والتواصل الفعال', false, 0, 'ongoing', 'beginner'],
            ['ريادة الأعمال للشباب', true, 300, 'closed', 'beginner'],
        ];
        $created = [];
        foreach ($programs as $i => [$title, $paid, $price, $status, $level]) {
            $start = now()->addDays(($i - 2) * 20)->startOfDay();
            $created[] = TrainingProgram::updateOrCreate(['title' => $title], [
                'training_center_id' => $centers[$i % count($centers)]->id,
                'instructor_id' => $trainer,
                'description' => "برنامج تدريبي ضمن منصة استدامة: {$title}. يشمل محاضرات حضورية ومحتوى إلكترونياً واختباراً نهائياً.",
                'image' => DemoData::image('training', 'program-' . ($i + 1)),
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays(30)->toDateString(),
                'status' => $status,
                'is_paid' => $paid,
                'price' => $price,
                'requires_application' => $i % 2 === 0,
                'certificate_enabled' => true,
                'level' => $level,
                'estimated_minutes' => 600 + $i * 120,
            ]);
        }

        $citizens = User::where('email', 'like', 'citizen%@demo.kfs.invalid')->take(20)->get();
        $reviewer = User::where('email', DemoData::email('estidama.admin'))->value('id');
        foreach ($citizens as $i => $c) {
            $status = ['pending', 'approved', 'approved', 'rejected'][$i % 4];
            TrainingApplication::updateOrCreate(['national_id' => $c->national_id], [
                'training_program_id' => $created[$i % count($created)]->id,
                'user_id' => $c->id,
                'applicant_email' => $c->email,
                'applicant_name' => $c->name,
                'phone' => $c->phone,
                'gender' => DemoData::pick(['ذكر', 'أنثى']),
                'educational_qualification' => DemoData::pick(['بكالوريوس', 'ليسانس', 'دبلوم فني', 'ماجستير']),
                'specialization' => DemoData::pick(['تجارة', 'هندسة', 'حاسبات ومعلومات', 'آداب']),
                'highest_degree' => 'بكالوريوس',
                'employment_status' => DemoData::pick(['موظف حكومي', 'قطاع خاص', 'لا يعمل']),
                'current_position' => DemoData::pick(['أخصائي', 'محاسب', 'مهندس', 'باحث']),
                'job_address' => 'كفر الشيخ',
                'national_id_front_image' => DemoData::image('training', 'id-front', 800, 500, 'NATIONAL ID FRONT (DEMO)'),
                'national_id_back_image' => DemoData::image('training', 'id-back', 800, 500, 'NATIONAL ID BACK (DEMO)'),
                'personal_statement' => 'أرغب في تطوير مهاراتي المهنية.',
                'has_taken_previous_courses' => $i % 3 === 0,
                'previous_courses_names' => $i % 3 === 0 ? 'ICDL' : null,
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? 'اكتمل العدد المطلوب للدفعة.' : null,
                'reviewed_at' => $status === 'pending' ? null : now()->subDays(mt_rand(1, 20)),
                'reviewed_by' => $status === 'pending' ? null : $reviewer,
            ]);
        }

        foreach (['حفل تخريج الدفعة الأولى من برنامج التحول الرقمي', 'ورشة عمل نظم المعلومات الجغرافية', 'ملتقى ريادة الأعمال للشباب', 'توقيع بروتوكول تعاون مع جامعة كفر الشيخ'] as $i => $t) {
            EstidamaEvent::updateOrCreate(['title' => $t], ['image' => DemoData::image('estidama-events', 'event-' . ($i + 1))]);
        }
    }
}
