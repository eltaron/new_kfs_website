<x-filament-panels::page>
    @if ($showMap && $removalOrder)
        {{-- record info cards row --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <x-filament::section>
                <x-slot name="heading">الرقم المسلسل</x-slot>
                <p class="text-lg font-bold">{{ $removalOrder->id }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">اسم المالك</x-slot>
                <p class="text-lg font-bold">{{ $removalOrder->owner_name }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">العنوان</x-slot>
                <p>{{ $removalOrder->center }} - {{ $removalOrder->local_unit }} - {{ $removalOrder->street }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">نوع المخالفة</x-slot>
                <p>{{ $removalOrder->violation_type === 'new_violation' ? 'بناء بدون ترخيص' : 'مخالفة شروط ترخيص' }}</p>
            </x-filament::section>
        </div>

        {{-- hidden input to sync GeoJSON to Livewire --}}
        <input type="hidden" id="polygonInput" wire:model.live="polygon_geojson">

        {{-- full-width map --}}
        {{-- <x-filament::section>
            <x-slot name="heading">الخريطة - الكشف المساحي</x-slot>
            <div wire:ignore>
                <div id="spatialMap" style="height:520px; border-radius:8px;" class="border dark:border-gray-600"></div>
            </div>
        </x-filament::section> --}}

        {{-- external GIS portal iframe --}}
        <x-filament::section class="mt-4">
            <x-slot name="heading">نظم المعلومات الجيومكانية- البوابة الإلكترونية</x-slot>
            <div wire:ignore>
                <iframe src="https://portal.kfs.gov.eg/portal/apps/webappviewer/index.html?id=9c948e109d434b9ab2fff63b4b4decf9" width="100%" height="900" frameborder="0"></iframe>
            </div>
        </x-filament::section>

        {{-- building photo + notes row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
            @if ($removalOrder->photo_file)
                <x-filament::section>
                    <x-slot name="heading">صورة المبني</x-slot>
                    <img src="{{ Storage::url($removalOrder->photo_file) }}"
                        class="w-full rounded-lg border dark:border-gray-600"
                        style="max-height:280px;object-fit:cover;">
                </x-filament::section>
            @endif
            <x-filament::section>
                <x-slot name="heading">ملاحظات مكانية</x-slot>
                <div wire:ignore class="mb-2">
                    <textarea id="notesField" rows="4"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500 text-sm"
                        placeholder="ملاحظات مكانية..." wire:model="spatial_notes"></textarea>
                </div>
                @if (auth()->user()->hasRole('عضو المتغيرات'))
                    <div class="mt-4">
                        <x-filament::button wire:click="save" color="success" size="lg" class="w-full">
                            حفظ الكشف المساحي
                        </x-filament::button>
                    </div>
                @endif
            </x-filament::section>
        </div>

        <div class="mt-4">
            <x-filament::button tag="a" href="{{ \App\Filament\Gis\Pages\SpatialSurvey::getUrl() }}"
                color="gray" size="sm">
                ← العودة
            </x-filament::button>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
        <style>
            #spatialMap {
                direction: ltr;
            }

            .leaflet-container {
                font-family: 'Cairo', sans-serif;
            }
        </style>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
        <script>
            (function() {
                function initMap() {
                    var container = document.getElementById('spatialMap');
                    if (!container || container._leaflet_map) return;
                    var map = L.map(container).setView([31.1, 30.94], 13);
                    container._leaflet_map = map;
                    L.tileLayer(
                        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: 'Esri, Maxar, Earthstar Geographics',
                            maxZoom: 19,
                        }).addTo(map);
                    var drawnItems = new L.FeatureGroup();
                    map.addLayer(drawnItems);
                    map.addControl(new L.Control.Draw({
                        edit: {
                            featureGroup: drawnItems
                        },
                        draw: {
                            polygon: {
                                allowIntersection: false,
                                showArea: true
                            },
                            polyline: false,
                            rectangle: true,
                            circle: false,
                            circlemarker: false,
                            marker: false,
                        }
                    }));
                    var existingJson = @json($this->polygon_geojson);
                    if (existingJson) {
                        try {
                            var parsed = typeof existingJson === 'string' ? JSON.parse(existingJson) : existingJson;
                            if (parsed.type === 'Feature') {
                                parsed = parsed.geometry;
                            }
                            if (parsed.type === 'FeatureCollection' && parsed.features[0]) {
                                parsed = parsed.features[0].geometry;
                            }
                            var layer = L.geoJSON(parsed);
                            layer.eachLayer(function(l) {
                                drawnItems.addLayer(l);
                            });
                            map.fitBounds(drawnItems.getBounds().pad(0.1));
                        } catch (e) {
                            console.warn('existing polygon parse error', e);
                        }
                    }

                    function setGeoJSON(gj) {
                        var input = document.getElementById('polygonInput');
                        if (input) {
                            input.value = JSON.stringify(gj);
                            input.dispatchEvent(new Event('input', {
                                bubbles: true
                            }));
                        }
                    }
                    map.on(L.Draw.Event.CREATED, function(e) {
                        drawnItems.clearLayers();
                        drawnItems.addLayer(e.layer);
                        var gj = e.layer.toGeoJSON();
                        setGeoJSON(gj);
                    });
                    map.on(L.Draw.Event.EDITED, function(e) {
                        var layer;
                        e.layers.eachLayer(function(l) {
                            layer = l;
                        });
                        if (layer) {
                            var gj = layer.toGeoJSON();
                            setGeoJSON(gj);
                        }
                    });
                    map.on(L.Draw.Event.DELETED, function() {
                        var input = document.getElementById('polygonInput');
                        if (input) {
                            input.value = '';
                            input.dispatchEvent(new Event('input', {
                                bubbles: true
                            }));
                        }
                    });
                    setTimeout(function() {
                        map.invalidateSize();
                    }, 500);
                }
                if (document.readyState === 'complete') {
                    setTimeout(initMap, 300);
                } else {
                    window.addEventListener('load', function() {
                        setTimeout(initMap, 300);
                    });
                }
                document.addEventListener('livewire:navigated', function() {
                    setTimeout(initMap, 300);
                });
            })();
        </script>
    @else
        <div class="p-12 text-center text-gray-500 dark:text-gray-400">
            <p>جاري التوجيه الي لوحة البيانات...</p>
        </div>
    @endif
</x-filament-panels::page>
