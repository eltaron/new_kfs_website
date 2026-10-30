<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>بيان فني رقمي - {{ $record->serial_number ?? $record->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        /* إعدادات الصفحة للطباعة */
        @page {
            size: A4;
            margin: 0;
            padding: 15mm;
        }

        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            color: #1e272e;
            background: #fff;
            line-height: 1.6;
        }

        .print-container {
            width: 100%;
            max-width: 210mm;
            margin: auto;
        }

        /* الهيدر المؤسسي */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid #1e272e;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .header-info h3 {
            margin: 0;
            font-weight: 900;
            font-size: 20px;
            color: #1e272e;
        }

        .header-info h4 {
            margin: 5px 0 0;
            font-weight: 700;
            font-size: 15px;
            color: #555;
        }

        .logo {
            height: 85px;
        }

        /* ستايل العناوين الفرعية */
        .section-header {
            background: #1e272e;
            color: #fff;
            padding: 10px 20px;
            font-weight: 900;
            border-radius: 8px;
            margin: 25px 0 15px;
            font-size: 16px;
            border-right: 8px solid #e1b12c;
            display: flex;
            justify-content: space-between;
        }

        /* الجداول والبيانات */
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            border: 1px solid #ddd;
            padding: 12px 15px;
            border-radius: 10px;
            background: #fcfcfc;
            display: flex;
            justify-content: space-between;
        }

        .info-item strong {
            color: #1e272e;
            font-weight: 800;
        }

        .info-item span {
            color: #555;
        }

        /* منطقة الخريطة والـ QR */
        .location-grid {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }

        .map-placeholder {
            flex: 1;
            border: 2px solid #1e272e;
            border-radius: 15px;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            position: relative;
        }

        .qr-code {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #fff;
            padding: 5px;
            border: 1px solid #eee;
        }

        /* جدول الحدود الأربعة */
        .borders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .borders-table th {
            background: #f1f2f6;
            border: 1px solid #1e272e;
            padding: 10px;
            font-size: 12px;
            font-weight: 900;
        }

        .borders-table td {
            border: 1px solid #1e272e;
            padding: 10px;
            text-align: center;
            font-size: 12px;
        }

        .dir-label {
            background: #f8f9fa;
            font-weight: 900;
            text-align: right !important;
            padding-right: 15px !important;
        }

        /* مربع الاشتراطات العامة والملاحظات */
        .rich-box {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 10px;
            background: #fafafa;
            font-size: 14px;
            text-align: justify;
        }

        .signatures {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            text-align: center;
            font-weight: bold;
        }

        @media print {
            .no-print {
                display: none;
            }

            .print-container {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    {{-- زر الطباعة (يختفي عند الطباعة) --}}
    <div class="no-print" style="text-align: center; padding: 20px; background: #f1f2f6; border-bottom: 1px solid #ddd;">
        <button onclick="window.print()"
            style="padding: 12px 50px; background: #e1b12c; color: #1e272e; border:none; cursor:pointer; font-weight:900; border-radius:50px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">إصدار
            وطباعة المحرر الرسمي 🖨️</button>
    </div>

    <div class="print-container">
        <!-- هيدر المستند -->
        <div class="header">
            <div class="header-info">
                <h3>محافظة كفر الشيخ</h3>
                <h4>مركز نظم المعلومات الجيومكانية</h4>
            </div>
            <img src="{{ asset('logo.png') }}" class="logo">
        </div>

        <!-- أولاً: بيانات الطلب -->
        <div class="section-header"><span>أولاً: بيانات المعاملة الرقمية</span> <small>ID: {{ $record->id }}</small>
        </div>
        <div class="data-grid">
            <div class="info-item"><strong>رقم المسلسل الإداري:</strong>
                <span>{{ $record->serial_number ?? '---' }}</span>
            </div>
            <div class="info-item"><strong>نوع الخدمة المطلوبة:</strong>
                <span>{{ $service->name ?? 'تسجيل مكاني' }}</span>
            </div>
            <div class="info-item"><strong>تاريخ تقديم الطلب:</strong>
                <span>{{ $record->created_at->format('Y-m-d') }}</span>
            </div>
            <div class="info-item"><strong>تاريخ المعاينة الفنية:</strong>
                <span>{{ optional($record->inspection_date)->format('Y-m-d') ?? 'قيد التحديد' }}</span>
            </div>
        </div>

        <!-- ثانياً: بيانات مقدم الطلب -->
        <div class="section-header">ثانياً: بيانات صاحب الشأن / المتقدم</div>
        <div class="data-grid">
            <div class="info-item"><strong>الاسم بالكامل:</strong>
                <span>{{ $record->applicant_info['name'] ?? $record->user->name }}</span>
            </div>
            <div class="info-item"><strong>الصفة القانونية:</strong>
                <span>{{ ($record->applicant_info['type'] ?? '') == 'owner' ? 'المالك الأصيل' : 'وكيل بموجب توكيل' }}</span>
            </div>
            <div class="info-item"><strong>الرقم القومي:</strong> <span>{{ $record->user->national_id }}</span></div>
            <div class="info-item"><strong>رقم الهاتف:</strong> <span>{{ $record->user->phone }}</span></div>
        </div>

        <!-- ثالثاً: بيانات الموقع الجغرافي -->
        <div class="section-header">ثالثاً: بيانات الموقع </div>
        <div class="location-grid">
            <table class="summary-table" style="flex: 1; border-collapse: collapse; border: 1px solid #ddd;">
                <tr style="border-bottom: 1px solid #eee;">
                    <th style="text-align: right; padding: 10px; background: #f9f9f9; width: 40%;">المركز</th>
                    <td style="padding: 10px;">{{ $record->address_info['markaz_name'] ?? 'كفر الشيخ' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <th style="text-align: right; padding: 10px; background: #f9f9f9;">الوحدة المحلية</th>
                    <td style="padding: 10px;">{{ $record->address_info['unit_name'] ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <th style="text-align: right; padding: 10px; background: #f9f9f9;">القرية / العزبة</th>
                    <td style="padding: 10px;">{{ $record->address_info['village_name'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="text-align: right; padding: 10px; background: #f9f9f9;">العنوان التفصيلي</th>
                    <td style="padding: 10px;">{{ $record->address_info['details'] ?? '-' }}</td>
                </tr>
            </table>

            <div class="map-placeholder">
                @if ($record->web_map_url)
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($record->web_map_url) }}"
                        class="qr-code" title="مسح الكود لفتح الخريطة">
                @endif
                <span style="font-weight: 900; color: #ddd; font-size: 24px;">خريطة الـ GIS الرقمية</span>
            </div>
        </div>

        <!-- رابعاً: الاشتراطات العامة -->
        <div class="section-header">رابعاً: الاشتراطات التخطيطية والبنائية العامة</div>

        <div class="rich-box">
            <strong>نص الاشتراطات العامة:</strong><br>
            {!! clean($record->urban_planning['general_requirements'] ?? 'لا توجد اشتراطات عامة إضافية مسجلة لهذه المنطقة.') !!}
        </div>

        <!-- خامساً: بيانات الحدود والاشتراطات الخاصة -->
        <div class="section-header">خامساً: بيان الحدود من الناحية التخطيطية والاشتراطات</div>
        <table class="borders-table">
            <thead>
                <tr>
                    <th>الحد / الجهة</th>
                    <th>نوع الواجهة</th>
                    <th>طول الضلع</th>
                    <th>ع. الشارع</th>
                    <th>الارتداد</th>
                    <th>البروز</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $dirs = [
                        'north' => 'البحري (الشمال)',
                        'south' => 'القبلي (الجنوب)',
                        'east' => 'الشرقي',
                        'west' => 'الغربي',
                    ];
                    $facades = ['none' => 'جار', 'front' => 'أمامية', 'back' => 'خلفية', 'side' => 'جانبية'];
                @endphp
                @foreach ($dirs as $key => $label)
                    <tr>
                        <td class="dir-label">{{ $label }}</td>
                        <td>{{ $facades[$record->borders[$key]['facade_type'] ?? 'none'] ?? '-' }}</td>
                        <td>{{ $record->borders[$key]['length'] ?? '0' }} م</td>
                        <td>{{ $record->borders[$key]['street_width'] ?? '0' }} م</td>
                        <td>{{ $record->borders[$key]['has_setback'] ?? false ? $record->borders[$key]['setback_amount'] . ' م' : '-' }}
                        </td>
                        <td>{{ $record->borders[$key]['has_overhang'] ?? false ? $record->borders[$key]['overhang_amount'] . ' م' : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="data-grid" style="margin-top: 20px">
            <div class="info-item"><strong>الارتفاع المقرر:</strong>
                <span>{{ $record->urban_planning['planned_height'] ?? '-' }} م</span>
            </div>
            <div class="info-item"><strong>نسبة البناء المسموحة:</strong>
                <span>{{ $record->urban_planning['building_percentage'] ?? '-' }} %</span>
            </div>
            <div class="info-item"><strong>الاستخدام المعتمد:</strong>
                <span>
                    @php $usages = ['residential'=>'سكني','commercial'=>'تجاري','industrial'=>'صناعي','mixed'=>'متعدد']; @endphp
                    {{ $usages[$record->urban_planning['planned_usage'] ?? ''] ?? 'غير محدد' }}
                </span>
            </div>
            <div class="info-item"><strong>المساحة المسجلة:</strong> <span>{{ $record->form_data['area_m2'] ?? '-' }}
                    متر مربع</span></div>
        </div>
        <div class="planning-details-wrapper" style="display: flex; flex-direction: column; gap: 15px; margin: 15px 0;">

            {{-- الاشتراطات البنائية العامة --}}
            <div class="rich-box" style="border-right: 4px solid #1e272e;">
                <strong style="color: #1e272e; text-decoration: underline;">الاشتراطات البنائية العامة:</strong>
                <div style="margin-top: 5px;">
                    {!! clean($record->urban_planning['building_requirements'] ?? 'لا توجد اشتراطات بنائية خاصة.') !!}
                </div>
            </div>

            {{-- اشتراطات المجلس الأعلى --}}
            <div class="rich-box" style="border: 2px solid #e1b12c; background: #fffdf5;">
                <strong style="color: #1e272e;"><i class="fas fa-gavel ms-1"></i> اشتراطات صادرة من المجلس الأعلى
                    للتخطيط
                    والتنمية العمرانية:</strong>
                <div style="margin-top: 5px; font-weight: bold;">
                    {!! clean($record->urban_planning['supreme_council_requirements'] ?? 'لا توجد اشتراطات المجلس الأعلى.') !!}
                </div>
            </div>

        </div>
        {{-- <div class="admin-notes-box mt-3" style="border: 1px solid #1e272e; padding: 10px; border-radius: 8px;">
            <strong>ملاحظات المراجعة الفنية الخاصة:</strong>
            <div style="font-size: 13px; margin-top: 5px;">{!! clean($record->admin_notes ?? 'لا توجد ملاحظات خاصة.') !!}</div>
        </div> --}}

        <!-- التوقيعات -->
        <div class="signatures">
            <div>عضو المركز الميداني<br><br>......................</div>
            <div>رئيس قسم التخطيط العمراني<br><br>......................</div>
            <div>يُعتمد،، مدير مركز المعلومات<br><br>......................</div>
        </div>

        <div
            style="margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px dashed #eee; padding-top: 10px;">
            هذا المستند استخراج رقمي معتمد من منظومة محافظة كفر الشيخ الجيومكانية - الرقم المرجعي للتحقق:
            {{ $record->id }}
        </div>
    </div>
</body>

</html>
