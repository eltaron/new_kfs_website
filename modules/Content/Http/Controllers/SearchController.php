<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Content\Models\Post;
use Modules\Investment\Models\Project;
use Modules\Citizen\Models\Service;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        // 1. Get the search query from the URL, for example: /search?query=hello
        $query = $request->input('query');

        // 2. Perform the search across multiple models if a query exists
        $posts = collect(); // Use empty collections as default
        $projects = collect();
        $services = collect();

        if ($query) {
            // Search in Posts (news, events) based on title or content
            $posts = Post::where('is_published', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%");
                })
                ->limit(10) // Limit results per model for performance
                ->get();

            // Search in Projects based on name or description
            $projects = Project::where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(10)
                ->get();

            // Search in Services based on title or description
            $services = Service::active()->where(function ($q) use ($query) {
                $q->where('title_line_1', 'like', "%{$query}%")
                    ->orWhere('title_line_2', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
                ->limit(10)
                ->get();
        }

        // 3. Return the view with the results
        return view('home.search-results', [
            'query' => $query,
            'posts' => $posts,
            'projects' => $projects,
            'services' => $services,
        ]);
    }
}
