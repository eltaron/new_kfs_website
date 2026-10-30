<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $certificate->kind_label }} {{ $certificate->number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600&family=Tajawal:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --gold:#D8B03E; --gold-dark:#B88920; --cyan-deep:#008FA5; --ivory:#FFFDE1; --charcoal:#282724; --text:#393733; --muted:#68645A; --danger:#A63A32; --success:#3D7A50; --line:rgba(40,39,36,.15); }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eceae0; color: var(--text); font-family: 'IBM Plex Sans Arabic', 'Tajawal', sans-serif; }
        .sheet { width: 210mm; min-height: 297mm; margin: 12px auto; background: #fff; padding: 16mm 16mm 12mm; position: relative; border-top: 8px solid {{ $certificate->kind === 'cancellation' ? 'var(--danger)' : 'var(--cyan-deep)' }}; }
        .head { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--gold); padding-bottom: 10px; }
        .head h1 { font-family: 'Tajawal'; font-size: 22px; margin: 0; color: var(--charcoal); }
        .head small { color: var(--muted); }
        .title { text-align: center; margin: 18px 0 8px; font-family: 'Tajawal'; font-weight: 800; font-size: 26px; color: {{ $certificate->kind === 'cancellation' ? 'var(--danger)' : 'var(--charcoal)' }}; }
        .num { text-align: center; direction: ltr; font-weight: 600; letter-spacing: 1px; color: var(--gold-dark); margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 14px; }
        th { background: var(--ivory); width: 32%; text-align: right; font-weight: 600; }
        th, td { border: 1px solid var(--line); padding: 7px 10px; vertical-align: top; }
        .section { font-family: 'Tajawal'; font-weight: 700; margin: 14px 0 6px; color: var(--cyan-deep); }
        .alert { border: 2px solid var(--danger); color: var(--danger); padding: 10px 14px; border-radius: 8px; font-weight: 700; margin: 12px 0; }
        .foot { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 18px; }
        .qr { text-align: center; font-size: 11px; color: var(--muted); }
        .qr svg { width: 120px; height: 120px; }
        .sign { text-align: center; min-width: 200px; }
        .sign div { border-top: 1px dashed var(--muted); margin-top: 48px; padding-top: 4px; }
        .toolbar { text-align: center; margin: 12px; }
        .toolbar button { background: var(--cyan-deep); color: #fff; border: 0; border-radius: 10px; padding: 10px 22px; font-family: inherit; font-size: 15px; cursor: pointer; }
        .stamp { position: absolute; top: 45%; left: 50%; transform: translate(-50%,-50%) rotate(-18deg); font-size: 64px; font-weight: 800; color: rgba(166,58,50,.12); white-space: nowrap; pointer-events: none; }
        @media print { body { background: #fff; } .sheet { margin: 0; width: auto; min-height: auto; } .toolbar { display: none; } @page { size: A4; margin: 0; } }
    </style>
</head>
<body>
<div class="toolbar"><button type="button" onclick="window.print()">طباعة / حفظ PDF</button></div>
<div class="sheet">
    @if($certificate->kind === 'supervision' && $certificate->status !== 'active')
        <div class="stamp">{{ $certificate->status_label }}</div>
    @endif
    <div class="head">
        <div>
            <h1>نقابة المهندسين — فرعية كفر الشيخ</h1>
            <small>بالتنسيق مع محافظة كفر الشيخ — مجلس مدينة {{ $certificate->city }}</small>
        </div>
        <img src="{{ asset('logo.png') }}" alt="شعار محافظة كفر الشيخ" style="height:64px">
    </div>

    <div class="title">{{ $certificate->kind_label }}</div>
    <div class="num">{{ $certificate->number }}</div>

    @if($certificate->kind === 'cancellation')
        <div class="alert">بموجب هذه الشهادة يُلغى إشراف المهندس الموضح أدناه على الموقع، ويجب إيقاف جميع الأعمال لحين صدور شهادة إشراف جديدة.</div>
    @endif

    <div class="section">بيانات المهندس / المكتب الهندسي</div>
    <table>
        <tr><th>اسم المهندس</th><td>{{ $certificate->engineer_name }}</td></tr>
        <tr><th>رقم عضوية النقابة</th><td>{{ $certificate->engineer_syndicate_number }}</td></tr>
        <tr><th>الشعبة / التخصص</th><td>{{ $certificate->engineer_department ?? '-' }}</td></tr>
        @if($certificate->office_name)
            <tr><th>المكتب الهندسي</th><td>{{ $certificate->office_name }} @if($certificate->office_registration_number) — قيد رقم {{ $certificate->office_registration_number }} @endif</td></tr>
        @endif
    </table>

    <div class="section">بيانات المالك والموقع</div>
    <table>
        <tr><th>اسم المالك</th><td>{{ $certificate->owner_name }}</td></tr>
        <tr><th>الرقم القومي</th><td>{{ $certificate->owner_national_id }}</td></tr>
        <tr><th>عنوان الموقع</th><td>{{ $certificate->project_address }}</td></tr>
        <tr><th>المركز / المحافظة</th><td>{{ $certificate->city }} — {{ $certificate->governorate }}</td></tr>
        <tr><th>رقم الترخيص وتاريخه</th><td>{{ $certificate->license_number ?? '-' }} {{ $certificate->license_date ? '— ' . $certificate->license_date->format('Y-m-d') : '' }}</td></tr>
        @if($certificate->type)
            <tr><th>نوع الأعمال</th><td>{{ $certificate->type_label }} {{ $certificate->sub_type_label ? '(' . $certificate->sub_type_label . ')' : '' }}</td></tr>
        @endif
        @if($certificate->project)
            <tr><th>كود المشروع</th><td dir="ltr" style="text-align:right">{{ $certificate->project->code }}</td></tr>
        @endif
    </table>

    <div class="section">بيانات الإصدار</div>
    <table>
        <tr><th>رقم المعاملة بمجلس المدينة</th><td dir="ltr" style="text-align:right">{{ $certificate->request?->transaction_number ?? '-' }}</td></tr>
        <tr><th>تاريخ الإصدار</th><td>{{ $certificate->issued_at?->format('Y-m-d H:i') }}</td></tr>
        @if($certificate->cancels)
            <tr><th>شهادة الإشراف الملغاة</th><td dir="ltr" style="text-align:right">{{ $certificate->cancels->number }}</td></tr>
        @endif
        @if($certificate->notes)
            <tr><th>ملاحظات</th><td>{{ $certificate->notes }}</td></tr>
        @endif
    </table>

    <div class="foot">
        <div class="qr">{!! $certificate->qrSvg(120) !!}<br>امسح الرمز للتحقق من صحة الشهادة<br><span dir="ltr">{{ $certificate->verificationUrl(false) }}</span></div>
        <div class="sign">يعتمد،<div>نقيب المهندسين بالفرعية</div></div>
    </div>
</div>
</body>
</html>
