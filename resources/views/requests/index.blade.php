@extends('layouts.app')
@section('title', 'تقديم طلب')
@push('css')
    <style>
        .request-cards {
            padding: 0 0 60px;
        }

        .request-card {
            display: block;
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            text-decoration: none !important;
            height: 100%;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .request-card-complaint {
            background: linear-gradient(135deg, #1e272e 0%, #2c3e50 100%);
            color: #fff;
        }

        .request-card-emergency {
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
            color: #fff;
        }

        .request-card-icon {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
            margin: 0 auto 25px;
            background: rgba(255, 255, 255, 0.12);
            transition: all 0.4s ease;
        }

        .request-card h3 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--kfs-on-dark);
            /* override global h1–h6 charcoal: cards are dark/red in both themes */
        }

        .request-card p {
            opacity: 0.8;
            font-size: 0.95rem;
            line-height: 1.7;
            max-width: 340px;
            margin: 0 auto;
        }

        .request-card-arrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 10px 24px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.12);
            transition: all 0.3s ease;
        }

        .request-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
        }

        .request-card:hover .request-card-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .request-card:hover .request-card-arrow {
            background: rgba(255, 255, 255, 0.25);
            gap: 14px;
        }

        @media (max-width: 767px) {
            .request-card {
                padding: 35px 25px;
            }
        }
    </style>
@endpush
@section('content')
    <main class="main-content">
        <x-kfs.page-hero title="كيف يمكننا مساعدتك؟" kicker="الشكاوى والمقترحات" icon="fa-solid fa-headset" current="تقديم طلب"
            subtitle="اختر نوع الطلب الذي تريد تقديمه وسيتم توجيهك إلى النموذج المناسب" />
        <div class="container">
            <div class="request-cards">
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-5 col-md-6">
                        <a href="https://www.shakwa.eg/GCP/Stakeholder/Form.aspx"
                            class="request-card request-card-complaint">
                            <div class="request-card-icon"><i class="fas fa-edit"></i></div>
                            <h3>تقديم شكوى</h3>
                            <p>إذا كنت تواجه مشكلة مع إحدى الخدمات أو لديك استفسار حول معاملة رسمية</p>
                            <span class="request-card-arrow">
                                ابدأ الآن <i class="fas fa-arrow-left"></i>
                            </span>
                        </a>
                    </div>
                    <div class="col-lg-5 col-md-6">
                        <a href="{{ route('emergency.create') }}" class="request-card request-card-emergency">
                            <div class="request-card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <h3>بلاغ عاجل</h3>
                            <p>للإبلاغ عن حالات طارئة أو مخالفات أو مشكلات تحتاج إلى تدخل فوري</p>
                            <span class="request-card-arrow">
                                ابدأ الآن <i class="fas fa-arrow-left"></i>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection


