<?php

namespace Modules\Marketplace\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Marketplace\Models\MarketVendor;

/**
 * Who may do what in the marketplace (shared by policies, panels and controllers).
 */
final class MarketplaceAccess
{
    public const ADMIN_PANEL = 'store-admin';
    public const VENDOR_PANEL = 'vendor';

    public static function isAdmin(?Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->hasAnyRole((array) config('marketplace.admin_roles', ['super_admin']));
    }

    public static function vendorFor(?Authenticatable $user): ?MarketVendor
    {
        if (! $user instanceof User) {
            return null;
        }

        return MarketVendor::query()->where('user_id', $user->getKey())->first();
    }

    public static function approvedVendorFor(?Authenticatable $user): ?MarketVendor
    {
        $vendor = self::vendorFor($user);

        return $vendor?->isApproved() ? $vendor : null;
    }

    public static function canAccessPanel(?Authenticatable $user, string $panelId): bool
    {
        return match ($panelId) {
            self::ADMIN_PANEL => self::isAdmin($user),
            self::VENDOR_PANEL => self::approvedVendorFor($user) !== null,
            default => false,
        };
    }

    /** Vendor of the user currently signed into the vendor panel (approved only). */
    public static function currentVendor(): ?MarketVendor
    {
        return self::approvedVendorFor(auth()->user());
    }

    public static function currentVendorId(): int
    {
        return (int) (self::currentVendor()?->getKey() ?? 0);
    }
}
