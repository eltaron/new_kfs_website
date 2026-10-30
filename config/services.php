<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Unified e-Finance payments (App\Services\Payments). No credential has a
    // default on purpose: an unconfigured gateway refuses to initiate payments.
    'efinance' => [
        // 'efinance' (real gateway) | 'fake' (tests / local simulation, never production)
        'driver'           => env('EFINANCE_DRIVER') ?: (env('APP_ENV') === 'testing' ? 'fake' : 'efinance'),
        'sender_id'        => env('EFINANCE_SENDER_ID'),
        'sender_name'      => env('EFINANCE_SENDER_NAME', 'محافظة كفر الشيخ'),
        'password'         => env('EFINANCE_PASSWORD'),
        'service_code'     => env('EFINANCE_SERVICE_CODE'),
        'settlement_code'  => env('EFINANCE_SETTLEMENT_CODE'),
        'url'              => env('EFINANCE_GATEWAY_URL'),
        'certificate_path' => env('EFINANCE_CERTIFICATE_PATH') ?: storage_path('app/efinance/InternetPaymentCrt.cer'),
        'server_ip'        => env('EFINANCE_SERVER_IP'),
        'currency'         => env('EFINANCE_CURRENCY') ?: '818',
        'expiry_hours'     => (int) (env('EFINANCE_EXPIRY_HOURS') ?: 24),
        // Violation fines (RemovalOrder) are payable only when explicitly enabled.
        'removal_order_payments' => (bool) env('EFINANCE_REMOVAL_ORDER_PAYMENTS', false),
    ],
    'fcm' => [
        'project_id'  => env('FCM_PROJECT_ID'),
        'credentials' => env('FCM_CREDENTIALS'),
    ],
];
