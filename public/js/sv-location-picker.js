/*!
 * منتقي موقع الأرض — خدمة «صلاحية الموقع» (يتطلب Leaflet 1.9).
 * يُستخدم في: نموذج المواطن، «إضافة طلب» في اللوحة، وإجراء «تحديد موقع الأرض».
 *
 *   const picker = svLocationPicker(el, {
 *     lat, lng,                  // القيمة الحالية (أو null)
 *     boundaries: '/geo/…geojson',
 *     points: { 'بلطيم': [lat, lng], … },   // مراكز بلا حدود (نقاط)
 *     centre: 'كفر الشيخ',       // اسم المركز المختار (اختياري)
 *     bounds: [minLat, maxLat, minLng, maxLng],
 *     onChange: (lat, lng) => {} // null, null عند المسح
 *   });
 *   picker.setCentre('دسوق'); picker.set(lat, lng); picker.clear();
 */
(function () {
    'use strict';

    const TILES = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
    const LABELS = 'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}';
    const geoCache = {};

    function loadGeo(url) {
        if (!url) return Promise.resolve(null);
        geoCache[url] ||= fetch(url).then(r => r.ok ? r.json() : null).catch(() => null);
        return geoCache[url];
    }

    const round = v => Math.round(v * 1e7) / 1e7;

    window.svLocationPicker = function (el, opts) {
        opts = Object.assign({ lat: null, lng: null, centre: null, points: {}, bounds: [30.85, 31.70, 30.30, 31.50], onChange() {} }, opts || {});
        const [minLat, maxLat, minLng, maxLng] = opts.bounds;
        const inside = (lat, lng) => lat >= minLat && lat <= maxLat && lng >= minLng && lng <= maxLng;

        const map = L.map(el, { zoomControl: true, attributionControl: true }).setView([31.25, 30.9], 10);
        L.tileLayer(TILES, { maxZoom: 19, attribution: 'Tiles &copy; Esri' }).addTo(map);
        L.tileLayer(LABELS, { maxZoom: 19 }).addTo(map);

        let marker = null, centres = {}, centreLayer = null;
        const pin = L.divIcon({ className: 'sv-pin', html: '<span></span>', iconSize: [28, 36], iconAnchor: [14, 34] });

        function emit() {
            const ll = marker ? marker.getLatLng() : null;
            opts.onChange(ll ? round(ll.lat) : null, ll ? round(ll.lng) : null);
        }

        function set(lat, lng, silent) {
            lat = parseFloat(lat); lng = parseFloat(lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            if (!inside(lat, lng)) { alert('الموقع المختار خارج حدود محافظة كفر الشيخ.'); return; }
            if (!marker) {
                marker = L.marker([lat, lng], { icon: pin, draggable: true, keyboard: true, title: 'موقع الأرض — اسحب لتعديله' }).addTo(map);
                marker.on('dragend', () => {
                    const p = marker.getLatLng();
                    if (!inside(p.lat, p.lng)) { alert('الموقع المختار خارج حدود محافظة كفر الشيخ.'); }
                    emit();
                });
            } else {
                marker.setLatLng([lat, lng]);
            }
            if (!silent) emit();
        }

        function clear() {
            if (marker) { map.removeLayer(marker); marker = null; }
            emit();
        }

        function setCentre(name) {
            opts.centre = name || null;
            if (centreLayer) { centreLayer.setStyle({ weight: 1, color: '#ffffff', fillOpacity: 0, dashArray: '4 4' }); centreLayer = null; }
            if (!name) return;
            const ls = centres[name];
            if (ls && ls.length) {
                centreLayer = L.featureGroup(ls);
                centreLayer.setStyle({ weight: 3, color: '#D8B03E', fillOpacity: .06, dashArray: null });
                if (!marker) map.fitBounds(centreLayer.getBounds().pad(.05));
            } else if (opts.points[name] && !marker) {
                map.setView(opts.points[name], 13);
            }
        }

        // حدود المراكز
        loadGeo(opts.boundaries).then(geo => {
            if (!geo) return;
            L.geoJSON(geo, {
                interactive: false,
                style: () => ({ weight: 1, color: '#ffffff', opacity: .8, fillOpacity: 0, dashArray: '4 4' }),
                onEachFeature: (f, layer) => (centres[f.properties.centre] ||= []).push(layer),
            }).addTo(map);
            if (opts.centre) setCentre(opts.centre);
            else if (!marker) map.fitBounds(L.geoJSON(geo).getBounds());
        });

        map.on('click', e => set(e.latlng.lat, e.latlng.lng));

        // أزرار: موقعي الحالي / مسح
        const Ctl = L.Control.extend({
            options: { position: 'topright' },
            onAdd() {
                const box = L.DomUtil.create('div', 'leaflet-bar sv-pick-ctl');
                const mk = (html, title, fn) => {
                    const a = L.DomUtil.create('a', '', box);
                    a.href = '#'; a.innerHTML = html; a.title = title; a.setAttribute('role', 'button'); a.setAttribute('aria-label', title);
                    L.DomEvent.on(a, 'click', ev => { L.DomEvent.stop(ev); fn(); });
                };
                mk('&#x2316;', 'موقعي الحالي', () => {
                    if (!navigator.geolocation) return alert('المتصفح لا يدعم تحديد الموقع.');
                    navigator.geolocation.getCurrentPosition(
                        p => { set(p.coords.latitude, p.coords.longitude); map.setView([p.coords.latitude, p.coords.longitude], 17); },
                        () => alert('تعذر الحصول على موقعك — اسمح للمتصفح بالوصول للموقع أو حدده يدوياً على الخريطة.'),
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                });
                mk('&#x2715;', 'مسح الموقع', clear);
                return box;
            },
        });
        map.addControl(new Ctl());

        if (opts.lat !== null && opts.lng !== null && opts.lat !== '' && opts.lng !== '') {
            set(opts.lat, opts.lng, true);
            map.setView([parseFloat(opts.lat), parseFloat(opts.lng)], 16);
        }

        // الخريطة داخل نافذة/تبويب مخفي: أعد الحساب عند الظهور
        if ('ResizeObserver' in window) new ResizeObserver(() => map.invalidateSize()).observe(el);

        return { map, set, clear, setCentre };
    };
})();
