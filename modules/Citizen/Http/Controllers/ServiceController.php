<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Citizen\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        // Fetch parent services along with their children
        $mainServices = Service::active()->ordered()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->active()->ordered()]) // Eager load active sub-services
            ->get();

        return view('services.index', [
            'services' => $mainServices
        ]);
    }

    // Page for showing details of a specific service
    public function show(Service $service)
    {
        // Load the necessary relationships
        $service->load(['parent', 'children' => fn ($q) => $q->active()->ordered()]);

        // الخدمة الموقوفة (أو التابعة لفئة موقوفة) لا تظهر للمواطنين
        abort_unless($service->isAvailable(), 404);

        // Fetch related services for the sidebar
        $relatedServices = Service::active()->ordered()
            ->where('parent_id', $service->parent_id) // Siblings or other parents
            ->where('id', '!=', $service->id)
            ->take(3)->get();

        return view('services.show', [
            'service' => $service,
            'relatedServices' => $relatedServices,
        ]);
    }
}
