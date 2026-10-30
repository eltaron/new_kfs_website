@extends('marketplace::layouts.store')

@section('store-title', 'طلباتي')
@section('store-heading', 'طلباتي')
@section('store-breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">طلباتي</li>
@endsection

@section('store-content')
    @if ($orders->isEmpty())
        <x-kfs.empty-state icon="inbox" title="لا توجد طلبات بعد" text="عندما تشتري منتجاً ستظهر طلباتك هنا مع حالة التوصيل خطوة بخطوة." class="mkt-empty-state" data-reveal>
            <a href="{{ route('market.products.index') }}" class="btn-kfs"><i class="fa-solid fa-store" aria-hidden="true"></i> ابدأ التسوق</a>
        </x-kfs.empty-state>
    @else
        <div class="card-kfs" data-reveal>
            <div class="card-body mkt-table-wrap">
                <table class="mkt-table stack">
                    <caption>كل طلباتك من متجر ذوي الهمم</caption>
                    <thead>
                        <tr>
                            <th scope="col">رقم الطلب</th>
                            <th scope="col">التاريخ</th>
                            <th scope="col">عدد المنتجات</th>
                            <th scope="col">الإجمالي</th>
                            <th scope="col">الحالة</th>
                            <th scope="col"><span class="mkt-sr-only">تفاصيل</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td data-label="رقم الطلب"><strong dir="ltr">{{ $order->number }}</strong></td>
                                <td data-label="التاريخ">{{ $order->created_at->format('Y-m-d') }}</td>
                                <td data-label="عدد المنتجات">{{ $order->items_count }}</td>
                                <td data-label="الإجمالي">{{ number_format($order->total, 2) }} ج.م</td>
                                <td data-label="الحالة"><span class="mkt-badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span></td>
                                <td>
                                    <a href="{{ route('market.orders.show', $order) }}" class="btn-kfs-outline" aria-label="تفاصيل الطلب {{ $order->number }}">
                                        @if ($order->isAwaitingPayment()) أكمل الدفع @else تتبّع الطلب @endif
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <nav aria-label="صفحات الطلبات">{{ $orders->links() }}</nav>
    @endif
@endsection
