<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $data = $getWelcomeData ?? $welcomeData;
        @endphp
        
        <div class="flex items-start gap-4">
            {{-- الأيقونة --}}
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-xl bg-{{ $data['color'] }}-100 flex items-center justify-center">
                    <x-dynamic-component 
                        :component="$data['icon']" 
                        class="w-6 h-6 text-{{ $data['color'] }}-600"
                    />
                </div>
            </div>
            
            {{-- المحتوى --}}
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-bold text-gray-900 mb-1">
                    {{ $data['title'] }}
                </h3>
                <p class="text-lg font-bold text-gray-900 mb-4">
                    {{ $data['message'] }}
                </p>
                
                {{-- الروابط السريعة --}}
                @if(!empty($data['quickLinks']))
                <div class="grid grid-cols-2 gap-2">
                    @foreach($data['quickLinks'] as $link)
                    <a 
                        href="{{ $link['url'] }}" 
                        class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 hover:bg-{{ $data['color'] }}-50 transition-colors group"
                    >
                        <x-dynamic-component 
                            :component="$link['icon']" 
                            class="w-4 h-4 text-gray-500 group-hover:text-{{ $data['color'] }}-600"
                        />
                        <span class="text-sm font-medium text-gray-700 group-hover:text-{{ $data['color'] }}-700">
                            {{ $link['label'] }}
                        </span>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>