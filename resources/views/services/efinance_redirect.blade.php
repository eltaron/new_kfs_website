<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>جاري التحويل إلى بوابة الدفع</title>
    <style>
        :root { --ivory: #FFFDE1; --charcoal: #282724; --soft: #68645A; --gold: #B88920; --line: rgba(216,176,62,.34); }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: var(--ivory); color: var(--charcoal);
               font-family: 'IBM Plex Sans Arabic', 'Tajawal', Tahoma, sans-serif; padding: 16px; }
        .box { max-width: 460px; width: 100%; background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 32px 24px; text-align: center; }
        .spinner { width: 46px; height: 46px; border: 4px solid var(--line); border-top-color: var(--gold); border-radius: 50%; margin: 0 auto 18px; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 1.25rem; margin: 0 0 8px; }
        p { color: var(--soft); margin: 0 0 18px; }
        .amount { font-weight: 700; color: var(--charcoal); }
        button { background: var(--gold); color: #fff; border: 0; border-radius: 999px; padding: 12px 28px; font: inherit; font-weight: 700; cursor: pointer; }
    </style>
</head>

<body>
    <div class="box">
        <div class="spinner" aria-hidden="true"></div>
        <h1>جاري توجيهك بأمان إلى بوابة الدفع e-finance</h1>
        @isset($transaction)
            <p>المبلغ: <span class="amount">{{ number_format((float) $transaction->amount, 2) }} ج.م</span></p>
        @endisset
        <p>يرجى عدم إغلاق الصفحة أو تحديثها...</p>

        <form method="post" id="initiationForm" action="{{ $url }}">
            @foreach ($params as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <noscript><button type="submit">متابعة إلى بوابة الدفع</button></noscript>
            <button type="submit" id="sendButton" style="display:none">متابعة إلى بوابة الدفع</button>
        </form>
    </div>

    <script>
        (function () {
            var form = document.getElementById('initiationForm');
            var btn = document.getElementById('sendButton');
            setTimeout(function () { btn.style.display = 'inline-block'; }, 4000);
            form.submit();
        })();
    </script>
</body>

</html>
