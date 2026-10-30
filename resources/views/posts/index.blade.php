@extends('layouts.app')

@section('title', 'آخر الأخبار')
@section('content')
    <x-kfs.page-hero title="آخر الأخبار والمقالات" subtitle="تابع آخر المستجدات والفعاليات والأخبار الرسمية لمحافظة كفر الشيخ" icon="fa-solid fa-newspaper" :image="asset('images/bg/news.jpg')" />
    <main class="main-content">
        <div class="container pb-5">
            <nav aria-label="تصنيفات الأخبار">
                <ul class="kfs-chips">
                    <li><a href="{{ route('posts.index') }}" class="kfs-chip {{ !$activeCategory ? 'is-active' : '' }}" @if (!$activeCategory) aria-current="page" @endif>الكل</a></li>
                    @foreach ($categories as $cat)
                        <li><a href="{{ route('posts.index', ['category' => $cat->slug]) }}" class="kfs-chip {{ $activeCategory === $cat->slug ? 'is-active' : '' }}"
                            @if ($activeCategory === $cat->slug) aria-current="page" @endif>
                            {{ $cat->name }} <span class="kfs-chip__count">{{ $cat->posts_count }}</span>
                        </a></li>
                    @endforeach
                </ul>
            </nav>
            @if ($posts->isNotEmpty())
                <div class="kfs-grid kfs-grid--3" data-stagger>
                    @foreach ($posts as $post)
                        <a href="{{ route('posts.show', $post->slug) }}" class="kfs-news">
                            <span class="kfs-card__media">
                                <img src="{{ $post->thumbnail ? Storage::url($post->thumbnail) : asset('images/kfs.png') }}" alt="" loading="lazy">
                                @if ($post->published_at)
                                    <time class="kfs-date" datetime="{{ $post->published_at->toDateString() }}"><b>{{ $post->published_at->format('d') }}</b><span>{{ $post->published_at->translatedFormat('F Y') }}</span></time>
                                @endif
                            </span>
                            <span class="kfs-card__body">
                                <span><span class="kfs-badge">{{ $post->category->name ?? 'غير مصنف' }}</span></span>
                                <span class="kfs-card__title">{{ $post->title }}</span>
                                <span class="kfs-card__text">{{ \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags($post->content), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 130) }}</span>
                                <span class="kfs-card__more">اقرأ المزيد <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                            </span>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination Links --}}
                <div class="pagination-wrapper mt-5">
                    {{ $posts->links() }}
                </div>
            @else
                <x-kfs.empty-state icon="docs" title="لا توجد أخبار لعرضها حاليًا" text="جرّب تصنيفاً آخر أو عُد لاحقاً." />
            @endif
        </div>
    </main>
@endsection
