<x-filament-panels::page class="fi-resource-list-records-page">
    <div class="flex flex-col gap-y-6">
        @php
            $activeCount = collect($this->q)->filter(fn($v) => $v !== '' && $v !== null)->count();
        @endphp

        {{-- Search / Filter Form --}}
        <div class="fi-card overflow-hidden rounded-2xl border border-primary-200 dark:border-primary-500/20 shadow-sm">
            {{-- Header --}}
            <div class="bg-gradient-to-l from-primary-800 via-primary-600 to-primary-900 px-6 py-5">
                <div class="flex items-center gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white">بحث متقدم عن قرارات الإزالة</h2>
                        <p class="mt-0.5 text-sm text-primary-100">
                            ابحث بالاسم أو رقم القرار أو الرقم القومي، وفلتر حسب المركز والحالة والنوع والمسؤول
                        </p>
                    </div>
                </div>
            </div>

            {{-- Fields --}}
            <div class="bg-white px-6 py-5 dark:bg-gray-900">
                <form wire:submit.prevent="applySearch">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="md:col-span-2 xl:col-span-2">
                            <label for="q-text"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">بحث نصي (اسم
                                المالك / رقم القرار / الرقم القومي / المركز)</label>
                            <div class="relative">
                                <x-heroicon-s-magnifying-glass
                                    class="pointer-events-none absolute inset-y-0 end-3 my-auto h-5 w-5 text-gray-400" />
                                <input wire:model.live.debounce.400ms="q.text" id="q-text" type="text"
                                    placeholder="مثال: أحمد محمد ... أو 2024/..."
                                    class="w-full rounded-lg border border-gray-300 bg-white py-2.5 ps-10 pe-4 text-sm shadow-sm transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-white/5" />
                            </div>
                        </div>

                        <div>
                            <label for="q-center"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">المركز</label>
                            <select wire:model.live="q.center" id="q-center"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pe-4 ps-4 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-white/5">
                                <option value="">كل المراكز</option>
                                @foreach ($this->getCenters() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="q-stage"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">الحالة
                                (المرحلة)</label>
                            <select wire:model.live="q.stage" id="q-stage"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pe-4 ps-4 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-white/5">
                                <option value="">كل الحالات</option>
                                @foreach ($this->getStageOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="q-type"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">النوع</label>
                            <select wire:model.live="q.violation_type" id="q-type"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pe-4 ps-4 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-white/5">
                                <option value="">الكل</option>
                                <option value="new_violation">بدون ترخيص</option>
                                <option value="licensed_violation">مخالفة ترخيص</option>
                            </select>
                        </div>

                        <div>
                            <label for="q-specialist"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">أخصائي
                                النظم</label>
                            <select wire:model.live="q.specialist" id="q-specialist"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pe-4 ps-4 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-white/5">
                                <option value="">الكل</option>
                                @foreach ($this->getSpecialistOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="q-role"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">الصفة / الصلاحية
                                (المتعاملين مع القرار)</label>
                            <select wire:model.live="q.role" id="q-role"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pe-4 ps-4 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-white/5">
                                <option value="">الكل</option>
                                @foreach ($this->getRoleOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div
                        class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4 dark:border-white/10">
                        <x-filament::button wire:click="applySearch" type="button" icon="heroicon-m-magnifying-glass">
                            ابحث
                        </x-filament::button>
                        <x-filament::button wire:click="clearSearch" type="button" color="gray"
                            icon="heroicon-m-x-mark">
                            مسح البحث
                        </x-filament::button>
                        <span
                            class="ms-auto inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                            <x-heroicon-m-funnel class="h-3.5 w-3.5" />
                            فلاتر نشطة: {{ $activeCount }}
                        </span>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results --}}
        <div class="fi-card overflow-hidden rounded-2xl border border-gray-200 shadow-sm dark:border-white/10">
            <div class="flex items-center justify-between bg-gray-50 px-6 py-3 dark:bg-white/5">
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">نتائج البحث</span>
                <span class="inline-flex items-center gap-1.5 text-sm text-gray-500">
                    <x-heroicon-m-document-text class="h-4 w-4" />
                    {{ number_format($this->getResultsCount()) }} قرار
                </span>
            </div>
            <div class="border-t border-gray-100 dark:border-white/10">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
