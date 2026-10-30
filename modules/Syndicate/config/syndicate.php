<?php

/*
|--------------------------------------------------------------------------
| تكامل نقابة المهندسين — شهادات الإشراف وإلغاء الإشراف
|--------------------------------------------------------------------------
*/

return [

    'governorate' => 'كفر الشيخ',

    // المراكز والمدن التابعة للمحافظة (تستخدم في نماذج تسجيل المشروعات).
    'centers' => [
        'كفر الشيخ', 'دسوق', 'فوه', 'مطوبس', 'بيلا', 'الحامول',
        'سيدي سالم', 'قلين', 'الرياض', 'البرلس', 'مصيف بلطيم',
    ],

    // بادئات الترقيم: PREFIX-YYYY-000123
    'numbering' => [
        'project' => 'SYN-PRJ',
        'supervision_request' => 'SYN-REQ',
        'cancellation_request' => 'SYN-CRQ',
        'supervision_certificate' => 'SYN-SUP',
        'cancellation_certificate' => 'SYN-CAN',
        'pad' => 6,
    ],

    'roles' => [
        'syndicate' => 'نقابة المهندسين',
        'council' => 'مجلس المدينة',
        // أدوار إضافية موجودة مسبقاً يسمح لها بالدخول إلى لوحة مجلس المدينة (/council).
        'council_extra' => [
            'مدير الادارة الهندسية',
            'مهندس التنظيم',
            'مدير التنظيم',
            'رئيس المركز والمدينة',
            'مدير الوحدة الفرعية',
        ],
        'admins' => ['super_admin', 'Admin'],
    ],

    'api' => [
        // قدرة التوكن المطلوبة على كل نقاط واجهة البرمجة.
        'ability' => 'syndicate',
        // قائمة عناوين IP / CIDR المسموح بها، مفصولة بفواصل. فارغة = بدون تقييد (يُنصح بتعبئتها في الإنتاج).
        'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('SYNDICATE_API_ALLOWED_IPS', ''))))),
        // عدد الطلبات المسموح بها في الدقيقة لكل توكن.
        'rate_limit_per_minute' => (int) env('SYNDICATE_API_RATE_LIMIT', 60),
        // مدة الاحتفاظ بمفاتيح Idempotency (ساعات).
        'idempotency_ttl_hours' => (int) env('SYNDICATE_API_IDEMPOTENCY_TTL', 24),
        // تسجيل جسم الطلب في سجل التدقيق (بعد حذف الحقول الحساسة).
        'log_request_bodies' => (bool) env('SYNDICATE_API_LOG_BODIES', true),
    ],

    // إشعارات صادرة (Webhook) إلى نظام النقابة عند تسجيل طلب جديد أو تغيير حالة.
    'webhook' => [
        'enabled' => (bool) env('SYNDICATE_WEBHOOK_ENABLED', false),
        'url' => env('SYNDICATE_WEBHOOK_URL'),
        'secret' => env('SYNDICATE_WEBHOOK_SECRET'),
        'timeout' => (int) env('SYNDICATE_WEBHOOK_TIMEOUT', 10),
    ],

    'notifications' => [
        // قنوات الإرسال: قاعدة البيانات + البريد + SMS (عبر واجهة SmsGateway) + Web Push عند الاشتراك.
        'mail' => (bool) env('SYNDICATE_NOTIFY_MAIL', true),
        'sms' => (bool) env('SYNDICATE_NOTIFY_SMS', true),
        'webpush' => (bool) env('SYNDICATE_NOTIFY_WEBPUSH', true),
        // بريد مجلس المدينة لاستلام إشعارات إيقاف الأعمال (مفصولة بفواصل).
        'council_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('SYNDICATE_COUNCIL_EMAILS', ''))))),
    ],

    'sms' => [
        // log = تسجيل الرسائل في ملف السجل فقط. يمكن ربط مزود فعلي بتنفيذ Modules\Syndicate\Contracts\SmsGateway.
        'driver' => env('SYNDICATE_SMS_DRIVER', 'log'),
    ],
];
