@php
    $record = $getRecord();
    // يقبل مشروعاً أو طلباً أو شهادة: الخط الزمني دائماً على مستوى المشروع.
    $project = $record instanceof \Modules\Syndicate\Models\Project ? $record : $record->project;
    $events = $project ? $project->events()->with(['request', 'certificate'])->limit(100)->get() : collect();
    $palette = ['success' => '#3D7A50', 'danger' => '#A63A32', 'warning' => '#B7791F', 'info' => '#147C92', 'gray' => '#929083'];
@endphp
@if ($events->isEmpty())
    <p class="text-sm text-gray-500">لا توجد أحداث بعد.</p>
@else
    <ol style="list-style:none; margin:0; padding:0; border-inline-start:2px solid rgba(216,176,62,.45)">
        @foreach ($events as $e)
            @php $color = $palette[\Modules\Syndicate\Models\SyndicateEvent::colors()[$e->type] ?? 'gray'] ?? '#929083'; @endphp
            <li style="position:relative; padding: 0 1.25rem 1rem">
                <span aria-hidden="true" style="position:absolute; inset-inline-start:-7px; top:.35rem; width:12px; height:12px; border-radius:50%; background: {{ $color }}"></span>
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <strong class="text-sm" style="color: {{ $color }}">{{ $e->title }}</strong>
                    <span class="text-xs text-gray-500">{{ $e->created_at?->format('Y-m-d H:i') }} — {{ $e->actor_name }}</span>
                </div>
                @if ($e->description)
                    <div class="text-sm text-gray-700 dark:text-gray-300">{{ $e->description }}</div>
                @endif
            </li>
        @endforeach
    </ol>
@endif
