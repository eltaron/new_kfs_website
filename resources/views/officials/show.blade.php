@extends('layouts.app')
@section('title', $pageTitle)
@push('css')
    <link rel="stylesheet" href="{{ asset('css') }}/officials.css">
@endpush
@section('content')
    <main class="main-content official-page-bg">
        <x-kfs.page-hero size="detail" :title="$description . ' كفر الشيخ'" :current="$pageTitle"
            kicker="قيادات المحافظة" icon="fa-solid fa-user-tie" wave-fill="var(--governor-light, var(--kfs-bg))" />

        <div class="container pt-0 pb-5">
            {{-- سكشن المسؤول الحالي --}}
            @if ($currentOfficial)
                <div class="official-main-card border-0">
                    <div class="row g-0">
                        <div class="col-lg-4">
                            <div class="official-image-wrapper">
                                <img src="{{ !empty($currentOfficial->official->image) ? (Str::startsWith($currentOfficial->official->image, 'http') ? $currentOfficial->official->image : Storage::url($currentOfficial->official->image)) : asset('images/placeholder.png') }}"
                                    alt="{{ $currentOfficial->official->name }}">
                                <div class="image-accent"></div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="official-info">
                                <span class="official-badge">{{ $pageTitle }} الحالي</span>
                                <h2 class="official-name">{{ $currentOfficial->official->name }}</h2>
                                <div class="official-bio custom-scrollbar">
                                    {!! clean($currentOfficial->official->bio, 'cms') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- سكشن السابقين --}}
            @if ($previousOfficials->isNotEmpty())
                <div class="previous-section mt-5">
                    <div class="section-heading text-center mb-5">
                        <h3 class="section-title">الأرشيف التاريخي للسادة {{ $pageTitle }} السابقين</h3>
                        <p class="section-subtitle">شخصيات أضاءت مسيرة العطاء في المحافظة</p>
                    </div>

                    <div class="row g-4 row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 justify-content-center">
                        @foreach ($previousOfficials as $role)
                            <div class="col">
                                <div class="previous-official-card text-center h-100">
                                    <div class="card-img-holder">
                                        <img src="{{ !empty($role->official->image) ? (Str::startsWith($role->official->image, 'http') ? $role->official->image : Storage::url($role->official->image)) : asset('images/placeholder.png') }}"
                                            alt="{{ $role->official->name }}">
                                    </div>
                                    <div class="card-body-custom">
                                        <h5 class="official-p-name">{{ $role->official->name }}</h5>
                                        <div class="work-period">
                                            <i class="fas fa-clock fa-sm me-1"></i>
                                            {{ $role->start_year }} - {{ $role->end_year ?? 'اليوم' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection
