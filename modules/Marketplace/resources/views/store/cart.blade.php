@extends('marketplace::layouts.store')

@section('store-title', 'سلة المشتريات')
@section('store-heading', 'سلة المشتريات')
@section('store-breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">سلة المشتريات</li>
@endsection

@section('store-content')
    @foreach ($summary['issues'] as $issue)
        <div class="mkt-alert status-pending" role="status"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div>{{ $issue }}</div></div>
    @endforeach

    @if ($summary['groups']->isEmpty())
        <x-kfs.empty-state icon="box" title="سلة المشتريات فارغة" text="تصفّح المنتجات وأضف ما يعجبك إلى السلة." class="mkt-empty-state" data-reveal>
            <a href="{{ route('market.products.index') }}" class="btn-kfs"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> تصفّح المنتجات</a>
        </x-kfs.empty-state>
    @else
        <div class="mkt-layout wide-aside">
            <div>
                @foreach ($summary['groups'] as $vendorId => $items)
                    @php $vendor = $items->first()->product->vendor; @endphp
                    <section class="card-kfs mkt-vendor-group" aria-labelledby="cart-vendor-{{ $vendorId }}" data-reveal>
                        <div class="card-body">
                            <h2 id="cart-vendor-{{ $vendorId }}" style="font-size:1.15em">
                                <i class="fa-solid fa-store" aria-hidden="true"></i> من متجر: {{ $vendor->store_name }}
                            </h2>
                            <div class="mkt-table-wrap">
                                <table class="mkt-table stack">
                                    <caption class="mkt-sr-only">منتجات متجر {{ $vendor->store_name }} في السلة</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">المنتج</th>
                                            <th scope="col">السعر</th>
                                            <th scope="col">الكمية</th>
                                            <th scope="col">الإجمالي</th>
                                            <th scope="col"><span class="mkt-sr-only">إزالة</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            @php $p = $item->product; @endphp
                                            <tr>
                                                <td data-label="المنتج">
                                                    <div class="mkt-cart-product">
                                                        @if ($p->primaryImage)
                                                            <img src="{{ $p->primaryImage->url() }}" alt="" width="64" height="64">
                                                        @endif
                                                        <div>
                                                            <a href="{{ route('market.products.show', $p->slug) }}">{{ $p->name }}</a>
                                                            @if ($item->variant !== '')
                                                                <br><small>{{ $item->variant }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td data-label="السعر">{{ number_format($p->price, 2) }} ج.م</td>
                                                <td data-label="الكمية">
                                                    <form method="post" action="{{ route('market.cart.update', $item->id) }}" class="mkt-qty">
                                                        @csrf @method('PATCH')
                                                        <label for="qty-{{ $item->id }}" class="mkt-sr-only">كمية {{ $p->name }}</label>
                                                        <input type="number" id="qty-{{ $item->id }}" name="quantity" value="{{ $item->quantity }}" min="0"
                                                               max="{{ min($p->availableStock(), (int) config('marketplace.max_quantity_per_item', 20)) }}" class="field" inputmode="numeric">
                                                        <button type="submit" class="btn-kfs-outline" aria-label="تحديث كمية {{ $p->name }}"><i class="fa-solid fa-rotate" aria-hidden="true"></i> تحديث</button>
                                                    </form>
                                                </td>
                                                <td data-label="الإجمالي">{{ $item->lineTotal() }} ج.م</td>
                                                <td>
                                                    <form method="post" action="{{ route('market.cart.destroy', $item->id) }}">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn-kfs-danger" aria-label="إزالة {{ $p->name }} من السلة"><i class="fa-solid fa-trash" aria-hidden="true"></i> إزالة</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>

            <aside aria-labelledby="cart-summary-title">
                <div class="card-kfs mkt-summary mkt-sticky" data-reveal>
                    <div class="card-body">
                        <h2 id="cart-summary-title" style="font-size:1.2em">ملخص الطلب</h2>
                        <dl>
                            <dt>المنتجات</dt><dd>{{ $summary['subtotal'] }} ج.م</dd>
                            <dt>الشحن ({{ $summary['groups']->count() }} {{ $summary['groups']->count() > 1 ? 'متاجر' : 'متجر' }})</dt><dd>{{ $summary['shipping'] }} ج.م</dd>
                            <dt class="total">الإجمالي</dt><dd class="total">{{ $summary['total'] }} ج.م</dd>
                        </dl>
                        <a href="{{ route('market.checkout') }}" class="btn-kfs btn-block" style="margin-top:1em">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i> إتمام الشراء
                        </a>
                        @guest
                            <p class="hint">سيُطلب منك تسجيل الدخول لإتمام الشراء، وستبقى منتجاتك في السلة.</p>
                        @endguest
                        <a href="{{ route('market.products.index') }}" class="btn-kfs-outline btn-block" style="margin-top:.5em">متابعة التسوق</a>
                    </div>
                </div>
            </aside>
        </div>
    @endif
@endsection
