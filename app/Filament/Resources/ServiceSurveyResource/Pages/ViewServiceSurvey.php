<?php

namespace App\Filament\Resources\ServiceSurveyResource\Pages;

use App\Filament\Resources\ServiceSurveyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceSurvey extends ViewRecord
{
    protected static string $resource = ServiceSurveyResource::class;

    // Mark as reviewed automatically when viewed
    protected function afterFill(): void
    {
        if (!$this->record->is_reviewed) {
            $this->record->update(['is_reviewed' => true]);
        }
    }
}
