@php($percent = max(0, min(100, (int) ($percent ?? 0))))
<div class="es-progress-wrap">
    <div class="es-progress-label"><span>{{ $label ?? 'نسبة الإنجاز' }}</span><b>{{ $percent }}%</b></div>
    <div class="es-progress {{ $percent >= 100 ? 'is-done' : '' }}" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $label ?? 'نسبة الإنجاز' }}">
        <span style="width: {{ $percent }}%"></span>
    </div>
</div>
