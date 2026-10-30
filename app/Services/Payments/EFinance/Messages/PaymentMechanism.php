<?php

namespace App\Services\Payments\EFinance\Messages;

/**
 * Payment mechanism block. We always send "NotSet" (the citizen chooses
 * card / wallet / Fawry-like channel on the gateway page).
 */
final class PaymentMechanism
{
    public function __construct(
        public string $type = 'NotSet',
        public string $mechanismType = 'NotSet',
    ) {
    }

    public function isChannel(): bool
    {
        return $this->type === 'Channel';
    }
}
