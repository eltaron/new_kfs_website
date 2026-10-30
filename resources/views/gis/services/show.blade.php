@extends('layouts.app')
@section('title', $service->name)
@push('css')
    <link rel="stylesheet" href="{{ asset('css/gis-services.css') }}">
@endpush

@php
    // $quote = GisPricing::quote($service, []) — أقل مبلغ، وهو نفس حساب الخادم عند التقديم.
    $isVariable = in_array($service->pricing_type, ['formula', 'tiered'], true);
    $tabs = [
        'definition' => ['التعريف بالخدمة', 'fa-solid fa-circle-info', $service->description],
        'requirements' => ['المستندات المطلوبة', 'fa-solid fa-folder-open', $service->requirements],
        'terms' => ['الشروط والأحكام', 'fa-solid fa-scale-balanced', $service->terms_conditions],
    ];
@endphp

@section('content')
    <main class="gp-page">
        <x-kfs.page-hero size="detail" :title="trim($service->name)" :kicker="$service->serviceType->name ?? 'الخدمات المكانية'"
            icon="fa-solid fa-map-location-dot" subtitle="خدمة رقمية موثقة بالكامل — تقديم وسداد إلكتروني دون الحاجة لزيارة المركز"
            :crumbs="['الخدمات المكانية' => route('gis.index')]">
            <x-slot:meta>
                <span><i class="fa-solid fa-receipt" aria-hidden="true"></i> {{ $isVariable ? 'من' : 'الإجمالي' }} {{ number_format((float) $quote['total'], 2) }} ج.م</span>
                <span><i class="fa-solid fa-laptop" aria-hidden="true"></i> تقديم إلكتروني بالكامل</span>
                <span><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد عبر e-Finance</span>
            </x-slot:meta>
            <x-slot:actions>
                <a href="#gis-apply-box" class="btn-kfs-gold">ابدأ تقديم الطلب <i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
            </x-slot:actions>
        </x-kfs.page-hero>

        <div class="container">
            <div class="gp-layout">
                <div>
                    <div class="gp-card gp-tabs">
                        <div class="gp-tabs__nav" role="tablist" aria-label="معلومات الخدمة">
                            @foreach ($tabs as $key => [$label, $icon])
                                <button type="button" class="gp-tab" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}">
                                    <i class="{{ $icon }}" aria-hidden="true"></i> {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        @foreach ($tabs as $key => [$label, $icon, $body])
                            <div class="gp-tabs__panel gp-prose" role="tabpanel" id="panel-{{ $key }}" aria-labelledby="tab-{{ $key }}" tabindex="0" @unless ($loop->first) hidden @endunless>
                                @if (filled(trim(strip_tags((string) $body))))
                                    {!! clean($body, 'cms') !!}
                                @else
                                    <p class="gp-empty">لا توجد بيانات إضافية.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="gp-card gp-start" id="gis-apply-box">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-file-signature"></i></span>
                        <div>
                            <h2>جاهز للبدء في تقديم الطلب؟</h2>
                            <p>يقر مقدم الطلب باطلاعه على الشروط والأحكام الفنية السابقة وصحة البيانات التي سيدلي بها.
                                @if ($isVariable) الرسوم النهائية تُحسب تلقائياً داخل نموذج الطلب حسب البيانات التي تدخلها. @endif</p>
                            <form action="{{ route('gis.apply.start', $service->slug) }}" method="GET" class="gp-start__row">
                                <label class="gp-check" for="agreeCheck">
                                    <input type="checkbox" id="agreeCheck" required>
                                    أوافق على جميع الشروط والأحكام
                                </label>
                                <button type="submit" class="gp-btn gp-btn--lg">انتقل لنموذج الطلب <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </div>
                </div>

                <aside class="gp-aside" aria-label="الرسوم وتتبع الطلب">
                    <div class="gp-card gp-pricecard">
                        <div class="gp-pricecard__head"><i class="fa-solid fa-receipt" aria-hidden="true"></i> {{ $isVariable ? 'الرسوم تبدأ من' : 'رسوم الخدمة' }}</div>
                        <div class="gp-rows">
                            <div class="gp-row"><span>{{ $isVariable ? 'المقابل الفني (حد أدنى)' : 'المقابل الفني' }}</span><span>{{ number_format((float) $quote['core'], 2) }} ج.م</span></div>
                            @if ((float) $quote['vat'] > 0)
                                <div class="gp-row"><span>ضريبة القيمة المضافة (14%)</span><span>{{ number_format((float) $quote['vat'], 2) }} ج.م</span></div>
                            @endif
                            <div class="gp-row"><span>دمغة الشهداء</span><span>{{ number_format((float) $quote['martyr_stamp_fee'], 2) }} ج.م</span></div>
                            <div class="gp-row"><span>مقابل المتابعة (SMS)</span><span>{{ number_format((float) $quote['sms_fee'], 2) }} ج.م</span></div>
                        </div>
                        <div class="gp-total">
                            <div class="gp-total__label">{{ $isVariable ? 'يبدأ من' : 'الإجمالي' }}
                                <small>{{ $isVariable ? 'يُحسب المبلغ النهائي تلقائياً في نموذج الطلب' : 'المطالبة الرقمية المعتمدة' }}</small></div>
                            <div class="gp-total__amount">{{ number_format((float) $quote['total'], 2) }}<small>ج.م</small></div>
                        </div>
                        <a href="#gis-apply-box" class="gp-btn">ابدأ تقديم الطلب <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                    </div>

                    <div class="gp-card gp-mini">
                        <h3><i class="fa-solid fa-magnifying-glass-location" aria-hidden="true"></i> تتبع حالة طلبك</h3>
                        <p>قدّمت طلباً من قبل؟ أدخل رقم المعاملة.</p>
                        <form action="{{ route('gis.tracking') }}" method="GET">
                            <label for="trackTicket" class="visually-hidden">رقم المعاملة</label>
                            <input id="trackTicket" type="text" name="ticket" class="gp-input" inputmode="numeric" placeholder="رقم المعاملة" required>
                            <button class="gp-btn gp-btn--ghost gp-btn--block">تتبع الطلب <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        (() => {
            const tabs = [...document.querySelectorAll('.gp-tab')];
            const select = tab => {
                tabs.forEach(t => {
                    const on = t === tab;
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    t.tabIndex = on ? 0 : -1;
                    document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
                });
                tab.focus();
            };
            tabs.forEach((tab, i) => {
                tab.addEventListener('click', () => select(tab));
                tab.addEventListener('keydown', e => {
                    // RTL: السهم الأيسر = التالي
                    const step = e.key === 'ArrowLeft' ? 1 : e.key === 'ArrowRight' ? -1 : 0;
                    if (step) { e.preventDefault(); select(tabs[(i + step + tabs.length) % tabs.length]); }
                });
            });
        })();
    </script>
@endpush
