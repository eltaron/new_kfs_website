<?php

namespace App\Notifications;

use App\Filament\Estidama\Resources\TrainingApplicationResource;
use Modules\Estidama\Models\TrainingApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewTrainingApplication extends Notification implements ShouldQueue
{
    use Queueable;

    public TrainingApplication $application;

    /**
     * Create a new notification instance.
     *
     * @param \Modules\Estidama\Models\TrainingApplication $application
     */
    public function __construct(TrainingApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  object  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Store in the database for Filament
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  object  $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'طلب تسجيل جديد في برنامج تدريبي',
            'body'  => "قام '{$this->application->applicant_name}' بالتقديم على برنامج '{$this->application->trainingProgram->title}'.",
            'icon'  => 'heroicon-o-inbox-stack', // Icon representing an inbox item
            'icon_color' => 'warning', // Orange color for pending actions

            'actions' => [
                [
                    'label' => 'مراجعة الطلب',
                    // Use `edit` because that's where the status is changed
                    'url'   => TrainingApplicationResource::getUrl('view', ['record' => $this->application], panel: 'estidama_panal'),
                    'should_open_in_new_tab' => true,
                ],
            ],
        ];
    }
}
