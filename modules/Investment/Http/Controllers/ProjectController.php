<?php

namespace Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Investment\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::where('category', 'قومي')->latest()->paginate(9); // Show 9 projects per page

        return view('projects.index', [
            'projects' => $projects
        ]);
    }

    public function show(Project $project)
    {
        $project->load('images');

        return view('projects.show', [
            'project' => $project,
        ]);
    }
}
