<?php

namespace Modules\Syndicate\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * يرسل Web Push فقط إذا كان المستلم مشتركاً (يملك pushSubscriptions عبر HasPushSubscriptions)
 * ومفاتيح VAPID مضبوطة. خلاف ذلك لا يفعل شيئاً — حتى لا تفشل الإشعارات الأخرى.
 */
class WebPushIfSubscribedChannel
{
    public static function canReach(mixed $notifiable): bool
    {
        if (! class_exists(WebPushChannel::class) || blank(config('webpush.vapid.public_key'))) {
            return false;
        }

        if (! is_object($notifiable) || ! method_exists($notifiable, 'pushSubscriptions')) {
            return false;
        }

        try {
            return $notifiable->pushSubscriptions()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! static::canReach($notifiable) || ! method_exists($notification, 'toWebPush')) {
            return;
        }

        try {
            app(WebPushChannel::class)->send($notifiable, $notification);
        } catch (\Throwable $e) {
            Log::warning('[syndicate-webpush] ' . $e->getMessage());
        }
    }
}
