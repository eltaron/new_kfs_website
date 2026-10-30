<?php

namespace Modules\Syndicate\Contracts;

/**
 * واجهة مزود الرسائل القصيرة. اربط تنفيذاً فعلياً (Vodafone / Infobip / ...) في
 * SyndicateServiceProvider أو أي ServiceProvider عبر:
 *   $this->app->bind(SmsGateway::class, MyProviderGateway::class);
 */
interface SmsGateway
{
    public function send(string $to, string $message): void;
}
