{{-- Advertising guide — map / list / filters / add-edit. Kept inline (not a static .js) so the security
     regression tests can assert that every user-supplied field is escaped (escHtml) before reaching innerHTML. --}}
@php
    $adgCfg = [
        'pointsUrl' => url('/advertising-guide/points'),
        'loggedIn' => auth()->check(),
        'types' => $types,
        'areas' => $areas,
    ];
@endphp
<script>
(function () {
    'use strict';

    const CFG = @json($adgCfg);

    // SECURITY: point fields are user-supplied; escape before any innerHTML / Leaflet popup.
    const escHtml = (v) => String(v ?? '').replace(/[&<>"'`]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#96;'}[c]));

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
    const root = document.documentElement;
    const nf = new Intl.NumberFormat('en-US');
    const fmt = (n) => nf.format(n);
    const reduceMotion = () => root.classList.contains('kfs-a11y-noanim')
        || (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
    const isNarrow = () => window.matchMedia('(max-width: 991.98px)').matches;

    const TYPES = CFG.types || {};
    const OTHER = TYPES['أخرى'] || { label: 'أخرى', color: '#6B7280', icon: 'fa-location-dot' };
    const typeMeta = (t) => TYPES[t] || { label: t || OTHER.label, color: OTHER.color, icon: OTHER.icon };
    // Only allow a plain hex colour / fa-* class through to style/class attributes.
    const safeColor = (c) => /^#[0-9a-f]{3,8}$/i.test(c) ? c : OTHER.color;
    const safeIcon = (i) => /^fa-[a-z0-9-]+$/.test(i) ? i : 'fa-location-dot';

    /* ---------------- toasts ---------------- */
    function toast(msg, kind = 'success') {
        const box = $('#adgToasts');
        if (!box) return;
        const el = document.createElement('div');
        el.className = 'adg-toast adg-toast--' + kind;
        el.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        el.innerHTML = `<i class="fa-solid ${kind === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'}" aria-hidden="true"></i><span>${escHtml(msg)}</span>`;
        box.appendChild(el);
        setTimeout(() => { el.classList.add('is-out'); setTimeout(() => el.remove(), 400); }, 4200);
    }

    /* ---------------- size buckets / search normalisation ---------------- */
    function sizeArea(size) {
        const m = String(size || '').replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).match(/(\d+(?:\.\d+)?)\s*[×xX*]\s*(\d+(?:\.\d+)?)/);
        return m ? parseFloat(m[1]) * parseFloat(m[2]) : null;
    }
    function sizeBucket(size) {
        const a = sizeArea(size);
        if (a === null) return '';
        return a < 4 ? 'small' : (a <= 18 ? 'medium' : 'large');
    }
    const norm = (s) => String(s || '').toLowerCase()
        .replace(/[ً-ْـ]/g, '')
        .replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');

    /* ---------------- state ---------------- */
    let all = [];
    const byId = new Map();
    const markers = new Map();
    const openStreets = new Set();
    let expandAll = false;
    let activeId = null;
    let firstLoad = true;
    const state = { q: '', type: '', area: '', size: '' };

    /* ---------------- map ---------------- */
    const mapEl = $('#adgMap');
    const mapWrap = $('#adgMapWrap');
    const layout = $('.adg-layout');
    if (!window.L || !mapEl) {
        $('#adgMapError')?.removeAttribute('hidden');
    }
    const HAS_MAP = !!(window.L && mapEl);
    const DEFAULT_VIEW = { center: [31.30, 30.90], zoom: 10 };

    let map = null, cluster = null, baseLayer = null, baseMode = 'map';
    const tiles = {};
    if (HAS_MAP) {
        map = L.map(mapEl, { center: DEFAULT_VIEW.center, zoom: DEFAULT_VIEW.zoom, minZoom: 8, maxZoom: 19, zoomControl: false, scrollWheelZoom: false });
        L.control.zoom({ position: 'topleft', zoomInTitle: 'تكبير', zoomOutTitle: 'تصغير' }).addTo(map);
        map.attributionControl.setPrefix(false);

        tiles.osm = () => L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, subdomains: 'abc', attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
        });
        tiles.sat = () => L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19, maxNativeZoom: 18, attribution: '&copy; Esri, Maxar, Earthstar Geographics'
        });

        cluster = (typeof L.markerClusterGroup === 'function')
            ? L.markerClusterGroup({
                showCoverageOnHover: false, maxClusterRadius: 48, spiderfyOnMaxZoom: true, chunkedLoading: true,
                animate: !reduceMotion(),
                iconCreateFunction: (c) => {
                    const n = c.getChildCount();
                    return L.divIcon({ html: `<span>${fmt(n)}</span>`, className: 'adg-cluster' + (n >= 20 ? ' adg-cluster--lg' : ''), iconSize: [42, 42] });
                },
            })
            : L.featureGroup();
        map.addLayer(cluster);

        // Wheel-zoom only once the visitor engages with the map (avoids hijacking page scroll).
        map.on('focus click', () => map.scrollWheelZoom.enable());
        mapEl.addEventListener('mouseleave', () => { if (!mapWrap.classList.contains('is-fullscreen')) map.scrollWheelZoom.disable(); });

        applyBase();
        new ResizeObserver(() => map.invalidateSize()).observe(mapEl);
    }

    function applyBase() {
        if (!map) return;
        // Dark theme keeps the same OSM tiles; kfs CSS dims/inverts the tile pane (no third-party key needed).
        const key = baseMode === 'sat' ? 'sat' : 'osm';
        if (baseLayer && baseLayer._adgKey === key) return;
        if (baseLayer) map.removeLayer(baseLayer);
        baseLayer = tiles[key]();
        baseLayer._adgKey = key;
        baseLayer.addTo(map);
        mapWrap?.classList.toggle('is-sat', key === 'sat');
    }

    function markerIcon(ad, active) {
        const m = typeMeta(ad.type);
        return L.divIcon({
            className: 'adg-pin' + (active ? ' is-active' : ''),
            html: `<span class="adg-pin__body" style="--pin:${safeColor(m.color)}"><i class="fa-solid ${safeIcon(m.icon)}" aria-hidden="true"></i></span>`,
            iconSize: [36, 44], iconAnchor: [18, 42], popupAnchor: [0, -38],
        });
    }

    function specsLine(ad) {
        const parts = [];
        if (ad.size) parts.push(`<span><i class="fa-solid fa-expand" aria-hidden="true"></i> ${escHtml(ad.size)}</span>`);
        if (ad.height !== null && ad.height !== undefined && ad.height !== '') parts.push(`<span><i class="fa-solid fa-ruler-vertical" aria-hidden="true"></i> ${escHtml(Number(ad.height))} م</span>`);
        return parts.join('');
    }

    function buildPopup(ad) {
        const m = typeMeta(ad.type);
        const lat = Number(ad.lat), lng = Number(ad.lng), id = Number(ad.id);
        const osm = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=18/${lat}/${lng}`;
        return `<div class="adg-pop" dir="rtl">
            <div class="adg-pop__head" style="--pin:${safeColor(m.color)}">
                <span class="adg-pop__icon"><i class="fa-solid ${safeIcon(m.icon)}" aria-hidden="true"></i></span>
                <div class="adg-pop__titles">
                    <span class="adg-pop__type">${escHtml(m.label)}</span>
                    <strong class="adg-pop__street">${escHtml(ad.street_name)}</strong>
                </div>
            </div>
            <dl class="adg-pop__specs">
                <div><dt>المقاس</dt><dd>${ad.size ? escHtml(ad.size) : '—'}</dd></div>
                <div><dt>الارتفاع</dt><dd>${ad.height !== null && ad.height !== '' && ad.height !== undefined ? escHtml(Number(ad.height)) + ' م' : '—'}</dd></div>
                <div><dt>المركز</dt><dd>${escHtml(ad.area || '—')}</dd></div>
            </dl>
            ${ad.description ? `<p class="adg-pop__desc">${escHtml(ad.description)}</p>` : ''}
            <div class="adg-pop__actions">
                <a href="${escHtml(osm)}" target="_blank" rel="noopener" class="adg-pop__btn"><i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i> فتح في الخرائط</a>
                <button type="button" class="adg-pop__btn" data-act="share" data-id="${id}"><i class="fa-solid fa-link" aria-hidden="true"></i> نسخ الرابط</button>
            </div>
            ${ad.can_manage ? `<div class="adg-pop__actions adg-pop__actions--manage">
                <button type="button" class="adg-pop__btn adg-pop__btn--primary" data-act="edit" data-id="${id}"><i class="fa-solid fa-pen" aria-hidden="true"></i> تعديل</button>
                <button type="button" class="adg-pop__btn adg-pop__btn--danger" data-act="delete" data-id="${id}"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> حذف</button>
            </div>` : ''}
        </div>`;
    }

    /* ---------------- data ---------------- */
    function loadPoints() {
        $('#adgList')?.setAttribute('aria-busy', 'true');
        return fetch(CFG.pointsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
            .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(data => {
                all = Array.isArray(data) ? data : [];
                byId.clear();
                markers.clear();
                all.forEach(ad => {
                    ad.lat = Number(ad.lat); ad.lng = Number(ad.lng);
                    ad._bucket = sizeBucket(ad.size);
                    ad._meta = typeMeta(ad.type);
                    ad._text = norm([ad.street_name, ad.description, ad._meta.label, ad.type, ad.area, ad.size].join(' '));
                    byId.set(Number(ad.id), ad);
                    if (HAS_MAP && isFinite(ad.lat) && isFinite(ad.lng)) {
                        const mk = L.marker([ad.lat, ad.lng], {
                            icon: markerIcon(ad, false), keyboard: true, riseOnHover: true,
                            title: ad._meta.label + ' — ' + ad.street_name, // DOM title property (not HTML)
                        });
                        mk.bindPopup(() => buildPopup(ad), { className: 'adg-popup', maxWidth: 320, minWidth: 240, autoPanPadding: [24, 24] });
                        mk.on('click', () => setActive(Number(ad.id), { fromMap: true }));
                        mk.on('popupclose', () => { if (activeId === Number(ad.id)) setActive(null); });
                        markers.set(Number(ad.id), mk);
                    }
                });
                buildTypeControls();
                buildStreetDatalist();
                render({ fit: firstLoad });
                if (firstLoad) {
                    firstLoad = false;
                    const deep = Number(new URLSearchParams(location.search).get('point'));
                    if (deep && byId.has(deep)) focusAd(deep);
                }
            })
            .catch(() => {
                $('#adgCount').textContent = 'تعذّر تحميل المواقع الإعلانية.';
                $('#adgList').innerHTML = `<div class="adg-loaderr" role="alert"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> تعذّر تحميل البيانات. <button type="button" class="adg-linkbtn" id="adgRetry">إعادة المحاولة</button></div>`;
                $('#adgRetry')?.addEventListener('click', loadPoints);
            })
            .finally(() => $('#adgList')?.setAttribute('aria-busy', 'false'));
    }

    function matches(ad, ignore) {
        if (ignore !== 'type' && state.type && ad.type !== state.type) return false;
        if (state.area && ad.area !== state.area) return false;
        if (state.size && ad._bucket !== state.size) return false;
        if (state.q) {
            const words = norm(state.q).split(/\s+/).filter(Boolean);
            if (!words.every(w => ad._text.includes(w))) return false;
        }
        return true;
    }

    /* ---------------- type select + chips + legend ---------------- */
    function presentTypes() {
        const counts = new Map();
        all.forEach(a => counts.set(a.type, (counts.get(a.type) || 0) + 1));
        return [...counts.entries()].sort((a, b) => b[1] - a[1]);
    }

    function buildTypeControls() {
        const types = presentTypes();
        const sel = $('#adgType');
        if (sel) {
            const cur = state.type;
            sel.innerHTML = '<option value="">كل الأنواع</option>' + types.map(([t]) =>
                `<option value="${escHtml(t)}">${escHtml(typeMeta(t).label)}</option>`).join('');
            sel.value = types.some(([t]) => t === cur) ? cur : '';
            state.type = sel.value;
        }
        const legend = $('#adgLegendList');
        if (legend) {
            legend.innerHTML = types.map(([t]) => {
                const m = typeMeta(t);
                return `<li><span class="adg-dot" style="--pin:${safeColor(m.color)}"><i class="fa-solid ${safeIcon(m.icon)}" aria-hidden="true"></i></span>${escHtml(m.label)}</li>`;
            }).join('') || '<li>لا توجد بيانات</li>';
        }
    }

    function renderChips() {
        const box = $('#adgChips');
        if (!box) return;
        const counts = new Map();
        let total = 0;
        all.forEach(a => { if (matches(a, 'type')) { counts.set(a.type, (counts.get(a.type) || 0) + 1); total++; } });
        const chip = (val, label, n, color, icon) => {
            const on = state.type === val;
            return `<button type="button" class="adg-chip${on ? ' is-active' : ''}" data-type="${escHtml(val)}" aria-pressed="${on}">
                ${color ? `<span class="adg-dot adg-dot--sm" style="--pin:${safeColor(color)}"><i class="fa-solid ${safeIcon(icon)}" aria-hidden="true"></i></span>` : '<i class="fa-solid fa-layer-group" aria-hidden="true"></i>'}
                <span>${escHtml(label)}</span><span class="adg-chip__count">${fmt(n)}</span></button>`;
        };
        box.innerHTML = chip('', 'الكل', total) + presentTypes().map(([t]) => {
            const m = typeMeta(t);
            return chip(t, m.label, counts.get(t) || 0, m.color, m.icon);
        }).join('');
    }

    function buildStreetDatalist() {
        const dl = $('#adgStreetList');
        if (!dl) return;
        dl.innerHTML = [...new Set(all.map(a => a.street_name))].sort((a, b) => a.localeCompare(b, 'ar'))
            .map(s => `<option value="${escHtml(s)}"></option>`).join('');
    }

    /* ---------------- list ---------------- */
    function render(opts = {}) {
        const list = $('#adgList');
        const filtered = all.filter(a => matches(a));
        const filtering = !!(state.q || state.type || state.area || state.size);

        // map markers
        if (HAS_MAP) {
            cluster.clearLayers();
            const layers = filtered.map(a => markers.get(Number(a.id))).filter(Boolean);
            if (cluster.addLayers) cluster.addLayers(layers); else layers.forEach(l => cluster.addLayer(l));
            if ((opts.fit || filtering || opts.refit) && layers.length) fitTo(layers.map(l => l.getLatLng()));
        }

        // grouped list
        const groups = new Map();
        filtered.forEach(a => { if (!groups.has(a.street_name)) groups.set(a.street_name, []); groups.get(a.street_name).push(a); });
        const streets = [...groups.keys()].sort((a, b) => groups.get(b).length - groups.get(a).length || a.localeCompare(b, 'ar'));
        if (!openStreets.size && streets.length) openStreets.add(streets[0]);

        let html = '';
        streets.forEach((street, si) => {
            const items = groups.get(street);
            const open = expandAll || filtering || openStreets.has(street);
            const sid = 'adg-s-' + si;
            const areas = [...new Set(items.map(i => i.area).filter(Boolean))].join('، ');
            html += `<section class="adg-street${open ? ' is-open' : ''}" data-street="${escHtml(street)}">
                <h3 class="adg-street__head">
                    <button type="button" class="adg-street__toggle" aria-expanded="${open}" aria-controls="${sid}">
                        <span class="adg-street__icon" aria-hidden="true"><i class="fa-solid fa-road"></i></span>
                        <span class="adg-street__titles"><span class="adg-street__name">${escHtml(street)}</span><span class="adg-street__area">${escHtml(areas)}</span></span>
                        <span class="adg-street__count" aria-label="${fmt(items.length)} موقع">${fmt(items.length)}</span>
                        <i class="fa-solid fa-chevron-down adg-street__chev" aria-hidden="true"></i>
                    </button>
                </h3>
                <ul class="adg-street__items" id="${sid}"${open ? '' : ' hidden'}>
                    ${items.map(ad => {
                        const m = ad._meta, id = Number(ad.id), on = id === activeId;
                        return `<li><button type="button" class="adg-item${on ? ' is-active' : ''}" data-id="${id}"${on ? ' aria-current="true"' : ''} style="--pin:${safeColor(m.color)}">
                            <span class="adg-item__icon" aria-hidden="true"><i class="fa-solid ${safeIcon(m.icon)}"></i></span>
                            <span class="adg-item__body">
                                <span class="adg-item__top"><span class="adg-item__type">${escHtml(m.label)}</span>${ad.can_manage ? '<span class="adg-item__mine">موقعك</span>' : ''}</span>
                                <span class="adg-item__specs">${specsLine(ad) || '<span>بدون مقاس مسجل</span>'}</span>
                                ${ad.description ? `<span class="adg-item__desc">${escHtml(ad.description)}</span>` : ''}
                            </span>
                            <i class="fa-solid fa-location-arrow adg-item__go" aria-hidden="true"></i>
                        </button></li>`;
                    }).join('')}
                </ul>
            </section>`;
        });
        if (list) { list.innerHTML = html; list.hidden = filtered.length === 0; }

        const empty = $('#adgEmpty');
        if (empty) empty.hidden = filtered.length > 0;

        const msg = filtered.length
            ? `عرض ${fmt(filtered.length)} موقع إعلاني في ${fmt(streets.length)} شارع${filtering ? ' (نتائج التصفية)' : ''}`
            : (all.length ? 'لا توجد نتائج مطابقة' : 'لا توجد مواقع إعلانية مسجلة بعد');
        $('#adgCount').textContent = msg;
        const tc = $('#adgTabCount');
        if (tc) tc.textContent = fmt(filtered.length);
        $('#adgReset').disabled = !filtering;
        renderChips();
    }

    function fitTo(latlngs) {
        if (!map || !latlngs.length) return;
        const b = L.latLngBounds(latlngs);
        if (latlngs.length === 1) map.setView(b.getCenter(), 16, { animate: !reduceMotion() });
        else map.fitBounds(b.pad(0.08), { maxZoom: 16, animate: !reduceMotion() });
    }

    /* ---------------- selection sync ---------------- */
    function setActive(id, opts = {}) {
        const prev = activeId;
        activeId = id;
        if (HAS_MAP) {
            if (prev && markers.has(prev)) { markers.get(prev).setIcon(markerIcon(byId.get(prev), false)); markers.get(prev).setZIndexOffset(0); }
            if (id && markers.has(id)) { markers.get(id).setIcon(markerIcon(byId.get(id), true)); markers.get(id).setZIndexOffset(1000); }
        }
        $$('.adg-item.is-active').forEach(b => { b.classList.remove('is-active'); b.removeAttribute('aria-current'); });
        if (!id) return;
        const ad = byId.get(id);
        if (ad && !openStreets.has(ad.street_name)) {
            openStreets.add(ad.street_name);
            const sec = $$('.adg-street').find(s => s.dataset.street === ad.street_name);
            if (sec) toggleStreet(sec, true);
        }
        const btn = $(`.adg-item[data-id="${Number(id)}"]`);
        if (btn) {
            btn.classList.add('is-active');
            btn.setAttribute('aria-current', 'true');
            if (opts.fromMap) {
                const body = $('#adgList');
                const top = btn.getBoundingClientRect().top - body.getBoundingClientRect().top + body.scrollTop - 60;
                body.scrollTo({ top, behavior: reduceMotion() ? 'auto' : 'smooth' });
            }
        }
    }

    function focusAd(id) {
        const mk = markers.get(id);
        if (!mk || !map) return;
        if (isNarrow()) switchTab('map');
        setActive(id);
        const open = () => {
            if (cluster.zoomToShowLayer) cluster.zoomToShowLayer(mk, () => mk.openPopup());
            else mk.openPopup();
        };
        map.invalidateSize(false);
        const target = Math.max(map.getZoom(), 17);
        if (reduceMotion() || !mapEl.clientWidth) { map.setView(mk.getLatLng(), target, { animate: false }); open(); }
        else { map.once('moveend', open); map.flyTo(mk.getLatLng(), target, { duration: 0.8 }); }
        if (isNarrow()) mapWrap.scrollIntoView({ block: 'start', behavior: reduceMotion() ? 'auto' : 'smooth' });
    }

    function toggleStreet(sec, force) {
        const btn = $('.adg-street__toggle', sec);
        const ul = $('.adg-street__items', sec);
        const open = force !== undefined ? force : btn.getAttribute('aria-expanded') !== 'true';
        btn.setAttribute('aria-expanded', String(open));
        ul.hidden = !open;
        sec.classList.toggle('is-open', open);
        if (open) openStreets.add(sec.dataset.street); else openStreets.delete(sec.dataset.street);
    }

    $('#adgList')?.addEventListener('click', (e) => {
        const item = e.target.closest('.adg-item');
        if (item) { focusAd(Number(item.dataset.id)); return; }
        const tog = e.target.closest('.adg-street__toggle');
        if (tog) toggleStreet(tog.closest('.adg-street'));
    });

    $('#adgExpandAll')?.addEventListener('click', function () {
        expandAll = !expandAll;
        this.setAttribute('aria-pressed', String(expandAll));
        this.textContent = expandAll ? 'طيّ الكل' : 'توسيع الكل';
        if (!expandAll) { openStreets.clear(); }
        render();
    });

    /* ---------------- filters ---------------- */
    let qTimer;
    $('#adgSearch')?.addEventListener('input', function () {
        clearTimeout(qTimer);
        qTimer = setTimeout(() => { state.q = this.value.trim(); render(); }, 180);
    });
    $('#adgType')?.addEventListener('change', function () { state.type = this.value; render(); });
    $('#adgArea')?.addEventListener('change', function () { state.area = this.value; render(); });
    $('#adgSize')?.addEventListener('change', function () { state.size = this.value; render(); });
    $('#adgChips')?.addEventListener('click', (e) => {
        const c = e.target.closest('.adg-chip');
        if (!c) return;
        state.type = state.type === c.dataset.type ? '' : c.dataset.type;
        $('#adgType').value = state.type;
        render();
        $(`.adg-chip[data-type="${CSS.escape(state.type)}"]`)?.focus();
    });
    function resetFilters() {
        Object.assign(state, { q: '', type: '', area: '', size: '' });
        $('#adgSearch').value = ''; $('#adgType').value = ''; $('#adgArea').value = ''; $('#adgSize').value = '';
        render({ refit: true });
    }
    $('#adgReset')?.addEventListener('click', resetFilters);
    document.addEventListener('click', (e) => { if (e.target.closest('[data-adg-reset]')) resetFilters(); });

    /* ---------------- tabs (mobile) ---------------- */
    function switchTab(which) {
        layout.dataset.view = which;
        $$('.adg-tab').forEach(t => {
            const on = t.dataset.adgTab === which;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', String(on));
            t.tabIndex = on ? 0 : -1;
        });
        if (which === 'map' && map) { map.invalidateSize(false); setTimeout(() => map.invalidateSize(), 60); }
    }
    $$('.adg-tab').forEach(t => {
        t.addEventListener('click', () => switchTab(t.dataset.adgTab));
        t.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
            const next = t.dataset.adgTab === 'map' ? 'list' : 'map';
            switchTab(next);
            $(`.adg-tab[data-adg-tab="${next}"]`).focus();
        });
    });

    /* ---------------- map tools ---------------- */
    let meMarker = null;
    function locate(cb) {
        if (!navigator.geolocation) { toast('المتصفح لا يدعم تحديد الموقع.', 'error'); return; }
        const btn = $('#adgLocate');
        btn?.classList.add('is-busy');
        navigator.geolocation.getCurrentPosition((pos) => {
            btn?.classList.remove('is-busy');
            const ll = [pos.coords.latitude, pos.coords.longitude];
            if (cb) { cb(ll); return; }
            if (!map) return;
            if (meMarker) map.removeLayer(meMarker);
            meMarker = L.layerGroup([
                L.circle(ll, { radius: Math.min(pos.coords.accuracy || 50, 800), className: 'adg-me-acc', weight: 1 }),
                L.circleMarker(ll, { radius: 8, className: 'adg-me', weight: 3 }).bindTooltip('موقعك الحالي', { direction: 'top' }),
            ]).addTo(map);
            map.setView(ll, 15, { animate: !reduceMotion() });
        }, (err) => {
            btn?.classList.remove('is-busy');
            toast(err.code === 1 ? 'لم يتم السماح بالوصول إلى موقعك.' : 'تعذّر تحديد موقعك الحالي.', 'error');
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    }
    $('#adgLocate')?.addEventListener('click', () => locate());

    function setFullscreen(on) {
        mapWrap.classList.toggle('is-fullscreen', on);
        document.body.classList.toggle('adg-noscroll', on);
        const b = $('#adgFullscreen');
        b.setAttribute('aria-pressed', String(on));
        b.setAttribute('aria-label', on ? 'الخروج من ملء الشاشة' : 'عرض الخريطة بملء الشاشة');
        b.title = on ? 'الخروج من ملء الشاشة' : 'ملء الشاشة';
        b.innerHTML = `<i class="fa-solid ${on ? 'fa-compress' : 'fa-expand'}" aria-hidden="true"></i>`;
        if (map) { on ? map.scrollWheelZoom.enable() : map.scrollWheelZoom.disable(); setTimeout(() => map.invalidateSize(), 60); }
    }
    $('#adgFullscreen')?.addEventListener('click', () => setFullscreen(!mapWrap.classList.contains('is-fullscreen')));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mapWrap?.classList.contains('is-fullscreen') && !document.querySelector('.modal.show')) setFullscreen(false);
    });

    $('#adgHome')?.addEventListener('click', () => {
        if (!map) return;
        map.closePopup();
        const lls = all.filter(a => matches(a)).map(a => [a.lat, a.lng]);
        lls.length ? fitTo(lls) : map.setView(DEFAULT_VIEW.center, DEFAULT_VIEW.zoom);
    });

    $('#adgLayer')?.addEventListener('click', function () {
        baseMode = baseMode === 'sat' ? 'map' : 'sat';
        const on = baseMode === 'sat';
        this.setAttribute('aria-pressed', String(on));
        this.setAttribute('aria-label', on ? 'التبديل إلى الخريطة العادية' : 'التبديل إلى صور الأقمار الصناعية');
        this.title = on ? 'الخريطة العادية' : 'صور الأقمار الصناعية';
        this.innerHTML = `<i class="fa-solid ${on ? 'fa-map' : 'fa-satellite'}" aria-hidden="true"></i>`;
        applyBase();
    });

    $('#adgLegendToggle')?.addEventListener('click', function () {
        const open = this.getAttribute('aria-expanded') !== 'true';
        this.setAttribute('aria-expanded', String(open));
        $('#adgLegend').classList.toggle('is-collapsed', !open);
    });
    // Legend starts collapsed (the type chips above the map already carry the same colours).
    $('#adgLegendToggle')?.setAttribute('aria-expanded', 'false');
    $('#adgLegend')?.classList.add('is-collapsed');

    // Distance measurement
    let measuring = false, mPts = [], mLine = null, mDots = [];
    function clearMeasure() {
        mPts = [];
        if (mLine) { map.removeLayer(mLine); mLine = null; }
        mDots.forEach(d => map.removeLayer(d)); mDots = [];
        $('#adgMeasureVal').textContent = '0 م';
    }
    function setMeasuring(on) {
        measuring = on;
        $('#adgMeasure')?.setAttribute('aria-pressed', String(on));
        $('#adgMeasure')?.classList.toggle('is-on', on);
        $('#adgMeasureBar').hidden = !on;
        mapEl.classList.toggle('is-picking', on);
        if (!on) clearMeasure();
    }
    $('#adgMeasure')?.addEventListener('click', () => map && setMeasuring(!measuring));
    $('#adgMeasureClear')?.addEventListener('click', clearMeasure);
    $('#adgMeasureClose')?.addEventListener('click', () => setMeasuring(false));
    if (map) {
        map.on('click', (e) => {
            if (!measuring) return;
            mPts.push(e.latlng);
            mDots.push(L.circleMarker(e.latlng, { radius: 5, className: 'adg-measure-dot', weight: 2 }).addTo(map));
            if (mPts.length > 1) {
                if (mLine) map.removeLayer(mLine);
                mLine = L.polyline(mPts, { className: 'adg-measure-line', weight: 3, dashArray: '8 6' }).addTo(map);
                const d = mPts.reduce((s, p, i) => i ? s + p.distanceTo(mPts[i - 1]) : 0, 0);
                $('#adgMeasureVal').textContent = d < 1000 ? fmt(Math.round(d)) + ' م' : fmt(Number((d / 1000).toFixed(2))) + ' كم';
            }
        });
    }

    // Popup buttons (event delegation — no inline handlers)
    mapEl?.addEventListener('click', (e) => {
        const b = e.target.closest('[data-act]');
        if (!b) return;
        const id = Number(b.dataset.id);
        const act = b.dataset.act;
        if (act === 'share') {
            const url = new URL(location.href);
            url.search = '?point=' + id;
            url.hash = '';
            (navigator.clipboard ? navigator.clipboard.writeText(url.toString()) : Promise.reject())
                .then(() => toast('تم نسخ رابط الموقع.'))
                .catch(() => toast('تعذّر النسخ — الرابط: ' + url.toString(), 'error'));
        } else if (act === 'edit') {
            openForm(byId.get(id));
        } else if (act === 'delete') {
            askDelete(byId.get(id));
        }
    });

    /* ---------------- add / edit (signed-in users) ---------------- */
    const form = $('#adgForm');
    const formModalEl = $('#adgFormModal');
    let formModal = null, pickMap = null, pickMarker = null, editing = null, pendingLatLng = null;
    const fieldIds = { street_name: 'adgFStreet', type: 'adgFType', height: 'adgFHeight', size: 'adgFSize', description: 'adgFDesc', lat: 'adgFLat', lng: 'adgFLng' };

    function csrf() {
        return form?.querySelector('input[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function clearErrors() {
        Object.values(fieldIds).forEach(id => { const el = $('#' + id); el?.classList.remove('is-invalid'); el?.removeAttribute('aria-invalid'); const fb = $('#' + id + 'Err'); if (fb) fb.textContent = ''; });
        const al = $('#adgFormAlert'); if (al) { al.hidden = true; al.textContent = ''; }
    }
    function showErrors(errors, summary) {
        let first = null;
        Object.entries(errors || {}).forEach(([k, msgs]) => {
            const id = fieldIds[k];
            if (!id) return;
            const el = $('#' + id);
            el?.classList.add('is-invalid');
            el?.setAttribute('aria-invalid', 'true');
            const fb = $('#' + id + 'Err');
            if (fb) fb.textContent = Array.isArray(msgs) ? msgs[0] : String(msgs);
            first = first || el;
        });
        const al = $('#adgFormAlert');
        if (al && summary) { al.hidden = false; al.textContent = summary; }
        first?.focus();
    }

    function setPick(lat, lng, pan) {
        lat = Number(Number(lat).toFixed(7)); lng = Number(Number(lng).toFixed(7));
        $('#adgFLat').value = lat; $('#adgFLng').value = lng;
        if (pickMap) {
            if (!pickMarker) {
                pickMarker = L.marker([lat, lng], { draggable: true, keyboard: true, title: 'موقع الإعلان — اسحب لتعديله', icon: markerIcon({ type: $('#adgFType').value }, true) }).addTo(pickMap);
                pickMarker.on('dragend', () => { const p = pickMarker.getLatLng(); setPick(p.lat, p.lng, false); });
            } else {
                pickMarker.setLatLng([lat, lng]);
            }
            if (pan) pickMap.setView([lat, lng], Math.max(pickMap.getZoom(), 16), { animate: false });
        }
    }

    function openForm(ad, latlng) {
        if (!form || !window.bootstrap) return;
        editing = ad || null;
        form.reset();
        clearErrors();
        $('#adgFormTitle').textContent = editing ? 'تعديل الموقع الإعلاني' : 'إضافة موقع إعلاني';
        $('.adg-submit-label').textContent = editing ? 'حفظ التعديلات' : 'حفظ الموقع';
        const typeSel = $('#adgFType');
        $$('option[data-custom]', typeSel).forEach(o => o.remove());
        if (editing) {
            $('#adgFStreet').value = editing.street_name || '';
            if (![...typeSel.options].some(o => o.value === editing.type)) {
                const o = document.createElement('option'); o.value = editing.type; o.textContent = editing.type; o.dataset.custom = '1'; typeSel.appendChild(o);
            }
            typeSel.value = editing.type;
            $('#adgFHeight').value = editing.height ?? '';
            $('#adgFSize').value = editing.size ?? '';
            $('#adgFDesc').value = editing.description ?? '';
            pendingLatLng = [editing.lat, editing.lng];
        } else {
            // Clicked spot → current map centre when zoomed in → Kafr El-Sheikh city centre.
            pendingLatLng = latlng || (map && map.getZoom() >= 14 ? [map.getCenter().lat, map.getCenter().lng] : (CFG.areas['كفر الشيخ'] || DEFAULT_VIEW.center));
        }
        updateDescCount();
        map?.closePopup();
        formModal = window.bootstrap.Modal.getOrCreateInstance(formModalEl);
        formModal.show();
    }

    function updateDescCount() {
        const c = $('#adgFDescCount');
        if (c) c.textContent = `${fmt(($('#adgFDesc')?.value || '').length)} / ${fmt(1000)}`;
    }
    $('#adgFDesc')?.addEventListener('input', updateDescCount);
    $('#adgFType')?.addEventListener('change', function () { pickMarker?.setIcon(markerIcon({ type: this.value }, true)); });
    ['adgFLat', 'adgFLng'].forEach(id => $('#' + id)?.addEventListener('change', () => {
        const lat = parseFloat($('#adgFLat').value), lng = parseFloat($('#adgFLng').value);
        if (isFinite(lat) && isFinite(lng)) setPick(lat, lng, true);
    }));
    $('#adgPickLocate')?.addEventListener('click', () => locate((ll) => setPick(ll[0], ll[1], true)));

    formModalEl?.addEventListener('shown.bs.modal', () => {
        if (window.L && !pickMap) {
            pickMap = L.map('adgPickMap', { zoomControl: true, attributionControl: false, scrollWheelZoom: true });
            tiles.osm().addTo(pickMap);
            pickMap.on('click', (e) => setPick(e.latlng.lat, e.latlng.lng, false));
        }
        if (pickMap) {
            pickMap.invalidateSize();
            pickMap.setView(pendingLatLng, editing ? 17 : Math.max(map ? map.getZoom() : 15, 15), { animate: false });
            if (pickMarker) pickMarker.setIcon(markerIcon({ type: $('#adgFType').value }, true));
        }
        setPick(pendingLatLng[0], pendingLatLng[1], false);
        $('#adgFStreet').focus();
    });

    function validateClient(data) {
        const errs = {};
        const bad = /[<>]/;
        if (!data.street_name) errs.street_name = ['اسم الشارع مطلوب.'];
        else if (bad.test(data.street_name)) errs.street_name = ['لا يُسمح باستخدام الرمزين < و > في اسم الشارع.'];
        if (!data.type) errs.type = ['اختر نوع الإعلان.'];
        if (data.size && bad.test(data.size)) errs.size = ['لا يُسمح باستخدام الرمزين < و >.'];
        if (data.description && bad.test(data.description)) errs.description = ['لا يُسمح باستخدام الرمزين < و > في الوصف.'];
        if (data.height !== null && (!isFinite(Number(data.height)) || Number(data.height) < 0)) errs.height = ['أدخل ارتفاعاً صحيحاً (صفر أو أكثر).'];
        if (data.lat === null || !isFinite(Number(data.lat)) || Math.abs(Number(data.lat)) > 90) errs.lat = ['حدد الموقع على الخريطة.'];
        if (data.lng === null || !isFinite(Number(data.lng)) || Math.abs(Number(data.lng)) > 180) errs.lng = ['حدد الموقع على الخريطة.'];
        return errs;
    }

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        clearErrors();
        const val = (name) => { const v = form.elements[name]?.value?.trim(); return v === '' || v === undefined ? null : v; };
        const data = {
            street_name: val('street_name'), type: val('type'), height: val('height'), size: val('size'),
            description: val('description'), lat: val('lat'), lng: val('lng'),
        };
        const errs = validateClient(data);
        if (Object.keys(errs).length) { showErrors(errs, 'يرجى مراجعة الحقول المظللة.'); return; }

        const btn = $('#adgFormSubmit');
        btn.disabled = true; $('.spinner-border', btn).classList.remove('d-none');
        const url = editing ? `${CFG.pointsUrl}/${Number(editing.id)}` : CFG.pointsUrl;
        fetch(url, {
            method: editing ? 'PUT' : 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify(data),
        }).then(async (r) => {
            const body = await r.json().catch(() => ({}));
            if (r.status === 422) { showErrors(body.errors, 'لم يتم الحفظ — راجع الحقول المظللة.'); return null; }
            if (r.status === 403) throw new Error('ليس لديك صلاحية تعديل هذا الموقع.');
            if (r.status === 419) throw new Error('انتهت صلاحية الجلسة — أعد تحميل الصفحة ثم حاول مجدداً.');
            if (r.status === 429) throw new Error('محاولات كثيرة متتالية — انتظر دقيقة ثم أعد المحاولة.');
            if (r.status === 401) throw new Error('يجب تسجيل الدخول أولاً.');
            if (!r.ok) throw new Error('تعذّر الحفظ (' + r.status + ').');
            return body;
        }).then((saved) => {
            if (!saved) return;
            const wasEdit = !!editing;
            formModal?.hide();
            toast(wasEdit ? 'تم حفظ التعديلات.' : 'تمت إضافة الموقع الإعلاني بنجاح.');
            const r = mapWrap.getBoundingClientRect();
            if (!isNarrow() && (r.top < 0 || r.bottom > window.innerHeight)) mapWrap.scrollIntoView({ block: 'center', behavior: reduceMotion() ? 'auto' : 'smooth' });
            return loadPoints().then(() => { if (saved.id && byId.has(Number(saved.id))) focusAd(Number(saved.id)); });
        }).catch((err) => {
            const al = $('#adgFormAlert');
            if (al) { al.hidden = false; al.textContent = err.message; }
        }).finally(() => { btn.disabled = false; $('.spinner-border', btn).classList.add('d-none'); });
    });

    // Delete
    let deleting = null;
    function askDelete(ad) {
        if (!ad || !window.bootstrap) return;
        deleting = ad;
        $('#adgDeleteName').textContent = ad._meta.label + ' — ' + ad.street_name;
        map?.closePopup();
        window.bootstrap.Modal.getOrCreateInstance($('#adgDeleteModal')).show();
    }
    $('#adgDeleteConfirm')?.addEventListener('click', function () {
        if (!deleting) return;
        this.disabled = true;
        fetch(`${CFG.pointsUrl}/${Number(deleting.id)}`, {
            method: 'DELETE', credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
        }).then(r => {
            if (r.status === 403) throw new Error('ليس لديك صلاحية حذف هذا الموقع.');
            if (!r.ok) throw new Error('تعذّر الحذف (' + r.status + ').');
            window.bootstrap.Modal.getOrCreateInstance($('#adgDeleteModal')).hide();
            toast('تم حذف الموقع من الدليل.');
            activeId = null;
            return loadPoints();
        }).catch(err => toast(err.message, 'error'))
          .finally(() => { this.disabled = false; deleting = null; });
    });

    // Entry points: hero / empty-state buttons, right-click on the map
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-adg-add]')) { e.preventDefault(); openForm(null); }
    });
    if (map && CFG.loggedIn) {
        map.on('contextmenu', (e) => { if (!measuring) openForm(null, [e.latlng.lat, e.latlng.lng]); });
    }

    if (HAS_MAP || $('#adgList')) loadPoints();
})();
</script>
