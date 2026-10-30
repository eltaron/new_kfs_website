@extends('marketplace::layouts.store')

@php
    $field = function (string $name) use ($errors) {
        return $errors->has($name) ? 'aria-invalid=true aria-describedby=' . $name . '-error' : '';
    };
@endphp

@section('store-title', 'طلب فتح متجر')
@section('store-heading', 'طلب فتح متجر')
@section('store-lead', 'املأ البيانات التالية. الحقول المعلَّمة بعلامة * مطلوبة.')
@section('store-breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('market.sell') }}">افتح متجرك</a></li>
    <li class="breadcrumb-item active" aria-current="page">طلب فتح متجر</li>
@endsection

@section('store-content')
    @if ($previous && $previous->status === \Modules\Marketplace\Models\MarketVendor::STATUS_REJECTED)
        <div class="mkt-alert status-pending" role="status">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>
                لم يُقبل طلبك السابق.
                @if ($previous->review_notes) السبب: {{ $previous->review_notes }}. @endif
                يمكنك تعديل البيانات وإعادة التقديم.
            </div>
        </div>
    @endif

    <form method="post" action="{{ route('market.sell.store') }}" enctype="multipart/form-data" novalidate style="max-width:52em">
        @csrf

        <fieldset class="card-kfs" style="background:var(--mk-ivory)">
            <legend>بيانات المتجر</legend>
            <div class="form-group">
                <label for="store_name">اسم المتجر <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="store_name" name="store_name" class="field" required maxlength="120"
                       value="{{ old('store_name', $previous?->store_name) }}" {!! $field('store_name') !!}>
                @include('marketplace::partials.field-error', ['name' => 'store_name'])
            </div>
            <div class="form-group">
                <label for="description">نبذة عن المتجر ومنتجاتك</label>
                <textarea id="description" name="description" class="field" maxlength="2000" rows="4" {!! $field('description') !!}>{{ old('description', $previous?->description) }}</textarea>
                @include('marketplace::partials.field-error', ['name' => 'description'])
            </div>
            <div class="form-group">
                <label for="logo">شعار المتجر أو صورة لك (اختياري)</label>
                <input type="file" id="logo" name="logo" class="field" accept="image/jpeg,image/png,image/webp" aria-describedby="logo-hint" {!! $field('logo') !!}>
                <span class="hint" id="logo-hint">صورة JPG أو PNG بحجم أقصى 2 ميجابايت.</span>
                @include('marketplace::partials.field-error', ['name' => 'logo'])
            </div>
        </fieldset>

        <fieldset class="card-kfs" style="background:var(--mk-ivory)">
            <legend>بيانات التحقق</legend>
            <div class="form-group">
                <label for="national_id">الرقم القومي <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="national_id" name="national_id" class="field" required maxlength="14" inputmode="numeric" dir="ltr" autocomplete="off"
                       value="{{ old('national_id', $user->national_id ?? '') }}" aria-describedby="national_id-hint" {!! $field('national_id') !!}>
                <span class="hint" id="national_id-hint">14 رقماً، ويجب أن يطابق الرقم المسجل في حسابك.</span>
                @include('marketplace::partials.field-error', ['name' => 'national_id'])
            </div>
            <div class="form-group">
                <label for="disability_card_number">رقم بطاقة الخدمات المتكاملة <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="disability_card_number" name="disability_card_number" class="field" required maxlength="50" dir="ltr" autocomplete="off"
                       value="{{ old('disability_card_number') }}" {!! $field('disability_card_number') !!}>
                @include('marketplace::partials.field-error', ['name' => 'disability_card_number'])
            </div>
            <div class="form-group">
                <label for="disability_card">صورة بطاقة الخدمات المتكاملة <span class="req" aria-hidden="true">*</span></label>
                <input type="file" id="disability_card" name="disability_card" class="field" required accept="image/jpeg,image/png,application/pdf"
                       aria-describedby="disability_card-hint" {!! $field('disability_card') !!}>
                <span class="hint" id="disability_card-hint">صورة واضحة أو ملف PDF بحد أقصى 4 ميجابايت. يُحفظ بشكل سري ولا يظهر للعامة.</span>
                @include('marketplace::partials.field-error', ['name' => 'disability_card'])
            </div>
        </fieldset>

        <fieldset class="card-kfs" style="background:var(--mk-ivory)">
            <legend>التواصل والعنوان</legend>
            <div class="form-group">
                <label for="phone">رقم الهاتف المحمول <span class="req" aria-hidden="true">*</span></label>
                <input type="tel" id="phone" name="phone" class="field" required maxlength="11" inputmode="numeric" dir="ltr" autocomplete="tel"
                       value="{{ old('phone', $previous?->phone ?? ($user->phone ?? '')) }}" {!! $field('phone') !!}>
                @include('marketplace::partials.field-error', ['name' => 'phone'])
            </div>
            <div class="form-group">
                <label for="center">المركز <span class="req" aria-hidden="true">*</span></label>
                <select id="center" name="center" class="field" required {!! $field('center') !!}>
                    <option value="">اختر المركز</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center }}" @selected(old('center', $previous?->center) === $center)>{{ $center }}</option>
                    @endforeach
                </select>
                @include('marketplace::partials.field-error', ['name' => 'center'])
            </div>
            <div class="form-group">
                <label for="address">العنوان (اختياري)</label>
                <input type="text" id="address" name="address" class="field" maxlength="255" autocomplete="street-address"
                       value="{{ old('address', $previous?->address) }}" {!! $field('address') !!}>
                @include('marketplace::partials.field-error', ['name' => 'address'])
            </div>
        </fieldset>

        <fieldset class="card-kfs" style="background:var(--mk-ivory)">
            <legend>طريقة استلام الأرباح <span class="req" aria-hidden="true">*</span></legend>
            @php $method = old('payout_method', $previous?->payout_method ?? 'wallet'); @endphp
            <label class="choice"><input type="radio" name="payout_method" value="wallet" @checked($method === 'wallet') data-payout> محفظة إلكترونية (فودافون كاش وغيرها)</label>
            <label class="choice"><input type="radio" name="payout_method" value="bank" @checked($method === 'bank') data-payout> حساب بنكي</label>
            @include('marketplace::partials.field-error', ['name' => 'payout_method'])

            <div data-payout-panel="wallet" @if ($method !== 'wallet') hidden @endif>
                <div class="form-group">
                    <label for="wallet_number">رقم المحفظة</label>
                    <input type="tel" id="wallet_number" name="wallet_number" class="field" maxlength="11" inputmode="numeric" dir="ltr"
                           value="{{ old('wallet_number') }}" {!! $field('wallet_number') !!}>
                    @include('marketplace::partials.field-error', ['name' => 'wallet_number'])
                </div>
            </div>
            <div data-payout-panel="bank" @if ($method !== 'bank') hidden @endif>
                <div class="form-group">
                    <label for="bank_name">اسم البنك</label>
                    <input type="text" id="bank_name" name="bank_name" class="field" maxlength="100" value="{{ old('bank_name') }}" {!! $field('bank_name') !!}>
                    @include('marketplace::partials.field-error', ['name' => 'bank_name'])
                </div>
                <div class="form-group">
                    <label for="account_name">اسم صاحب الحساب</label>
                    <input type="text" id="account_name" name="account_name" class="field" maxlength="120" value="{{ old('account_name') }}" {!! $field('account_name') !!}>
                    @include('marketplace::partials.field-error', ['name' => 'account_name'])
                </div>
                <div class="form-group">
                    <label for="account_number">رقم الحساب / IBAN</label>
                    <input type="text" id="account_number" name="account_number" class="field" maxlength="34" dir="ltr" value="{{ old('account_number') }}" {!! $field('account_number') !!}>
                    @include('marketplace::partials.field-error', ['name' => 'account_number'])
                </div>
            </div>
        </fieldset>

        <div class="form-group">
            <label class="choice">
                <input type="checkbox" name="terms" value="1" required @checked(old('terms')) {!! $field('terms') !!}>
                أقر بصحة البيانات وأوافق على شروط البيع في متجر ذوي الهمم.
            </label>
            @include('marketplace::partials.field-error', ['name' => 'terms'])
        </div>

        <button type="submit" class="btn-kfs"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> إرسال الطلب</button>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var radios = document.querySelectorAll('[data-payout]');
            function sync() {
                var checked = document.querySelector('[data-payout]:checked');
                document.querySelectorAll('[data-payout-panel]').forEach(function (panel) {
                    panel.hidden = !checked || panel.dataset.payoutPanel !== checked.value;
                });
            }
            radios.forEach(function (r) { r.addEventListener('change', sync); });
        })();
    </script>
@endpush
