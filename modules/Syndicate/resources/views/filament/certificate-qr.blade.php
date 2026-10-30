@php /** @var \Modules\Syndicate\Models\Certificate $certificate */ $certificate = $getRecord(); @endphp
@if ($certificate->number)
    <div class="flex flex-wrap items-center gap-6">
        <div style="background:#fff; padding:8px; border-radius:10px; width:136px">{!! $certificate->qrSvg(120) !!}</div>
        <div class="text-sm">
            <div class="font-bold">رابط التحقق العام</div>
            <a href="{{ $certificate->verificationUrl(false) }}" target="_blank" rel="noopener" dir="ltr" class="text-primary-600 hover:underline">{{ $certificate->verificationUrl(false) }}</a>
            <div class="mt-1 text-gray-500">رمز QR المطبوع يتضمن رمز تحقق يعرض البيانات كاملة.</div>
        </div>
    </div>
@endif
