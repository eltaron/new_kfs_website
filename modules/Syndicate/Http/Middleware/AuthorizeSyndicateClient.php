<?php

namespace Modules\Syndicate\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Syndicate\Http\ApiResponse;
use Modules\Syndicate\Models\IntegrationAccount;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * يتحقق من:
 *  1) عنوان IP ضمن القائمة المسموح بها (config('syndicate.api.allowed_ips') + قائمة الحساب)،
 *  2) توكن Sanctum صالح لحساب تكامل نشط (IntegrationAccount)،
 *  3) أن التوكن يملك القدرة 'syndicate'.
 */
class AuthorizeSyndicateClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();

        if (! $this->ipAllowed($ip, (array) config('syndicate.api.allowed_ips', []))) {
            return ApiResponse::error(403, 'ip_not_allowed', 'عنوان IP غير مسموح له', 'عنوان IP ' . $ip . ' غير مدرج في قائمة السماح.');
        }

        $account = Auth::guard('sanctum')->user();

        if (! $account) {
            return ApiResponse::error(401, 'unauthenticated', 'غير مصرح', 'يجب إرسال توكن صالح في الترويسة Authorization: Bearer <token>.');
        }

        if (! $account instanceof IntegrationAccount || ! $account->is_active) {
            return ApiResponse::error(403, 'forbidden', 'غير مسموح', 'التوكن لا يخص حساب تكامل نشط لنقابة المهندسين.');
        }

        $request->attributes->set('syndicate_account', $account);

        $ability = (string) config('syndicate.api.ability', 'syndicate');
        if (! $account->tokenCan($ability)) {
            return ApiResponse::error(403, 'missing_ability', 'صلاحية غير كافية', 'التوكن لا يملك القدرة "' . $ability . '".');
        }

        if (! empty($account->allowed_ips) && ! $this->ipAllowed($ip, $account->allowed_ips)) {
            return ApiResponse::error(403, 'ip_not_allowed', 'عنوان IP غير مسموح له', 'عنوان IP ' . $ip . ' غير مسموح لهذا الحساب.');
        }

        Auth::shouldUse('sanctum');
        $request->setUserResolver(fn () => $account);

        if (! $account->last_used_at || $account->last_used_at->lt(now()->subMinute())) {
            $account->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        return $next($request);
    }

    protected function ipAllowed(string $ip, array $allowed): bool
    {
        $allowed = array_values(array_filter(array_map('trim', $allowed)));

        if (! $allowed || in_array('*', $allowed, true)) {
            return true;
        }

        return IpUtils::checkIp($ip, $allowed);
    }
}
