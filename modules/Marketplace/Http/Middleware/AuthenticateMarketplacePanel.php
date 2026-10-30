<?php

namespace Modules\Marketplace\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * Panel gate for /vendor and /store-admin.
 *
 * These panels have no login page of their own: visitors sign in through the
 * site's citizen login (/login) and come back via the intended URL. Access is
 * decided by MarketplaceAccess (approved vendor / marketplace admin roles), so
 * the shared User::canAccessPanel() does not need to know about these panels.
 */
class AuthenticateMarketplacePanel extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return; /** @phpstan-ignore-line */
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        $user = $guard->user();
        $panelId = Filament::getCurrentPanel()?->getId() ?? '';

        if (MarketplaceAccess::canAccessPanel($user, $panelId)) {
            return;
        }

        if ($panelId === MarketplaceAccess::VENDOR_PANEL && ! $request->expectsJson() && ! $request->header('X-Livewire')) {
            // Not (yet) an approved vendor: show the application status page instead of a bare 403.
            throw new HttpResponseException(redirect()->route('market.sell.status'));
        }

        abort(403);
    }

    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
