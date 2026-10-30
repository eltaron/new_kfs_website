<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة متابعة الإحصائيات - كفر الشيخ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root { --primary: #DAA520; --dark: #1a1a2e; --darker: #16213e; --card-bg: #ffffff; --text: #333; }
        body { background: #f0f2f5; font-family: 'Cairo', sans-serif; }
        .sidebar { background: var(--dark); min-height: 100vh; position: fixed; width: 250px; padding-top: 20px; z-index: 100; }
        .sidebar .nav-link { color: #aaa; padding: 12px 20px; border-radius: 8px; margin: 2px 10px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(218,165,32,0.15); color: var(--primary); }
        .sidebar .nav-link i { width: 25px; margin-left: 10px; }
        .main-content { margin-right: 250px; padding: 30px; }
        .stat-card { background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: transform 0.2s; border-right: 4px solid transparent; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card.gold { border-right-color: var(--primary); }
        .stat-card.blue { border-right-color: #3498db; }
        .stat-card.green { border-right-color: #27ae60; }
        .stat-card.red { border-right-color: #e74c3c; }
        .stat-card.purple { border-right-color: #9b59b6; }
        .stat-card.orange { border-right-color: #f39c12; }
        .stat-number { font-size: 2rem; font-weight: 700; color: var(--dark); }
        .stat-label { color: #888; font-size: 0.9rem; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .chart-card { background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .section-title { font-size: 1.3rem; font-weight: 700; color: var(--dark); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--primary); }
        .table-wrapper { background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .top-bar { background: var(--card-bg); border-radius: 16px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; }
        .brand { font-size: 1.4rem; font-weight: 700; color: var(--dark); }
        .nav-tabs-custom .nav-link { color: #888; border: none; padding: 10px 20px; border-radius: 8px 8px 0 0; }
        .nav-tabs-custom .nav-link.active { color: var(--primary); background: rgba(218,165,32,0.1); border-bottom: 2px solid var(--primary); }
        canvas { max-height: 300px !important; }
    </style>
</head>
<body>
    {{-- Sidebar --}}
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4 style="color: var(--primary)"><i class="fas fa-chart-pie me-2"></i>لوحة الإحصائيات</h4>
            <small style="color:#666">كفر الشيخ - نظام المتابعة</small>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#overview"><i class="fas fa-home"></i>نظرة عامة</a>
            <a class="nav-link" href="#gis"><i class="fas fa-map-marked-alt"></i>النظام الجيومكاني</a>
            <a class="nav-link" href="#removals"><i class="fas fa-ban"></i>قرارات الإزالة</a>
            <a class="nav-link" href="#syndicate"><i class="fas fa-certificate"></i>شهادات النقابة</a>
            <a class="nav-link" href="#services"><i class="fas fa-concierge-bell"></i>الخدمات</a>
            <a class="nav-link" href="#complaints"><i class="fas fa-exclamation-triangle"></i>الشكاوي والبلاغات</a>
            <a class="nav-link" href="#bookings"><i class="fas fa-calendar-check"></i>الحجوزات</a>
            <a class="nav-link" href="#financial"><i class="fas fa-money-bill-wave"></i>المالية</a>
            <a class="nav-link" href="#content"><i class="fas fa-newspaper"></i>المحتوى</a>
            <a class="nav-link" href="#users"><i class="fas fa-users"></i>المستخدمين</a>
            <hr style="border-color:#333; margin:10px 20px">
            <a class="nav-link" href="{{ url('/') }}"><i class="fas fa-arrow-right"></i>العودة للموقع</a>
            <a class="nav-link" href="{{ url('/logout') }}"><i class="fas fa-sign-out-alt"></i>تسجيل الخروج</a>
        </nav>
    </div>

    {{-- Main Content --}}
    <div class="main-content">
        {{-- Top Bar --}}
        <div class="top-bar">
            <div class="brand">
                <i class="fas fa-chart-pie me-2" style="color:var(--primary)"></i>
                لوحة متابعة الإحصائيات
            </div>
            <div>
                <span class="text-muted">مرحباً {{ auth()->user()->name }}</span>
                <span class="badge bg-warning text-dark ms-2">{{ now()->format('Y-m-d H:i') }}</span>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 1: OVERVIEW --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="overview" class="mb-5">
            <h3 class="section-title"><i class="fas fa-home me-2" style="color:var(--primary)"></i>نظرة عامة</h3>

            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card gold">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">{{ number_format($totalGisSubmissions) }}</div>
                                <div class="stat-label">طلبات الجيومكانية</div>
                            </div>
                            <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-map"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">{{ number_format($totalRemovalOrders) }}</div>
                                <div class="stat-label">قرارات الإزالة</div>
                            </div>
                            <div class="stat-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-ban"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">{{ number_format($totalServiceSubmissions) }}</div>
                                <div class="stat-label">طلبات الخدمات</div>
                            </div>
                            <div class="stat-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-concierge-bell"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card red">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">{{ number_format($totalComplaints) }}</div>
                                <div class="stat-label">الشكاوي</div>
                            </div>
                            <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card purple">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">{{ number_format($totalSyndicateCerts) }}</div>
                                <div class="stat-label">شهادات النقابة</div>
                            </div>
                            <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-certificate"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card orange">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">{{ number_format($totalUsers) }}</div>
                                <div class="stat-label">المستخدمين</div>
                            </div>
                            <div class="stat-icon" style="background:#fff7ed;color:#ea580c"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Revenue Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card" style="border-right-color:#059669">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number text-success">{{ number_format($gisRevenue, 2) }} ج.م</div>
                                <div class="stat-label">إيرادات الجيومكانية</div>
                            </div>
                            <div class="stat-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-coins"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="border-right-color:#2563eb">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number text-primary">{{ number_format($servicesRevenue, 2) }} ج.م</div>
                                <div class="stat-label">إيرادات الخدمات</div>
                            </div>
                            <div class="stat-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-coins"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="border-right-color:#d97706">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number text-warning">{{ number_format($totalRevenue, 2) }} ج.م</div>
                                <div class="stat-label">إجمالي المعاملات المالية</div>
                            </div>
                            <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-money-bill-wave"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Monthly Activity Chart --}}
            <div class="row g-3">
                <div class="col-12">
                    <div class="chart-card">
                        <h5 class="mb-3"><i class="fas fa-chart-line me-2" style="color:var(--primary)"></i>النشاط الشهري (آخر 12 شهر)</h5>
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 2: GIS --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="gis" class="mb-5">
            <h3 class="section-title"><i class="fas fa-map-marked-alt me-2" style="color:var(--primary)"></i>النظام الجيومكاني</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card gold">
                        <div class="stat-number">{{ number_format($totalGisSubmissions) }}</div>
                        <div class="stat-label">إجمالي الطلبات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-number">{{ number_format($gisRevenue) }} ج.م</div>
                        <div class="stat-label">الإيرادات المحصلة</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card blue">
                        @php $pendingPayment = $gisByPayment->where('payment_status','pending')->first()?->total ?? 0; @endphp
                        <div class="stat-number">{{ number_format($pendingPayment) }}</div>
                        <div class="stat-label">بانتظار الدفع</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card purple">
                        @php $fulfilled = $gisByFulfillment->where('fulfillment_status','completed')->first()?->total ?? 0; @endphp
                        <div class="stat-number">{{ number_format($fulfilled) }}</div>
                        <div class="stat-label">تم التنفيذ</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>توزيع الطلبات حسب الحالة</h5>
                        <canvas id="gisStatusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>حالة الدفع</h5>
                        <canvas id="gisPaymentChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 3: REMOVALS --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="removals" class="mb-5">
            <h3 class="section-title"><i class="fas fa-ban me-2" style="color:var(--primary)"></i>قرارات الإزالة</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card red">
                        <div class="stat-number">{{ number_format($totalRemovalOrders) }}</div>
                        <div class="stat-label">إجمالي القرارات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        @php $completedR = $removalsByStage->where('stage','completed')->first()?->total ?? 0; @endphp
                        <div class="stat-number">{{ number_format($completedR) }}</div>
                        <div class="stat-label">تم التنفيذ</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card blue">
                        @php $newR = $removalsByStage->where('stage','created')->first()?->total ?? 0; @endphp
                        <div class="stat-number">{{ number_format($newR) }}</div>
                        <div class="stat-label">جديدة</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <div class="stat-number">{{ number_format($removalsCost, 2) }} ج.م</div>
                        <div class="stat-label">تكلفة المخالفات</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>توزيع القرارات حسب المركز</h5>
                        <canvas id="removalsCenterChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>مراحل العمل</h5>
                        <canvas id="removalsStageChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="table-wrapper mt-3">
                <h5>أعلى 10 مراكز بالمخالفات</h5>
                <table class="table table-hover">
                    <thead class="table-light"><tr><th>المركز</th><th>عدد القرارات</th><th>النسبة</th></tr></thead>
                    <tbody>
                        @foreach($removalsByCenter as $item)
                            @php $pct = $totalRemovalOrders > 0 ? round(($item->total / $totalRemovalOrders) * 100) : 0; @endphp
                            <tr>
                                <td>{{ $item->center }}</td>
                                <td><span class="badge bg-danger">{{ $item->total }}</span></td>
                                <td>
                                    <div class="progress" style="height:20px">
                                        <div class="progress-bar bg-danger" style="width:{{ $pct }}%">{{ $pct }}%</div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 4: SYNDICATE --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="syndicate" class="mb-5">
            <h3 class="section-title"><i class="fas fa-certificate me-2" style="color:var(--primary)"></i>شهادات نقابة المهندسين</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card purple">
                        <div class="stat-number">{{ number_format($totalSyndicateCerts) }}</div>
                        <div class="stat-label">إجمالي الشهادات</div>
                    </div>
                </div>
                <div class="col-md-4">
                    @php $execCerts = $syndicateByType->where('type','execution')->first()?->total ?? 0; @endphp
                    <div class="stat-card green">
                        <div class="stat-number">{{ number_format($execCerts) }}</div>
                        <div class="stat-label">شهادات تنفيذ</div>
                    </div>
                </div>
                
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>نوع التدخل</h5>
                        <canvas id="syndicateTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 5: SERVICES --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="services" class="mb-5">
            <h3 class="section-title"><i class="fas fa-concierge-bell me-2" style="color:var(--primary)"></i>الخدمات الإلكترونية</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-number">{{ number_format($totalServiceSubmissions) }}</div>
                        <div class="stat-label">إجمالي الطلبات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    @php $completedS = $servicesByStatus->where('status','completed')->first()?->total ?? 0; @endphp
                    <div class="stat-card gold">
                        <div class="stat-number">{{ number_format($completedS) }}</div>
                        <div class="stat-label">مكتملة</div>
                    </div>
                </div>
                <div class="col-md-3">
                    @php $pendingS = $servicesByStatus->where('status','pending')->first()?->total ?? 0; @endphp
                    <div class="stat-card orange">
                        <div class="stat-number">{{ number_format($pendingS) }}</div>
                        <div class="stat-label">قيد الانتظار</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <div class="stat-number">{{ number_format($servicesRevenue) }} ج.م</div>
                        <div class="stat-label">الإيرادات</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>حالة الطلبات</h5>
                        <canvas id="servicesStatusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>حالة التنفيذ</h5>
                        <canvas id="servicesFulfillmentChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 6: COMPLAINTS & EMERGENCIES --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="complaints" class="mb-5">
            <h3 class="section-title"><i class="fas fa-exclamation-triangle me-2" style="color:var(--primary)"></i>الشكاوي والبلاغات الطارئة</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card red">
                        <div class="stat-number">{{ number_format($totalComplaints) }}</div>
                        <div class="stat-label">الشكاوي</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <div class="stat-number">{{ number_format($totalSuggestions) }}</div>
                        <div class="stat-label">الاقتراحات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <div class="stat-number">{{ number_format($totalEmergencies) }}</div>
                        <div class="stat-label">البلاغات الطارئة</div>
                    </div>
                </div>
                <div class="col-md-3">
                    @php $resolvedC = $complaintsByStatus->where('status','resolved')->first()?->total ?? 0; @endphp
                    <div class="stat-card green">
                        <div class="stat-number">{{ number_format($resolvedC) }}</div>
                        <div class="stat-label">شكاوي تم حلها</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>حالة الشكاوي</h5>
                        <canvas id="complaintsStatusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>البلاغات الطارئة حسب المركز</h5>
                        <canvas id="emergenciesCenterChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 7: BOOKINGS --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="bookings" class="mb-5">
            <h3 class="section-title"><i class="fas fa-calendar-check me-2" style="color:var(--primary)"></i>الحجوزات</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card blue">
                        <div class="stat-number">{{ number_format($totalBookings) }}</div>
                        <div class="stat-label">حجوزات المعالم</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card green">
                        <div class="stat-number">{{ number_format($totalTourBookings) }}</div>
                        <div class="stat-label">حجوزات السياحة</div>
                    </div>
                </div>
                <div class="col-md-4">
                    @php $confirmedB = $bookingsByStatus->where('status','confirmed')->first()?->total ?? 0; @endphp
                    <div class="stat-card gold">
                        <div class="stat-number">{{ number_format($confirmedB) }}</div>
                        <div class="stat-label">حجوزات مؤكدة</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>حجوزات المعالم</h5>
                        <canvas id="bookingsChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>حجوزات السياحة</h5>
                        <canvas id="tourBookingsChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 8: FINANCIAL --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="financial" class="mb-5">
            <h3 class="section-title"><i class="fas fa-money-bill-wave me-2" style="color:var(--primary)"></i>المالية والمعاملات</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card green">
                        <div class="stat-number">{{ number_format($totalTransactions) }}</div>
                        <div class="stat-label">إجمالي المعاملات</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card gold">
                        <div class="stat-number">{{ number_format($totalRevenue, 2) }} ج.م</div>
                        <div class="stat-label">إجمالي الإيرادات المحصلة</div>
                    </div>
                </div>
                <div class="col-md-4">
                    @php $pendingT = $transactionsByStatus->where('status','pending')->first()?->total ?? 0; @endphp
                    <div class="stat-card orange">
                        <div class="stat-number">{{ number_format($pendingT) }}</div>
                        <div class="stat-label">معاملات معلقة</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>حالة المعاملات</h5>
                        <canvas id="transactionsChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>مصادر الإيرادات</h5>
                        <canvas id="revenueSourceChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 9: CONTENT --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="content" class="mb-5">
            <h3 class="section-title"><i class="fas fa-newspaper me-2" style="color:var(--primary)"></i>المحتوى والإعدادات</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <div class="stat-number">{{ number_format($totalPosts) }}</div>
                        <div class="stat-label">المنشورات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card gold">
                        <div class="stat-number">{{ number_format($totalAds) }}</div>
                        <div class="stat-label">الأعلانات</div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card purple">
                        <div class="stat-number">{{ number_format($totalCenterGuides) }}</div>
                        <div class="stat-label">أدلة المراكز</div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <div class="stat-number">{{ number_format($totalExams) }}</div>
                        <div class="stat-label">نتائج الامتحانات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <div class="stat-number">{{ number_format($totalInvestmentMessages) }}</div>
                        <div class="stat-label">رسائل الاستثمار</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="chart-card">
                        <h5>المنشورات</h5>
                        <canvas id="postsChart"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-card">
                        <h5>تعديلات الخطط</h5>
                        <canvas id="planModsChart"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-card">
                        <h5>نتائج الامتحانات</h5>
                        <canvas id="examsChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- SECTION 10: USERS --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <section id="users" class="mb-5">
            <h3 class="section-title"><i class="fas fa-users me-2" style="color:var(--primary)"></i>المستخدمين والأدوار</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card gold">
                        <div class="stat-number">{{ number_format($totalUsers) }}</div>
                        <div class="stat-label">إجمالي المستخدمين</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card blue">
                        <div class="stat-number">{{ number_format($totalRoles) }}</div>
                        <div class="stat-label">عدد الأدوار</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>توزيع المستخدمين حسب المركز</h5>
                        <canvas id="usersCenterChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5>المستخدمين حسب الدور</h5>
                        <canvas id="usersRoleChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="table-wrapper mt-3">
                <h5>آخر 10 مستخدمين مسجلين</h5>
                <table class="table table-hover">
                    <thead class="table-light"><tr><th>الاسم</th><th>الإيميل</th><th>المركز</th><th>الحالة</th><th>تاريخ التسجيل</th></tr></thead>
                    <tbody>
                        @foreach($recentUsers as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->center ?? '-' }}</td>
                                <td>
                                    <span class="badge-status {{ $user->status === 'approved' ? 'bg-success' : ($user->status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                        {{ $user->status }}
                                    </span>
                                </td>
                                <td>{{ $user->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="text-center text-muted py-4">
            <small>لوحة متابعة الإحصائيات - محافظة كفر الشيخ &copy; {{ now()->year }}</small>
        </div>
    </div>

    <script>
    const COLORS = ['#DAA520','#3498db','#27ae60','#e74c3c','#9b59b6','#f39c12','#1abc9c','#e67e22','#34495e','#e91e63'];
    const COLORS_ALPHA = COLORS.map(c => c + '33');

    function makeChart(id, type, labels, datasets, options={}) {
        new Chart(document.getElementById(id), {
            type, data: { labels, datasets },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, ...options }
        });
    }

    function pieChart(id, labels, data) {
        makeChart(id, 'doughnut', labels, [{
            data, backgroundColor: COLORS.slice(0, data.length), borderWidth: 2, borderColor: '#fff'
        }]);
    }

    function barChart(id, labels, datasets) {
        makeChart(id, 'bar', labels, datasets, { scales: { y: { beginAtZero: true } } });
    }

    // ─── Overview: Monthly Activity ───
    (function(){
        const m = @json($monthlyActivity);
        makeChart('monthlyChart', 'line', m.map(i=>i.label), [
            { label:'جيومكانية', data:m.map(i=>i.gis), borderColor:'#DAA520', backgroundColor:'#DAA52033', fill:true, tension:0.3 },
            { label:'إزالة', data:m.map(i=>i.removals), borderColor:'#e74c3c', backgroundColor:'#e74c3c33', fill:true, tension:0.3 },
            { label:'خدمات', data:m.map(i=>i.services), borderColor:'#27ae60', backgroundColor:'#27ae6033', fill:true, tension:0.3 },
            { label:'شكاوي', data:m.map(i=>i.complaints), borderColor:'#f39c12', backgroundColor:'#f39c1233', fill:true, tension:0.3 },
        ]);
    })();

    // ─── GIS ───
    (function(){
        const s = @json($gisByStatus);
        pieChart('gisStatusChart', s.map(i=>i.status), s.map(i=>i.total));
        const p = @json($gisByPayment);
        pieChart('gisPaymentChart', p.map(i=>i.payment_status), p.map(i=>i.total));
    })();

    // ─── Removals ───
    (function(){
        const c = @json($removalsByCenter);
        barChart('removalsCenterChart', c.map(i=>i.center), [{ label:'عدد القرارات', data:c.map(i=>i.total), backgroundColor:'#e74c3c' }]);
        const st = @json($removalsByStage);
        pieChart('removalsStageChart', st.map(i=>i.stage), st.map(i=>i.total));
    })();

    // ─── Syndicate ───
    (function(){
        const t = @json($syndicateByType);
        pieChart('syndicateTypeChart', t.map(i=>i.type === 'execution' ? 'تنفيذ' : 'هدم'), t.map(i=>i.total));
    })();

    // ─── Services ───
    (function(){
        const s = @json($servicesByStatus);
        pieChart('servicesStatusChart', s.map(i=>i.status), s.map(i=>i.total));
        const f = @json($servicesByFulfillment);
        pieChart('servicesFulfillmentChart', f.map(i=>i.fulfillment_status), f.map(i=>i.total));
    })();

    // ─── Complaints ───
    (function(){
        const c = @json($complaintsByStatus);
        pieChart('complaintsStatusChart', c.map(i=>i.status), c.map(i=>i.total));
        const e = @json($emergenciesByCenter);
        barChart('emergenciesCenterChart', e.map(i=>i.center), [{ label:'بلاغات طارئة', data:e.map(i=>i.total), backgroundColor:'#f39c12' }]);
    })();

    // ─── Bookings ───
    (function(){
        const b = @json($bookingsByStatus);
        pieChart('bookingsChart', b.map(i=>i.status), b.map(i=>i.total));
        const t = @json($tourBookingsByStatus);
        pieChart('tourBookingsChart', t.map(i=>i.status), t.map(i=>i.total));
    })();

    // ─── Financial ───
    (function(){
        const t = @json($transactionsByStatus);
        pieChart('transactionsChart', t.map(i=>i.status), t.map(i=>i.total));
        barChart('revenueSourceChart', ['جيومكانية','خدمات'], [
            { label:'الإيرادات (ج.م)', data:[{{ $gisRevenue }},{{ $servicesRevenue }}], backgroundColor:['#DAA520','#27ae60'] }
        ]);
    })();

    // ─── Content ───
    (function(){
        const p = @json($postsByStatus);
        pieChart('postsChart', p.map(i=>i.is_published ? 'منشور' : 'مسودة'), p.map(i=>i.total));
        const ex = @json($examsByStatus);
        pieChart('examsChart', ex.map(i=>i.status === 'pass' ? 'ناجح' : 'راسب'), ex.map(i=>i.total));
    })();

    // ─── Users ───
    (function(){
        const uc = @json($usersByCenter);
        barChart('usersCenterChart', uc.map(i=>i.center), [{ label:'مستخدمين', data:uc.map(i=>i.total), backgroundColor:'#3498db' }]);
        const ur = @json($usersPerRole);
        pieChart('usersRoleChart', ur.map(i=>i.role_name), ur.map(i=>i.total));
    })();
    </script>
</body>
</html>
