@extends('layouts.app')
@section('title', 'تقديم بلاغ عاجل')
@section('description', 'سجّل بلاغاً عن حالة طارئة في محافظة كفر الشيخ ليُحوَّل فوراً إلى الجهة المختصة.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-help.css') }}?v={{ @filemtime(public_path('css/kfs-help.css')) }}">
@endpush

@section('content')
    @php
        $typeIcons = [
            'حريق' => 'fa-solid fa-fire',
            'حادث مروري' => 'fa-solid fa-car-burst',
            'تلوث بيئي' => 'fa-solid fa-smog',
            'انهيار مبنى' => 'fa-solid fa-house-crack',
            'تسريب غاز' => 'fa-solid fa-wind',
            'انقطاع مرافق' => 'fa-solid fa-plug-circle-xmark',
            'أخرى' => 'fa-solid fa-circle-exclamation',
        ];
        $hotlines = [['123', 'الإسعاف'], ['122', 'شرطة النجدة'], ['180', 'الحماية المدنية']];
        $success = session('success');
        $trackingNo = $success && preg_match('/(\d+)\s*$/u', $success, $m) ? $m[1] : null;
    @endphp

    <main class="kh kh--emergency">
        {{-- .emergency-hero kept as a hook for the dark-theme layer --}}
        <x-kfs.page-hero size="compact" class="emergency-hero" title="تقديم بلاغ عاجل" kicker="مركز سيطرة الطوارئ"
            icon="fa-solid fa-triangle-exclamation" :crumbs="['الشكاوى والبلاغات' => route('requests.index')]"
            subtitle="املأ البيانات التالية — يُحوَّل بلاغك فوراً إلى الجهة المختصة." />

        <div class="container kh__wrap">
            @if ($success)
                <section class="kh-done" role="status" aria-live="polite">
                    <span class="kh-done__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                    <div class="kh-done__body">
                        <h2>تم استلام بلاغك بنجاح</h2>
                        @if ($trackingNo)
                            <p>رقم المتابعة: <strong class="kh-done__no">{{ $trackingNo }}</strong> — احتفظ به للمتابعة.</p>
                        @else
                            <p>{{ $success }}</p>
                        @endif
                        <p class="kh-done__hint">إذا كانت الحالة تهدد الحياة، اتصل أيضاً بالرقم المختص من القائمة الجانبية.</p>
                    </div>
                    <a href="{{ route('home') }}" class="kh-btn kh-btn--ghost">العودة للرئيسية</a>
                </section>
            @endif

            @if ($errors->any())
                <section class="kh-errors" role="alert">
                    <h2><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> يرجى مراجعة البيانات التالية</h2>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="kh-form-layout">
                <form action="{{ route('emergency.store') }}" method="POST" enctype="multipart/form-data" class="kh-form" id="khForm" novalidate>
                    @csrf
                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

                    {{-- 1. Type --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">1</span> ما نوع الحالة؟ <span class="kh-req">*</span></legend>
                        <div class="kh-types" role="radiogroup" aria-label="نوع البلاغ">
                            @foreach ($reportTypes as $type)
                                <label class="kh-type">
                                    <input type="radio" name="report_type" value="{{ $type }}" required @checked(old('report_type') === $type)>
                                    <span class="kh-type__box">
                                        <i class="{{ $typeIcons[$type] ?? 'fa-solid fa-circle-exclamation' }}" aria-hidden="true"></i>
                                        <span>{{ $type }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('report_type')<p class="kh-field-error">{{ $message }}</p>@enderror
                    </fieldset>

                    {{-- 2. Location --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">2</span> أين تقع الحالة؟</legend>

                        <div class="kh-loc-btn-row">
                            <button type="button" id="getLocationBtn" class="kh-loc-btn">
                                <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                                <span>تحديد موقعي الحالي تلقائياً</span>
                            </button>
                            <small class="kh-loc-status" id="locStatus" aria-live="polite">
                                {{ old('latitude') ? 'تم إرفاق الإحداثيات' : 'يساعد فرق الطوارئ على الوصول بدقة' }}
                            </small>
                        </div>

                        <div class="kh-grid">
                            <div class="kh-field kh-field--full">
                                <span class="kh-label">نوع المكان <span class="kh-req">*</span></span>
                                <div class="kh-seg" role="radiogroup" aria-label="نوع المكان">
                                    <label><input type="radio" name="location_type" value="مدينة" required @checked(old('location_type', 'مدينة') === 'مدينة')><span><i class="fa-solid fa-city" aria-hidden="true"></i> مدينة</span></label>
                                    <label><input type="radio" name="location_type" value="قرية" required @checked(old('location_type') === 'قرية')><span><i class="fa-solid fa-house-chimney" aria-hidden="true"></i> قرية</span></label>
                                </div>
                            </div>
                            <div class="kh-field">
                                <label for="center" class="kh-label">المركز <span class="kh-req">*</span></label>
                                <select class="kh-input" id="center" name="center" required>
                                    <option value="" disabled @selected(! old('center'))>اختر المركز</option>
                                    @foreach ($centers as $center)
                                        <option value="{{ $center->name }}" data-id="{{ $center->id }}" @selected(old('center') === $center->name)>{{ $center->name }}</option>
                                    @endforeach
                                </select>
                                @error('center')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="area" class="kh-label">القرية / المدينة <span class="kh-req">*</span></label>
                                <select class="kh-input" id="area" name="area" required data-old="{{ old('area') }}">
                                    <option value="" disabled selected>اختر المركز أولاً</option>
                                </select>
                                @error('area')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field kh-field--full">
                                <label for="location_description" class="kh-label">وصف الموقع بالتفصيل <span class="kh-req">*</span></label>
                                <textarea id="location_description" name="location_description" class="kh-input" rows="3" required
                                    placeholder="مثال: الشارع الرئيسي بجوار مدرسة …، أمام محطة الوقود">{{ old('location_description') }}</textarea>
                                @error('location_description')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- 3. Details --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">3</span> تفاصيل ومرفقات</legend>
                        <div class="kh-grid">
                            <div class="kh-field kh-field--full">
                                <label for="details" class="kh-label">ماذا يحدث؟ <span class="kh-opt">(اختياري)</span></label>
                                <textarea name="details" id="details" class="kh-input" rows="4"
                                    placeholder="صف الحالة: عدد المصابين إن وُجد، مدى الخطورة، منذ متى بدأت…">{{ old('details') }}</textarea>
                            </div>
                            <div class="kh-field kh-field--full">
                                <span class="kh-label">صور أو فيديو أو مستندات <span class="kh-opt">(اختياري)</span></span>
                                <label class="kh-drop" for="attachments">
                                    <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                                    <strong>اضغط لاختيار الملفات</strong>
                                    <small>JPG · PNG · PDF · MP4 — حتى 10 ميجابايت للملف</small>
                                </label>
                                <input type="file" name="attachments[]" id="attachments" class="kh-drop__input" multiple accept=".jpg,.jpeg,.png,.pdf,.mp4">
                                <ul class="kh-files" id="khFiles" aria-live="polite"></ul>
                                @error('attachments.*')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- 4. Reporter --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">4</span> بيانات مقدم البلاغ</legend>
                        <div class="kh-grid">
                            <div class="kh-field">
                                <label for="reporter_name" class="kh-label">الاسم بالكامل <span class="kh-req">*</span></label>
                                <input type="text" id="reporter_name" name="reporter_name" class="kh-input" value="{{ old('reporter_name', auth()->user()?->name) }}" required autocomplete="name">
                                @error('reporter_name')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="reporter_phone" class="kh-label">رقم الهاتف <span class="kh-req">*</span></label>
                                <input type="tel" id="reporter_phone" name="reporter_phone" class="kh-input" dir="ltr" inputmode="tel" autocomplete="tel"
                                    value="{{ old('reporter_phone', auth()->user()?->phone) }}" required placeholder="01XXXXXXXXX">
                                @error('reporter_phone')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="reporter_national_id" class="kh-label">الرقم القومي <span class="kh-req">*</span></label>
                                <input type="text" id="reporter_national_id" name="reporter_national_id" class="kh-input" dir="ltr" inputmode="numeric"
                                    pattern="\d{14}" maxlength="14" title="14 رقماً" value="{{ old('reporter_national_id', auth()->user()?->national_id) }}" required placeholder="14 رقماً">
                                @error('reporter_national_id')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <div class="kh-submit">
                        <button type="submit" class="kh-btn kh-btn--danger kh-btn--lg" id="khSubmit">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>إرسال البلاغ الآن</span>
                        </button>
                        <p><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> بياناتك مؤمّنة ويتم التعامل معها بسرية تامة.</p>
                    </div>
                </form>

                {{-- Side panel --}}
                <aside class="kh-side" aria-label="أرقام الطوارئ ونصائح">
                    <div class="kh-side__card kh-side__card--hot">
                        <h2><i class="fa-solid fa-phone-volume" aria-hidden="true"></i> خطر على الحياة؟ اتصل الآن</h2>
                        <div class="kh-side__nums">
                            @foreach ($hotlines as [$num, $label])
                                <a href="tel:{{ $num }}"><strong>{{ $num }}</strong><span>{{ $label }}</span></a>
                            @endforeach
                        </div>
                    </div>
                    <div class="kh-side__card">
                        <h2><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> بلاغ أوضح = استجابة أسرع</h2>
                        <ul>
                            <li>اضغط «تحديد موقعي» إذا كنت في مكان الحادث.</li>
                            <li>اذكر علامة مميزة قريبة من الموقع.</li>
                            <li>صورة واحدة واضحة تكفي — لا تعرّض نفسك للخطر.</li>
                            <li>اكتب رقم هاتف متاح للتواصل معك.</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<script>
(function () {
    const centerSelect = document.getElementById('center');
    const areaSelect = document.getElementById('area');
    const oldArea = areaSelect.dataset.old || '';
    const opt = (value, label, opts = {}) => { const o = new Option(label, value); if (opts.disabled) o.disabled = true; if (opts.selected) o.selected = true; return o; };

    // Villages / cities of the selected centre
    centerSelect.addEventListener('change', function () {
        const centerId = this.options[this.selectedIndex]?.dataset?.id;
        areaSelect.replaceChildren(opt('', centerId ? 'جاري التحميل…' : 'اختر المركز أولاً', { disabled: true, selected: true }));
        if (!centerId) return;
        fetch(`{{ url('emergency-report/villages') }}/${encodeURIComponent(centerId)}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(list => {
                areaSelect.replaceChildren(opt('', list.length ? 'اختر القرية / المدينة' : 'لا توجد قرى مضافة لهذا المركز', { disabled: true, selected: true }));
                const groups = { city: 'المدن', village: 'القرى' };
                Object.keys(groups).forEach(type => {
                    const items = list.filter(v => (v.type || 'village') === type);
                    if (!items.length) return;
                    const g = document.createElement('optgroup'); g.label = groups[type];
                    items.forEach(v => g.append(opt(v.name, v.name, { selected: v.name === oldArea })));
                    areaSelect.append(g);
                });
            })
            .catch(() => areaSelect.replaceChildren(opt('', 'تعذر التحميل — أعد اختيار المركز', { disabled: true, selected: true })));
    });
    if (centerSelect.value) centerSelect.dispatchEvent(new Event('change'));

    // Geolocation
    const btn = document.getElementById('getLocationBtn');
    const status = document.getElementById('locStatus');
    const lat = document.getElementById('latitude');
    const lng = document.getElementById('longitude');
    if (lat.value) btn.classList.add('is-done');
    btn.addEventListener('click', () => {
        if (!navigator.geolocation) { status.textContent = 'المتصفح لا يدعم تحديد الموقع.'; return; }
        btn.disabled = true; btn.classList.remove('is-done', 'is-error');
        btn.querySelector('span').textContent = 'جاري تحديد الموقع…';
        navigator.geolocation.getCurrentPosition(pos => {
            lat.value = pos.coords.latitude.toFixed(7);
            lng.value = pos.coords.longitude.toFixed(7);
            btn.disabled = false; btn.classList.add('is-done');
            btn.querySelector('span').textContent = 'تم تحديد الموقع';
            status.textContent = 'الدقة التقريبية: ' + Math.round(pos.coords.accuracy) + ' متر';
        }, () => {
            btn.disabled = false; btn.classList.add('is-error');
            btn.querySelector('span').textContent = 'إعادة المحاولة';
            status.textContent = 'تعذر الوصول للموقع — فعّل خدمة الموقع في المتصفح، أو اكتب الوصف يدوياً.';
        }, { enableHighAccuracy: true, timeout: 15000 });
    });

    // Attachments list + 10MB check
    const input = document.getElementById('attachments');
    const list = document.getElementById('khFiles');
    const MAX = 10 * 1024 * 1024;
    input.addEventListener('change', () => {
        list.replaceChildren();
        let tooBig = false;
        Array.from(input.files).forEach(f => {
            const li = document.createElement('li');
            const big = f.size > MAX; tooBig = tooBig || big;
            li.className = big ? 'is-error' : '';
            const i = document.createElement('i');
            i.className = f.type.startsWith('image/') ? 'fa-solid fa-image' : f.type.startsWith('video/') ? 'fa-solid fa-film' : 'fa-solid fa-file';
            const name = document.createElement('span'); name.textContent = f.name;
            const size = document.createElement('small'); size.textContent = (f.size / 1048576).toFixed(1) + ' MB' + (big ? ' — أكبر من المسموح' : '');
            li.append(i, name, size); list.append(li);
        });
        input.setCustomValidity(tooBig ? 'أحد الملفات أكبر من 10 ميجابايت' : '');
    });

    // Client-side check before submit: report type + required fields
    const form = document.getElementById('khForm');
    form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) {
            e.preventDefault();
            const first = form.querySelector(':invalid');
            first?.closest('.kh-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(() => first?.reportValidity(), 350);
            return;
        }
        const b = document.getElementById('khSubmit');
        b.disabled = true; b.querySelector('span').textContent = 'جاري الإرسال…';
    });
})();
</script>
@endpush
