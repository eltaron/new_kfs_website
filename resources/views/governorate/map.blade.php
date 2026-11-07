@extends('layouts.app')
@section('title', 'الخريطة التفاعلية للمحافظة')
@section('description', 'استكشف المعالم السياحية والمشروعات والفرص الاستثمارية والخدمات في محافظة كفر الشيخ على خريطة تفاعلية واحدة.')

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('css/kfs-map.css') }}?v={{ @filemtime(public_path('css/kfs-map.css')) }}" />
@endpush

@section('content')
    <main class="kmap" id="kmap" data-panel="peek">
        {{-- ===== Side panel (bottom sheet on mobile) ===== --}}
        <aside class="kmap__panel" id="kmapPanel" aria-label="البحث والتصفية">
            <button type="button" class="kmap__grip" id="kmapGrip" aria-controls="kmapPanel" aria-expanded="false">
                <span class="kmap__grip-bar" aria-hidden="true"></span>
                <span class="visually-hidden">إظهار أو إخفاء القائمة</span>
            </button>

            <header class="kmap__head">
                <nav aria-label="مسار التنقل" class="kmap__crumbs">
                    <a href="{{ route('home') }}">الرئيسية</a>
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    <span aria-current="page">الخريطة التفاعلية</span>
                </nav>
                <h1 class="kmap__title">الخريطة التفاعلية للمحافظة</h1>
                <p class="kmap__lead">المعالم والمشروعات والخدمات في كفر الشيخ على خريطة واحدة.</p>
            </header>

            <div class="kmap__search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <label for="kmapSearch" class="visually-hidden">ابحث عن مكان</label>
                <input type="search" id="kmapSearch" placeholder="ابحث باسم المكان…" autocomplete="off">
                <button type="button" class="kmap__search-clear" id="kmapSearchClear" hidden aria-label="مسح البحث">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            <div class="kmap__groups" role="group" aria-label="نوع المكان">
                <button type="button" class="kmap__group is-active" data-group="all" aria-pressed="true">
                    <span class="kmap__group-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                    <span class="kmap__group-label">الكل</span>
                    <span class="kmap__group-count">{{ $points->count() }}</span>
                </button>
                @foreach ($groups as $type => $group)
                    <button type="button" class="kmap__group" data-group="{{ $type }}" aria-pressed="false" style="--c: var(--kmap-{{ $type }})">
                        <span class="kmap__group-icon"><i class="{{ $group['icon'] }}" aria-hidden="true"></i></span>
                        <span class="kmap__group-label">{{ $group['label'] }}</span>
                        <span class="kmap__group-count">{{ $group['count'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="kmap__chips" id="kmapChips" role="group" aria-label="تصنيف فرعي" hidden></div>

            <div class="kmap__results">
                <p class="kmap__count" id="kmapCount" aria-live="polite"></p>
                <ul class="kmap__list" id="kmapList"></ul>
                <div class="kmap__empty" id="kmapEmpty" hidden>
                    <i class="fa-solid fa-map-location" aria-hidden="true"></i>
                    <p>لا توجد أماكن مطابقة.</p>
                    <button type="button" class="kmap__link-btn" id="kmapReset">عرض كل الأماكن</button>
                </div>
            </div>
        </aside>

        {{-- ===== Map ===== --}}
        <div class="kmap__stage">
            <div class="kmap__canvas" id="kmapCanvas" role="region" aria-label="خريطة محافظة كفر الشيخ"></div>

            <div class="kmap__tools">
                <div class="kmap__seg" role="group" aria-label="نوع الخريطة">
                    <button type="button" class="is-active" data-base="streets" aria-pressed="true">
                        <i class="fa-solid fa-map" aria-hidden="true"></i><span>خريطة</span>
                    </button>
                    <button type="button" data-base="satellite" aria-pressed="false">
                        <i class="fa-solid fa-satellite" aria-hidden="true"></i><span>قمر صناعي</span>
                    </button>
                </div>
                <button type="button" class="kmap__tool" id="kmapFit" title="عرض المحافظة كاملة" aria-label="عرض المحافظة كاملة">
                    <i class="fa-solid fa-expand" aria-hidden="true"></i>
                </button>
                <button type="button" class="kmap__tool" id="kmapLocate" title="موقعي" aria-label="تحديد موقعي">
                    <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                </button>
            </div>

            <div class="kmap__legend" aria-hidden="true">
                @foreach ($groups as $type => $group)
                    <span style="--c: var(--kmap-{{ $type }})"><i></i>{{ $group['label'] }}</span>
                @endforeach
            </div>

            {{-- Selected place --}}
            <article class="kmap__card" id="kmapCard" hidden aria-live="polite">
                <button type="button" class="kmap__card-close" id="kmapCardClose" aria-label="إغلاق">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
                <div class="kmap__card-media" id="kmapCardMedia"></div>
                <div class="kmap__card-body">
                    <span class="kmap__card-cat" id="kmapCardCat"></span>
                    <h2 class="kmap__card-title" id="kmapCardTitle"></h2>
                    <p class="kmap__card-text" id="kmapCardText"></p>
                    <div class="kmap__card-actions">
                        <a class="kmap__btn kmap__btn--primary" id="kmapCardMore" href="#">
                            <span>عرض التفاصيل</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        </a>
                        <a class="kmap__btn" id="kmapCardDir" href="#" target="_blank" rel="noopener">
                            <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i><span>الاتجاهات</span>
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js" crossorigin="anonymous"></script>
    <script type="application/json" id="kmapData">@json(['points' => $points->values(), 'groups' => $groups])</script>
    <script>
    (function () {
        const DATA = JSON.parse(document.getElementById('kmapData').textContent);
        const POINTS = DATA.points;
        const GROUPS = DATA.groups;
        const $ = (id) => document.getElementById(id);
        const root = $('kmap');

        // ---------- helpers ----------
        const norm = (s) => (s || '').toString()
            .replace(/[ً-ٰٟـ]/g, '')   // tashkeel + tatweel
            .replace(/[أإآٱ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه')
            .toLowerCase().trim();
        const el = (tag, cls, text) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; };
        const icon = (cls) => { const i = el('i', cls); i.setAttribute('aria-hidden', 'true'); return i; };
        const groupIcon = (type) => (GROUPS[type] && GROUPS[type].icon) || 'fa-solid fa-location-dot';
        const mobile = () => window.matchMedia('(max-width: 991.98px)').matches;
        POINTS.forEach(p => { p._q = norm(p.name + ' ' + p.category + ' ' + (p.address || '')); });

        // ---------- map ----------
        const map = L.map('kmapCanvas', { zoomControl: false, attributionControl: true }).setView([31.25, 30.85], 10);
        L.control.zoom({ position: 'topleft', zoomInTitle: 'تكبير', zoomOutTitle: 'تصغير' }).addTo(map);

        const bases = {
            // OpenStreetMap standard tiles: no API key, Arabic local names.
            streets: L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            }),
            satellite: L.layerGroup([
                L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: 'Tiles &copy; Esri' }),
                L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, opacity: .85 })
            ])
        };
        let base = 'streets';
        bases.streets.addTo(map);

        const cluster = L.markerClusterGroup({
            showCoverageOnHover: false,
            maxClusterRadius: 46,
            spiderfyOnMaxZoom: true,
            iconCreateFunction: (c) => L.divIcon({
                html: '<span>' + c.getChildCount() + '</span>',
                className: 'kmap-cluster',
                iconSize: L.point(42, 42)
            })
        });
        map.addLayer(cluster);

        const markers = {};
        POINTS.forEach(p => {
            const m = L.marker([p.lat, p.lng], {
                title: p.name,
                keyboard: true,
                icon: L.divIcon({
                    className: 'kmap-pin-wrap',
                    html: '<span class="kmap-pin kmap-pin--' + p.type + '"><i class="' + groupIcon(p.type) + '" aria-hidden="true"></i></span>',
                    iconSize: [34, 42], iconAnchor: [17, 40]
                })
            });
            m.on('click', () => select(p.id, { fly: false }));
            markers[p.id] = m;
        });

        // ---------- state ----------
        const state = { group: 'all', cat: null, q: '', selected: null };

        function filtered() {
            const q = norm(state.q);
            return POINTS.filter(p =>
                (state.group === 'all' || p.type === state.group) &&
                (!state.cat || p.category === state.cat) &&
                (!q || p._q.includes(q)));
        }

        function fitTo(list) {
            if (!list.length) return;
            const b = L.latLngBounds(list.map(p => [p.lat, p.lng]));
            map.fitBounds(b.pad(0.15), { maxZoom: 14, paddingTopLeft: [0, 0], paddingBottomRight: mobile() ? [0, 120] : [0, 0] });
        }

        function renderChips() {
            const box = $('kmapChips');
            box.replaceChildren();
            const g = GROUPS[state.group];
            const cats = g ? Object.entries(g.categories) : [];
            if (cats.length < 2) { box.hidden = true; return; }
            box.hidden = false;
            const make = (label, value, count) => {
                const b = el('button', 'kmap__chip' + (state.cat === value ? ' is-active' : ''));
                b.type = 'button';
                b.setAttribute('aria-pressed', state.cat === value ? 'true' : 'false');
                b.append(el('span', null, label));
                if (count != null) b.append(el('small', null, count));
                b.addEventListener('click', () => { state.cat = value; update(true); });
                return b;
            };
            box.append(make('الكل', null, null));
            cats.forEach(([name, count]) => box.append(make(name, name, count)));
        }

        function renderList(list) {
            const ul = $('kmapList');
            ul.replaceChildren();
            list.forEach(p => {
                const li = el('li');
                const b = el('button', 'kmap__item' + (state.selected === p.id ? ' is-active' : ''));
                b.type = 'button';
                b.dataset.id = p.id;
                b.style.setProperty('--c', 'var(--kmap-' + p.type + ')');
                const thumb = el('span', 'kmap__item-thumb');
                if (p.image) {
                    const img = el('img'); img.src = p.image; img.alt = ''; img.loading = 'lazy';
                    img.onerror = () => { img.remove(); thumb.append(icon(groupIcon(p.type))); };
                    thumb.append(img);
                } else {
                    thumb.append(icon(groupIcon(p.type)));
                }
                const body = el('span', 'kmap__item-body');
                body.append(el('strong', null, p.name), el('small', null, p.category));
                b.append(thumb, body, icon('fa-solid fa-chevron-left kmap__item-go'));
                b.addEventListener('click', () => select(p.id, { fly: true }));
                li.append(b);
                ul.append(li);
            });
            const n = list.length;
            $('kmapCount').textContent = n === 0 ? '' : (n === 1 ? 'مكان واحد' : n === 2 ? 'مكانان' : n + (n <= 10 ? ' أماكن' : ' مكانًا'));
            $('kmapEmpty').hidden = n !== 0;
        }

        function update(refit) {
            document.querySelectorAll('.kmap__group').forEach(b => {
                const on = b.dataset.group === state.group;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            const list = filtered();
            cluster.clearLayers();
            cluster.addLayers(list.map(p => markers[p.id]));
            if (state.selected != null && !list.some(p => p.id === state.selected)) closeCard();
            renderChips();
            renderList(list);
            if (refit) fitTo(list);
        }

        // ---------- selection card ----------
        function select(id, opts) {
            const p = POINTS.find(x => x.id === id);
            if (!p) return;
            if (state.selected != null && markers[state.selected]) markers[state.selected].getElement()?.classList.remove('is-selected');
            state.selected = id;

            const media = $('kmapCardMedia');
            media.replaceChildren();
            media.style.setProperty('--c', 'var(--kmap-' + p.type + ')');
            if (p.image) { const img = el('img'); img.src = p.image; img.alt = ''; media.append(img); }
            else media.append(icon(groupIcon(p.type)));
            media.classList.toggle('has-img', !!p.image);

            const cat = $('kmapCardCat');
            cat.textContent = p.category;
            cat.style.setProperty('--c', 'var(--kmap-' + p.type + ')');
            $('kmapCardTitle').textContent = p.name;
            $('kmapCardText').textContent = p.address || '';
            $('kmapCardText').hidden = !p.address;
            const more = $('kmapCardMore');
            more.hidden = !p.url;
            if (p.url) more.href = p.url;
            $('kmapCardDir').href = 'https://www.google.com/maps/dir/?api=1&destination=' + p.lat + ',' + p.lng;
            $('kmapCard').hidden = false;
            root.classList.add('has-card');

            document.querySelectorAll('.kmap__item').forEach(b => b.classList.toggle('is-active', +b.dataset.id === id));
            const m = markers[id];
            const reveal = () => m.getElement()?.classList.add('is-selected');
            if (opts && opts.fly) {
                cluster.zoomToShowLayer(m, () => { map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 15), { duration: .8 }); reveal(); });
            } else reveal();
            if (mobile()) setPanel('peek');
        }

        function closeCard() {
            if (state.selected != null && markers[state.selected]) markers[state.selected].getElement()?.classList.remove('is-selected');
            state.selected = null;
            $('kmapCard').hidden = true;
            root.classList.remove('has-card');
            document.querySelectorAll('.kmap__item.is-active').forEach(b => b.classList.remove('is-active'));
        }

        // ---------- mobile sheet ----------
        function setPanel(mode) {
            root.dataset.panel = mode;
            $('kmapGrip').setAttribute('aria-expanded', mode === 'open' ? 'true' : 'false');
            setTimeout(() => map.invalidateSize(), 320);
        }
        $('kmapGrip').addEventListener('click', () => setPanel(root.dataset.panel === 'open' ? 'peek' : 'open'));

        // ---------- events ----------
        document.querySelectorAll('.kmap__group').forEach(b => b.addEventListener('click', () => {
            state.group = b.dataset.group; state.cat = null; update(true);
        }));
        let t;
        $('kmapSearch').addEventListener('input', (e) => {
            state.q = e.target.value;
            $('kmapSearchClear').hidden = !state.q;
            clearTimeout(t); t = setTimeout(() => update(true), 160);
        });
        $('kmapSearch').addEventListener('focus', () => { if (mobile()) setPanel('open'); });
        $('kmapSearchClear').addEventListener('click', () => {
            $('kmapSearch').value = ''; state.q = ''; $('kmapSearchClear').hidden = true; update(true); $('kmapSearch').focus();
        });
        $('kmapReset').addEventListener('click', () => {
            $('kmapSearch').value = ''; Object.assign(state, { group: 'all', cat: null, q: '' }); $('kmapSearchClear').hidden = true; update(true);
        });
        $('kmapCardClose').addEventListener('click', closeCard);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && state.selected != null) closeCard(); });
        $('kmapFit').addEventListener('click', () => fitTo(filtered().length ? filtered() : POINTS));

        document.querySelectorAll('[data-base]').forEach(b => b.addEventListener('click', () => {
            if (b.dataset.base === base) return;
            map.removeLayer(bases[base]);
            base = b.dataset.base;
            bases[base].addTo(map);
            root.classList.toggle('is-satellite', base === 'satellite');
            document.querySelectorAll('[data-base]').forEach(x => {
                const on = x === b; x.classList.toggle('is-active', on); x.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        }));

        let me;
        $('kmapLocate').addEventListener('click', () => {
            if (!navigator.geolocation) return;
            const btn = $('kmapLocate');
            btn.classList.add('is-busy');
            navigator.geolocation.getCurrentPosition((pos) => {
                btn.classList.remove('is-busy');
                const ll = [pos.coords.latitude, pos.coords.longitude];
                if (me) me.setLatLng(ll); else me = L.circleMarker(ll, { radius: 8, weight: 3, color: '#fff', fillColor: '#1a73e8', fillOpacity: 1 }).addTo(map);
                map.flyTo(ll, 14, { duration: .8 });
            }, () => btn.classList.remove('is-busy'), { enableHighAccuracy: true, timeout: 10000 });
        });

        update(false);
        fitTo(POINTS);
        window.addEventListener('resize', () => map.invalidateSize());
    })();
    </script>
@endpush
