<?php

namespace App\Notifications;

use App\Filament\Resources\UserResource; // <-- Import UserResource
use App\Models\User; // <-- Import User model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    // A public property to hold the new user
    public User $user;

    /**
     * Create a new notification instance.
     * We pass the new user to the constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     * We want to send it to the database to appear in Filament's notification bell.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Changed from 'mail' to 'database'
    }

    /**
     * Get the database representation of the notification.
     * This is what Filament will use.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            // Data that will be stored in the 'data' column of the notifications table
            'title' => 'مستخدم جديد قام بالتسجيل!',
            'body' => 'المستخدم ' . $this->user->name . ' (' . $this->user->email . ') قام بإنشاء حساب جديد.',
            'icon' => 'heroicon-o-user-plus',
            'icon_color' => 'success',

            // This will make the notification clickable
            'actions' => [
                [
                    'label' => 'عرض المستخدم',
                    'url' => UserResource::getUrl('edit', ['record' => $this->user]),
                    'should_open_in_new_tab' => true,
                ],
            ],
        ];
    }

    /**
     * (Optional) Get the mail representation of the notification.
     * If you also want to send an email.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->subject('New User Registration')
    //         ->line('A new user has registered on the platform:')
    //         ->line('Name: ' . $this->user->name)
    //         ->line('Email: ' . $this->user->email)
    //         ->action('View User Profile', UserResource::getUrl('edit', ['record' => $this->user]))
    //         ->line('Thank you!');
    // }
}
