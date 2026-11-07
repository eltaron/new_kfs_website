@extends('layouts.app')
@section('title', 'الشكاوى والبلاغات')
@section('description', 'قدّم شكوى عبر منظومة الشكاوى الحكومية الموحدة، أو أبلغ عن حالة طارئة في محافظة كفر الشيخ.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-help.css') }}?v={{ @filemtime(public_path('css/kfs-help.css')) }}">
@endpush

@section('content')
    @php
        $hotlines = [
            ['123', 'الإسعاف', 'fa-solid fa-truck-medical'],
            ['122', 'شرطة النجدة', 'fa-solid fa-shield-halved'],
            ['180', 'الحماية المدنية', 'fa-solid fa-fire-extinguisher'],
        ];
        $more = collect([
            ['suggestions.create', 'fa-solid fa-lightbulb', 'تقديم مقترح', 'شاركنا فكرة لتطوير خدمة أو مكان'],
            ['directory.index', 'fa-solid fa-phone-volume', 'دليل الهاتف', 'أرقام الجهات والمصالح الحكومية'],
            ['contact.index', 'fa-solid fa-envelope-open-text', 'تواصل معنا', 'استفسار عام أو رسالة للمحافظة'],
            ['surveys.service.create', 'fa-solid fa-star-half-stroke', 'تقييم الخدمات', 'رأيك في الخدمة التي حصلت عليها'],
        ])->filter(fn ($l) => \App\Support\EServices::visible($l[0]));
    @endphp

    <main class="kh">
        <x-kfs.page-hero size="compact" title="الشكاوى والبلاغات" kicker="كيف يمكننا مساعدتك؟" icon="fa-solid fa-headset"
            current="الشكاوى والبلاغات"
            subtitle="اختر المسار المناسب لطلبك — شكوى عن خدمة، أو بلاغ عن حالة تحتاج تدخلاً عاجلاً." />

        <div class="container kh__wrap">
            {{-- Life-threatening: call first --}}
            <section class="kh-hot" aria-labelledby="khHotTitle">
                <div class="kh-hot__lead">
                    <span class="kh-hot__pulse" aria-hidden="true"><i class="fa-solid fa-phone-volume"></i></span>
                    <div>
                        <h2 id="khHotTitle">حالة تهدد الحياة الآن؟</h2>
                        <p>لا تنتظر — اتصل فوراً بالرقم المختص، ثم سجّل البلاغ للمتابعة.</p>
                    </div>
                </div>
                <div class="kh-hot__nums">
                    @foreach ($hotlines as [$num, $label, $icon])
                        <a href="tel:{{ $num }}" class="kh-hot__num" aria-label="اتصل بـ{{ $label }} {{ $num }}">
                            <i class="{{ $icon }}" aria-hidden="true"></i>
                            <strong>{{ $num }}</strong>
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- The two paths --}}
            <div class="kh-paths">
                <article class="kh-path kh-path--complaint">
                    <header class="kh-path__head">
                        <span class="kh-path__icon" aria-hidden="true"><i class="fa-solid fa-file-pen"></i></span>
                        <div>
                            <span class="kh-path__kicker">منظومة الشكاوى الحكومية الموحدة</span>
                            <h2 class="kh-path__title">تقديم شكوى</h2>
                        </div>
                    </header>
                    <p class="kh-path__text">لمشكلة في خدمة حكومية، أو تأخر في معاملة، أو ملاحظة على أداء جهة بالمحافظة.</p>
                    <ul class="kh-path__list">
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> تُسجَّل وتُتابع عبر بوابة الشكاوى الرسمية</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> جهّز الرقم القومي ورقم الهاتف</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> أرفق أي مستند يوضح المشكلة إن وُجد</li>
                    </ul>
                    <a href="https://www.shakwa.eg/GCP/Stakeholder/Form.aspx" target="_blank" rel="noopener" class="kh-btn kh-btn--dark">
                        <span>تقديم شكوى</span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </a>
                    <small class="kh-path__note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> سيتم فتح موقع shakwa.eg في نافذة جديدة</small>
                </article>

                <article class="kh-path kh-path--urgent">
                    <header class="kh-path__head">
                        <span class="kh-path__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <div>
                            <span class="kh-path__kicker">مركز سيطرة الطوارئ بالمحافظة</span>
                            <h2 class="kh-path__title">بلاغ عاجل</h2>
                        </div>
                    </header>
                    <p class="kh-path__text">لحريق، حادث، تسريب غاز، انهيار مبنى، تلوث، أو انقطاع مرافق يحتاج تدخلاً سريعاً.</p>
                    <ul class="kh-path__list">
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> يُحوَّل فوراً إلى الجهة المختصة</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> حدّد موقعك بضغطة لمساعدة الفرق في الوصول</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> أرفق صوراً أو فيديو من مكان الحادث</li>
                    </ul>
                    <a href="{{ route('emergency.create') }}" class="kh-btn kh-btn--danger">
                        <span>تسجيل بلاغ عاجل</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </a>
                    <small class="kh-path__note"><i class="fa-solid fa-lock" aria-hidden="true"></i> بياناتك مؤمّنة ولا تُستخدم إلا لمتابعة البلاغ</small>
                </article>
            </div>

            {{-- What happens next (urgent reports) --}}
            <section class="kh-steps" aria-labelledby="khStepsTitle">
                <h2 class="kh-section-title" id="khStepsTitle">ماذا يحدث بعد تسجيل البلاغ العاجل؟</h2>
                <ol class="kh-steps__list">
                    <li><span class="kh-steps__num">1</span><strong>تسجيل البلاغ</strong><small>تحصل على رقم متابعة فور الإرسال</small></li>
                    <li><span class="kh-steps__num">2</span><strong>التحويل للجهة المختصة</strong><small>حسب نوع البلاغ والمركز</small></li>
                    <li><span class="kh-steps__num">3</span><strong>التعامل والمتابعة</strong><small>قد يتم التواصل معك على رقم هاتفك</small></li>
                </ol>
            </section>

            @if ($more->isNotEmpty())
                <section class="kh-more" aria-labelledby="khMoreTitle">
                    <h2 class="kh-section-title" id="khMoreTitle">قنوات أخرى للتواصل</h2>
                    <div class="kh-more__grid">
                        @foreach ($more as [$route, $icon, $title, $desc])
                            <a href="{{ route($route) }}" class="kh-more__card">
                                <span class="kh-more__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                <span class="kh-more__body"><strong>{{ $title }}</strong><small>{{ $desc }}</small></span>
                                <i class="fa-solid fa-arrow-left kh-more__go" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection
