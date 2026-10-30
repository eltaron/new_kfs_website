<?php

namespace Database\Seeders;

use Modules\Estidama\Models\Badge;
use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ExamQuestion;
use Modules\Estidama\Models\ProgramLesson;
use Modules\Estidama\Models\ProgramModule;
use Modules\Estidama\Models\TrainingApplication;
use Modules\Estidama\Models\TrainingCenter;
use Modules\Estidama\Models\TrainingProgram;
use App\Models\User;
use App\Services\Lms\EnrollmentService;
use App\Services\Lms\ExamService;
use App\Services\Lms\ProgressService;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Estidama LMS demo content (Arabic): sections, lessons (text / PDF / links /
 * quizzes), final exams, rule-based badges and learner activity (progress,
 * attempts, certificates).
 *
 * Runs after TrainingBasicsSeeder (which creates centers, programs and
 * applications) but also works on its own. Idempotent: programs that already
 * have lessons are left untouched.
 */
class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        DemoData::seed(2222);

        foreach (['estidama_admin', 'estidama_trainer'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->ensurePrograms();
        $this->seedBadges();

        foreach (TrainingProgram::orderBy('id')->get() as $program) {
            if ($program->lessons()->exists()) {
                continue;
            }
            $this->buildCurriculum($program, $this->blueprintFor($program->title));
        }

        $this->seedLearnerActivity();
    }

    // ------------------------------------------------------------------
    // Programs & badges
    // ------------------------------------------------------------------

    protected function ensurePrograms(): void
    {
        if (TrainingProgram::query()->exists()) {
            return;
        }

        $center = TrainingCenter::firstOrCreate(['name' => 'مركز استدامة الرئيسي - ديوان عام المحافظة'], ['address' => 'كفر الشيخ']);
        foreach ([
            ['أساسيات التنمية المستدامة والعمل المناخي', false, 0, false],
            ['التحول الرقمي في الخدمات الحكومية', false, 0, true],
            ['ريادة الأعمال للشباب', true, 300, false],
        ] as $i => [$title, $paid, $price, $application]) {
            TrainingProgram::create([
                'training_center_id' => $center->id,
                'title' => $title,
                'description' => '<p>برنامج تدريبي ضمن منصة استدامة بمحافظة كفر الشيخ: ' . e($title) . '.</p>',
                'image' => DemoData::image('training', 'lms-program-' . ($i + 1)),
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->addDays(50)->toDateString(),
                'status' => 'open',
                'is_paid' => $paid,
                'price' => $price,
                'requires_application' => $application,
                'sequential' => true,
                'certificate_enabled' => true,
                'level' => 'beginner',
            ]);
        }
    }

    protected function seedBadges(): void
    {
        foreach ([
            ['المتعلم المبادر', 'first_course', null, 'تُمنح عند إتمام أول برنامج تدريبي على منصة استدامة.'],
            ['المثابر', 'lessons_count', 10, 'تُمنح عند إتمام عشرة دروس.'],
            ['المتفوق', 'high_score', 90, 'تُمنح عند الحصول على 90% فأكثر في امتحان نهائي.'],
            ['سفير الاستدامة', 'programs_count', 3, 'تُمنح عند إتمام ثلاثة برامج تدريبية.'],
        ] as [$name, $rule, $value, $description]) {
            Badge::updateOrCreate(['name' => $name], [
                'image' => DemoData::image('badges', $rule, 400, 400, strtoupper($rule)),
                'description' => $description,
                'rule_type' => $rule,
                'rule_value' => $value,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Curriculum
    // ------------------------------------------------------------------

    /**
     * @param array{intro: string, modules: list<array{title: string, lessons: list<array>, quiz: list<array>}>, final: list<array>} $blueprint
     */
    protected function buildCurriculum(TrainingProgram $program, array $blueprint): void
    {
        $order = 0;
        $minutes = 0;

        foreach ($blueprint['modules'] as $m => $moduleData) {
            $module = ProgramModule::create([
                'training_program_id' => $program->id,
                'title' => $moduleData['title'],
                'description' => $moduleData['description'] ?? null,
                'type' => 'offline_lecture',
                'order' => $m + 1,
                'is_published' => true,
                'unlock_after_days' => $m === 2 ? 3 : null, // third section drips after 3 days
            ]);

            foreach ($moduleData['lessons'] as $l => $lessonData) {
                $minutes += $lessonData['minutes'] ?? 10;
                $attributes = [
                    'program_module_id' => $module->id,
                    'title' => $lessonData['title'],
                    'summary' => $lessonData['summary'] ?? null,
                    'type' => $lessonData['type'],
                    'sort_order' => ++$order,
                    'estimated_minutes' => $lessonData['minutes'] ?? 10,
                    'is_required' => true,
                    'is_published' => true,
                    'is_preview' => $m === 0 && $l === 0,
                ];
                $attributes += match ($lessonData['type']) {
                    'pdf' => ['file_path' => DemoData::pdf('lms', $program->id . '-' . $order), 'body' => $lessonData['body'] ?? null],
                    'link' => ['external_url' => $lessonData['url'], 'body' => $lessonData['body'] ?? null],
                    default => ['body' => $lessonData['body']],
                };
                ProgramLesson::create($attributes);
            }

            if (! empty($moduleData['quiz'])) {
                $quiz = $this->makeExam($program, 'اختبار قصير: ' . $moduleData['title'], 'quiz', $moduleData['quiz'], 10, 60, 3);
                ProgramLesson::create([
                    'program_module_id' => $module->id,
                    'title' => 'اختبار الوحدة: ' . $moduleData['title'],
                    'summary' => 'اختبار قصير للتأكد من فهم محتوى الوحدة. يكتمل الدرس تلقائيًا عند النجاح.',
                    'type' => 'quiz',
                    'exam_id' => $quiz->id,
                    'sort_order' => ++$order,
                    'estimated_minutes' => 10,
                    'is_required' => true,
                    'is_published' => true,
                ]);
                $minutes += 10;
            }
        }

        $updates = ['estimated_minutes' => $program->estimated_minutes ?: $minutes];
        if (! $program->exam_id && ! empty($blueprint['final'])) {
            $final = $this->makeExam($program, 'الامتحان النهائي: ' . $program->title, 'final', $blueprint['final'], 20, 70, 3, true);
            $updates['exam_id'] = $final->id;
        }
        if (blank(strip_tags((string) $program->description)) || mb_strlen(strip_tags((string) $program->description)) < 120) {
            $updates['description'] = $blueprint['intro'];
        }
        $program->update($updates);
    }

    protected function makeExam(TrainingProgram $program, string $title, string $kind, array $questions, int $minutes, int $passing, ?int $attempts, bool $shuffle = false): Exam
    {
        $exam = Exam::create([
            'training_program_id' => $program->id,
            'title' => $title,
            'kind' => $kind,
            'description' => $kind === 'final'
                ? 'يغطي الامتحان جميع وحدات البرنامج. اقرأ كل سؤال بعناية؛ تُحفظ إجاباتك تلقائيًا.'
                : 'اختبار قصير على محتوى الوحدة.',
            'duration_minutes' => $minutes,
            'passing_score' => $passing,
            'max_attempts' => $attempts,
            'shuffle_questions' => $shuffle,
            'shuffle_options' => $shuffle,
            'allow_review' => true,
        ]);

        foreach ($questions as $i => $q) {
            $isTf = count($q['options']) === 2 && in_array($q['answer'], ExamQuestion::TRUE_FALSE_OPTIONS, true);
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'question_type' => $isTf ? 'true_false' : 'mcq',
                'question_text' => '<p>' . e($q['q']) . '</p>',
                'options' => array_map(fn ($o) => ['option_text' => $o], $q['options']),
                'correct_answer' => $q['answer'],
                'points' => 1,
                'explanation' => $q['why'] ?? null,
                'sort_order' => $i + 1,
            ]);
        }

        return $exam;
    }

    // ------------------------------------------------------------------
    // Learner activity
    // ------------------------------------------------------------------

    protected function seedLearnerActivity(): void
    {
        $enrollments = app(EnrollmentService::class);
        $progress = app(ProgressService::class);
        $exams = app(ExamService::class);

        // Approved applications become enrollments (created approved => no update event fired).
        TrainingApplication::where('status', 'approved')->get()->each(fn ($a) => $enrollments->enrollFromApplication($a));

        // A few citizens join programs that allow direct enrollment.
        $citizens = User::where('email', 'like', 'citizen%@demo.kfs.invalid')->orderBy('email')->take(12)->get();
        $direct = TrainingProgram::where('requires_application', false)->whereIn('status', ['open', 'ongoing'])->get();
        foreach ($citizens as $i => $citizen) {
            if ($direct->isNotEmpty() && $i % 2 === 0) {
                $enrollments->enrollDirect($citizen, $direct[$i % $direct->count()]);
            }
        }

        $i = 0;
        foreach (Enrollment::with('trainingProgram', 'user')->whereIn('status', ['approved'])->get() as $enrollment) {
            if (! $enrollment->user || $enrollment->lessonProgress()->exists()) {
                continue;
            }
            if ($enrollment->needsPayment() && $i % 2 === 0) {
                // Demo: settled paid seat (real seats are paid through the unified payment service).
                $enrollment->forceFill(['payment_status' => 'paid', 'paid_at' => now()->subDays(12)])->save();
            }
            if (! $enrollment->canAccessContent()) {
                $i++;
                continue;
            }
            $enrollment->forceFill(['enrolled_at' => now()->subDays(14)])->save();

            $states = $progress->lessonStates($enrollment->fresh());
            $plan = $i % 4; // 0: just started, 1: half way, 2-3: finished
            $target = match ($plan) {
                0 => 1,
                1 => (int) ceil($states->count() / 2),
                default => $states->count(),
            };

            foreach ($states->take($target) as $state) {
                $lesson = $state['lesson'];
                if ($lesson->type === 'quiz' && $lesson->exam) {
                    $this->passExam($exams, $lesson->exam, $enrollment, $lesson);
                } else {
                    $progress->markComplete($enrollment, $lesson);
                }
            }

            $program = $enrollment->trainingProgram;
            if ($plan >= 2 && $program->exam_id && $program->finalExam) {
                if ($plan === 2) {
                    $this->failExam($exams, $program->finalExam, $enrollment);
                }
                $this->passExam($exams, $program->finalExam, $enrollment, null, $plan === 3 ? 1.0 : 0.85);
            }
            $i++;
        }
    }

    protected function passExam(ExamService $exams, Exam $exam, Enrollment $enrollment, ?ProgramLesson $lesson, float $ratio = 1.0): void
    {
        if ($exams->startBlocker($exam, $enrollment->user) !== null && ! $exams->openAttempt($exam, $enrollment->user)) {
            return;
        }
        $attempt = $exams->start($exam, $enrollment->user, $enrollment, $lesson);
        $questions = $exams->questionsFor($attempt);
        $right = (int) ceil($questions->count() * $ratio);
        $answers = [];
        foreach ($questions->values() as $n => $q) {
            $answers[$q->id] = $n < $right ? $q->correct_answer : collect($q->display_options)->first(fn ($o) => $o !== $q->correct_answer);
        }
        $exams->submit($attempt, $answers);
    }

    protected function failExam(ExamService $exams, Exam $exam, Enrollment $enrollment): void
    {
        if ($exams->startBlocker($exam, $enrollment->user) !== null) {
            return;
        }
        $attempt = $exams->start($exam, $enrollment->user, $enrollment);
        $answers = [];
        foreach ($exams->questionsFor($attempt) as $n => $q) {
            $answers[$q->id] = $n % 3 === 0 ? $q->correct_answer : collect($q->display_options)->first(fn ($o) => $o !== $q->correct_answer);
        }
        $exams->submit($attempt, $answers);
    }

    // ------------------------------------------------------------------
    // Arabic content blueprints
    // ------------------------------------------------------------------

    protected function blueprintFor(string $title): array
    {
        return match (true) {
            str_contains($title, 'التحول الرقمي') => $this->digitalTransformation(),
            str_contains($title, 'GIS') || str_contains($title, 'الجغرافية') => $this->gis(),
            str_contains($title, 'المشروعات') => $this->projectManagement(),
            str_contains($title, 'خدمة المواطنين') || str_contains($title, 'التواصل') => $this->citizenService(),
            str_contains($title, 'ريادة') => $this->entrepreneurship(),
            default => $this->sustainability(),
        };
    }

    protected function p(string ...$paragraphs): string
    {
        return implode('', array_map(fn ($p) => str_starts_with($p, '<') ? $p : '<p>' . e($p) . '</p>', $paragraphs));
    }

    protected function ul(string ...$items): string
    {
        return '<ul>' . implode('', array_map(fn ($i) => '<li>' . e($i) . '</li>', $items)) . '</ul>';
    }

    protected function tf(string $q, bool $true, ?string $why = null): array
    {
        return ['q' => $q, 'options' => ExamQuestion::TRUE_FALSE_OPTIONS, 'answer' => $true ? 'صح' : 'خطأ', 'why' => $why];
    }

    protected function digitalTransformation(): array
    {
        return [
            'intro' => $this->p(
                'يهدف البرنامج إلى تمكين العاملين بالجهاز الإداري للمحافظة من فهم مفاهيم التحول الرقمي وتطبيقها في تقديم الخدمات للمواطنين بسرعة وشفافية.',
                'يتناول البرنامج البنية الرقمية للخدمات الحكومية، وإعادة هندسة الإجراءات، وحماية البيانات، وقياس رضا المواطن.'
            ),
            'modules' => [
                [
                    'title' => 'مفاهيم التحول الرقمي',
                    'lessons' => [
                        ['title' => 'ما هو التحول الرقمي؟', 'type' => 'text', 'minutes' => 12, 'body' => $this->p(
                            'التحول الرقمي ليس مجرد شراء أجهزة أو إنشاء موقع إلكتروني؛ بل هو إعادة التفكير في طريقة تقديم الخدمة من منظور المواطن.',
                            '<h3>محاور التحول الرقمي</h3>',
                            $this->ul('رقمنة الإجراءات والنماذج', 'ربط قواعد البيانات بين الجهات', 'الدفع الإلكتروني الموحد', 'قياس الأداء ورضا المواطنين')
                        )],
                        ['title' => 'استراتيجية مصر الرقمية', 'type' => 'link', 'minutes' => 15, 'url' => 'https://mcit.gov.eg/ar', 'body' => $this->p('اطّلع على محاور استراتيجية مصر الرقمية على موقع وزارة الاتصالات وتكنولوجيا المعلومات، ثم دوّن ثلاث أفكار يمكن تطبيقها في جهة عملك.')],
                        ['title' => 'دليل المصطلحات الرقمية', 'type' => 'pdf', 'minutes' => 10, 'body' => $this->p('ملف مرجعي يضم أهم المصطلحات المستخدمة في مشروعات التحول الرقمي.')],
                    ],
                    'quiz' => [
                        ['q' => 'أي مما يلي يُعد جوهر التحول الرقمي؟', 'options' => ['إعادة تصميم الخدمة من منظور المواطن', 'شراء أجهزة حاسب جديدة', 'زيادة عدد الموظفين'], 'answer' => 'إعادة تصميم الخدمة من منظور المواطن'],
                        $this->tf('الدفع الإلكتروني الموحد من محاور التحول الرقمي.', true),
                        ['q' => 'ما الهدف من ربط قواعد البيانات بين الجهات؟', 'options' => ['تقليل المستندات المطلوبة من المواطن', 'زيادة رسوم الخدمة', 'تأخير الإجراءات'], 'answer' => 'تقليل المستندات المطلوبة من المواطن'],
                    ],
                ],
                [
                    'title' => 'إعادة هندسة الإجراءات',
                    'lessons' => [
                        ['title' => 'رسم خريطة الإجراء الحالي', 'type' => 'text', 'minutes' => 15, 'body' => $this->p(
                            'قبل رقمنة أي خدمة يجب توثيق خطواتها الحالية: من يقدّم الطلب؟ ما المستندات؟ من يراجع؟ وكم يستغرق كل إجراء؟',
                            $this->ul('حدد نقطة البداية والنهاية للخدمة', 'سجّل كل توقيع أو موافقة مطلوبة', 'احسب الزمن الفعلي لكل خطوة', 'حدد الخطوات المكررة أو غير الضرورية')
                        )],
                        ['title' => 'تبسيط الإجراءات وتقليل زمن الخدمة', 'type' => 'text', 'minutes' => 12, 'body' => $this->p(
                            'التبسيط يعني حذف الخطوات التي لا تضيف قيمة، ودمج الموافقات، والاعتماد على البيانات المتاحة إلكترونيًا بدلًا من مطالبة المواطن بها.',
                            'مثال: خدمة استخراج ترخيص يمكن أن تنخفض من سبع خطوات إلى ثلاث عند ربطها بقاعدة بيانات الرقم القومي.'
                        )],
                    ],
                    'quiz' => [
                        ['q' => 'ما أول خطوة قبل رقمنة أي خدمة؟', 'options' => ['توثيق الإجراء الحالي', 'شراء نظام جاهز', 'إلغاء الخدمة'], 'answer' => 'توثيق الإجراء الحالي'],
                        $this->tf('دمج الموافقات المتعددة يساعد على تقليل زمن الخدمة.', true),
                        ['q' => 'أي الخطوات يجب حذفها عند التبسيط؟', 'options' => ['الخطوات التي لا تضيف قيمة', 'خطوة تقديم الطلب', 'خطوة تسليم الخدمة'], 'answer' => 'الخطوات التي لا تضيف قيمة'],
                    ],
                ],
                [
                    'title' => 'أمن المعلومات وحماية البيانات',
                    'lessons' => [
                        ['title' => 'مبادئ حماية بيانات المواطنين', 'type' => 'text', 'minutes' => 14, 'body' => $this->p(
                            'بيانات المواطنين أمانة. يجب جمع الحد الأدنى من البيانات اللازمة للخدمة فقط، وتخزينها بشكل آمن، وعدم إتاحتها إلا للمختصين.',
                            $this->ul('استخدم كلمات مرور قوية والتحقق الثنائي', 'لا تشارك صور البطاقات عبر تطبيقات المراسلة', 'أغلق جهازك عند مغادرة المكتب', 'أبلغ فورًا عن أي رسالة تصيّد مشبوهة')
                        )],
                        ['title' => 'التعرف على رسائل التصيد الاحتيالي', 'type' => 'text', 'minutes' => 10, 'body' => $this->p('رسائل التصيد تنتحل صفة جهات موثوقة لسرقة كلمات المرور. انتبه إلى عنوان المرسل، والروابط المختصرة، ولغة الاستعجال والتهديد.')],
                    ],
                    'quiz' => [
                        $this->tf('يجوز إرسال صور بطاقات الرقم القومي عبر تطبيقات المراسلة الشخصية.', false, 'المستندات الشخصية تُتداول فقط عبر الأنظمة الرسمية المؤمَّنة.'),
                        ['q' => 'ما أفضل وسيلة لحماية حسابك الوظيفي؟', 'options' => ['التحقق الثنائي وكلمة مرور قوية', 'كلمة مرور قصيرة سهلة التذكر', 'مشاركة كلمة المرور مع الزملاء'], 'answer' => 'التحقق الثنائي وكلمة مرور قوية'],
                        ['q' => 'من علامات رسائل التصيد:', 'options' => ['لغة الاستعجال والتهديد', 'وجود توقيع رسمي واضح', 'إرسالها من البريد الحكومي المعروف'], 'answer' => 'لغة الاستعجال والتهديد'],
                    ],
                ],
            ],
            'final' => [
                ['q' => 'التحول الرقمي في المقام الأول هو:', 'options' => ['تغيير في طريقة تقديم الخدمة', 'شراء أجهزة حديثة', 'إنشاء صفحة على مواقع التواصل'], 'answer' => 'تغيير في طريقة تقديم الخدمة'],
                ['q' => 'الهدف من توثيق الإجراء الحالي هو:', 'options' => ['اكتشاف فرص التبسيط', 'زيادة المستندات المطلوبة', 'إلغاء دور الموظف'], 'answer' => 'اكتشاف فرص التبسيط'],
                ['q' => 'أي مما يلي يقلل المستندات المطلوبة من المواطن؟', 'options' => ['ربط قواعد البيانات', 'طباعة النماذج', 'زيادة التوقيعات'], 'answer' => 'ربط قواعد البيانات'],
                ['q' => 'مبدأ "الحد الأدنى من البيانات" يعني:', 'options' => ['جمع ما تحتاجه الخدمة فقط', 'جمع كل البيانات الممكنة', 'عدم جمع أي بيانات'], 'answer' => 'جمع ما تحتاجه الخدمة فقط'],
                ['q' => 'عند تلقي رسالة تطلب كلمة المرور بشكل عاجل يجب:', 'options' => ['الإبلاغ عنها وعدم الضغط على الروابط', 'الرد بكلمة المرور', 'إعادة إرسالها للزملاء'], 'answer' => 'الإبلاغ عنها وعدم الضغط على الروابط'],
                ['q' => 'مؤشر رضا المواطن يُستخدم من أجل:', 'options' => ['تحسين الخدمة باستمرار', 'معاقبة الموظفين', 'زيادة الرسوم'], 'answer' => 'تحسين الخدمة باستمرار'],
                $this->tf('التحقق الثنائي يزيد من أمان الحسابات.', true),
                $this->tf('رقمنة الخدمة تعني نقل الإجراء الورقي كما هو دون أي تغيير.', false, 'الرقمنة الفعالة تبدأ بتبسيط الإجراء ثم أتمتته.'),
            ],
        ];
    }

    protected function sustainability(): array
    {
        return [
            'intro' => $this->p(
                'برنامج تمهيدي يعرّف المشاركين بأهداف التنمية المستدامة السبعة عشر، وبتحديات تغيّر المناخ في دلتا النيل، ودور المجتمع المحلي في كفر الشيخ في الحفاظ على الموارد.',
                'يجمع البرنامج بين المحتوى المقروء والموارد الدولية واختبارات قصيرة تقيس الفهم.'
            ),
            'modules' => [
                [
                    'title' => 'مدخل إلى التنمية المستدامة',
                    'lessons' => [
                        ['title' => 'تعريف التنمية المستدامة', 'type' => 'text', 'minutes' => 10, 'body' => $this->p(
                            'التنمية المستدامة هي التنمية التي تلبي احتياجات الحاضر دون الإضرار بقدرة الأجيال القادمة على تلبية احتياجاتها.',
                            '<h3>الأبعاد الثلاثة</h3>',
                            $this->ul('البعد الاقتصادي: نمو يوفر فرص عمل لائقة', 'البعد الاجتماعي: عدالة وتكافؤ في الفرص', 'البعد البيئي: حماية الموارد الطبيعية')
                        )],
                        ['title' => 'أهداف التنمية المستدامة 2030', 'type' => 'link', 'minutes' => 15, 'url' => 'https://sdgs.un.org/goals', 'body' => $this->p('تصفح أهداف التنمية المستدامة السبعة عشر على موقع الأمم المتحدة، واختر هدفين ترى أنهما الأكثر ارتباطًا بمحافظة كفر الشيخ.')],
                    ],
                    'quiz' => [
                        ['q' => 'كم عدد أهداف التنمية المستدامة؟', 'options' => ['17 هدفًا', '10 أهداف', '25 هدفًا'], 'answer' => '17 هدفًا'],
                        $this->tf('للتنمية المستدامة بعد اقتصادي واجتماعي وبيئي.', true),
                        ['q' => 'التنمية المستدامة تراعي:', 'options' => ['احتياجات الأجيال القادمة', 'الربح السريع فقط', 'المدن الكبرى فقط'], 'answer' => 'احتياجات الأجيال القادمة'],
                    ],
                ],
                [
                    'title' => 'المياه والزراعة في كفر الشيخ',
                    'lessons' => [
                        ['title' => 'ترشيد استهلاك المياه', 'type' => 'text', 'minutes' => 12, 'body' => $this->p(
                            'تعتمد كفر الشيخ على الزراعة وصيد الأسماك، لذلك فإن كل نقطة مياه لها قيمة. من أساليب الترشيد: الري بالتنقيط، وتبطين المساقي، وإعادة استخدام مياه الصرف المعالجة.',
                            $this->ul('الري الليلي يقلل الفاقد بالتبخر', 'صيانة الوصلات المنزلية توفر حتى 10% من الاستهلاك', 'اختيار محاصيل مناسبة للتربة والمياه المتاحة')
                        )],
                        ['title' => 'دليل الممارسات الزراعية الذكية مناخيًا', 'type' => 'pdf', 'minutes' => 15],
                    ],
                    'quiz' => [
                        ['q' => 'أي أساليب الري أكثر توفيرًا للمياه؟', 'options' => ['الري بالتنقيط', 'الري بالغمر', 'الري في وقت الظهيرة'], 'answer' => 'الري بالتنقيط'],
                        $this->tf('الري الليلي يقلل الفاقد بالتبخر.', true),
                        ['q' => 'تبطين المساقي يهدف إلى:', 'options' => ['تقليل تسرب المياه', 'زيادة الحشائش', 'رفع تكلفة الري'], 'answer' => 'تقليل تسرب المياه'],
                    ],
                ],
                [
                    'title' => 'العمل المناخي المجتمعي',
                    'lessons' => [
                        ['title' => 'تغير المناخ ودلتا النيل', 'type' => 'text', 'minutes' => 12, 'body' => $this->p('تُعد المناطق الساحلية في الدلتا من أكثر المناطق تأثرًا بارتفاع منسوب سطح البحر وتملّح التربة. تتطلب مواجهة ذلك خططًا للتكيف وحماية الشواطئ وتنويع المحاصيل.')],
                        ['title' => 'مبادرات يمكنك قيادتها', 'type' => 'text', 'minutes' => 10, 'body' => $this->ul('حملات فصل المخلفات من المنبع في القرية', 'تشجير الشوارع والمدارس', 'التوعية بترشيد الكهرباء', 'دعم المنتجات المحلية لتقليل البصمة الكربونية')],
                    ],
                    'quiz' => [],
                ],
            ],
            'final' => [
                ['q' => 'تعريف التنمية المستدامة يركّز على:', 'options' => ['التوازن بين الحاضر والمستقبل', 'الاستهلاك غير المحدود', 'النمو الاقتصادي وحده'], 'answer' => 'التوازن بين الحاضر والمستقبل'],
                ['q' => 'من أخطر آثار تغير المناخ على الدلتا:', 'options' => ['تملح التربة', 'زيادة الأمطار الصحراوية', 'انخفاض درجات الحرارة'], 'answer' => 'تملح التربة'],
                ['q' => 'أفضل وقت للري لتقليل التبخر:', 'options' => ['الليل أو الصباح الباكر', 'الظهيرة', 'لا يهم'], 'answer' => 'الليل أو الصباح الباكر'],
                ['q' => 'فصل المخلفات من المنبع يساعد على:', 'options' => ['إعادة التدوير', 'زيادة الحرق المكشوف', 'زيادة التلوث'], 'answer' => 'إعادة التدوير'],
                ['q' => 'أي هدف أممي يتعلق بالعمل المناخي؟', 'options' => ['الهدف 13', 'الهدف 1', 'الهدف 9'], 'answer' => 'الهدف 13'],
                $this->tf('دعم المنتجات المحلية يمكن أن يقلل البصمة الكربونية.', true),
                $this->tf('التنمية المستدامة تهتم بالبيئة فقط.', false, 'لها ثلاثة أبعاد: اقتصادي واجتماعي وبيئي.'),
            ],
        ];
    }

    protected function gis(): array
    {
        return [
            'intro' => $this->p('يقدّم البرنامج أساسيات نظم المعلومات الجغرافية وتطبيقاتها في التخطيط العمراني وإدارة الخدمات بالمحافظة، مع أمثلة من خرائط المراكز والقرى.'),
            'modules' => [
                [
                    'title' => 'مفاهيم نظم المعلومات الجغرافية',
                    'lessons' => [
                        ['title' => 'ما هي نظم المعلومات الجغرافية؟', 'type' => 'text', 'minutes' => 12, 'body' => $this->p('نظم المعلومات الجغرافية (GIS) هي أنظمة لجمع وتخزين وتحليل وعرض البيانات المرتبطة بموقع على سطح الأرض.', $this->ul('البيانات الخطية (Vector): نقاط وخطوط ومضلعات', 'البيانات الشبكية (Raster): صور فضائية وخرائط ارتفاعات', 'الجداول الوصفية المرتبطة بالمعالم'))],
                        ['title' => 'مقدمة من Esri', 'type' => 'link', 'minutes' => 10, 'url' => 'https://www.esri.com/en-us/what-is-gis/overview'],
                    ],
                    'quiz' => [
                        ['q' => 'أي مما يلي بيانات خطية (Vector)؟', 'options' => ['حدود قطعة أرض', 'صورة فضائية', 'خريطة ارتفاعات'], 'answer' => 'حدود قطعة أرض'],
                        $this->tf('الصور الفضائية من البيانات الشبكية.', true),
                        ['q' => 'GIS تربط البيانات بـ:', 'options' => ['الموقع الجغرافي', 'الوقت فقط', 'اسم الموظف'], 'answer' => 'الموقع الجغرافي'],
                    ],
                ],
                [
                    'title' => 'أنظمة الإحداثيات والطبقات',
                    'lessons' => [
                        ['title' => 'أنظمة الإحداثيات في مصر', 'type' => 'text', 'minutes' => 15, 'body' => $this->p('تُستخدم في مصر أنظمة إحداثيات مثل WGS 84 والإسقاط المستعرض لميركاتور (UTM). اختيار النظام الصحيح ضروري لدقة القياسات والمساحات.')],
                        ['title' => 'دليل إدارة الطبقات', 'type' => 'pdf', 'minutes' => 12],
                    ],
                    'quiz' => [
                        ['q' => 'نظام WGS 84 هو:', 'options' => ['نظام إحداثيات عالمي', 'برنامج رسم', 'نوع من الطابعات'], 'answer' => 'نظام إحداثيات عالمي'],
                        $this->tf('اختيار نظام الإحداثيات يؤثر على دقة حساب المساحات.', true),
                        ['q' => 'الطبقة في GIS تمثل:', 'options' => ['مجموعة معالم من نوع واحد', 'لون الخريطة فقط', 'اسم المشروع'], 'answer' => 'مجموعة معالم من نوع واحد'],
                    ],
                ],
                [
                    'title' => 'تطبيقات في المحافظة',
                    'lessons' => [
                        ['title' => 'رصد المتغيرات المكانية والتعديات', 'type' => 'text', 'minutes' => 12, 'body' => $this->p('تُستخدم المقارنة بين الصور الفضائية في تواريخ مختلفة لرصد البناء المخالف والتعديات على الأراضي الزراعية، ثم تُحال الحالات للمعاينة الميدانية.')],
                    ],
                    'quiz' => [],
                ],
            ],
            'final' => [
                ['q' => 'أي نوع بيانات مناسب لتمثيل شبكة الطرق؟', 'options' => ['خطوط Vector', 'صورة Raster', 'جدول بدون مواقع'], 'answer' => 'خطوط Vector'],
                ['q' => 'لرصد التعديات نقارن بين:', 'options' => ['صور فضائية بتواريخ مختلفة', 'أسماء القرى', 'أرقام الهواتف'], 'answer' => 'صور فضائية بتواريخ مختلفة'],
                ['q' => 'UTM هو:', 'options' => ['إسقاط خرائطي', 'برنامج محاسبي', 'نوع ملف PDF'], 'answer' => 'إسقاط خرائطي'],
                ['q' => 'الجدول الوصفي في GIS يحتوي على:', 'options' => ['خصائص المعالم', 'صور الموظفين', 'كلمات المرور'], 'answer' => 'خصائص المعالم'],
                $this->tf('يمكن ربط بيانات طلبات المواطنين بمواقعها على الخريطة.', true),
                $this->tf('الصور الفضائية بيانات خطية.', false),
            ],
        ];
    }

    protected function projectManagement(): array
    {
        return [
            'intro' => $this->p('برنامج متقدم في إدارة المشروعات وفق الممارسات الدولية: تحديد النطاق، والجدولة، وإدارة المخاطر، ومتابعة الأداء في مشروعات التنمية المحلية.'),
            'modules' => [
                [
                    'title' => 'دورة حياة المشروع',
                    'lessons' => [
                        ['title' => 'مراحل المشروع الخمس', 'type' => 'text', 'minutes' => 12, 'body' => $this->ul('البدء: تحديد الهدف وأصحاب المصلحة', 'التخطيط: النطاق والجدول والميزانية', 'التنفيذ: إدارة الفريق والموارد', 'المتابعة والتحكم: قياس الأداء', 'الإغلاق: التسليم والدروس المستفادة')],
                        ['title' => 'معهد إدارة المشروعات PMI', 'type' => 'link', 'minutes' => 8, 'url' => 'https://www.pmi.org'],
                    ],
                    'quiz' => [
                        ['q' => 'في أي مرحلة يُحدد نطاق المشروع؟', 'options' => ['التخطيط', 'الإغلاق', 'التنفيذ'], 'answer' => 'التخطيط'],
                        $this->tf('الدروس المستفادة تُوثق في مرحلة الإغلاق.', true),
                        ['q' => 'أصحاب المصلحة هم:', 'options' => ['كل من يتأثر بالمشروع', 'مدير المشروع فقط', 'المورّدون فقط'], 'answer' => 'كل من يتأثر بالمشروع'],
                    ],
                ],
                [
                    'title' => 'الجدولة وإدارة المخاطر',
                    'lessons' => [
                        ['title' => 'المسار الحرج', 'type' => 'text', 'minutes' => 15, 'body' => $this->p('المسار الحرج هو أطول سلسلة من الأنشطة المتتابعة، وأي تأخير فيه يؤخر المشروع كله. لذلك تُركز المتابعة اليومية على أنشطته.')],
                        ['title' => 'سجل المخاطر (نموذج)', 'type' => 'pdf', 'minutes' => 10],
                    ],
                    'quiz' => [
                        ['q' => 'تأخير نشاط على المسار الحرج يؤدي إلى:', 'options' => ['تأخير المشروع', 'لا شيء', 'تقليل التكلفة'], 'answer' => 'تأخير المشروع'],
                        $this->tf('سجل المخاطر يُحدَّث مرة واحدة فقط في بداية المشروع.', false, 'يُحدَّث بانتظام طوال عمر المشروع.'),
                        ['q' => 'الاستجابة للمخاطر قد تكون:', 'options' => ['تجنب أو تخفيف أو نقل أو قبول', 'التجاهل فقط', 'إلغاء المشروع دائمًا'], 'answer' => 'تجنب أو تخفيف أو نقل أو قبول'],
                    ],
                ],
                [
                    'title' => 'متابعة الأداء',
                    'lessons' => [
                        ['title' => 'مؤشرات الأداء الرئيسية', 'type' => 'text', 'minutes' => 10, 'body' => $this->p('من أهم مؤشرات متابعة مشروعات المحافظة: نسبة الإنجاز الفعلية مقابل المخططة، والالتزام بالميزانية، وعدد الشكاوى المرتبطة بالمشروع.')],
                    ],
                    'quiz' => [],
                ],
            ],
            'final' => [
                ['q' => 'ما المرحلة التي تُعد فيها خطة المشروع؟', 'options' => ['التخطيط', 'الإغلاق', 'البدء'], 'answer' => 'التخطيط'],
                ['q' => 'المسار الحرج هو:', 'options' => ['أطول تسلسل أنشطة', 'أقصر تسلسل أنشطة', 'قائمة الموردين'], 'answer' => 'أطول تسلسل أنشطة'],
                ['q' => 'نقل المخاطرة مثل:', 'options' => ['التأمين على المعدات', 'تجاهل المخاطرة', 'زيادة نطاق المشروع'], 'answer' => 'التأمين على المعدات'],
                ['q' => 'نسبة الإنجاز الفعلية تقارن مع:', 'options' => ['النسبة المخططة', 'عدد الموظفين', 'عدد الاجتماعات'], 'answer' => 'النسبة المخططة'],
                $this->tf('إشراك أصحاب المصلحة مبكرًا يقلل مقاومة التغيير.', true),
                $this->tf('يمكن إغلاق المشروع دون تسليم المخرجات.', false),
            ],
        ];
    }

    protected function citizenService(): array
    {
        return [
            'intro' => $this->p('برنامج عملي لتطوير مهارات العاملين في المراكز التكنولوجية ومنافذ خدمة المواطنين: الاستقبال، والاستماع الفعّال، والتعامل مع الشكاوى، وخدمة ذوي الهمم.'),
            'modules' => [
                [
                    'title' => 'أساسيات خدمة المواطنين',
                    'lessons' => [
                        ['title' => 'ميثاق خدمة المواطن', 'type' => 'text', 'minutes' => 10, 'body' => $this->ul('الاحترام والابتسامة في الاستقبال', 'الوضوح في شرح المستندات المطلوبة', 'الالتزام بالمواعيد المعلنة', 'الشفافية في الرسوم')],
                        ['title' => 'الاستماع الفعّال', 'type' => 'text', 'minutes' => 12, 'body' => $this->p('الاستماع الفعّال يعني التركيز الكامل على المواطن، وإعادة صياغة طلبه للتأكد من فهمه، وتجنب المقاطعة.')],
                    ],
                    'quiz' => [
                        ['q' => 'الاستماع الفعّال يتضمن:', 'options' => ['إعادة صياغة الطلب للتأكد من فهمه', 'مقاطعة المواطن', 'الانشغال بالهاتف'], 'answer' => 'إعادة صياغة الطلب للتأكد من فهمه'],
                        $this->tf('الشفافية في الرسوم جزء من ميثاق خدمة المواطن.', true),
                        ['q' => 'عند عدم معرفة إجابة سؤال يجب:', 'options' => ['الرجوع للمختص وإبلاغ المواطن بموعد الرد', 'اختلاق إجابة', 'تجاهل السؤال'], 'answer' => 'الرجوع للمختص وإبلاغ المواطن بموعد الرد'],
                    ],
                ],
                [
                    'title' => 'التعامل مع الشكاوى',
                    'lessons' => [
                        ['title' => 'خطوات معالجة الشكوى', 'type' => 'text', 'minutes' => 12, 'body' => $this->ul('استقبال الشكوى وتسجيلها برقم متابعة', 'الاعتذار عن الإزعاج دون إلقاء اللوم', 'إحالة الشكوى للجهة المختصة', 'متابعة الحل وإبلاغ المواطن')],
                        ['title' => 'نموذج تسجيل الشكاوى', 'type' => 'pdf', 'minutes' => 5],
                    ],
                    'quiz' => [
                        ['q' => 'أول خطوة في معالجة الشكوى:', 'options' => ['تسجيلها برقم متابعة', 'إغلاقها فورًا', 'رفضها'], 'answer' => 'تسجيلها برقم متابعة'],
                        $this->tf('إبلاغ المواطن بنتيجة الشكوى خطوة اختيارية.', false),
                        ['q' => 'رقم المتابعة يفيد في:', 'options' => ['تتبع الشكوى حتى الحل', 'زيادة الرسوم', 'إخفاء الشكوى'], 'answer' => 'تتبع الشكوى حتى الحل'],
                    ],
                ],
                [
                    'title' => 'خدمة ذوي الهمم',
                    'lessons' => [
                        ['title' => 'إتاحة الخدمات للجميع', 'type' => 'text', 'minutes' => 10, 'body' => $this->p('تشمل الإتاحة: مداخل مهيأة، وأولوية في الدور، والتواصل بلغة الإشارة أو الكتابة عند الحاجة، ونماذج مطبوعة بخط كبير.')],
                    ],
                    'quiz' => [],
                ],
            ],
            'final' => [
                ['q' => 'الهدف من ميثاق خدمة المواطن:', 'options' => ['توحيد معايير الخدمة', 'زيادة الإجراءات', 'تقليل ساعات العمل'], 'answer' => 'توحيد معايير الخدمة'],
                ['q' => 'عند غضب المواطن يجب:', 'options' => ['الهدوء والاستماع ثم الحل', 'الرد بنفس الأسلوب', 'إنهاء المقابلة'], 'answer' => 'الهدوء والاستماع ثم الحل'],
                ['q' => 'من وسائل إتاحة الخدمة لضعاف البصر:', 'options' => ['نماذج بخط كبير', 'نماذج بخط صغير', 'إلغاء النماذج'], 'answer' => 'نماذج بخط كبير'],
                ['q' => 'الإحالة للجهة المختصة تكون:', 'options' => ['بعد تسجيل الشكوى', 'قبل استلامها', 'دون إبلاغ المواطن'], 'answer' => 'بعد تسجيل الشكوى'],
                $this->tf('أولوية الدور لكبار السن وذوي الهمم من حقوقهم.', true),
                $this->tf('يجوز إغلاق الشكوى دون حل إذا تأخر المواطن في المتابعة.', false),
            ],
        ];
    }

    protected function entrepreneurship(): array
    {
        return [
            'intro' => $this->p('برنامج لشباب كفر الشيخ لتحويل الأفكار إلى مشروعات صغيرة ناجحة ومستدامة، مع التركيز على الفرص المحلية في الزراعة والثروة السمكية والصناعات اليدوية.'),
            'modules' => [
                [
                    'title' => 'من الفكرة إلى الفرصة',
                    'lessons' => [
                        ['title' => 'اكتشاف الفرص المحلية', 'type' => 'text', 'minutes' => 12, 'body' => $this->p('ابدأ من مشكلة حقيقية يعاني منها الناس حولك. من أمثلة الفرص في المحافظة: تعبئة وتغليف الأسماك، وتدوير قش الأرز، والمنتجات اليدوية من البردي والخوص.')],
                        ['title' => 'نموذج العمل التجاري (Business Model Canvas)', 'type' => 'pdf', 'minutes' => 15],
                    ],
                    'quiz' => [
                        ['q' => 'نقطة البداية الأفضل لفكرة مشروع:', 'options' => ['مشكلة حقيقية لدى العملاء', 'تقليد أي مشروع ناجح', 'أكبر رأس مال ممكن'], 'answer' => 'مشكلة حقيقية لدى العملاء'],
                        $this->tf('تدوير قش الأرز يمكن أن يكون فرصة مشروع وحلًا بيئيًا.', true),
                        ['q' => 'نموذج العمل التجاري يوضح:', 'options' => ['كيف يخلق المشروع القيمة ويحقق الدخل', 'أسماء الموظفين فقط', 'ألوان الشعار'], 'answer' => 'كيف يخلق المشروع القيمة ويحقق الدخل'],
                    ],
                ],
                [
                    'title' => 'التمويل والتسويق',
                    'lessons' => [
                        ['title' => 'مصادر تمويل المشروعات الصغيرة', 'type' => 'text', 'minutes' => 12, 'body' => $this->ul('المدخرات الشخصية والشركاء', 'جهاز تنمية المشروعات المتوسطة والصغيرة ومتناهية الصغر', 'مبادرات البنوك للتمويل الميسر', 'حاضنات الأعمال')],
                        ['title' => 'جهاز تنمية المشروعات', 'type' => 'link', 'minutes' => 8, 'url' => 'https://www.msmeda.org.eg'],
                    ],
                    'quiz' => [
                        ['q' => 'من الجهات الداعمة للمشروعات الصغيرة في مصر:', 'options' => ['جهاز تنمية المشروعات', 'مصلحة الأرصاد', 'هيئة البريد فقط'], 'answer' => 'جهاز تنمية المشروعات'],
                        $this->tf('دراسة السوق خطوة يمكن تجاوزها في المشروعات الصغيرة.', false),
                        ['q' => 'التسويق الرقمي يساعد على:', 'options' => ['الوصول لعملاء أكثر بتكلفة أقل', 'زيادة التكاليف دائمًا', 'إلغاء الحاجة للمنتج'], 'answer' => 'الوصول لعملاء أكثر بتكلفة أقل'],
                    ],
                ],
                [
                    'title' => 'خطة العمل والعرض على المستثمرين',
                    'lessons' => [
                        ['title' => 'عناصر خطة العمل', 'type' => 'text', 'minutes' => 12, 'body' => $this->ul('الملخص التنفيذي', 'تحليل السوق والمنافسين', 'الخطة التسويقية', 'الخطة التشغيلية', 'التوقعات المالية ونقطة التعادل')],
                    ],
                    'quiz' => [],
                ],
            ],
            'final' => [
                ['q' => 'نقطة التعادل هي:', 'options' => ['تساوي الإيرادات مع التكاليف', 'أعلى ربح ممكن', 'بداية الخسارة'], 'answer' => 'تساوي الإيرادات مع التكاليف'],
                ['q' => 'الملخص التنفيذي يُكتب:', 'options' => ['في بداية خطة العمل', 'في نهاية العقد', 'على المنتج'], 'answer' => 'في بداية خطة العمل'],
                ['q' => 'تحليل المنافسين يساعد على:', 'options' => ['تمييز منتجك', 'نسخ الأسعار فقط', 'إغلاق المشروع'], 'answer' => 'تمييز منتجك'],
                ['q' => 'الحاضنات تقدم للمشروعات الناشئة:', 'options' => ['الدعم الفني والإرشاد', 'الضرائب', 'المخالفات'], 'answer' => 'الدعم الفني والإرشاد'],
                $this->tf('المشروعات الخضراء يمكن أن تكون مربحة.', true),
                $this->tf('لا حاجة لخطة تسويقية إذا كان المنتج جيدًا.', false),
            ],
        ];
    }
}
