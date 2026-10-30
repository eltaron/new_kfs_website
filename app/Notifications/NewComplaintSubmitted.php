<?php
// app/Notifications/NewComplaintSubmitted.php
namespace App\Notifications;

use App\Filament\Resources\ComplaintResource;
use Modules\Citizen\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewComplaintSubmitted extends Notification
{
    use Queueable;
    public Complaint $complaint;

    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
    }
    public function via(object $notifiable): array
    {
        return ['database'];
    }
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'شكوى جديدة تم تقديمها',
            'body' => "تم استلام شكوى جديدة من {$this->complaint->name} بعنوان '{$this->complaint->subject}'.",
            'icon' => 'heroicon-o-exclamation-triangle',
            'icon_color' => 'danger',
            'actions' => [
                ['label' => 'عرض الشكوى', 'url' => ComplaintResource::getUrl('edit', ['record' => $this->complaint]),]
            ],
        ];
    }
}
