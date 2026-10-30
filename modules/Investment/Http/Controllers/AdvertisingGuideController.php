<?php

namespace Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Investment\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class AdvertisingGuideController extends Controller
{
    /** Root e-service «ترخيص الإعلانات» (fixed UUID from ServiceSeeder). */
    protected const LICENCE_SERVICE_ID = '019dd3ed-4573-7255-b27f-16fe7d4cc744';

    public function index()
    {
        // Lightweight aggregates for the hero / stats strip (the map itself loads /advertising-guide/points).
        $active = Advertisement::where('status', 'active')->get(['street_name', 'type', 'lat', 'lng']);

        $stats = [
            'points'  => $active->count(),
            'streets' => $active->pluck('street_name')->unique()->count(),
            'types'   => $active->pluck('type')->unique()->count(),
            'areas'   => $active->map(fn ($a) => Advertisement::areaFor((float) $a->lat, (float) $a->lng))->unique()->count(),
        ];

        $licenceUrl = null;
        try {
            if (Route::has('services.show')) {
                $licenceId = \Illuminate\Support\Facades\DB::table('services')->where('id', self::LICENCE_SERVICE_ID)->value('id')
                    ?? \Illuminate\Support\Facades\DB::table('services')->whereNull('parent_id')->where('title', 'like', '%ترخيص الإعلان%')->value('id');
                $licenceUrl = $licenceId ? route('services.show', $licenceId) : null;
            }
        } catch (\Throwable $e) {
            $licenceUrl = null;
        }

        return view('advertising-guide.index', [
            'stats' => $stats,
            'types' => Advertisement::TYPES,
            'areas' => Advertisement::AREAS,
            'licenceUrl' => $licenceUrl,
        ]);
    }

    public function points()
    {
        $user = auth()->user();

        $ads = Advertisement::where('status', 'active')
            ->select('id', 'street_name', 'lat', 'lng', 'type', 'height', 'size', 'description', 'user_id')
            ->orderBy('street_name')
            ->orderBy('id')
            ->get()
            ->map(fn (Advertisement $ad) => [
                'id' => $ad->id,
                'street_name' => $ad->street_name,
                'lat' => $ad->lat,
                'lng' => $ad->lng,
                'type' => $ad->type,
                'height' => $ad->height,
                'size' => $ad->size,
                'description' => $ad->description,
                // Additive, derived fields (no PII): nearest administrative centre + whether the viewer may edit it.
                'area' => Advertisement::areaFor((float) $ad->lat, (float) $ad->lng),
                'can_manage' => $user ? Gate::forUser($user)->allows('manage-advertisement', $ad) : false,
            ]);

        return response()->json($ads)->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'street_name' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'type'        => ['required', 'string', 'max:100', 'not_regex:/[<>]/'],
            'height'      => 'nullable|numeric|min:0',
            'size'        => ['nullable', 'string', 'max:100', 'not_regex:/[<>]/'],
            'description' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/'],
        ]);

        $validated['user_id'] = auth()->id();
        $ad = Advertisement::create($validated);

        Log::info('Advertisement created', ['id' => $ad->id, 'user' => auth()->id()]);

        return response()->json($ad);
    }

    public function update(Request $request, Advertisement $ad)
    {
        $this->authorizeAd($ad);

        $validated = $request->validate([
            'street_name' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'type'        => ['required', 'string', 'max:100', 'not_regex:/[<>]/'],
            'height'      => 'nullable|numeric|min:0',
            'size'        => ['nullable', 'string', 'max:100', 'not_regex:/[<>]/'],
            'description' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/'],
        ]);

        $ad->update($validated);
        return response()->json($ad);
    }

    public function destroy(Advertisement $ad)
    {
        $this->authorizeAd($ad);

        $ad->update(['status' => 'inactive']);
        return response()->json(['message' => 'done']);
    }

    /** Only the creator or staff (admin / update_advertisement permission) may modify a point. */
    protected function authorizeAd(Advertisement $ad): void
    {
        abort_unless(Gate::allows('manage-advertisement', $ad), 403, 'ليس لديك صلاحية تعديل هذه النقطة.');
    }
}
