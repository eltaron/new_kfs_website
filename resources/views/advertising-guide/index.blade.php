@extends('layouts.app')
@section('title', 'دليل الإعلانات — الدليل الإرشادي للمواقع الإعلانية | محافظة كفر الشيخ')
@section('description', 'خريطة تفاعلية للمواقع الإعلانية المعتمدة بشوارع ومحاور محافظة كفر الشيخ: الأنواع والمقاسات والارتفاعات، مع إمكانية تسجيل موقع إعلاني جديد.')

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('css/advertising-guide.css') }}?v={{ @filemtime(public_path('css/advertising-guide.css')) }}" />
@endpush

@section('content')
    @php
        $fmt = fn ($n) => number_format((int) $n);
    @endphp
    <main class="adg" id="adg">
        <x-kfs.page-hero title="دليل الإعلانات" kicker="الدليل الإرشادي للإعلانات" icon="fa-solid fa-rectangle-ad"
            :crumbs="['الاستثمار' => \Illuminate\Support\Facades\Route::has('investments.index') ? route('investments.index') : null]"
            lead="خريطة تفاعلية للمواقع الإعلانية المعتمدة في شوارع ومحاور المحافظة — تعرّف على نوع كل موقع ومقاسه وارتفاعه قبل التقدم بطلب الترخيص.">
            <x-slot:meta>
                <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $fmt($stats['points']) }} موقع إعلاني</span>
                <span><i class="fa-solid fa-road" aria-hidden="true"></i> {{ $fmt($stats['streets']) }} شارع ومحور</span>
                <span><i class="fa-solid fa-map" aria-hidden="true"></i> {{ $fmt($stats['areas']) }} مركز ومدينة</span>
            </x-slot:meta>
            <x-slot:actions>
                @auth
                    <button type="button" class="btn btn-kfs-gold" data-adg-add>
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> أضف موقعاً إعلانياً
                    </button>
                @else
                    <a href="{{ route('login') }}" class="btn btn-kfs-gold">
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> سجّل الدخول لإضافة موقع
                    </a>
                @endauth
                @if ($licenceUrl)
                    <a href="{{ $licenceUrl }}" class="btn btn-kfs-outline">
                        <i class="fa-solid fa-file-signature" aria-hidden="true"></i> ترخيص الإعلانات
                    </a>
                @endif
            </x-slot:actions>
        </x-kfs.page-hero>

        {{-- ===== Stats strip ===== --}}
        <section class="container adg-stats" aria-label="المواقع الإعلانية في أرقام">
            <x-kfs.stat-counter :value="$stats['points']" label="موقع إعلاني معتمد" icon="fa-solid fa-location-dot" />
            <x-kfs.stat-counter :value="$stats['streets']" label="شارع ومحور مروري" icon="fa-solid fa-road" />
            <x-kfs.stat-counter :value="$stats['types']" label="نوع من الهياكل الإعلانية" icon="fa-solid fa-shapes" />
            <x-kfs.stat-counter :value="$stats['areas']" label="مركز ومدينة مغطاة" icon="fa-solid fa-map-location-dot" />
        </section>

        {{-- ===== Explorer: filters + list + map ===== --}}
        <section class="container adg-explorer" aria-labelledby="adgExplorerTitle">
            <x-kfs.section-title id="adgExplorerTitle" eyebrow="استكشف المواقع" title="خريطة المواقع الإعلانية"
                subtitle="ابحث بالشارع أو صفِّ النتائج حسب النوع والمركز والمقاس، ثم اختر موقعاً لعرضه على الخريطة." />

            <form class="adg-toolbar" role="search" aria-label="البحث في المواقع الإعلانية" id="adgFilters" onsubmit="return false">
                <div class="adg-field adg-field--search">
                    <label for="adgSearch" class="visually-hidden">ابحث عن شارع أو موقع</label>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="adgSearch" class="form-control" placeholder="ابحث باسم الشارع أو المعلم القريب…" autocomplete="off" maxlength="80">
                </div>
                <div class="adg-field">
                    <label for="adgType" class="adg-field__label">النوع</label>
                    <select id="adgType" class="form-select">
                        <option value="">كل الأنواع</option>
                    </select>
                </div>
                <div class="adg-field">
                    <label for="adgArea" class="adg-field__label">المركز</label>
                    <select id="adgArea" class="form-select">
                        <option value="">كل المراكز</option>
                        @foreach (array_keys($areas) as $area)
                            <option value="{{ $area }}">{{ $area }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="adg-field">
                    <label for="adgSize" class="adg-field__label">المقاس</label>
                    <select id="adgSize" class="form-select">
                        <option value="">كل المقاسات</option>
                        <option value="small">صغير (أقل من 4 م²)</option>
                        <option value="medium">متوسط (4 – 18 م²)</option>
                        <option value="large">كبير (أكثر من 18 م²)</option>
                    </select>
                </div>
                <button type="button" class="btn btn-kfs-outline adg-reset" id="adgReset">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> إعادة الضبط
                </button>
            </form>

            <div class="adg-chips" id="adgChips" role="group" aria-label="تصفية سريعة حسب نوع الإعلان"></div>

            <div class="adg-tabs" role="tablist" aria-label="طريقة العرض">
                <button type="button" role="tab" class="adg-tab is-active" id="adgTabMap" aria-selected="true" aria-controls="adgMapWrap" data-adg-tab="map">
                    <i class="fa-solid fa-map" aria-hidden="true"></i> الخريطة
                </button>
                <button type="button" role="tab" class="adg-tab" id="adgTabList" aria-selected="false" aria-controls="adgListPanel" data-adg-tab="list" tabindex="-1">
                    <i class="fa-solid fa-list" aria-hidden="true"></i> القائمة <span class="adg-tab__count" id="adgTabCount"></span>
                </button>
            </div>

            <div class="adg-layout" data-view="map">
                <aside class="adg-list" id="adgListPanel" role="tabpanel" aria-labelledby="adgTabList">
                    <div class="adg-list__head">
                        <p class="adg-list__count" id="adgCount" aria-live="polite">جارٍ تحميل المواقع…</p>
                        <button type="button" class="adg-linkbtn" id="adgExpandAll" aria-pressed="false">توسيع الكل</button>
                    </div>
                    <div class="adg-list__body" id="adgList" aria-busy="true">
                        @if ($stats['points'] > 0)
                            @for ($i = 0; $i < 4; $i++)
                                <div class="adg-skel" aria-hidden="true"><span></span><span></span></div>
                            @endfor
                        @endif
                    </div>
                    <div class="adg-list__empty" id="adgEmpty" @if ($stats['points'] > 0) hidden @endif>
                        @if ($stats['points'] > 0)
                            <x-kfs.empty-state icon="search" compact title="لا توجد نتائج مطابقة" text="جرّب كلمة بحث أخرى أو أعد ضبط عوامل التصفية.">
                                <button type="button" class="btn btn-kfs-outline btn-kfs--sm" data-adg-reset>إعادة الضبط</button>
                            </x-kfs.empty-state>
                        @else
                            <x-kfs.empty-state icon="map" compact title="لا توجد مواقع إعلانية مسجلة بعد" text="ستظهر المواقع المعتمدة هنا فور إضافتها.">
                                @auth
                                    <button type="button" class="btn btn-kfs btn-kfs--sm" data-adg-add>أضف أول موقع</button>
                                @endauth
                            </x-kfs.empty-state>
                        @endif
                    </div>
                </aside>

                <div class="adg-mapwrap" id="adgMapWrap" role="tabpanel" aria-labelledby="adgTabMap">
                    <div id="adgMap" class="adg-map" role="region" aria-label="خريطة المواقع الإعلانية — استخدم أزرار التكبير أو اختر موقعاً من القائمة" tabindex="0"></div>

                    <div class="adg-mapctl" role="toolbar" aria-label="أدوات الخريطة">
                        <button type="button" class="adg-ctl" id="adgLocate" title="موقعي الحالي" aria-label="عرض موقعي الحالي"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></button>
                        <button type="button" class="adg-ctl" id="adgFullscreen" title="ملء الشاشة" aria-label="عرض الخريطة بملء الشاشة" aria-pressed="false"><i class="fa-solid fa-expand" aria-hidden="true"></i></button>
                        <button type="button" class="adg-ctl" id="adgHome" title="عرض كل المواقع" aria-label="عرض كل المواقع"><i class="fa-solid fa-house" aria-hidden="true"></i></button>
                        <button type="button" class="adg-ctl" id="adgLayer" title="صور الأقمار الصناعية" aria-label="التبديل إلى صور الأقمار الصناعية" aria-pressed="false"><i class="fa-solid fa-satellite" aria-hidden="true"></i></button>
                        <button type="button" class="adg-ctl" id="adgMeasure" title="قياس المسافة" aria-label="قياس المسافة على الخريطة" aria-pressed="false"><i class="fa-solid fa-ruler" aria-hidden="true"></i></button>
                    </div>

                    <div class="adg-legend is-collapsed" id="adgLegend">
                        <button type="button" class="adg-legend__toggle" id="adgLegendToggle" aria-expanded="false" aria-controls="adgLegendList">
                            <i class="fa-solid fa-palette" aria-hidden="true"></i> دليل الألوان <i class="fa-solid fa-chevron-down adg-legend__chev" aria-hidden="true"></i>
                        </button>
                        <ul class="adg-legend__list" id="adgLegendList"></ul>
                    </div>

                    <div class="adg-measure" id="adgMeasureBar" hidden role="status" aria-live="polite">
                        <span>المسافة: <strong id="adgMeasureVal">0 م</strong></span>
                        <span class="adg-measure__hint">انقر على الخريطة لإضافة نقاط</span>
                        <button type="button" class="adg-linkbtn" id="adgMeasureClear">مسح</button>
                        <button type="button" class="adg-measure__close" id="adgMeasureClose" aria-label="إنهاء القياس"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>

                    <div class="adg-maperror" id="adgMapError" hidden role="alert">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <span>تعذّر تحميل الخريطة. تحقّق من الاتصال ثم أعد تحميل الصفحة.</span>
                    </div>
                    <noscript><p class="adg-maperror">تتطلب الخريطة التفاعلية تفعيل JavaScript في المتصفح.</p></noscript>
                </div>
            </div>
            <p class="adg-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                المواقع المعروضة استرشادية؛ تركيب أي إعلان يستلزم الحصول على ترخيص من المحافظة وفق الضوابط الهندسية المعتمدة.
                @auth انقر بزر الفأرة الأيمن على الخريطة لتسجيل موقع جديد في النقطة المحددة. @endauth
            </p>
        </section>

        {{-- ===== How it works ===== --}}
        <section class="container adg-steps" aria-labelledby="adgStepsTitle">
            <x-kfs.section-title id="adgStepsTitle" title="كيف تستفيد من الدليل؟" subtitle="ثلاث خطوات من استكشاف الموقع حتى الحصول على الترخيص." />
            <ol class="adg-steps__grid">
                <li class="adg-step">
                    <span class="adg-step__num" aria-hidden="true">1</span>
                    <span class="adg-step__icon" aria-hidden="true"><i class="fa-solid fa-map-location-dot"></i></span>
                    <h3 class="adg-step__title">استكشف المواقع</h3>
                    <p class="adg-step__text">استعرض المواقع حسب الشارع والنوع والمقاس، واطّلع على ارتفاع الهيكل ووصف المكان.</p>
                </li>
                <li class="adg-step">
                    <span class="adg-step__num" aria-hidden="true">2</span>
                    <span class="adg-step__icon" aria-hidden="true"><i class="fa-solid fa-pen-ruler"></i></span>
                    <h3 class="adg-step__title">سجّل موقعاً</h3>
                    <p class="adg-step__text">بعد تسجيل الدخول يمكنك إضافة موقع إعلاني بتحديده على الخريطة، وتعديل المواقع التي أضفتها.</p>
                </li>
                <li class="adg-step">
                    <span class="adg-step__num" aria-hidden="true">3</span>
                    <span class="adg-step__icon" aria-hidden="true"><i class="fa-solid fa-file-signature"></i></span>
                    <h3 class="adg-step__title">تقدّم بطلب الترخيص</h3>
                    <p class="adg-step__text">قدّم طلب ترخيص الإعلان إلكترونياً وتابع حالته حتى إصدار الترخيص.</p>
                    @if ($licenceUrl)
                        <a href="{{ $licenceUrl }}" class="adg-step__link">خدمة ترخيص الإعلانات <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                    @endif
                </li>
            </ol>
        </section>

        @auth
            {{-- ===== Add / edit point (same endpoints & field names as before) ===== --}}
            <div class="modal fade adg-modal" id="adgFormModal" tabindex="-1" aria-labelledby="adgFormTitle" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
                    <form class="modal-content" id="adgForm" novalidate>
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title" id="adgFormTitle">إضافة موقع إعلاني</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <div class="adg-form__alert" id="adgFormAlert" role="alert" hidden></div>
                            <div class="adg-form__grid">
                                <div class="adg-form__fields">
                                    <div class="mb-3">
                                        <label for="adgFStreet" class="form-label">اسم الشارع <span class="adg-req" aria-hidden="true">*</span></label>
                                        <input type="text" class="form-control" id="adgFStreet" name="street_name" list="adgStreetList" maxlength="255" required aria-required="true" aria-describedby="adgFStreetErr" placeholder="مثال: شارع الجيش">
                                        <datalist id="adgStreetList"></datalist>
                                        <div class="invalid-feedback" id="adgFStreetErr"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="adgFType" class="form-label">نوع الإعلان <span class="adg-req" aria-hidden="true">*</span></label>
                                        <select class="form-select" id="adgFType" name="type" required aria-required="true" aria-describedby="adgFTypeErr">
                                            @foreach ($types as $value => $meta)
                                                <option value="{{ $value }}">{{ $meta['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="adgFTypeErr"></div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-6">
                                            <label for="adgFSize" class="form-label">المقاس</label>
                                            <input type="text" class="form-control" id="adgFSize" name="size" list="adgSizeList" maxlength="100" placeholder="مثال: 6×3 م" aria-describedby="adgFSizeErr">
                                            <datalist id="adgSizeList">
                                                <option value="2×1 م"></option><option value="3×2 م"></option><option value="4×3 م"></option>
                                                <option value="6×3 م"></option><option value="8×4 م"></option><option value="12×4 م"></option>
                                            </datalist>
                                            <div class="invalid-feedback" id="adgFSizeErr"></div>
                                        </div>
                                        <div class="col-6">
                                            <label for="adgFHeight" class="form-label">الارتفاع عن الأرض (م)</label>
                                            <input type="number" class="form-control" id="adgFHeight" name="height" min="0" step="0.1" inputmode="decimal" placeholder="مثال: 4.5" aria-describedby="adgFHeightErr">
                                            <div class="invalid-feedback" id="adgFHeightErr"></div>
                                        </div>
                                    </div>
                                    <div class="mb-1">
                                        <label for="adgFDesc" class="form-label">وصف الموقع</label>
                                        <textarea class="form-control" id="adgFDesc" name="description" rows="3" maxlength="1000" placeholder="أقرب معلم، اتجاه الرؤية، الإضاءة…" aria-describedby="adgFDescErr adgFDescCount"></textarea>
                                        <div class="adg-form__hint"><span id="adgFDescCount">0 / 1000</span> · لا يُسمح باستخدام الرمزين <bdi dir="ltr">&lt; &gt;</bdi></div>
                                        <div class="invalid-feedback" id="adgFDescErr"></div>
                                    </div>
                                </div>
                                <div class="adg-form__where">
                                    <span class="form-label d-block">الموقع على الخريطة <span class="adg-req" aria-hidden="true">*</span></span>
                                    <div class="adg-pick" id="adgPickMap" role="application" aria-label="انقر لتحديد موقع الإعلان أو اسحب العلامة"></div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-6">
                                            <label for="adgFLat" class="form-label adg-form__small">خط العرض</label>
                                            <input type="number" class="form-control form-control-sm" id="adgFLat" name="lat" step="any" required aria-required="true" dir="ltr" aria-describedby="adgFLatErr">
                                            <div class="invalid-feedback" id="adgFLatErr"></div>
                                        </div>
                                        <div class="col-6">
                                            <label for="adgFLng" class="form-label adg-form__small">خط الطول</label>
                                            <input type="number" class="form-control form-control-sm" id="adgFLng" name="lng" step="any" required aria-required="true" dir="ltr" aria-describedby="adgFLngErr">
                                            <div class="invalid-feedback" id="adgFLngErr"></div>
                                        </div>
                                    </div>
                                    <button type="button" class="adg-linkbtn mt-2" id="adgPickLocate"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> استخدم موقعي الحالي</button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-kfs-outline" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-kfs" id="adgFormSubmit">
                                <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                                <span class="adg-submit-label">حفظ الموقع</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade adg-modal" id="adgDeleteModal" tabindex="-1" aria-labelledby="adgDeleteTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title" id="adgDeleteTitle">حذف الموقع الإعلاني</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">هل تريد حذف الموقع <strong id="adgDeleteName"></strong> من الدليل؟ لن يظهر على الخريطة بعد الحذف.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-kfs-outline" data-bs-dismiss="modal">تراجع</button>
                            <button type="button" class="btn btn-danger" id="adgDeleteConfirm">حذف الموقع</button>
                        </div>
                    </div>
                </div>
            </div>
        @endauth

        <div class="adg-toasts" id="adgToasts" aria-live="polite" aria-atomic="false"></div>
    </main>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js" crossorigin="anonymous"></script>
    @include('advertising-guide.partials.script')
@endpush
