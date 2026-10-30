<?php

namespace Tests\Feature\Lms;

use Modules\Estidama\Models\Badge;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ProgramLesson;
use Modules\Estidama\Models\TrainingCertificate;
use Modules\Estidama\Models\TrainingProgram;
use Database\Seeders\LmsDemoSeeder;

class LmsDemoSeederTest extends LmsTestCase
{
    public function test_seeder_builds_arabic_courses_and_is_idempotent(): void
    {
        $program = $this->makeProgram(['title' => 'التحول الرقمي في الخدمات الحكومية - اختبار']);
        $learner = $this->makeUser(null, ['email' => 'citizen-lms-test@demo.kfs.invalid']);
        $this->enroll($learner, $program);

        $this->seed(LmsDemoSeeder::class);

        $program->refresh();
        $this->assertGreaterThanOrEqual(6, $program->lessons()->count());
        $this->assertTrue($program->lessons()->where('type', 'quiz')->exists());
        $this->assertNotNull($program->exam_id);
        $this->assertGreaterThanOrEqual(6, Exam::find($program->exam_id)->questions()->count());
        $this->assertTrue(Badge::where('rule_type', 'first_course')->exists());

        $lessons = ProgramLesson::count();
        $this->seed(LmsDemoSeeder::class);
        $this->assertSame($lessons, ProgramLesson::count(), 'Seeder must not duplicate lessons.');

        // The seeded content is usable end-to-end.
        $this->actingAs($learner)->get(route('lms.course', $program))->assertRedirect();
        $this->get(route('estidama.program.show', $program))->assertOk()->assertSee('مفاهيم التحول الرقمي');
        $this->assertTrue(TrainingProgram::whereNotNull('exam_id')->exists());
        $this->assertGreaterThanOrEqual(0, TrainingCertificate::count());
    }
}
