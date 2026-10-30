@extends('layouts.app')
@section('title', 'تواصل مع قطاع الاستثمار')

@push('css')
    <style>
        .inv-contact-bg {
            padding: 0 0 80px;
        }

        .inv-card {
            border-radius: 20px;
            overflow: hidden;
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
        }

        .inv-info-side {
            background: #1e272e;
            color: #fff;
            padding: 50px;
        }

        .inv-form-side {
            background: #fff;
            padding: 50px;
        }

        .gold-text {
            color: #e1b12c;
        }

        .form-control:focus {
            border-color: #e1b12c;
            box-shadow: 0 0 0 0.25 row rgba(225, 177, 44, 0.25);
        }

        .btn-invest {
            background: #e1b12c;
            color: #1e272e;
            font-weight: 800;
            border-radius: 50px;
            padding: 12px 40px;
            border: none;
            transition: 0.3s;
        }

        .btn-invest:hover {
            background: #1e272e;
            color: #fff;
            transform: translateY(-3px);
        }
    </style>
@endpush

@section('content')
    <x-kfs.page-hero size="compact" title="تواصل مع قطاع الاستثمار" kicker="الاستثمار"
        subtitle="نحن هنا للإجابة على استفساراتكم حول الفرص الاستثمارية والمناطق الصناعية بالمحافظة."
        icon="fa-solid fa-handshake" :image="asset('images/bg/investment.jpg')"
        :crumbs="['الفرص الاستثمارية' => route('investments.index')]" />

    <div class="inv-contact-bg">
        <div class="container">
            <div class="inv-card">
                <div class="row g-0">
                    {{-- معلومات التواصل --}}
                    <div class="col-lg-5 inv-info-side">
                        <h2 class="fw-bold mb-4">قطاع <span class="gold-text">الاستثمار</span></h2>
                        <p class="mb-5 opacity-75">نحن هنا للإجابة على استفساراتكم حول الفرص الاستثمارية والمناطق الصناعية
                            بالمحافظة.</p>
                        {{--
                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3 fs-3 gold-text"><i class="fas fa-headset"></i></div>
                            <div>
                                <h6 class="mb-0">المكتب الفني للاستثمار</h6>
                                <small class="opacity-50">تواصل مباشر: 047-XXXXXXX</small>
                            </div>
                        </div> --}}

                        <div class="d-flex align-items-center mb-4">
                            <div class="ms-3 fs-3 gold-text"><i class="fas fa-building"></i></div>
                            <div>
                                <h6 class="mb-0">موقعنا</h6>
                                <small class="opacity-50">ديوان عام محافظة كفر الشيخ </small>
                            </div>
                        </div>
                    </div>

                    {{-- فورم المراسلة --}}
                    <div class="col-lg-7 inv-form-side">
                        @if (session('success'))
                            <script>Swal.fire({ icon:'success', title:'تم بنجاح', text:{{ json_encode(session('success')) }}, confirmButtonText:'حسناً' });</script>
                        @endif

                        <h4 class="fw-bold mb-4 text-dark text-end">أرسل استفسارك الآن</h4>
                        <form action="{{ route('investment.contact.store') }}" method="POST" class="text-end">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الاسم الكامل</label>
                                    <input type="text" name="name" class="form-control bg-light border-0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">اسم الشركة (إن وجد)</label>
                                    <input type="text" name="company_name" class="form-control bg-light border-0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control bg-light border-0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="text" name="phone" class="form-control bg-light border-0" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">موضوع الاستفسار</label>
                                    <input type="text" name="subject" class="form-control bg-light border-0"
                                        placeholder="مثال: استفسار عن المنطقة الصناعية بمطوبس" required>
                                </div>
                                <div class="col-12 mb-4">
                                    <label class="form-label">رسالتك</label>
                                    <textarea name="message" class="form-control bg-light border-0" rows="5" required></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-invest shadow-lg w-100 h4 py-3">إرسال الطلب الآن</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
