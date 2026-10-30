<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>فواتير متعددة</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #fff; color: #000; padding: 40px; }
        .invoice { max-width: 800px; margin: 40px auto; border: 1px solid #e5e7eb; border-radius: 16px; padding: 40px; page-break-after: always; }
        .invoice:last-child { page-break-after: avoid; }
        .header { text-align: center; border-bottom: 2px solid #1e272e; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { font-size: 26px; color: #1e272e; }
        .header p { color: #6b7280; font-size: 13px; margin-top: 5px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-grid div p { font-size: 13px; color: #6b7280; }
        .info-grid div h4 { font-size: 15px; color: #1e272e; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f3f4f6; padding: 12px; font-size: 13px; text-align: center; border-bottom: 2px solid #1e272e; }
        td { padding: 12px; text-align: center; font-size: 14px; border-bottom: 1px solid #e5e7eb; }
        .total-row td { font-weight: bold; font-size: 16px; border-top: 2px solid #1e272e; }
        .footer { text-align: center; color: #9ca3af; font-size: 11px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .separator { text-align: center; padding: 20px; color: #d1d5db; font-size: 13px; }
        @media print { body { padding: 20px; } .invoice { border: none; box-shadow: none; } }
    </style>
</head>
<body>
    @foreach ($submissions as $submission)
        <div class="invoice">
            <div class="header">
                <h1>فاتورة دفع</h1>
                <p>محافظة كفر الشيخ - منظومة الخدمات</p>
            </div>
            <div class="info-grid">
                <div>
                    <p>رقم الطلب</p>
                    <h4>{{ $submission->serial_number ?? $submission->id }}</h4>
                </div>
                <div>
                    <p>التاريخ</p>
                    <h4>{{ $submission->created_at->format('Y-m-d') }}</h4>
                </div>
                <div>
                    <p>المواطن</p>
                    <h4>{{ $submission->user->name ?? '' }}</h4>
                </div>
                <div>
                    <p>الخدمة</p>
                    <h4>{{ $submission->subService->name ?? '' }}</h4>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>البيان</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>{{ $submission->subService->name ?? 'رسوم خدمة' }}</td>
                        <td>{{ number_format($submission->total_amount, 2) }} ج.م</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2">الإجمالي</td>
                        <td>{{ number_format($submission->total_amount, 2) }} ج.م</td>
                    </tr>
                </tbody>
            </table>
            <div class="footer">
                <p>فاتورة رقم {{ $loop->iteration }} — تم إنشاؤها آلياً</p>
            </div>
        </div>
    @endforeach
    <script>window.print();</script>
</body>
</html>