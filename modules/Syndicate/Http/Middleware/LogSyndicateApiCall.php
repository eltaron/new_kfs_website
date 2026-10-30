<?php

namespace Modules\Syndicate\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\Syndicate\Models\ApiLog;
use Modules\Syndicate\Models\IntegrationAccount;
use Symfony\Component\HttpFoundation\Response;

/**
 * أول طبقة في واجهة النقابة: يفرض JSON ويسجل كل استدعاء (بما فيها المرفوضة) في syndicate_api_logs.
 */
class LogSyndicateApiCall
{
    protected array $redact = ['password', 'token', 'secret', 'authorization'];

    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        $started = microtime(true);

        $response = $next($request);

        try {
            $account = $request->attributes->get('syndicate_account');
            $errorCode = null;
            if ($response->getStatusCode() >= 400) {
                $decoded = json_decode((string) $response->getContent(), true);
                $errorCode = Arr::get($decoded, 'errors.0.code');
            }

            ApiLog::create([
                'integration_account_id' => $account instanceof IntegrationAccount ? $account->getKey() : null,
                'token_id' => $account instanceof IntegrationAccount ? $account->currentAccessToken()?->getKey() : null,
                'method' => $request->method(),
                'path' => mb_substr('/' . ltrim($request->path(), '/'), 0, 255),
                'route_name' => $request->route()?->getName(),
                'ip' => $request->ip(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'idempotency_key' => mb_substr((string) $request->header('Idempotency-Key'), 0, 255) ?: null,
                'request_body' => config('syndicate.api.log_request_bodies') && ! $request->isMethod('GET')
                    ? $this->sanitize($request->all()) : ($request->query() ?: null),
                'error_code' => $errorCode,
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[syndicate-api] audit log failed: ' . $e->getMessage());
        }

        return $response;
    }

    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            } elseif (in_array(strtolower((string) $key), $this->redact, true)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
