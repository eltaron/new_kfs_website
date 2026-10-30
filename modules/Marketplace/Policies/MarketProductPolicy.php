<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * Vendors manage only their own products; marketplace admins moderate all.
 */
class MarketProductPolicy
{
    public function viewAny(User $user): bool
    {
        return MarketplaceAccess::isAdmin($user) || MarketplaceAccess::approvedVendorFor($user) !== null;
    }

    public function view(User $user, MarketProduct $product): bool
    {
        return MarketplaceAccess::isAdmin($user) || $this->owns($user, $product);
    }

    public function create(User $user): bool
    {
        return MarketplaceAccess::approvedVendorFor($user) !== null;
    }

    public function update(User $user, MarketProduct $product): bool
    {
        return $this->owns($user, $product) || MarketplaceAccess::isAdmin($user);
    }

    public function delete(User $user, MarketProduct $product): bool
    {
        return $this->owns($user, $product) || MarketplaceAccess::isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return MarketplaceAccess::approvedVendorFor($user) !== null || MarketplaceAccess::isAdmin($user);
    }

    public function moderate(User $user, MarketProduct $product): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function restore(User $user, MarketProduct $product): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function forceDelete(User $user, MarketProduct $product): bool
    {
        return false;
    }

    private function owns(User $user, MarketProduct $product): bool
    {
        $vendor = MarketplaceAccess::approvedVendorFor($user);

        return $vendor !== null && (int) $product->vendor_id === (int) $vendor->getKey();
    }
}
