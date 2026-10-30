<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\TwoFactorAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginCitizenController extends Controller
{
    /**
     * Show the application's login form.
     */
    public function create()
    {
        return view('auth.login-citizen');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Flow: credentials -> account status gate -> (optional) Google
     * Authenticator challenge -> session login.
     */
    public function store(Request $request)
    {
        $request->validate([
            'national_id' => 'required|string|digits:14',
            'password' => 'required|string',
        ]);

        $credentials = [
            'national_id' => $request->national_id,
            'password' => $request->password,
        ];

        $failed = fn () => back()->withErrors([
            'national_id' => 'الرقم القومي أو كلمة المرور غير صحيحة، أو الحساب غير مُفعَّل.',
        ])->onlyInput('national_id');

        $guard = Auth::guard('web');
        /** @var User|null $user */
        $user = $guard->getProvider()->retrieveByCredentials($credentials);

        if (! $user || ! $guard->getProvider()->validateCredentials($user, $credentials)) {
            return $failed();
        }

        if ($message = static::statusBlockMessage($user)) {
            return back()->withErrors(['national_id' => $message])->onlyInput('national_id');
        }

        // Password is correct; if Google Authenticator is enabled, defer login.
        if (TwoFactorAuthenticator::isEnabled($user)) {
            $request->session()->regenerate();
            $request->session()->put([
                TwoFactorAuthenticator::SESSION_PENDING_USER => (string) $user->getKey(),
                TwoFactorAuthenticator::SESSION_PENDING_REMEMBER => $request->boolean('remember'),
                TwoFactorAuthenticator::SESSION_PENDING_EXPIRES => now()->addMinutes((int) config('security.two_factor.challenge_ttl', 10))->timestamp,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        $guard->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return static::redirectAfterLogin($user);
    }

    /**
     * Returns an Arabic error message if the account status forbids login.
     * Staff accounts (any non-citizen role) are never blocked for "pending",
     * because staff rows created without an explicit status default to it.
     */
    public static function statusBlockMessage(User $user): ?string
    {
        $status = $user->status;

        if (in_array($status, config('security.login.blocked_statuses', ['rejected', 'inactive']), true)) {
            return $status === 'rejected'
                ? 'تم رفض طلب تفعيل هذا الحساب. يرجى التواصل مع الدعم الفني.'
                : 'هذا الحساب موقوف. يرجى التواصل مع الدعم الفني.';
        }

        if ($status === 'pending'
            && config('security.login.block_pending', true)
            && ! TwoFactorAuthenticator::isStaff($user)) {
            return 'حسابك قيد المراجعة من قبل الإدارة، وسيتم إخطارك فور تفعيله.';
        }

        return null;
    }

    public static function redirectAfterLogin(User $user)
    {
        if ($user->hasRole(['super_admin', 'Super Admin', 'Admin'])) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended(route('citizen.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
