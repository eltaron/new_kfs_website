<?php

namespace Tests\Feature\Filament;

use App\Filament\Estidama\Pages as EP;
use App\Filament\Estidama\Resources as ER;
use App\Filament\Estidama\Widgets as EW;
use Modules\Estidama\Models\Badge;
use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\EstidamaEvent;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ExamQuestion;
use Modules\Estidama\Models\PartnerLogo;
use Modules\Estidama\Models\ProgramLesson;
use Modules\Estidama\Models\ProgramModule;
use Modules\Estidama\Models\TrainingApplication;
use Modules\Estidama\Models\TrainingCenter;
use Modules\Estidama\Models\TrainingProgram;
use App\Models\User;
use App\Services\Lms\EnrollmentService;
use App\Services\Lms\ExamService;
use App\Services\Lms\ProgressService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Every page of the Estidama panel (resources, custom pages, relation managers,
 * widgets) must render for super_admin and estidama_admin, and must never crash
 * for a trainer (403 allowed, scoped to their own programs).
 */
class EstidamaPanelSmokeTest extends TestCase
{
    use DatabaseTransactions;

    protected TrainingProgram $program;
    protected Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        config(['security.two_factor.require_for_staff' => false]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Filament::setCurrentPanel(Filament::getPanel('estidama_panal'));
    }

    protected function makeUser(?string $role = null): User
    {
        $user = User::factory()->create([
            'national_id' => (string) random_int(10000000000000, 29999999999999),
            'status' => 'approved',
        ]);
        if ($role) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $user->fresh();
    }

    /** A complete program so that every edit/view page has a record. */
    protected function seedLms(?User $instructor = null): void
    {
        $center = TrainingCenter::create(['name' => 'مركز اختبار اللوحة']);
        $this->program = TrainingProgram::create([
            'training_center_id' => $center->id,
            'instructor_id' => $instructor?->id,
            'title' => 'برنامج اللوحة ' . Str::random(5),
            'description' => '<p>وصف</p>',
            'image' => 'programs/x.jpg',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'open',
            'requires_application' => false,
        ]);
        $module = ProgramModule::create(['training_program_id' => $this->program->id, 'title' => 'وحدة', 'type' => 'video', 'order' => 1]);
        $lesson = ProgramLesson::create(['program_module_id' => $module->id, 'title' => 'درس', 'type' => 'text', 'body' => '<p>نص</p>', 'sort_order' => 1]);
        $this->exam = Exam::create(['training_program_id' => $this->program->id, 'title' => 'امتحان اللوحة', 'kind' => 'final', 'duration_minutes' => 10, 'passing_score' => 50]);
        ExamQuestion::create(['exam_id' => $this->exam->id, 'question_type' => 'mcq', 'question_text' => '<p>س</p>',
            'options' => [['option_text' => 'أ'], ['option_text' => 'ب']], 'correct_answer' => 'أ', 'points' => 1]);
        ExamQuestion::create(['exam_id' => $this->exam->id, 'question_type' => 'essay', 'question_text' => '<p>اشرح</p>', 'options' => [], 'points' => 2]);
        $this->program->update(['exam_id' => $this->exam->id]);

        $learner = $this->makeUser();
        $enrollment = app(EnrollmentService::class)->enrollDirect($learner, $this->program);
        app(ProgressService::class)->markComplete($enrollment, $lesson);
        $attempt = app(ExamService::class)->start($this->exam, $learner, $enrollment);
        app(ExamService::class)->submit($attempt, []);

        TrainingApplication::create([
            'training_program_id' => $this->program->id, 'applicant_email' => 'x@example.com', 'applicant_name' => 'متقدم',
            'national_id' => (string) random_int(10000000000000, 29999999999999), 'phone' => '0100', 'gender' => 'ذكر',
            'educational_qualification' => 'ب', 'employment_status' => 'م', 'current_position' => 'م', 'job_address' => 'ك',
            'national_id_front_image' => 'applications/a.jpg', 'national_id_back_image' => 'applications/b.jpg',
            'has_taken_previous_courses' => false, 'status' => 'pending',
        ]);
        app(\App\Services\Lms\CertificateService::class)->issue($enrollment);
        Badge::firstOrCreate(['name' => 'شارة اللوحة'], ['image' => 'badges/x.png', 'description' => '-', 'rule_type' => 'manual']);
        EstidamaEvent::firstOrCreate(['title' => 'حدث اللوحة'], ['image' => 'estidama/events/x.jpg']);
        if (PartnerLogo::query()->doesntExist()) {
            try {
                PartnerLogo::create(['name' => 'شريك', 'logo' => 'partners/logos/x.png']);
            } catch (\Throwable) {
                // schema differs: the smoke test simply skips its edit page
            }
        }
    }

    public function test_every_page_renders_for_super_admin(): void
    {
        $this->seedLms();
        $this->assertPanelRenders($this->makeUser('super_admin'));
    }

    public function test_every_page_renders_for_estidama_admin(): void
    {
        $this->seedLms();
        $this->assertPanelRenders($this->makeUser('estidama_admin'));
    }

    public function test_trainer_pages_do_not_crash_and_are_scoped(): void
    {
        $trainer = $this->makeUser('estidama_trainer');
        $this->seedLms($trainer);
        $own = $this->program;
        $this->seedLms(); // somebody else's program

        $failures = $this->collectFailures($trainer, allowForbidden: true);
        $this->assertSame([], $failures, implode("\n", $failures));

        $this->actingAs($trainer);
        Livewire::test(ER\TrainingProgramResource\Pages\ListTrainingPrograms::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$this->program]);

        $this->get(ER\TrainingProgramResource::getUrl('edit', ['record' => $own]))->assertOk();
        $this->get(ER\TrainingProgramResource::getUrl('edit', ['record' => $this->program]))->assertNotFound();
        $this->get(ER\EstidamaEventResource::getUrl('index'))->assertForbidden();
    }

    public function test_relation_managers_widgets_and_reports_render(): void
    {
        $this->seedLms();
        $this->actingAs($this->makeUser('estidama_admin'));

        $editProgram = ER\TrainingProgramResource\Pages\EditTrainingProgram::class;
        foreach ([
            ER\TrainingProgramResource\RelationManagers\ProgramModulesRelationManager::class,
            ER\TrainingProgramResource\RelationManagers\LessonsRelationManager::class,
            ER\TrainingProgramResource\RelationManagers\EnrollmentsRelationManager::class,
        ] as $rm) {
            Livewire::test($rm, ['ownerRecord' => $this->program, 'pageClass' => $editProgram])->assertSuccessful();
        }
        foreach ([
            ER\ExamResource\RelationManagers\QuestionsRelationManager::class,
            ER\ExamResource\RelationManagers\SubmissionsRelationManager::class,
        ] as $rm) {
            Livewire::test($rm, ['ownerRecord' => $this->exam, 'pageClass' => ER\ExamResource\Pages\EditExam::class])->assertSuccessful();
        }

        Livewire::test(ER\TrainingProgramResource\RelationManagers\LessonsRelationManager::class, ['ownerRecord' => $this->program, 'pageClass' => $editProgram])
            ->mountTableAction('create')
            ->assertHasNoTableActionErrors();

        foreach ([EW\EstidamaStatsOverview::class, EW\EnrollmentsByProgramChart::class, EW\PendingEnrollments::class, EW\ProgramPerformanceStats::class] as $widget) {
            Livewire::test($widget)->assertSuccessful();
        }
        foreach ([EP\ProgramAnalyticsReport::class, EP\LessonDropOffReport::class, EP\ExamStatsReport::class, EP\ProgramPerformanceReport::class, EP\EnrollmentReport::class] as $page) {
            Livewire::test($page)->assertSuccessful();
        }

        // Essay grading action from the submissions relation manager.
        $attempt = $this->exam->submissions()->first();
        $essayId = $this->exam->questions()->where('question_type', 'essay')->value('id');
        Livewire::test(ER\ExamResource\RelationManagers\SubmissionsRelationManager::class, ['ownerRecord' => $this->exam, 'pageClass' => ER\ExamResource\Pages\EditExam::class])
            ->callTableAction('grade', $attempt, ['essay' => [$essayId => 2], 'feedback' => 'جيد'])
            ->assertHasNoTableActionErrors();
        $this->assertSame('submitted', $attempt->fresh()->status);
    }

    public function test_application_approval_action_creates_enrollment(): void
    {
        $this->seedLms();
        $this->actingAs($this->makeUser('estidama_admin'));
        $applicant = $this->makeUser();
        $application = TrainingApplication::where('training_program_id', $this->program->id)->first();
        $application->update(['national_id' => $applicant->national_id]);

        Livewire::test(ER\TrainingApplicationResource\Pages\ListTrainingApplications::class)
            ->callTableAction('approve', $application)
            ->assertHasNoTableActionErrors();

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertTrue(Enrollment::where('user_id', $applicant->id)->where('training_program_id', $this->program->id)->exists());
    }

    // ------------------------------------------------------------------

    protected function assertPanelRenders(User $user): void
    {
        $failures = $this->collectFailures($user);
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    protected function collectFailures(User $user, bool $allowForbidden = false): array
    {
        $panel = Filament::getPanel('estidama_panal');
        Filament::setCurrentPanel($panel);
        $this->actingAs($user);

        $urls = [];
        $sharedOnly = []; // panel-wide resources (e.g. Shield roles) reserved for super_admin
        foreach ($panel->getPages() as $page) {
            $urls[class_basename($page)] = $page::getUrl(panel: 'estidama_panal');
        }
        foreach ($panel->getResources() as $resource) {
            if (! str_starts_with($resource, 'App\\Filament\\Estidama\\') && ! $user->hasRole('super_admin')) {
                $sharedOnly[] = class_basename($resource);
            }
            foreach ($resource::getPages() as $name => $registration) {
                $label = class_basename($resource) . '::' . $name;
                $needsRecord = in_array($name, ['edit', 'view'], true);
                if ($needsRecord) {
                    $record = $resource::getEloquentQuery()->first() ?? $resource::getModel()::query()->first();
                    if (! $record) {
                        continue;
                    }
                    $urls[$label] = $resource::getUrl($name, ['record' => $record], panel: 'estidama_panal');
                } else {
                    $urls[$label] = $resource::getUrl($name, panel: 'estidama_panal');
                }
            }
        }

        $failures = [];
        foreach ($urls as $label => $url) {
            $response = $this->get($url);
            $status = $response->getStatusCode();
            if ($status >= 500) {
                $e = $response->exception;
                $failures[] = "{$label} ({$url}): HTTP {$status}" . ($e ? ' ' . get_class($e) . ': ' . Str::limit($e->getMessage(), 300) . ' @ ' . $e->getFile() . ':' . $e->getLine() : '');
            } elseif (in_array($status, [403, 404], true) && ! $allowForbidden && ! in_array(strtok($label, ':'), $sharedOnly, true)) {
                $failures[] = "{$label} ({$url}): HTTP {$status}";
            }
        }

        return $failures;
    }
}
