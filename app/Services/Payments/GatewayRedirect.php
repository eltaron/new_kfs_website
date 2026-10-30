<?php

namespace App\Services\Payments;

/**
 * What the browser must POST to the gateway, plus the per-request key
 * material we need to keep to decrypt the callback.
 */
final class GatewayRedirect
{
    /**
     * @param  array<string,string>  $fields
     */
    public function __construct(
        public readonly string $url,
        public readonly array $fields,
        public readonly ?string $secret = null,
    ) {
    }
}
