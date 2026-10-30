@extends('marketplace::layouts.store')

@section('store-title', $vendor->store_name)
@section('store-description', \Illuminate\Support\Str::limit(strip_tags((string) $vendor->description), 155) ?: 'متجر ' . $vendor->store_name . ' على متجر ذوي الهمم')
@section('store-heading', $vendor->store_name)
@section('store-breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">{{ $vendor->store_name }}</li>
@endsection

@section('store-content')
    <section aria-labelledby="vendor-about-title" class="card-kfs mkt-vendor-card" data-reveal>
        <div class="card-body mkt-vendor-head">
            @if ($vendor->logoUrl())
                <img src="{{ $vendor->logoUrl() }}" alt="شعار {{ $vendor->store_name }}" class="mkt-logo" width="88" height="88">
            @else
                <span class="mkt-logo" aria-hidden="true">{{ mb_substr($vendor->store_name, 0, 1) }}</span>
            @endif
            <div style="flex:1 1 280px">
                <h2 id="vendor-about-title" style="margin:0">عن المتجر</h2>
                @if ($vendor->center)
                    <p class="hint" style="margin:.2em 0"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $vendor->center }}، محافظة كفر الشيخ</p>
                @endif
                @if (filled($vendor->description))
                    <p style="margin:.4em 0 0">{!! nl2br(e($vendor->description)) !!}</p>
                @endif
            </div>
        </div>
    </section>

    <section aria-labelledby="vendor-products-title">
        <div class="section-heading" data-reveal>
            <h2 id="vendor-products-title">منتجات المتجر</h2>
            <p>{{ $products->total() }} منتج</p>
        </div>
        @if ($products->isEmpty())
            <x-kfs.empty-state icon="box" title="لا توجد منتجات منشورة في هذا المتجر حالياً" text="تابع المتجر لاحقاً، أو تصفّح منتجات باقي البائعين." class="mkt-empty-state" data-reveal>
                <a href="{{ route('market.products.index') }}" class="btn-kfs-outline">تصفّح كل المنتجات</a>
            </x-kfs.empty-state>
        @else
            <ul class="mkt-grid" data-stagger>
                @foreach ($products as $product)
                    <li>@include('marketplace::partials.product-card', ['product' => $product])</li>
                @endforeach
            </ul>
            <nav aria-label="صفحات المنتجات">{{ $products->links() }}</nav>
        @endif
    </section>
@endsection
