<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

/**
 * Reads trusted proxies from config('security.trusted_proxies') (env
 * TRUSTED_PROXIES: comma-separated IPs/CIDRs, or "*"). Nothing is trusted by
 * default, so X-Forwarded-* headers cannot be spoofed to bypass throttling.
 */
class TrustProxies extends Middleware
{
    protected function proxies()
    {
        $configured = config('security.trusted_proxies');

        if (blank($configured)) {
            return parent::proxies();
        }

        return $configured === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $configured))));
    }
}
