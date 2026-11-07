<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل قرار الإزالة رقم {{ $record->violation_report_number }}</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap");
        @page { size: A4; margin: 15mm; }
        * { box-sizing: border-box; }
        body {
            font-family: "Cairo", sans-serif;
            margin: 0; padding: 0;
            color: #1a1a1a; background: #f4f4f9;
            line-height: 1.7;
        }
        .page {
            max-width: 210mm; margin: 20px auto;
            background: #fff; padding: 30px 35px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08); border-radius: 6px;
        }
        .doc-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 3px solid #1a3a5c;
            padding-bottom: 18px; margin-bottom: 28px;
        }
        .doc-header .gov-info {
            text-align: center; font-size: 13px; font-weight: 600;
            line-height: 1.6; color: #1a3a5c;
        }
        .doc-header .gov-info .gov-title { font-size: 16px; font-weight: 700; }
        .doc-header .logo { height: 70px; }
        .title-box { text-align: center; margin: 10px 0 28px; }
        .title-box h1 {
            font-size: 22px; font-weight: 700;
            color: #1a3a5c; margin: 0 0 4px;
        }
        .title-box .sub-title { font-size: 14px; color: #555; }
        .section { margin-bottom: 24px; page-break-inside: avoid; }
        .section-header {
            background: #1a3a5c; color: #fff;
            padding: 6px 14px; font-size: 14px; font-weight: 700;
            border-radius: 4px; margin-bottom: 12px;
        }
        .section-header.green  { background: #1a7a4c; }
        .section-header.orange { background: #b45f1b; }
        .section-header.teal   { background: #0f6b6b; }
        .section-header.purple { background: #5a3e7a; }
        .section-header.brown  { background: #6b4423; }
        .section-header.red    { background: #8b2c24; }
        .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; }
        .data-grid .item {
            display: flex; border-bottom: 1px solid #e8e8e8; padding: 4px 0;
        }
        .data-grid .item .label {
            flex: 0 0 120px; font-weight: 600; font-size: 12px; color: #555;
        }
        .data-grid .item .value {
            flex: 1; font-size: 13px; color: #1a1a1a;
        }
        .data-grid .item.full { grid-column: 1 / -1; }
        .data-grid .item.full .label { flex: 0 0 140px; }
        .workflow-steps { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .workflow-step {
            display: flex; align-items: center; gap: 6px;
            background: #f0f4f8; border-radius: 20px;
            padding: 5px 14px; font-size: 12px; border: 1px solid #d0d8e0;
        }
        .workflow-step .step-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .workflow-step .step-dot.active  { background: #1a7a4c; box-shadow: 0 0 0 2px #b5d8c4; }
        .workflow-step .step-dot.done    { background: #1a3a5c; }
        .workflow-step .step-dot.pending { background: #c0c8d0; }
        .workflow-step .step-label { font-weight: 600; }
        .workflow-step .step-assignee { color: #666; margin-right: 4px; }
        .file-link {
            display: inline-block; background: #f0f4f8;
            padding: 2px 10px; border-radius: 4px; font-size: 12px;
            color: #1a3a5c; text-decoration: none; border: 1px solid #d0d8e0; margin: 2px;
        }
        .file-link.view-pdf { background: #e8f0e8; border-color: #a0c0a0; color: #1a6a3c; }
        .no-data { color: #999; font-size: 12px; }
        .no-print { text-align: center; margin-bottom: 16px; }
        .btn-print {
            padding: 10px 40px; background: #1a3a5c; color: #fff;
            border: none; cursor: pointer; font-weight: 700;
            border-radius: 6px; font-size: 14px;
        }
        .btn-print:hover { background: #14304d; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; padding: 0; box-shadow: none; border-radius: 0; }
            .no-print { display: none; }
            .section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button class="btn-print" onclick="window.print()">طباعة التفاصيل</button>
</div>
<div class="page">

<div class="doc-header">
    <div class="gov-info">
        <div class="gov-title">جمهورية مصر العربية</div>
        محافظة كفر الشيخ<br>
        مركز المعلومات والتحول الرقمي
    </div>
    <img src="{{ asset("logo.png") }}" class="logo" alt="Logo">
    <div class="gov-info">
        إدارة حوكمة العمران<br>
        قسم الإزالات والمخالفات
    </div>
</div>

<div class="title-box">
    <h1>تفاصيل قرار الإزالة</h1>
    <div class="sub-title">
        رقم المحضر {{ $record->violation_report_number }} لسنة {{ $record->created_at->format("Y") }}
        (@if($record->violation_type === "new_violation") بناء بدون ترخيص @else مخالفة شروط ترخيص @endif)
    </div>
</div>

<div class="section">
    <div class="section-header">بيانات المالك</div>
    <div class="data-grid">
        <div class="item"><span class="label">الاسم</span><span class="value">{{ $record->owner_name }}</span></div>
        <div class="item"><span class="label">الرقم القومي</span><span class="value">{{ $record->owner_national_id }}</span></div>
        <div class="item"><span class="label">المركز</span><span class="value">{{ $record->owner_center }}</span></div>
        <div class="item"><span class="label">الوحدة</span><span class="value">{{ $record->owner_unit }}</span></div>
        <div class="item"><span class="label">الشارع</span><span class="value">{{ $record->owner_street }}</span></div>
        <div class="item"><span class="label">الحي</span><span class="value">{{ $record->owner_district }}</span></div>
        <div class="item full"><span class="label">المحافظة</span><span class="value">{{ $record->owner_governorate }}</span></div>
    </div>
</div>

<div class="section">
    <div class="section-header green">موقع المخالفة</div>
    <div class="data-grid">
        <div class="item"><span class="label">المركز</span><span class="value">{{ $record->center }}</span></div>
        <div class="item"><span class="label">الوحدة المحلية</span><span class="value">{{ $record->local_unit }}</span></div>
        <div class="item"><span class="label">الشارع</span><span class="value">{{ $record->street }}</span></div>
        <div class="item"><span class="label">الحي</span><span class="value">{{ $record->district }}</span></div>
        <div class="item"><span class="label">رقم القطعة</span><span class="value">{{ $record->violation_plot }}</span></div>
        <div class="item"><span class="label">مساحة المخالفة</span><span class="value">{{ $record->violation_area }}</span></div>
    </div>
</div>

<div class="section">
    <div class="section-header orange">بيانات المخالفة</div>
    <div class="data-grid">
        <div class="item full"><span class="label">وصف الأعمال المخالفة</span><span class="value">{!! clean($record->violation_works) !!}</span></div>
        @if($record->license_number)
        <div class="item"><span class="label">رقم الترخيص</span><span class="value">{{ $record->license_number }}</span></div>
        <div class="item"><span class="label">تاريخ الترخيص</span><span class="value">{{ $record->license_date }}</span></div>
        <div class="item full"><span class="label">الأعمال المرخصة</span><span class="value">{{ $record->licensed_works }}</span></div>
        @endif
        <div class="item"><span class="label">التكلفة التقديرية</span><span class="value">{{ number_format($record->violation_cost, 2) }} ج.م</span></div>
        <div class="item"><span class="label">تاريخ الإعلان</span><span class="value">{{ $record->announcement_date ? $record->announcement_date->format("Y-m-d") : "—" }}</span></div>
    </div>
</div>

<div class="section">
    <div class="section-header teal">المهندس والمقاول</div>
    <div class="data-grid">
        <div class="item"><span class="label">المهندس المشرف</span><span class="value">{{ $record->engineer_name ?: "—" }}</span></div>
        <div class="item"><span class="label">رقمه القومي</span><span class="value">{{ $record->engineer_national_id ?: "—" }}</span></div>
        <div class="item"><span class="label">المقاول</span><span class="value">{{ $record->contractor_name ?: "—" }}</span></div>
        <div class="item"><span class="label">رقمه القومي</span><span class="value">{{ $record->contractor_national_id ?: "—" }}</span></div>
    </div>
</div>

<div class="section">
    <div class="section-header purple">المستندات الرسمية</div>
    <div class="data-grid">
        <div class="item"><span class="label">رقم أمر الإيقاف</span><span class="value">{{ $record->stop_order_number ?: "—" }}</span></div>
        <div class="item"><span class="label">تاريخه</span><span class="value">{{ $record->stop_order_date ? $record->stop_order_date->format("Y-m-d") : "—" }}</span></div>
        <div class="item"><span class="label">رقم محضر المخالفة</span><span class="value">{{ $record->violation_report_number ?: "—" }}</span></div>
        <div class="item"><span class="label">تاريخه</span><span class="value">{{ $record->report_date ? $record->report_date->format("Y-m-d") : "—" }}</span></div>
        <div class="item full">
            <span class="label">ملفات</span>
            <span class="value">
                @if($record->stop_order_file)<a href="{{ Storage::url($record->stop_order_file) }}" target="_blank" class="file-link view-pdf">أمر الإيقاف</a>@endif
                @if($record->violation_report_file)<a href="{{ Storage::url($record->violation_report_file) }}" target="_blank" class="file-link view-pdf">محضر المخالفة</a>@endif
                @if(!$record->stop_order_file && !$record->violation_report_file)<span class="no-data">لا توجد ملفات</span>@endif
            </span>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-header brown">الحدود والأبعاد (توصيف المهندس)</div>
    <div class="data-grid">
        @if($record->border_east || $record->dimension_east)
        <div class="item"><span class="label">الحد الشرقي</span><span class="value">{{ $record->border_east ?: "—" }} @if($record->dimension_east) بعد {{ $record->dimension_east }} م @endif</span></div>
        @endif
        @if($record->border_west || $record->dimension_west)
        <div class="item"><span class="label">الحد الغربي</span><span class="value">{{ $record->border_west ?: "—" }} @if($record->dimension_west) بعد {{ $record->dimension_west }} م @endif</span></div>
        @endif
        @if($record->border_north || $record->dimension_north)
        <div class="item"><span class="label">الحد الشمالي</span><span class="value">{{ $record->border_north ?: "—" }} @if($record->dimension_north) بعد {{ $record->dimension_north }} م @endif</span></div>
        @endif
        @if($record->border_south || $record->dimension_south)
        <div class="item"><span class="label">الحد الجنوبي</span><span class="value">{{ $record->border_south ?: "—" }} @if($record->dimension_south) بعد {{ $record->dimension_south }} م @endif</span></div>
        @endif
        @if($record->area)
        <div class="item"><span class="label">المساحة</span><span class="value">{{ number_format($record->area, 2) }} م²</span></div>
        @endif
        @if($record->engineer_description)
        <div class="item full"><span class="label">توصيف المهندس</span><span class="value">{{ $record->engineer_description }}</span></div>
        @endif
        @if($record->review_notes)
        <div class="item full"><span class="label">ملاحظات المراجعة</span><span class="value">{{ $record->review_notes }}</span></div>
        @endif
        @if(!$record->border_east && !$record->border_west && !$record->border_north && !$record->border_south && !$record->area)
        <div class="item full"><span class="label">ملاحظة</span><span class="value no-data">لم يتم إدخال بيانات الحدود والأبعاد بعد</span></div>
        @endif
    </div>
</div>

<div class="section">
    <div class="section-header red">البيانات المكانية (الكشف المساحي)</div>
    <div class="data-grid">
        @if($record->spatial_data && is_array($record->spatial_data))
            <div class="item"><span class="label">خط العرض</span><span class="value">{{ $record->spatial_data["latitude"] ?? "—" }}</span></div>
            <div class="item"><span class="label">خط الطول</span><span class="value">{{ $record->spatial_data["longitude"] ?? "—" }}</span></div>
            <div class="item"><span class="label">عدد النقاط</span><span class="value">
                @php
                    $geo = $record->spatial_data["polygon_geojson"] ?? null;
                    $c = 0;
                    if ($geo) {
                        $p = json_decode($geo, true);
                        if ($p && isset($p["geometry"]["coordinates"][0])) $c = count($p["geometry"]["coordinates"][0]);
                        elseif ($p && isset($p["features"][0]["geometry"]["coordinates"][0])) $c = count($p["features"][0]["geometry"]["coordinates"][0]);
                    }
                @endphp
                {{ $c > 0 ? $c . " نقطة" : "—" }}
            </span></div>
            <div class="item full"><span class="label">ملاحظات مكانية</span><span class="value">{{ $record->spatial_data["spatial_notes"] ?? "—" }}</span></div>
            <div class="item full">
                <span class="label">تحميل</span>
                <span class="value">
                    @if($geo)
                        <a href="{{ route('gis.removal.geojson', $record) }}" class="file-link" target="_blank">GeoJSON</a>
                        <a href="{{ route('gis.removal.shapefile', $record) }}" class="file-link view-pdf" target="_blank">Shapefile (.zip)</a>
                    @else
                        <span class="no-data">لا توجد بيانات مكانية</span>
                    @endif
                </span>
            </div>
        @else
            <div class="item full"><span class="label">ملاحظة</span><span class="value no-data">لم يتم إجراء الكشف المساحي بعد</span></div>
        @endif
    </div>

    @if($record->spatial_data && $geo)
    <div class="no-print" style="margin-top:14px;">
        <div style="font-weight:600;font-size:12px;color:#555;margin-bottom:6px;">الرسم علي الخريطة:</div>
        <div id="printMap" style="height:380px;border-radius:6px;border:1px solid #ddd;" class="no-print"></div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (function() {
                function loadMap() {
                    var container = document.getElementById('printMap');
                    if (!container || container._mapLoaded) return;
                    container._mapLoaded = true;
                    var geojsonStr = @json($geo);
                    var geojson;
                    try { geojson = JSON.parse(geojsonStr); } catch(e) { geojson = geojsonStr; }
                    if (!geojson) return;
                    if (geojson.type === 'FeatureCollection' && geojson.features[0]) geojson = geojson.features[0];
                    if (geojson.type === 'Feature') geojson = geojson.geometry;
                    if (!geojson || !geojson.coordinates) return;
                    var center = @json($record->spatial_data ? ($record->spatial_data['latitude'] ?? null) : null);
                    var lng = @json($record->spatial_data ? ($record->spatial_data['longitude'] ?? null) : null);
                    var map = L.map(container, { zoomControl: true, scrollWheelZoom: false });
                    if (center && lng) {
                        map.setView([center, lng], 18);
                    } else {
                        var coords = geojson.coordinates[0];
                        var latSum = 0, lngSum = 0;
                        for (var i = 0; i < coords.length; i++) { latSum += coords[i][1]; lngSum += coords[i][0]; }
                        map.setView([latSum/coords.length, lngSum/coords.length], 18);
                    }
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 }).addTo(map);
                    var layer = L.geoJSON(geojson, { style: { color: '#e74c3c', weight: 3, fillColor: '#e74c3c', fillOpacity: 0.15 } });
                    layer.addTo(map);
                    try { map.fitBounds(layer.getBounds().pad(0.15)); } catch(e) {}
                    setTimeout(function() { map.invalidateSize(); }, 400);
                }
                if (document.readyState === 'complete') { setTimeout(loadMap, 500); }
                else { window.addEventListener('load', function() { setTimeout(loadMap, 500); }); }
            })();
        </script>
    </div>
    @endif

    @if($record->photo_file)
    <div style="margin-top:10px;">
        <span style="font-weight:600;font-size:12px;color:#555;">صورة المبني:</span>
        <div style="margin-top:6px;">
            <img src="{{ Storage::url($record->photo_file) }}" style="max-width:100%;max-height:300px;border-radius:6px;border:1px solid #ddd;object-fit:cover;">
        </div>
    </div>
    @endif
</div>

@if($record->pdf_file || $record->visa_file)
<div class="section">
    <div class="section-header">ملفات القرار</div>
    <div class="data-grid">
        @if($record->pdf_file)
        <div class="item full"><span class="label">ملف القرار</span><span class="value"><a href="{{ Storage::url($record->pdf_file) }}" target="_blank" class="file-link view-pdf">عرض ملف PDF</a></span></div>
        @endif
        @if($record->visa_file)
        <div class="item full"><span class="label">التصديق النهائي</span><span class="value"><a href="{{ Storage::url($record->visa_file) }}" target="_blank" class="file-link view-pdf">عرض ملف PDF</a></span></div>
        @endif
    </div>
</div>
@endif

<div class="section">
    <div class="section-header purple">سير العمل</div>
    <div class="data-grid">
        <div class="item"><span class="label">المرحلة الحالية</span><span class="value">{{ Modules\RemovalGovernance\Models\RemovalOrder::stages()[$record->stage] ?? $record->stage }}</span></div>
        <div class="item"><span class="label">حالة النظام</span><span class="value">{{ $record->status ?: "—" }}</span></div>
        <div class="item"><span class="label">تاريخ الإنشاء</span><span class="value">{{ $record->created_at->format("Y-m-d H:i") }}</span></div>
        <div class="item"><span class="label">آخر تحديث</span><span class="value">{{ $record->updated_at->format("Y-m-d H:i") }}</span></div>
        <div class="item full"><span class="label">المنشئ</span><span class="value">{{ $record->creator->name ?? $record->created_by }}</span></div>
    </div>
    <div class="workflow-steps">
        @php
            $allStages = ["created"=>["label"=>"إنشاء المحضر",""],"engineering_review"=>["label"=>"المراجعة الهندسية",""],"spatial_pending"=>["label"=>"انتظار الكشف المساحي",""],"spatial_dimensioned"=>["label"=>"تم الكشف المساحي",""],"pdf_ready"=>["label"=>"انتظار اعتماد القرار",""],"systems_processing"=>["label"=>"التحليل المكاني",""],"local_unit_approval"=>["label"=>"اعتماد الوحدة المحلية",""],"gis_pending"=>["label"=>"التوجيه للاعتماد",""],"visa_pending"=>["label"=>"انتظار التصديق",""],"completed"=>["label"=>"تم التنفيذ",""]];
            $stageKeys = array_keys($allStages);
            $currentIndex = array_search($record->stage, $stageKeys);
        @endphp
        @foreach($allStages as $key => $info)
            @php
                $idx = array_search($key, $stageKeys);
                $class = $idx < $currentIndex ? "done" : ($idx === $currentIndex ? "active" : "pending");
                $assignee = "";
                if ($key === "created" && $record->creator) $assignee = $record->creator->name;
                if ($key === "engineering_review" && $record->engineeringEngineer) $assignee = $record->engineeringEngineer->name;
                if ($key === "spatial_pending" && $record->spatialMember) $assignee = $record->spatialMember->name;
                if ($key === "spatial_dimensioned" && $record->spatialMember) $assignee = $record->spatialMember->name;
                if ($key === "pdf_ready" && $record->systemsSpecialist) $assignee = $record->systemsSpecialist->name;
            @endphp
            <div class="workflow-step">
                <span class="step-dot {{ $class }}"></span>
                <span class="step-label">{{ $info["label"] }}</span>
                @if($assignee)<span class="step-assignee">({{ $assignee }})</span>@endif
            </div>
        @endforeach
    </div>
</div>

<div style="margin-top:30px;padding-top:12px;border-top:1px solid #ddd;font-size:11px;color:#888;text-align:center;">
    تم استخراج التقرير آلياً عبر نظام حوكمة الإزالات الرقمي &mdash; Ticket ID: {{ $record->id }}
</div>
</div>
</body>
</html>
