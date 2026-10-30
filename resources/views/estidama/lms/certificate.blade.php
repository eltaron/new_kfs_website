<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>شهادة {{ $certificate->program_title }} — {{ $certificate->serial }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600&family=Tajawal:wght@500;700;800&display=swap">
    <style>
        :root { --gold: #D8B03E; --gold-dark: #B88920; --gold-deep: #8F6917; --cyan: #00BFD8; --cyan-deep: #008FA5;
                --ivory: #FFFDE1; --papyrus: #F2EBC7; --charcoal: #282724; --text: #393733; --soft: #68645A; --dark: #26302D; }
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #e9e5d2; color: var(--text); font-family: 'IBM Plex Sans Arabic', 'Tajawal', sans-serif; }
        .toolbar { display: flex; gap: .6rem; justify-content: center; padding: 1rem; }
        .toolbar a, .toolbar button { font: 700 .95rem 'Tajawal', sans-serif; padding: .55rem 1.1rem; border-radius: 6px; border: 1px solid var(--gold); background: var(--gold); color: var(--charcoal); cursor: pointer; text-decoration: none; }
        .toolbar a.ghost { background: #fff; }
        .sheet { width: 297mm; height: 210mm; margin: 0 auto 2rem; background: var(--ivory); position: relative; overflow: hidden;
                 box-shadow: 0 25px 70px rgba(40,39,36,.18); padding: 14mm 18mm; }
        .frame { position: absolute; inset: 8mm; border: 1.5px solid var(--gold); pointer-events: none; }
        .frame::after { content: ''; position: absolute; inset: 2.5mm; border: .6px solid var(--gold); }
        .wave { position: absolute; inset: auto 0 0 0; height: 10mm;
                background: linear-gradient(90deg, transparent, rgba(0,191,216,.55) 25%, var(--gold) 50%, rgba(0,191,216,.55) 75%, transparent); opacity: .35; }
        .pattern { position: absolute; inset: 0; opacity: .05; pointer-events: none;
                   background: repeating-linear-gradient(135deg, var(--cyan-deep) 0 1px, transparent 1px 22px); }
        .head { display: flex; align-items: center; justify-content: space-between; position: relative; }
        .head img { height: 26mm; }
        .head .gov { text-align: center; font-family: 'Tajawal', sans-serif; color: var(--dark); line-height: 1.6; }
        .head .gov b { display: block; font-size: 15pt; }
        .head .gov span { font-size: 11pt; color: var(--soft); }
        .title { text-align: center; margin-top: 7mm; position: relative; }
        .title h1 { font-family: 'Tajawal', sans-serif; font-weight: 800; font-size: 30pt; color: var(--charcoal); margin: 0; letter-spacing: .5px; }
        .title .sub { color: var(--gold-deep); font: 700 13pt 'Tajawal', sans-serif; margin-top: 1mm; }
        .heritage { width: 70mm; height: 3mm; margin: 4mm auto; border-top: 1px solid var(--gold); border-bottom: 1px solid var(--gold); }
        .body { text-align: center; font-size: 14pt; line-height: 2; position: relative; }
        .body .name { font: 800 26pt 'Tajawal', sans-serif; color: var(--cyan-deep); margin: 2mm 0; }
        .body .program { font: 800 18pt 'Tajawal', sans-serif; color: var(--charcoal); }
        .foot { position: absolute; inset: auto 20mm 18mm 20mm; display: flex; align-items: flex-end; justify-content: space-between; gap: 10mm; }
        .foot .meta { font-size: 10.5pt; line-height: 1.9; color: var(--soft); }
        .foot .meta b { color: var(--charcoal); }
        .foot .serial { font-family: 'Courier New', monospace; font-weight: 700; color: var(--charcoal); direction: ltr; unicode-bidi: embed; letter-spacing: 1px; }
        .sign { text-align: center; font-family: 'Tajawal', sans-serif; min-width: 55mm; }
        .sign .line { border-top: 1px solid var(--charcoal); margin-top: 14mm; padding-top: 1.5mm; font-weight: 700; }
        .qr { text-align: center; font-size: 8.5pt; color: var(--soft); }
        .qr svg { width: 30mm; height: 30mm; display: block; margin: 0 auto 1mm; background: #fff; padding: 1.5mm; border: 1px solid var(--gold); }
        .revoked { position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none; }
        .revoked span { transform: rotate(-18deg); border: 4px solid #A63A32; color: #A63A32; font: 800 34pt 'Tajawal', sans-serif; padding: 4mm 14mm; background: rgba(255,253,225,.85); }
        @media print {
            html, body { background: none; }
            .toolbar { display: none; }
            .sheet { margin: 0; box-shadow: none; }
        }
        @media screen and (max-width: 1150px) {
            .sheet { transform: scale(calc(100vw / 1150px)); transform-origin: top center; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">طباعة / حفظ PDF</button>
        <a class="ghost" href="{{ $certificate->verificationUrl() }}" target="_blank" rel="noopener">صفحة التحقق</a>
        @auth
            <a class="ghost" href="{{ route('lms.dashboard') }}">العودة إلى «تعلّمي»</a>
        @endauth
    </div>

    <main class="sheet" aria-label="شهادة إتمام">
        <div class="pattern" aria-hidden="true"></div>
        <div class="frame" aria-hidden="true"></div>
        <div class="wave" aria-hidden="true"></div>

        <div class="head">
            <img src="{{ asset('logo.png') }}" alt="شعار محافظة كفر الشيخ">
            <div class="gov">
                <b>جمهورية مصر العربية — محافظة كفر الشيخ</b>
                <span>{{ $certificate->trainingProgram?->trainingCenter?->name ?? 'مركز استدامة للتدريب والتنمية المستدامة' }}</span>
            </div>
            <div style="width:26mm"></div>
        </div>

        <div class="title">
            <h1>شهادة إتمام برنامج تدريبي</h1>
            <div class="sub">Certificate of Completion</div>
        </div>
        <div class="heritage" aria-hidden="true"></div>

        <div class="body">
            <div>تشهد محافظة كفر الشيخ — مركز استدامة بأن</div>
            <div class="name">{{ $certificate->holder_name }}</div>
            <div>قد أتمّ بنجاح متطلبات البرنامج التدريبي</div>
            <div class="program">«{{ $certificate->program_title }}»</div>
            @if ($certificate->final_score !== null)
                <div>وحصل في الامتحان النهائي على تقدير <b>{{ $certificate->final_score }}%</b></div>
            @endif
        </div>

        <div class="foot">
            <div class="meta">
                <div>تاريخ الإصدار: <b>{{ $certificate->issued_at->format('Y-m-d') }}</b></div>
                <div>الرقم التسلسلي: <span class="serial">{{ $certificate->serial }}</span></div>
                @if ($certificate->trainingProgram?->instructor)
                    <div>المدرب: <b>{{ $certificate->trainingProgram->instructor->name }}</b></div>
                @endif
            </div>
            <div class="sign">
                <div class="line">مدير مركز استدامة</div>
            </div>
            <div class="qr">
                {!! $certificate->qrSvg(160) !!}
                امسح الرمز للتحقق من صحة الشهادة
            </div>
        </div>

        @unless ($certificate->isValid())
            <div class="revoked" aria-hidden="false"><span>شهادة ملغاة</span></div>
        @endunless
    </main>
</body>
</html>
