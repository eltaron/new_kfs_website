@php
    $totals = $this->getTotals();
    $egp = fn ($v) => number_format((float) $v, 2) . ' ج.م';
@endphp
<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">الفترة</x-slot>
        <div class="flex flex-wrap items-end gap-4">
            <label class="flex flex-col gap-1 text-sm font-medium">
                من تاريخ
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="from" />
                </x-filament::input.wrapper>
            </label>
            <label class="flex flex-col gap-1 text-sm font-medium">
                إلى تاريخ
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="until" />
                </x-filament::input.wrapper>
            </label>
            <p class="text-sm text-gray-500">تُحتسب الطلبات المؤكدة فقط (مدفوعة إلكترونياً أو بالدفع عند الاستلام)، باستثناء المنتجات الملغاة.</p>
        </div>
    </x-filament::section>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" role="list" aria-label="ملخص الفترة">
        @foreach ([
            'عدد الطلبات' => $totals['orders'],
            'عدد القطع' => $totals['qty'],
            'إجمالي المبيعات' => $egp($totals['sales']),
            'عمولة المتجر' => $egp($totals['commission']),
            'مستحقات البائعين' => $egp($totals['earnings']),
        ] as $label => $value)
            <x-filament::section role="listitem">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="text-2xl font-bold">{{ $value }}</p>
            </x-filament::section>
        @endforeach
    </div>

    @foreach ([
        ['title' => 'المبيعات حسب البائع', 'rows' => $this->getByVendor(), 'cols' => ['name' => 'البائع', 'orders' => 'الطلبات', 'qty' => 'القطع', 'sales' => 'المبيعات', 'commission' => 'العمولة', 'earnings' => 'مستحق البائع']],
        ['title' => 'المبيعات حسب القسم', 'rows' => $this->getByCategory(), 'cols' => ['name' => 'القسم', 'qty' => 'القطع', 'sales' => 'المبيعات']],
        ['title' => 'المبيعات الشهرية', 'rows' => $this->getByMonth(), 'cols' => ['month' => 'الشهر', 'orders' => 'الطلبات', 'sales' => 'المبيعات', 'commission' => 'العمولة']],
    ] as $report)
        <x-filament::section>
            <x-slot name="heading">{{ $report['title'] }}</x-slot>
            @if ($report['rows']->isEmpty())
                <p class="text-gray-500">لا توجد بيانات في هذه الفترة.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <caption class="sr-only">{{ $report['title'] }}</caption>
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                @foreach ($report['cols'] as $label)
                                    <th scope="col" class="px-3 py-2 text-start font-semibold">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['rows'] as $row)
                                <tr class="border-b border-gray-100 dark:border-white/5">
                                    @foreach (array_keys($report['cols']) as $key)
                                        <td class="px-3 py-2">
                                            {{ in_array($key, ['sales', 'commission', 'earnings'], true) ? $egp($row->{$key}) : $row->{$key} }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
