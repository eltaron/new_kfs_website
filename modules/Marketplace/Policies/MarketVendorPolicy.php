<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Support\MarketplaceAccess;

class MarketVendorPolicy
{
    public function viewAny(User $user): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function view(User $user, MarketVendor $vendor): bool
    {
        return MarketplaceAccess::isAdmin($user) || $vendor->user_id === $user->getKey();
    }

    /** Only citizens without an existing store may apply (handled in the controller). */
    public function create(User $user): bool
    {
        return MarketplaceAccess::vendorFor($user) === null;
    }

    public function update(User $user, MarketVendor $vendor): bool
    {
        return MarketplaceAccess::isAdmin($user) || ($vendor->user_id === $user->getKey() && $vendor->isApproved());
    }

    public function review(User $user, MarketVendor $vendor): bool
    {
        return MarketplaceAccess::isAdmin($user) && $vendor->user_id !== $user->getKey();
    }

    public function viewDocument(User $user, MarketVendor $vendor): bool
    {
        return MarketplaceAccess::isAdmin($user) || $vendor->user_id === $user->getKey();
    }

    public function delete(User $user, MarketVendor $vendor): bool
    {
        return $user->hasRole('super_admin');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
