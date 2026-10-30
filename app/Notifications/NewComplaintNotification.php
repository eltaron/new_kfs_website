<?php

namespace App\Notifications;

use Modules\Citizen\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\AndroidConfig;
use NotificationChannels\Fcm\Resources\AndroidFcmOptions;
use NotificationChannels\Fcm\Resources\ApnsConfig;
use NotificationChannels\Fcm\Resources\ApnsFcmOptions;

class NewComplaintNotification extends Notification
{
    use Queueable;

    public function __construct(public Complaint $complaint) {}

    public function via(object $notifiable): array
    {
        $channels = ['database']; // دايمًا Filament DB notification

        // لو عنده FCM token يبعت push كمان
        if ($notifiable->fcm_token) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    // Filament DB Notification
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('شكوى جديدة مقدمة')
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('warning')
            ->body("الموضوع: {$this->complaint->subject} - مرسلة من: " . ($this->complaint->name ?? 'مجهول'))
            ->actions([
                Action::make('view')
                    ->label('عرض الشكوى')
                    ->url("/admin/complaints/{$this->complaint->id}")
            ])
            ->getDatabaseMessage();
    }

    // Push Notification للموبايل
    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->setData([
                'complaint_id' => (string) $this->complaint->id,
                'type'         => 'new_complaint',
            ])
            ->setNotification(
                \NotificationChannels\Fcm\Resources\Notification::create()
                    ->setTitle('شكوى جديدة مقدمة 🔔')
                    ->setBody("الموضوع: {$this->complaint->subject}\nمن: " . ($this->complaint->name ?? 'مجهول'))
            )
            ->setAndroid(
                AndroidConfig::create()
                    ->setFcmOptions(AndroidFcmOptions::create()->setAnalyticsLabel('complaint'))
            )
            ->setApns(
                ApnsConfig::create()
                    ->setFcmOptions(ApnsFcmOptions::create()->setAnalyticsLabel('complaint'))
            );
    }

    // لازم تحدد الـ FCM Token
    public function routeNotificationForFcm(): string
    {
        return $this->fcm_token;
    }
}