@extends('layouts.app')
@php
    use Modules\SiteValidity\Support\SvStatus;
    $r = $svRequest;
    $stepIndex = SvStatus::stepIndex($r->status);
    $final = $files->where('kind', 'final')->last();
    $ownFiles = $files->where('kind', '!=', 'final');
@endphp
@section('title', 'الطلب ' . $r->number . ' — صلاحية الموقع')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">
    <link rel="stylesheet" href="{{ $svCssUrl }}">
@endpush
@section('content')
    <x-kfs.page-hero size="compact" :title="'الطلب ' . $r->number" kicker="طلب صلاحية الموقع" icon="fa-solid fa-map-location-dot"
        :subtitle="SvStatus::hint($r->status)"
        :crumbs="['لوحة التحكم' => route('citizen.dashboard'), 'طلبات صلاحية الموقع' => route('site-validity.requests.index')]" :current="$r->number" />

    <div class="dashboard-root pt-0">
        <div class="container-fluid g-0">
            <div class="dashboard-layout">
                <main class="dashboard-content-area">
                    @include('citizen.dashboard.partials.top-nav')
                    <div class="px-3 px-md-4 pb-5">
                        @if (session('success'))
                            <div class="sv-alert sv-alert--success" role="status">{{ session('success') }}</div>
                        @endif
                        @foreach (['error', 'info'] as $flash)
                            @if (session($flash))
                                <div class="sv-alert {{ $flash === 'error' ? 'sv-alert--danger' : '' }}" role="status">{{ session($flash) }}</div>
                            @endif
                        @endforeach

                        {{-- الحالة والمراحل --}}
                        <div class="sv-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="sv-muted sv-small d-block">الحالة الحالية</span>
                                    <span class="kfs-badge kfs-badge--{{ SvStatus::webTone($r->status) }}" style="font-size:.9rem">{{ $r->statusLabel() }}</span>
                                </div>
                                <div class="text-end">
                                    <span class="sv-muted sv-small d-block">الرسوم</span>
                                    <strong>{{ number_format((float) $r->fee, 2) }} ج.م</strong>
                                    @if ($r->isPaid())
                                        <span class="kfs-badge kfs-badge--success ms-1"><i class="fa-solid fa-check" aria-hidden="true"></i> مدفوع</span>
                                    @else
                                        <span class="kfs-badge kfs-badge--warning ms-1">غير مدفوع</span>
                                    @endif
                                </div>
                            </div>
                            @if ($r->status !== SvStatus::REJECTED)
                                <ol class="sv-stepper" aria-label="مراحل الطلب">
                                    @foreach (array_values(SvStatus::STEPS) as $i => $label)
                                        <li class="{{ $i < $stepIndex || $r->status === SvStatus::COMPLETED ? 'is-done' : ($i === $stepIndex ? 'is-current' : '') }}" @if ($i === $stepIndex) aria-current="step" @endif>{{ $label }}</li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>

                        @if ($r->status === SvStatus::REJECTED && $r->rejection_reason)
                            <div class="sv-alert sv-alert--danger mt-3" role="alert">
                                <strong class="d-block">سبب الرفض</strong>{{ $r->rejection_reason }}
                            </div>
                        @endif

                        {{-- تأكيد موعد المعاينة بعد موافقة المهندس --}}
                        @if ($canConfirmAppointment)
                            <div class="sv-card" id="appointment">
                                <h2 class="sv-card__title"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> موعد المعاينة — يرجى التأكيد</h2>
                                <p class="mb-2">وافق مهندس التنظيم على طلبك وحُدد موعد المعاينة الميدانية:</p>
                                <p class="fs-5 fw-bold mb-3" dir="ltr" style="text-align:right">{{ $r->appointment_at?->format('Y-m-d H:i') }}</p>
                                @error('appointment')<div class="sv-alert sv-alert--danger" role="alert">{{ $message }}</div>@enderror
                                @error('date')<div class="sv-alert sv-alert--danger" role="alert">{{ $message }}</div>@enderror
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('site-validity.requests.appointment.confirm', $r) }}">
                                        @csrf
                                        <button type="submit" class="sv-btn"><i class="fa-solid fa-check" aria-hidden="true"></i> موافق على الموعد</button>
                                    </form>
                                    <button type="button" class="sv-btn sv-btn--ghost" onclick="document.getElementById('svReschedule').hidden = false; this.hidden = true;">
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i> الموعد غير مناسب — اختيار يوم آخر
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('site-validity.requests.appointment.reschedule', $r) }}" id="svReschedule" class="mt-3" @if (! $errors->has('date') && ! $errors->has('appointment')) hidden @endif>
                                    @csrf
                                    @if ($availableDates)
                                        <p class="sv-label mb-2">الأيام المتاحة لمعاينات مركز {{ $r->center_name }}:</p>
                                        <div class="sv-options" role="radiogroup">
                                            @foreach ($availableDates as $date => $label)
                                                <label class="sv-option">
                                                    <input type="radio" name="date" value="{{ $date }}" required @checked(old('date') === $date)>
                                                    <span class="sv-option__box"><span class="sv-type__name">{{ $label }}</span></span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <button type="submit" class="sv-btn sv-btn--gold mt-3"><i class="fa-solid fa-calendar-plus" aria-hidden="true"></i> تأكيد اليوم المختار</button>
                                    @else
                                        <div class="sv-alert sv-alert--warning mb-0">لا توجد أيام متاحة حالياً لمركز {{ $r->center_name }} خلال الشهر القادم. يرجى التواصل مع المركز الجيومكاني أو قبول الموعد الحالي.</div>
                                    @endif
                                </form>
                            </div>
                        @elseif ($r->appointment_confirmation === \Modules\SiteValidity\Models\SiteValidityRequest::CONFIRMATION_ACCEPTED && ! $r->isClosed() && $r->geo_inspected_at === null && $r->engineer_inspected_at === null)
                            <div class="sv-alert sv-alert--success" role="status"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> تم تأكيد موعد المعاينة: <strong dir="ltr">{{ $r->appointment_at?->format('Y-m-d H:i') }}</strong></div>
                        @endif

                        {{-- الملف النهائي --}}
                        @if ($final)
                            <div class="sv-card">
                                <div class="sv-final">
                                    <div>
                                        <strong class="d-block"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i> الملف النهائي جاهز</strong>
                                        <span class="sv-small sv-muted">{{ $final->original_name }} · {{ $final->humanSize() }} · {{ $r->completed_at?->format('Y-m-d') }}</span>
                                        @if ($r->final_notes)
                                            <p class="mb-0 mt-1 sv-small">{{ $r->final_notes }}</p>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ $final->url() }}" target="_blank" rel="noopener" class="sv-btn sv-btn--ghost"><i class="fa-solid fa-print" aria-hidden="true"></i> طباعة</a>
                                        <a href="{{ $final->url() }}?download=1" class="sv-btn"><i class="fa-solid fa-download" aria-hidden="true"></i> تنزيل الملف النهائي</a>
                                    </div>
                                </div>
                                {{-- كود الاستلام (للطلبات المنتهية قبل إضافته: رقم الطلب) --}}
                                <div class="sv-alert sv-alert--success mt-3 mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2" role="note">
                                    <span><i class="fa-solid fa-ticket" aria-hidden="true"></i> كود الطلب — قدّمه في <strong>المركز التكنولوجي</strong> لاستلام الشهادة:</span>
                                    <strong dir="ltr" style="font-size:1.35rem; letter-spacing:.15em; font-family:monospace">{{ $r->pickup_code ?: $r->number }}</strong>
                                </div>
                            </div>
                        @endif

                        {{-- الاستيفاء المطلوب --}}
                        @if ($openCompletion)
                            <div class="sv-card" id="completion">
                                <h2 class="sv-card__title"><i class="fa-solid {{ $openCompletion->isPayment() ? 'fa-money-bill-wave' : 'fa-reply' }}" aria-hidden="true"></i>
                                    مطلوب استيفاء: {{ $openCompletion->typeLabel() }} (الجولة {{ $openCompletion->round }})</h2>
                                <div class="sv-alert sv-alert--warning">
                                    @if ($openCompletion->isPayment())
                                        <strong class="d-block mb-1">مطلوب منك سداد <span dir="ltr">{{ number_format((float) $openCompletion->amount, 2) }}</span> ج.م — من خانة «الرسوم والسداد»</strong>
                                    @else
                                        <strong class="d-block mb-1">مطلوب منك استكمال المستندات / البيانات التالية</strong>
                                    @endif
                                    <strong class="d-block mb-1">ملاحظات {{ $r->attachments_uploaded_at ? 'مهندس التنظيم' : 'المركز الجيومكاني' }}</strong>
                                    <span style="white-space: pre-line">{{ $openCompletion->notes }}</span>
                                    @if (! empty($openCompletion->requested_items))
                                        <ul class="mb-0 mt-2">
                                            @foreach ($openCompletion->requested_items as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                @if ($canReply)
                                    <form method="POST" action="{{ route('site-validity.requests.completion', $r) }}" enctype="multipart/form-data" novalidate>
                                        @csrf
                                        <label class="sv-label" for="reply">ردك على المطلوب <span class="req">*</span></label>
                                        <textarea class="sv-textarea @error('reply') is-invalid @enderror" id="reply" name="reply" rows="4" maxlength="3000" required>{{ old('reply') }}</textarea>
                                        @error('reply')<span class="sv-error">{{ $message }}</span>@enderror
                                        <label class="sv-label mt-3" for="files">المستندات المطلوبة (حتى 5 ملفات PDF أو صور)</label>
                                        <input class="sv-input @error('files.*') is-invalid @enderror" type="file" id="files" name="files[]" multiple accept=".pdf,.jpg,.jpeg,.png">
                                        @error('files')<span class="sv-error">{{ $message }}</span>@enderror
                                        @error('files.*')<span class="sv-error">{{ $message }}</span>@enderror
                                        <div class="mt-3">
                                            <button type="submit" class="sv-btn sv-btn--gold"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> إرسال الاستيفاء</button>
                                        </div>
                                    </form>
                                @elseif ($openCompletion->canBePaid())
                                    <a href="{{ $openCompletion->paymentUrl() }}" class="sv-btn sv-btn--gold"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد {{ number_format((float) $openCompletion->amount, 2) }} ج.م إلكترونياً</a>
                                    <p class="sv-small sv-muted mt-2 mb-0">بعد السداد يُغلق الاستيفاء تلقائياً ويستكمل الطلب مساره.</p>
                                @endif
                            </div>
                        @endif

                        <div class="row g-3 mt-0">
                            <div class="col-lg-7">
                                {{-- بيانات الطلب --}}
                                <div class="sv-card h-100">
                                    <h2 class="sv-card__title"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> بيانات الطلب</h2>
                                    <dl class="sv-kv">
                                        <div><dt>نوع الخدمة</dt><dd>{{ $r->serviceType?->name ?? '—' }}</dd></div>
                                        <div><dt>نوع الصلاحية</dt><dd>{{ $r->constructionTypeLabel() }}</dd></div>
                                        <div><dt>مقدم الطلب</dt><dd>{{ $r->applicant_name }}</dd></div>
                                        <div><dt>صفة مقدم الطلب</dt><dd>{{ $r->capacityLabel() }}</dd></div>
                                        <div><dt>الرقم القومي</dt><dd dir="ltr" class="text-end">{{ $r->national_id }}</dd></div>
                                        <div><dt>المحمول</dt><dd dir="ltr" class="text-end">{{ $r->phone }}</dd></div>
                                        <div><dt>المركز</dt><dd>{{ $r->center_name }}</dd></div>
                                        <div><dt>المدينة أو القرية</dt><dd>{{ $r->village_name ?? '—' }}</dd></div>
                                        <div style="grid-column: 1 / -1"><dt>العنوان</dt><dd>{{ $r->address }}</dd></div>
                                        <div><dt>تاريخ التقديم</dt><dd>{{ $r->created_at->format('Y-m-d H:i') }}</dd></div>
                                        <div><dt>موعد المعاينة</dt><dd>{{ $r->appointment_at?->format('Y-m-d H:i') ?? 'لم يحدد بعد' }}</dd></div>
                                    </dl>
                                    @if ($r->appointment_notes && ! $r->isClosed())
                                        <div class="sv-alert mt-3 mb-0"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> {{ $r->appointment_notes }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-5">
                                {{-- الدفع --}}
                                <div class="sv-card">
                                    <h2 class="sv-card__title"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> الرسوم والسداد</h2>
                                    @if ($r->isPaid())
                                        <p class="mb-0">تم سداد <strong>{{ number_format((float) ($r->paid_amount ?? $r->fee), 2) }} ج.م</strong>
                                            @if ($r->paid_at) بتاريخ {{ $r->paid_at->format('Y-m-d') }} @endif.</p>
                                        @if ($r->payment_reference)<p class="sv-small sv-muted mb-0">مرجع الدفع: <span dir="ltr">{{ $r->payment_reference }}</span></p>@endif
                                    @elseif ($r->canBePaid())
                                        <p class="sv-small sv-muted">{{ \Modules\SiteValidity\Support\SvSettings::get('cost_note') }}</p>
                                        @if ($paymentRequired)
                                            <div class="sv-alert sv-alert--warning sv-small">لن يبدأ تحديد موعد المعاينة إلا بعد السداد.</div>
                                        @endif
                                        <a href="{{ $r->paymentUrl() }}" class="sv-btn sv-btn--gold sv-btn--block"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد {{ number_format((float) $r->fee, 2) }} ج.م إلكترونياً</a>
                                    @elseif (! $r->completions->contains(fn ($c) => $c->isPayment()))
                                        <p class="sv-muted mb-0">لا توجد رسوم مستحقة.</p>
                                    @endif

                                    {{-- رسوم الاستيفاء الإضافية --}}
                                    @foreach ($r->completions->filter->isPayment() as $c)
                                        <div class="sv-costbox mt-3">
                                            <span>
                                                <span class="d-block fw-bold">رسوم استيفاء — الجولة {{ $c->round }}</span>
                                                <span class="sv-small sv-muted">{{ \Illuminate\Support\Str::limit($c->notes, 120) }}</span>
                                            </span>
                                            <strong><span dir="ltr">{{ number_format((float) $c->amount, 2) }}</span> ج.م</strong>
                                        </div>
                                        @if ($c->isPaid())
                                            <p class="sv-small mb-0 mt-1 text-success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> تم السداد @if ($c->paid_at) بتاريخ {{ $c->paid_at->format('Y-m-d') }} @endif
                                                @if ($c->payment_reference) — مرجع الدفع: <span dir="ltr">{{ $c->payment_reference }}</span>@endif</p>
                                        @elseif ($c->canBePaid())
                                            <a href="{{ $c->paymentUrl() }}" class="sv-btn sv-btn--gold sv-btn--block mt-2"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد {{ number_format((float) $c->amount, 2) }} ج.م إلكترونياً</a>
                                        @else
                                            <p class="sv-small sv-muted mb-0 mt-1">غير مستحقة حالياً.</p>
                                        @endif
                                    @endforeach
                                </div>
                                {{-- الملفات --}}
                                <div class="sv-card">
                                    <h2 class="sv-card__title"><i class="fa-solid fa-paperclip" aria-hidden="true"></i> مستنداتي</h2>
                                    <ul class="sv-files">
                                        @forelse ($ownFiles as $file)
                                            <li>
                                                <span><strong class="d-block sv-small">{{ $file->label() }}</strong><span class="sv-small sv-muted">{{ $file->original_name }}</span></span>
                                                <a href="{{ $file->url() }}" target="_blank" rel="noopener">عرض</a>
                                            </li>
                                        @empty
                                            <li class="sv-muted">لا توجد ملفات.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- الاستيفاءات السابقة --}}
                        @php $past = $svRequest->completions->whereNotNull('replied_at'); @endphp
                        @if ($past->isNotEmpty())
                            <div class="sv-card mt-3">
                                <h2 class="sv-card__title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> سجل الاستيفاء</h2>
                                @foreach ($past as $c)
                                    <div class="sv-alert">
                                        <strong class="d-block">الجولة {{ $c->round }} — طُلب {{ $c->requested_at?->format('Y-m-d') }}</strong>
                                        <span class="d-block" style="white-space: pre-line">{{ $c->notes }}</span>
                                        <span class="d-block mt-1 sv-small"><strong>ردك ({{ $c->replied_at->format('Y-m-d') }}):</strong> {{ $c->reply }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- الخط الزمني --}}
                        <div class="sv-card mt-3">
                            <h2 class="sv-card__title"><i class="fa-solid fa-timeline" aria-hidden="true"></i> سجل الطلب</h2>
                            <ol class="sv-timeline">
                                @foreach ($events->reverse() as $e)
                                    <li>
                                        <span class="sv-timeline__title">{{ $e->title() }}</span>
                                        <span class="sv-timeline__meta d-block">{{ $e->created_at?->format('Y-m-d H:i') }} — {{ \Modules\SiteValidity\Support\SvAccess::roleLabel($e->actor_role) }}</span>
                                        @if ($e->notes && in_array($e->action, ['set_appointment', 'reschedule', 'approve', 'reject', 'request_completion', 'complete', 'override', 'paid', 'submit_completion'], true))
                                            <span class="sv-timeline__note d-block">{{ $e->notes }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </main>
                @include('citizen.dashboard.partials.sidebar', ['activeNav' => 'site-validity'])
            </div>
        </div>
    </div>
@endsection
