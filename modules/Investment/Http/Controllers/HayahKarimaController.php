<?php

namespace Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Investment\Models\HayahKarimaProject;
use Illuminate\Http\Request;

class HayahKarimaController extends Controller
{
    public function index()
    {
        $projects = HayahKarimaProject::withCount('locations')->orderBy('progress', 'desc')->get();
        return view('projects.hay_karima', compact('projects'));
    }

    /** تفاصيل قطاع: الوصف الكامل، نسبة الإنجاز، ومواقع التنفيذ على الخريطة. */
    public function show(string $slug)
    {
        $sector = HayahKarimaProject::where('slug', $slug)->with('locations')->firstOrFail();
        $others = HayahKarimaProject::whereKeyNot($sector->getKey())->orderBy('progress', 'desc')->get(['id', 'sector_name', 'slug', 'icon', 'progress']);

        return view('projects.hay_karima_show', compact('sector', 'others'));
    }
}
