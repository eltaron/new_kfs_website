@php
    $suspendedCount = auth()->check() ? \Modules\Syndicate\Models\Project::where('work_status', 'suspended')->count() : 0;
    $panelId = \Modules\Syndicate\Filament\PanelContext::id();
@endphp
@if ($suspendedCount > 0)
    <div role="alert" style="border:1px solid rgba(166,58,50,.4); background:rgba(166,58,50,.08); color:#A63A32; border-radius:12px; padding:.75rem 1rem; display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6" />
        <span style="font-weight:700">يوجد {{ $suspendedCount }} {{ $suspendedCount === 1 ? 'مشروع موقوف' : 'مشروعات موقوفة' }} — بدون مهندس مشرف ويجب إيقاف الأعمال بها.</span>
        @if ($panelId)
            <a href="{{ url('/' . $panelId . '/projects?tableFilters[work_status][value]=suspended') }}" style="text-decoration:underline; font-weight:600">عرض المشروعات الموقوفة</a>
        @endif
    </div>
@endif
