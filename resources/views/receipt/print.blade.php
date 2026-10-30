<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>إيصال سداد إلكتروني</title>
    <style>
        :root { --charcoal: #282724; --soft: #68645A; --gold: #B88920; --line: rgba(40,39,36,.15); --ok: #3D7A50; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: var(--charcoal); font-family: 'IBM Plex Sans Arabic', 'Tajawal', Tahoma, sans-serif; }
        .receipt { max-width: 560px; margin: 24px auto; padding: 24px; border: 1px solid var(--line); border-top: 6px solid var(--gold); border-radius: 12px; }
        header { text-align: center; margin-bottom: 16px; }
        header h1 { font-size: 1.3rem; margin: 6px 0 2px; }
        header p { margin: 0; color: var(--soft); font-size: .9rem; }
        table { width: 100%; border-collapse: collapse; font-size: .95rem; }
        td { padding: 8px 4px; border-bottom: 1px dashed var(--line); vertical-align: top; }
        td:first-child { color: var(--soft); width: 40%; }
        td:last-child { font-weight: 600; word-break: break-all; }
        .total td { font-size: 1.1rem; border-bottom: 2px solid var(--charcoal); }
        .paid { display: inline-block; color: var(--ok); border: 2px solid var(--ok); border-radius: 8px; padding: 2px 12px; font-weight: 800; }
        footer { text-align: center; margin-top: 18px; color: var(--soft); font-size: .85rem; }
        .no-print { text-align: center; margin: 12px; }
        .no-print button { background: var(--gold); color: #fff; border: 0; border-radius: 999px; padding: 10px 24px; font: inherit; cursor: pointer; }
        @media print { .no-print { display: none; } .receipt { border-color: #999; margin: 0 auto; } }
    </style>
</head>

<body>
    @php($payable = $transaction->transactionable)
    <div class="receipt">
        <header>
            <strong>محافظة كفر الشيخ</strong>
            <h1>إيصال سداد إلكتروني</h1>
            <p>بوابة الدفع الحكومي e-finance</p>
        </header>

        <table>
            <tr><td>رقم الإيصال</td><td>{{ $transaction->id }}</td></tr>
            <tr><td>البيان</td><td>{{ $transaction->description ?? ($payable instanceof \App\Contracts\Payable ? $payable->payableDescription() : $transaction->typeLabel()) }}</td></tr>
            <tr><td>نوع المعاملة</td><td>{{ $transaction->typeLabel() }}</td></tr>
            <tr><td>اسم المسدد</td><td>{{ $transaction->user?->name ?? ($payable->company_name ?? $payable->customer_name ?? '—') }}</td></tr>
            <tr><td>تاريخ السداد</td><td>{{ $transaction->completed_at?->format('Y/m/d - h:i A') }}</td></tr>
            <tr><td>رقم العملية</td><td>{{ $transaction->sender_request_number }}</td></tr>
            @if ($transaction->payment_request_number)
                <tr><td>رقم المرجع (e-finance)</td><td>{{ $transaction->payment_request_number }}</td></tr>
            @endif
            @if ($transaction->authorization_code)
                <tr><td>كود التفويض</td><td>{{ $transaction->authorization_code }}</td></tr>
            @endif
            @if ($transaction->collection_fees !== null && (float) $transaction->collection_fees > 0)
                <tr><td>رسوم التحصيل</td><td>{{ number_format((float) $transaction->collection_fees, 2) }} ج.م</td></tr>
            @endif
            <tr class="total"><td>المبلغ المسدد</td><td>{{ number_format((float) $transaction->amount, 2) }} ج.م</td></tr>
            <tr><td>الحالة</td><td><span class="paid">مدفوع</span></td></tr>
        </table>

        <footer>هذا الإيصال صادر إلكترونياً ولا يحتاج إلى توقيع. شكراً لتعاملكم معنا.</footer>
    </div>

    <div class="no-print"><button type="button" onclick="window.print()">طباعة</button></div>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>

</html>
