@extends('layouts.app')

@section('title', 'نتائج البحث عن: ' . $query)
@push('css')
    <link rel="stylesheet" href="{{ asset('css') }}/search.css">
@endpush
@section('content')
    <main class="main-content bg-light">
        <x-kfs.page-hero size="compact" title="نتائج البحث" kicker="البحث في البوابة" icon="fa-solid fa-magnifying-glass"
            wave-fill="var(--kfs-ivory-2)">
            <x-slot:meta>
                @if (filled($query))
                    <span><i class="fa-solid fa-quote-right" aria-hidden="true"></i> عبارة البحث: «{{ $query }}»</span>
                @endif
                <span><i class="fa-solid fa-list-check" aria-hidden="true"></i> {{ $posts->count() + $projects->count() + $services->count() }} نتيجة</span>
            </x-slot:meta>
        </x-kfs.page-hero>

        <div class="container pt-0 pb-5">

            @if ($posts->isEmpty() && $projects->isEmpty() && $services->isEmpty())
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>عذرًا، لم نتمكن من العثور على نتائج.</h3>
                    <p>جرب استخدام كلمات بحث مختلفة أو تحقق من الإملاء.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary mt-3">العودة للصفحة الرئيسية</a>
                </div>
            @else
                <div class="search-results-wrapper">
                    {{-- Results from Posts (News & Events) --}}
                    @if ($posts->isNotEmpty())
                        <div class="result-category">
                            <h4 class="category-title">الأخبار والأحداث ({{ $posts->count() }})</h4>
                            @foreach ($posts as $post)
                                <a href="{{ route('posts.show', $post->slug) }}" class="result-item">
                                    <div class="item-icon">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <div class="item-content">
                                        <h5 class="item-title">{{ $post->title }}</h5>
                                        <p class="item-excerpt">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($post->content), 180) }}</p>
                                        <span class="item-meta">{{ $post->published_at->diffForHumans() }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Results from Projects --}}
                    @if ($projects->isNotEmpty())
                        <div class="result-category">
                            <h4 class="category-title">المشروعات ({{ $projects->count() }})</h4>
                            @foreach ($projects as $project)
                                <a href="#" class="result-item">
                                    <div class="item-icon">
                                        <i class="fas fa-building-flag"></i>
                                    </div>
                                    <div class="item-content">
                                        <h5 class="item-title">{{ $project->name }}</h5>
                                        <p class="item-excerpt">
                                            {{ \Illuminate\Support\Str::limit($project->description, 180) }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Results from Services --}}
                    @if ($services->isNotEmpty())
                        <div class="result-category">
                            <h4 class="category-title">الخدمات ({{ $services->count() }})</h4>
                            @foreach ($services as $service)
                                <a href="#" class="result-item">
                                    <div class="item-icon">
                                        <i class="fas fa-concierge-bell"></i>
                                    </div>
                                    <div class="item-content">
                                        <h5 class="item-title">{{ $service->title_line_1 }} {{ $service->title_line_2 }}
                                        </h5>
                                        <p class="item-excerpt">
                                            {{ \Illuminate\Support\Str::limit($service->description, 180) }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </main>
@endsection
