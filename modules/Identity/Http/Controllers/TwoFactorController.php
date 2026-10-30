<?php

namespace Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Citizen\Http\Controllers\LoginCitizenController;
use App\Models\User;
use App\Services\Security\TwoFactorAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Google Authenticator (TOTP) for the citizen site, also used as the
 * post-login verification page for Filament panel sessions.
 */
class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorAuthenticator $twoFactor)
    {
    }

    /* ------------------------------------------------------------------
     | Pre-auth challenge (citizen /login: password ok, code pending)
     * ------------------------------------------------------------------ */

    public function challenge(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', [
            'action' => route('two-factor.challenge.store'),
            'cancelUrl' => route('login'),
        ]);
    }

    public function challengeStore(Request $request)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['national_id' => 'انتهت صلاحية جلسة التحقق، يرجى تسجيل الدخول مرة أخرى.']);
        }

        $this->assertValidCode($request, $user);

        $remember = (bool) $request->session()->pull(TwoFactorAuthenticator::SESSION_PENDING_REMEMBER, false);
        $request->session()->forget([TwoFactorAuthenticator::SESSION_PENDING_USER, TwoFactorAuthenticator::SESSION_PENDING_EXPIRES]);

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        TwoFactorAuthenticator::markSessionVerified($user);

        return LoginCitizenController::redirectAfterLogin($user);
    }

    /* ------------------------------------------------------------------
     | Post-auth verification (Filament login, remember-me sessions)
     * ------------------------------------------------------------------ */

    public function verify(Request $request)
    {
        $user = $request->user();
        if (! TwoFactorAuthenticator::isEnabled($user) || TwoFactorAuthenticator::sessionIsVerified($user)) {
            return redirect()->intended('/');
        }

        return view('auth.two-factor-challenge', [
            'action' => route('two-factor.verify.store'),
            'cancelUrl' => null,
        ]);
    }

    public function verifyStore(Request $request)
    {
        $user = $request->user();
        if (! TwoFactorAuthenticator::isEnabled($user)) {
            return redirect()->intended('/');
        }

        $this->assertValidCode($request, $user);

        $request->session()->regenerate();
        TwoFactorAuthenticator::markSessionVerified($user);

        return redirect()->intended(TwoFactorAuthenticator::isStaff($user) ? '/admin' : route('citizen.dashboard'));
    }

    /* ------------------------------------------------------------------
     | Settings (enable / confirm / disable / recovery codes)
     * ------------------------------------------------------------------ */

    public function settings(Request $request)
    {
        $user = $request->user();
        $pending = filled($user->two_factor_secret) && $user->two_factor_confirmed_at === null;

        return view('account.two-factor', [
            'user' => $user,
            'enabled' => TwoFactorAuthenticator::isEnabled($user),
            'pending' => $pending,
            'qrSvg' => $pending ? $this->twoFactor->qrCodeSvg($user, $user->two_factor_secret) : null,
            'secret' => $pending ? $user->two_factor_secret : null,
            'recoveryCodes' => session('two_factor_recovery_codes'),
            'remainingCodes' => TwoFactorAuthenticator::isEnabled($user) ? count($this->twoFactor->recoveryCodes($user)) : 0,
            'required' => TwoFactorAuthenticator::isRequiredFor($user),
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();

        $this->assertPassword($user, $request->input('password'));

        if (TwoFactorAuthenticator::isEnabled($user)) {
            return redirect()->route('two-factor.settings');
        }

        $this->twoFactor->beginEnable($user);

        return redirect()->route('two-factor.settings')
            ->with('status', 'امسح رمز QR بتطبيق Google Authenticator ثم أدخل الرمز المكوَّن من 6 أرقام لتأكيد التفعيل.');
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();

        $codes = $this->twoFactor->confirm($user, $request->input('code'));

        if ($codes === null) {
            throw ValidationException::withMessages(['code' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.']);
        }

        TwoFactorAuthenticator::markSessionVerified($user);

        return redirect()->route('two-factor.settings')
            ->with('status', 'تم تفعيل المصادقة الثنائية بنجاح. احفظ رموز الاسترداد في مكان آمن.')
            ->with('two_factor_recovery_codes', $codes);
    }

    public function cancel(Request $request)
    {
        $user = $request->user();
        if (! TwoFactorAuthenticator::isEnabled($user)) {
            $this->twoFactor->disable($user);
        }

        return redirect()->route('two-factor.settings');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string', 'code' => 'required|string']);
        $user = $request->user();

        $this->assertPassword($user, $request->input('password'));

        if (TwoFactorAuthenticator::isRequiredFor($user)) {
            throw ValidationException::withMessages(['code' => 'المصادقة الثنائية إلزامية لحسابات الموظفين ولا يمكن تعطيلها.']);
        }

        if (! $this->twoFactor->verifyUserCode($user, $request->input('code'))
            && ! $this->twoFactor->useRecoveryCode($user, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'رمز التحقق غير صحيح.']);
        }

        $this->twoFactor->disable($user);
        $request->session()->forget(TwoFactorAuthenticator::SESSION_VERIFIED);

        return redirect()->route('two-factor.settings')->with('status', 'تم تعطيل المصادقة الثنائية.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();

        $this->assertPassword($user, $request->input('password'));

        abort_unless(TwoFactorAuthenticator::isEnabled($user), 404);

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);

        return redirect()->route('two-factor.settings')
            ->with('status', 'تم إنشاء رموز استرداد جديدة، والرموز القديمة لم تعد صالحة.')
            ->with('two_factor_recovery_codes', $codes);
    }

    /* ------------------------------------------------------------------ */

    protected function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get(TwoFactorAuthenticator::SESSION_PENDING_USER);
        $expires = (int) $request->session()->get(TwoFactorAuthenticator::SESSION_PENDING_EXPIRES, 0);

        if (! $id || $expires < now()->timestamp) {
            $request->session()->forget([
                TwoFactorAuthenticator::SESSION_PENDING_USER,
                TwoFactorAuthenticator::SESSION_PENDING_REMEMBER,
                TwoFactorAuthenticator::SESSION_PENDING_EXPIRES,
            ]);

            return null;
        }

        $user = User::find($id);

        return TwoFactorAuthenticator::isEnabled($user) ? $user : null;
    }

    protected function assertValidCode(Request $request, User $user): void
    {
        $request->validate([
            'code' => 'nullable|string|max:20',
            'recovery_code' => 'nullable|string|max:50',
        ]);

        $code = $request->input('code');
        $recovery = $request->input('recovery_code');

        $ok = filled($recovery)
            ? $this->twoFactor->useRecoveryCode($user, $recovery)
            : (filled($code) && $this->twoFactor->verifyUserCode($user, $code));

        if (! $ok) {
            throw ValidationException::withMessages([
                filled($recovery) ? 'recovery_code' : 'code' => filled($recovery)
                    ? 'رمز الاسترداد غير صحيح أو سبق استخدامه.'
                    : 'رمز التحقق غير صحيح أو منتهي الصلاحية.',
            ]);
        }
    }

    protected function assertPassword(User $user, ?string $password): void
    {
        if (! Hash::check((string) $password, $user->password)) {
            throw ValidationException::withMessages(['password' => 'كلمة المرور غير صحيحة.']);
        }
    }
}
