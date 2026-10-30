<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. شبكة البطاقات الإحصائية (Stats Grid) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- بطاقة إجمالي الطلبات --}}
            <div
                class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">إجمالي الطلبات</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white">
                        {{ number_format($stats['total_submissions']) }}</h3>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                    <x-heroicon-o-document-text class="w-8 h-8 text-blue-600" />
                </div>
            </div>

            {{-- بطاقة المحصلات المالية --}}
            <div
                class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">المحصلات الرقمية</p>
                    <h3 class="text-2xl font-black text-green-600">{{ number_format($stats['paid_amount'], 2) }} <small
                            class="text-xs">ج.م</small></h3>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                    <x-heroicon-o-banknotes class="w-8 h-8 text-green-600" />
                </div>
            </div>

            {{-- بطاقة المهام المعلقة --}}
            <div
                class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center justify-between border-r-4 border-r-amber-500">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">قيد المعاينة</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ $stats['pending_tasks'] }}</h3>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                    <x-heroicon-o-clock class="w-8 h-8 text-amber-600" />
                </div>
            </div>

            {{-- بطاقة قرارات الإزالة --}}
            <div
                class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center justify-between border-r-4 border-r-red-500">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">قرارات الإزالة</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ $stats['removal_orders'] }}</h3>
                </div>
                <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                    <x-heroicon-o-trash class="w-8 h-8 text-red-600" />
                </div>
            </div>
        </div>

        {{-- 2. قسم التحليل البياني والتوزيع --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- قائمة توزيع الإزالات حسب المراكز --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <h4 class="text-lg font-bold mb-4 flex items-center">
                    <x-heroicon-m-map class="w-5 h-5 me-2 text-gold" /> توزيع الإزالات حسب المراكز
                </h4>
                <div class="space-y-3">
                    @foreach ($removalsByCenter as $rem)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ $rem->center }}</span>
                            <span
                                class="px-3 py-1 bg-navy text-white dark:bg-gray-700 rounded-full text-xs">{{ $rem->total }}
                                قرار</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- قائمة أحدث المعاملات --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <h4 class="text-lg font-bold mb-4 flex items-center">
                    <x-heroicon-m-bolt class="w-5 h-5 me-2 text-gold" /> أحدث معاملات الخدمات المكانية
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-right">
                        <thead>
                            <tr class="text-gray-400 text-xs border-b border-gray-100 dark:border-gray-700">
                                <th class="pb-2">المواطن</th>
                                <th class="pb-2">الخدمة</th>
                                <th class="pb-2">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @foreach ($recentSubmissions as $sub)
                                <tr>
                                    <td class="py-3 text-sm font-bold">{{ $sub->user->name }}</td>
                                    <td class="py-3 text-sm text-gray-500">{{ $sub->subService->name }}</td>
                                    <td class="py-3">
                                        <span
                                            class="px-2 py-1 text-[10px] rounded-full {{ $sub->status == 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $sub->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 3. تذييل الصفحة - خيارات الطباعة --}}
        <div class="flex justify-end gap-3 pt-4">
            <x-filament::button color="gray" icon="heroicon-m-printer" onclick="window.print()">
                طباعة الملخص الحالي
            </x-filament::button>
            <x-filament::button color="warning" icon="heroicon-m-arrow-down-tray">
                تصدير تقرير PDF مفصل
            </x-filament::button>
        </div>

    </div>

    <style>
        .text-gold {
            color: #e1b12c;
        }

        .bg-navy {
            background-color: #1e272e;
        }

        @media print {

            .site-header,
            .filament-main-sidebar,
            .filament-main-topbar,
            .flex.justify-end {
                display: none !important;
            }

            .p-6 {
                border: 1px solid #eee !important;
                box-shadow: none !important;
            }
        }
    </style>
</x-filament-panels::page>
