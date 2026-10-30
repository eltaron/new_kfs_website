{{--
    حقل «موقع الأرض على الخريطة» داخل نماذج Filament (ViewField).
    يكتب في الحقلين الشقيقين latitude / longitude بنفس مسار الحالة، ويتابع حقل center_id إن وُجد
    (أو مركزاً ثابتاً عبر $centreName). الأصول (Leaflet + المنتقي) محمّلة من رأس اللوحة.
--}}
@php
    $base = \Illuminate\Support\Str::beforeLast($getStatePath(), '.');
    $config = [
        'latPath' => $base . '.latitude',
        'lngPath' => $base . '.longitude',
        'centrePath' => ($watchCentre ?? true) ? $base . '.center_id' : null,
        'centreName' => $centreName ?? null,
        'centreNames' => \Modules\SiteValidity\Models\Geo\Centre::query()->pluck('name', 'id')->all(),
        'boundaries' => asset((string) config('site-validity.map.boundaries')),
        'points' => (array) config('site-validity.map.points', []),
        'bounds' => \Modules\SiteValidity\Models\SiteValidityRequest::KFS_BOUNDS,
    ];
@endphp
<div wire:ignore class="sv-picker"
    x-data="{
        cfg: @js($config), lat: null, lng: null, picker: null,
        init() {
            const boot = () => (window.L && window.svLocationPicker) ? this.start() : setTimeout(boot, 80);
            boot();
        },
        start() {
            if (this.picker) return;
            this.lat = $wire.$get(this.cfg.latPath); this.lng = $wire.$get(this.cfg.lngPath);
            const centreId = this.cfg.centrePath ? $wire.$get(this.cfg.centrePath) : null;
            this.picker = svLocationPicker(this.$refs.map, {
                lat: this.lat, lng: this.lng, boundaries: this.cfg.boundaries, points: this.cfg.points, bounds: this.cfg.bounds,
                centre: this.cfg.centreName || (centreId ? this.cfg.centreNames[centreId] : null),
                onChange: (a, b) => { this.lat = a; this.lng = b; $wire.$set(this.cfg.latPath, a, false); $wire.$set(this.cfg.lngPath, b, false); },
            });
            if (this.cfg.centrePath) $wire.$watch(this.cfg.centrePath, id => this.picker.setCentre(id ? this.cfg.centreNames[id] : null));
        },
    }">
    <div x-ref="map" class="sv-picker__map"></div>
    <div class="sv-picker__bar">
        <template x-if="lat !== null && lat !== '' && lng !== null && lng !== ''">
            <span class="is-set">✓ تم تحديد الموقع: <b x-text="Number(lat).toFixed(6) + ', ' + Number(lng).toFixed(6)"></b></span>
        </template>
        <template x-if="lat === null || lat === '' || lng === null || lng === ''">
            <span>اضغط على الخريطة لتحديد موقع الأرض، أو استخدم زر «موقعي الحالي» (⌖). يمكنك سحب العلامة لتعديلها.</span>
        </template>
    </div>
</div>
