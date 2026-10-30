@php
    /** @var \Modules\Syndicate\Models\Project $project */
    $project = $getRecord();
    $panel = \Modules\Syndicate\Filament\PanelContext::id();
    $requests = $project->requests()->get();
    $certificates = $project->certificates()->get();
    $statusColor = fn ($c) => match ($c) { 'success' => '#3D7A50', 'danger' => '#A63A32', 'warning' => '#B7791F', 'gray' => '#68645A', default => '#147C92' };
@endphp
<div class="grid gap-6 md:grid-cols-2">
    <div>
        <h4 class="mb-2 text-sm font-bold text-gray-700 dark:text-gray-200">المعاملات ({{ $requests->count() }})</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-gray-500"><th class="p-2 text-start">رقم المعاملة</th><th class="p-2 text-start">النوع</th><th class="p-2 text-start">الحالة</th><th class="p-2 text-start">التاريخ</th></tr></thead>
                <tbody>
                @forelse ($requests as $r)
                    <tr class="border-t border-gray-200 dark:border-white/10">
                        <td class="p-2" dir="ltr"><a class="font-semibold text-primary-600 hover:underline" href="{{ \Modules\Syndicate\Filament\Resources\RequestResource::getUrl('view', ['record' => $r], panel: $panel) }}">{{ $r->transaction_number }}</a></td>
                        <td class="p-2">{{ $r->type_label }}</td>
                        <td class="p-2"><span style="color: {{ $statusColor(\Modules\Syndicate\Models\SyndicateRequest::statusColors()[$r->status] ?? 'info') }}; font-weight:700">{{ $r->status_label }}</span></td>
                        <td class="p-2">{{ $r->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-2 text-gray-500">لا توجد معاملات.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div>
        <h4 class="mb-2 text-sm font-bold text-gray-700 dark:text-gray-200">الشهادات ({{ $certificates->count() }})</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-gray-500"><th class="p-2 text-start">رقم الشهادة</th><th class="p-2 text-start">النوع</th><th class="p-2 text-start">المهندس</th><th class="p-2 text-start">الحالة</th></tr></thead>
                <tbody>
                @forelse ($certificates as $c)
                    <tr class="border-t border-gray-200 dark:border-white/10">
                        <td class="p-2" dir="ltr"><a class="font-semibold text-primary-600 hover:underline" href="{{ \Modules\Syndicate\Filament\Resources\CertificateResource::getUrl('view', ['record' => $c], panel: $panel) }}">{{ $c->number }}</a></td>
                        <td class="p-2">{{ $c->kind_label }}</td>
                        <td class="p-2">{{ $c->engineer_name }}</td>
                        <td class="p-2"><span style="color: {{ $statusColor($c->kind === 'cancellation' ? 'danger' : (\Modules\Syndicate\Models\Certificate::statusColors()[$c->status] ?? 'gray')) }}; font-weight:700">{{ $c->status_label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-2 text-gray-500">لا توجد شهادات.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
