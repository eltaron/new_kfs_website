@extends('layouts.app')
@section('title', 'حجز الشركات السياحية')

@push('css')
<style>
    .booking-page {
        padding: 0 0 40px;
        min-height: 80vh;
    }
    .booking-container {
        max-width: 900px;
        margin: 0 auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        padding: 40px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 30px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-group label {
        font-size: 14px;
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 6px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 10px 14px;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        transition: border-color .2s;
        background: #fff;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #c9a226;
        box-shadow: 0 0 0 3px rgba(201,162,38,0.12);
    }
    .checkbox-container {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        padding-top: 6px;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 14px;
        color: #333;
        cursor: pointer;
    }
    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #c9a226;
    }
    .submit-btn {
        margin-top: 30px;
        padding: 12px 48px;
        background: #c9a226;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: background .2s;
        font-family: inherit;
    }
    .submit-btn:hover {
        background: #b8941f;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        padding: 14px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-weight: 600;
        text-align: center;
    }
    @media (max-width: 768px) {
        .booking-container { padding: 24px 16px; }
        .form-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<x-kfs.page-hero size="compact" title="حجز الشركات السياحية" kicker="السياحة"
    subtitle="يرجى ملء النموذج التالي لحجز رحلة سياحية" icon="fa-solid fa-route" :image="asset('images/bg/terosim.jpg')"
    :crumbs="['المعالم السياحية' => route('landmarks.index')]" />

<div class="booking-page">
    <div class="booking-container">

        @if (session('success'))
            <div class="alert-success">
                {{ session('success') }}
                @if (session('booking_followup_url'))
                    <br>
                    <a href="{{ session('booking_followup_url') }}" style="color:#155724; text-decoration:underline;">
                        رابط متابعة الحجز والسداد الإلكتروني — احتفظوا به
                    </a>
                @endif
            </div>
        @endif

        <form action="{{ route('tour-bookings.store') }}" method="POST">
            @csrf
            <div class="form-grid">

                <div class="form-group">
                    <label>اسم الشركة</label>
                    <input type="text" name="company_name" placeholder="-----" value="{{ old('company_name') }}" required>
                    @error('company_name') <small style="color:#dc3545;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>الرحلة السياحية</label>
                    <input type="text" name="tour_name" placeholder="-----" value="{{ old('tour_name') }}" required>
                    @error('tour_name') <small style="color:#dc3545;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>تاريخ الرحلة</label>
                    <input type="date" name="trip_date" value="{{ old('trip_date', '2026-01-01') }}" required>
                    @error('trip_date') <small style="color:#dc3545;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>نوع الرحلة</label>
                    <div class="checkbox-container">
                        <label class="checkbox-item"><input type="checkbox" name="trip_types[]" value="أثرى" @checked(in_array('أثرى', old('trip_types', [])))> أثرى</label>
                        <label class="checkbox-item"><input type="checkbox" name="trip_types[]" value="شاطئ" @checked(in_array('شاطئ', old('trip_types', [])))> شاطئ</label>
                        <label class="checkbox-item"><input type="checkbox" name="trip_types[]" value="ترفيهي" @checked(in_array('ترفيهي', old('trip_types', [])))> ترفيهي</label>
                        <label class="checkbox-item"><input type="checkbox" name="trip_types[]" value="تاريخي" @checked(in_array('تاريخي', old('trip_types', [])))> تاريخي</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>عدد الأفراد</label>
                    <input type="number" name="people_count" placeholder="-----" min="1" value="{{ old('people_count') }}">
                    @error('people_count') <small style="color:#dc3545;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>حجز وجبات</label>
                    <input type="text" name="meals_booking" placeholder="-----" value="{{ old('meals_booking') }}">
                    @error('meals_booking') <small style="color:#dc3545;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group full-width">
                    <label>حجز رحلة نيلية من دسوق الى فوه الأثرية</label>
                    <input type="text" name="desouk_to_fouh_details" placeholder="-----" value="{{ old('desouk_to_fouh_details') }}">
                    @error('desouk_to_fouh_details') <small style="color:#dc3545;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group full-width">
                    <label>حجز رحلة نيلية من قناطر إدفينا بمطوبس الى مدينة دسوق</label>
                    <input type="text" name="edfina_to_desouk_details" placeholder="-----" value="{{ old('edfina_to_desouk_details') }}">
                    @error('edfina_to_desouk_details') <small style="color:#dc3545;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group full-width">
                    <label>إجمالي التكلفة</label>
                    <p style="margin:6px 0 0; color:#555; font-size:14px;">
                        تحدد إدارة السياحة التكلفة بعد مراجعة الطلب، ويمكنكم السداد إلكترونياً عبر بوابة e-finance من رابط متابعة الحجز.
                    </p>
                </div>

            </div>

            <button type="submit" class="submit-btn">تأكيد الحجز</button>
        </form>
    </div>
</div>
@endsection
