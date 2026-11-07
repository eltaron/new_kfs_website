/*!
 * KFS — Home hero search console: live suggestions (combobox), rotating
 * placeholder examples and the «/» shortcut. Data: #khsData (JSON array of
 * {t: title, u: url, i: icon class, k: kind, d?: description}).
 */
(function () {
    'use strict';
    var form = document.getElementById('khsForm');
    var input = document.getElementById('khsInput');
    var list = document.getElementById('khsList');
    var clear = document.getElementById('khsClear');
    if (!form || !input || !list) return;

    var data = [];
    try { data = JSON.parse(document.getElementById('khsData').textContent) || []; } catch (e) { data = []; }

    var norm = function (s) {
        return (s || '').toString()
            .replace(/[ً-ٰٟـ]/g, '')
            .replace(/[أإآٱ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه')
            .toLowerCase().trim();
    };
    data.forEach(function (d) { d._n = norm(d.t + ' ' + (d.d || '') + ' ' + (d.k || '')); d._t = norm(d.t); });

    var el = function (tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; };
    var active = -1;
    var options = [];

    // Highlight the matched part of the title without using innerHTML.
    function titleNode(title, q) {
        var strong = el('strong');
        var nt = norm(title);
        var idx = q ? nt.indexOf(q) : -1;
        // Diacritics are rare in titles; when lengths match we can map indexes 1:1.
        if (idx < 0 || nt.length !== title.length) { strong.textContent = title; return strong; }
        strong.append(document.createTextNode(title.slice(0, idx)));
        strong.append(el('mark', null, title.slice(idx, idx + q.length)));
        strong.append(document.createTextNode(title.slice(idx + q.length)));
        return strong;
    }

    function option(item, q, id) {
        var a = el('a', 'khs__opt');
        a.href = item.u;
        a.id = id;
        a.setAttribute('role', 'option');
        a.setAttribute('aria-selected', 'false');
        a.tabIndex = -1;
        var ic = el('span', 'khs__opt-icon'); var i = el('i', item.i); i.setAttribute('aria-hidden', 'true'); ic.append(i);
        var body = el('span', 'khs__opt-body');
        body.append(titleNode(item.t, q));
        if (item.d) body.append(el('small', null, item.d));
        a.append(ic, body);
        if (item.k) a.append(el('span', 'khs__opt-kind', item.k));
        return a;
    }

    function render() {
        var raw = input.value.trim();
        var q = norm(raw);
        clear.hidden = !raw;
        list.replaceChildren();
        options = [];
        active = -1;
        input.removeAttribute('aria-activedescendant');
        if (!q) { close(); return; }

        var matches = data.filter(function (d) { return d._n.indexOf(q) !== -1; })
            .sort(function (a, b) {
                var as = a._t.indexOf(q) === 0 ? 0 : (a._t.indexOf(q) > -1 ? 1 : 2);
                var bs = b._t.indexOf(q) === 0 ? 0 : (b._t.indexOf(q) > -1 ? 1 : 2);
                return as - bs;
            })
            .slice(0, 6);

        matches.forEach(function (m, n) {
            var li = el('li'); li.setAttribute('role', 'presentation');
            var a = option(m, q, 'khs-opt-' + n);
            li.append(a); list.append(li); options.push(a);
        });

        // Always offer a full-site search as the last option.
        var li = el('li'); li.setAttribute('role', 'presentation');
        var all = option({ t: 'ابحث عن «' + raw + '» في كل الموقع', u: form.action + '?query=' + encodeURIComponent(raw), i: 'fa-solid fa-magnifying-glass' }, '', 'khs-opt-all');
        all.classList.add('khs__opt--all');
        li.append(all); list.append(li); options.push(all);

        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function close() {
        list.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        active = -1;
    }

    function move(delta) {
        if (list.hidden || !options.length) return;
        if (active > -1) options[active].setAttribute('aria-selected', 'false');
        active = (active + delta + options.length) % options.length;
        var o = options[active];
        o.setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', o.id);
        o.scrollIntoView({ block: 'nearest' });
    }

    input.addEventListener('input', render);
    input.addEventListener('focus', function () { if (input.value.trim()) render(); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
        else if (e.key === 'Escape') { close(); }
        else if (e.key === 'Enter' && active > -1 && !list.hidden) { e.preventDefault(); window.location.href = options[active].href; }
    });
    clear.addEventListener('click', function () { input.value = ''; render(); input.focus(); });
    document.addEventListener('click', function (e) { if (!form.contains(e.target)) close(); });
    form.addEventListener('submit', function (e) { if (!input.value.trim()) { e.preventDefault(); input.focus(); } });

    // «/» focuses the search (unless typing in a field).
    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
        var t = e.target;
        if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
        e.preventDefault();
        input.focus();
        input.scrollIntoView({ block: 'center', behavior: 'smooth' });
    });

    // Rotating placeholder examples (paused while focused or when motion is reduced).
    var examples = [];
    try { examples = JSON.parse(input.dataset.examples || '[]'); } catch (e) { examples = []; }
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (examples.length && !reduce) {
        var base = input.placeholder, n = 0;
        setInterval(function () {
            if (document.activeElement === input || input.value) return;
            input.classList.add('is-swapping');
            setTimeout(function () {
                input.placeholder = 'جرّب: ' + examples[n % examples.length] + '…';
                n++;
                input.classList.remove('is-swapping');
            }, 250);
        }, 2800);
        input.addEventListener('blur', function () { if (!input.value) input.placeholder = base; });
    }
})();
