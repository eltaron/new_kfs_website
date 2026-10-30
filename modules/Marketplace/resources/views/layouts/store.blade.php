@extends('layouts.app')

@section('title', trim($__env->yieldContent('store-title', 'متجر ذوي الهمم')) . ' | محافظة كفر الشيخ')
@section('description', trim($__env->yieldContent('store-description', 'متجر ذوي الهمم بمحافظة كفر الشيخ: منتجات يدوية وإبداعات أبناء المحافظة من ذوي الهمم، تسوّق وادعمهم.')))

@push('css')
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" />
    @if (is_file(public_path('css/kfs-ds.css')))
        <link rel="stylesheet" href="{{ asset('css/kfs-ds.css') }}?v={{ filemtime(public_path('css/kfs-ds.css')) }}" />
    @endif
    <link rel="stylesheet" href="{{ $marketCssUrl ?? asset('modules/marketplace/marketplace.css') }}" />
@endpush

@section('content')
    <div class="mkt" lang="ar" dir="rtl">
        <a href="#market-main" class="mkt-skip">تخطَّ إلى محتوى المتجر</a>

        @include('marketplace::partials.nav')

        @php
            $storeLead = trim($__env->yieldContent('store-lead'));
            $storeActions = trim($__env->yieldContent('store-hero-actions'));
        @endphp
        <x-kfs.page-hero id="mkt-hero" class="mkt-page-hero" container="mkt-container" kicker="متجر ذوي الهمم · محافظة كفر الشيخ" icon="fa-solid fa-hand-holding-heart"
            :title="html_entity_decode(trim($__env->yieldContent('store-heading', 'متجر ذوي الهمم')), ENT_QUOTES)"
            :lead="$storeLead !== '' ? new \Illuminate\Support\HtmlString($storeLead) : null"
            :size="request()->routeIs('market.home', 'market.sell') ? 'default' : 'compact'" wave-fill="var(--mk-ivory-2, var(--kfs-bg))">
            <x-slot:breadcrumb>
                @hasSection('store-breadcrumb')
                    <li class="breadcrumb-item"><a href="{{ route('market.home') }}">متجر ذوي الهمم</a></li>
                    @yield('store-breadcrumb')
                @else
                    <li class="breadcrumb-item active" aria-current="page">متجر ذوي الهمم</li>
                @endif
            </x-slot:breadcrumb>
            <span id="mkt-page-title">@yield('store-heading', 'متجر ذوي الهمم')</span>
            @if ($storeActions !== '')
                <x-slot:actions>{!! $storeActions !!}</x-slot:actions>
            @endif
        </x-kfs.page-hero>

        <main id="market-main" class="mkt-container" tabindex="-1">
            @include('marketplace::partials.flash')
            @yield('store-content')
        </main>
    </div>
@endsection
