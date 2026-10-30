@extends('layouts.app')

@section('title', 'التحقق من شهادات الإشراف الهندسي')

@include('syndicate::web._styles')

@section('content')
<main class="main-content kfs-portal syn-page" dir="rtl">
    <x-kfs.page-hero title="التحقق من شهادة إشراف / إلغاء إشراف" kicker="نقابة المهندسين × مجالس المدن" subtitle="أدخل رقم الشهادة الصادرة من نقابة المهندسين (مثال: SYN-SUP-2026-000045) أو امسح رمز QR المطبوع عليها." icon="fa-solid fa-qrcode"
        :crumbs="['الاستعلام عن الإشراف الهندسي' => route('citizen-lookup')]" />

    <div class="container syn-wrap">
        <section class="syn-search" data-reveal aria-labelledby="verify-search-title">
            <h2 id="verify-search-title"><span class="kfs-iframe" aria-hidden="true"><i class="fa-solid fa-certificate"></i></span> رقم الشهادة</h2>
            <p class="syn-search__lead">ستظهر لك حالة الشهادة فورًا: سارية، أو ملغاة، أو غير موجودة.</p>
            <form action="{{ route('syndicate.verify.form') }}" method="GET" role="search">
                <div class="syn-search__bar">
                    <div class="syn-search__field">
                        <label for="number" class="visually-hidden">رقم الشهادة</label>
                        <i class="fa-solid fa-hashtag" aria-hidden="true"></i>
                        <input id="number" name="number" class="syn-input" dir="ltr"
                               value="{{ $number ?? '' }}" placeholder="SYN-SUP-2026-000045" required maxlength="40" pattern="[A-Za-z0-9\-]+"
                               @error('number') aria-invalid="true" aria-describedby="number-error" @enderror>
                    </div>
                    <button class="syn-btn syn-btn-primary" type="submit"><i class="fas fa-search" aria-hidden="true"></i> تحقق</button>
                </div>
            </form>
            @error('number')<div class="syn-error" id="number-error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> رقم الشهادة غير صحيح.</div>@enderror
        </section>

        @isset($number)
            @if (! $certificate)
                <div class="syn-verdict danger" role="alert" data-reveal>
                    <span class="syn-verdict__seal" aria-hidden="true"><i class="fas fa-times"></i></span>
                    <div>
                        <h2>لا توجد شهادة بهذا الرقم.</h2>
                        <p>تأكد من الرقم أو تواصل مع نقابة المهندسين / مجلس المدينة.</p>
                    </div>
                </div>
            @else
                @php
                    $project = $certificate->project;
                    $valid = $certificate->isActive() || $certificate->kind === 'cancellation';
                @endphp

                @if ($certificate->kind === 'cancellation')
                    <div class="syn-verdict danger" role="status" data-reveal>
                        <span class="syn-verdict__seal" aria-hidden="true"><i class="fas fa-ban"></i></span>
                        <div>
                            <h2>شهادة إلغاء إشراف صحيحة وصادرة من النقابة.</h2>
                            <p>بموجبها أُلغي الإشراف {{ $certificate->cancels ? 'بالشهادة ' . $certificate->cancels->number : '' }}، ولا يجوز استمرار الأعمال بالموقع بدون شهادة إشراف جديدة.</p>
                        </div>
                    </div>
                @elseif ($certificate->isActive())
                    <div class="syn-verdict success" role="status" data-reveal>
                        <span class="syn-verdict__seal" aria-hidden="true"><i class="fas fa-check"></i></span>
                        <div>
                            <h2>شهادة إشراف سارية.</h2>
                            <p>صادرة من نقابة المهندسين ومسجلة لدى مجلس المدينة.</p>
                        </div>
                    </div>
                @else
                    <div class="syn-verdict danger" role="status" data-reveal>
                        <span class="syn-verdict__seal" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></span>
                        <div>
                            <h2>هذه الشهادة غير سارية ({{ $certificate->status_label }}).</h2>
                            @if($certificate->cancellation)<p>أُلغيت بشهادة الإلغاء رقم {{ $certificate->cancellation->number }}.</p>@endif
                        </div>
                    </div>
                @endif

                <section class="syn-card syn-card--accent" data-reveal aria-labelledby="cert-title">
                    <div class="syn-card__head">
                        <div>
                            <span class="kfs-iframe" aria-hidden="true"><i class="fa-solid fa-file-shield"></i></span>
                            <div>
                                <h3 id="cert-title">{{ $certificate->kind_label }}</h3>
                                <span class="syn-card__meta" dir="ltr">{{ $certificate->number }}</span>
                            </div>
                        </div>
                        @if($project)
                            <span class="syn-badge {{ \Modules\Syndicate\Models\Project::workStatusColors()[$project->work_status] ?? 'gray' }}">{{ $project->work_status_label }}</span>
                        @endif
                    </div>
                    <div class="syn-grid">
                        <div class="syn-field"><small>رقم الشهادة</small><strong dir="ltr">{{ $certificate->number }}</strong></div>
                        <div class="syn-field"><small>تاريخ الإصدار</small><strong>{{ $certificate->issued_at?->format('Y-m-d') ?? '-' }}</strong></div>
                        <div class="syn-field"><small>المهندس المشرف</small><strong>{{ $certificate->engineer_name ?? '-' }}</strong></div>
                        <div class="syn-field"><small>رقم عضوية النقابة</small><strong>{{ $certificate->engineer_syndicate_number ?? '-' }}</strong></div>
                        @if($certificate->office_name)
                            <div class="syn-field"><small>المكتب الهندسي</small><strong>{{ $certificate->office_name }}</strong></div>
                        @endif
                        <div class="syn-field"><small>المالك</small><strong>{{ $full ? $certificate->owner_name : \Illuminate\Support\Str::limit((string) $certificate->owner_name, 12) }}</strong></div>
                        <div class="syn-field"><small>الرقم القومي للمالك</small><strong dir="ltr">{{ $full ? $certificate->owner_national_id : \Modules\Syndicate\Models\Certificate::maskNationalId($certificate->owner_national_id) }}</strong></div>
                        <div class="syn-field"><small>المركز / المدينة</small><strong>{{ $certificate->city ?? $project?->center }}</strong></div>
                        @if($full)
                            <div class="syn-field syn-field--full"><small>عنوان الموقع</small><strong>{{ $certificate->project_address }}</strong></div>
                            <div class="syn-field"><small>رقم الترخيص</small><strong>{{ $certificate->license_number ?? '-' }}</strong></div>
                        @endif
                        @if($project)
                            <div class="syn-field"><small>حالة المشروع حالياً</small>
                                <span class="syn-badge {{ \Modules\Syndicate\Models\Project::workStatusColors()[$project->work_status] ?? 'gray' }}">{{ $project->work_status_label }}</span>
                            </div>
                        @endif
                    </div>
                    @unless($full)
                        <p class="syn-note"><i class="fa-solid fa-user-lock" aria-hidden="true"></i><span>يتم إخفاء جزء من البيانات الشخصية. امسح رمز QR المطبوع على الشهادة لعرض البيانات كاملة.</span></p>
                    @endunless
                </section>
            @endif
        @else
            <x-kfs.empty-state icon="search" title="أدخل رقم الشهادة للتحقق منها" text="يمكنك أيضًا مسح رمز QR المطبوع على الشهادة بكاميرا هاتفك للانتقال مباشرة إلى نتيجة التحقق." data-reveal />
        @endisset
    </div>
</main>
@endsection
