@php
    /** @var \Modules\SiteValidity\Models\SiteValidityRequest $record */
    $record = $getRecord();
    $completions = $record->completions()->with('files')->get();
@endphp
<div class="space-y-3">
    @if ($record->rejection_reason)
        <div class="rounded-xl border border-danger-300 dark:border-danger-500/40 bg-danger-50 dark:bg-danger-500/10 p-3">
            <p class="text-sm font-semibold text-danger-700 dark:text-danger-400">سبب الرفض</p>
            <p class="text-sm text-gray-800 dark:text-gray-200">{{ $record->rejection_reason }}</p>
        </div>
    @endif
    @if ($record->final_notes)
        <div class="rounded-xl border border-success-300 dark:border-success-500/40 bg-success-50 dark:bg-success-500/10 p-3">
            <p class="text-sm font-semibold text-success-700 dark:text-success-400">ملاحظات الانتهاء</p>
            <p class="text-sm text-gray-800 dark:text-gray-200">{{ $record->final_notes }}</p>
        </div>
    @endif
    @foreach ($completions as $c)
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">استيفاء — الجولة {{ $c->round }} · {{ $c->typeLabel() }}
                    @if ($c->isPayment())<span class="text-warning-700 dark:text-warning-400">({{ number_format((float) $c->amount, 2) }} ج.م)</span>@endif
                </p>
                <span class="text-xs text-gray-500">طُلب {{ $c->requested_at?->format('Y-m-d H:i') }}</span>
            </div>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1">{{ $c->notes }}</p>
            @if (! empty($c->requested_items))
                <ul class="list-disc ps-5 mt-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($c->requested_items as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-2 border-t border-gray-100 dark:border-white/5 pt-2">
                @if ($c->replied_at)
                    <p class="text-xs font-semibold text-success-700 dark:text-success-400">رد المواطن ({{ $c->replied_at->format('Y-m-d H:i') }})</p>
                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ $c->reply }}</p>
                    @foreach ($c->files as $f)
                        <a href="{{ $f->url() }}" target="_blank" rel="noopener" class="inline-block text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline me-3">{{ $f->original_name ?: 'مستند' }}</a>
                    @endforeach
                    @if ($c->paid_at && $c->payment_reference)
                        <p class="text-xs text-gray-500">مرجع الدفع: <bdi>{{ $c->payment_reference }}</bdi></p>
                    @endif
                @else
                    <p class="text-xs font-semibold text-warning-700 dark:text-warning-400">{{ $c->isPayment() ? 'بانتظار سداد المواطن' : 'بانتظار رد المواطن' }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
