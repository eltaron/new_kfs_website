<?php

return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'slug' => 'shield/roles',
        'navigation_sort' => -1,
        'navigation_badge' => true,
        'navigation_group' => true,
        'sub_navigation_position' => null,
        'is_globally_searchable' => false,
        'show_model_path' => true,
        'is_scoped_to_tenant' => false, // ✅ غيّرها لـ false لو مش بتستخدم Multi-tenancy
        'cluster' => null,
    ],

    'tenant_model' => null,

    'auth_provider_model' => [
        'fqcn' => 'App\\Models\\User',
    ],

    // ✅ دمج الإعدادات في مكان واحد (بدون تكرار)
    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,  // ✅ خليها false عشان تتجنب أخطاء الـ Gate
        'intercept_gate' => 'before', // ✅ القيمة لازم تكون string: 'before' أو 'after'
    ],

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    // ✅ إضافة مسارات كل الـ Panels (Admin + GIS)
    'discover_resources' => [
        'enabled' => true,
        'directories' => [
            app_path('Filament/Resources'),      // Admin Panel
            app_path('Filament/Gis/Resources'),  // ✅ GIS Panel
            app_path('Filament/Estidama/Resources'),  // ✅ Estidama Panel
        ],
    ],

    // تفعيل الصلاحيات المخصصة (Ad-hoc)
    'permission_prefixes' => [
        'resource' => [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ],
        'page' => 'page',
        'widget' => 'widget',
    ],

    // دعم اللغة العربية للعناوين
    'localized_permissions_enabled' => true,

    // ✅ حذف التكرار - الإعدادات مدمجة فوق في 'super_admin'

    'entities' => [
        'pages' => true,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => false,
    ],

    'generator' => [
        'option' => 'policies_and_permissions',
        'policy_directory' => 'Policies',
        'policy_namespace' => 'Policies',
    ],

    'exclude' => [
        'enabled' => true,
        'pages' => [
            'Dashboard',
        ],
        'widgets' => [
            'AccountWidget',
            'FilamentInfoWidget',
        ],
        'resources' => [],
    ],

    'discovery' => [
        'discover_all_resources' => true,
        'discover_all_widgets' => true,
        'discover_all_pages' => true,
    ],

    'register_role_policy' => [
        'enabled' => true,
    ],

    // ✅ إضافة مهمة: تحديد الـ Panels اللي Shield يشتغل فيها
    'panels' => [
        'admin',  // اسم الـ Panel الأول (من ->id('admin'))
        'gis',    // ✅ اسم الـ Panel التاني (من ->id('gis'))
        'estidama_panal',    // ✅ اسم الـ Panel التاني (من ->id('estidama'))
    ],
];
