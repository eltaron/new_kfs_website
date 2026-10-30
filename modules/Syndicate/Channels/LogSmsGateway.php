<?php

namespace Modules\Syndicate\Channels;

use Illuminate\Support\Facades\Log;
use Modules\Syndicate\Contracts\SmsGateway;

/** المزود الافتراضي: يسجل الرسالة في السجل فقط (للتطوير لحين ربط مزود فعلي). */
class LogSmsGateway implements SmsGateway
{
    /** @var array<int, array{to:string,message:string}> للاختبارات */
    public static array $sent = [];

    public function send(string $to, string $message): void
    {
        static::$sent[] = ['to' => $to, 'message' => $message];
        Log::info('[syndicate-sms] ' . $to . ': ' . $message);
    }
}
