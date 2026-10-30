<?php

/*
| خدمة «طلب صلاحية الموقع» — إعدادات ثابتة (الإعدادات القابلة للتعديل من اللوحة في جدول sv_settings).
*/
return [
    // اسم الخدمة في روابط الدفع: route('payments.pay', ['type' => 'site_validity_request', 'id' => ...])
    'payable_alias' => 'site_validity_request',
    // رسوم الاستيفاء: route('payments.pay', ['type' => 'site_validity_completion', 'id' => $completion->id])
    'completion_payable_alias' => 'site_validity_completion',

    // القرص الخاص (storage/app/private) — لا تُخدم الملفات إلا عبر مسار مُصرّح.
    'disk' => 'local',
    'directory' => 'site-validity',

    // ملفات المواطن (الكروكي، عقد الملكية، صورة البطاقة، ملفات الاستيفاء).
    'citizen_upload' => [
        'extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_kb' => 5120,
    ],

    // الملف النهائي الذي يرفعه مهندس التنظيم.
    'final_upload' => [
        'extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'dwg', 'dxf', 'zip'],
        'max_kb' => 20480,
    ],

    // ملفات CAD/أرشيف: امتدادات مسموح بها بشرط مرورها على SafeUpload.
    'staff_extensions_whitelist' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'dwg', 'dxf', 'zip', 'kml', 'kmz'],

    // الرسوم الافتراضية عند غياب رسوم نوع الخدمة.
    'default_fee' => env('SITE_VALIDITY_DEFAULT_FEE', 500),

    // خريطة الطلبات (لوحة الخدمة ← «خريطة الطلبات»).
    'map' => [
        // حدود المراكز (تقريبية): public/geo/kafr-elsheikh-centres.geojson — geoBoundaries، CC BY 4.0
        'boundaries' => 'geo/kafr-elsheikh-centres.geojson',
        'center' => [31.27, 30.86],
        'zoom' => 10,
        // مراكز ليس لها حدود منفصلة في ملف الحدود: تظهر كنقطة (إحداثيات تقريبية — صحّحها هنا عند الحاجة).
        'points' => [
            'بلطيم' => [31.5547, 31.0889],
            'مصيف بلطيم' => [31.5930, 31.1510],
            'سيدى غازى' => [31.1980, 30.8060],
            'مسير' => [31.1530, 30.8800],
        ],
    ],
];
