@extends('marketplace::layouts.store')

@php
    /** @var \Modules\Marketplace\Models\MarketProduct $product */
    $available = $product->availableStock();
    $maxQty = min($available, (int) config('marketplace.max_quantity_per_item', 20));
    $groups = $product->variantGroups();
    $mainImage = $product->images->first();
@endphp

@section('store-title', $product->name)
@section('store-description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 155))
@section('store-heading', $product->name)
@section('store-breadcrumb')
    @if ($product->category)
        <li class="breadcrumb-item"><a href="{{ route('market.categories.show', $product->category->slug) }}">{{ $product->category->name }}</a></li>
    @endif
    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
@endsection

@section('store-content')
    <div class="mkt-product-page">
        <div class="mkt-product-top">
            {{-- Gallery --}}
            <section aria-label="صور المنتج" class="mkt-gallery" data-reveal>
                @if ($mainImage)
                    <figure>
                        <img src="{{ $mainImage->url() }}" alt="{{ $mainImage->alt_text }}" class="mkt-main-img" id="mkt-main-img" width="800" height="800">
                        <figcaption id="mkt-main-caption">{{ $mainImage->alt_text }}</figcaption>
                    </figure>
                    @if ($product->images->count() > 1)
                        <ul class="mkt-thumbs" aria-label="صور إضافية">
                            @foreach ($product->images as $image)
                                <li>
                                    <button type="button" class="mkt-thumb-btn"
                                            data-src="{{ $image->url() }}" data-alt="{{ $image->alt_text }}"
                                            aria-label="عرض الصورة: {{ $image->alt_text }}" @if ($loop->first) aria-pressed="true" @else aria-pressed="false" @endif>
                                        <img src="{{ $image->url() }}" alt="" width="76" height="76" loading="lazy">
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <x-kfs.empty-state icon="box" title="لا توجد صور لهذا المنتج." compact class="mkt-empty-state" />
                @endif
            </section>

            {{-- Buy box --}}
            <section aria-labelledby="buy-title" class="card-kfs mkt-buybox" data-reveal>
                <div class="card-body">
                    <h2 id="buy-title" class="mkt-sr-only">الشراء</h2>
                    <p class="mkt-price mkt-price--lg">
                        <span class="mkt-sr-only">السعر: </span>{{ number_format($product->price, 2) }} ج.م
                        @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                            <del><span class="mkt-sr-only">بدلاً من </span>{{ number_format($product->compare_at_price, 2) }} ج.م</del>
                        @endif
                    </p>

                    <ul class="mkt-meta">
                        <li><i class="fa-solid fa-store" aria-hidden="true"></i> البائع: <a href="{{ route('market.vendors.show', $product->vendor->slug) }}">{{ $product->vendor->store_name }}</a></li>
                        @if ($product->category)
                            <li><i class="fa-solid fa-tag" aria-hidden="true"></i> القسم: {{ $product->category->name }}</li>
                        @endif
                        <li>
                            <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
                            @if ($available > 0)
                                <span class="mkt-badge status-success">متوفر</span> @if ($available <= 5) (باقي {{ $available }} فقط) @endif
                            @else
                                <span class="mkt-badge status-danger">نفدت الكمية</span>
                            @endif
                        </li>
                        @if ($product->rating_count > 0)
                            <li>
                                <i class="fa-solid fa-star" aria-hidden="true"></i>
                                <span>التقييم {{ number_format($product->rating_avg, 1) }} من 5 ({{ $product->rating_count }} تقييم)</span>
                            </li>
                        @endif
                    </ul>

                    @if ($available > 0)
                        <form method="post" action="{{ route('market.cart.store') }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            @foreach ($groups as $i => $group)
                                <div class="form-group">
                                    <label for="variant-{{ $i }}">{{ $group['name'] }} <span class="req" aria-hidden="true">*</span></label>
                                    <select id="variant-{{ $i }}" name="variant[{{ $i }}]" class="field" required
                                            @error('variant') aria-invalid="true" aria-describedby="variant-error" @enderror>
                                        <option value="">اختر {{ $group['name'] }}</option>
                                        @foreach ($group['options'] as $option)
                                            <option value="{{ $option }}" @selected(old('variant.' . $i) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                            @include('marketplace::partials.field-error', ['name' => 'variant'])

                            <div class="form-group">
                                <label for="qty">الكمية</label>
                                <div class="mkt-qty">
                                    <input type="number" id="qty" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="{{ $maxQty }}" inputmode="numeric" class="field"
                                           aria-describedby="qty-hint" @error('quantity') aria-invalid="true" @enderror>
                                </div>
                                <span class="hint" id="qty-hint">الحد الأقصى {{ $maxQty }} قطعة.</span>
                                @include('marketplace::partials.field-error', ['name' => 'quantity'])
                            </div>

                            <div class="mkt-buy-actions">
                                <button type="submit" class="btn-kfs"><i class="fa-solid fa-cart-plus" aria-hidden="true"></i> أضف إلى السلة</button>
                                <button type="submit" name="buy_now" value="1" class="btn-kfs-cyan"><i class="fa-solid fa-bolt" aria-hidden="true"></i> اشترِ الآن</button>
                            </div>
                        </form>
                    @endif

                    @auth
                        <form method="post" action="{{ route('market.wishlist.toggle', $product->id) }}" class="mkt-wish-form">
                            @csrf
                            <button type="submit" class="btn-kfs-outline" aria-pressed="{{ $inWishlist ? 'true' : 'false' }}">
                                <i class="fa-{{ $inWishlist ? 'solid' : 'regular' }} fa-heart" aria-hidden="true"></i>
                                {{ $inWishlist ? 'إزالة من المفضلة' : 'أضف إلى المفضلة' }}
                            </button>
                        </form>
                    @else
                        <p class="mkt-wish-form"><i class="fa-regular fa-heart" aria-hidden="true"></i> <a href="{{ route('login') }}">سجّل الدخول</a> لحفظ المنتج في المفضلة.</p>
                    @endauth
                </div>
            </section>
        </div>

        {{-- Description --}}
        <section aria-labelledby="desc-title" class="card-kfs mkt-panel" data-reveal>
            <div class="card-body">
                <h2 id="desc-title"><i class="fa-solid fa-align-right" aria-hidden="true"></i> وصف المنتج</h2>
                @if (filled($product->description))
                    <div>{!! nl2br(e($product->description)) !!}</div>
                @else
                    <p>لا يوجد وصف.</p>
                @endif
            </div>
        </section>

        {{-- Reviews --}}
        <section aria-labelledby="reviews-title" id="reviews" class="card-kfs mkt-panel" tabindex="-1" data-reveal>
            <div class="card-body">
                <h2 id="reviews-title"><i class="fa-solid fa-star" aria-hidden="true"></i> تقييمات المشترين</h2>

                @if ($canReview)
                    <form method="post" action="{{ route('market.reviews.store', $product->slug) }}" class="card-kfs mkt-review-form">
                        @csrf
                        <fieldset style="border:0;padding:0;margin:0">
                            <legend>{{ $myReview ? 'عدّل تقييمك' : 'قيّم هذا المنتج' }}</legend>
                            <div class="mkt-rating" role="radiogroup" aria-label="التقييم من 1 إلى 5">
                                @for ($i = 5; $i >= 1; $i--)
                                    <label>
                                        <input type="radio" name="rating" value="{{ $i }}" required @checked((int) old('rating', $myReview?->rating) === $i)>
                                        <span aria-hidden="true">{{ str_repeat('★', $i) }}</span>
                                        <span class="mkt-sr-only">{{ $i }} من 5</span>
                                    </label>
                                @endfor
                            </div>
                            @include('marketplace::partials.field-error', ['name' => 'rating'])
                            <div class="form-group" style="margin-top:.75em">
                                <label for="review-comment">تعليقك (اختياري)</label>
                                <textarea id="review-comment" name="comment" class="field" maxlength="1000" rows="3">{{ old('comment', $myReview?->comment) }}</textarea>
                            </div>
                            <button type="submit" class="btn-kfs">حفظ التقييم</button>
                        </fieldset>
                    </form>
                @elseif (auth()->check())
                    <p class="hint">يمكنك تقييم المنتج بعد شرائه.</p>
                @endif

                @if ($reviews->isEmpty())
                    <p>لا توجد تقييمات بعد.</p>
                @else
                    <ul class="mkt-reviews">
                        @foreach ($reviews as $review)
                            <li>
                                <strong>{{ $review->user?->name ?? 'مشترٍ' }}</strong>
                                <span class="mkt-stars"><span aria-hidden="true">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span><span class="mkt-sr-only">{{ $review->rating }} من 5</span></span>
                                <span class="mkt-badge status-success" style="font-size:.75em">مشترٍ موثّق</span>
                                <time datetime="{{ $review->created_at->toDateString() }}" class="hint">{{ $review->created_at->format('Y-m-d') }}</time>
                                @if ($review->comment)
                                    <p style="margin:.3em 0 0">{{ $review->comment }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- Related --}}
        @if ($related->isNotEmpty())
            <section aria-labelledby="related-title">
                <div class="section-heading" data-reveal><span class="sh-kicker" aria-hidden="true">✦</span><h2 id="related-title">منتجات قد تعجبك</h2></div>
                <ul class="mkt-grid" data-stagger>
                    @foreach ($related as $item)
                        <li>@include('marketplace::partials.product-card', ['product' => $item])</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var main = document.getElementById('mkt-main-img');
            var caption = document.getElementById('mkt-main-caption');
            document.querySelectorAll('.mkt-thumb-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!main) return;
                    main.src = btn.dataset.src;
                    main.alt = btn.dataset.alt;
                    if (caption) caption.textContent = btn.dataset.alt;
                    document.querySelectorAll('.mkt-thumb-btn').forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
                });
            });
        })();
    </script>
@endpush
