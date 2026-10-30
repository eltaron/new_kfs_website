<x-filament-panels::page>
    <div class="space-y-5">

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1e272e] via-[#2d3a42] to-[#1a1a2e] dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-8 text-white">
            <div class="absolute top-0 left-0 w-64 h-64 bg-emerald-500/10 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
            <div class="relative z-10">
                <h2 class="text-3xl font-black tracking-tight">سجل المعاملات المالية</h2>
                <p class="text-white/60 mt-2 text-sm">جميع عمليات الدفع المكتملة في منظومة الخدمات</p>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>