<?php

namespace Modules\Marketplace\Notifications;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Database (Filament bell) + mail notification for marketplace events.
 *
 * Kinds: vendor_application, vendor_status, product_submitted, product_moderated, new_order (vendor),
 * order_confirmed (buyer), order_status (buyer).
 */
class MarketplaceNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $kind,
        public string $title,
        public string $body,
        public ?string $url = null,
        public string $actionLabel = 'عرض التفاصيل',
        public string $color = 'info',
        public string $icon = 'heroicon-o-shopping-bag',
        public array $context = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (filled($notifiable->email ?? null) && ! str_ends_with((string) $notifiable->email, '.invalid')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon)
            ->color($this->color);

        if ($this->url) {
            $notification->actions([
                Action::make('view')->label($this->actionLabel)->url($this->url)->markAsRead(),
            ]);
        }

        return $notification->getDatabaseMessage() + ['kind' => $this->kind, 'context' => $this->context];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->title.' — متجر ذوي الهمم')
            ->greeting('مرحباً '.($notifiable->name ?? ''))
            ->line($this->body);

        if ($this->url) {
            $mail->action($this->actionLabel, $this->url);
        }

        return $mail->salutation('متجر ذوي الهمم — محافظة كفر الشيخ');
    }
}
