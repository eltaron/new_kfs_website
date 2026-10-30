<x-filament-panels::page>
    <div class="space-y-8">

        {{-- Hero Section --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-900 via-primary-800 to-primary-700 dark:from-gray-900 dark:via-gray-800 dark:to-gray-700 p-8 md:p-12">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4"></div>
            <div class="relative">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                        <x-filament::icon icon="heroicon-o-question-mark-circle" class="w-7 h-7 text-white" />
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">مركز المساعدة</h1>
                </div>
                <p class="text-white/80 text-lg max-w-2xl leading-relaxed">
                    مرحباً بك في مركز المساعدة الخاص ببوابة كفر الشيخ الجيومكانية. هنا ستجد كل ما تحتاج من معلومات ودعم لمساعدتك في أداء مهامك بكفاءة.
                </p>
            </div>
        </div>

        {{-- Contact Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="absolute inset-0 bg-gradient-to-br from-primary-50/50 to-transparent dark:from-primary-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/50 mb-4">
                        <x-filament::icon icon="heroicon-o-envelope" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">البريد الإلكتروني</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ltr:text-left rtl:text-right" dir="ltr">support@kfs.gov.eg</p>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="absolute inset-0 bg-gradient-to-br from-primary-50/50 to-transparent dark:from-primary-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/50 mb-4">
                        <x-filament::icon icon="heroicon-o-phone" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">الهاتف</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400" dir="ltr">047-XXX-XXXX</p>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="absolute inset-0 bg-gradient-to-br from-primary-50/50 to-transparent dark:from-primary-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/50 mb-4">
                        <x-filament::icon icon="heroicon-o-clock" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">ساعات العمل</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">من 9 صباحاً حتى 3 مساءً</p>
                </div>
            </div>
        </div>

        {{-- FAQ Section --}}
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <x-filament::icon icon="heroicon-o-book-open" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">الأسئلة الشائعة</h2>
            </div>

            <div class="space-y-3" x-data="{ active: null }">
                @php
                    $faqs = [
                        [
                            'q' => 'كيف أغير كلمة المرور؟',
                            'a' => 'يمكنك تغيير كلمة المرور بالذهاب إلى قائمة الملف الشخصي من أعلى اليمين، ثم اختيار "تغيير كلمة المرور". أدخل كلمة المرور الحالية ثم كلمة المرور الجديدة مرتين للتأكيد.'
                        ],
                        [
                            'q' => 'كيف أضيف قرار إزالة جديد؟',
                            'a' => 'من القائمة الجانبية اختر "إضافة قرار إزالة جديد" ثم اتبع خطوات المعالج. ستحتاج إلى إدخال بيانات المخالفة، موقع المخالف، وصورة المخالفة مع إحداثيات GPS.'
                        ],
                        [
                            'q' => 'ماذا أفعل إذا لم يتم تحديد الموقع GPS تلقائياً؟',
                            'a' => 'تأكد من تفعيل خدمة GPS على جهازك ومنح الإذن للمتصفح بالوصول إلى موقعك. يمكنك أيضاً إدخال الإحداثيات يدوياً في الحقول المخصصة.'
                        ],
                        [
                            'q' => 'كيف أتابع حالة قرار الإزالة؟',
                            'a' => 'يمكنك متابعة حالة قرارات الإزالة الخاصة بك من خلال صفحة "قرارات الإزالة". تظهر الحالة الحالية لكل قرار ويمكنك معرفة الخطوة التالية في سير العمل.'
                        ],
                        [
                            'q' => 'كيف أرفع محضر المخالفة PDF؟',
                            'a' => 'من قائمة "قرارات الإزالة الخاصة بي"، اختر القرار المطلوب ثم انقر على أيقونة رفع المحضر. اختر ملف PDF الخاص بالمحضر ثم احفظ.'
                        ],
                        [
                            'q' => 'من يمكنه رؤية قرارات الإزالة؟',
                            'a' => 'كل مستخدم يرى القرارات حسب صلاحياته. مدير المركز يرى كل القرارات في مركزه، فني التنظيم يرى القرارات المحالة إليه، والعضو الميداني يرى القرارات التي قام بإضافتها.'
                        ],
                    ];
                @endphp

                @foreach ($faqs as $i => $faq)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200"
                         x-bind:class="{ 'border-primary-300 dark:border-primary-600 shadow-sm': active === {{ $i }} }">
                        <button x-on:click="active = active === {{ $i }} ? null : {{ $i }}"
                                class="w-full flex items-center justify-between gap-3 px-5 py-4 text-right bg-gray-50/50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors duration-150">
                            <span class="font-medium text-gray-900 dark:text-gray-100 text-sm md:text-base">{{ $faq['q'] }}</span>
                            <x-filament::icon icon="heroicon-m-chevron-down"
                                              class="w-5 h-5 text-gray-400 dark:text-gray-500 transition-transform duration-200 shrink-0"
                                              x-bind:class="{ 'rotate-180': active === {{ $i }} }" />
                        </button>
                        <div x-show="active === {{ $i }}"
                             x-transition:enter="transition-all duration-300 ease-out"
                             x-transition:enter-start="opacity-0 max-h-0"
                             x-transition:enter-end="opacity-100 max-h-96"
                             x-transition:leave="transition-all duration-200 ease-in"
                             x-transition:leave-start="opacity-100 max-h-96"
                             x-transition:leave-end="opacity-0 max-h-0"
                             class="overflow-hidden">
                            <div class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400 leading-relaxed border-t border-gray-100 dark:border-gray-700/50">
                                {{ $faq['a'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <x-filament::icon icon="heroicon-o-link" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">روابط سريعة</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <a href="/gis/add-removal-order"
                   class="group flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 hover:border-primary-300 dark:hover:border-primary-600">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 group-hover:scale-110 transition-transform duration-200">
                        <x-filament::icon icon="heroicon-o-plus-circle" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                        إضافة قرار إزالة
                    </span>
                </a>
                <a href="/gis/my-removal-orders"
                   class="group flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 hover:border-amber-300 dark:hover:border-amber-600">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 group-hover:scale-110 transition-transform duration-200">
                        <x-filament::icon icon="heroicon-o-document-text" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                        قرارات الإزالة الخاصة بي
                    </span>
                </a>
                <a href="/gis/workflow-dashboard"
                   class="group flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-300 dark:hover:border-emerald-600">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 group-hover:scale-110 transition-transform duration-200">
                        <x-filament::icon icon="heroicon-o-presentation-chart-bar" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                        لوحة سير العمل
                    </span>
                </a>
                <a href="/gis/profile"
                   class="group flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 hover:border-purple-300 dark:hover:border-purple-600">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 group-hover:scale-110 transition-transform duration-200">
                        <x-filament::icon icon="heroicon-o-user-circle" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                        الملف الشخصي
                    </span>
                </a>
            </div>
        </div>

        {{-- System Info --}}
        <div class="rounded-xl bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-800/30 border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700">
                        <x-filament::icon icon="heroicon-o-information-circle" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">بوابة كفر الشيخ الجيومكانية</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">نظام إدارة قرارات الإزالة — الإصدار 1.0</p>
                    </div>
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500">
                    © {{ date('Y') }} جميع الحقوق محفوظة — محافظة كفر الشيخ
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
