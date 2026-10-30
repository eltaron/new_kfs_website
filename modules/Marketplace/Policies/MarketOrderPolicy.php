<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * Buyers see only their own orders; vendors see orders containing their items
 * (and only their items, enforced by the vendor panel queries); admins see all.
 */
class MarketOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function view(User $user, MarketOrder $order): bool
    {
        if ($order->user_id === $user->getKey() || MarketplaceAccess::isAdmin($user)) {
            return true;
        }
        $vendor = MarketplaceAccess::approvedVendorFor($user);

        return $vendor !== null && $order->items()->where('vendor_id', $vendor->getKey())->exists();
    }

    /** Pay / retry / cancel an unpaid order: the buyer only. */
    public function pay(User $user, MarketOrder $order): bool
    {
        return $order->user_id === $user->getKey();
    }

    public function cancel(User $user, MarketOrder $order): bool
    {
        return $order->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return false; // orders come only from checkout
    }

    public function update(User $user, MarketOrder $order): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function delete(User $user, MarketOrder $order): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
