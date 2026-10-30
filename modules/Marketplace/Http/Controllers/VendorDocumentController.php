<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Marketplace\Models\MarketVendor;

/**
 * Streams the private integrated-services card scan to its owner or to
 * marketplace admins. Never exposed through a public URL.
 */
class VendorDocumentController extends Controller
{
    public function __invoke(Request $request, int $vendor)
    {
        $vendor = MarketVendor::withTrashed()->findOrFail($vendor);
        abort_unless(Gate::allows('viewDocument', $vendor), 403);

        $disk = Storage::disk((string) config('marketplace.documents_disk', 'local'));
        $path = (string) $vendor->disability_card_path;
        abort_if($path === '' || ! $disk->exists($path), 404);

        return $disk->response($path, 'card-'.$vendor->getKey().'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
