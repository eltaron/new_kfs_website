@php
    /** @var \Modules\SiteValidity\Models\SiteValidityRequest $record */
    $record = $getRecord();
    $events = $record->events()->limit(200)->get();
    $palette = ['success' => '#3D7A50', 'danger' => '#A63A32', 'warning' => '#B7791F', 'info' => '#147C92', 'primary' => '#657342', 'gray' => '#929083'];
@endphp
@if ($events->isEmpty())
    <p class="text-sm text-gray-500">لا توجد أحداث بعد.</p>
@else
    <ol style="list-style:none; margin:0; padding:0; border-inline-start:2px solid rgba(101,115,66,.45)">
        @foreach ($events as $e)
            @php $color = $palette[\Modules\SiteValidity\Support\SvStatus::color($e->to_status)] ?? '#929083'; @endphp
            <li style="position:relative; padding: 0 1.25rem 1rem">
                <span aria-hidden="true" style="position:absolute; inset-inline-start:-7px; top:.35rem; width:12px; height:12px; border-radius:50%; background: {{ $color }}"></span>
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <strong class="text-sm" style="color: {{ $color }}">{{ $e->title() }}</strong>
                    <span class="text-xs" style="opacity:.7">{{ $e->created_at?->format('Y-m-d H:i') }} — {{ $e->actorLabel() }}</span>
                </div>
                @if ($e->from_status !== $e->to_status && $e->to_status)
                    <div class="text-xs" style="opacity:.75">
                        {{ $e->from_status ? \Modules\SiteValidity\Support\SvStatus::label($e->from_status) : '—' }} ← {{ \Modules\SiteValidity\Support\SvStatus::label($e->to_status) }}
                    </div>
                @endif
                @if ($e->notes)
                    <div class="text-sm" style="opacity:.9; white-space:pre-line">{{ $e->notes }}</div>
                @endif
            </li>
        @endforeach
    </ol>
@endif
