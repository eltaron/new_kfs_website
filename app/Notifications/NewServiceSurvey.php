<?php

namespace App\Notifications;

use App\Filament\Resources\ServiceSurveyResource; //  <-- استدعِ الـ Resource
use Modules\Citizen\Models\ServiceSurvey; // <-- استدعِ الموديل
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewServiceSurvey extends Notification implements ShouldQueue // Added ShouldQueue for performance
{
    use Queueable;

    // A public property to hold the survey data
    public ServiceSurvey $survey;

    /**
     * Create a new notification instance.
     * We pass the new survey record to the constructor.
     * @param \Modules\Citizen\Models\ServiceSurvey $survey
     */
    public function __construct(ServiceSurvey $survey)
    {
        $this->survey = $survey;
    }

    /**
     * Get the notification's delivery channels.
     * We want to send it to the database to appear in Filament's notification bell.
     *
     * @param  object  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // This tells Laravel to store the notification in the DB
    }

    /**
     * Get the database representation of the notification.
     * This is what Filament will use to display the notification.
     *
     * @param  object  $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            // Data that will be stored in the 'data' column of the notifications table
            'title' => 'تقييم جديد للخدمات',
            'body'  => "تم استلام تقييم جديد لـ '{$this->survey->center_name}' من قبل '{$this->survey->name}'.",
            'icon'  => 'heroicon-o-star', // A star icon for ratings
            'icon_color' => 'primary', // Use the primary theme color (gold)

            // This will make the notification clickable and take the user to the survey details page
            'actions' => [
                [
                    'label' => 'عرض التقييم',
                    // Generates the correct URL to view the survey in the admin panel
                    'url'   => ServiceSurveyResource::getUrl('edit', ['record' => $this->survey]),
                    // Optional: opens the link in a new tab
                    'should_open_in_new_tab' => true,
                ],
            ],
        ];
    }
}
