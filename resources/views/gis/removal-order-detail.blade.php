<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل قرار الإزالة #{{ $record->violation_report_number ?: $record->id }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', 'Segoe UI', sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
            line-height: 1.7;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .08);
            padding: 24px 28px;
            margin-bottom: 20px;
        }

        .card-header {
            font-size: 15px;
            font-weight: 700;
            color: #1a3a5c;
            padding-bottom: 10px;
            margin-bottom: 16px;
            border-bottom: 2px solid #e8ecf1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header .badge {
            font-size: 11px;
            background: #1a3a5c;
            color: #fff;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 24px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px 24px;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 6px 24px;
        }

        .field {
            display: flex;
            flex-direction: column;
            border-bottom: 1px solid #f0f0f0;
            padding: 4px 0;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field .label {
            font-size: 11px;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .field .value {
            font-size: 14px;
            color: #1a1a2e;
        }

        .field .value.empty {
            color: #bbb;
            font-style: italic;
        }

        .stage-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .stage-badge.created {
            background: #e8e8e8;
            color: #555;
        }

        .stage-badge.engineering_review {
            background: #fff3cd;
            color: #856404;
        }

        .stage-badge.spatial_pending {
            background: #cce5ff;
            color: #004085;
        }

        .stage-badge.spatial_dimensioned {
            background: #d4edda;
            color: #155724;
        }

        .stage-badge.pdf_ready {
            background: #f8d7da;
            color: #721c24;
        }

        .stage-badge.visa_pending {
            background: #e2d5f0;
            color: #4a1a6b;
        }

        .stage-badge.completed {
            background: #1a7a4c;
            color: #fff;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding: 8px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            right: 19px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dce0e6;
        }

        .tl-item {
            position: relative;
            padding-right: 52px;
            padding-bottom: 22px;
        }

        .tl-item:last-child {
            padding-bottom: 0;
        }

        .tl-dot {
            position: absolute;
            right: 11px;
            top: 4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 3px solid #dce0e6;
            background: #fff;
        }

        .tl-dot.done {
            border-color: #1a7a4c;
            background: #1a7a4c;
        }

        .tl-dot.active {
            border-color: #1a3a5c;
            background: #1a3a5c;
            box-shadow: 0 0 0 3px rgba(26, 58, 92, .15);
        }

        .tl-dot.pending {
            border-color: #c0c8d0;
            background: #f0f2f5;
        }

        .tl-content {}

        .tl-title {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .tl-meta {
            font-size: 12px;
            color: #888;
            margin-top: 1px;
        }

        .tl-meta .person {
            color: #1a3a5c;
            font-weight: 600;
        }

        /* Files */
        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f0f4f8;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            color: #1a3a5c;
            text-decoration: none;
            border: 1px solid #d0d8e0;
            margin: 3px;
            transition: all .15s;
        }

        .file-link:hover {
            background: #e0e8f0;
        }

        .file-link.pdf {
            background: #e8f0e8;
            border-color: #a0c0a0;
            color: #1a6a3c;
        }

        .file-link.pdf:hover {
            background: #d8e8d8;
        }

        .file-link.dl {
            background: #1a3a5c;
            color: #fff;
            border-color: #1a3a5c;
        }

        .file-link.dl:hover {
            background: #14304d;
        }

        .actions-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }

        .btn-primary {
            background: #1a3a5c;
            color: #fff;
        }

        .btn-primary:hover {
            background: #14304d;
        }

        .btn-success {
            background: #1a7a4c;
            color: #fff;
        }

        .btn-success:hover {
            background: #15653f;
        }

        .btn-outline {
            background: transparent;
            color: #1a3a5c;
            border: 1px solid #1a3a5c;
        }

        .btn-outline:hover {
            background: #f0f4f8;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .header-bar h1 {
            font-size: 22px;
            color: #1a3a5c;
        }

        .header-bar .sub {
            font-size: 13px;
            color: #888;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                font-size: 12px;
            }
            .container {
                max-width: 100%;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 12px 16px;
                margin-bottom: 10px;
                page-break-inside: avoid;
            }
            .card-header {
                font-size: 13px;
                padding-bottom: 6px;
                margin-bottom: 10px;
            }
            .field .label {
                font-size: 10px;
            }
            .field .value {
                font-size: 12px;
            }
            .header-bar h1 {
                font-size: 18px;
            }
            .actions-bar,
            .btn,
            .btn-outline,
            .btn-primary,
            iframe {
                display: none !important;
            }
            .grid-2,
            .grid-3,
            .grid-4 {
                gap: 4px 16px;
            }
        }

        @media (max-width: 768px) {

            .grid-2,
            .grid-3,
            .grid-4 {
                grid-template-columns: 1fr;
            }

            .header-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header-bar">
            <div>
                <h1>تفاصيل قرار الإزالة</h1>
                <div class="sub">
                    @if ($record->violation_report_number)
                        محضر رقم {{ $record->violation_report_number }}
                    @endif
                    @if ($record->center)
                        — {{ $record->center }}
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="javascript:window.print()" class="btn btn-primary">🖨️ طباعة</a>
                <a href="{{ url('/gis') }}" class="btn btn-outline">⬅ العودة</a>
            </div>
        </div>

        {{-- ─── سير العمل ─── --}}
        <div class="card">
            <div class="card-header">
                📋 مسار القرار
                <span class="stage-badge {{ $record->stage }}">
                    {{ \Modules\RemovalGovernance\Models\RemovalOrder::stages()[$record->stage] ?? $record->stage }}
                </span>
            </div>
            <div class="grid-2" style="margin-bottom:16px;">
                <div class="field"><span class="label">تاريخ الإنشاء</span><span
                        class="value">{{ $record->created_at->format('Y-m-d H:i') }}</span></div>
                <div class="field"><span class="label">آخر تحديث</span><span
                        class="value">{{ $record->updated_at->format('Y-m-d H:i') }}</span></div>
                <div class="field"><span class="label">الحالة</span><span
                        class="value">{{ $record->status ?: '—' }}</span></div>
            </div>
            <div class="timeline">
                @php
                    $stages = [
                        'created' => ['label' => 'إنشاء المحضر', 'user' => $record->creator],
                        'engineering_review' => [
                            'label' => 'المراجعة الهندسية',
                            'user' => $record->engineeringEngineer,
                        ],
                        'spatial_pending' => ['label' => 'بإنتظار الكشف المساحي', 'user' => $record->spatialManager],
                        'spatial_dimensioned' => ['label' => 'تم الكشف المساحي', 'user' => $record->spatialMember],
                        'pdf_ready' => ['label' => 'بإنتظار اعتماد القرار', 'user' => $record->systemsSpecialist],
                        'visa_pending' => ['label' => 'بإنتظار التصديق النهائي', 'user' => null],
                        'completed' => ['label' => 'تم تنفيذ القرار', 'user' => null],
                    ];
                    $currentIdx = array_search($record->stage, array_keys($stages));
                @endphp
                @foreach ($stages as $key => $info)
                    @php
                        $idx = array_search($key, array_keys($stages));
                        $status = $idx < $currentIdx ? 'done' : ($idx === $currentIdx ? 'active' : 'pending');
                    @endphp
                    <div class="tl-item">
                        <div class="tl-dot {{ $status }}"></div>
                        <div class="tl-content">
                            <div class="tl-title">{{ $info['label'] }}</div>
                            <div class="tl-meta">
                                @if ($info['user'])
                                    بواسطة: <span class="person">{{ $info['user']->name }}</span>
                                @elseif($key === 'visa_pending' && $record->stage === 'visa_pending')
                                    ← تم الاعتماد
                                @elseif($key === 'completed' && $record->stage === 'completed')
                                    تم التصديق النهائي
                                @elseif($status === 'pending')
                                    —
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ─── بيانات المالك ─── --}}
        <div class="card">
            <div class="card-header">👤 بيانات المالك</div>
            <div class="grid-2">
                <div class="field"><span class="label">الاسم</span><span
                        class="value">{{ $record->owner_name ?: '—' }}</span></div>
                <div class="field"><span class="label">الرقم القومي</span><span
                        class="value">{{ $record->owner_national_id ?: '—' }}</span></div>
                <div class="field"><span class="label">المركز</span><span
                        class="value">{{ $record->owner_center ?: '—' }}</span></div>
                <div class="field"><span class="label">الوحدة</span><span
                        class="value">{{ $record->owner_unit ?: '—' }}</span></div>
                <div class="field"><span class="label">الشارع</span><span
                        class="value">{{ $record->owner_street ?: '—' }}</span></div>
                <div class="field"><span class="label">الحي</span><span
                        class="value">{{ $record->owner_district ?: '—' }}</span></div>
                <div class="field full"><span class="label">المحافظة</span><span
                        class="value">{{ $record->owner_governorate ?: '—' }}</span></div>
            </div>
        </div>

        {{-- ─── موقع المخالفة ─── --}}
        <div class="card">
            <div class="card-header">📍 موقع المخالفة</div>
            <div class="grid-2">
                <div class="field"><span class="label">المركز</span><span
                        class="value">{{ $record->center ?: '—' }}</span></div>
                <div class="field"><span class="label">الوحدة المحلية</span><span
                        class="value">{{ $record->local_unit ?: '—' }}</span></div>
                <div class="field"><span class="label">الشارع</span><span
                        class="value">{{ $record->street ?: '—' }}</span></div>
                <div class="field"><span class="label">الحي</span><span
                        class="value">{{ $record->district ?: '—' }}</span></div>
                <div class="field"><span class="label">رقم القطعة</span><span
                        class="value">{{ $record->violation_plot ?: '—' }}</span></div>
                <div class="field"><span class="label">مساحة المخالفة</span><span
                        class="value">{{ $record->violation_area ?: '—' }}</span></div>
            </div>
        </div>

        {{-- ─── بيانات المخالفة ─── --}}
        <div class="card">
            <div class="card-header">⚠️ بيانات المخالفة</div>
            <div class="grid-2">
                <div class="field full"><span class="label">نوع المخالفة</span><span
                        class="value">{{ $record->violation_type === 'new_violation' ? 'بناء بدون ترخيص' : 'مخالفة شروط ترخيص' }}</span>
                </div>
                @if ($record->license_number)
                    <div class="field"><span class="label">رقم الترخيص</span><span
                            class="value">{{ $record->license_number }}</span></div>
                    <div class="field"><span class="label">تاريخ الترخيص</span><span
                            class="value">{{ $record->license_date ? $record->license_date->format('Y-m-d') : '—' }}</span>
                    </div>
                    <div class="field full"><span class="label">الأعمال المرخصة</span><span
                            class="value">{{ $record->licensed_works ?: '—' }}</span></div>
                @endif
                <div class="field full"><span class="label">وصف الأعمال المخالفة</span><span
                        class="value">{{ strip_tags(html_entity_decode($record->violation_works ?: '—')) }}</span></div>
                <div class="field"><span class="label">التكلفة التقديرية</span><span
                        class="value">{{ $record->violation_cost ? number_format($record->violation_cost, 2) . ' ج.م' : '—' }}</span>
                </div>
                <div class="field"><span class="label">تاريخ الإعلان</span><span
                        class="value">{{ $record->announcement_date ? $record->announcement_date->format('Y-m-d') : '—' }}</span>
                </div>
            </div>
        </div>

        {{-- ─── المهندس والمقاول ─── --}}
        <div class="card">
            <div class="card-header">👷 المهندس والمقاول</div>
            <div class="grid-2">
                <div class="field"><span class="label">المهندس المشرف</span><span
                        class="value">{{ $record->engineer_name ?: '—' }}</span></div>
                <div class="field"><span class="label">رقمه القومي</span><span
                        class="value">{{ $record->engineer_national_id ?: '—' }}</span></div>
                <div class="field"><span class="label">المقاول</span><span
                        class="value">{{ $record->contractor_name ?: '—' }}</span></div>
                <div class="field"><span class="label">رقمه القومي</span><span
                        class="value">{{ $record->contractor_national_id ?: '—' }}</span></div>
            </div>
        </div>

        {{-- ─── المستندات الرسمية ─── --}}
        <div class="card">
            <div class="card-header">📄 المستندات الرسمية</div>
            <div class="grid-2">
                <div class="field"><span class="label">رقم أمر الإيقاف</span><span
                        class="value">{{ $record->stop_order_number ?: '—' }}</span></div>
                <div class="field"><span class="label">تاريخه</span><span
                        class="value">{{ $record->stop_order_date ? $record->stop_order_date->format('Y-m-d') : '—' }}</span>
                </div>
                <div class="field"><span class="label">رقم محضر المخالفة</span><span
                        class="value">{{ $record->violation_report_number ?: '—' }}</span></div>
                <div class="field"><span class="label">تاريخه</span><span
                        class="value">{{ $record->report_date ? $record->report_date->format('Y-m-d') : '—' }}</span>
                </div>
                <div class="field full">
                    <span class="label">الملفات</span>
                    <span class="value">
                        @if ($record->stop_order_file)
                            <a href="{{ Storage::url($record->stop_order_file) }}" target="_blank"
                                class="file-link pdf">📎 أمر الإيقاف</a>
                        @endif
                        @if ($record->violation_report_file)
                            <a href="{{ Storage::url($record->violation_report_file) }}" target="_blank"
                                class="file-link pdf">📎 محضر المخالفة</a>
                        @endif
                        @if (!$record->stop_order_file && !$record->violation_report_file)
                            <span class="value empty">لا توجد ملفات</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- ─── الحدود والأبعاد ─── --}}
        <div class="card">
            <div class="card-header">📐 الحدود والأبعاد</div>
            <div class="grid-2">
                @if ($record->border_east || $record->dimension_east)
                    <div class="field"><span class="label">الحد الشرقي</span><span
                            class="value">{{ $record->border_east ?: '—' }} @if ($record->dimension_east)
                                ← بعد {{ $record->dimension_east }} م
                            @endif
                        </span>
                    </div>
                @endif
                @if ($record->border_west || $record->dimension_west)
                    <div class="field"><span class="label">الحد الغربي</span><span
                            class="value">{{ $record->border_west ?: '—' }} @if ($record->dimension_west)
                                ← بعد {{ $record->dimension_west }} م
                            @endif
                        </span>
                    </div>
                @endif
                @if ($record->border_north || $record->dimension_north)
                    <div class="field"><span class="label">الحد الشمالي</span><span
                            class="value">{{ $record->border_north ?: '—' }} @if ($record->dimension_north)
                                ← بعد {{ $record->dimension_north }} م
                            @endif
                        </span>
                    </div>
                @endif
                @if ($record->border_south || $record->dimension_south)
                    <div class="field"><span class="label">الحد الجنوبي</span><span
                            class="value">{{ $record->border_south ?: '—' }} @if ($record->dimension_south)
                                ← بعد {{ $record->dimension_south }} م
                            @endif
                        </span></div>
                @endif
                <div class="field"><span class="label">المساحة</span><span
                        class="value">{{ $record->area ? number_format($record->area, 2) . ' م²' : '—' }}</span>
                </div>
                <div class="field full"><span class="label">توصيف المهندس</span><span
                        class="value">{{ $record->engineer_description ?: '—' }}</span></div>
                @if ($record->review_notes)
                    <div class="field full"><span class="label">ملاحظات المراجعة</span><span
                            class="value">{{ $record->review_notes }}</span></div>
                @endif
            </div>
        </div>

        {{-- GIS Portal iframe --}}
        <div class="card">
            <div class="card-header">🌐 نظام المعلومات المكانية - البوابة الإلكترونية</div>
            <iframe
                src="https://portal.kfs.gov.eg/portal/apps/webappviewer/index.html?id=43014c258c0449c6b09e747d1368f27d"
                width="100%" height="750px" style="border:none; border-radius:8px;"></iframe>
        </div>

        {{-- ─── ملفات القرار ─── --}}
        @if ($record->pdf_file || $record->visa_file)
            <div class="card">
                <div class="card-header">📁 ملفات القرار</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @if ($record->pdf_file)
                        <a href="{{ Storage::url($record->pdf_file) }}" target="_blank" class="file-link pdf">📄 ملف
                            القرار</a>
                    @endif
                    @if ($record->visa_file)
                        <a href="{{ Storage::url($record->visa_file) }}" target="_blank" class="file-link pdf">📄
                            التصديق النهائي</a>
                    @endif
                </div>
            </div>
        @endif

    </div>

</body>

</html>
