<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Citizen\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        // Fetch parent services along with their children
        $mainServices = Service::whereNull('parent_id')
            ->with('children') // Eager load sub-services
            ->get();

        return view('services.index', [
            'services' => $mainServices
        ]);
    }

    // Page for showing details of a specific service
    public function show(Service $service)
    {
        // Load the necessary relationships
        $service->load(['parent', 'children']);

        // Fetch related services for the sidebar
        $relatedServices = Service::where('parent_id', $service->parent_id) // Siblings or other parents
            ->where('id', '!=', $service->id)
            ->take(3)->get();

        return view('services.show', [
            'service' => $service,
            'relatedServices' => $relatedServices,
        ]);
    }
}
