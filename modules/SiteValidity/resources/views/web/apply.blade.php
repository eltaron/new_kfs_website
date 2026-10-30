@extends('layouts.app')
@php
    use Modules\SiteValidity\Models\SiteValidityRequest;
    use Modules\SiteValidity\Support\SvSettings;
    $defaultFee = (float) SvSettings::defaultFee();
    $exts = implode(',', array_map(fn ($e) => '.' . $e, $upload['extensions']));
    $maxMb = (int) ($upload['max_kb'] / 1024);
    $stepOf = [
        'service_type_id' => 1, 'construction_type' => 1,
        'applicant_capacity' => 2, 'applicant_name' => 2, 'national_id' => 2, 'phone' => 2,
        'center_id' => 3, 'village_id' => 3, 'address' => 3, 'notes' => 3, 'latitude' => 3, 'longitude' => 3,
        'sketch' => 4, 'contract' => 4, 'id_card' => 4, 'poa' => 4, 'floors' => 4,
        'agree' => 5, 'request' => 5,
    ];
    $errorStep = 1;
    if ($errors->any()) {
        $errorStep = collect($errors->keys())->map(fn ($k) => $stepOf[$k] ?? 5)->min();
    }
    // [العنوان، الشرح، الأيقونة، إلزامي، يظهر عند (حقل=قيمة)]
    $uploads = [
        'sketch' => ['الكروكي', 'رسم توضيحي لموقع الأرض وحدودها والشوارع المحيطة.', 'fa-solid fa-draw-polygon', true, null],
        'contract' => ['صورة عقد الملكية (اختياري)', 'صورة واضحة لعقد الملكية أو ما يثبت الحيازة إن وُجد.', 'fa-solid fa-file-contract', false, null],
        'id_card' => ['صورة البطاقة', 'صورة بطاقة الرقم القومي (الوجهان في ملف واحد إن أمكن).', 'fa-solid fa-id-card', true, null],
        'poa' => ['صورة التوكيل', 'صورة واضحة من التوكيل الصادر لك من المالك.', 'fa-solid fa-file-signature', true, 'applicant_capacity=' . SiteValidityRequest::CAPACITY_AGENT],
        'floors' => ['ملف موقف الأدوار القائمة', 'ما يوضح الأدوار المبنية حالياً وموقفها (ترخيص سابق / تصالح / شهادة).', 'fa-solid fa-building', true, 'construction_type=' . SiteValidityRequest::CONSTRUCTION_EXTENSION],
    ];
    $choice = fn (string $name, string $default) => (string) old($name, $default);
@endphp
@section('title', 'تقديم ' . SvSettings::get('page_title'))
@push('css')
    <link rel="stylesheet" href="{{ $svCssUrl }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="{{ asset('css/sv-location-picker.css') }}?v={{ @filemtime(public_path('css/sv-location-picker.css')) }}">
@endpush
@section('content')
    <x-kfs.page-hero size="compact" :title="'تقديم ' . SvSettings::get('page_title')" kicker="الخدمات الإلكترونية" icon="fa-solid fa-file-circle-plus"
        subtitle="املأ البيانات على خمس خطوات قصيرة، وراجعها قبل الإرسال."
        :crumbs="[SvSettings::get('page_title') => route('site-validity.landing')]" current="تقديم طلب" />

    <section class="sv-page">
        <div class="container">
            <div class="sv-layout">
                <form method="POST" action="{{ route('site-validity.store') }}" enctype="multipart/form-data" class="sv-card" id="svForm" novalidate
                      data-step="{{ $errorStep }}" data-villages-url="{{ url('site-validity-request/villages') }}" data-old-village="{{ old('village_id') }}">
                    @csrf

                    <ol class="sv-progress" aria-label="مراحل الطلب">
                        <li data-for="1">الخدمة</li>
                        <li data-for="2">البيانات الشخصية</li>
                        <li data-for="3">الموقع</li>
                        <li data-for="4">المستندات</li>
                        <li data-for="5">المراجعة والرسوم</li>
                    </ol>

                    @if ($errors->has('request'))
                        <div class="sv-alert sv-alert--danger" role="alert">{{ $errors->first('request') }}</div>
                    @elseif ($errors->any())
                        <div class="sv-alert sv-alert--danger" role="alert">يرجى مراجعة الحقول المظللة باللون الأحمر.</div>
                    @endif

                    {{-- 1. الخدمة --}}
                    <fieldset class="sv-step" data-step="1">
                        <legend class="sv-step__title">اختر نوع الخدمة</legend>
                        <p class="sv-step__lead">تختلف الرسوم حسب نوع الخدمة.</p>
                        <div class="sv-options" role="radiogroup">
                            @forelse ($types as $type)
                                @php $fee = (float) $type->fee > 0 ? (float) $type->fee : $defaultFee; @endphp
                                <label class="sv-option">
                                    <input type="radio" name="service_type_id" value="{{ $type->id }}" data-fee="{{ number_format($fee, 2, '.', '') }}" data-name="{{ $type->name }}"
                                        @checked((string) old('service_type_id', $types->count() === 1 ? $type->id : '') === (string) $type->id) required>
                                    <span class="sv-option__box">
                                        <span class="sv-type__name">{{ $type->name }}</span>
                                        @if ($type->description)<span class="sv-type__desc">{{ $type->description }}</span>@endif
                                        <span class="sv-fee">{{ number_format($fee, 2) }} <small>ج.م</small></span>
                                    </span>
                                </label>
                            @empty
                                <div class="sv-alert sv-alert--warning">لا توجد خدمات متاحة حالياً.</div>
                            @endforelse
                        </div>
                        @error('service_type_id')<span class="sv-error">{{ $message }}</span>@enderror

                        <p class="sv-label mt-4 mb-2">نوع الصلاحية <span class="req">*</span></p>
                        <div class="sv-options" role="radiogroup">
                            @foreach ([SiteValidityRequest::CONSTRUCTION_NEW => ['إنشاء جديد', 'أرض فضاء أو مبنى جديد بالكامل.'], SiteValidityRequest::CONSTRUCTION_EXTENSION => ['تعلية', 'إضافة أدوار فوق مبنى قائم — يلزم إرفاق ملف موقف الأدوار القائمة.']] as $value => [$name, $desc])
                                <label class="sv-option">
                                    <input type="radio" name="construction_type" value="{{ $value }}" data-name="{{ $name }}" required
                                        @checked($choice('construction_type', SiteValidityRequest::CONSTRUCTION_NEW) === $value)>
                                    <span class="sv-option__box">
                                        <span class="sv-type__name">{{ $name }}</span>
                                        <span class="sv-type__desc">{{ $desc }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('construction_type')<span class="sv-error">{{ $message }}</span>@enderror
                    </fieldset>

                    {{-- 2. البيانات الشخصية --}}
                    <fieldset class="sv-step" data-step="2">
                        <legend class="sv-step__title">بيانات مقدم الطلب</legend>
                        <p class="sv-step__lead">تم ملء البيانات من حسابك ويمكنك تعديلها.</p>
                        <p class="sv-label mb-2">صفة مقدم الطلب <span class="req">*</span></p>
                        <div class="sv-options mb-3" role="radiogroup">
                            @foreach ([SiteValidityRequest::CAPACITY_OWNER => ['المالك بنفسه', 'أتقدم بالطلب عن أرضي / مبناي.'], SiteValidityRequest::CAPACITY_AGENT => ['وكيل عن المالك', 'أتقدم بتوكيل من المالك — يلزم إرفاق صورة التوكيل.']] as $value => [$name, $desc])
                                <label class="sv-option">
                                    <input type="radio" name="applicant_capacity" value="{{ $value }}" data-name="{{ $name }}" required
                                        @checked($choice('applicant_capacity', SiteValidityRequest::CAPACITY_OWNER) === $value)>
                                    <span class="sv-option__box">
                                        <span class="sv-type__name">{{ $name }}</span>
                                        <span class="sv-type__desc">{{ $desc }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('applicant_capacity')<span class="sv-error">{{ $message }}</span>@enderror
                        <div class="sv-fields">
                            <div class="sv-span">
                                <label class="sv-label" for="applicant_name">الاسم رباعياً <span class="req">*</span></label>
                                <input class="sv-input @error('applicant_name') is-invalid @enderror" id="applicant_name" name="applicant_name" maxlength="120" required autocomplete="name"
                                       value="{{ old('applicant_name', $user->name) }}">
                                @error('applicant_name')<span class="sv-error">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="sv-label" for="national_id">الرقم القومي <span class="req">*</span></label>
                                <input class="sv-input @error('national_id') is-invalid @enderror" id="national_id" name="national_id" inputmode="numeric" maxlength="14" pattern="\d{14}" required dir="ltr"
                                       value="{{ old('national_id', $user->national_id) }}">
                                <span class="sv-help">14 رقماً كما في البطاقة.</span>
                                @error('national_id')<span class="sv-error">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="sv-label" for="phone">رقم المحمول <span class="req">*</span></label>
                                <input class="sv-input @error('phone') is-invalid @enderror" id="phone" name="phone" inputmode="tel" maxlength="11" required dir="ltr" autocomplete="tel"
                                       value="{{ old('phone', $user->phone) }}" placeholder="01xxxxxxxxx">
                                @error('phone')<span class="sv-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- 3. الموقع --}}
                    <fieldset class="sv-step" data-step="3">
                        <legend class="sv-step__title">موقع الأرض</legend>
                        <p class="sv-step__lead">اختر المركز أولاً ثم المدينة أو القرية.</p>
                        <div class="sv-fields">
                            <div>
                                <label class="sv-label" for="center_id">المركز <span class="req">*</span></label>
                                <select class="sv-select @error('center_id') is-invalid @enderror" id="center_id" name="center_id" required>
                                    <option value="">— اختر المركز —</option>
                                    @foreach ($centres as $c)
                                        <option value="{{ $c->id }}" @selected((string) old('center_id') === (string) $c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('center_id')<span class="sv-error">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="sv-label" for="village_id">المدينة أو القرية <span class="req">*</span></label>
                                <select class="sv-select @error('village_id') is-invalid @enderror" id="village_id" name="village_id" required disabled>
                                    <option value="">— اختر المركز أولاً —</option>
                                </select>
                                @error('village_id')<span class="sv-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="sv-span">
                                <label class="sv-label" for="address">عنوان الموقع / وصف المكان <span class="req">*</span></label>
                                <input class="sv-input @error('address') is-invalid @enderror" id="address" name="address" maxlength="300" required
                                       value="{{ old('address') }}" placeholder="مثال: شارع الجلاء — بجوار مدرسة ... — قطعة رقم ...">
                                @error('address')<span class="sv-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="sv-span">
                                <span class="sv-label" id="svLocLabel">موقع الأرض على الخريطة (اختياري — يُسرّع المعاينة)</span>
                                <div class="sv-picker" aria-labelledby="svLocLabel">
                                    <div id="svLocMap" class="sv-picker__map" role="application" aria-label="خريطة لتحديد موقع الأرض"></div>
                                    <div class="sv-picker__bar"><span id="svLocStatus">اضغط على الخريطة في مكان الأرض، أو استخدم زر «موقعي الحالي» (⌖) إن كنت في الموقع الآن.</span></div>
                                </div>
                                <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                                <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                                @error('latitude')<span class="sv-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="sv-span">
                                <label class="sv-label" for="notes">ملاحظات (اختياري)</label>
                                <textarea class="sv-textarea @error('notes') is-invalid @enderror" id="notes" name="notes" maxlength="1000" rows="3">{{ old('notes') }}</textarea>
                                @error('notes')<span class="sv-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- 4. المستندات --}}
                    <fieldset class="sv-step" data-step="4">
                        <legend class="sv-step__title">المستندات</legend>
                        <p class="sv-step__lead">PDF أو صورة (JPG / PNG) — بحد أقصى {{ $maxMb }} ميجابايت لكل ملف.</p>
                        <div class="sv-fields">
                            @foreach ($uploads as $field => [$label, $help, $icon, $isRequired, $when])
                                <div class="sv-span" @if ($when) data-when="{{ $when }}" @endif>
                                    <label class="sv-label" for="{{ $field }}">{{ $label }} @if ($isRequired)<span class="req">*</span>@endif</label>
                                    <div class="sv-drop @error($field) is-invalid @enderror" data-drop>
                                        <span class="sv-drop__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                        <span class="sv-drop__text">
                                            <span class="sv-drop__name" data-name>اضغط لاختيار الملف أو اسحبه هنا</span>
                                            <span class="sv-help">{{ $help }}</span>
                                        </span>
                                        <input type="file" id="{{ $field }}" name="{{ $field }}" accept="{{ $exts }}" @required($isRequired) data-max="{{ $upload['max_kb'] }}">
                                    </div>
                                    @error($field)<span class="sv-error">{{ $message }}</span>@enderror
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- 5. المراجعة --}}
                    <fieldset class="sv-step" data-step="5">
                        <legend class="sv-step__title">مراجعة الطلب والرسوم</legend>
                        <p class="sv-step__lead">تأكد من صحة البيانات قبل الإرسال.</p>
                        <dl class="sv-review">
                            <div><dt>نوع الخدمة</dt><dd data-review="service">—</dd></div>
                            <div><dt>نوع الصلاحية</dt><dd data-review="construction_type">—</dd></div>
                            <div><dt>الاسم</dt><dd data-review="applicant_name">—</dd></div>
                            <div><dt>الصفة</dt><dd data-review="applicant_capacity">—</dd></div>
                            <div><dt>الرقم القومي</dt><dd data-review="national_id" dir="ltr">—</dd></div>
                            <div><dt>المحمول</dt><dd data-review="phone" dir="ltr">—</dd></div>
                            <div><dt>المركز</dt><dd data-review="center">—</dd></div>
                            <div><dt>المدينة أو القرية</dt><dd data-review="village">—</dd></div>
                            <div style="grid-column: 1 / -1"><dt>العنوان</dt><dd data-review="address">—</dd></div>
                            <div style="grid-column: 1 / -1"><dt>موقع الأرض</dt><dd data-review="location">—</dd></div>
                            <div style="grid-column: 1 / -1"><dt>المستندات</dt><dd data-review="files">—</dd></div>
                        </dl>
                        <div class="sv-costbox">
                            <span>
                                <span class="d-block fw-bold">رسوم الخدمة</span>
                                <span class="sv-small sv-muted">{{ SvSettings::get('cost_note') }}</span>
                            </span>
                            <strong><span data-review="fee">—</span> ج.م</strong>
                        </div>
                        @if (SvSettings::paymentRequired())
                            <div class="sv-alert sv-alert--warning mt-3 mb-0"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> يبدأ تحديد موعد المعاينة بعد سداد الرسوم إلكترونياً من صفحة الطلب.</div>
                        @endif
                        <div class="form-check mt-3">
                            <input class="form-check-input @error('agree') is-invalid @enderror" type="checkbox" id="agree" name="agree" value="1" required @checked(old('agree'))>
                            <label class="form-check-label" for="agree">أقر بصحة البيانات والمستندات المرفقة، وأعلم أن تقديم بيانات غير صحيحة يعرضني للمساءلة.</label>
                            @error('agree')<span class="sv-error">{{ $message }}</span>@enderror
                        </div>
                    </fieldset>

                    <div class="sv-actions">
                        <button type="button" class="sv-btn sv-btn--ghost" data-prev><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> السابق</button>
                        <button type="button" class="sv-btn" data-next>التالي <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                        <button type="submit" class="sv-btn sv-btn--gold" data-submit><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> إرسال الطلب</button>
                    </div>
                </form>

                <aside class="sv-card sv-cta" aria-label="معلومات">
                    <h2 class="sv-card__title"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> قبل أن تبدأ</h2>
                    <ul class="sv-checks sv-small">
                        @foreach ($documents as $doc)
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ $doc }}</span></li>
                        @endforeach
                    </ul>
                    <hr>
                    <p class="sv-small sv-muted mb-2">بعد الإرسال ستحصل على رقم طلب يمكنك متابعته من «طلباتي».</p>
                    <a href="{{ route('site-validity.requests.index') }}" class="sv-btn sv-btn--ghost sv-btn--block"><i class="fa-solid fa-list-check" aria-hidden="true"></i> طلباتي السابقة</a>
                </aside>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('js/sv-location-picker.js') }}?v={{ @filemtime(public_path('js/sv-location-picker.js')) }}"></script>
<script>
// منتقي موقع الأرض (اختياري) — يُنشأ عند ظهور خطوة «الموقع» (الخريطة لا تُرسم داخل عنصر مخفي)
(function () {
    var el = document.getElementById('svLocMap'), lat = document.getElementById('latitude'), lng = document.getElementById('longitude');
    var status = document.getElementById('svLocStatus'), centre = document.getElementById('center_id');
    if (!el || !window.svLocationPicker) return;
    var picker = null;
    var name = function () { return centre && centre.selectedIndex > 0 ? centre.options[centre.selectedIndex].text.trim() : null; };
    var start = function () {
        if (picker || el.offsetParent === null) return;
        picker = svLocationPicker(el, {
            lat: lat.value || null, lng: lng.value || null, centre: name(),
            boundaries: @js(asset((string) config('site-validity.map.boundaries'))),
            points: @js((array) config('site-validity.map.points', [])),
            bounds: @js(\Modules\SiteValidity\Models\SiteValidityRequest::KFS_BOUNDS),
            onChange: function (a, b) {
                lat.value = a === null ? '' : a; lng.value = b === null ? '' : b;
                status.innerHTML = a === null ? 'لم يُحدد الموقع.' : '<span class="is-set">✓ تم تحديد موقع الأرض: <b>' + a.toFixed(6) + ', ' + b.toFixed(6) + '</b></span>';
            },
        });
        if (lat.value) status.innerHTML = '<span class="is-set">✓ تم تحديد موقع الأرض: <b>' + Number(lat.value).toFixed(6) + ', ' + Number(lng.value).toFixed(6) + '</b></span>';
    };
    if ('IntersectionObserver' in window) new IntersectionObserver(function (e) { if (e[0].isIntersecting) start(); }).observe(el);
    if (centre) centre.addEventListener('change', function () { start(); if (picker) picker.setCentre(name()); });
})();
</script>
<script>
(function () {
    var form = document.getElementById('svForm');
    if (!form) return;
    var steps = Array.prototype.slice.call(form.querySelectorAll('.sv-step'));
    var bar = Array.prototype.slice.call(form.querySelectorAll('.sv-progress li'));
    var prev = form.querySelector('[data-prev]'), next = form.querySelector('[data-next]'), submit = form.querySelector('[data-submit]');
    var current = parseInt(form.dataset.step || '1', 10);

    function show(n) {
        current = Math.max(1, Math.min(steps.length, n));
        steps.forEach(function (s) { s.hidden = parseInt(s.dataset.step, 10) !== current; });
        bar.forEach(function (li) {
            var i = parseInt(li.dataset.for, 10);
            li.classList.toggle('is-done', i < current);
            li.classList.toggle('is-current', i === current);
            if (i === current) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
        });
        prev.hidden = current === 1;
        next.hidden = current === steps.length;
        submit.hidden = current !== steps.length;
        if (current === steps.length) review();
    }

    function stepValid(n) {
        var ok = true, first = null;
        steps[n - 1].querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.disabled && el.id !== 'village_id') return;
            var valid = el.checkValidity();
            if (el.id === 'village_id' && !el.value) valid = false;
            if (el.type === 'file' && el.files[0] && el.files[0].size > parseInt(el.dataset.max, 10) * 1024) {
                valid = false;
            }
            var box = el.type === 'file' ? el.closest('[data-drop]') : (el.type === 'radio' ? null : el);
            if (box) box.classList.toggle('is-invalid', !valid);
            if (!valid) { ok = false; first = first || el; }
        });
        if (first) { (first.type === 'file' ? first.closest('[data-drop]') : first).scrollIntoView({ behavior: 'smooth', block: 'center' }); if (first.type !== 'file') first.focus({ preventScroll: true }); }
        return ok;
    }

    function text(sel) { var el = form.querySelector(sel); return el ? el.value : ''; }
    function review() {
        var svc = form.querySelector('input[name=service_type_id]:checked');
        var centre = form.querySelector('#center_id'), village = form.querySelector('#village_id');
        var set = function (k, v) { var el = form.querySelector('[data-review="' + k + '"]'); if (el) el.textContent = v || '—'; };
        set('service', svc ? svc.dataset.name : '');
        set('fee', svc ? Number(svc.dataset.fee).toLocaleString('ar-EG', { minimumFractionDigits: 2 }) : '');
        var pick = function (n) { var el = form.querySelector('input[name=' + n + ']:checked'); return el ? el.dataset.name : ''; };
        set('construction_type', pick('construction_type'));
        set('applicant_capacity', pick('applicant_capacity'));
        set('applicant_name', text('#applicant_name'));
        set('national_id', text('#national_id'));
        set('phone', text('#phone'));
        set('center', centre.selectedIndex > 0 ? centre.options[centre.selectedIndex].text : '');
        set('village', village.selectedIndex > 0 ? village.options[village.selectedIndex].text : '');
        set('address', text('#address'));
        set('location', text('#latitude') ? '✓ محدد على الخريطة (' + Number(text('#latitude')).toFixed(5) + '، ' + Number(text('#longitude')).toFixed(5) + ')' : 'غير محدد');
        var names = [];
        form.querySelectorAll('input[type=file]').forEach(function (f) { if (!f.disabled && f.files[0]) names.push(f.files[0].name); });
        set('files', names.join(' · '));
    }

    next.addEventListener('click', function () { if (stepValid(current)) { show(current + 1); form.scrollIntoView({ behavior: 'smooth' }); } });
    prev.addEventListener('click', function () { show(current - 1); form.scrollIntoView({ behavior: 'smooth' }); });
    form.addEventListener('submit', function (e) {
        for (var i = 1; i <= steps.length; i++) { if (!stepValid(i)) { e.preventDefault(); show(i); return; } }
        submit.disabled = true;
        submit.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> جارٍ الإرسال...';
    });

    // Cascading centre → city/village
    var centre = form.querySelector('#center_id'), village = form.querySelector('#village_id');
    var oldVillage = form.dataset.oldVillage;
    function loadVillages(id) {
        village.innerHTML = '<option value="">' + (id ? 'جارٍ التحميل...' : '— اختر المركز أولاً —') + '</option>';
        village.disabled = true;
        if (!id) return;
        fetch(form.dataset.villagesUrl + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (list) {
                village.innerHTML = '<option value="">— اختر المدينة أو القرية —</option>';
                list.forEach(function (v) {
                    var o = document.createElement('option');
                    o.value = v.id; o.textContent = v.name + ' (' + v.type + ')';
                    if (String(v.id) === String(oldVillage)) o.selected = true;
                    village.appendChild(o);
                });
                village.disabled = list.length === 0;
            })
            .catch(function () { village.innerHTML = '<option value="">تعذر التحميل — أعد المحاولة</option>'; });
    }
    centre.addEventListener('change', function () { oldVillage = ''; loadVillages(centre.value); });
    if (centre.value) loadVillages(centre.value);

    // File previews
    form.querySelectorAll('[data-drop]').forEach(function (drop) {
        var input = drop.querySelector('input[type=file]'), name = drop.querySelector('[data-name]'), icon = drop.querySelector('.sv-drop__icon');
        var original = icon.innerHTML;
        input.addEventListener('change', function () {
            var f = input.files[0];
            drop.classList.toggle('has-file', !!f);
            drop.classList.remove('is-invalid');
            if (!f) { name.textContent = 'اضغط لاختيار الملف أو اسحبه هنا'; icon.innerHTML = original; return; }
            var tooBig = f.size > parseInt(input.dataset.max, 10) * 1024;
            name.textContent = f.name + ' — ' + (f.size / 1048576).toFixed(2) + ' م.ب' + (tooBig ? ' (أكبر من المسموح)' : '');
            if (tooBig) drop.classList.add('is-invalid');
            if (/^image\//.test(f.type)) {
                var img = document.createElement('img'); img.alt = '';
                img.src = URL.createObjectURL(f); icon.innerHTML = ''; icon.appendChild(img);
            } else {
                icon.innerHTML = '<i class="fa-solid fa-file-pdf"></i>';
            }
        });
    });

    // مستندات مشروطة: صورة التوكيل للوكيل، وموقف الأدوار القائمة للتعلية (المخفي يُعطَّل فلا يُرسل ولا يُتحقق منه)
    function syncConditional() {
        form.querySelectorAll('[data-when]').forEach(function (box) {
            var parts = box.dataset.when.split('='), checked = form.querySelector('input[name=' + parts[0] + ']:checked');
            var on = !!checked && checked.value === parts[1];
            box.hidden = !on;
            box.querySelectorAll('input').forEach(function (i) { i.disabled = !on; });
        });
    }
    form.querySelectorAll('input[name=applicant_capacity], input[name=construction_type]').forEach(function (r) { r.addEventListener('change', syncConditional); });
    syncConditional();

    show(current);
})();
</script>
@endpush
