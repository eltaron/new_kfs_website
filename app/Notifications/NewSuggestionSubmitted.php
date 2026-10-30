<?php

namespace App\Notifications;

use App\Filament\Resources\SuggestionResource;
use Modules\Citizen\Models\Suggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSuggestionSubmitted extends Notification
{
    use Queueable;
    public Suggestion $suggestion;

    public function __construct(Suggestion $suggestion)
    {
        $this->suggestion = $suggestion;
    }
    public function via(object $notifiable): array
    {
        return ['database'];
    }
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'مقترح جديد تم تقديمه',
            'body' => "تم استلام مقترح جديد من {$this->suggestion->name} بعنوان '{$this->suggestion->subject}'.",
            'icon' => 'heroicon-o-light-bulb',
            'icon_color' => 'success',
            'actions' => [
                ['label' => 'عرض المقترح', 'url' => SuggestionResource::getUrl('edit', ['record' => $this->suggestion]),]
            ],
        ];
    }
}
