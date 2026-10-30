<x-filament::page>
    <div class="max-w-2xl mx-auto" style="width:100%">
        <div class="mb-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">الرقم القومي للمالك</label>
            <input type="text"
                wire:model.live="nationalId"
                maxlength="14"
                minlength="14"
                placeholder="أدخل الرقم القومي (14 رقم)"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white px-3 py-2 border" />
        </div>

        <div class="mb-6">
            <x-filament::button wire:click="search" size="lg" class="w-full">
                بحث
            </x-filament::button>
        </div>

        @if($result !== null)
            @if($result['found'] ?? false)
                <x-filament::section>
                    <x-slot name="heading">
                        نتائج البحث للرقم: {{ $searchedId }}
                    </x-slot>

                    <div class="space-y-4">
                        <div>
                            <span class="fi-badge bg-{{ ($result['type'] ?? '') === 'تنفيذ' ? 'success' : 'danger' }}-100 text-{{ ($result['type'] ?? '') === 'تنفيذ' ? 'success' : 'danger' }}-700 px-2 py-1 rounded-full text-sm font-semibold">{{ $result['type'] }}</span>
                            @if($result['execution_sub_type'] ?? false)
                                <span class="fi-badge bg-primary-100 text-primary-700 px-2 py-1 rounded-full text-sm font-semibold">{{ $result['execution_sub_type'] }}</span>
                            @endif
                        </div>

                        <x-filament::section heading="بيانات المهندس المشرف" size="sm">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><span class="text-gray-500">الاسم:</span> {{ $result['engineer_name'] }}</div>
                                <div><span class="text-gray-500">رقم العضوية:</span> {{ $result['engineer_syndicate_number'] }}</div>
                                <div><span class="text-gray-500">التخصص:</span> {{ $result['engineer_department'] }}</div>
                                <div><span class="text-gray-500">رقم السجل الهندسي:</span> {{ $result['engineer_registration_number'] }}</div>
                            </div>
                        </x-filament::section>

                        <x-filament::section heading="بيانات المالك والمشروع" size="sm">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><span class="text-gray-500">المالك:</span> {{ $result['owner_name'] }}</div>
                                <div><span class="text-gray-500">العنوان:</span> {{ $result['owner_address'] }}</div>
                                <div><span class="text-gray-500">رقم الترخيص:</span> {{ $result['license_number'] }}</div>
                                <div><span class="text-gray-500">تاريخ الترخيص:</span> {{ $result['license_date'] }}</div>
                                <div><span class="text-gray-500">جهة الإصدار:</span> {{ $result['license_authority'] }}</div>
                                <div><span class="text-gray-500">المدينة:</span> {{ $result['city'] }}</div>
                                <div><span class="text-gray-500">المحافظة:</span> {{ $result['governorate'] }}</div>
                                <div class="col-span-2"><span class="text-gray-500">العنوان بالتفصيل:</span> {{ $result['project_address'] }}</div>
                            </div>
                        </x-filament::section>

                        <x-filament::section heading="بيان الأعمال" size="sm">
                            <div class="space-y-2 text-sm">
                                <div>{{ $result['licensed_works_description'] }}</div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div><span class="text-gray-500">قيمة الأعمال:</span> {{ $result['works_value_numbers'] }} ج.م</div>
                                    <div><span class="text-gray-500">بالحروف:</span> {{ $result['works_value_words'] }}</div>
                                </div>
                            </div>
                        </x-filament::section>

                        @if($result['certificate_file'] ?? false)
                            <x-filament::section heading="شهادة الإشراف" size="sm">
                                <a href="{{ asset('storage/' . $result['certificate_file']) }}" target="_blank" class="fi-link text-primary-600 underline">
                                    عرض الملف
                                </a>
                            </x-filament::section>
                        @endif
                    </div>
                </x-filament::section>
            @else
                <x-filament::section>
                    <div class="text-center py-8">
                        @if($result['error'] ?? false)
                            <div class="text-danger-600 text-lg font-semibold">{{ $result['error'] }}</div>
                        @else
                            <div class="text-danger-600 text-lg font-semibold">لم يتم العثور على نتائج</div>
                            <div class="text-gray-500 mt-2">لا توجد شهادات إشراف مسجلة لهذا الرقم القومي: {{ $searchedId }}</div>
                        @endif
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament::page>
