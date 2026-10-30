<?php

namespace Tests\Feature\Lms;

use Modules\Estidama\Models\ExamQuestion;
use Modules\Estidama\Models\ExamSubmission;
use App\Services\Lms\ExamService;

class ExamAttemptTest extends LmsTestCase
{
    public function test_final_exam_requires_completed_lessons(): void
    {
        $program = $this->makeProgram();
        $this->makeLesson($this->makeModule($program));
        $exam = $this->makeExam($program);
        $program->update(['exam_id' => $exam->id]);
        $user = $this->makeUser();
        $this->enroll($user, $program);

        $this->actingAs($user)->post(route('lms.exams.start', [$program, $exam]))
            ->assertRedirect(route('lms.course', $program));
        $this->assertSame(0, ExamSubmission::where('user_id', $user->id)->count());
    }

    public function test_attempt_is_timed_graded_and_reviewable(): void
    {
        $program = $this->makeProgram();
        $exam = $this->makeExam($program, 4, ['passing_score' => 75]);
        $program->update(['exam_id' => $exam->id]);
        $user = $this->makeUser();
        $this->enroll($user, $program);

        $this->actingAs($user)->get(route('lms.exams.show', [$program, $exam]))->assertOk()->assertSee('ابدأ الامتحان');
        $this->actingAs($user)->post(route('lms.exams.start', [$program, $exam]))->assertRedirect();

        $attempt = ExamSubmission::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(ExamSubmission::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNotNull($attempt->expires_at);
        $this->assertCount(4, $attempt->question_order);

        $this->actingAs($user)->get(route('lms.attempts.show', $attempt))->assertOk()->assertSee('تسليم الإجابات');

        // 3/4 correct = 75% => pass
        $this->actingAs($user)->post(route('lms.attempts.submit', $attempt), ['answers' => $this->answersFor($exam, 3)])
            ->assertRedirect(route('lms.attempts.review', $attempt));

        $attempt->refresh();
        $this->assertSame(ExamSubmission::STATUS_SUBMITTED, $attempt->status);
        $this->assertSame(75, $attempt->score);
        $this->assertTrue($attempt->passed);

        $this->actingAs($user)->get(route('lms.attempts.review', $attempt))->assertOk()->assertSee('مراجعة الإجابات');

        // Another user cannot see it.
        $this->actingAs($this->makeUser())->get(route('lms.attempts.review', $attempt))->assertForbidden();
    }

    public function test_attempt_limit_is_enforced(): void
    {
        $program = $this->makeProgram();
        $exam = $this->makeExam($program, 2, ['max_attempts' => 2, 'passing_score' => 100]);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);
        $service = app(ExamService::class);

        foreach ([1, 2] as $n) {
            $attempt = $service->start($exam, $user, $enrollment);
            $this->assertSame($n, $attempt->attempt_number);
            $service->submit($attempt, $this->answersFor($exam, 0));
        }

        $this->assertSame(0, $service->remainingAttempts($exam, $user));
        $this->assertNotNull($service->startBlocker($exam, $user));
        $this->expectException(\DomainException::class);
        $service->start($exam, $user, $enrollment);
    }

    public function test_late_submission_keeps_only_autosaved_answers(): void
    {
        $program = $this->makeProgram();
        $exam = $this->makeExam($program, 2);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);
        $service = app(ExamService::class);

        $attempt = $service->start($exam, $user, $enrollment);
        $service->saveAnswers($attempt, $this->answersFor($exam, 1)); // autosaved: 1 correct

        $this->travel(15)->minutes(); // timer (10 min) + grace exceeded
        $this->actingAs($user)->post(route('lms.attempts.submit', $attempt), ['answers' => $this->answersFor($exam, 2)]);

        $attempt->refresh();
        $this->assertSame(ExamSubmission::STATUS_SUBMITTED, $attempt->status);
        $this->assertSame(50, $attempt->score);
    }

    public function test_shuffled_attempts_keep_a_stable_order_and_reject_foreign_options(): void
    {
        $program = $this->makeProgram();
        $exam = $this->makeExam($program, 5, ['shuffle_questions' => true, 'shuffle_options' => true]);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);
        $service = app(ExamService::class);

        $attempt = $service->start($exam, $user, $enrollment);
        $ids = $exam->questions()->pluck('id')->sort()->values()->all();
        $order = collect($attempt->question_order)->sort()->values()->all();
        $this->assertSame($ids, $order);
        $this->assertSame($attempt->question_order, $service->questionsFor($attempt)->pluck('id')->all());

        $answers = $this->answersFor($exam, 5);
        $answers[$ids[0]] = 'إجابة مزيفة';
        $service->submit($attempt, $answers);
        $this->assertSame(80, $attempt->fresh()->score);
    }

    public function test_essay_questions_need_manual_grading(): void
    {
        $program = $this->makeProgram();
        $exam = $this->makeExam($program, 1, ['passing_score' => 60]);
        $essay = ExamQuestion::create([
            'exam_id' => $exam->id, 'question_type' => 'essay', 'question_text' => 'اشرح مفهوم الاستدامة',
            'options' => [], 'points' => 4, 'sort_order' => 9,
        ]);
        $user = $this->makeUser();
        $trainer = $this->makeUser('estidama_trainer');
        $enrollment = $this->enroll($user, $program);
        $service = app(ExamService::class);

        $attempt = $service->start($exam, $user, $enrollment);
        $service->submit($attempt, $this->answersFor($exam, 1) + [$essay->id => 'الاستدامة هي ...']);
        $attempt->refresh();
        $this->assertSame(ExamSubmission::STATUS_NEEDS_GRADING, $attempt->status);
        $this->assertFalse($attempt->passed);

        $service->gradeManually($attempt, [$essay->id => 3], 'إجابة جيدة', $trainer);
        $attempt->refresh();
        $this->assertSame(ExamSubmission::STATUS_SUBMITTED, $attempt->status);
        $this->assertSame(80, $attempt->score); // (1 + 3) / 5
        $this->assertTrue($attempt->passed);
        $this->assertSame((string) $trainer->id, (string) $attempt->graded_by);
    }

    public function test_passing_a_lesson_quiz_completes_the_lesson(): void
    {
        $program = $this->makeProgram();
        $quiz = $this->makeExam($program, 2, ['kind' => 'quiz']);
        $lesson = $this->makeLesson($this->makeModule($program), ['type' => 'quiz', 'exam_id' => $quiz->id, 'body' => null]);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);

        $this->actingAs($user)->get(route('lms.lesson', [$program, $lesson]))->assertOk()->assertSee('ابدأ الاختبار');
        $this->actingAs($user)->post(route('lms.exams.start', [$program, $quiz]), ['lesson' => $lesson->id])->assertRedirect();
        $attempt = ExamSubmission::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($lesson->id, $attempt->program_lesson_id);

        $this->actingAs($user)->post(route('lms.attempts.submit', $attempt), ['answers' => $this->answersFor($quiz, 2)]);

        $this->assertSame(100, $enrollment->fresh()->progress_percent);
    }
}
