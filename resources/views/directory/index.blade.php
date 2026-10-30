@extends('layouts.app')
@section('title', 'دليل الهاتف والأرقام الهامة')
@push('css')
    <link rel="stylesheet" href="{{ asset('css') }}/directory.css">
@endpush
@section('content')
    <main class="main-content">

        <x-kfs.page-hero title="دليل الهاتف والأرقام الهامة" subtitle="وصول سريع لأرقام الطوارئ والخدمات العامة بالمحافظة." icon="fa-solid fa-address-book" :image="asset('images/bg/directory.jpg')" />

        <div class="container py-5">
            @if ($groupedEntries->isNotEmpty())
                @foreach ($groupedEntries as $category => $entries)
                    <div class="directory-category-section">
                        <h2 class="category-title">{{ $category }}</h2>
                        <div class="row g-4">
                            @foreach ($entries as $entry)
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="directory-card">
                                        <div class="card-icon">
                                            <i class="{{ $entry->icon_class }}"></i>
                                        </div>
                                        <h4 class="card-name">{{ $entry->name }}</h4>
                                        <p class="card-number">{{ $entry->phone_number }}</p>
                                        <a href="tel:{{ $entry->phone_number }}" class="call-button">
                                            اتصل الآن <i class="fas fa-phone-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-center">لا توجد أرقام لعرضها حاليًا.</p>
            @endif
        </div>
    </main>
@endsection
