<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * Categories and reviews: managed by marketplace admins only.
 */
class MarketAdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function view(User $user, Model $model): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function create(User $user): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function update(User $user, Model $model): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return MarketplaceAccess::isAdmin($user);
    }
}
