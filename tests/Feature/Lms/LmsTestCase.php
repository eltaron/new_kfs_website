<?php

namespace Tests\Feature\Lms;

use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ExamQuestion;
use Modules\Estidama\Models\ProgramLesson;
use Modules\Estidama\Models\ProgramModule;
use Modules\Estidama\Models\TrainingCenter;
use Modules\Estidama\Models\TrainingProgram;
use App\Models\User;
use App\Services\Lms\EnrollmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Fixtures for the Estidama LMS feature tests. */
abstract class LmsTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.efinance.driver' => 'fake', 'services.efinance.sender_id' => 'TEST-SENDER']);
        config(['security.two_factor.require_for_staff' => false]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeUser(?string $role = null, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'national_id' => (string) random_int(10000000000000, 29999999999999),
            'status' => 'approved',
        ], $attributes));

        if ($role) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $user->fresh();
    }

    protected function makeProgram(array $attributes = []): TrainingProgram
    {
        $center = TrainingCenter::create(['name' => 'مركز اختبار']);

        return TrainingProgram::create(array_merge([
            'training_center_id' => $center->id,
            'title' => 'برنامج اختبار ' . uniqid(),
            'description' => '<p>وصف البرنامج</p>',
            'image' => 'programs/test.jpg',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'open',
            'requires_application' => false,
            'is_paid' => false,
            'price' => 0,
            'sequential' => false,
            'certificate_enabled' => true,
        ], $attributes));
    }

    protected function makeModule(TrainingProgram $program, array $attributes = []): ProgramModule
    {
        return ProgramModule::create(array_merge([
            'training_program_id' => $program->id,
            'title' => 'وحدة',
            'type' => 'video',
            'order' => (int) ProgramModule::where('training_program_id', $program->id)->max('order') + 1,
            'is_published' => true,
        ], $attributes));
    }

    protected function makeLesson(ProgramModule $module, array $attributes = []): ProgramLesson
    {
        return ProgramLesson::create(array_merge([
            'program_module_id' => $module->id,
            'title' => 'درس ' . uniqid(),
            'type' => 'text',
            'body' => '<p>محتوى الدرس</p>',
            'sort_order' => (int) ProgramLesson::where('program_module_id', $module->id)->max('sort_order') + 1,
            'is_published' => true,
            'is_required' => true,
        ], $attributes));
    }

    /** Exam with $count MCQ questions whose correct answer is "صحيح". */
    protected function makeExam(?TrainingProgram $program, int $count = 4, array $attributes = []): Exam
    {
        $exam = Exam::create(array_merge([
            'training_program_id' => $program?->id,
            'title' => 'امتحان اختبار',
            'kind' => 'final',
            'duration_minutes' => 10,
            'passing_score' => 50,
            'allow_review' => true,
        ], $attributes));

        for ($i = 1; $i <= $count; $i++) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'question_type' => 'mcq',
                'question_text' => '<p>سؤال ' . $i . '</p>',
                'options' => [['option_text' => 'صحيح'], ['option_text' => 'خطأ ' . $i], ['option_text' => 'آخر ' . $i]],
                'correct_answer' => 'صحيح',
                'points' => 1,
                'sort_order' => $i,
            ]);
        }

        return $exam;
    }

    protected function enroll(User $user, TrainingProgram $program): Enrollment
    {
        return app(EnrollmentService::class)->enrollDirect($user, $program);
    }

    /** Answers for all questions of an attempt: $correct of them right. */
    protected function answersFor(Exam $exam, int $correct): array
    {
        $answers = [];
        foreach ($exam->questions()->where('question_type', '!=', 'essay')->get()->values() as $i => $q) {
            $answers[$q->id] = $i < $correct ? 'صحيح' : $q->optionTexts()[1];
        }

        return $answers;
    }
}
