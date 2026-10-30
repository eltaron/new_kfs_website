<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Citizen login status gate
    |--------------------------------------------------------------------------
    | Users whose `status` column is in this list cannot sign in through the
    | public /login form. The users table uses: pending | approved | rejected.
    | "inactive" is also blocked for forward compatibility.
    | Users holding any staff role (anything other than the roles listed in
    | `citizen_roles`) are only blocked for rejected/inactive, never for pending,
    | so staff accounts created without an explicit status are not locked out.
    */
    'login' => [
        'block_pending' => (bool) env('AUTH_BLOCK_PENDING_LOGIN', true),
        'blocked_statuses' => ['rejected', 'inactive'],
        'citizen_roles' => ['citizen'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-factor authentication (Google Authenticator / TOTP)
    |--------------------------------------------------------------------------
    */
    'two_factor' => [
        // Shown as the issuer in authenticator apps.
        'issuer' => env('TWO_FACTOR_ISSUER', 'محافظة كفر الشيخ'),

        // When true, every staff user (any role other than citizen) must set
        // up 2FA before using the Filament panels. Default off.
        'require_for_staff' => (bool) env('TWO_FACTOR_REQUIRE_FOR_STAFF', false),

        // Number of 30s windows tolerated on either side of "now".
        'window' => 1,

        'recovery_codes' => 8,

        // Pending (pre-auth) challenge lifetime, in minutes.
        'challenge_ttl' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security headers
    |--------------------------------------------------------------------------
    */
    // Comma-separated IPs/CIDRs of the reverse proxy / load balancer, or "*".
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    'headers' => [
        'enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),
        'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

        // Content-Security-Policy is sent in Report-Only mode until it has
        // been validated against real traffic. Set CSP_ENFORCE=true to enforce.
        'csp_enforce' => (bool) env('CSP_ENFORCE', false),
        'csp_report_uri' => env('CSP_REPORT_URI'),
    ],
];
