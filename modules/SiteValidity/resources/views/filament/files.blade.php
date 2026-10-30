@php
    /** @var \Modules\SiteValidity\Models\SiteValidityRequest $record */
    $record = $getRecord();
    $files = $record->files()->with('attachmentType')->get();
    $groups = [
        'مستندات المواطن' => $files->whereIn('kind', \Modules\SiteValidity\Models\RequestFile::CITIZEN_KINDS),
        'المرفقات الفنية (المركز الجيومكاني)' => $files->where('kind', 'attachment'),
        'مستندات الاستيفاء' => $files->where('kind', 'completion'),
        'الملف النهائي' => $files->where('kind', 'final'),
    ];
@endphp
<div class="grid gap-4 md:grid-cols-2">
    @foreach ($groups as $title => $items)
        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-3">
            <p class="text-sm font-semibold text-gray-950 dark:text-white mb-2">{{ $title }}</p>
            @forelse ($items as $file)
                <div class="flex items-center justify-between gap-2 py-1.5 border-t first:border-t-0 border-gray-100 dark:border-white/5">
                    <div class="min-w-0">
                        <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $file->label() }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><bdi>{{ $file->original_name }}</bdi> · {{ $file->humanSize() }} · {{ $file->created_at?->format('Y-m-d') }}</p>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        @if ($file->isImage() || $file->mime === 'application/pdf')
                            <a href="{{ $file->url() }}" target="_blank" rel="noopener" class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline">عرض</a>
                        @endif
                        <a href="{{ $file->url() }}?download=1" class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline">تنزيل</a>
                    </div>
                </div>
            @empty
                <p class="text-xs text-gray-500 dark:text-gray-400">لا توجد ملفات.</p>
            @endforelse
        </div>
    @endforeach
</div>
