<?php

namespace App\Http\Middleware;

use App\Services\Security\TwoFactorAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces an authenticated user who has Google Authenticator enabled to pass
 * the code challenge once per session before using the site or any panel.
 *
 * Usage:
 *  - appended to the "web" group (citizen site + Livewire endpoints)
 *  - added to each Filament panel's authMiddleware as "...:panel", which also
 *    enforces 2FA *setup* for staff when security.two_factor.require_for_staff is on.
 */
class EnsureTwoFactorIsVerified
{
    /** Routes reachable while a challenge is pending. */
    protected array $challengeExempt = [
        'two-factor.verify',
        'two-factor.verify.store',
        'two-factor.challenge',
        'two-factor.challenge.store',
        'logout',
        'filament.*.auth.logout',
    ];

    /** Routes reachable while 2FA setup is required but not completed. */
    protected array $setupExempt = [
        'two-factor.*',
        'logout',
        'filament.*.auth.logout',
        'filament.*.pages.two-factor-authentication',
    ];

    public function handle(Request $request, Closure $next, ?string $context = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (TwoFactorAuthenticator::isEnabled($user)) {
            if (TwoFactorAuthenticator::sessionIsVerified($user) || $request->routeIs(...$this->challengeExempt)) {
                return $next($request);
            }

            if ($request->isMethod('GET') && ! $request->expectsJson() && ! $request->header('X-Livewire')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return $this->deny($request, route('two-factor.verify'), 'يرجى إدخال رمز التحقق الثنائي للمتابعة.');
        }

        if ($context === 'panel'
            && TwoFactorAuthenticator::isRequiredFor($user)
            && ! $request->routeIs(...$this->setupExempt)) {
            $target = $this->panelSetupUrl() ?? route('two-factor.settings');

            return $this->deny($request, $target, 'يجب تفعيل المصادقة الثنائية (Google Authenticator) لحسابك قبل استخدام لوحة التحكم.');
        }

        return $next($request);
    }

    protected function deny(Request $request, string $url, string $message): Response
    {
        if ($request->expectsJson() || $request->header('X-Livewire')) {
            return response()->json(['message' => $message, 'redirect' => $url], 423);
        }

        return redirect()->to($url)->with('warning', $message);
    }

    protected function panelSetupUrl(): ?string
    {
        try {
            $panel = \Filament\Facades\Filament::getCurrentPanel();
            if (! $panel) {
                return null;
            }

            $name = 'filament.' . $panel->getId() . '.pages.two-factor-authentication';

            return \Illuminate\Support\Facades\Route::has($name) ? route($name) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
