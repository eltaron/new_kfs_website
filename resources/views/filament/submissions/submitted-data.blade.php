@php
    $data = $getRecord()?->submitted_data ?? [];
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @forelse ($data as $key => $value)
        <div class="p-4 bg-dark-50 rounded-xl border">
            <p class="text-sm text-gray-500 mb-1">
                {{ str_replace('_', ' ', ucfirst($key)) }}
            </p>
            <p class="font-semibold text-gray-900 break-words">
                {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}
            </p>
        </div>
    @empty
        <p class="text-gray-500">لا توجد بيانات.</p>
    @endforelse
</div>
