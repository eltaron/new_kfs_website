<?php

namespace Modules\SiteValidity\Notifications;

use Filament\Notifications\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvSettings;

/**
 * إشعار موحد لخدمة صلاحية الموقع: قاعدة البيانات (جرس لوحة /site-validity + لوحة المواطن) والبريد.
 * $audience: staff | citizen — يحدد رابط «عرض».
 */
class SiteValidityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SiteValidityRequest $request,
        public string $title,
        public string $body,
        public string $audience = 'citizen',
        public string $level = 'info',
    ) {
    }

    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            'database',
            SvSettings::bool('notify_mail') && filled($notifiable->email ?? null) ? 'mail' : null,
        ]));
    }

    public function url(): string
    {
        return $this->audience === 'staff'
            ? url('/site-validity/requests/' . $this->request->getKey())
            : route('site-validity.requests.show', $this->request);
    }

    public function toDatabase(object $notifiable): array
    {
        $icon = match ($this->level) {
            'danger' => 'heroicon-o-exclamation-triangle',
            'success' => 'heroicon-o-check-badge',
            'warning' => 'heroicon-o-clock',
            default => 'heroicon-o-map-pin',
        };

        $message = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon($icon)
            ->status($this->level)
            ->actions([FilamentAction::make('view')->label('عرض الطلب')->url($this->url())->markAsRead()])
            ->getDatabaseMessage();

        return array_merge($message, [
            'message' => $this->body,
            'url' => $this->url(),
            'site_validity' => [
                'request_id' => $this->request->getKey(),
                'number' => $this->request->number,
                'status' => $this->request->status,
                'audience' => $this->audience,
            ],
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->title . ' — ' . $this->request->number)
            ->greeting('السلام عليكم ورحمة الله')
            ->line($this->body)
            ->line('رقم الطلب: ' . $this->request->number);

        if ($this->level === 'danger') {
            $mail->error();
        }

        return $mail->action('عرض الطلب', $this->url())
            ->salutation('بوابة محافظة كفر الشيخ — خدمة طلب صلاحية الموقع');
    }
}
