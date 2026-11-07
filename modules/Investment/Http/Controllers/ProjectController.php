<?php

namespace Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Investment\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::where('category', 'قومي')
            ->withCount('images')
            ->orderByDesc('is_highlighted')->latest()
            ->paginate(9); // Show 9 projects per page

        // Counts for the «programs» links under the list.
        $programCounts = Project::selectRaw('category, COUNT(*) as c')->groupBy('category')->pluck('c', 'category');

        return view('projects.index', [
            'projects' => $projects,
            'programCounts' => $programCounts,
        ]);
    }

    public function show(Project $project)
    {
        $project->load(['images', 'investment']);

        // «مشروعات أخرى» — same category first, then the rest; highlighted first.
        $others = Project::whereKeyNot($project->getKey())
            ->orderByDesc('is_highlighted')->latest()
            ->get(['id', 'name', 'slug', 'category', 'thumbnail', 'is_highlighted', 'created_at']);
        $related = $others->where('category', $project->category)
            ->concat($others->where('category', '!=', $project->category))
            ->filter(fn ($p) => $p->slug)
            ->take(3)->values();

        return view('projects.show', [
            'project' => $project,
            'related' => $related,
        ]);
    }
}
