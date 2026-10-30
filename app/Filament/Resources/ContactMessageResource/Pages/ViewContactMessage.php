<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    // Automatically mark the message as read when it's viewed
    protected function afterFill(): void
    {
        $this->record->update(['is_read' => true]);
    }
}
