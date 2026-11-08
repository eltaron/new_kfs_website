{{-- Shared behaviour for the contact forms (.kh-topic chips → subject, message counter, single submit). --}}
<script>
(function () {
    const form = document.getElementById('khForm');
    if (!form) return;
    const subject = document.getElementById('subject');
    const chips = form.querySelectorAll('.kh-topic');
    const sync = () => chips.forEach(c => c.setAttribute('aria-pressed', String(c.dataset.topic === subject.value.trim())));
    chips.forEach(c => c.addEventListener('click', () => { subject.value = c.dataset.topic; sync(); document.getElementById('message')?.focus(); }));
    subject.addEventListener('input', sync);
    sync();

    const msg = document.getElementById('message');
    const count = document.getElementById('khCount');
    if (msg && count) {
        const max = +msg.getAttribute('maxlength') || 0;
        const upd = () => { count.textContent = msg.value.length + (max ? ' / ' + max : ''); };
        msg.addEventListener('input', upd); upd();
    }

    form.addEventListener('submit', () => {
        const btn = document.getElementById('khSubmit');
        if (!btn) return;
        btn.disabled = true;
        btn.querySelector('span').textContent = 'جاري الإرسال…';
    });
})();
</script>
