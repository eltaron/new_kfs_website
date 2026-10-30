<?php

namespace Tests\Feature\Lms;

use Modules\Estidama\Models\LessonProgress;
use App\Services\Lms\ProgressService;

class LessonProgressTest extends LmsTestCase
{
    public function test_guest_and_non_enrolled_users_cannot_open_the_player(): void
    {
        $program = $this->makeProgram();
        $lesson = $this->makeLesson($this->makeModule($program));

        $this->get(route('lms.lesson', [$program, $lesson]))->assertRedirect(route('login'));

        $this->actingAs($this->makeUser())
            ->get(route('lms.lesson', [$program, $lesson]))
            ->assertRedirect(route('estidama.program.show', $program));
    }

    public function test_program_page_and_player_render(): void
    {
        $program = $this->makeProgram();
        $module = $this->makeModule($program);
        $lesson = $this->makeLesson($module, ['title' => 'مقدمة في الاستدامة']);
        $this->makeLesson($module, ['type' => 'video', 'video_provider' => 'youtube', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $user = $this->makeUser();

        $this->get(route('estidama.program.show', $program))->assertOk()->assertSee($program->title)->assertSee('مقدمة في الاستدامة');

        $this->enroll($user, $program);
        $this->actingAs($user)->get(route('lms.course', $program))->assertRedirect(route('lms.lesson', [$program, $lesson]));
        $this->actingAs($user)->get(route('lms.lesson', [$program, $lesson]))
            ->assertOk()
            ->assertSee('مقدمة في الاستدامة')
            ->assertSee('تحديد كمكتمل');
        $this->actingAs($user)->get(route('lms.dashboard'))->assertOk()->assertSee($program->title);
    }

    public function test_marking_lessons_complete_updates_progress_and_resume_point(): void
    {
        $program = $this->makeProgram();
        $module = $this->makeModule($program);
        $first = $this->makeLesson($module);
        $second = $this->makeLesson($module);
        $this->makeLesson($module, ['is_required' => false]); // optional lesson does not count
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);

        $this->actingAs($user)
            ->post(route('lms.lesson.complete', [$program, $first]))
            ->assertRedirect(route('lms.lesson', [$program, $second]));

        $enrollment->refresh();
        $this->assertSame(50, $enrollment->progress_percent);
        $this->assertNotNull(LessonProgress::where('enrollment_id', $enrollment->id)->where('program_lesson_id', $first->id)->value('completed_at'));

        // Resume goes to the first unfinished lesson.
        $this->assertSame($second->id, app(ProgressService::class)->resumeLesson($enrollment)->id);
    }

    public function test_sequential_programs_lock_later_lessons(): void
    {
        $program = $this->makeProgram(['sequential' => true]);
        $module = $this->makeModule($program);
        $first = $this->makeLesson($module);
        $second = $this->makeLesson($module);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);

        $states = app(ProgressService::class)->lessonStates($enrollment);
        $this->assertFalse($states[$first->id]['locked']);
        $this->assertTrue($states[$second->id]['locked']);

        $this->actingAs($user)->get(route('lms.lesson', [$program, $second]))
            ->assertRedirect(route('lms.lesson', [$program, $first]));
        $this->actingAs($user)->post(route('lms.lesson.complete', [$program, $second]))->assertSessionHas('error');

        app(ProgressService::class)->markComplete($enrollment, $first);
        $this->actingAs($user)->get(route('lms.lesson', [$program, $second]))->assertOk();
    }

    public function test_prerequisites_and_drip_schedule_lock_lessons(): void
    {
        $program = $this->makeProgram();
        $module = $this->makeModule($program);
        $base = $this->makeLesson($module);
        $dependent = $this->makeLesson($module, ['prerequisite_lesson_id' => $base->id]);
        $later = $this->makeLesson($this->makeModule($program, ['unlock_after_days' => 7]));
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);

        $states = app(ProgressService::class)->lessonStates($enrollment);
        $this->assertTrue($states[$dependent->id]['locked']);
        $this->assertStringContainsString($base->title, $states[$dependent->id]['reason']);
        $this->assertTrue($states[$later->id]['locked']);

        $enrollment->forceFill(['enrolled_at' => now()->subDays(8)])->save();
        $states = app(ProgressService::class)->lessonStates($enrollment->fresh());
        $this->assertFalse($states[$later->id]['locked']);
    }

    public function test_video_position_and_notes_are_saved(): void
    {
        $program = $this->makeProgram();
        $lesson = $this->makeLesson($this->makeModule($program), ['type' => 'video', 'video_provider' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $user = $this->makeUser();
        $enrollment = $this->enroll($user, $program);

        $this->actingAs($user)
            ->postJson(route('lms.lesson.progress', [$program, $lesson]), ['position' => 125, 'notes' => 'ملاحظة مهمة'])
            ->assertOk()->assertJson(['saved' => true, 'position' => 125]);

        $progress = LessonProgress::where('enrollment_id', $enrollment->id)->first();
        $this->assertSame(125, $progress->last_position_seconds);
        $this->assertSame('ملاحظة مهمة', $progress->notes);
        $this->assertSame($lesson->id, $enrollment->fresh()->last_lesson_id);
    }

    public function test_lesson_media_is_only_served_to_enrolled_learners(): void
    {
        \Illuminate\Support\Facades\Storage::disk('local')->put('lms/lessons/test-file.pdf', '%PDF-1.4 test');
        $program = $this->makeProgram();
        $lesson = $this->makeLesson($this->makeModule($program), ['type' => 'pdf', 'file_path' => 'lms/lessons/test-file.pdf']);
        $learner = $this->makeUser();
        $this->enroll($learner, $program);

        try {
            $this->actingAs($this->makeUser())->get(route('lms.lesson.media', [$lesson, 'file']))->assertForbidden();
            $this->actingAs($learner)->get(route('lms.lesson.media', [$lesson, 'file']))->assertOk();
        } finally {
            \Illuminate\Support\Facades\Storage::disk('local')->delete('lms/lessons/test-file.pdf');
        }
    }
}
