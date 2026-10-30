@php
    $cartService = app(\Modules\Marketplace\Services\CartService::class);
    $cartCount = $cartService->count($cartService->current(auth()->user(), request()->session()));
    $navVendor = \Modules\Marketplace\Support\MarketplaceAccess::vendorFor(auth()->user());
    $navItems = [
        ['route' => 'market.home', 'match' => 'market.home', 'icon' => 'fa-store', 'label' => 'المتجر'],
        ['route' => 'market.products.index', 'match' => 'market.products.*|market.categories.*', 'icon' => 'fa-magnifying-glass', 'label' => 'تصفح وابحث'],
        ['route' => 'market.cart.show', 'match' => 'market.cart.*|market.checkout*', 'icon' => 'fa-cart-shopping', 'label' => 'سلة المشتريات', 'count' => $cartCount],
    ];
    if (auth()->check()) {
        $navItems[] = ['route' => 'market.wishlist.index', 'match' => 'market.wishlist.*', 'icon' => 'fa-heart', 'label' => 'المفضلة'];
        $navItems[] = ['route' => 'market.orders.index', 'match' => 'market.orders.*', 'icon' => 'fa-box', 'label' => 'طلباتي'];
    }
    if ($navVendor?->isApproved()) {
        $navItems[] = ['url' => url('/vendor'), 'match' => '', 'icon' => 'fa-gauge', 'label' => 'لوحة البائع'];
    } else {
        $navItems[] = ['route' => 'market.sell', 'match' => 'market.sell*', 'icon' => 'fa-hand-holding-heart', 'label' => 'بِع منتجاتك'];
    }
@endphp
<nav class="mkt-nav" aria-label="قائمة المتجر">
    <div class="mkt-container">
        <ul>
            @foreach ($navItems as $item)
                @php
                    $active = $item['match'] !== '' && request()->routeIs(...explode('|', $item['match']));
                @endphp
                <li>
                    <a href="{{ $item['url'] ?? route($item['route']) }}" @if ($active) aria-current="page" @endif>
                        <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                        @isset($item['count'])
                            <span class="mkt-count" aria-label="عدد القطع في السلة: {{ $item['count'] }}">{{ $item['count'] }}</span>
                        @endisset
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
