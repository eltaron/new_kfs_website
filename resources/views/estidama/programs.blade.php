@extends('layouts.app')

@section('title', 'كل البرامج التدريبية')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/estidama-portal.css') }}?v={{ @filemtime(public_path('css/estidama-portal.css')) }}">
@endpush

@php
    $pagePrograms = collect($trainingPrograms->items());
    $statusCounts = $pagePrograms->countBy('status');
    $filters = ['open' => 'التسجيل متاح', 'ongoing' => 'جاري التنفيذ', 'closed' => 'التسجيل مغلق'];
@endphp

@section('content')
    <main class="es-page">
        <x-kfs.page-hero title="البرامج التدريبية" kicker="منصة استدامة" icon="fa-solid fa-graduation-cap"
            subtitle="استكشف جميع البرامج المتاحة والمستقبلية التي يقدمها مركز استدامة."
            :crumbs="['استدامة' => route('estidama.index')]">
            <x-slot:meta>
                <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i> {{ $trainingPrograms->total() }} برنامج</span>
                <span><i class="fa-solid fa-laptop" aria-hidden="true"></i> تسجيل إلكتروني</span>
                <span><i class="fa-solid fa-award" aria-hidden="true"></i> شهادات برمز تحقق QR</span>
            </x-slot:meta>
            <x-slot:actions>
                @auth
                    <a href="{{ route('lms.dashboard') }}" class="btn-kfs-gold"><i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> تعلّمي</a>
                @endauth
                <a href="{{ route('lms.certificates.verify') }}" class="btn-kfs-outline"><i class="fa-solid fa-qrcode" aria-hidden="true"></i> التحقق من شهادة</a>
            </x-slot:actions>
        </x-kfs.page-hero>

        <div class="container">
            <section class="es-tight" aria-labelledby="esListTitle">
                @if ($trainingPrograms->isNotEmpty())
                    <div class="es-head">
                        <div>
                            <span class="es-head__kicker">اختر برنامجك</span>
                            <h2 class="es-head__title" id="esListTitle">كل البرامج التدريبية</h2>
                            <p class="es-head__lead">
                                عرض {{ $trainingPrograms->firstItem() }}–{{ $trainingPrograms->lastItem() }} من {{ $trainingPrograms->total() }} برنامج.
                                اضغط على البرنامج لعرض المحتوى والتسجيل.
                            </p>
                        </div>
                        @if ($statusCounts->count() > 1)
                            <div class="es-chips" role="group" aria-label="تصفية حسب حالة التسجيل">
                                <button type="button" class="es-chip" data-es-filter="all" aria-pressed="true">الكل <small>{{ $pagePrograms->count() }}</small></button>
                                @foreach ($filters as $key => $label)
                                    @if ($statusCounts->get($key))
                                        <button type="button" class="es-chip" data-es-filter="{{ $key }}" aria-pressed="false">{{ $label }} <small>{{ $statusCounts->get($key) }}</small></button>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="es-grid" data-stagger>
                        @foreach ($trainingPrograms as $program)
                            @include('estidama.partials.program-card', ['program' => $program])
                        @endforeach
                    </div>

                    @if ($trainingPrograms->hasPages())
                        <div class="es-pagination pagination-wrapper">
                            {{ $trainingPrograms->links() }}
                        </div>
                    @endif
                @else
                    <div class="es-empty es-empty--lg">
                        <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                        <strong id="esListTitle">عذرًا، لا توجد برامج تدريبية لعرضها حاليًا.</strong>
                        <span>تابعنا قريبًا — يُعلن عن البرامج الجديدة فور فتح باب التسجيل.</span>
                        <a href="{{ route('estidama.index') }}" class="es-btn es-btn--ghost es-btn--sm">العودة إلى استدامة</a>
                    </div>
                @endif
            </section>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-es-filter]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var key = chip.dataset.esFilter;
                document.querySelectorAll('[data-es-filter]').forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
                document.querySelectorAll('[data-es-status]').forEach(function (card) {
                    card.hidden = key !== 'all' && card.dataset.esStatus !== key;
                });
            });
        });
    </script>
@endpush
