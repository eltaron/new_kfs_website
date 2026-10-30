@extends('marketplace::layouts.store')

@php $V = \Modules\Marketplace\Models\MarketVendor::class; @endphp

@section('store-title', 'حالة طلب المتجر')
@section('store-heading', 'حالة طلب المتجر')
@section('store-breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('market.sell') }}">افتح متجرك</a></li>
    <li class="breadcrumb-item active" aria-current="page">حالة الطلب</li>
@endsection

@section('store-content')
    <section class="card-kfs" aria-labelledby="app-status-title" style="max-width:46em">
        <div class="card-body">
            <h2 id="app-status-title">
                متجر «{{ $vendor->store_name }}»:
                <span class="mkt-badge {{ $vendor->statusClass() }}">{{ $vendor->statusLabel() }}</span>
            </h2>

            @switch($vendor->status)
                @case($V::STATUS_PENDING)
                    <p>استلمنا طلبك بتاريخ {{ $vendor->created_at?->format('Y-m-d') }} وهو قيد المراجعة الآن. سنرسل لك إشعاراً فور اتخاذ القرار.</p>
                    @break
                @case($V::STATUS_APPROVED)
                    <p>تم اعتماد متجرك. يمكنك الآن إضافة منتجاتك ومتابعة طلباتك.</p>
                    <a href="{{ url('/vendor') }}" class="btn-kfs"><i class="fa-solid fa-gauge" aria-hidden="true"></i> الذهاب إلى لوحة البائع</a>
                    <a href="{{ route('market.vendors.show', $vendor->slug) }}" class="btn-kfs-outline">عرض صفحة متجري</a>
                    @break
                @case($V::STATUS_REJECTED)
                    <p>نأسف، لم يتم قبول طلبك.</p>
                    @if ($vendor->review_notes)
                        <p><strong>السبب:</strong> {{ $vendor->review_notes }}</p>
                    @endif
                    <a href="{{ route('market.sell.apply') }}" class="btn-kfs">تعديل البيانات وإعادة التقديم</a>
                    @break
                @case($V::STATUS_SUSPENDED)
                    <p>تم إيقاف متجرك مؤقتاً ولا تظهر منتجاتك للمشترين حالياً.</p>
                    @if ($vendor->review_notes)
                        <p><strong>السبب:</strong> {{ $vendor->review_notes }}</p>
                    @endif
                    <p>للاستفسار تواصل مع إدارة المتجر عبر صفحة التواصل بالبوابة.</p>
                    @break
            @endswitch
        </div>
    </section>
@endsection
