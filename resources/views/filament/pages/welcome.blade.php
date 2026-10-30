<x-filament-panels::page>
    <div class="space-y-6">
        
        {{-- بطاقة الترحيب الرئيسية --}}
        <div class="fi-fo-section rounded-2xl overflow-hidden bg-gradient-to-br from-{{ $getWelcomeData()['color'] }}-500 to-{{ $getWelcomeData()['color'] }}-700 text-white p-6 shadow-lg">
            <div class="flex items-start gap-4">
                <div class="text-5xl">{{ $getWelcomeData()['icon'] }}</div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold mb-2">{{ $getWelcomeData()['title'] }}</h1>
                    <p class="text-white/90 text-lg leading-relaxed">{{ $getWelcomeData()['message'] }}</p>
                </div>
            </div>
        </div>

        {{-- معلومات المستخدم السريعة --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="fi-fo-section p-4 rounded-xl bg-white shadow-sm border border-gray-100">
                <div class="text-gray-500 text-sm mb-1">المستخدم</div>
                <div class="font-semibold text-gray-900">{{ $getWelcomeData()['userName'] }}</div>
            </div>
            <div class="fi-fo-section p-4 rounded-xl bg-white shadow-sm border border-gray-100">
                <div class="text-gray-500 text-sm mb-1">الدور</div>
                <div class="font-semibold text-gray-900">{{ $getWelcomeData()['userRole'] }}</div>
            </div>
            <div class="fi-fo-section p-4 rounded-xl bg-white shadow-sm border border-gray-100">
                <div class="text-gray-500 text-sm mb-1">آخر دخول</div>
                <div class="font-semibold text-gray-900">{{ $getWelcomeData()['lastLogin'] }}</div>
            </div>
            <div class="fi-fo-section p-4 rounded-xl bg-white shadow-sm border border-gray-100">
                <div class="text-gray-500 text-sm mb-1">الحالة</div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="font-semibold text-gray-900">نشط</span>
                </div>
            </div>
        </div>

        {{-- الروابط السريعة --}}
        <div class="fi-fo-section p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <x-heroicon-o-bolt class="w-5 h-5 text-{{ $getWelcomeData()['color'] }}-500"/>
                روابط سريعة
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($getWelcomeData()['quickLinks'] as $link)
                <a href="{{ $link['url'] }}" 
                   class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 hover:border-{{ $getWelcomeData()['color'] }}-300 hover:bg-{{ $getWelcomeData()['color'] }}-50 transition-all group">
                    <x-dynamic-component :component="$link['icon']" class="w-5 h-5 text-gray-500 group-hover:text-{{ $getWelcomeData()['color'] }}-600"/>
                    <span class="font-medium text-gray-700 group-hover:text-{{ $getWelcomeData()['color'] }}-700">{{ $link['label'] }}</span>
                </a>
                @endforeach
            </div>
        </div>

        {{-- التعليمات والإرشادات --}}
        <div class="fi-fo-section p-6 rounded-2xl bg-amber-50 border border-amber-200">
            <h2 class="text-lg font-bold text-amber-900 mb-4 flex items-center gap-2">
                <x-heroicon-o-light-bulb class="w-5 h-5"/>
                إرشادات هامة
            </h2>
            <ul class="space-y-3">
                @foreach($getWelcomeData()['instructions'] as $instruction)
                <li class="flex items-start gap-3 text-amber-800">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"/>
                    <span>{{ $instruction }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        {{-- دعم فني سريع --}}
        <div class="fi-fo-section p-6 rounded-2xl bg-gray-50 border border-gray-200">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-gray-900">تحتاج مساعدة؟ 🤝</h3>
                    <p class="text-gray-600 text-sm mt-1">فريق الدعم الفني جاهز لمساعدتك في أي وقت</p>
                </div>
                <div class="flex gap-3">
                    <a href="mailto:support@kfs.gov.eg" 
                       class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition flex items-center gap-2">
                        <x-heroicon-o-envelope class="w-4 h-4"/>
                        راسلنا
                    </a>
                    <a href="/admin/help" 
                       class="px-4 py-2 bg-{{ $getWelcomeData()['color'] }}-600 text-white rounded-lg hover:bg-{{ $getWelcomeData()['color'] }}-700 transition flex items-center gap-2">
                        <x-heroicon-o-question-mark-circle class="w-4 h-4"/>
                        مركز المساعدة
                    </a>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>