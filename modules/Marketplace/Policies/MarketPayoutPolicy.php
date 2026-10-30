<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\MarketPayout;
use Modules\Marketplace\Support\MarketplaceAccess;

class MarketPayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return MarketplaceAccess::isAdmin($user) || MarketplaceAccess::approvedVendorFor($user) !== null;
    }

    public function view(User $user, MarketPayout $payout): bool
    {
        if (MarketplaceAccess::isAdmin($user)) {
            return true;
        }
        $vendor = MarketplaceAccess::approvedVendorFor($user);

        return $vendor !== null && (int) $payout->vendor_id === (int) $vendor->getKey();
    }

    public function create(User $user): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function update(User $user, MarketPayout $payout): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function delete(User $user, MarketPayout $payout): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
