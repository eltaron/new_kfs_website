@extends('marketplace::layouts.store')

@php

    /** @var \Modules\Marketplace\Models\MarketOrder $order */
    $steps = [
        'placed' => 'تم استلام الطلب',
        'confirmed' => $order->payment_method === \Modules\Marketplace\Models\MarketOrder::METHOD_COD ? 'تم تأكيد الطلب' : 'تم الدفع',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التسليم',
    ];
    $reached = match ($order->status) {
        \Modules\Marketplace\Models\MarketOrder::STATUS_PAID => 1,
        \Modules\Marketplace\Models\MarketOrder::STATUS_PROCESSING => $order->payment_method === \Modules\Marketplace\Models\MarketOrder::METHOD_COD ? 1 : 2,
        \Modules\Marketplace\Models\MarketOrder::STATUS_SHIPPED => 3,
        \Modules\Marketplace\Models\MarketOrder::STATUS_DELIVERED, \Modules\Marketplace\Models\MarketOrder::STATUS_COMPLETED => 4,
        default => 0,
    };
    $stoppedLabel = in_array($order->status, [\Modules\Marketplace\Models\MarketOrder::STATUS_CANCELLED, \Modules\Marketplace\Models\MarketOrder::STATUS_PAYMENT_FAILED], true);
@endphp

@section('store-title', 'الطلب ' . $order->number)
@section('store-heading', 'الطلب رقم ' . $order->number)
@section('store-breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('market.orders.index') }}">طلباتي</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $order->number }}</li>
@endsection

@section('store-content')
    <section aria-labelledby="order-status-title" class="card-kfs mkt-order-status" data-reveal>
        <div class="card-body">
            <h2 id="order-status-title" style="font-size:1.2em">
                حالة الطلب: <span class="mkt-badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
            </h2>

            @unless ($stoppedLabel)
                <ol class="mkt-steps" aria-label="مراحل الطلب">
                    @foreach (array_values($steps) as $i => $label)
                        <li class="{{ $i < $reached ? 'done' : ($i === $reached ? 'current' : '') }}" @if ($i === $reached) aria-current="step" @endif>
                            @if ($i < $reached)<i class="fa-solid fa-check" aria-hidden="true"></i><span class="mkt-sr-only">(تم) </span>@endif
                            {{ $label }}
                        </li>
                    @endforeach
                </ol>
            @endunless

            @if ($order->isAwaitingPayment())
                <div class="mkt-alert status-pending" role="status">
                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
                    <div>
                        @if ($order->status === \Modules\Marketplace\Models\MarketOrder::STATUS_PAYMENT_FAILED)
                            لم تكتمل عملية الدفع. يمكنك المحاولة مرة أخرى.
                        @else
                            طلبك بانتظار الدفع.
                            @if ($order->reserved_until)
                                الكمية محجوزة لك حتى {{ $order->reserved_until->format('H:i') }}.
                            @endif
                        @endif
                        @if ($lastTransaction)
                            <br><small>آخر محاولة دفع: {{ $lastTransaction->created_at?->format('Y-m-d H:i') }}</small>
                        @endif
                    </div>
                </div>
                <div class="mkt-buy-actions">
                    <form method="post" action="{{ route('market.orders.pay', $order) }}">
                        @csrf
                        <button type="submit" class="btn-kfs"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> ادفع الآن {{ number_format($order->total, 2) }} ج.م</button>
                    </form>
                    <form method="post" action="{{ route('market.orders.cancel', $order) }}" onsubmit="return confirm('هل تريد إلغاء هذا الطلب؟');">
                        @csrf
                        <button type="submit" class="btn-kfs-danger"><i class="fa-solid fa-xmark" aria-hidden="true"></i> إلغاء الطلب</button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    <div class="mkt-layout wide-aside" style="margin-top:1.2em">
        <div>
            @foreach ($order->subOrders() as $vendorId => $items)
                @php $vendor = $items->first()->vendor; @endphp
                <section class="card-kfs mkt-vendor-group" aria-labelledby="sub-{{ $vendorId }}" data-reveal>
                    <div class="card-body">
                        <h2 id="sub-{{ $vendorId }}" style="font-size:1.1em">
                            <i class="fa-solid fa-store" aria-hidden="true"></i>
                            من متجر:
                            @if ($vendor && $vendor->isApproved())
                                <a href="{{ route('market.vendors.show', $vendor->slug) }}">{{ $vendor->store_name }}</a>
                            @else
                                {{ $vendor?->store_name ?? '—' }}
                            @endif
                        </h2>
                        <div class="mkt-table-wrap">
                            <table class="mkt-table stack">
                                <caption class="mkt-sr-only">منتجات هذا المتجر في طلبك</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">المنتج</th>
                                        <th scope="col">الكمية</th>
                                        <th scope="col">الإجمالي</th>
                                        <th scope="col">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td data-label="المنتج">
                                                @if ($item->product && $item->product->slug)
                                                    <a href="{{ route('market.products.show', $item->product->slug) }}">{{ $item->product_name }}</a>
                                                @else
                                                    {{ $item->product_name }}
                                                @endif
                                                @if ($item->variant !== '')<br><small>{{ $item->variant }}</small>@endif
                                                @if ($item->fulfillment_status === \Modules\Marketplace\Models\MarketOrderItem::FULFILLMENT_DELIVERED && ! $item->review && $item->product?->slug)
                                                    <br><a href="{{ route('market.products.show', $item->product->slug) }}#reviews">قيّم المنتج</a>
                                                @endif
                                            </td>
                                            <td data-label="الكمية">{{ $item->quantity }}</td>
                                            <td data-label="الإجمالي">{{ number_format($item->line_total, 2) }} ج.م</td>
                                            <td data-label="الحالة">
                                                @if ($order->isFulfillable() || $item->fulfillment_status === \Modules\Marketplace\Models\MarketOrderItem::FULFILLMENT_CANCELLED)
                                                    <span class="mkt-badge {{ $item->fulfillmentClass() }}">{{ $item->fulfillmentLabel() }}</span>
                                                @else
                                                    <span class="mkt-badge status-pending">بانتظار الدفع</span>
                                                @endif
                                                @if ($item->tracking_note)
                                                    <br><small>{{ $item->tracking_note }}</small>
                                                @endif
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

        <aside>
            <section class="card-kfs mkt-summary" aria-labelledby="order-total-title">
                <div class="card-body">
                    <h2 id="order-total-title" style="font-size:1.15em">الفاتورة</h2>
                    <dl>
                        <dt>المنتجات</dt><dd>{{ number_format($order->subtotal, 2) }} ج.م</dd>
                        <dt>الشحن</dt><dd>{{ number_format($order->shipping_total, 2) }} ج.م</dd>
                        <dt class="total">الإجمالي</dt><dd class="total">{{ number_format($order->total, 2) }} ج.م</dd>
                        <dt>طريقة الدفع</dt><dd>{{ $order->methodLabel() }}</dd>
                        @if ($order->paid_at)
                            <dt>تاريخ الدفع</dt><dd>{{ $order->paid_at->format('Y-m-d H:i') }}</dd>
                        @endif
                    </dl>
                    @if ($lastTransaction && $order->paid_at && \Illuminate\Support\Facades\Route::has('receipt.print'))
                        <a href="{{ route('receipt.print', $lastTransaction) }}" class="btn-kfs-outline btn-block" style="margin-top:1em" target="_blank" rel="noopener">
                            <i class="fa-solid fa-print" aria-hidden="true"></i> طباعة الإيصال
                        </a>
                    @endif
                </div>
            </section>
            <section class="card-kfs" aria-labelledby="order-ship-title" style="margin-top:1em">
                <div class="card-body">
                    <h2 id="order-ship-title" style="font-size:1.15em">عنوان التوصيل</h2>
                    <p style="margin:0">
                        {{ $order->shipping_name }}<br>
                        <span dir="ltr">{{ $order->shipping_phone }}</span><br>
                        {{ $order->shipping_center }} — {{ $order->shipping_address }}
                    </p>
                </div>
            </section>
        </aside>
    </div>
@endsection
