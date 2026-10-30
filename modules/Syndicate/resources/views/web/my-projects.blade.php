@extends('layouts.app')

@section('title', 'مشروعاتي وشهادات الإشراف')

@include('syndicate::web._styles')

@section('content')
<main class="main-content kfs-portal syn-page" dir="rtl">
    <x-kfs.page-hero title="مشروعاتي وشهادات الإشراف الهندسي" kicker="لوحة المواطن" subtitle="المواقع المسجلة باسمكم لدى مجلس المدينة، أو التي تشرفون عليها كمهندسين، وحالة الإشراف عليها." icon="fa-solid fa-helmet-safety"
        :crumbs="['الاستعلام عن الإشراف الهندسي' => route('citizen-lookup')]" />

    <div class="container syn-wrap syn-wrap--wide">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" data-reveal>
            <p class="mb-0 text-muted"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> يتم ربط المشروعات بحسابكم عن طريق الرقم القومي.</p>
            <a href="{{ route('syndicate.verify.form') }}" class="syn-btn syn-btn-outline"><i class="fas fa-qrcode" aria-hidden="true"></i> التحقق من شهادة</a>
        </div>

        @if (session('syndicate_success'))
            <div class="syn-banner success" role="status"><i class="fas fa-check-circle" aria-hidden="true"></i><div>{{ session('syndicate_success') }}</div></div>
        @endif
        @error('syndicate')
            <div class="syn-banner danger" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i><div>{{ $message }}</div></div>
        @enderror

        @if (blank($user->national_id))
            <div class="syn-banner gray"><i class="fas fa-id-card" aria-hidden="true"></i><div>لا يوجد رقم قومي مسجل بحسابكم؛ يتم ربط المشروعات بالحساب عن طريق الرقم القومي.</div></div>
        @endif

        @forelse ($projects as $project)
            @php
                $isOwner = $project->owner_user_id === $user->getKey() || ($user->national_id && $user->national_id === $project->owner_national_id);
                $color = \Modules\Syndicate\Models\Project::workStatusColors()[$project->work_status] ?? 'gray';
                $openTypes = $project->requests->filter->isOpen()->pluck('type')->all();
            @endphp
            <section class="syn-card syn-card--accent" aria-labelledby="p{{ $project->id }}" data-reveal>
                <div class="syn-card__head">
                    <div>
                        <span class="kfs-iframe" aria-hidden="true"><i class="fa-solid {{ $isOwner ? 'fa-building' : 'fa-helmet-safety' }}"></i></span>
                        <div>
                            <h2 id="p{{ $project->id }}">{{ $project->address }}</h2>
                            <span class="syn-card__meta">{{ $project->center }} — كود المشروع <span dir="ltr">{{ $project->code }}</span> — {{ $isOwner ? 'بصفتكم المالك' : 'بصفتكم المهندس المشرف' }}</span>
                        </div>
                    </div>
                    <span class="syn-badge {{ $color }}">{{ $project->work_status_label }}</span>
                </div>

                @if ($project->isSuspended())
                    <div class="syn-banner danger" role="alert">
                        <i class="fas fa-hand-paper" aria-hidden="true"></i>
                        <div><strong>الأعمال موقوفة — المشروع بدون مهندس مشرف</strong> منذ {{ $project->suspended_at?->format('Y-m-d H:i') }}.<br>
                            لا يجوز استئناف الأعمال إلا بعد صدور شهادة إشراف جديدة من نقابة المهندسين.</div>
                    </div>
                @endif

                <div class="syn-grid">
                    <div class="syn-field"><small>المالك</small><strong>{{ $project->owner_name }}</strong></div>
                    <div class="syn-field"><small>رقم الترخيص</small><strong>{{ $project->license_number ?? '-' }}</strong></div>
                    <div class="syn-field"><small>المهندس المشرف الحالي</small>
                        <strong>{{ $project->activeCertificate?->engineer_name ?? 'لا يوجد' }}</strong></div>
                    <div class="syn-field"><small>شهادة الإشراف السارية</small>
                        <strong dir="ltr">{{ $project->activeCertificate?->number ?? '-' }}</strong></div>
                </div>

                @if ($project->certificates->isNotEmpty())
                    <h3 class="syn-card__sec">الشهادات</h3>
                    <div class="syn-table-wrap">
                        <table class="syn-table syn-table--stack">
                            <thead><tr><th scope="col">الرقم</th><th scope="col">النوع</th><th scope="col">المهندس</th><th scope="col">الإصدار</th><th scope="col">الحالة</th><th scope="col"><span class="visually-hidden">طباعة</span></th></tr></thead>
                            <tbody>
                            @foreach ($project->certificates as $c)
                                <tr>
                                    <td data-label="الرقم" dir="ltr">{{ $c->number }}</td>
                                    <td data-label="النوع">{{ $c->kind_label }}</td>
                                    <td data-label="المهندس">{{ $c->engineer_name }}</td>
                                    <td data-label="الإصدار">{{ $c->issued_at?->format('Y-m-d') }}</td>
                                    <td data-label="الحالة"><span class="syn-badge {{ $c->kind === 'cancellation' ? 'danger' : (\Modules\Syndicate\Models\Certificate::statusColors()[$c->status] ?? 'gray') }}">{{ $c->status_label }}</span></td>
                                    <td><a href="{{ $c->printUrl() }}" target="_blank" rel="noopener" class="syn-btn syn-btn-outline syn-btn-sm" aria-label="طباعة الشهادة {{ $c->number }} (تفتح في نافذة جديدة)"><i class="fas fa-print" aria-hidden="true"></i> طباعة</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($project->requests->isNotEmpty())
                    <h3 class="syn-card__sec">المعاملات</h3>
                    <div class="syn-table-wrap">
                        <table class="syn-table syn-table--stack">
                            <thead><tr><th scope="col">رقم المعاملة</th><th scope="col">النوع</th><th scope="col">الحالة</th><th scope="col">التاريخ</th></tr></thead>
                            <tbody>
                            @foreach ($project->requests as $r)
                                <tr>
                                    <td data-label="رقم المعاملة" dir="ltr">{{ $r->transaction_number }}</td>
                                    <td data-label="النوع">{{ $r->type_label }}</td>
                                    <td data-label="الحالة"><span class="syn-badge {{ ['danger' => 'danger', 'success' => 'success', 'warning' => 'warning'][\Modules\Syndicate\Models\SyndicateRequest::statusColors()[$r->status] ?? ''] ?? 'info' }}">{{ $r->status_label }}</span></td>
                                    <td data-label="التاريخ">{{ $r->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <details class="syn-details">
                    <summary><i class="fa-solid fa-timeline" aria-hidden="true"></i> الخط الزمني للمشروع</summary>
                    <ul class="syn-timeline">
                        @foreach ($project->events as $e)
                            @php $ec = \Modules\Syndicate\Models\SyndicateEvent::colors()[$e->type] ?? 'gray'; @endphp
                            <li class="{{ $ec }}">
                                <strong>{{ $e->title }}</strong> <small>— {{ $e->created_at?->format('Y-m-d H:i') }}</small>
                                @if($e->description)<div style="font-size:.9rem">{{ $e->description }}</div>@endif
                            </li>
                        @endforeach
                    </ul>
                </details>

                <div class="syn-actions">
                    @if ($isOwner && $project->work_status !== \Modules\Syndicate\Models\Project::WORK_ACTIVE && ! in_array(\Modules\Syndicate\Models\SyndicateRequest::TYPE_SUPERVISION, $openTypes, true))
                        <form method="POST" action="{{ route('syndicate.my-projects.requests', $project) }}">
                            @csrf
                            <input type="hidden" name="type" value="supervision">
                            <button class="syn-btn syn-btn-primary" type="submit"><i class="fas fa-user-check" aria-hidden="true"></i> تقديم طلب إشراف إلكترونياً</button>
                        </form>
                    @endif
                    @if ($project->isActive() && ! in_array(\Modules\Syndicate\Models\SyndicateRequest::TYPE_CANCELLATION, $openTypes, true))
                        <form method="POST" action="{{ route('syndicate.my-projects.requests', $project) }}"
                              onsubmit="return confirm('سيؤدي إلغاء الإشراف بعد اعتماده من النقابة إلى إيقاف الأعمال بالموقع. هل تريد المتابعة؟');">
                            @csrf
                            <input type="hidden" name="type" value="cancellation">
                            <label class="visually-hidden" for="reason{{ $project->id }}">سبب الإلغاء</label>
                            <input id="reason{{ $project->id }}" name="reason" class="syn-input" placeholder="سبب طلب إلغاء الإشراف" maxlength="1000">
                            <button class="syn-btn syn-btn-danger" type="submit"><i class="fas fa-user-times" aria-hidden="true"></i> طلب إلغاء الإشراف</button>
                        </form>
                    @endif
                </div>
            </section>
        @empty
            <x-kfs.empty-state icon="map" title="لا توجد مشروعات مرتبطة بحسابكم" text="لتسجيل طلب إشراف على موقع بناء، توجه إلى مجلس المدينة / الوحدة المحلية التابع لها الموقع ومعك صورة الترخيص والرقم القومي." data-reveal>
                <a href="{{ route('citizen-lookup') }}" class="btn-kfs-outline"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> الاستعلام برقم المعاملة أو الشهادة</a>
            </x-kfs.empty-state>
        @endforelse
    </div>
</main>
@endsection
