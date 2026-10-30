<x-filament-widgets::widget>
    <div class="space-y-4">
        <x-filament::section>
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-14 h-14 rounded-xl bg-{{ $welcomeData['color'] }}-100 flex items-center justify-center">
                        <x-dynamic-component
                            :component="$welcomeData['icon']"
                            class="w-7 h-7 text-{{ $welcomeData['color'] }}-600"
                        />
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">
                        {{ $welcomeData['title'] }}
                    </h3>
                    <p class="text-gray-600 mb-4">
                        {{ $welcomeData['message'] }}
                    </p>
                    @if(!empty($welcomeData['quickLinks']))
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                        @foreach($welcomeData['quickLinks'] as $link)
                        <a
                            href="{{ $link['url'] }}"
                            class="flex items-center gap-2 p-3 rounded-lg bg-gray-50 hover:bg-{{ $welcomeData['color'] }}-50 transition-colors group border border-gray-200 hover:border-{{ $welcomeData['color'] }}-300"
                        >
                            <x-dynamic-component
                                :component="$link['icon']"
                                class="w-5 h-5 text-gray-500 group-hover:text-{{ $welcomeData['color'] }}-600"
                            />
                            <span class="text-sm font-medium text-gray-700 group-hover:text-{{ $welcomeData['color'] }}-700">
                                {{ $link['label'] }}
                            </span>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- قسم المساعدة --}}
        <x-filament::section>
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                        <x-heroicon-o-question-mark-circle class="w-6 h-6 text-amber-600" />
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900">تحتاج مساعدة؟</h4>
                        <p class="text-sm text-gray-500">فريق الدعم الفني جاهز لمساعدتك</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="mailto:support@kfs.gov.eg"
                       class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition flex items-center gap-2 text-sm">
                        <x-heroicon-o-envelope class="w-4 h-4"/>
                        راسلنا
                    </a>
                    <a href="/gis/help"
                       class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition flex items-center gap-2 text-sm">
                        <x-heroicon-o-question-mark-circle class="w-4 h-4"/>
                        مركز المساعدة
                    </a>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
