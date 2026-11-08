<?php

namespace Modules\Gis\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Investment\Models\Project;
use Modules\Tourism\Models\Landmark;
use Modules\Tourism\Models\Location;

class MapController extends Controller
{
    /**
     * Point groups shown in the map's filter panel (type => label, icon, colour token).
     * Colours are CSS custom properties defined in public/css/kfs-map.css.
     */
    public const GROUPS = [
        'landmark' => ['label' => 'معالم سياحية', 'icon' => 'fa-solid fa-monument'],
        'project' => ['label' => 'مشروعات وفرص استثمارية', 'icon' => 'fa-solid fa-city'],
        'hayah' => ['label' => 'حياة كريمة', 'icon' => 'fa-solid fa-hand-holding-heart'],
        'guide' => ['label' => 'دليل الخدمات', 'icon' => 'fa-solid fa-hospital'],
    ];

    public function index()
    {
        $points = $this->landmarks()
            ->concat($this->projects())
            ->concat($this->hayahKarima())
            ->concat($this->locations())
            ->filter(fn ($p) => $this->validCoords($p['lat'], $p['lng']))
            ->values()
            ->map(fn ($p, $i) => ['id' => $i] + $p);

        // Sub-categories per group, with counts, for the filter chips.
        $groups = collect(self::GROUPS)->map(function ($meta, $type) use ($points) {
            $inGroup = $points->where('type', $type);

            return $meta + [
                'count' => $inGroup->count(),
                'categories' => $inGroup->countBy('category')->sortDesc()->all(),
            ];
        })->filter(fn ($g) => $g['count'] > 0);

        return view('governorate.map', [
            'points' => $points,
            'groups' => $groups,
        ]);
    }

    private function landmarks(): Collection
    {
        return Landmark::whereNotNull('latitude')->get()->map(fn ($l) => [
            'type' => 'landmark',
            'category' => 'معلم سياحي',
            'name' => $l->name,
            'address' => $this->plain($l->address),
            'lat' => (float) $l->latitude,
            'lng' => (float) $l->longitude,
            'image' => $l->thumbnail ? Storage::url($l->thumbnail) : null,
            'url' => route('landmarks.show', $l->id),
        ]);
    }

    private function projects(): Collection
    {
        return Project::whereNotNull('latitude')->get()->map(fn ($p) => [
            'type' => 'project',
            'category' => $p->category ?: 'مشروع',
            'name' => $p->name,
            'address' => Str::limit((string) $this->plain($p->description), 90) ?: null,
            'lat' => (float) $p->latitude,
            'lng' => (float) $p->longitude,
            'image' => $p->thumbnail ? Storage::url($p->thumbnail) : null,
            'url' => $p->slug ? route('projects.show', $p->slug) : null,
        ]);
    }

    /** مواقع تنفيذ «حياة كريمة» — التصنيف = القطاع، والرابط = صفحة القطاع. */
    private function hayahKarima(): Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('hayah_karima_locations')) {
            return collect();
        }

        return \Modules\Investment\Models\HayahKarimaLocation::with('project:id,sector_name,slug,image')
            ->whereNotNull('latitude')->whereNotNull('longitude')->get()
            ->map(fn ($l) => [
                'type' => 'hayah',
                'category' => $l->project?->sector_name ?: 'حياة كريمة',
                'name' => $l->name,
                'address' => trim(collect([$l->village, $l->center, $l->status])->filter()->implode(' — ')) ?: null,
                'lat' => (float) $l->latitude,
                'lng' => (float) $l->longitude,
                'image' => $l->project?->image ? Storage::url($l->project->image) : null,
                'url' => $l->project?->slug ? route('projects.hay-karima.show', $l->project->slug) : route('projects.hay-karima'),
            ]);
    }

    private function locations(): Collection
    {
        return Location::with('cityGuideCategory')->whereNotNull('latitude')->get()->map(fn ($loc) => [
            'type' => 'guide',
            'category' => $loc->cityGuideCategory->name ?? 'خدمي',
            'name' => $loc->name,
            'address' => null,
            'lat' => (float) $loc->latitude,
            'lng' => (float) $loc->longitude,
            'image' => null,
            'url' => null,
        ]);
    }

    private function validCoords($lat, $lng): bool
    {
        return $lat && $lng && abs($lat) <= 90 && abs($lng) <= 180;
    }

    private function plain(?string $html): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $text !== '' ? $text : null;
    }
}
