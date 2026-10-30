@extends('layouts.app')
@section('title', 'تقديم طلب - ' . $service->name)
@push('css')
    <link rel="stylesheet" href="{{ asset('css/gis-services.css') }}">
@endpush

@section('content')
    @php
        $pricingType = $service->pricing_type ?: 'fixed';
        $settings = $service->pricing_settings ?? [];
        $dynamicFields = collect($service->dynamic_fields ?? [])->filter(fn ($f) => is_array($f) && ! empty($f['name']))->keyBy('name');

        // مدخلات الحاسبة حسب طريقة التسعير (نفس منطق GisPricing).
        $calcInputs = [];
        if (in_array($pricingType, ['formula', 'tiered'], true)) {
            $calcInputs['area'] = ['field' => $priceFields['area'], 'label' => 'المساحة', 'unit' => 'م²'];
        }
        if ($pricingType === 'tiered' && ! empty($settings['point_threshold'])) {
            $calcInputs['points'] = ['field' => $priceFields['points'], 'label' => 'عدد النقاط', 'unit' => 'نقطة'];
        }
        if ($pricingType === 'formula' && isset($settings['static_point_price'])) {
            $calcInputs['static_points'] = ['field' => $priceFields['static_points'], 'label' => 'عدد النقاط الثابتة', 'unit' => 'نقطة'];
        }
        foreach ($calcInputs as $key => $input) {
            $calcInputs[$key]['inForm'] = $dynamicFields->has($input['field']);
            if ($calcInputs[$key]['inForm']) {
                $calcInputs[$key]['label'] = trim($dynamicFields[$input['field']]['label'] ?? $input['label']);
            }
        }
    @endphp

    <main class="gp-page gp-apply">
        <x-kfs.page-hero size="compact" :title="trim($service->name)" :kicker="$service->serviceType->name ?? 'خدمة GIS'"
            icon="fa-solid fa-layer-group"
            :crumbs="['الخدمات المكانية' => route('gis.index'), trim($service->name) => route('gis.service.show', $service->slug)]"
            current="تقديم الطلب">
            <x-slot:meta>
                <span><i class="fa-solid fa-list-ol" aria-hidden="true"></i> 4 خطوات</span>
                <span><i class="fa-solid fa-calculator" aria-hidden="true"></i> حساب الرسوم تلقائياً</span>
                <span><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد إلكتروني</span>
            </x-slot:meta>
        </x-kfs.page-hero>

        <div class="container">
            <div class="gp-layout">
                <div>
                    <ol class="gp-card gp-stepper" aria-label="خطوات التقديم">
                        <li class="step-item active" data-step="1"><span class="step-circle">1</span><span class="step-label">البيانات الشخصية</span></li>
                        <li class="step-item" data-step="2"><span class="step-circle">2</span><span class="step-label">موقع العقار</span></li>
                        <li class="step-item" data-step="3"><span class="step-circle">3</span><span class="step-label">تفاصيل الطلب</span></li>
                        <li class="step-item" data-step="4"><span class="step-circle">4</span><span class="step-label">المراجعة والدفع</span></li>
                    </ol>

                    <form action="{{ route('gis.apply.submit', $service->slug) }}" method="POST" enctype="multipart/form-data" id="applyForm">
                        @csrf

                        {{-- Step 1: Personal Data --}}
                        <div class="wizard-step active" id="step1">
                            <div class="gp-card gp-form">
                                <h2 class="gp-form__title"><span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-user"></i></span> بيانات مقدم الطلب</h2>
                                <p class="gp-form__hint">بيانات المالك تؤخذ من حسابك تلقائياً.</p>
                                @if ($errors->any())
                                    <div class="gp-alert" role="alert">
                                        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                                    </div>
                                @endif
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="ownerName">اسم المالك</label>
                                        <input id="ownerName" type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="applicant_type">صفة مقدم الطلب <span class="text-danger">*</span></label>
                                        <select name="applicant_type" id="applicant_type" class="form-select" required onchange="toggleAgent(this.value)">
                                            <option value="owner" @selected(old('applicant_type') === 'owner')>المالك الأصيل</option>
                                            <option value="agent" @selected(old('applicant_type') === 'agent')>وكيل بموجب توكيل</option>
                                            <option value="other" @selected(old('applicant_type') === 'other')>أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="owner_id_card">صورة بطاقة الرقم القومي للمالك <span class="text-danger">*</span></label>
                                        <input type="file" name="owner_id_card" id="owner_id_card" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                                    </div>
                                </div>
                                <div id="agentSection" class="gp-subbox" style="display:none">
                                    <h3 class="gp-subbox__title"><i class="fa-solid fa-file-signature" aria-hidden="true"></i> بيانات الوكالة</h3>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="agent_name">اسم الوكيل رباعي</label>
                                            <input type="text" name="agent_name" id="agent_name" class="form-control" value="{{ old('agent_name') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="proxy_doc">صورة التوكيل الرسمي</label>
                                            <input type="file" name="proxy_doc" id="proxy_doc" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                        </div>
                                    </div>
                                </div>
                                <div class="gp-form__nav">
                                    <button type="button" class="gp-btn" onclick="goStep(2)">التالي <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Location --}}
                        <div class="wizard-step" id="step2">
                            <div class="gp-card gp-form">
                                <h2 class="gp-form__title"><span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span> الموقع الجغرافي للعقار</h2>
                                <p class="gp-form__hint">اختر المركز ثم الوحدة المحلية ثم القرية بالترتيب.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" for="markaz_select">المركز <span class="text-danger">*</span></label>
                                        <select name="markaz_id" id="markaz_select" class="form-select" onchange="loadUnits(this.value)" required>
                                            <option value="">-- اختر المركز --</option>
                                            @foreach ($markazs as $m)
                                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="unit_select">الوحدة المحلية <span class="text-danger">*</span></label>
                                        <select name="shiakha_id" id="unit_select" class="form-select" onchange="loadVillages(this.value)" required>
                                            <option value="">بانتظار المركز...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="village_select">القرية / العزبة <span class="text-danger">*</span></label>
                                        <select name="village_id" id="village_select" class="form-select" required>
                                            <option value="">بانتظار الوحدة...</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="address">تفاصيل العنوان</label>
                                        <textarea name="address" id="address" class="form-control" rows="2" placeholder="الشارع — المعالم المميزة">{{ old('address') }}</textarea>
                                    </div>
                                </div>
                                <div class="gp-form__nav">
                                    <button type="button" class="gp-btn gp-btn--ghost" onclick="goStep(1)"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> السابق</button>
                                    <button type="button" class="gp-btn" onclick="goStep(3)">التالي <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>

                        {{-- Step 3: Service Details --}}
                        <div class="wizard-step" id="step3">
                            <div class="gp-card gp-form">
                                <h2 class="gp-form__title"><span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></span> بيانات وتفاصيل الخدمة</h2>
                                <p class="gp-form__hint">الرسوم تتحدث تلقائياً في الحاسبة مع كل تغيير.</p>

                                <div class="gp-subbox mt-0 mb-4">
                                    <label class="form-label" for="request_type">بيان الخدمة <span class="text-danger">*</span></label>
                                    <select name="request_type" id="request_type" class="form-select" required onchange="toggleRequestType(this.value)">
                                        <option value="new">تسجيل جديد</option>
                                        <option value="restudy">إعادة دراسة</option>
                                        <option value="duplicate">استخراج بدل فاقد</option>
                                    </select>
                                </div>

                                {{-- New request dynamic fields --}}
                                <div id="newRequestFields">
                                    <div class="row g-3">
                                        @foreach ($service->dynamic_fields ?? [] as $field)
                                            @php $fid = 'df_' . ($field['name'] ?? $loop->index); $req = $field['is_required'] ?? false; @endphp
                                            <div class="col-md-6">
                                                <label class="form-label" for="{{ $fid }}">{{ $field['label'] }} @if ($req)<span class="text-danger">*</span>@endif</label>
                                                @if (($field['type'] ?? 'text') === 'text')
                                                    <input type="text" id="{{ $fid }}" name="form_data[{{ $field['name'] }}]" class="form-control dynamic-input" data-name="{{ $field['name'] }}" {{ $req ? 'required' : '' }}>
                                                @elseif ($field['type'] === 'number')
                                                    <input type="number" id="{{ $fid }}" min="0" step="any" inputmode="decimal" name="form_data[{{ $field['name'] }}]" class="form-control dynamic-input" data-name="{{ $field['name'] }}" {{ $req ? 'required' : '' }}>
                                                @elseif ($field['type'] === 'select')
                                                    <select id="{{ $fid }}" name="form_data[{{ $field['name'] }}]" class="form-select dynamic-input" data-name="{{ $field['name'] }}" {{ $req ? 'required' : '' }}>
                                                        <option value="">-- اختر --</option>
                                                        @foreach (explode("\n", $field['options'] ?? '') as $opt)
                                                            <option value="{{ trim($opt) }}">{{ trim($opt) }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif ($field['type'] === 'file')
                                                    <input type="file" id="{{ $fid }}" name="form_data[{{ $field['name'] }}]" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" {{ $req ? 'required' : '' }}>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Restudy fields --}}
                                <div id="restudyFields" style="display:none">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="prev_code">كود الشهادة السابقة <span class="text-danger">*</span></label>
                                            <input type="text" name="prev_code" id="prev_code" class="form-control">
                                        </div>
                                        <div class="col-md-6" id="restudyReasonCol">
                                            <label class="form-label" for="reason_id">سبب طلب إعادة الدراسة</label>
                                            <select name="reason_id" id="reason_id" class="form-select">
                                                <option value="">-- اختر السبب --</option>
                                                @foreach ($reasons as $r)
                                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="gp-form__nav">
                                    <button type="button" class="gp-btn gp-btn--ghost" onclick="goStep(2)"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> السابق</button>
                                    <button type="button" class="gp-btn" onclick="goStep(4)">المراجعة النهائية <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>

                        {{-- Step 4: Review & Submit --}}
                        <div class="wizard-step" id="step4">
                            <div class="gp-card gp-form">
                                <h2 class="gp-form__title"><span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span> مراجعة الطلب وتأكيد الدفع</h2>
                                <p class="gp-form__hint">راجع الرسوم في الحاسبة. بعد التأكيد ستتم إحالتك إلى بوابة الدفع الإلكتروني.</p>
                                <div class="d-grid gap-2">
                                    <label class="gp-check" for="agreePayment">
                                        <input type="checkbox" id="agreePayment" required>
                                        <span>أوافق على <a href="#" onclick="event.preventDefault(); showTermsModal('payment');">شروط السداد الإلكتروني</a></span>
                                    </label>
                                    <label class="gp-check" for="agreeService">
                                        <input type="checkbox" id="agreeService" required>
                                        <span>أقر باطلاعي على <a href="#" onclick="event.preventDefault(); showTermsModal('service');">شروط الخدمة والضوابط الفنية</a></span>
                                    </label>
                                </div>
                                <div class="gp-form__nav">
                                    <button type="button" class="gp-btn gp-btn--ghost" onclick="goStep(3)"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> تعديل البيانات</button>
                                    <button type="submit" class="gp-btn gp-btn--ok"><i class="fa-solid fa-lock" aria-hidden="true"></i> تأكيد ودفع <span class="gp-calc-total-inline"></span></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- حاسبة الرسوم --}}
                <aside class="gp-aside" aria-label="حاسبة الرسوم">
                    <div class="gp-card gp-pricecard gp-calc" id="gpCalc">
                        <div class="gp-pricecard__head"><i class="fa-solid fa-calculator" aria-hidden="true"></i> حاسبة الرسوم</div>
                        <div class="gp-svcmeta">
                            <strong>{{ trim($service->name) }}</strong>
                            <span>{{ ['fixed' => 'رسوم ثابتة', 'formula' => 'تُحسب حسب المساحة', 'tiered' => 'تُحسب بالشرائح حسب المساحة'][$pricingType] ?? 'رسوم ثابتة' }}</span>
                        </div>

                        @if ($calcInputs)
                            <div class="gp-calc__inputs">
                                @foreach ($calcInputs as $key => $input)
                                    <label class="gp-calc__field">
                                        <span>{{ $input['label'] }}</span>
                                        <span class="gp-calc__control">
                                            <input type="number" min="0" step="any" inputmode="decimal" class="gp-input" placeholder="0"
                                                data-gp-calc="{{ $key }}" data-field="{{ $input['field'] }}"
                                                @unless ($input['inForm']) name="form_data[{{ $input['field'] }}]" form="applyForm" @endunless>
                                            <em>{{ $input['unit'] }}</em>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div class="gp-calc__method">
                            <div class="gp-calc__title">طريقة الحساب</div>
                            <ol class="gp-calc__lines" id="gpCalcLines" aria-live="polite"></ol>
                        </div>
                        <div class="gp-total">
                            <div class="gp-total__label">الإجمالي المطلوب <small id="gpCalcNote">شامل الرسوم والدمغات</small></div>
                            <div class="gp-total__amount"><span id="totalDisplay">—</span><small>ج.م</small></div>
                        </div>
                    </div>
                    <p class="gp-calc__disclaimer"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> يُعاد حساب المبلغ على الخادم بنفس الطريقة عند التأكيد.</p>
                </aside>
            </div>
        </div>

        {{-- Terms Modal --}}
        <div class="modal fade gp-terms" id="termsModal" tabindex="-1" aria-hidden="true" aria-labelledby="termsModalTitle">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5"><i class="fa-solid fa-file-contract" aria-hidden="true"></i> <span id="termsModalTitle">الشروط والأحكام</span></h2>
                        <button type="button" class="btn-close btn-close-white ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body p-4" id="termsModalBody"></div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4 d-grid">
                        <label class="gp-check" for="termsAgreeCheck">
                            <input type="checkbox" id="termsAgreeCheck">
                            لقد اطلعت على جميع الشروط والأحكام وأوافق عليها
                        </label>
                        <button type="button" class="gp-btn gp-btn--block" id="termsConfirmBtn" disabled onclick="confirmTerms()">
                            <i class="fa-solid fa-check" aria-hidden="true"></i> تأكيد الموافقة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<div id="termsServiceData" style="display:none;">
    @if($service->terms_conditions)
        <h3 class="fs-6 fw-bold mb-3">الشروط والضوابط الفنية للخدمة</h3>
        <div style="line-height:2;">{!! nl2br(e($service->terms_conditions)) !!}</div>
    @else
        <p class="text-muted">لا توجد شروط إضافية لهذه الخدمة.</p>
    @endif
</div>
<script>
/*
 * حاسبة الرسوم — نسخة طبق الأصل من App\Services\Payments\Pricing\GisPricing::quote()
 * (الخادم يعيد الحساب عند الإرسال؛ هذه للعرض فقط).
 */
const pricing = {
    type: @json($pricingType),
    base: {{ (float) $service->base_price }},
    settings: @json((object) $settings),
    hasVat: @json((bool) $service->has_vat),
    martyr: {{ (float) ($service->martyr_stamp_fee ?? 0) }},
    sms: {{ (float) ($service->sms_fee ?? 0) }},
    fields: @json($priceFields),
};

const num = v => { const n = parseFloat(v); return Number.isFinite(n) && n > 0 ? n : 0; };
// مكافئ empty() في PHP
const filled = v => !(v === undefined || v === null || v === '' || v === '0' || v === 0 || v === false);
const money = v => v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const qty = v => v.toLocaleString('en-US', { maximumFractionDigits: 2 });
const isNewRequest = () => document.getElementById('request_type').value === 'new';

function formField(name) {
    return document.querySelector(`#applyForm [name="form_data[${name}]"]`);
}
function inputValue(key) {
    if (!isNewRequest()) return 0; // إعادة الدراسة / بدل فاقد: الخادم لا يستخدم بيانات النموذج
    const name = pricing.fields[key];
    const el = formField(name) || document.querySelector(`[data-gp-calc="${key}"]`);
    return el ? num(el.value) : 0;
}

function quote() {
    const s = pricing.settings || {};
    const lines = [];
    const area = inputValue('area');
    let core = pricing.base;

    if (pricing.type === 'formula') {
        const mult = num(s.multiplier), extra = num(s.fixed_extra);
        core = area * mult + pricing.base + extra;
        lines.push([`المساحة × سعر المتر: ${qty(area)} م² × ${money(mult)}`, area * mult]);
        lines.push(['الرسوم الأساسية', pricing.base]);
        if (extra) lines.push(['رسوم إضافية ثابتة', extra]);
        if (s.static_point_price !== undefined && s.static_point_price !== null) {
            const pts = inputValue('static_points'), price = num(s.static_point_price);
            core += pts * price;
            lines.push([`النقاط الثابتة: ${qty(pts)} × ${money(price)}`, pts * price]);
        }
    } else if (pricing.type === 'tiered') {
        const tiers = (Array.isArray(s.tiers) ? s.tiers : [])
            .filter(t => t && t.max !== undefined && t.price !== undefined)
            .sort((a, b) => parseFloat(a.max) - parseFloat(b.max));
        if (tiers.length) {
            let idx = tiers.length - 1;
            for (let i = 0; i < tiers.length; i++) { if (area <= parseFloat(tiers[i].max)) { idx = i; break; } }
            core = num(tiers[idx].price);
            const from = idx ? qty(parseFloat(tiers[idx - 1].max)) : '0';
            const to = qty(parseFloat(tiers[idx].max));
            lines.push([area > parseFloat(tiers[idx].max)
                ? `المساحة ${qty(area)} م² ← أعلى شريحة (حتى ${to} م²)`
                : `المساحة ${qty(area)} م² ← شريحة ${from} – ${to} م²`, core]);
        } else {
            lines.push(['الرسوم الأساسية', core]);
        }
        const threshold = num(s.overflow_threshold);
        if (filled(s.has_overflow) && area > threshold) {
            const unit = num(s.overflow_unit_size ?? 4200) || 4200;
            const units = Math.ceil((area - threshold) / unit), add = units * num(s.overflow_price);
            core += add;
            lines.push([`ما زاد عن ${qty(threshold)} م²: ${units} وحدة (كل ${qty(unit)} م²) × ${money(num(s.overflow_price))}`, add]);
        }
        const pThreshold = num(s.point_threshold), points = inputValue('points');
        if (filled(s.point_threshold)) {
            if (points > pThreshold) {
                const add = (points - pThreshold) * num(s.point_extra);
                core += add;
                lines.push([`نقاط إضافية: (${qty(points)} − ${qty(pThreshold)}) × ${money(num(s.point_extra))}`, add]);
            } else {
                lines.push([`أول ${qty(pThreshold)} نقاط مشمولة في السعر`, 0]);
            }
        }
    } else {
        lines.push(['المقابل الفني (رسوم ثابتة)', core]);
    }

    const vat = pricing.hasVat ? core * 0.14 : 0;
    if (pricing.hasVat) lines.push(['ضريبة القيمة المضافة 14%', vat]);
    lines.push(['دمغة الشهداء', pricing.martyr]);
    lines.push(['مقابل المتابعة (SMS)', pricing.sms]);

    return { lines, total: core + vat + pricing.martyr + pricing.sms };
}

function calcPrice() {
    const { lines, total } = quote();
    const ol = document.getElementById('gpCalcLines');
    ol.replaceChildren(...lines.map(([label, value]) => {
        const li = document.createElement('li');
        const a = document.createElement('span'); a.textContent = label;
        const b = document.createElement('span'); b.textContent = money(value);
        li.append(a, b);
        return li;
    }));
    document.getElementById('totalDisplay').textContent = money(total);
    document.querySelectorAll('.gp-calc-total-inline').forEach(el => el.textContent = `(${money(total)} ج.م)`);
    document.getElementById('gpCalcNote').textContent = isNewRequest()
        ? 'شامل الرسوم والدمغات'
        : 'إعادة الدراسة / بدل فاقد: الرسوم الأساسية فقط';
    document.querySelectorAll('[data-gp-calc]').forEach(el => el.disabled = !isNewRequest());
}

// ربط مدخلات الحاسبة بحقول النموذج في الاتجاهين
document.querySelectorAll('[data-gp-calc]').forEach(calcEl => {
    const field = formField(calcEl.dataset.field);
    if (field && field !== calcEl) {
        calcEl.value = field.value;
        calcEl.addEventListener('input', () => { field.value = calcEl.value; field.classList.remove('is-invalid'); calcPrice(); });
        field.addEventListener('input', () => { calcEl.value = field.value; });
    } else {
        calcEl.addEventListener('input', calcPrice);
    }
});

function toggleAgent(val) {
    document.getElementById('agentSection').style.display = val === 'agent' ? 'block' : 'none';
}

function toggleRequestType(val) {
    document.getElementById('newRequestFields').style.display = val === 'new' ? 'block' : 'none';
    document.getElementById('restudyFields').style.display = val !== 'new' ? 'block' : 'none';
    calcPrice();
}

function goStep(n) {
    const current = document.querySelector('.wizard-step.active');
    const currentId = current ? parseInt(current.id.replace('step', '')) : 1;

    if (n > currentId) {
        if (!validateStep(currentId)) return;
    }

    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
    document.getElementById('step' + n).classList.add('active');
    document.querySelectorAll('.step-item').forEach(el => { el.classList.remove('active', 'done'); el.removeAttribute('aria-current'); });
    const active = document.querySelector(`.step-item[data-step="${n}"]`);
    active.classList.add('active');
    active.setAttribute('aria-current', 'step');

    for (let i = 1; i < n; i++) {
        document.querySelector(`.step-item[data-step="${i}"]`).classList.add('done');
    }

    document.querySelector('.gp-stepper').scrollIntoView({ behavior: 'smooth', block: 'start' });
    calcPrice();
}

function validateStep(step) {
    const container = document.getElementById('step' + step);
    const required = container.querySelectorAll('input[required], select[required], textarea[required]');
    let valid = true;
    let firstInvalid = null;

    container.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    required.forEach(el => {
        if (el.closest('#agentSection') && document.getElementById('agentSection').style.display === 'none') return;
        if (el.closest('#restudyFields') && document.getElementById('restudyFields').style.display === 'none') return;
        if (el.closest('#newRequestFields') && document.getElementById('newRequestFields').style.display === 'none') return;

        let value = '';
        if (el.type === 'file') value = el.files.length > 0 ? 'filled' : '';
        else if (el.type === 'checkbox') value = el.checked ? 'checked' : '';
        else value = el.value.trim();

        if (!value) {
            el.classList.add('is-invalid');
            valid = false;
            if (!firstInvalid) firstInvalid = el;
        }
    });

    if (!valid) {
        (window.toastr?.error || alert)('يرجى تعبئة جميع الحقول المطلوبة قبل الانتقال للخطوة التالية', 'حقول ناقصة');
        if (firstInvalid) { firstInvalid.focus(); firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        return false;
    }
    return true;
}

// Terms modal
const termsContent = {
    payment: {
        title: 'شروط السداد الإلكتروني',
        body: `<h3 class="fs-6 fw-bold mb-3">شروط وأحكام السداد الإلكتروني عبر بوابة e-finance</h3>
<ul>
    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> يتم السداد عبر بوابة الدفع الإلكتروني الحكومية (e-finance).</li>
    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> المبلغ المسدد هو إجمالي قيمة الخدمة شاملاً الرسوم والضرائب المقررة.</li>
    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> في حالة فشل عملية الدفع، يمكنك المحاولة مرة أخرى من خلال متابعة الطلب.</li>
    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> المبالغ المدفوعة غير قابلة للاسترداد إلا في الحالات التي تحددها اللوائح.</li>
    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> يجب الاحتفاظ برقم المعاملة للاستعلام عن حالة الطلب لاحقاً.</li>
</ul>`,
    },
    service: {
        title: 'شروط الخدمة والضوابط الفنية',
        body: document.getElementById('termsServiceData').innerHTML,
    },
};

function showTermsModal(type) {
    const content = termsContent[type];
    if (!content) return;
    document.getElementById('termsModalTitle').textContent = content.title;
    document.getElementById('termsModalBody').innerHTML = content.body;
    document.getElementById('termsAgreeCheck').checked = false;
    document.getElementById('termsConfirmBtn').disabled = true;
    document.getElementById('termsModal').setAttribute('data-terms-type', type);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('termsModal')).show();
}

document.getElementById('termsAgreeCheck').addEventListener('change', function() {
    document.getElementById('termsConfirmBtn').disabled = !this.checked;
});

function confirmTerms() {
    const type = document.getElementById('termsModal').getAttribute('data-terms-type');
    if (type === 'payment') {
        document.getElementById('agreePayment').checked = true;
    } else if (type === 'service') {
        document.getElementById('agreeService').checked = true;
    }
    bootstrap.Modal.getInstance(document.getElementById('termsModal')).hide();
}

document.getElementById('applyForm').addEventListener('submit', function(e) {
    for (let s = 1; s <= 3; s++) {
        if (!validateStep(s)) {
            e.preventDefault();
            goStep(s);
            (window.toastr?.error || alert)('يوجد حقول ناقصة في الخطوة ' + s + '. يرجى مراجعتها.', 'بيانات ناقصة');
            return;
        }
    }
    const paymentChecked = document.getElementById('agreePayment').checked;
    const serviceChecked = document.getElementById('agreeService').checked;
    if (!paymentChecked || !serviceChecked) {
        e.preventDefault();
        (window.toastr?.error || alert)('يرجى الاطلاع على الشروط والموافقة عليها قبل الإرسال', 'شروط غير موافق عليها');
        if (!paymentChecked) document.getElementById('agreePayment').focus();
        else document.getElementById('agreeService').focus();
    }
});

// تحديث الرسوم مع أي إدخال
document.getElementById('applyForm').addEventListener('input', function(e) {
    if (e.target.classList.contains('dynamic-input')) calcPrice();
});
document.getElementById('request_type').addEventListener('change', calcPrice);
toggleAgent(document.getElementById('applicant_type').value);

// Dependent dropdowns
const optionList = (placeholder, items, label) => {
    const frag = [new Option(placeholder, '')];
    items.forEach(i => frag.push(new Option(label(i), i.id)));
    return frag;
};
async function loadUnits(id) {
    const unit = document.getElementById('unit_select');
    const village = document.getElementById('village_select');
    village.replaceChildren(new Option('بانتظار الوحدة...', ''));
    if (!id) { unit.replaceChildren(new Option('بانتظار المركز...', '')); return; }
    unit.replaceChildren(new Option('جارٍ التحميل...', ''));
    const data = await (await fetch(`/gis-portal/api/markaz/${encodeURIComponent(id)}/units`)).json();
    unit.replaceChildren(...optionList('-- اختر الوحدة --', data, i => i.name));
}
async function loadVillages(id) {
    const village = document.getElementById('village_select');
    if (!id) { village.replaceChildren(new Option('بانتظار الوحدة...', '')); return; }
    village.replaceChildren(new Option('جارٍ التحميل...', ''));
    const data = await (await fetch(`/gis-portal/api/unit/${encodeURIComponent(id)}/villages`)).json();
    village.replaceChildren(...optionList('-- اختر القرية --', data, i => i.name + (i.is_ezba ? ' (عزبة)' : '')));
}

calcPrice();
</script>
@endpush
