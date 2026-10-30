@extends('layouts.app')
@php
    use Modules\SiteValidity\Support\SvSettings;
    $title = SvSettings::get('page_title');
    $minFee = $types->min(fn ($t) => (float) $t->fee) ?: (float) SvSettings::defaultFee();
@endphp
@section('title', $title . ' — ' . ($settings['site_name'] ?? 'محافظة كفر الشيخ'))
@section('description', \Illuminate\Support\Str::limit(SvSettings::get('page_intro'), 160))
@push('css')
    <link rel="stylesheet" href="{{ $svCssUrl }}">
@endpush
@section('content')
    <x-kfs.page-hero :title="$title" :kicker="SvSettings::get('page_kicker')" icon="fa-solid fa-map-location-dot"
        :subtitle="SvSettings::get('page_intro')" :crumbs="['الخدمات الإلكترونية' => route('services.index')]">
        <x-slot:actions>
            <a href="{{ route('site-validity.apply') }}" class="btn-kfs-gold"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> تقديم طلب جديد</a>
            @auth
                <a href="{{ route('site-validity.requests.index') }}" class="btn-kfs-outline"><i class="fa-solid fa-list-check" aria-hidden="true"></i> طلباتي</a>
            @endauth
        </x-slot:actions>
    </x-kfs.page-hero>

    <section class="sv-page">
        <div class="container">
            <div class="sv-layout">
                <div>
                    <div class="sv-card">
                        <h2 class="sv-card__title"><i class="fa-solid fa-route" aria-hidden="true"></i> خطوات الحصول على الخدمة</h2>
                        <ol class="sv-steps">
                            @foreach ($instructions as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ol>
                    </div>

                    <div class="sv-card">
                        <h2 class="sv-card__title"><i class="fa-solid fa-folder-open" aria-hidden="true"></i> المستندات المطلوبة</h2>
                        <ul class="sv-checks">
                            @foreach ($documents as $doc)
                                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ $doc }}</span></li>
                            @endforeach
                        </ul>
                        <p class="sv-muted sv-small mt-3 mb-0">الصيغ المقبولة: PDF أو صورة (JPG / PNG) بحد أقصى {{ (int) (config('site-validity.citizen_upload.max_kb') / 1024) }} ميجابايت لكل ملف.</p>
                    </div>

                    @if ($types->isNotEmpty())
                        <div class="sv-card">
                            <h2 class="sv-card__title"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> أنواع الخدمة والرسوم</h2>
                            <div class="sv-types">
                                @foreach ($types as $type)
                                    <div class="sv-type">
                                        <span class="sv-type__name">{{ $type->name }}</span>
                                        @if ($type->description)
                                            <span class="sv-type__desc">{{ $type->description }}</span>
                                        @endif
                                        <span class="sv-fee">{{ number_format((float) $type->fee, 2) }} <small>ج.م</small></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <aside class="sv-card sv-cta" aria-label="ابدأ الطلب">
                    <p class="sv-muted mb-1">الرسوم تبدأ من</p>
                    <p class="sv-cta__price mb-2">{{ number_format($minFee, 2) }} <small class="sv-small sv-muted">ج.م</small></p>
                    <p class="sv-small sv-muted">{{ SvSettings::get('cost_note') }}</p>
                    <a href="{{ route('site-validity.apply') }}" class="sv-btn sv-btn--block mb-2">
                        <i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> تقديم طلب صلاحية موقع
                    </a>
                    @guest
                        <p class="sv-small sv-muted mb-2">يجب تسجيل الدخول بحساب المواطن أولاً.</p>
                        <a href="{{ route('register.citizen') }}" class="sv-btn sv-btn--ghost sv-btn--block">
                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i> إنشاء حساب جديد
                        </a>
                    @else
                        <a href="{{ route('site-validity.requests.index') }}" class="sv-btn sv-btn--ghost sv-btn--block">
                            <i class="fa-solid fa-list-check" aria-hidden="true"></i> متابعة طلباتي
                        </a>
                    @endguest
                    <hr>
                    <ul class="sv-checks sv-small">
                        <li><i class="fa-solid fa-lock" aria-hidden="true"></i><span>ملفاتك محفوظة بشكل خاص ولا يطلع عليها إلا الموظف المختص.</span></li>
                        <li><i class="fa-solid fa-bell" aria-hidden="true"></i><span>تصلك إشعارات بكل مرحلة على البوابة والبريد الإلكتروني.</span></li>
                        <li><i class="fa-solid fa-credit-card" aria-hidden="true"></i><span>السداد إلكترونياً عبر e-Finance من صفحة الطلب.</span></li>
                    </ul>
                </aside>
            </div>
        </div>
    </section>
@endsection
