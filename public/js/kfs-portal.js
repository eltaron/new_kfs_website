/*!
 * KFS Portal helpers (auth / account pages). Progressive enhancement only:
 * every page works without this file.
 *  - [data-pw-toggle="<input id>"]  show / hide a password field
 *  - [data-copy-target="<element id>"]  copy an element's text to the clipboard
 */
(function () {
    'use strict';

    document.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
        var input = document.getElementById(btn.getAttribute('data-pw-toggle'));
        if (!input) return;
        btn.hidden = false;
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
            var icon = btn.querySelector('i');
            if (icon) { icon.classList.toggle('fa-eye', !show); icon.classList.toggle('fa-eye-slash', show); }
            input.focus();
        });
    });

    document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
        var target = document.getElementById(btn.getAttribute('data-copy-target'));
        if (!target || !navigator.clipboard) return;
        btn.hidden = false;
        var label = btn.querySelector('span');
        var original = label ? label.textContent : '';
        btn.addEventListener('click', function () {
            var text = Array.prototype.map.call(target.querySelectorAll('li'), function (li) { return li.textContent.trim(); }).join('\n') || target.textContent.trim();
            navigator.clipboard.writeText(text).then(function () {
                btn.setAttribute('data-copied', '1');
                if (label) label.textContent = 'تم النسخ';
                setTimeout(function () { btn.removeAttribute('data-copied'); if (label) label.textContent = original; }, 2200);
            });
        });
    });
})();
