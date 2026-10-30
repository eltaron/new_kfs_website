<?php

namespace Modules\Citizen;

use App\Support\Modules\ModuleServiceProvider;

/**
 * خدمات المواطن — services & submissions, complaints, suggestions, emergency, contact, surveys, citizen auth/dashboard, lookup.
 * See docs/architecture.md.
 */
class CitizenServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Citizen';
    }

    protected function movedModels(): array
    {
        return [
            Models\Service::class,
            Models\ServiceSubmission::class,
            Models\ServiceSurvey::class,
            Models\Complaint::class,
            Models\Suggestion::class,
            Models\EmergencyReport::class,
            Models\ContactMessage::class,
        ];
    }
}
