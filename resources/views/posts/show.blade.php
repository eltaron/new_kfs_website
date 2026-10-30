@extends('layouts.app')
@section('title', $post->title)
@push('css')
    <style>
        .kfs-share { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; }
        .kfs-share a { display: grid; place-items: center; height: 44px; border: 1px solid var(--line); border-radius: var(--r); color: var(--kfs-charcoal); background: #fff; }
        .kfs-share a:hover { border-color: var(--kfs-primary); color: var(--kfs-primary); }
        .kfs-article { padding: 0; overflow: hidden; }
        .kfs-article__body { padding: clamp(20px, 3.5vw, 40px); }
        .kfs-article .kfs-figure { margin: 0; border-radius: 0; }
        .kfs-tags { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--line); display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; font-size: var(--fs-sm); color: var(--kfs-text-soft); }
    </style>
@endpush

@section('content')
    <x-kfs.page-hero :title="$post->title" icon="fa-solid fa-newspaper" :image="asset('images/bg/news.jpg')"
        :kicker="$post->category->name ?? 'الأخبار'" :crumbs="['الأخبار' => route('posts.index')]" />

    <main class="main-content">
        <div class="container pb-5">
            <div class="kfs-detail">
                <div class="kfs-detail__main">
                    <article class="kfs-panel kfs-article">
                        @if ($post->thumbnail)
                            <figure class="kfs-figure"><img src="{{ Storage::url($post->thumbnail) }}" alt="{{ $post->title }}"></figure>
                        @endif
                        <div class="kfs-article__body">
                            <div class="kfs-meta-row">
                                @if ($post->category)
                                    <a href="{{ route('posts.index', ['category' => $post->category->slug]) }}" class="kfs-badge">{{ $post->category->name }}</a>
                                @endif
                                @if ($post->published_at)
                                    <span><i class="fa-regular fa-calendar" aria-hidden="true"></i><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('d F Y') }}</time></span>
                                @endif
                                <span><i class="fa-regular fa-user" aria-hidden="true"></i>فريق التحرير</span>
                            </div>
                            <div class="article-inner">
                                {!! clean($post->content, 'cms') !!}
                            </div>
                            <div class="kfs-tags">
                                <i class="fa-solid fa-tags text-primary" aria-hidden="true"></i> الكلمات الدالة:
                                <span class="kfs-badge">#كفر_الشيخ</span><span class="kfs-badge">#المحافظة</span><span class="kfs-badge">#أخبار_رسمية</span>
                            </div>
                        </div>
                    </article>

                    @if ($post->allow_comments)
                        <section class="kfs-panel mt-4" aria-labelledby="kfsCommentTitle">
                            <h2 class="kfs-panel__title" id="kfsCommentTitle"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i> اترك تعليقك</h2>
                            <form action="{{ route('comments.store', $post) }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="cAuthor">الاسم</label>
                                        <input type="text" id="cAuthor" name="author_name" class="form-control" placeholder="الاسم" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="cEmail">البريد الإلكتروني</label>
                                        <input type="email" id="cEmail" name="author_email" class="form-control" placeholder="name@example.com" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="cContent">التعليق</label>
                                        <textarea id="cContent" name="content" class="form-control" rows="4" placeholder="نرحب بمقترحاتكم وتعليقاتكم هنا..." required></textarea>
                                    </div>
                                    <div class="col-12"><button type="submit" class="btn-kfs"><span>إضافة تعليقك الآن</span><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button></div>
                                </div>
                            </form>
                        </section>
                    @endif
                </div>

                <aside class="kfs-aside">
                    <div class="kfs-panel">
                        <h2 class="kfs-panel__title"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i> مشاركة الخبر</h2>
                        <div class="kfs-share">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" aria-label="مشاركة على فيسبوك"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
                            <a href="https://x.com/intent/tweet?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" aria-label="مشاركة على إكس"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a>
                            <a href="https://wa.me/?text={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" aria-label="مشاركة عبر واتساب"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
                            <a href="mailto:?subject={{ rawurlencode($post->title) }}&body={{ rawurlencode(url()->current()) }}" aria-label="مشاركة عبر البريد"><i class="fa-solid fa-envelope" aria-hidden="true"></i></a>
                        </div>
                    </div>

                    @if ($relatedPosts->isNotEmpty())
                        <div class="kfs-panel">
                            <h2 class="kfs-panel__title"><i class="fa-solid fa-newspaper" aria-hidden="true"></i> أخبار قد تهمك</h2>
                            <ul class="kfs-linklist">
                                @foreach ($relatedPosts as $related)
                                    <li><a href="{{ route('posts.show', $related->slug) }}">
                                        <img src="{{ $related->thumbnail ? Storage::url($related->thumbnail) : asset('images/kfs.png') }}" alt="" loading="lazy">
                                        <span>{{ Str::limit($related->title, 70) }}
                                            @if ($related->published_at)<small>{{ $related->published_at->translatedFormat('d F Y') }}</small>@endif
                                        </span>
                                    </a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </main>
@endsection
