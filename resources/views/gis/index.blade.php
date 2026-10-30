@extends('layouts.app')
@section('title', 'بوابة الخدمات الجيومكانية')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/gis-services.css') }}">
@endpush

@inject('gisPricing', 'App\Services\Payments\Pricing\GisPricing')

@php
    // أيقونة افتراضية لكل تصنيف عند عدم تحديدها من اللوحة.
    $catIcons = ['fa-solid fa-map-location-dot', 'fa-solid fa-ruler-combined', 'fa-solid fa-house-circle-check', 'fa-solid fa-layer-group'];
    $serviceCount = $categories->sum(fn ($c) => $c->subServices->count());
@endphp

@section('content')
    <main class="gp-page">
        <x-kfs.page-hero title="منظومة الخدمات المكانية" kicker="منظومة رقمية متكاملة" icon="fa-solid fa-map-location-dot"
            subtitle="خدمات جغرافية رقمية موثقة وآمنة — تقديم وسداد ومتابعة إلكترونياً بالكامل دون الحاجة لزيارة المركز">
            <x-slot:meta>
                <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i> {{ $serviceCount }} خدمة إلكترونية</span>
                <span><i class="fa-solid fa-shapes" aria-hidden="true"></i> {{ $categories->count() }} تصنيفات</span>
                <span><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد إلكتروني آمن</span>
            </x-slot:meta>
            <x-slot:actions>
                <a href="#gp-services" class="btn-kfs-gold"><i class="fa-solid fa-grip" aria-hidden="true"></i> تصفح الخدمات</a>
                <a href="#gp-video" class="btn-kfs-outline"><i class="fa-solid fa-circle-play" aria-hidden="true"></i> كيف أقدم؟</a>
            </x-slot:actions>
        </x-kfs.page-hero>

        <div class="container">
            {{-- تتبع سريع --}}
            <div class="gp-card gp-quick" id="gp-track">
                <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-location"></i></span>
                <div class="gp-quick__text">
                    <strong>تابع طلبك الحالي</strong>
                    <span>أدخل رقم المعاملة الذي وصلك بعد التقديم</span>
                </div>
                <form class="gp-quick__form" action="{{ route('gis.tracking') }}" method="GET" role="search">
                    <label for="gpTicket" class="visually-hidden">رقم المعاملة</label>
                    <input id="gpTicket" type="text" name="ticket" class="gp-input" inputmode="numeric" placeholder="رقم المعاملة" required>
                    <button class="gp-btn" type="submit">تتبع <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                </form>
                @if (session('error'))
                    <p class="gp-quick__alert" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ session('error') }}</p>
                @endif
            </div>

            {{-- خطوات الحصول على الخدمة --}}
            <section class="gp-section" aria-labelledby="gpHowTitle">
                <div class="gp-head">
                    <div>
                        <span class="gp-head__kicker">أربع خطوات فقط</span>
                        <h2 class="gp-head__title" id="gpHowTitle">كيف تحصل على الخدمة؟</h2>
                    </div>
                </div>
                <ol class="gp-steps">
                    <li class="gp-card gp-step">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-hand-pointer"></i></span>
                        <h3>اختر الخدمة</h3>
                        <p>اطلع على التعريف والمستندات المطلوبة والرسوم قبل البدء.</p>
                    </li>
                    <li class="gp-card gp-step">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-file-pen"></i></span>
                        <h3>قدّم البيانات</h3>
                        <p>حدد موقع العقار وأرفق المستندات، وتظهر لك الرسوم فوراً.</p>
                    </li>
                    <li class="gp-card gp-step">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-credit-card"></i></span>
                        <h3>ادفع إلكترونياً</h3>
                        <p>سداد آمن عبر بوابة الدفع الحكومية e-Finance.</p>
                    </li>
                    <li class="gp-card gp-step">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-file-circle-check"></i></span>
                        <h3>تابع واستلم</h3>
                        <p>تابع حالة الطلب برقم المعاملة حتى صدور المخرجات.</p>
                    </li>
                </ol>
            </section>

            {{-- الخدمات --}}
            <section class="gp-section" id="gp-services" aria-labelledby="gpSvcTitle">
                <div class="gp-head">
                    <div>
                        <span class="gp-head__kicker">اختر الخدمة</span>
                        <h2 class="gp-head__title" id="gpSvcTitle">الخدمات المكانية</h2>
                        <p class="gp-head__lead">{{ $serviceCount }} خدمة موزعة على {{ $categories->count() }} تصنيفات — اضغط على الخدمة لعرض تفاصيلها والبدء.</p>
                    </div>
                    @if ($categories->count() > 1)
                        <div class="gp-chips" role="group" aria-label="تصفية حسب التصنيف">
                            <button type="button" class="gp-chip" data-gp-filter="all" aria-pressed="true">الكل <small>{{ $serviceCount }}</small></button>
                            @foreach ($categories as $cat)
                                <button type="button" class="gp-chip" data-gp-filter="cat-{{ $cat->id }}" aria-pressed="false">{{ $cat->name }} <small>{{ $cat->subServices->count() }}</small></button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @foreach ($categories as $cat)
                    @php $icon = $cat->icon ?: $catIcons[$loop->index % count($catIcons)]; @endphp
                    <div class="gp-cat" data-gp-cat="cat-{{ $cat->id }}">
                        <div class="gp-cat__head">
                            <span class="gp-ico" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                            <div>
                                <h3 class="gp-cat__name">{{ $cat->name }} <span class="gp-count">{{ $cat->subServices->count() }} خدمة</span></h3>
                                @if ($cat->description)
                                    <div class="gp-cat__desc">{!! clean($cat->description, 'cms') !!}</div>
                                @endif
                            </div>
                        </div>

                        @if ($cat->subServices->isNotEmpty())
                            <div class="gp-grid">
                                @foreach ($cat->subServices as $sub)
                                    @php
                                        $q = $gisPricing->quote($sub, []);
                                        $variable = in_array($sub->pricing_type, ['formula', 'tiered'], true);
                                    @endphp
                                    <a href="{{ route('gis.service.show', $sub->slug) }}" class="gp-card gp-svc">
                                        <span class="gp-svc__top">
                                            <span class="gp-ico" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                            <span class="gp-svc__name">{{ trim($sub->name) }}</span>
                                        </span>
                                        <span class="gp-svc__foot">
                                            <span class="gp-price">
                                                <small>{{ $variable ? 'تبدأ الرسوم من' : 'إجمالي الرسوم' }}</small>
                                                <strong>{{ number_format((float) $q['total'], 2) }}<span>ج.م</span></strong>
                                            </span>
                                            <span class="gp-go" aria-hidden="true"><i class="fa-solid fa-arrow-left"></i></span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="gp-empty">لا توجد خدمات متاحة في هذا التصنيف حالياً.</p>
                        @endif
                    </div>
                @endforeach
            </section>

            {{-- فيديو تعريفي --}}
            <section class="gp-section" id="gp-video">
                <div class="gp-explain">
                    <div>
                        <span class="gp-head__kicker">شاهد الشرح</span>
                        <h2>فيديو تعريفي بمراحل الحصول على الخدمة</h2>
                        <p>شرح مختصر لخطوات التقديم من البداية حتى استلام الخدمة.</p>
                        <ul class="gp-checks">
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>إنشاء الحساب واختيار الخدمة المناسبة</span></li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>إدخال بيانات العقار وإرفاق المستندات</span></li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>السداد الإلكتروني ومتابعة الطلب حتى صدور المخرجات</span></li>
                        </ul>
                    </div>
                    <button type="button" class="gp-video" onclick="openVideoModal()" aria-label="تشغيل الفيديو التعريفي">
                        <img src="{{ asset('gis.png') }}" alt="" loading="lazy">
                        <span class="gp-video__overlay">
                            <span class="gp-video__play"><i class="fa-solid fa-play" aria-hidden="true"></i></span>
                            شاهد الفيديو
                        </span>
                    </button>
                </div>
            </section>

            {{-- لماذا الخدمة الإلكترونية --}}
            <section class="gp-section" aria-label="مزايا الخدمات المكانية الإلكترونية">
                <div class="gp-benefits">
                    <div class="gp-card gp-benefit">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-house-laptop"></i></span>
                        <div><strong>من مكانك</strong><span>تقديم كامل أونلاين بدون زيارة المركز.</span></div>
                    </div>
                    <div class="gp-card gp-benefit">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-calculator"></i></span>
                        <div><strong>رسوم واضحة</strong><span>حاسبة تعرض الرسوم بالتفصيل قبل الدفع.</span></div>
                    </div>
                    <div class="gp-card gp-benefit">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>
                        <div><strong>بيانات محمية</strong><span>المستندات محفوظة ولا يطلع عليها إلا المختص.</span></div>
                    </div>
                    <div class="gp-card gp-benefit">
                        <span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-comment-sms"></i></span>
                        <div><strong>متابعة لحظية</strong><span>رسائل SMS بكل مرحلة من مراحل الطلب.</span></div>
                    </div>
                </div>
            </section>
        </div>

        <div class="gp-modal" id="videoModal" onclick="closeVideoModal(event)" role="dialog" aria-modal="true" aria-label="الفيديو التعريفي">
            <div class="gp-modal__box" onclick="event.stopPropagation()">
                <button type="button" class="gp-modal__close" onclick="closeVideoModal()" aria-label="إغلاق"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                <iframe id="videoIframe" src="" title="فيديو تعريفي بالخدمات المكانية"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        function openVideoModal() {
            document.getElementById('videoIframe').src = 'https://www.youtube.com/embed/WREUvW9NIDM?autoplay=1';
            document.getElementById('videoModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            document.querySelector('#videoModal .gp-modal__close').focus();
        }
        function closeVideoModal(e) {
            if (e && e.target !== e.currentTarget) return;
            const modal = document.getElementById('videoModal');
            if (!modal.classList.contains('active')) return;
            modal.classList.remove('active');
            document.getElementById('videoIframe').src = '';
            document.body.style.overflow = '';
            document.querySelector('.gp-video')?.focus();
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeVideoModal(); });

        // تصفية التصنيفات
        document.querySelectorAll('[data-gp-filter]').forEach(chip => {
            chip.addEventListener('click', () => {
                const key = chip.dataset.gpFilter;
                document.querySelectorAll('[data-gp-filter]').forEach(c => c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'));
                document.querySelectorAll('[data-gp-cat]').forEach(block => {
                    block.hidden = key !== 'all' && block.dataset.gpCat !== key;
                });
            });
        });
    </script>
@endpush
