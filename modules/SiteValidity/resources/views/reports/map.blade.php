<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>تقرير طلبات صلاحية الموقع — {{ now()->format('Y-m-d') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=Tajawal:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #ECEAE2; color: #1D2422; font: 12px/1.6 "IBM Plex Sans Arabic", Tahoma, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sheet { max-width: 1120px; margin: 16px auto; background: #fff; padding: 22px 26px; box-shadow: 0 6px 24px rgba(0,0,0,.08); }
        .toolbar { max-width: 1120px; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-start; }
        .toolbar button { font: 700 13px "Tajawal", sans-serif; padding: 9px 18px; border-radius: 8px; border: 0; cursor: pointer; background: #00788A; color: #fff; }
        .toolbar button.ghost { background: #fff; color: #1D2422; border: 1px solid #d4d4d8; }
        header.top { display: flex; align-items: center; gap: 14px; border-bottom: 3px solid #D8B03E; padding-bottom: 10px; margin-bottom: 12px; }
        header.top img { height: 54px; }
        header.top h1 { margin: 0; font: 800 20px/1.3 "Tajawal", sans-serif; }
        header.top p { margin: 0; color: #68645A; }
        header.top .meta { margin-inline-start: auto; text-align: end; color: #68645A; font-size: 11px; }
        .filters { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .filters span { background: #F4F2EA; border-radius: 999px; padding: 2px 10px; font-weight: 600; font-size: 11px; }
        .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 8px; margin-bottom: 12px; }
        .kpi { border: 1px solid #e4e4e7; border-inline-start: 4px solid var(--c, #26302D); border-radius: 8px; padding: 6px 10px; }
        .kpi b { display: block; font: 800 18px/1.2 "Tajawal", sans-serif; color: var(--c, #26302D); }
        .kpi span { font-size: 10.5px; color: #52525b; font-weight: 600; }
        .row2 { display: grid; grid-template-columns: 1.25fr 1fr; gap: 14px; align-items: start; }
        #map { height: 430px; border: 1px solid #e4e4e7; border-radius: 8px; }
        h2 { font: 800 14px/1.3 "Tajawal", sans-serif; margin: 14px 0 6px; padding-inline-start: 8px; border-inline-start: 3px solid #D8B03E; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #26302D; color: #fff; font-weight: 700; padding: 5px 6px; text-align: start; }
        td { padding: 4px 6px; border-bottom: 1px solid #ececec; vertical-align: top; }
        tbody tr:nth-child(even) td { background: #FAF9F4; }
        td.n, th.n { text-align: center; font-variant-numeric: tabular-nums; }
        tfoot td { font-weight: 800; background: #F4F2EA; }
        .st { display: inline-block; padding: 0 7px; border-radius: 4px; color: #fff; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .bar { display: flex; height: 7px; border-radius: 99px; overflow: hidden; background: #f4f4f5; min-width: 80px; }
        .bar i { display: block; }
        .note { color: #71717a; font-size: 10.5px; margin-top: 4px; }
        .label { background: transparent; border: 0; }
        .label span { display: inline-flex; gap: 4px; transform: translate(-50%, -50%); white-space: nowrap; padding: 1px 7px; border-radius: 999px; background: #fff;
            font: 700 10px/1.4 "IBM Plex Sans Arabic", sans-serif; box-shadow: 0 1px 4px rgba(0,0,0,.25); }
        .label span b { background: #00788A; color: #fff; border-radius: 99px; padding: 0 5px; }
        .label.sel span { background: #26302D; color: #fff; } .label.sel span b { background: #D8B03E; color: #1D2422; }
        footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e4e4e7; display: flex; justify-content: space-between; color: #71717a; font-size: 10px; }
        .page-break { break-before: page; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; padding: 0; box-shadow: none; max-width: none; }
            .toolbar { display: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
            .leaflet-control-zoom { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">طباعة / حفظ PDF</button>
        <button type="button" class="ghost" onclick="window.close()">إغلاق</button>
    </div>

    <main class="sheet">
        <header class="top">
            <img src="{{ asset('logo.png') }}" alt="">
            <div>
                <h1>تقرير طلبات صلاحية الموقع</h1>
                <p>محافظة كفر الشيخ — منظومة طلب صلاحية الموقع</p>
            </div>
            <div class="meta">
                تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}<br>
                أعدّه: {{ $generatedBy }}
            </div>
        </header>

        <div class="filters">
            <span>الفلاتر:</span>
            @forelse ($filters as $f)
                <span>{{ $f }}</span>
            @empty
                <span>كل الطلبات (بدون فلاتر)</span>
            @endforelse
        </div>

        <div class="kpis">
            <div class="kpi"><b>{{ number_format($total) }}</b><span>إجمالي الطلبات</span></div>
            <div class="kpi" style="--c:#3D7A50"><b>{{ number_format($paid) }}</b><span>طلبات مدفوعة</span></div>
            <div class="kpi" style="--c:#00788A"><b>{{ number_format($revenue, 2) }}</b><span>الإيراد المحصل (ج.م)</span></div>
            @foreach ($statusShort as $key => $label)
                @if (! empty($statuses[$key]))
                    <div class="kpi" style="--c: {{ $statusHex[$key] }}"><b>{{ $statuses[$key] }}</b><span>{{ $label }}</span></div>
                @endif
            @endforeach
        </div>

        <div class="row2">
            <div>
                <div id="map"></div>
                <p class="note">كثافة اللون = عدد الطلبات، والنقاط = مواقع الطلبات المحددة على الخريطة ({{ $points->count() }} طلب{{ $unlocated ? '، و' . $unlocated . ' بدون موقع محدد' : '' }}). الحدود تقريبية (geoBoundaries — CC BY 4.0)؛ بلطيم ومصيف بلطيم وسيدي غازي ومسير تظهر كنقاط مراكز.</p>
            </div>
            <div>
                <h2 style="margin-top:0">التوزيع حسب المركز</h2>
                @php $max = max(1, $centres->max('total') ?? 1); @endphp
                <table>
                    <thead><tr><th>المركز</th><th class="n">الطلبات</th><th class="n">مدفوع</th><th>توزيع الحالات</th></tr></thead>
                    <tbody>
                        @forelse ($centres as $c)
                            <tr>
                                <td>{{ $c['name'] }}</td>
                                <td class="n">{{ $c['total'] }}</td>
                                <td class="n">{{ $c['paid'] }}</td>
                                <td>
                                    <div class="bar" style="width: {{ max(12, $c['total'] / $max * 100) }}%">
                                        @foreach ($statusHex as $s => $hex)
                                            @if (! empty($c['statuses'][$s]))<i style="width: {{ $c['statuses'][$s] / $c['total'] * 100 }}%; background: {{ $hex }}"></i>@endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">لا توجد طلبات مطابقة.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($centres->isNotEmpty())
                        <tfoot><tr><td>الإجمالي</td><td class="n">{{ $total }}</td><td class="n">{{ $paid }}</td><td></td></tr></tfoot>
                    @endif
                </table>
                <p class="note">
                    @foreach ($statusShort as $s => $label)
                        @if (! empty($statuses[$s]))<span class="st" style="background: {{ $statusHex[$s] }}">{{ $label }}</span> @endif
                    @endforeach
                </p>
            </div>
        </div>

        <h2 class="page-break">قائمة الطلبات ({{ number_format($rows->count()) }}{{ $truncated ? ' من ' . number_format($total) : '' }})</h2>
        @if ($truncated)
            <p class="note">يعرض التقرير أحدث {{ number_format($maxRows) }} طلب فقط — ضيّق الفلاتر لعرض الباقي.</p>
        @endif
        <table>
            <thead>
                <tr><th>#</th><th>رقم الطلب</th><th>مقدم الطلب</th><th>الرقم القومي</th><th>الخدمة</th><th>المركز / القرية</th><th>الحالة</th><th class="n">الرسوم</th><th class="n">مدفوع</th><th>التقديم</th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td class="n">{{ $loop->iteration }}</td>
                        <td>{{ $r->number }}</td>
                        <td>{{ $r->applicant_name }}</td>
                        <td dir="ltr" style="text-align:right">{{ $r->national_id }}</td>
                        <td>{{ $r->serviceType?->name }}</td>
                        <td>{{ $r->locationLabel() }}</td>
                        <td><span class="st" style="background: {{ $statusHex[$r->status] ?? '#78756A' }}">{{ $statusShort[$r->status] ?? $r->status }}</span></td>
                        <td class="n">{{ number_format((float) $r->fee, 2) }}</td>
                        <td class="n">{{ $r->paid_at ? '✓' : '—' }}</td>
                        <td>{{ $r->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10">لا توجد طلبات مطابقة.</td></tr>
                @endforelse
            </tbody>
        </table>

        <footer>
            <span>محافظة كفر الشيخ — تقرير داخلي: يحتوي بيانات شخصية، يُتداول للاستخدام الرسمي فقط.</span>
            <span>{{ url()->current() }}</span>
        </footer>
    </main>

    <script>
        (() => {
            const cfg = @js($mapConfig), data = @js($centres->keyBy('name')), ids = @js($centreIds), selected = @js($selected);
            const max = Math.max(1, ...Object.values(data).map(c => c.total));
            const map = L.map('map', { zoomControl: true, attributionControl: true }).setView(cfg.center, cfg.zoom);
            const tiles = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
                { maxZoom: 16, attribution: 'Tiles &copy; Esri' }).addTo(map);
            const label = (name, ll) => {
                const t = (data[name] || {}).total || 0, sel = selected && ids[name] === selected;
                L.marker(ll, { icon: L.divIcon({ className: 'label' + (sel ? ' sel' : ''), html: `<span>${name} <b>${t}</b></span>`, iconSize: [0, 0] }) }).addTo(map);
            };
            fetch(cfg.boundaries).then(r => r.json()).then(geo => {
                const groups = {};
                const layer = L.geoJSON(geo, {
                    style: f => { const t = (data[f.properties.centre] || {}).total || 0, sel = selected && ids[f.properties.centre] === selected;
                        return { weight: sel ? 3 : 1.2, color: sel ? '#D8B03E' : '#00788A', fillColor: '#00788A', fillOpacity: t ? .12 + t / max * .55 : .04 }; },
                    onEachFeature: (f, l) => (groups[f.properties.centre] ||= []).push(l),
                }).addTo(map);
                Object.entries(groups).forEach(([name, ls]) => {
                    const big = ls.reduce((a, b) => { const A = a.getBounds(), B = b.getBounds();
                        return (B.getNorth() - B.getSouth()) * (B.getEast() - B.getWest()) > (A.getNorth() - A.getSouth()) * (A.getEast() - A.getWest()) ? b : a; });
                    label(name, big.getBounds().getCenter());
                });
                Object.entries(cfg.points).forEach(([name, ll]) => {
                    const t = (data[name] || {}).total || 0;
                    L.circleMarker(ll, { radius: 7 + t / max * 9, weight: 2, color: '#00788A', fillColor: '#00788A', fillOpacity: .4 }).addTo(map);
                    label(name, L.latLng(ll[0] + .025, ll[1]));
                });
                // نقاط الطلبات المحددة على الخريطة (ملونة حسب الحالة)
                (@js($points)).forEach(([lat, lng, st, no]) => L.circleMarker([lat, lng], { radius: 4.5, weight: 1.5, color: '#fff', fillColor: (@js($statusHex))[st] || '#78756A', fillOpacity: 1 }).bindTooltip(no).addTo(map));
                map.fitBounds(layer.getBounds().pad(.02));
            });
            // اطبع تلقائياً بعد تحميل الخريطة (مرة واحدة)
            let printed = false;
            tiles.on('load', () => { if (! printed && new URLSearchParams(location.search).has('autoprint')) { printed = true; setTimeout(() => window.print(), 400); } });
        })();
    </script>
</body>
</html>
