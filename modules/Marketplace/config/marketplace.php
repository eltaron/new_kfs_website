<?php

return [

    /*
    |--------------------------------------------------------------------------
    | متجر ذوي الهمم — Marketplace settings
    |--------------------------------------------------------------------------
    | Values here are defaults. The commission rate and the per-vendor shipping
    | fee can be changed at runtime from /store-admin (stored in market_settings).
    */

    // Default commission (percent) taken from each sale. Disabled vendors: 0%.
    'default_commission_rate' => (float) env('MARKET_DEFAULT_COMMISSION', 0),

    // Flat shipping fee charged once per vendor in an order (EGP).
    'shipping_fee_per_vendor' => (float) env('MARKET_SHIPPING_FEE_PER_VENDOR', 0),

    // Allow "cash on delivery" at checkout. Off by default: e-Finance only.
    'cod_enabled' => (bool) env('MARKET_COD_ENABLED', false),

    // Stock reserved for an unpaid order is released after this many minutes.
    'reservation_minutes' => (int) env('MARKET_RESERVATION_MINUTES', 60),

    // Max quantity of one product per order line.
    'max_quantity_per_item' => 20,

    // Morph alias used for the unified payment service (route payments.pay {type}).
    'payable_alias' => 'market_order',

    // Roles allowed into the /store-admin panel (any of).
    'admin_roles' => ['super_admin', 'Admin', 'admin', 'مشرف المتجر'],

    // Roles notified about new vendor applications / products awaiting review.
    'notify_roles' => ['super_admin', 'مشرف المتجر'],

    // Private disk for disability / integrated-services card scans.
    'documents_disk' => 'local',

    // Public disk for logos and product images.
    'media_disk' => 'public',

    'per_page' => 12,

    // Kafr El-Sheikh centers (vendor location / shipping destination).
    'centers' => [
        'كفر الشيخ', 'دسوق', 'فوه', 'مطوبس', 'البرلس', 'الحامول', 'بيلا', 'الرياض', 'سيدي سالم', 'قلين',
    ],
];
