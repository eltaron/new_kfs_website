<?php

namespace Modules\Gis\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Investment\Models\Project;
use Modules\Tourism\Models\Landmark;
use Modules\Tourism\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MapController extends Controller
{
    public function index()
    {
        // 1. جلب المشروعات (القومية والاستثمارية والصناعية)
        $projects = Project::whereNotNull('latitude')->get()->map(fn($p) => [
            'type' => 'project',
            'category' => $p->category, // قومي، استثماري، صناعي
            'name' => $p->name,
            'lat' => (float)$p->latitude,
            'lng' => (float)$p->longitude,
            'image' => Storage::url($p->thumbnail),
            'url' => route('projects.show', $p->slug)
        ]);

        // 2. جلب المعالم السياحية
        $landmarks = Landmark::whereNotNull('latitude')->get()->map(fn($l) => [
            'type' => 'landmark',
            'category' => 'سياحي',
            'name' => $l->name,
            'lat' => (float)$l->latitude,
            'lng' => (float)$l->longitude,
            'image' => Storage::url($l->thumbnail),
            'url' => route('landmarks.show', $l->id)
        ]);

        // 3. جلب دليل العاصمة (المواقع)
        $locations = Location::with('cityGuideCategory')->get()->map(fn($loc) => [
            'type' => 'guide',
            'category' => $loc->cityGuideCategory->name ?? 'خدمي',
            'name' => $loc->name,
            'lat' => (float)$loc->latitude,
            'lng' => (float)$loc->longitude,
            'image' => null,
            'url' => '#'
        ]);

        // دمج الجميع
        $allData = $projects->concat($landmarks)->concat($locations);

        return view('governorate.map', compact('allData'));
    }
}
