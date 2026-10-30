<?php

namespace Tests\Feature\Lms;

use Modules\Estidama\Models\Badge;
use Modules\Estidama\Models\TrainingCertificate;
use App\Services\Lms\ExamService;
use App\Services\Lms\ProgressService;

class CertificateIssuanceTest extends LmsTestCase
{
    public function test_completing_all_lessons_issues_a_certificate_when_there_is_no_exam(): void
    {
        $program = $this->makeProgram();
        $module = $this->makeModule($program);
        $a = $this->makeLesson($module);
        $b = $this->makeLesson($module);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);

        app(ProgressService::class)->markComplete($enrollment, $a);
        $this->assertNull($enrollment->fresh()->certificate);

        $this->actingAs($user)->post(route('lms.lesson.complete', [$program, $b]))->assertRedirect();

        $enrollment->refresh();
        $this->assertSame('completed', $enrollment->status);
        $this->assertNotNull($enrollment->completed_at);
        $certificate = $enrollment->certificate;
        $this->assertNotNull($certificate);
        $this->assertMatchesRegularExpression('/^KFS-EST-\d{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $certificate->serial);
        $this->assertSame($user->name, $certificate->holder_name);
    }

    public function test_certificate_requires_passing_the_final_exam(): void
    {
        $program = $this->makeProgram();
        $lesson = $this->makeLesson($this->makeModule($program));
        $exam = $this->makeExam($program, 4, ['passing_score' => 50]);
        $program->update(['exam_id' => $exam->id]);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);
        $exams = app(ExamService::class);

        app(ProgressService::class)->markComplete($enrollment, $lesson);
        $this->assertSame('approved', $enrollment->fresh()->status);

        $failed = $exams->start($exam, $user, $enrollment);
        $exams->submit($failed, $this->answersFor($exam, 1));
        $this->assertNull($enrollment->fresh()->certificate);

        $passed = $exams->start($exam, $user, $enrollment);
        $exams->submit($passed, $this->answersFor($exam, 4));

        $enrollment->refresh();
        $this->assertSame('completed', $enrollment->status);
        $this->assertSame(100, $enrollment->certificate->final_score);
        $this->assertSame($passed->id, $enrollment->certificate->exam_submission_id);
    }

    public function test_certificate_page_is_private_and_verification_is_public(): void
    {
        $program = $this->makeProgram();
        $lesson = $this->makeLesson($this->makeModule($program));
        $user = $this->makeUser(null, ['name' => 'أحمد محمد علي']);
        $enrollment = $this->enroll($user, $program);
        app(ProgressService::class)->markComplete($enrollment, $lesson);
        $certificate = $enrollment->fresh()->certificate;

        $this->actingAs($user)->get(route('lms.certificates.show', $certificate->serial))
            ->assertOk()->assertSee($certificate->serial)->assertSee('<svg', false);
        $this->actingAs($this->makeUser())->get(route('lms.certificates.show', $certificate->serial))->assertForbidden();
        $this->actingAs($this->makeUser('estidama_admin'))->get(route('lms.certificates.show', $certificate->serial))->assertOk();

        auth()->logout();
        $this->get(route('lms.certificates.verify', ['serial' => $certificate->serial]))
            ->assertOk()->assertSee('شهادة صحيحة وسارية')->assertSee('أحمد م. ع.')->assertDontSee('أحمد محمد علي');
        $this->get(route('lms.certificates.verify', ['serial' => 'KFS-EST-2000-AAAA-BBBB']))
            ->assertOk()->assertSee('لم يتم العثور على شهادة');

        $certificate->update(['revoked_at' => now(), 'revocation_reason' => 'اختبار']);
        $this->get(route('lms.certificates.verify', ['serial' => $certificate->serial]))->assertOk()->assertSee('هذه الشهادة ملغاة');
    }

    public function test_rule_based_badges_are_awarded_on_completion(): void
    {
        $first = Badge::create(['name' => 'أول برنامج ' . uniqid(), 'image' => 'badges/x.png', 'description' => 'أتم أول برنامج', 'rule_type' => 'first_course']);
        $lessons = Badge::create(['name' => 'مثابر ' . uniqid(), 'image' => 'badges/y.png', 'description' => 'أتم درسين', 'rule_type' => 'lessons_count', 'rule_value' => 2]);
        $never = Badge::create(['name' => 'خمسة برامج ' . uniqid(), 'image' => 'badges/z.png', 'description' => '-', 'rule_type' => 'programs_count', 'rule_value' => 5]);

        $program = $this->makeProgram();
        $module = $this->makeModule($program);
        $a = $this->makeLesson($module);
        $b = $this->makeLesson($module);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);

        app(ProgressService::class)->markComplete($enrollment, $a);
        app(ProgressService::class)->markComplete($enrollment, $b);

        $owned = $user->badges()->pluck('badges.id')->all();
        $this->assertContains($first->id, $owned);
        $this->assertContains($lessons->id, $owned);
        $this->assertNotContains($never->id, $owned);
        $this->assertSame(1, TrainingCertificate::where('user_id', $user->id)->count());
    }
}
