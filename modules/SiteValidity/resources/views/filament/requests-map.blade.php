<x-filament-panels::page>
    {{-- Leaflet محمّل من رأس اللوحة (SiteValidityPanelProvider) --}}
    {{ $this->form }}

    {{-- مؤشرات الحالات (تعمل كفلتر سريع) --}}
    <div class="svm-kpis">
        <button type="button" class="svm-kpi svm-kpi--total {{ $activeStatuses ? '' : 'is-on' }}"
            wire:click="$set('filters.statuses', [])" style="--c:#26302D">
            <span class="svm-kpi__n">{{ number_format($data['total']) }}</span>
            <span class="svm-kpi__l">إجمالي الطلبات</span>
        </button>
        @foreach ($statusShort as $key => $label)
            @php $n = $data['statuses'][$key] ?? 0; @endphp
            <button type="button" wire:click="toggleStatus('{{ $key }}')"
                class="svm-kpi {{ in_array($key, $activeStatuses, true) ? 'is-on' : '' }} {{ $n ? '' : 'is-zero' }}"
                style="--c: {{ $statusHex[$key] }}" aria-pressed="{{ in_array($key, $activeStatuses, true) ? 'true' : 'false' }}">
                <span class="svm-kpi__n">{{ number_format($n) }}</span>
                <span class="svm-kpi__l">{{ $label }}</span>
            </button>
        @endforeach
    </div>

    <div class="svm-grid">
        {{-- الخريطة --}}
        <section class="svm-card svm-map-card" aria-label="خريطة المراكز">
            <div wire:ignore
                x-data="svRequestsMap(@js($data), @js($mapConfig), @js($centreIds), @js($statusHex), @js($statusShort))"
                class="svm-map-wrap">
                <div x-ref="map" class="svm-map"></div>
                <div class="svm-legend" x-ref="legend"></div>
            </div>
            <p class="svm-note">اضغط على أي مركز لعرض توزيع طلباته وتصفية الصفحة به. الحدود تقريبية (geoBoundaries — CC BY 4.0)؛ بلطيم ومصيف بلطيم وسيدي غازي ومسير تظهر كنقاط.</p>
        </section>

        {{-- ترتيب المراكز --}}
        <aside class="svm-card svm-side" aria-label="المراكز">
            <h3 class="svm-side__title">
                @if ($data['selected'])
                    <button type="button" wire:click="setCentre(null)" class="svm-back" title="كل المراكز">&rarr;</button>
                    {{ collect($data['centres'])->firstWhere('id', $data['selected'])['name'] ?? 'المركز' }}
                @else
                    الطلبات حسب المركز
                @endif
            </h3>

            @php $max = max(1, collect($data['centres'])->max('total') ?? 1); @endphp
            @if (! $data['selected'])
                <ol class="svm-rank">
                    @forelse ($data['centres'] as $c)
                        <li>
                            <button type="button" wire:click="setCentre({{ $c['id'] }})" class="svm-rank__row">
                                <span class="svm-rank__name">{{ $c['name'] }}</span>
                                <span class="svm-rank__n">{{ $c['total'] }}</span>
                                <span class="svm-stack" aria-hidden="true">
                                    @foreach ($statusHex as $s => $hex)
                                        @if (! empty($c['statuses'][$s]))
                                            <i style="width: {{ $c['statuses'][$s] / $max * 100 }}%; background: {{ $hex }}" title="{{ $statusShort[$s] }}: {{ $c['statuses'][$s] }}"></i>
                                        @endif
                                    @endforeach
                                </span>
                            </button>
                        </li>
                    @empty
                        <li class="svm-empty">لا توجد طلبات مطابقة.</li>
                    @endforelse
                </ol>
            @else
                @php $sel = collect($data['centres'])->firstWhere('id', $data['selected']); @endphp
                <dl class="svm-facts">
                    <div><dt>الطلبات</dt><dd>{{ $sel['total'] ?? 0 }}</dd></div>
                    <div><dt>مدفوعة</dt><dd>{{ $sel['paid'] ?? 0 }}</dd></div>
                    <div><dt>الإيراد</dt><dd>{{ number_format($sel['revenue'] ?? 0) }} <small>ج.م</small></dd></div>
                </dl>
                <h4 class="svm-sub">أكثر القرى طلباً</h4>
                @php $vmax = max(1, $villages->max('total') ?? 1); @endphp
                <ol class="svm-rank svm-rank--villages">
                    @forelse ($villages as $v)
                        <li class="svm-rank__row is-static">
                            <span class="svm-rank__name">{{ $v['name'] }}</span>
                            <span class="svm-rank__n">{{ $v['total'] }}</span>
                            <span class="svm-stack"><i style="width: {{ $v['total'] / $vmax * 100 }}%; background: #00788A"></i></span>
                        </li>
                    @empty
                        <li class="svm-empty">لا توجد طلبات في هذا المركز.</li>
                    @endforelse
                </ol>
            @endif
        </aside>
    </div>

    {{ $this->table }}

    <style>
        .svm-kpis { display: flex; flex-wrap: wrap; gap: .5rem; }
        .svm-kpi { display: flex; flex-direction: column; align-items: flex-start; gap: .1rem; min-width: 7.5rem; padding: .55rem .8rem;
            border-radius: .75rem; border: 1px solid color-mix(in srgb, var(--c) 28%, transparent); border-inline-start: 4px solid var(--c);
            background: color-mix(in srgb, var(--c) 7%, #fff); color: var(--c); text-align: start; transition: background-color .15s, transform .15s; }
        .svm-kpi:hover { transform: translateY(-1px); background: color-mix(in srgb, var(--c) 13%, #fff); }
        .svm-kpi__n { font-size: 1.35rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .svm-kpi__l { font-size: .75rem; font-weight: 700; }
        .svm-kpi.is-on { background: var(--c); color: #fff; }
        .svm-kpi.is-zero:not(.is-on) { opacity: .55; }
        .dark .svm-kpi { background: color-mix(in srgb, var(--c) 18%, #18181b); color: color-mix(in srgb, var(--c) 45%, #fff); }
        .dark .svm-kpi.is-on { background: var(--c); color: #fff; }

        .svm-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1rem; align-items: start; }
        @media (max-width: 1100px) { .svm-grid { grid-template-columns: 1fr; } }
        .svm-card { background: #fff; border-radius: .75rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); outline: 1px solid rgba(0,0,0,.06); }
        .dark .svm-card { background: rgb(24 24 27); outline-color: rgba(255,255,255,.08); }
        .svm-map-card { padding: .5rem; }
        .svm-map-wrap { position: relative; }
        .svm-map { height: 560px; border-radius: .6rem; z-index: 0; }
        @media (max-width: 640px) { .svm-map { height: 420px; } }
        .svm-note { margin: .5rem .25rem 0; font-size: .72rem; color: rgb(113 113 122); }
        .svm-legend { position: absolute; bottom: 14px; inset-inline-start: 14px; z-index: 500; background: rgba(255,255,255,.94); border-radius: .5rem;
            padding: .5rem .65rem; font-size: .72rem; box-shadow: 0 4px 14px rgba(0,0,0,.12); max-width: 210px; color: #1D2422; }
        .svm-legend b { display: block; margin-bottom: .3rem; }
        .svm-legend .row { display: flex; align-items: center; gap: .4rem; margin: .15rem 0; }
        .svm-legend .sw { width: 14px; height: 10px; border-radius: 2px; flex: none; }

        .svm-side { padding: 1rem; max-height: 600px; overflow: auto; }
        .svm-side__title { display: flex; align-items: center; gap: .5rem; font-weight: 800; font-size: 1rem; margin-bottom: .75rem; }
        .svm-back { width: 1.75rem; height: 1.75rem; border-radius: 999px; background: rgba(0,0,0,.06); }
        .svm-rank { list-style: none; margin: 0; padding: 0; display: grid; gap: .35rem; }
        .svm-rank__row { width: 100%; display: grid; grid-template-columns: 1fr auto; gap: .25rem .5rem; padding: .5rem .6rem; border-radius: .5rem; text-align: start; }
        button.svm-rank__row:hover { background: rgba(0,120,138,.07); }
        .svm-rank__name { font-weight: 700; font-size: .85rem; }
        .svm-rank__n { font-weight: 800; font-variant-numeric: tabular-nums; }
        .svm-stack { grid-column: 1 / -1; display: flex; height: 7px; border-radius: 99px; overflow: hidden; background: rgba(0,0,0,.05); }
        .svm-stack i { display: block; height: 100%; }
        .svm-facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin: 0 0 1rem; }
        .svm-facts div { padding: .6rem; border-radius: .5rem; background: rgba(0,120,138,.07); }
        .svm-facts dt { font-size: .7rem; color: rgb(113 113 122); }
        .svm-facts dd { margin: 0; font-weight: 800; font-size: 1.05rem; }
        .svm-sub { font-weight: 700; font-size: .8rem; color: rgb(113 113 122); margin-bottom: .5rem; }
        .svm-empty { color: rgb(113 113 122); font-size: .85rem; padding: .5rem; }

        .svm-label { background: transparent; border: 0; }
        .svm-label span { display: inline-flex; align-items: center; gap: .3rem; transform: translate(-50%, -50%); white-space: nowrap;
            padding: .2rem .5rem; border-radius: 999px; background: #fff; color: #1D2422; font: 700 11px/1.3 "IBM Plex Sans Arabic", sans-serif;
            box-shadow: 0 2px 8px rgba(0,0,0,.18); border: 1px solid rgba(0,0,0,.08); }
        .svm-label span b { min-width: 1.4rem; padding: 0 .3rem; border-radius: 99px; background: #00788A; color: #fff; text-align: center; }
        .svm-label.is-zero span { opacity: .7; } .svm-label.is-zero span b { background: #a1a1aa; }
        .svm-label.is-selected span { background: #26302D; color: #fff; } .svm-label.is-selected span b { background: #D8B03E; color: #1D2422; }
        .svm-pop { min-width: 210px; font-family: "IBM Plex Sans Arabic", sans-serif; direction: rtl; text-align: right; }
        .svm-pop h4 { margin: 0 0 .25rem; font-size: 14px; font-weight: 800; }
        .svm-pop .meta { color: #71717a; font-size: 11px; margin-bottom: .4rem; }
        .svm-pop .bar { display: flex; height: 8px; border-radius: 99px; overflow: hidden; background: #f4f4f5; margin-bottom: .4rem; }
        .svm-pop .bar i { display: block; }
        .svm-pop ul { list-style: none; margin: 0 0 .5rem; padding: 0; font-size: 11.5px; }
        .svm-pop li { display: flex; justify-content: space-between; gap: .75rem; padding: 1px 0; }
        .svm-pop li span::before { content: ""; display: inline-block; width: 8px; height: 8px; border-radius: 2px; background: var(--c); margin-inline-end: 5px; }
        .svm-pop button { width: 100%; padding: .35rem; border-radius: .4rem; background: #00788A; color: #fff; font-weight: 700; font-size: 12px; }
        .leaflet-container { font-family: "IBM Plex Sans Arabic", sans-serif; }
        .svm-legend .dot { width: 10px; height: 10px; border-radius: 50%; flex: none; box-sizing: border-box; }
        .svm-legend { max-height: calc(100% - 28px); overflow: auto; }
        .svm-open { display: block; margin-top: .4rem; padding: .35rem; border-radius: .4rem; background: #00788A; color: #fff !important; text-align: center; font-weight: 700; font-size: 12px; }
        .svm-fs a { font-size: 18px; line-height: 30px; font-weight: 700; color: #1D2422; }
        /* ملء الشاشة */
        .svm-map-wrap:fullscreen { background: #fff; }
        .svm-map-wrap:fullscreen .svm-map, .svm-map-wrap.is-pseudo-full .svm-map { height: 100vh; border-radius: 0; }
        .svm-map-wrap.is-pseudo-full { position: fixed; inset: 0; z-index: 60; background: #fff; }
    </style>

    <script>
        function svRequestsMap(initial, cfg, centreIds, statusHex, statusShort) {
            return {
                map: null, layers: {}, labels: {}, data: initial,
                init() {
                    const boot = () => window.L ? this.build() : setTimeout(boot, 60);
                    boot();
                    this.$wire.$on('sv-map-data', ({ data }) => { this.data = data; this.paint(); });
                },
                build() {
                    if (this.map) return;
                    this.map = L.map(this.$refs.map, { zoomControl: true, attributionControl: true, scrollWheelZoom: true })
                        .setView(cfg.center, cfg.zoom);
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
                        maxZoom: 18,
                        attribution: 'Tiles &copy; Esri | Boundaries: geoBoundaries (CC BY 4.0)',
                    }).addTo(this.map);

                    fetch(cfg.boundaries).then(r => r.json()).then(geo => {
                        const byCentre = {};
                        L.geoJSON(geo, {
                            style: () => ({ weight: 1.5, color: '#00788A', fillColor: '#00788A', fillOpacity: .1 }),
                            onEachFeature: (f, layer) => {
                                const name = f.properties.centre;
                                (byCentre[name] ||= []).push(layer);
                                layer.on('click', e => this.popup(name, e.latlng));
                                layer.on('mouseover', () => layer.setStyle({ weight: 3 }));
                                layer.on('mouseout', () => layer.setStyle({ weight: 1.5 }));
                            },
                        }).addTo(this.map);
                        this.layers = byCentre;
                        this.map.fitBounds(L.geoJSON(geo).getBounds().pad(.04));
                        Object.entries(byCentre).forEach(([name, ls]) => {
                            const biggest = ls.reduce((a, b) => this.area(b) > this.area(a) ? b : a);
                            this.addLabel(name, biggest.getBounds().getCenter());
                        });
                        Object.entries(cfg.points).forEach(([name, ll]) => {
                            this.layers[name] = [L.circleMarker(ll, { radius: 9, weight: 2, color: '#00788A', fillColor: '#00788A', fillOpacity: .35 })
                                .on('click', e => this.popup(name, e.latlng)).addTo(this.map)];
                            this.addLabel(name, L.latLng(ll), [0, -18]);
                        });
                        this.allBounds = L.geoJSON(geo).getBounds().pad(.04);
                        this.paint();
                    });
                    this.pointsLayer = L.layerGroup().addTo(this.map);
                    this.legend();
                    this.fullscreenControl();
                },
                // زر تكبير الخريطة لملء الشاشة (Fullscreen API، مع بديل CSS)
                fullscreenControl() {
                    const wrap = this.$refs.map.parentElement, self = this;
                    const isFull = () => document.fullscreenElement === wrap || wrap.classList.contains('is-pseudo-full');
                    const Ctl = L.Control.extend({
                        options: { position: 'topleft' },
                        onAdd() {
                            const box = L.DomUtil.create('div', 'leaflet-bar svm-fs');
                            const a = L.DomUtil.create('a', '', box);
                            a.href = '#'; a.setAttribute('role', 'button');
                            const sync = () => { const f = isFull(); a.innerHTML = f ? '&#x2715;' : '&#x26F6;'; a.title = f ? 'إغلاق ملء الشاشة' : 'تكبير الخريطة (ملء الشاشة)'; a.setAttribute('aria-label', a.title); setTimeout(() => { self.map.invalidateSize(); self.refit(); }, 150); };
                            L.DomEvent.on(a, 'click', ev => {
                                L.DomEvent.stop(ev);
                                if (isFull()) {
                                    document.fullscreenElement ? document.exitFullscreen() : wrap.classList.remove('is-pseudo-full');
                                } else if (wrap.requestFullscreen) {
                                    wrap.requestFullscreen().catch(() => wrap.classList.add('is-pseudo-full'));
                                } else {
                                    wrap.classList.add('is-pseudo-full');
                                }
                                setTimeout(sync, 60);
                            });
                            document.addEventListener('fullscreenchange', sync);
                            document.addEventListener('keydown', e => { if (e.key === 'Escape' && wrap.classList.contains('is-pseudo-full')) { wrap.classList.remove('is-pseudo-full'); sync(); } });
                            sync();
                            return box;
                        },
                    });
                    this.map.addControl(new Ctl());
                },
                // نقاط طلبات المركز المختار: بإحداثيات = موقعها الفعلي، بدونها = حلقة حول المركز (متقطعة)
                drawPoints() {
                    this.pointsLayer.clearLayers();
                    const pts = this.data.points || [];
                    if (!pts.length) return null;
                    const name = Object.keys(centreIds).find(n => centreIds[n] === this.data.selected);
                    const origin = this.centrePos[name] || this.map.getCenter();
                    const missing = pts.filter(p => p.lat === null || p.lng === null);
                    const located = [];
                    pts.forEach(p => {
                        let ll, approx = false;
                        if (p.lat !== null && p.lng !== null) {
                            ll = L.latLng(p.lat, p.lng); located.push(ll);
                        } else {
                            const i = missing.indexOf(p), n = missing.length, ring = .018 + Math.floor(i / 16) * .012;
                            const a = (2 * Math.PI * (i % 16)) / Math.min(16, n) - Math.PI / 2;
                            ll = L.latLng(origin.lat + ring * Math.sin(a), origin.lng + ring * 1.15 * Math.cos(a)); approx = true;
                        }
                        const c = statusHex[p.status] || '#78756A';
                        L.circleMarker(ll, approx
                            ? { radius: 6, weight: 2, color: c, dashArray: '3 3', fillColor: '#fff', fillOpacity: .9 }
                            : { radius: 7.5, weight: 2.5, color: '#fff', fillColor: c, fillOpacity: 1 })
                            .bindTooltip(`${this.esc(p.number)} — ${this.esc(p.label)}`, { direction: 'top', offset: [0, -6] })
                            .bindPopup(`<div class="svm-pop"><h4>${this.esc(p.number)}</h4>
                                <div class="meta">${this.esc(p.name)}<br>${this.esc(p.village || '')}</div>
                                <ul><li style="--c:${c}"><span>${this.esc(p.label)}</span></li></ul>
                                ${approx ? '<div class="meta" style="color:#B7791F">⚠ الموقع غير محدد — يظهر بجوار المركز. حدده من صفحة الطلب «تحديد موقع الأرض».</div>' : ''}
                                <a href="${p.url}" class="svm-open">فتح الطلب ←</a></div>`)
                            .addTo(this.pointsLayer);
                    });
                    return located.length ? L.latLngBounds(located) : null;
                },
                area(layer) { const b = layer.getBounds(); return (b.getNorth() - b.getSouth()) * (b.getEast() - b.getWest()); },
                addLabel(name, latlng, offset = [0, 0]) {
                    const m = L.marker(latlng, { icon: L.divIcon({ className: 'svm-label', html: '', iconSize: [0, 0], iconAnchor: offset.map(v => -v) }), keyboard: false })
                        .on('click', e => this.popup(name, e.latlng)).addTo(this.map);
                    this.labels[name] = m;
                    (this.centrePos ||= {})[name] = latlng;
                },
                stats(name) { return this.data.centres[name] || { id: centreIds[name], total: 0, paid: 0, revenue: 0, statuses: {} }; },
                paint() {
                    const max = Math.max(1, ...Object.values(this.data.centres).map(c => c.total));
                    const selectedId = this.data.selected;
                    Object.entries(this.layers).forEach(([name, ls]) => {
                        const s = this.stats(name), ratio = s.total / max;
                        const isSel = selectedId && centreIds[name] === selectedId;
                        ls.forEach(l => l.setStyle({
                            fillOpacity: s.total ? .12 + ratio * .55 : .04,
                            color: isSel ? '#D8B03E' : '#00788A', weight: isSel ? 3.5 : 1.5,
                            ...(l instanceof L.CircleMarker ? { radius: 8 + ratio * 10 } : {}),
                        }));
                    });
                    Object.entries(this.labels).forEach(([name, m]) => {
                        const s = this.stats(name), isSel = selectedId && centreIds[name] === selectedId;
                        m.setIcon(L.divIcon({ className: 'svm-label' + (s.total ? '' : ' is-zero') + (isSel ? ' is-selected' : ''),
                            html: `<span>${this.esc(name)} <b>${s.total}</b></span>`, iconSize: [0, 0], iconAnchor: m.options.icon.options.iconAnchor }));
                    });
                    this.located = this.drawPoints();
                    this.refit();
                },
                // تكبير على المركز المختار ونقاطه، أو على المحافظة كلها
                refit() {
                    if (!this.map) return;
                    const selectedId = this.data.selected;
                    if (selectedId) {
                        const name = Object.keys(centreIds).find(n => centreIds[n] === selectedId);
                        const ls = this.layers[name];
                        if (ls) {
                            let b = L.featureGroup(ls).getBounds().pad(ls[0] instanceof L.CircleMarker ? 2 : .25);
                            if (this.located) b = b.extend(this.located);
                            this.map.flyToBounds(b, { duration: .6, maxZoom: 14 });
                        }
                    } else if (this.allBounds) {
                        this.map.flyToBounds(this.allBounds, { duration: .6 });
                    }
                },
                popup(name, latlng) {
                    const s = this.stats(name), id = centreIds[name];
                    const keys = Object.keys(statusShort).filter(k => s.statuses[k]);
                    const bar = keys.map(k => `<i style="width:${s.statuses[k] / Math.max(1, s.total) * 100}%;background:${statusHex[k]}"></i>`).join('');
                    const rows = keys.map(k => `<li style="--c:${statusHex[k]}"><span>${statusShort[k]}</span><b>${s.statuses[k]}</b></li>`).join('');
                    const html = `<div class="svm-pop"><h4>مركز ${this.esc(name)}</h4>
                        <div class="meta">${s.total} طلب · ${s.paid} مدفوع · ${Math.round(s.revenue).toLocaleString('en')} ج.م</div>
                        ${s.total ? `<div class="bar">${bar}</div><ul>${rows}</ul>` : '<div class="meta">لا توجد طلبات مطابقة للفلاتر.</div>'}
                        ${id ? `<button type="button" data-centre="${id}">عرض طلبات المركز</button>` : ''}</div>`;
                    const p = L.popup({ maxWidth: 280 }).setLatLng(latlng).setContent(html).openOn(this.map);
                    p.getElement()?.querySelector('[data-centre]')?.addEventListener('click', () => { this.map.closePopup(); this.$wire.setCentre(id); });
                },
                legend() {
                    this.$refs.legend.innerHTML = '<b>كثافة الطلبات</b>'
                        + [['أعلى', .67], ['متوسط', .4], ['أقل', .14]].map(([l, o]) =>
                            `<div class="row"><span class="sw" style="background:rgba(0,120,138,${o})"></span>${l}</div>`).join('')
                        + '<div class="row"><span class="sw" style="background:#fff;border:2px solid #D8B03E"></span>المركز المحدد</div>'
                        + '<b style="margin-top:.4rem">نقاط الطلبات (عند اختيار مركز)</b>'
                        + Object.keys(statusShort).map(k => `<div class="row"><span class="dot" style="background:${statusHex[k]}"></span>${statusShort[k]}</div>`).join('')
                        + '<div class="row"><span class="dot" style="background:#fff;border:2px dashed #78756A"></span>الموقع غير محدد</div>';
                },
                esc(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); },
            };
        }
    </script>
</x-filament-panels::page>
