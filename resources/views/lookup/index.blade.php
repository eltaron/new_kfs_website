@extends('layouts.app')

@section('title', 'الاستعلام عن شهادة الإشراف الهندسي')

@include('syndicate::web._styles')

@section('content')
    <main class="main-content kfs-portal">
        <x-kfs.page-hero title="الاستعلام عن الإشراف الهندسي على المواقع" kicker="خدمة استعلام عامة" subtitle="شهادات الإشراف وإلغاء الإشراف الصادرة من نقابة المهندسين والمسجلة لدى مجالس المدن" icon="fa-solid fa-helmet-safety" :image="asset('images/bg/complaint.jpg')" />

        <div class="syn-page" dir="rtl">
            <div class="container syn-wrap">
                <section class="syn-search" data-reveal aria-labelledby="lookup-search-title">
                    <h2 id="lookup-search-title"><span class="kfs-iframe" aria-hidden="true"><i class="fas fa-search"></i></span> ابحث عن مشروع أو شهادة أو معاملة</h2>
                    <p class="syn-search__lead">أدخل <strong>الرقم القومي للمالك</strong> لعرض حالة الإشراف على مشروعاته، أو <strong>رقم الشهادة</strong> (SYN-SUP-…) للتحقق منها، أو <strong>رقم المعاملة</strong> (SYN-REQ-…) لمتابعة طلبك.</p>

                    @if ($errors->any())
                        <div class="syn-banner danger" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i><div>{{ $errors->first() }}</div></div>
                    @endif

                    <form action="{{ route('citizen-lookup.search') }}" method="GET" role="search">
                        <div class="syn-search__bar">
                            <div class="syn-search__field">
                                <label for="q" class="visually-hidden">الرقم القومي أو رقم الشهادة أو رقم المعاملة</label>
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="text" id="q" name="q" class="syn-input" dir="ltr"
                                       value="{{ $searched ?? old('q') }}" maxlength="40" required autocomplete="off"
                                       aria-describedby="lookup-formats"
                                       placeholder="29001011234567 / SYN-SUP-2026-000045 / SYN-REQ-2026-000123">
                            </div>
                            <button type="submit" class="syn-btn syn-btn-primary"><i class="fas fa-search" aria-hidden="true"></i> بحث</button>
                        </div>
                    </form>
                    <ul class="syn-chips" id="lookup-formats" aria-label="صيغ البحث المقبولة">
                        <li><i class="fa-solid fa-id-card" aria-hidden="true"></i> <b>الرقم القومي</b> 14 رقمًا</li>
                        <li><i class="fa-solid fa-certificate" aria-hidden="true"></i> <b>شهادة</b> <code>SYN-SUP-…</code></li>
                        <li><i class="fa-solid fa-file-signature" aria-hidden="true"></i> <b>معاملة</b> <code>SYN-REQ-…</code></li>
                    </ul>
                    <div class="syn-links">
                        <a href="{{ route('syndicate.verify.form') }}"><i class="fas fa-qrcode" aria-hidden="true"></i> التحقق من شهادة برمز QR</a>
                        @auth
                            <a href="{{ route('syndicate.my-projects') }}"><i class="fas fa-hard-hat" aria-hidden="true"></i> مشروعاتي وشهاداتي</a>
                        @endauth
                    </div>
                </section>

                @if (! empty($notFound))
                    <x-kfs.empty-state icon="search" title="لم يتم العثور على نتائج." data-reveal>
                        <p class="kfs-empty__text mb-0">لا توجد مشروعات أو شهادات أو معاملات مسجلة بالرقم: <span dir="ltr">{{ $searched }}</span></p>
                    </x-kfs.empty-state>
                @endif

                {{-- نتيجة البحث برقم المعاملة --}}
                @isset($transaction)
                    @php $r = $transaction; @endphp
                    <section class="syn-card syn-card--accent" aria-label="المعاملة" data-reveal>
                        <div class="syn-card__head">
                            <div>
                                <span class="kfs-iframe" aria-hidden="true"><i class="fa-solid fa-file-signature"></i></span>
                                <div>
                                    <h3>{{ $r->type_label }} — <span dir="ltr">{{ $r->transaction_number }}</span></h3>
                                    <span class="syn-card__meta">معاملة إشراف هندسي</span>
                                </div>
                            </div>
                            <span class="syn-badge {{ ['danger' => 'danger', 'success' => 'success', 'warning' => 'warning', 'gray' => 'gray'][\Modules\Syndicate\Models\SyndicateRequest::statusColors()[$r->status] ?? ''] ?? 'info' }}">{{ $r->status_label }}</span>
                        </div>
                        <div class="syn-grid">
                            <div class="syn-field"><small>تاريخ التقديم</small><strong>{{ $r->created_at?->format('Y-m-d') }}</strong></div>
                            <div class="syn-field"><small>المركز</small><strong>{{ $r->project?->center }}</strong></div>
                            <div class="syn-field"><small>حالة المشروع</small>
                                <span class="syn-badge {{ \Modules\Syndicate\Models\Project::workStatusColors()[$r->project?->work_status] ?? 'gray' }}">{{ $r->project?->work_status_label }}</span></div>
                            @if ($r->certificate)
                                <div class="syn-field"><small>الشهادة الصادرة</small>
                                    <strong><a dir="ltr" href="{{ route('syndicate.verify.show', $r->certificate->number) }}">{{ $r->certificate->number }}</a></strong></div>
                            @endif
                            @if ($r->status === 'rejected' && $r->rejection_reason)
                                <div class="syn-field syn-field--full"><small>سبب الرفض</small><strong>{{ $r->rejection_reason }}</strong></div>
                            @endif
                        </div>
                    </section>
                @endisset

                {{-- نتيجة البحث بالرقم القومي --}}
                @if (! empty($projects) && $projects->isNotEmpty())
                    @foreach ($projects as $project)
                        @php $active = $project->activeCertificate; @endphp
                        <section class="syn-card syn-card--accent" aria-label="مشروع {{ $project->code }}" data-reveal>
                            <div class="syn-card__head">
                                <div>
                                    <span class="kfs-iframe" aria-hidden="true"><i class="fa-solid fa-building"></i></span>
                                    <div>
                                        <h3>مشروع <span dir="ltr">{{ $project->code }}</span></h3>
                                        <span class="syn-card__meta"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $project->center }}</span>
                                    </div>
                                </div>
                                <span class="syn-badge {{ \Modules\Syndicate\Models\Project::workStatusColors()[$project->work_status] ?? 'gray' }}">{{ $project->work_status_label }}</span>
                            </div>
                            @if ($project->isSuspended())
                                <div class="syn-banner danger" role="alert"><i class="fas fa-hand-paper" aria-hidden="true"></i>
                                    <div><strong>الأعمال موقوفة — لا يوجد مهندس مشرف</strong> منذ {{ $project->suspended_at?->format('Y-m-d') }}. يجب إيقاف الأعمال لحين صدور شهادة إشراف جديدة.</div></div>
                            @endif
                            <div class="syn-grid">
                                <div class="syn-field"><small>رقم الترخيص</small><strong>{{ $project->license_number ?? '-' }}</strong></div>
                                <div class="syn-field"><small>المهندس المشرف</small><strong>{{ $active?->engineer_name ?? 'لا يوجد' }}</strong></div>
                                <div class="syn-field"><small>رقم عضوية النقابة</small><strong>{{ $active?->engineer_syndicate_number ?? '-' }}</strong></div>
                                <div class="syn-field"><small>شهادة الإشراف السارية</small>
                                    <strong>@if ($active)<a dir="ltr" href="{{ route('syndicate.verify.show', $active->number) }}">{{ $active->number }}</a>@else - @endif</strong></div>
                            </div>
                        </section>
                    @endforeach
                @endif

                @if (! empty($legacy) && $legacy->isNotEmpty())
                    <section class="syn-card" aria-labelledby="legacy-title" data-reveal>
                        <div class="syn-card__head">
                            <div>
                                <span class="kfs-iframe" aria-hidden="true"><i class="fa-solid fa-box-archive"></i></span>
                                <div><h3 id="legacy-title">شهادات إشراف مسجلة سابقاً</h3></div>
                            </div>
                        </div>
                        <div class="syn-table-wrap">
                            <table class="syn-table syn-table--stack">
                                <thead><tr><th scope="col">رقم الشهادة</th><th scope="col">نوع التدخل</th><th scope="col">المهندس</th><th scope="col">رقم الترخيص</th><th scope="col">المدينة</th></tr></thead>
                                <tbody>
                                @foreach ($legacy as $c)
                                    <tr>
                                        <td data-label="رقم الشهادة" dir="ltr">{{ $c->number ?? '-' }}</td>
                                        <td data-label="نوع التدخل">{{ $c->type_label }} {{ $c->sub_type_label ? '(' . $c->sub_type_label . ')' : '' }}</td>
                                        <td data-label="المهندس">{{ $c->engineer_name }}</td>
                                        <td data-label="رقم الترخيص">{{ $c->license_number }}</td>
                                        <td data-label="المدينة">{{ $c->city }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </main>
@endsection
