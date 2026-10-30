@extends('marketplace::layouts.store')

@section('store-title', 'إتمام الشراء')
@section('store-heading', 'إتمام الشراء')
@section('store-lead', 'أدخل عنوان التوصيل ثم اختر طريقة الدفع.')
@section('store-breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('market.cart.show') }}">سلة المشتريات</a></li>
    <li class="breadcrumb-item active" aria-current="page">إتمام الشراء</li>
@endsection

@section('store-content')
    @foreach ($summary['issues'] as $issue)
        <div class="mkt-alert status-pending" role="status"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div>{{ $issue }}</div></div>
    @endforeach

    <form method="post" action="{{ route('market.checkout.store') }}" class="mkt-layout wide-aside" novalidate>
        @csrf
        <div>
            <fieldset class="card-kfs mkt-fieldset" data-reveal>
                <legend><span class="mkt-legend-num" aria-hidden="true">١</span> عنوان التوصيل</legend>

                <div class="form-group">
                    <label for="shipping_name">اسم المستلم <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" id="shipping_name" name="shipping_name" class="field" required maxlength="120" autocomplete="name"
                           value="{{ old('shipping_name', $user->name) }}" @error('shipping_name') aria-invalid="true" aria-describedby="shipping_name-error" @enderror>
                    @include('marketplace::partials.field-error', ['name' => 'shipping_name'])
                </div>

                <div class="form-group">
                    <label for="shipping_phone">رقم الهاتف المحمول <span class="req" aria-hidden="true">*</span></label>
                    <input type="tel" id="shipping_phone" name="shipping_phone" class="field" required maxlength="11" inputmode="numeric" autocomplete="tel" dir="ltr"
                           value="{{ old('shipping_phone', $user->phone ?? '') }}" aria-describedby="shipping_phone-hint @error('shipping_phone') shipping_phone-error @enderror"
                           @error('shipping_phone') aria-invalid="true" @enderror>
                    <span class="hint" id="shipping_phone-hint">11 رقماً يبدأ بـ 01، سيتصل بك البائع لتأكيد التوصيل.</span>
                    @include('marketplace::partials.field-error', ['name' => 'shipping_phone'])
                </div>

                <div class="form-group">
                    <label for="shipping_center">المركز <span class="req" aria-hidden="true">*</span></label>
                    <select id="shipping_center" name="shipping_center" class="field" required @error('shipping_center') aria-invalid="true" aria-describedby="shipping_center-error" @enderror>
                        <option value="">اختر المركز</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center }}" @selected(old('shipping_center') === $center)>{{ $center }}</option>
                        @endforeach
                    </select>
                    @include('marketplace::partials.field-error', ['name' => 'shipping_center'])
                </div>

                <div class="form-group">
                    <label for="shipping_address">العنوان بالتفصيل <span class="req" aria-hidden="true">*</span></label>
                    <textarea id="shipping_address" name="shipping_address" class="field" required maxlength="255" rows="3" autocomplete="street-address"
                              aria-describedby="shipping_address-hint" @error('shipping_address') aria-invalid="true" @enderror>{{ old('shipping_address') }}</textarea>
                    <span class="hint" id="shipping_address-hint">القرية أو الشارع، رقم المنزل، وعلامة مميزة.</span>
                    @include('marketplace::partials.field-error', ['name' => 'shipping_address'])
                </div>

                <div class="form-group" style="margin:0">
                    <label for="notes">ملاحظات للبائع (اختياري)</label>
                    <textarea id="notes" name="notes" class="field" maxlength="500" rows="2">{{ old('notes') }}</textarea>
                </div>
            </fieldset>

            <fieldset class="card-kfs mkt-fieldset" data-reveal>
                <legend><span class="mkt-legend-num" aria-hidden="true">٢</span> طريقة الدفع</legend>
                <label class="choice mkt-paychoice">
                    <input type="radio" name="payment_method" value="efinance" @checked(old('payment_method', 'efinance') === 'efinance')>
                    <span><i class="fa-solid fa-credit-card" aria-hidden="true"></i> دفع إلكتروني آمن عبر e-Finance (بطاقة بنكية / ميزة / محفظة)</span>
                </label>
                @if ($codEnabled)
                    <label class="choice mkt-paychoice">
                        <input type="radio" name="payment_method" value="cod" @checked(old('payment_method') === 'cod')>
                        <span><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i> الدفع نقداً عند الاستلام</span>
                    </label>
                @endif
                @include('marketplace::partials.field-error', ['name' => 'payment_method'])
                <p class="hint">نحجز الكمية لك لمدة {{ (int) config('marketplace.reservation_minutes', 60) }} دقيقة لإتمام الدفع الإلكتروني.</p>
            </fieldset>
        </div>

        <aside aria-labelledby="checkout-summary-title">
            <div class="card-kfs mkt-summary mkt-sticky" data-reveal>
                <div class="card-body">
                    <h2 id="checkout-summary-title" style="font-size:1.2em">ملخص الطلب</h2>
                    @foreach ($summary['groups'] as $items)
                        <h3 style="font-size:1em;margin:.8em 0 .3em"><i class="fa-solid fa-store" aria-hidden="true"></i> {{ $items->first()->product->vendor->store_name }}</h3>
                        <ul style="list-style:none;padding:0;margin:0 0 .5em">
                            @foreach ($items as $item)
                                <li style="display:flex;justify-content:space-between;gap:.5em">
                                    <span>{{ $item->product->name }} × {{ $item->quantity }} @if ($item->variant !== '')<small>({{ $item->variant }})</small>@endif</span>
                                    <span>{{ $item->lineTotal() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                    <dl>
                        <dt>المنتجات</dt><dd>{{ $summary['subtotal'] }} ج.م</dd>
                        <dt>الشحن</dt><dd>{{ $summary['shipping'] }} ج.م</dd>
                        <dt class="total">الإجمالي</dt><dd class="total">{{ $summary['total'] }} ج.م</dd>
                    </dl>
                    <button type="submit" class="btn-kfs btn-block" style="margin-top:1em">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i> تأكيد الطلب
                    </button>
                    <p class="hint">بالضغط على «تأكيد الطلب» سيتم تحويلك إلى صفحة الدفع الآمن.</p>
                </div>
            </div>
        </aside>
    </form>
@endsection
