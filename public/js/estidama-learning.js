/* Estidama LMS — course player (resume, notes) and timed exams (timer, autosave). */
(function () {
    'use strict';

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        }).then(function (r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        });
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments, self = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(self, args); }, ms);
        };
    }

    /* ---------- Player: resume uploaded video + heartbeat ---------- */
    function initPlayer() {
        var sidebar = document.querySelector('[data-lms-progress-url]');
        var url = sidebar ? sidebar.getAttribute('data-lms-progress-url') : null;
        var video = document.querySelector('video[data-lms-video]');

        if (video && url) {
            var resume = parseInt(video.getAttribute('data-resume') || '0', 10);
            var lastSent = -1;

            video.addEventListener('loadedmetadata', function () {
                if (resume > 5 && (!video.duration || resume < video.duration - 5)) {
                    video.currentTime = resume;
                }
            });

            var send = function () {
                var pos = Math.floor(video.currentTime || 0);
                if (pos === lastSent) { return; }
                lastSent = pos;
                post(url, { position: pos }).catch(function () { /* offline: try again later */ });
            };

            var timer = null;
            video.addEventListener('play', function () { timer = setInterval(send, 15000); });
            video.addEventListener('pause', function () { clearInterval(timer); send(); });
            video.addEventListener('ended', function () { clearInterval(timer); send(); });
            window.addEventListener('pagehide', send);
        }

        var notes = document.querySelector('[data-lms-notes]');
        if (notes) {
            var status = document.querySelector('[data-lms-notes-status]');
            var notesUrl = notes.getAttribute('data-url');
            var save = debounce(function () {
                if (status) { status.textContent = 'جارٍ الحفظ…'; }
                post(notesUrl, { notes: notes.value })
                    .then(function () { if (status) { status.textContent = 'تم الحفظ ✓'; } })
                    .catch(function () { if (status) { status.textContent = 'تعذّر الحفظ، سنعيد المحاولة عند الكتابة.'; } });
            }, 1200);
            notes.addEventListener('input', save);
        }
    }

    /* ---------- Exams: timer, autosave, unanswered warning ---------- */
    function initExam() {
        var form = document.querySelector('form[data-lms-exam]');
        if (!form) { return; }

        var saveUrl = form.getAttribute('data-save-url');
        var saveStatus = document.querySelector('[data-lms-save-status]');
        var answeredEl = document.querySelector('[data-lms-answered]');
        var questions = form.querySelectorAll('[data-lms-question]');
        var submitting = false;

        function collect() {
            var answers = {};
            new FormData(form).forEach(function (value, key) {
                var m = key.match(/^answers\[(\d+)\]$/);
                if (m && String(value).trim() !== '') { answers[m[1]] = value; }
            });
            return answers;
        }

        function countAnswered() {
            var n = Object.keys(collect()).length;
            if (answeredEl) { answeredEl.textContent = n; }
            return n;
        }

        var autosave = debounce(function () {
            if (submitting) { return; }
            if (saveStatus) { saveStatus.textContent = 'جارٍ حفظ الإجابات…'; }
            post(saveUrl, { answers: collect() })
                .then(function () { if (saveStatus) { saveStatus.textContent = 'تم حفظ إجاباتك تلقائيًا ✓'; } })
                .catch(function () { if (saveStatus) { saveStatus.textContent = 'تعذّر الحفظ التلقائي — تحقّق من الاتصال.'; } });
        }, 800);

        form.addEventListener('change', function () { countAnswered(); autosave(); });
        form.addEventListener('input', function (e) { if (e.target.tagName === 'TEXTAREA') { countAnswered(); autosave(); } });
        setInterval(autosave, 30000);
        countAnswered();

        form.addEventListener('submit', function (e) {
            if (submitting) { return; }
            var unanswered = questions.length - countAnswered();
            if (unanswered > 0 && !form.dataset.autoSubmit) {
                if (!window.confirm('لم تُجب عن ' + unanswered + ' سؤال. هل تريد التسليم الآن؟')) {
                    e.preventDefault();
                    return;
                }
            }
            submitting = true;
            var btn = form.querySelector('[data-lms-submit]');
            if (btn) { btn.setAttribute('disabled', 'disabled'); }
        });

        window.addEventListener('beforeunload', function (e) {
            if (!submitting) { e.preventDefault(); e.returnValue = ''; }
        });

        var timerEl = document.querySelector('[data-lms-timer]');
        if (!timerEl) { return; }

        var announce = document.querySelector('[data-lms-timer-announce]');
        var endAt = Date.now() + parseInt(timerEl.getAttribute('data-remaining') || '0', 10) * 1000;
        var warned = {};

        function fmt(s) {
            var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            return (h ? h + ':' + pad(m) : pad(m)) + ':' + pad(sec);
        }

        function tick() {
            var left = Math.max(0, Math.round((endAt - Date.now()) / 1000));
            timerEl.textContent = fmt(left);
            timerEl.setAttribute('aria-label', 'الوقت المتبقي ' + fmt(left));

            [300, 60].forEach(function (mark) {
                if (left <= mark && !warned[mark] && announce) {
                    warned[mark] = true;
                    announce.textContent = 'تنبيه: تبقّى ' + (mark / 60) + (mark === 60 ? ' دقيقة واحدة' : ' دقائق') + ' على انتهاء الوقت.';
                }
            });
            if (left <= 60) { timerEl.classList.add('low'); }

            if (left <= 0) {
                clearInterval(interval);
                form.dataset.autoSubmit = '1';
                if (saveStatus) { saveStatus.textContent = 'انتهى الوقت — يتم تسليم إجاباتك…'; }
                submitting = true;
                form.submit();
            }
        }

        var interval = setInterval(tick, 1000);
        tick();
    }

    function init() { initPlayer(); initExam(); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
