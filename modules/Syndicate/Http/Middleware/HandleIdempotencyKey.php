<?php

namespace Modules\Syndicate\Http\Middleware;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Modules\Syndicate\Http\ApiResponse;
use Modules\Syndicate\Models\IdempotencyKey;
use Modules\Syndicate\Models\IntegrationAccount;
use Symfony\Component\HttpFoundation\Response;

/**
 * دعم الترويسة Idempotency-Key على طلبات POST:
 *  - نفس المفتاح + نفس الجسم ⇒ تعاد الاستجابة المخزنة (Idempotent-Replayed: true) دون تنفيذ ثانٍ.
 *  - نفس المفتاح + جسم مختلف ⇒ 422 idempotency_key_reused.
 */
class HandleIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        $account = $request->attributes->get('syndicate_account');

        if (! $request->isMethod('POST') || $key === '' || ! $account instanceof IntegrationAccount) {
            return $next($request);
        }

        if (mb_strlen($key) > 100) {
            return ApiResponse::error(422, 'invalid_idempotency_key', 'مفتاح Idempotency غير صالح', 'الحد الأقصى 100 حرف.');
        }

        $hash = hash('sha256', $request->method() . '|' . $request->path() . '|' . $request->getContent());
        $ttl = (int) config('syndicate.api.idempotency_ttl_hours', 24);

        $existing = IdempotencyKey::where('integration_account_id', $account->getKey())->where('key', $key)->first();

        if ($existing && $existing->created_at->lt(now()->subHours($ttl))) {
            $existing->delete();
            $existing = null;
        }

        if ($existing) {
            if (! hash_equals($existing->request_hash, $hash)) {
                return ApiResponse::error(422, 'idempotency_key_reused', 'مفتاح Idempotency مستخدم', 'تم استخدام نفس المفتاح مع طلب مختلف.');
            }

            return response($existing->response_body, $existing->response_status, [
                'Content-Type' => 'application/json',
                'Idempotent-Replayed' => 'true',
            ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() < 500) {
            try {
                IdempotencyKey::create([
                    'integration_account_id' => $account->getKey(),
                    'key' => $key,
                    'method' => $request->method(),
                    'path' => mb_substr($request->path(), 0, 255),
                    'request_hash' => $hash,
                    'response_status' => $response->getStatusCode(),
                    'response_body' => (string) $response->getContent(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // طلب متزامن بنفس المفتاح سبقنا؛ نتجاهل.
            }
        }

        return $response;
    }
}
