<x-filament-panels::page>
    <div class="space-y-6">

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1e272e] via-[#2d3a42] to-[#1a1a2e] dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-8 text-white">
            <div class="absolute top-0 right-0 w-72 h-72 bg-blue-500/10 rounded-full translate-x-1/3 -translate-y-1/3 blur-3xl"></div>
            <div class="relative z-10">
                <h2 class="text-3xl font-black tracking-tight">التقرير المالي الشامل</h2>
                <p class="text-white/60 mt-2 text-sm">إحصائيات وتفاصيل التحصيلات المالية</p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5 transition-all hover:shadow-lg">
                <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي التحصيلات</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_paid'], 2) }} ج.م</h3>
            </div>
            <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5 transition-all hover:shadow-lg">
                <p class="text-sm text-gray-500 dark:text-gray-400">المعاملات المكتملة</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['total_count'] }}</h3>
            </div>
            <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5 transition-all hover:shadow-lg">
                <p class="text-sm text-gray-500 dark:text-gray-400">قيد الانتظار</p>
                <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $stats['pending_count'] }}</h3>
            </div>
            <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5 transition-all hover:shadow-lg">
                <p class="text-sm text-gray-500 dark:text-gray-400">تحصيلات اليوم</p>
                <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ number_format($stats['today'], 2) }} ج.م</h3>
            </div>
        </div>

        {{-- By Center --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-6">
            <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4">التوزيع حسب المركز</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-right">
                    <thead>
                        <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-gray-100 dark:border-gray-800">
                            <th class="pb-3 font-medium">المركز</th>
                            <th class="pb-3 font-medium">عدد المعاملات</th>
                            <th class="pb-3 font-medium">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800/30">
                        @forelse ($stats['by_center'] as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-colors">
                                <td class="py-3 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $item['center'] }}</td>
                                <td class="py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item['count'] }}</td>
                                <td class="py-3 text-sm font-bold text-gray-900 dark:text-white">{{ number_format($item['total'], 2) }} ج.م</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-gray-400">لا توجد بيانات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detailed Table --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-6">
            <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4">تفاصيل المعاملات</h4>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>