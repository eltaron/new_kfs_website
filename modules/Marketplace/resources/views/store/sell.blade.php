@extends('marketplace::layouts.store')

@section('store-title', 'افتح متجرك')
@section('store-heading', 'افتح متجرك على متجر ذوي الهمم')
@section('store-lead', 'مساحة مجانية لأبناء محافظة كفر الشيخ من ذوي الهمم لعرض منتجاتهم وبيعها لكل المواطنين.')
@section('store-breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">افتح متجرك</li>
@endsection

@section('store-hero-actions')
    @if ($vendor?->isApproved())
        <a href="{{ url('/vendor') }}" class="btn-kfs"><i class="fa-solid fa-gauge" aria-hidden="true"></i> الذهاب إلى لوحة البائع</a>
    @elseif ($vendor && $vendor->status !== \Modules\Marketplace\Models\MarketVendor::STATUS_REJECTED)
        <a href="{{ route('market.sell.status') }}" class="btn-kfs"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> متابعة حالة طلبك</a>
    @else
        <a href="{{ route('market.sell.apply') }}" class="btn-kfs"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> قدّم طلب فتح متجر</a>
    @endif
@endsection

@section('store-content')
    <section aria-labelledby="how-title">
        <div class="section-heading">
            <h2 id="how-title">كيف تبدأ؟</h2>
            <p>أربع خطوات بسيطة.</p>
        </div>
        <ol class="mkt-steps-how">
            <li class="card-kfs"><h3>سجّل الدخول</h3><p>استخدم حسابك على بوابة المحافظة، أو أنشئ حساباً جديداً.</p></li>
            <li class="card-kfs"><h3>قدّم طلبك</h3><p>اكتب اسم متجرك، وأرفق صورة بطاقة الخدمات المتكاملة، واختر طريقة استلام أرباحك.</p></li>
            <li class="card-kfs"><h3>انتظر الموافقة</h3><p>تراجع إدارة المتجر طلبك ونرسل لك إشعاراً بالنتيجة.</p></li>
            <li class="card-kfs"><h3>أضف منتجاتك وابدأ البيع</h3><p>من لوحة البائع أضف المنتجات وتابع الطلبات والأرباح.</p></li>
        </ol>
    </section>

    <section aria-labelledby="terms-title" class="card-kfs" style="margin-top:2em">
        <div class="card-body">
            <h2 id="terms-title">ما تحتاج معرفته</h2>
            <ul class="mkt-meta">
                <li><i class="fa-solid fa-percent" aria-hidden="true"></i>
                    @if ($commission > 0)
                        عمولة المتجر {{ rtrim(rtrim(number_format($commission, 2), '0'), '.') }}% من قيمة كل منتج يُباع.
                    @else
                        لا توجد أي عمولة على مبيعاتك — المتجر مجاني بالكامل.
                    @endif
                </li>
                <li><i class="fa-solid fa-id-card" aria-hidden="true"></i> مطلوب: بطاقة الخدمات المتكاملة (صورة واضحة) والرقم القومي المسجل في حسابك.</li>
                <li><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> بياناتك الشخصية ومستنداتك محفوظة بشكل سري، ولا يراها إلا فريق المراجعة.</li>
                <li><i class="fa-solid fa-wallet" aria-hidden="true"></i> تُحوَّل أرباح المبيعات المدفوعة إلكترونياً إلى محفظتك أو حسابك البنكي بعد تسليم الطلب.</li>
                <li><i class="fa-solid fa-ban" aria-hidden="true"></i> يُمنع بيع المنتجات المقلّدة أو المخالفة للقانون أو الأغذية غير المرخصة.</li>
            </ul>
            @guest
                <p>لتقديم الطلب <a href="{{ route('login') }}">سجّل الدخول</a> أولاً.</p>
            @endguest
        </div>
    </section>
@endsection
