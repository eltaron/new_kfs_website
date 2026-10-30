<?php

namespace Modules\Estidama;

use App\Support\Modules\ModuleServiceProvider;

/**
 * استدامة — training programs and the LMS (enrollments, lessons, exams, certificates).
 * See docs/architecture.md.
 */
class EstidamaServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Estidama';
    }

    protected function movedModels(): array
    {
        return [
            Models\Badge::class,
            Models\Enrollment::class,
            Models\EstidamaEvent::class,
            Models\Exam::class,
            Models\ExamQuestion::class,
            Models\ExamSubmission::class,
            Models\LessonProgress::class,
            Models\ProgramLesson::class,
            Models\ProgramModule::class,
            Models\TrainingApplication::class,
            Models\TrainingCenter::class,
            Models\TrainingCertificate::class,
            Models\TrainingProgram::class,
            Models\PartnerLogo::class,
        ];
    }
}
