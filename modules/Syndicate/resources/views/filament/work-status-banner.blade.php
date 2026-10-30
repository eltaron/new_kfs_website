@php
    /** @var \Modules\Syndicate\Models\Project $project */
    $project = $getRecord();
    $styles = [
        'suspended' => ['border' => 'rgba(166,58,50,.45)', 'bg' => 'rgba(166,58,50,.08)', 'fg' => '#A63A32', 'icon' => 'heroicon-o-hand-raised'],
        'active' => ['border' => 'rgba(61,122,80,.4)', 'bg' => 'rgba(61,122,80,.08)', 'fg' => '#3D7A50', 'icon' => 'heroicon-o-shield-check'],
        'pending' => ['border' => 'rgba(146,144,131,.4)', 'bg' => 'rgba(146,144,131,.1)', 'fg' => '#68645A', 'icon' => 'heroicon-o-clock'],
    ];
    $s = $styles[$project->work_status] ?? $styles['pending'];
@endphp
<div role="{{ $project->isSuspended() ? 'alert' : 'status' }}"
     style="border:2px solid {{ $s['border'] }}; background: {{ $s['bg'] }}; color: {{ $s['fg'] }}; border-radius: 14px; padding: 1rem 1.25rem; display:flex; gap:1rem; align-items:flex-start;">
    <x-filament::icon :icon="$s['icon']" class="h-8 w-8 shrink-0" />
    <div>
        <div style="font-weight:800; font-size:1.1rem">{{ $project->work_status_label }}</div>
        @if ($project->isSuspended())
            <div>لا يوجد مهندس مشرف على الموقع منذ {{ $project->suspended_at?->format('Y-m-d H:i') }} ({{ $project->suspended_at?->diffForHumans() }}).
                <strong>يجب إيقاف جميع الأعمال بالموقع</strong> لحين صدور شهادة إشراف جديدة من نقابة المهندسين.</div>
        @elseif ($project->isActive())
            <div>المهندس المشرف: {{ $project->activeCertificate?->engineer_name }} — شهادة رقم <span dir="ltr">{{ $project->activeCertificate?->number }}</span>
                منذ {{ $project->work_status_changed_at?->format('Y-m-d') }}.</div>
        @else
            <div>لم تصدر شهادة إشراف لهذا المشروع بعد. لا يجوز بدء الأعمال قبل تعيين مهندس مشرف.</div>
        @endif
    </div>
</div>
