<?php

namespace App\Filament\Security\Pages;

use App\Services\Security\TwoFactorAuthenticator;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

/**
 * Google Authenticator setup page shared by the admin, gis and estidama panels.
 * Registered in each PanelProvider via ->pages([...]) and linked from the user menu.
 */
class TwoFactorAuthentication extends Page
{
    protected static string $view = 'filament.security.two-factor-authentication';

    protected static ?string $slug = 'two-factor-authentication';

    protected static ?string $title = 'المصادقة الثنائية';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $password = null;

    public ?string $code = null;

    public array $recoveryCodes = [];

    public function getViewData(): array
    {
        $user = auth()->user();
        $service = app(TwoFactorAuthenticator::class);
        $pending = filled($user->two_factor_secret) && $user->two_factor_confirmed_at === null;

        return [
            'enabled' => TwoFactorAuthenticator::isEnabled($user),
            'pending' => $pending,
            'required' => TwoFactorAuthenticator::isRequiredFor($user),
            'qrSvg' => $pending ? new HtmlString($service->qrCodeSvg($user, $user->two_factor_secret)) : null,
            'secret' => $pending ? trim(chunk_split($user->two_factor_secret, 4, ' ')) : null,
            'confirmedAt' => $user->two_factor_confirmed_at,
            'remainingCodes' => TwoFactorAuthenticator::isEnabled($user) ? count($service->recoveryCodes($user)) : 0,
        ];
    }

    public function enable(): void
    {
        if (! $this->checkPassword()) {
            return;
        }

        // Re-enabling would wipe the confirmed secret: that is a 2FA disable
        // without a code. Disabling must go through disable() (password + code).
        if (TwoFactorAuthenticator::isEnabled(auth()->user())) {
            return;
        }

        app(TwoFactorAuthenticator::class)->beginEnable(auth()->user());
        $this->reset('password', 'code');
    }

    public function confirm(): void
    {
        $this->resetErrorBag();
        if (! $this->rateLimitOk()) {
            return;
        }

        $user = auth()->user();
        $codes = app(TwoFactorAuthenticator::class)->confirm($user, $this->code);

        if ($codes === null) {
            $this->addError('code', 'رمز التحقق غير صحيح أو منتهي الصلاحية.');

            return;
        }

        TwoFactorAuthenticator::markSessionVerified($user);
        $this->recoveryCodes = $codes;
        $this->reset('code', 'password');

        Notification::make()->title('تم تفعيل المصادقة الثنائية بنجاح')->success()->send();
    }

    public function cancelSetup(): void
    {
        $user = auth()->user();
        if (! TwoFactorAuthenticator::isEnabled($user)) {
            app(TwoFactorAuthenticator::class)->disable($user);
        }
    }

    public function regenerateCodes(): void
    {
        if (! $this->checkPassword()) {
            return;
        }

        $user = auth()->user();
        if (! TwoFactorAuthenticator::isEnabled($user)) {
            return;
        }

        $this->recoveryCodes = app(TwoFactorAuthenticator::class)->regenerateRecoveryCodes($user);
        $this->reset('password');

        Notification::make()->title('تم إنشاء رموز استرداد جديدة')->success()->send();
    }

    public function disable(): void
    {
        $user = auth()->user();

        if (TwoFactorAuthenticator::isRequiredFor($user)) {
            $this->addError('code', 'المصادقة الثنائية إلزامية لحسابات الموظفين ولا يمكن تعطيلها.');

            return;
        }

        if (! $this->checkPassword() || ! $this->rateLimitOk()) {
            return;
        }

        $service = app(TwoFactorAuthenticator::class);
        if (! $service->verifyUserCode($user, $this->code) && ! $service->useRecoveryCode($user, $this->code)) {
            $this->addError('code', 'رمز التحقق غير صحيح.');

            return;
        }

        $service->disable($user);
        session()->forget(TwoFactorAuthenticator::SESSION_VERIFIED);
        $this->reset('password', 'code', 'recoveryCodes');

        Notification::make()->title('تم تعطيل المصادقة الثنائية')->warning()->send();
    }

    protected function checkPassword(): bool
    {
        $this->resetErrorBag();

        if (! Hash::check((string) $this->password, auth()->user()->password)) {
            $this->addError('password', 'كلمة المرور غير صحيحة.');

            return false;
        }

        return true;
    }

    protected function rateLimitOk(): bool
    {
        $key = 'filament-2fa|' . auth()->id() . '|' . request()->ip();

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('code', 'محاولات كثيرة، يرجى الانتظار دقيقة ثم المحاولة مجددًا.');

            return false;
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        return true;
    }
}
