@extends('layouts.app')

@section('title', $service->title)

@push('css')
    <link rel="stylesheet" href="{{ asset('css/services.css') }}?v={{ @filemtime(public_path('css/services.css')) }}">
    <style>
        .conditional-field { display: none; }
        .service-form-card { border-top: 3px solid var(--kfs-primary); }
        .service-form-card .form-label { color: var(--kfs-charcoal); font-weight: 700; margin-bottom: .5rem; }
        .pricing-widget { padding: 0; overflow: hidden; }
        .pricing-widget__head { padding: 1rem 1.25rem; border-bottom: 1px solid var(--line); background: var(--kfs-bg); }
        .price-breakdown li { display: flex; justify-content: space-between; gap: 1rem; padding: .7rem 0; border-bottom: 1px dashed var(--line-strong); font-size: var(--fs-sm); }
        .total-box { background: var(--kfs-dark); color: #fff; border-radius: var(--r-lg); }
        .text-gold { color: var(--kfs-gold) !important; }
        .btn-gold { background-color: var(--kfs-primary); border-color: var(--kfs-primary); color: #fff; }
        .btn-gold:hover, .btn-gold:focus-visible { background-color: var(--kfs-primary-hover); border-color: var(--kfs-primary-hover); color: #fff; }
        .input-group>:not(:first-child):not(.dropdown-menu):not(.valid-tooltip):not(.valid-feedback):not(.invalid-tooltip):not(.invalid-feedback) {
            border-top-left-radius: var(--r);
            border-bottom-left-radius: var(--r);
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
    </style>
@endpush

@section('content')
    <main class="main-content">
        <x-kfs.page-hero :title="strip_tags($service->title)" :icon="$service->icon ?: 'fa-solid fa-hand-holding-hand'"
            :kicker="$service->parent ? strip_tags($service->parent->title) : 'دليل الخدمات'"
            :crumbs="['دليل الخدمات' => route('services.index')]" :image="asset('images/bg/services.jpg')" />

        <div class="container pb-5">
            <div class="row g-4 g-lg-5">
                {{-- قسم الفورم --}}
                <div class="col-lg-8">
                    @if ($service->description)
                        <article class="rich-text-content kfs-panel mb-4">
                            {!! clean($service->description, 'cms') !!}
                        </article>
                    @endif
                    @if ($service->children->isNotEmpty())
                        <div class="sub-services-section mt-4">
                            <x-kfs.section-title title="الخدمات الفرعية المتاحة" subtitle="اختر الخدمة المطلوبة للاطلاع على شروطها وتقديم الطلب." />
                            <div class="kfs-grid kfs-grid--2" data-stagger>
                                @foreach ($service->children as $subService)
                                    <a href="{{ $subService->publicUrl() }}"
                                        class="kfs-svc" @if ($subService->opensExternally()) target="_blank" rel="noopener" @endif>
                                        <span class="kfs-svc__icon" aria-hidden="true"><i class="{{ $subService->icon ?? 'fa-solid fa-file-lines' }}"></i></span>
                                        <span class="kfs-svc__body">
                                            <span class="kfs-svc__title">{{ strip_tags($subService->title) }}</span>
                                        </span>
                                        <span class="kfs-card__more">ابدأ الخدمة <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="service-form-card kfs-panel">
                            <div class="mb-4 border-bottom pb-3">
                                <h2 class="h4 fw-bold mb-1">استمارة تقديم الطلب الرقمية</h2>
                                <p class="text-muted mb-0">نظام الربط الإلكتروني المباشر بمركز المتغيرات المكانية</p>
                            </div>

                            @auth
                                <form action="{{ route('services.submit', $service) }}" method="POST" enctype="multipart/form-data" id="dynamicServiceForm">
                                    @csrf

                                    @foreach ($service->form_fields as $field)
                                        <div class="form-group mb-4" id="group_{{ $field['name'] }}">
                                            <label class="form-label">
                                                {{ $field['label'] }}
                                                @if ($field['is_required'] ?? false) <span class="text-danger">*</span> @endif
                                            </label>

                                            @switch($field['type'])
                                                @case('select')
                                                    <select name="{{ $field['name'] }}" class="form-select p-3 px-5 {{ $field['name'] == 'category' ? 'calc-trigger' : '' }}" required>
                                                        <option value="">-- اختر --</option>
                                                        {{-- إذا كان الحقل هو حقل المناطق، نقوم بتوليد الخيارات من جدول الأسعار تلقائياً --}}
                                                        @if($field['name'] == 'category' && !empty($service->category_pricing))
                                                            @foreach ($service->category_pricing as $priceItem)
                                                                <option value="{{ $priceItem['name'] }}">{{ $priceItem['name'] }}</option>
                                                            @endforeach
                                                        @else
                                                            @foreach ($field['options'] ?? [] as $opt)
                                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                @break

                                                @case('file')
                                                    <input type="file" name="{{ $field['name'] }}" class="form-control p-2" @if ($field['is_required'] ?? false) required @endif>
                                                @break

                                                @case('text')
                                                    @if($field['name'] == 'map')
                                                        <div class="input-group">
                                                            <input type="text" name="map" id="map_input" class="form-control p-3" readonly placeholder="اضغط لالتقاط العنوان عبر GPS...">
                                                            <button type="button" onclick="getLocation()" class="btn btn-gold text-white">
                                                                <i class="fas fa-map-marker-alt"></i>
                                                            </button>
                                                        </div>
                                                    @else
                                                        <input type="text" name="{{ $field['name'] }}" class="form-control p-3" @if($field['name'] == 'area') placeholder="أدخل المساحة بالمتر المربع" @endif>
                                                    @endif
                                                @break

                                                @case('number')
                                                    <input type="number" name="{{ $field['name'] }}" class="form-control p-3 {{ in_array($field['name'], ['area', 'quantity']) ? 'calc-trigger' : '' }}" placeholder="0.00">
                                                @break
                                                
                                                @default
                                                    <input type="text" name="{{ $field['name'] }}" class="form-control p-3">
                                            @endswitch
                                        </div>
                                    @endforeach

                                    <div class="mt-4 pt-4 border-top">
                                        <div class="alert alert-info text-center mb-4">
                                            <button type="button" class="btn btn-link text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#termsModal">
                                                <i class="fas fa-file-contract"></i> الإقرار بالصلاحية والشروط والأحكام
                                            </button>
                                        </div>
                                        <button type="submit" class="btn btn-gold btn-lg w-100 fw-bold">
                                            تقديم الطلب والانتقال للدفع <i class="fas fa-chevron-left ms-2"></i>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-warning text-center p-4 mb-0">
                                    يرجى <a href="{{ route('login') }}" class="fw-bold">تسجيل الدخول</a> لتتمكن من تقديم الطلب.
                                </div>
                            @endauth
                        </div>
                    @endif
                </div>

                {{-- السايدبار المالي --}}
                <div class="col-lg-4">
                    <aside class="kfs-aside">
                        @if (!$service->children->isNotEmpty())
                            <div class="kfs-panel pricing-widget mb-3">
                                <div class="pricing-widget__head">
                                    <h2 class="h6 m-0 fw-bold"><i class="fa-solid fa-calculator text-primary ms-1" aria-hidden="true"></i> تقدير المقابل</h2>
                                </div>

                                <div class="p-4">
                                    <ul class="price-breakdown list-unstyled p-0 m-0">
                                        <li><span>سعر المتر الأساسي:</span> <strong id="price_per_meter">0.00</strong></li>
                                        <li><span>مقابل استغلال المكان:</span> <strong id="display_subtotal">0.00</strong></li>
                                        <li><span>تأمين ({{ $service->insurance_percentage }}%):</span> <strong id="display_insurance">0.00</strong></li>
                                        @if ($service->has_vat)
                                            <li><span>ضريبة (14%):</span> <strong id="display_vat">0.00</strong></li>
                                        @endif
                                        <li><span>طابع الشهداء + SMS:</span> <strong>{{ number_format($service->martyr_stamp_fee + $service->sms_fee, 2) }}</strong></li>
                                    </ul>

                                    <div class="total-box text-center p-3 mt-3">
                                        <small class="d-block opacity-75">إجمالي المبلغ المطلوب</small>
                                        <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                            <span class="h1 fw-bold text-gold mb-0" id="grandTotalDisplay">15.00</span>
                                            <span class="h4 text-gold mb-0">ج.م</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($relatedServices->isNotEmpty())
                            <div class="kfs-panel">
                                <h2 class="kfs-panel__title"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> خدمات ذات صلة</h2>
                                <ul class="kfs-linklist">
                                    @foreach ($relatedServices as $related)
                                        <li><a href="{{ route('services.show', $related) }}">
                                            <i class="{{ $related->icon ?? 'fa-solid fa-file-lines' }} text-primary" aria-hidden="true" style="width:1.25rem;text-align:center"></i>
                                            <span>{{ strip_tags($related->title) }}</span>
                                        </a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </aside>
                </div>
            </div>
        </div>
    </main>

    {{-- مودال الشروط --}}
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">الضوابط والشروط القانونية</h5>
                </div>
                <div class="modal-body p-4">
                    <p>1. يلتزم مقدم الطلب بصحة كافة البيانات المذكورة.</p>
                    <p>2. يتم حساب المقابل بناءً على الفئات المعتمدة بالمحافظة والمساحة الفعلية.</p>
                    <p>3. المقابل المدفوعة لا تُسترد في حال البدء في المعاينة الميدانية.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-gold px-4" data-bs-dismiss="modal">موافق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // 1. وظيفة الـ GPS
        async function getLocation() {
            if (navigator.geolocation) {
                const btn = event.currentTarget;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                navigator.geolocation.getCurrentPosition(async (pos) => {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&format=json&accept-language=ar`);
                        const data = await res.json();
                        document.getElementById('map_input').value = data.display_name;
                    } catch (e) { Swal.fire({ icon:'error', title:'خطأ', text:'تعذر جلب العنوان.', confirmButtonText:'حسناً' }); }
                    btn.innerHTML = '<i class="fas fa-map-marker-alt"></i>';
                });
            }
        }

        // 2. محرك الأسعار الموحد
        const pricingConfig = {
            categoryData: {{ \Illuminate\Support\Js::from($service->category_pricing ?? []) }},
            insuranceRate: {{ $service->insurance_percentage ?? 0 }},
            fixedFees: {{ $service->martyr_stamp_fee + $service->sms_fee }},
            hasVat: {{ $service->has_vat ? 1 : 0 }}
        };

        function runCalculation() {
            // جلب القيم - تأكد من مطابقة أسماء الـ inputs
            const areaInput = document.querySelector('[name="area"]') || document.querySelector('[name="number"]');
            const area = parseFloat(areaInput?.value) || 0;
            const categoryName = document.querySelector('[name="category"]')?.value;

            let pricePerMeter = 0;
            const match = pricingConfig.categoryData.find(c => c.name === categoryName);
            if (match) {
                pricePerMeter = parseFloat(match.price_multiplier);
            }

            // الحسابات
            const subtotal = area * pricePerMeter;
            const insurance = subtotal * (pricingConfig.insuranceRate / 100);
            const vat = pricingConfig.hasVat ? (subtotal * 0.14) : 0;
            const total = subtotal + insurance + vat + pricingConfig.fixedFees;

            // تحديث الواجهة
            document.getElementById('price_per_meter').innerText = pricePerMeter.toLocaleString();
            document.getElementById('display_subtotal').innerText = subtotal.toLocaleString();
            document.getElementById('display_insurance').innerText = insurance.toLocaleString();
            if (pricingConfig.hasVat) {
                document.getElementById('display_vat').innerText = vat.toLocaleString();
            }
            document.getElementById('grandTotalDisplay').innerText = total.toLocaleString('en-US', {
                minimumFractionDigits: 2
            });
        }

        // الاستماع للتغيرات في كل حقول الفورم التي تؤثر على السعر
        const form = document.getElementById('dynamicServiceForm');
        if (form) {
            form.addEventListener('input', runCalculation);
            form.addEventListener('change', runCalculation);
        }
    </script>
@endpush