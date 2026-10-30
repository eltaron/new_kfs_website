<?php

namespace App\Services\Security;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Google Authenticator (TOTP) helper shared by the citizen site and the
 * three Filament panels.
 *
 * Storage (users table):
 *  - two_factor_secret          encrypted base32 secret
 *  - two_factor_recovery_codes  encrypted JSON array of one-time codes
 *  - two_factor_confirmed_at    set once the user proved possession of the device
 */
class TwoFactorAuthenticator
{
    /** Session key marking that the current session passed the 2FA challenge. */
    public const SESSION_VERIFIED = 'two_factor.verified_user';

    /** Session keys for the pre-auth (password ok, code pending) citizen login. */
    public const SESSION_PENDING_USER = 'two_factor.login.id';
    public const SESSION_PENDING_REMEMBER = 'two_factor.login.remember';
    public const SESSION_PENDING_EXPIRES = 'two_factor.login.expires';

    public function __construct(protected Google2FA $engine)
    {
    }

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    public function otpauthUrl(User $user, string $secret): string
    {
        $holder = $user->email ?: ($user->national_id ?: (string) $user->getKey());

        return $this->engine->getQRCodeUrl(config('security.two_factor.issuer'), $holder, $secret);
    }

    public function qrCodeSvg(User $user, string $secret, int $size = 200): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));

        $svg = $writer->writeString($this->otpauthUrl($user, $secret));

        // Strip the XML prolog so the SVG can be inlined in HTML.
        return trim(preg_replace('/^<\?xml.*?\?>/', '', $svg));
    }

    public static function isEnabled(?User $user): bool
    {
        return $user !== null
            && filled($user->two_factor_secret)
            && $user->two_factor_confirmed_at !== null;
    }

    /**
     * Staff = any role that is not a citizen role.
     */
    public static function isStaff(?User $user): bool
    {
        if (! $user || ! method_exists($user, 'getRoleNames')) {
            return false;
        }

        $citizenRoles = config('security.login.citizen_roles', ['citizen']);

        return $user->getRoleNames()->diff($citizenRoles)->isNotEmpty();
    }

    public static function isRequiredFor(?User $user): bool
    {
        return (bool) config('security.two_factor.require_for_staff') && static::isStaff($user);
    }

    /**
     * Verify a 6-digit TOTP code against a secret, rejecting replays of a code
     * already used by the same user within its validity window.
     */
    public function verifyCode(string $secret, ?string $code, ?User $user = null): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $cacheKey = $user ? 'two_factor.last_ts.' . $user->getKey() : null;
        $lastTimestamp = $cacheKey ? Cache::get($cacheKey) : null;

        try {
            $timestamp = $this->engine->verifyKeyNewer(
                $secret,
                $code,
                $lastTimestamp,
                (int) config('security.two_factor.window', 1)
            );
        } catch (\Throwable) {
            return false;
        }

        if ($timestamp === false) {
            return false;
        }

        if ($cacheKey) {
            Cache::put($cacheKey, $timestamp === true ? $this->engine->getTimestamp() : $timestamp, now()->addMinutes(5));
        }

        return true;
    }

    public function verifyUserCode(User $user, ?string $code): bool
    {
        return filled($user->two_factor_secret)
            && $this->verifyCode($user->two_factor_secret, $code, $user);
    }

    /**
     * Consume a recovery code (single use). Returns true if it was valid.
     */
    public function useRecoveryCode(User $user, ?string $code): bool
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return false;
        }

        $codes = collect($this->recoveryCodes($user));
        $match = $codes->first(fn ($stored) => hash_equals(strtoupper($stored), $code));

        if ($match === null) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => json_encode($codes->reject(fn ($c) => $c === $match)->values()->all()),
        ])->save();

        return true;
    }

    public function recoveryCodes(User $user): array
    {
        $raw = $user->two_factor_recovery_codes;
        if (blank($raw)) {
            return [];
        }

        $decoded = is_array($raw) ? $raw : json_decode($raw, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    public function generateRecoveryCodes(): Collection
    {
        return Collection::times(
            (int) config('security.two_factor.recovery_codes', 8),
            fn () => strtoupper(Str::random(5) . '-' . Str::random(5))
        );
    }

    /** Start enabling: store a fresh unconfirmed secret. */
    public function beginEnable(User $user): string
    {
        $secret = $this->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $secret;
    }

    /** Confirm the pending secret with a code. Returns the new recovery codes or null. */
    public function confirm(User $user, ?string $code): ?array
    {
        if (blank($user->two_factor_secret) || $user->two_factor_confirmed_at !== null) {
            return null;
        }

        if (! $this->verifyUserCode($user, $code)) {
            return null;
        }

        $codes = $this->generateRecoveryCodes()->all();

        $user->forceFill([
            'two_factor_recovery_codes' => json_encode($codes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $codes;
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes()->all();

        $user->forceFill(['two_factor_recovery_codes' => json_encode($codes)])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public static function markSessionVerified(User $user): void
    {
        session()->put(self::SESSION_VERIFIED, (string) $user->getKey());
    }

    public static function sessionIsVerified(User $user): bool
    {
        return session(self::SESSION_VERIFIED) === (string) $user->getKey();
    }
}
