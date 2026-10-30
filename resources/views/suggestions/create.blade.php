@extends('layouts.app')

@section('title', 'تقديم مقترح')
@push('css')
    <link rel="stylesheet" href="{{ asset('css') }}/complaints.css">
@endpush
@section('content')
    <main class="main-content bg-light">
        {{-- Page Header --}}
        <x-kfs.page-hero title="تقديم مقترح" subtitle="مساهمتك تساعد في تطوير خدماتنا. شاركنا بأفكارك." icon="fa-solid fa-lightbulb" :image="asset('images/bg/suggestion.jpg')" />

        <div class="container py-5">
            <div class="form-wrapper-container mx-auto" style="max-width: 800px;">
                <div class="form-card">
                    <div class="card-header">
                        <div class="header-icon"><i class="fas fa-lightbulb"></i></div>
                        <h3>نموذج تقديم مقترح</h3>
                        <p>كل فكرة هي خطوة نحو مستقبل أفضل.</p>
                    </div>
                    <div class="card-body">

                        @if (session('success'))
                            <script>Swal.fire({ icon:'success', title:'تم بنجاح', text:{{ json_encode(session('success')) }}, confirmButtonText:'حسناً' });</script>
                        @endif

                        {{-- Validation Errors --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>حدث خطأ!</strong> يرجى مراجعة الحقول أدناه.
                            </div>
                        @endif

                        <form action="{{ route('suggestions.store') }}" method="POST" class="needs-validation" novalidate>
                            @csrf

                            {{-- Personal Information Section --}}
                            <h5 class="form-section-title">بياناتك الشخصية (اختياري)</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">الاسم الكامل </label>
                                    <input type="text" id="name" name="name" class="form-control"
                                        value="{{ old('name', auth()->check() ? auth()->user()->name : '') }}"
                                        {{ auth()->check() ? '' : 'required' }}>

                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="national_id" class="form-label">الرقم القومي (14 رقم) </label>
                                    <input type="text" id="national_id" name="national_id" class="form-control"
                                        pattern="\d{14}" title="يجب إدخال 14 رقم"
                                        value="{{ old('national_id', auth()->check() ? auth()->user()->national_id : '') }}"
                                        {{ auth()->check() ? '' : 'required' }}>

                                    @error('national_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">رقم الهاتف للتواصل </label>
                                    <input type="tel" id="phone" name="phone" class="form-control"
                                        value="{{ old('phone', auth()->check() ? auth()->user()->phone : '') }}"
                                        {{ auth()->check() ? '' : 'required' }}>

                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني </label>
                                    <input type="email" id="email" name="email" class="form-control"
                                        value="{{ old('email', auth()->check() ? auth()->user()->email : '') }}"
                                        {{ auth()->check() ? '' : 'required' }}>

                                </div>
                            </div>
                            <hr class="my-4">

                            {{-- Suggestion Details Section --}}
                            <h5 class="form-section-title">تفاصيل المقترح</h5>
                            <div class="mb-3">
                                <label for="subject" class="form-label">عنوان المقترح <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="subject" name="subject" class="form-control"
                                    value="{{ old('subject') }}" required>
                            </div>
                            <div class="mb-4">
                                <label for="message" class="form-label">اشرح مقترحك بالتفصيل <span
                                        class="text-danger">*</span></label>
                                <textarea id="message" name="message" class="form-control" rows="8" required>{{ old('message') }}</textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">إرسال المقترح</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
