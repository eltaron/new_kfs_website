@extends('marketplace::layouts.store')

@section('store-title', 'متجر ذوي الهمم')
@section('store-heading', 'متجر ذوي الهمم')
@section('store-lead', 'منتجات يدوية وإبداعات أبناء كفر الشيخ من ذوي الهمم. كل عملية شراء تدعم بائعاً من أبناء المحافظة مباشرة.')

@section('store-hero-actions')
    <a href="{{ route('market.products.index') }}" class="btn-kfs"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> تصفّح المنتجات</a>
    <a href="{{ route('market.sell') }}" class="btn-kfs-outline"><i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i> افتح متجرك</a>
@endsection

@section('store-content')
    {{-- Search --}}
    <section aria-labelledby="home-search-title" class="card-kfs">
        <div class="card-body">
            <h2 id="home-search-title" class="mkt-sr-only">البحث في المتجر</h2>
            <form action="{{ route('market.products.index') }}" method="get" role="search" class="mkt-toolbar" style="margin:0">
                <div style="flex:1 1 260px">
                    <label for="home-q">ابحث عن منتج</label>
                    <input type="search" id="home-q" name="q" class="field" placeholder="مثال: سجاد، خوص، مشغولات" maxlength="100">
                </div>
                <button type="submit" class="btn-kfs-cyan" style="align-self:flex-end"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> بحث</button>
            </form>
        </div>
    </section>

    {{-- Categories --}}
    @if ($categories->isNotEmpty())
        <section aria-labelledby="home-cats-title">
            <div class="section-heading" data-reveal>
                <span class="sh-kicker" aria-hidden="true">٠١</span>
                <h2 id="home-cats-title">الأقسام</h2>
                <p>اختر القسم لعرض منتجاته.</p>
            </div>
            <ul class="mkt-cats" data-stagger>
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ route('market.categories.show', $category->slug) }}" class="card-kfs">
                            <i class="fa-solid {{ $category->icon ?: 'fa-tag' }}" aria-hidden="true"></i>
                            <span>{{ $category->name }}</span>
                            <small>{{ $category->visible_count }} منتج</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Featured --}}
    @if ($featured->isNotEmpty())
        <section aria-labelledby="home-featured-title">
            <div class="section-heading" data-reveal>
                <span class="sh-kicker" aria-hidden="true">٠٢</span>
                <h2 id="home-featured-title">منتجات مختارة</h2>
                <p>منتجات اختارتها إدارة المتجر لجودتها وتميّزها.</p>
            </div>
            <ul class="mkt-grid" data-stagger>
                @foreach ($featured as $product)
                    <li>@include('marketplace::partials.product-card', ['product' => $product])</li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Latest --}}
    <section aria-labelledby="home-latest-title">
        <div class="section-heading section-heading-row" data-reveal>
            <div>
                <span class="sh-kicker" aria-hidden="true">٠٣</span>
                <h2 id="home-latest-title">أحدث المنتجات</h2>
            </div>
            <a href="{{ route('market.products.index') }}" class="btn-kfs-outline">عرض كل المنتجات</a>
        </div>
        @if ($latest->isEmpty())
            <x-kfs.empty-state icon="box" title="لا توجد منتجات منشورة حالياً" text="عُد قريباً! يضيف البائعون منتجات جديدة باستمرار." class="mkt-empty-state" data-reveal />
        @else
            <ul class="mkt-grid" data-stagger>
                @foreach ($latest as $product)
                    <li>@include('marketplace::partials.product-card', ['product' => $product])</li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Vendors --}}
    @if ($vendors->isNotEmpty())
        <section aria-labelledby="home-vendors-title">
            <div class="section-heading" data-reveal>
                <span class="sh-kicker" aria-hidden="true">٠٤</span>
                <h2 id="home-vendors-title">تعرّف على البائعين</h2>
                <p>متاجر يديرها أبناء المحافظة من ذوي الهمم.</p>
            </div>
            <ul class="mkt-cats" data-stagger>
                @foreach ($vendors as $vendor)
                    <li>
                        <a href="{{ route('market.vendors.show', $vendor->slug) }}" class="card-kfs">
                            @if ($vendor->logoUrl())
                                <img src="{{ $vendor->logoUrl() }}" alt="" class="mkt-logo" width="88" height="88" loading="lazy">
                            @else
                                <span class="mkt-logo" aria-hidden="true">{{ mb_substr($vendor->store_name, 0, 1) }}</span>
                            @endif
                            <span>{{ $vendor->store_name }}</span>
                            <small>{{ $vendor->visible_count }} منتج @if ($vendor->center) · {{ $vendor->center }} @endif</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Become a seller --}}
    <section aria-labelledby="home-sell-title" class="card-kfs" style="margin-top:2.5em">
        <div class="card-body">
            <h2 id="home-sell-title">هل أنت من ذوي الهمم ولديك منتج تريد بيعه؟</h2>
            <p>افتح متجرك مجاناً على بوابة المحافظة. نراجع طلبك ونساعدك في عرض منتجاتك واستلام أرباحك.</p>
            <a href="{{ route('market.sell') }}" class="btn-kfs"><i class="fa-solid fa-store" aria-hidden="true"></i> ابدأ البيع الآن</a>
        </div>
    </section>
@endsection
