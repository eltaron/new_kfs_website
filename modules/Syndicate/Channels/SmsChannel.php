<?php

namespace Modules\Syndicate\Channels;

use Illuminate\Notifications\Notification;
use Modules\Syndicate\Contracts\SmsGateway;

/** قناة إشعارات SMS تعتمد على واجهة SmsGateway القابلة للاستبدال. */
class SmsChannel
{
    public function __construct(protected SmsGateway $gateway)
    {
    }

    public function send(mixed $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor('sms', $notification) ?: ($notifiable->phone ?? null);

        if (blank($to) || ! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        if (filled($message)) {
            $this->gateway->send((string) $to, (string) $message);
        }
    }
}
