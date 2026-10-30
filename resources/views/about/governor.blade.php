@extends('layouts.app')

@section('title', $settings['governor_page_title'] ?? 'كلمة المحافظ')
@push('css')
    <link rel="stylesheet" href="{{ asset('css') }}/about.css">
@endpush
@section('content')
    <main class="main-content bg-light">

        <x-kfs.page-hero :title="strip_tags(clean($settings['governor_page_title'] ?? 'كلمة المحافظ', 'cms'))" subtitle="المهندس/ إبراهيم عبد القادر مكي محجوب"
            icon="fa-solid fa-feather-pointed" :crumbs="['عن المحافظة' => route('about')]" :image="asset('images/bg/about.jpeg')" />

        <div class="container py-5">
            <div class="governor-message-wrapper">
                <div class="row g-5 align-items-start">

                    {{-- Governor's Image and Title --}}
                    <div class="col-lg-4">
                        <aside class="sidebar-sticky">
                            <div class="governor-profile-card">
                                @if (!empty($settings['governor_page_image']))
                                    <img src="{{ Storage::url($settings['governor_page_image']) }}" alt="صورة المحافظ"
                                        class="governor-image">
                                @endif
                                <div class="governor-info">
                                    </h4>
                                    
                                    <span class="title">محافظ كفر الشيخ</span>
                                </div>
                            </div>
                        </aside>
                    </div>

                    {{-- Governor's Message Content --}}
                    <div class="col-lg-8">
                        <article class="message-content rich-text-content">
                            {!! clean($settings['governor_page_content'] ?? 'محتوى تجريبي لكلمة المحافظ...', 'cms') !!}
                        </article>
                    </div>

                </div>
            </div>
        </div>
    </main>
@endsection
