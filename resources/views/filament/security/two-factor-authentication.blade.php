<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">
        <x-filament::section>
            <x-slot name="heading">
                Google Authenticator
            </x-slot>
            <x-slot name="description">
                بعد كلمة المرور سيُطلب منك رمز متغير من تطبيق Google Authenticator على هاتفك.
            </x-slot>

            <div class="mb-4">
                @if ($enabled)
                    <x-filament::badge color="success" icon="heroicon-o-check-circle">مفعّلة</x-filament::badge>
                @else
                    <x-filament::badge color="danger" icon="heroicon-o-x-circle">غير مفعّلة</x-filament::badge>
                @endif
            </div>

            @if ($required && ! $enabled)
                <div class="mb-4 rounded-lg bg-warning-50 p-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                    المصادقة الثنائية إلزامية لحسابات الموظفين. يرجى تفعيلها للمتابعة.
                </div>
            @endif

            @if (! $enabled && ! $pending)
                <form wire:submit="enable" class="space-y-4 max-w-md">
                    <div>
                        <label for="tfa-password" class="text-sm font-medium">أكّد كلمة المرور للبدء</label>
                        <x-filament::input.wrapper :valid="! $errors->has('password')">
                            <x-filament::input id="tfa-password" type="password" wire:model="password" autocomplete="current-password" />
                        </x-filament::input.wrapper>
                        @error('password') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <x-filament::button type="submit" icon="heroicon-o-shield-check">تفعيل المصادقة الثنائية</x-filament::button>
                </form>
            @elseif ($pending)
                <div class="space-y-4">
                    <p class="text-sm">1. امسح رمز QR بتطبيق Google Authenticator:</p>
                    <div class="inline-block rounded-xl bg-white p-3 ring-1 ring-gray-200">
                        <div style="width:200px;height:200px">{{ $qrSvg }}</div>
                    </div>
                    <p class="text-sm">أو أدخل المفتاح يدويًا: <code dir="ltr" class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">{{ $secret }}</code></p>

                    <form wire:submit="confirm" class="space-y-3 max-w-md">
                        <label for="tfa-code" class="text-sm font-medium">2. أدخل الرمز المكوَّن من 6 أرقام</label>
                        <x-filament::input.wrapper :valid="! $errors->has('code')">
                            <x-filament::input id="tfa-code" type="text" inputmode="numeric" maxlength="7" dir="ltr" wire:model="code" autocomplete="one-time-code" />
                        </x-filament::input.wrapper>
                        @error('code') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                        <div class="flex gap-2">
                            <x-filament::button type="submit">تأكيد التفعيل</x-filament::button>
                            <x-filament::button color="gray" wire:click="cancelSetup" type="button">إلغاء</x-filament::button>
                        </div>
                    </form>
                </div>
            @else
                <p class="text-sm">تم التفعيل بتاريخ: <strong>{{ optional($confirmedAt)->format('Y-m-d H:i') }}</strong></p>
                <p class="text-sm">رموز الاسترداد المتبقية: <strong>{{ $remainingCodes }}</strong></p>
            @endif
        </x-filament::section>

        @if (! empty($recoveryCodes))
            <x-filament::section>
                <x-slot name="heading">رموز الاسترداد</x-slot>
                <x-slot name="description">احفظها في مكان آمن؛ كل رمز يُستخدم مرة واحدة ولن تظهر مرة أخرى.</x-slot>
                <div dir="ltr" class="grid grid-cols-2 gap-2 font-mono text-sm">
                    @foreach ($recoveryCodes as $rc)
                        <span>{{ $rc }}</span>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        @if ($enabled)
            <x-filament::section>
                <x-slot name="heading">إدارة المصادقة الثنائية</x-slot>
                <div class="space-y-3 max-w-md">
                    <div>
                        <label for="tfa-manage-password" class="text-sm font-medium">كلمة المرور</label>
                        <x-filament::input.wrapper :valid="! $errors->has('password')">
                            <x-filament::input id="tfa-manage-password" type="password" wire:model="password" autocomplete="current-password" />
                        </x-filament::input.wrapper>
                        @error('password') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @unless ($required)
                        <div>
                            <label for="tfa-manage-code" class="text-sm font-medium">رمز التطبيق أو رمز استرداد (للتعطيل)</label>
                            <x-filament::input.wrapper :valid="! $errors->has('code')">
                                <x-filament::input id="tfa-manage-code" type="text" dir="ltr" wire:model="code" autocomplete="one-time-code" />
                            </x-filament::input.wrapper>
                            @error('code') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endunless
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button color="gray" wire:click="regenerateCodes" type="button">إنشاء رموز استرداد جديدة</x-filament::button>
                        @unless ($required)
                            <x-filament::button color="danger" wire:click="disable" type="button">تعطيل المصادقة الثنائية</x-filament::button>
                        @endunless
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
