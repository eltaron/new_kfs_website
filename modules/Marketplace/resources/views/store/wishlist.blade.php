@extends('marketplace::layouts.store')

@section('store-title', 'المفضلة')
@section('store-heading', 'المفضلة')
@section('store-breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">المفضلة</li>
@endsection

@section('store-content')
    @if ($items->isEmpty())
        <x-kfs.empty-state icon="box" title="قائمة المفضلة فارغة" text="احفظ المنتجات التي تعجبك لتعود إليها لاحقاً." class="mkt-empty-state" data-reveal>
            <a href="{{ route('market.products.index') }}" class="btn-kfs">تصفّح المنتجات</a>
        </x-kfs.empty-state>
    @else
        <ul class="mkt-grid" data-stagger>
            @foreach ($items as $wish)
                <li style="display:flex;flex-direction:column;gap:.5em">
                    @include('marketplace::partials.product-card', ['product' => $wish->product])
                    <form method="post" action="{{ route('market.wishlist.toggle', $wish->product_id) }}">
                        @csrf
                        <button type="submit" class="btn-kfs-outline btn-block" aria-label="إزالة {{ $wish->product->name }} من المفضلة">
                            <i class="fa-solid fa-heart-crack" aria-hidden="true"></i> إزالة من المفضلة
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
        <nav aria-label="صفحات المفضلة">{{ $items->links() }}</nav>
    @endif
@endsection
