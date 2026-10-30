@extends('layouts.app')

@section('title', $project->name)
@push('css')
    <link rel="stylesheet" href="{{ asset('css/projects.css') }}?v={{ @filemtime(public_path('css/projects.css')) }}">
@endpush
@section('content')
    <main class="main-content">
        {{-- Project Header with Thumbnail --}}
        <x-kfs.page-hero :title="$project->name" :kicker="$project->category ?: 'مشروعات المحافظة'" icon="fa-solid fa-diagram-project"
            :image="$project->thumbnail ? Storage::url($project->thumbnail) : asset('images/bg/projects.jpg')"
            :crumbs="['المشروعات' => route('projects.index')]" />

        <div class="container pb-5">
            <div class="project-details-wrapper">

                {{-- Project Description --}}
                @if ($project->description)
                    <div class="project-section kfs-panel mb-4">
                        <h2 class="kfs-panel__title"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> عن المشروع</h2>
                        <div class="rich-text-content">
                            {!! clean($project->description, 'cms') !!}
                        </div>
                    </div>
                @endif

                <div class="row g-4">
                    {{-- Image Gallery --}}
                    @if ($project->images->isNotEmpty())
                        <div class="col-lg-7 project-section">
                            <x-kfs.section-title title="معرض الصور" />
                            <div class="swiper project-gallery-slider">
                                <div class="swiper-wrapper">
                                    @foreach ($project->images as $image)
                                        <div class="swiper-slide">
                                            <a href="{{ Storage::url($image->path) }}" data-fslightbox="project-gallery">
                                                <img src="{{ Storage::url($image->path) }}"
                                                    alt="{{ $image->caption ?? $project->name }}">
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                                {{-- Slider Navigation --}}
                                <div class="swiper-button-next"></div>
                                <div class="swiper-button-prev"></div>
                            </div>
                        </div>
                    @endif
                    <aside class="{{ $project->images->isNotEmpty() ? 'col-lg-5' : 'col-12' }}">
                        {{-- Contact CTA Widget --}}
                        <div class="kfs-panel text-center kfs-aside" style="border-top: 3px solid var(--kfs-gold);">
                            <span class="kfs-stat2__icon mx-auto mb-3" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
                            <h2 class="h5 fw-bold mb-3">في حالة الرغبة في التقدم لأي فرصة أو وجود أي استفسارات</h2>
                            <a href="{{ route('investment.contact') }}" class="btn-kfs"><span>تواصل معنا من هنا</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                        </div>
                    </aside>
                </div>
                {{-- Embedded Map --}}
                @if ($project->iframe || ($project->latitude && $project->longitude))
                    <div class="project-section mt-5">
                        <x-kfs.section-title title="موقع المشروع على الخريطة" />
                        <div class="map-iframe-wrapper-fullwidth">
                            @if ($project->iframe)
                                {{-- If a custom iframe exists, display it --}}
                                {!! clean(preg_replace('/(width|height)="[^"]*"/', '', $project->iframe), 'cms') !!}
                            @else
                                {{-- Otherwise, display the Google Maps embed using coordinates --}}
                                <iframe width="100%" height="450" style="border:0" loading="lazy" allowfullscreen
                                    src="https://maps.google.com/maps?q={{ $project->latitude }},{{ $project->longitude }}&hl=ar&z=15&amp;output=embed">
                                </iframe>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    {{-- Library for image lightbox/gallery popup --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fslightbox/3.4.1/fslightbox.bundle.min.js"></script>
    {{-- Swiper.js for the gallery --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.project-gallery-slider')) {
                const projectGallery = new Swiper('.project-gallery-slider', {
                    loop: true,
                    spaceBetween: 20,
                    slidesPerView: 1,
                    breakpoints: {
                        768: {
                            slidesPerView: 1,
                        },
                        992: {
                            slidesPerView: 1,
                        }
                    },
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                });
            }
        });
    </script>
@endpush
