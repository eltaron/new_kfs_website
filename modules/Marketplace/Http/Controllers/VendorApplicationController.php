<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Http\Requests\VendorApplicationRequest;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Services\OrderNotifier;
use Modules\Marketplace\Support\MarketplaceAccess;
use Modules\Marketplace\Support\MarketSettings;

/**
 * A signed-in citizen applies to open a store. Admins review the
 * application (and the integrated-services card) from /store-admin.
 */
class VendorApplicationController extends Controller
{
    public function landing(Request $request)
    {
        return view('marketplace::store.sell', [
            'vendor' => MarketplaceAccess::vendorFor($request->user()),
            'commission' => MarketSettings::commissionRate(),
        ]);
    }

    public function create(Request $request)
    {
        $vendor = MarketplaceAccess::vendorFor($request->user());
        if ($vendor && $vendor->status !== MarketVendor::STATUS_REJECTED) {
            return redirect()->route('market.sell.status');
        }

        return view('marketplace::store.apply', [
            'user' => $request->user(),
            'previous' => $vendor,
            'centers' => (array) config('marketplace.centers', []),
        ]);
    }

    public function store(VendorApplicationRequest $request, OrderNotifier $notifier)
    {
        $user = $request->user();
        $existing = MarketplaceAccess::vendorFor($user);
        if ($existing && $existing->status !== MarketVendor::STATUS_REJECTED) {
            return redirect()->route('market.sell.status');
        }

        $data = $request->validated();
        $documentsDisk = (string) config('marketplace.documents_disk', 'local');
        $mediaDisk = (string) config('marketplace.media_disk', 'public');

        $cardPath = $request->file('disability_card')->store('marketplace/vendor-documents', $documentsDisk);
        $logoPath = $request->hasFile('logo') ? $request->file('logo')->store('marketplace/logos', $mediaDisk) : null;

        $payout = $data['payout_method'] === 'wallet'
            ? ['wallet_number' => $data['wallet_number']]
            : ['bank_name' => $data['bank_name'], 'account_name' => $data['account_name'], 'account_number' => $data['account_number']];

        $vendor = DB::transaction(function () use ($existing, $user, $data, $cardPath, $logoPath, $payout) {
            $vendor = $existing ?? new MarketVendor(['user_id' => $user->getKey()]);
            $vendor->fill([
                'store_name' => $data['store_name'],
                'description' => isset($data['description']) ? strip_tags($data['description']) : null,
                'disability_card_number' => $data['disability_card_number'],
                'disability_card_path' => $cardPath,
                'national_id' => $data['national_id'],
                'phone' => $data['phone'],
                'center' => $data['center'],
                'address' => $data['address'] ?? null,
                'payout_method' => $data['payout_method'],
                'payout_details' => $payout,
            ]);
            if ($logoPath) {
                $vendor->logo = $logoPath;
            }
            if (! $vendor->exists) {
                $vendor->slug = null;
            }
            // Re-application after rejection goes back to review.
            $vendor->status = MarketVendor::STATUS_PENDING;
            $vendor->review_notes = null;
            $vendor->reviewed_by = null;
            $vendor->reviewed_at = null;
            $vendor->save();

            return $vendor;
        });

        $notifier->vendorApplicationSubmitted($vendor);

        return redirect()->route('market.sell.status')
            ->with('success', 'تم استلام طلبك بنجاح. سيتم مراجعته وإبلاغك بالنتيجة.');
    }

    public function status(Request $request)
    {
        $vendor = MarketplaceAccess::vendorFor($request->user());
        if (! $vendor) {
            return redirect()->route('market.sell.apply');
        }

        return view('marketplace::store.application-status', ['vendor' => $vendor]);
    }
}
