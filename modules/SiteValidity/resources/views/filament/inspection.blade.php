@php
    $rows = (array) ($getRecord()->inspection_data ?? []);
@endphp
<div class="grid gap-2 sm:grid-cols-2">
    @foreach ($rows as $row)
        <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $row['label'] ?? $row['key'] ?? '' }}</span>
            <span class="text-sm font-semibold text-gray-950 dark:text-white text-end">{{ \Modules\SiteValidity\Models\InspectionField::displayValue($row['type'] ?? null, $row['value'] ?? null) }}</span>
        </div>
    @endforeach
</div>
