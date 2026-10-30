@php
    /** @var \Modules\Marketplace\Models\MarketProduct $product */
    $img = $product->primaryImage;
    $available = $product->availableStock();
@endphp
<article class="card-kfs mkt-product" aria-labelledby="p-{{ $product->id }}-name">
    <div class="mkt-thumb">
        @if ($img)
            <img src="{{ $img->url() }}" alt="{{ $img->alt_text }}" loading="lazy" width="400" height="300">
        @else
            <i class="fa-solid fa-gift" aria-hidden="true"></i>
        @endif
    </div>
    <div class="card-body">
        <h3 id="p-{{ $product->id }}-name"><a href="{{ route('market.products.show', $product->slug) }}">{{ $product->name }}</a></h3>
        @if ($product->vendor)
            <span class="mkt-vendor"><span class="mkt-sr-only">البائع: </span><i class="fa-solid fa-store" aria-hidden="true"></i> {{ $product->vendor->store_name }}</span>
        @endif
        @if ($product->rating_count > 0)
            <span class="mkt-stars">
                <span aria-hidden="true">@for ($i = 1; $i <= 5; $i++){{ $i <= round($product->rating_avg) ? '★' : '☆' }}@endfor</span>
                <span class="mkt-sr-only">التقييم {{ number_format($product->rating_avg, 1) }} من 5</span>
                <small>({{ $product->rating_count }})</small>
            </span>
        @endif
        <div class="mkt-card-foot">
            <span class="mkt-price">
                <span class="mkt-sr-only">السعر: </span>{{ number_format($product->price, 2) }} ج.م
                @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                    <del><span class="mkt-sr-only">بدلاً من </span>{{ number_format($product->compare_at_price, 2) }}</del>
                @endif
            </span>
            @if ($available < 1)
                <span class="mkt-out">نفدت الكمية</span>
            @endif
        </div>
    </div>
</article>
