<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Support\MarketplaceAccess;

class MarketOrderItemPolicy
{
    public function viewAny(User $user): bool
    {
        return MarketplaceAccess::approvedVendorFor($user) !== null || MarketplaceAccess::isAdmin($user);
    }

    public function view(User $user, MarketOrderItem $item): bool
    {
        return $this->owns($user, $item) || MarketplaceAccess::isAdmin($user);
    }

    /** Fulfilment updates: the owning vendor (or an admin). */
    public function update(User $user, MarketOrderItem $item): bool
    {
        return $this->owns($user, $item) || MarketplaceAccess::isAdmin($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, MarketOrderItem $item): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function owns(User $user, MarketOrderItem $item): bool
    {
        $vendor = MarketplaceAccess::approvedVendorFor($user);

        return $vendor !== null && (int) $item->vendor_id === (int) $vendor->getKey();
    }
}
