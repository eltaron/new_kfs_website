<?php

namespace Modules\Syndicate\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;

/**
 * استجابات موحدة بأسلوب JSON:API:
 *   نجاح: {"data": {...}|[...], "meta": {...}, "links": {...}}
 *   خطأ:  {"errors": [{"status":"422","code":"validation_failed","title":"...","detail":"...","source":{"pointer":"/field"}}]}
 */
class ApiResponse
{
    public static function data(mixed $data, int $status = 200, array $meta = [], array $headers = []): JsonResponse
    {
        $body = ['data' => $data];
        if ($meta) {
            $body['meta'] = $meta;
        }
        $body['jsonapi'] = ['version' => '1.1'];

        return response()->json($body, $status, $headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function paginated(LengthAwarePaginator $paginator, callable $transform): JsonResponse
    {
        return response()->json([
            'data' => collect($paginator->items())->map($transform)->values(),
            'meta' => [
                'page' => [
                    'current' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last' => $paginator->lastPage(),
                ],
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
                'last' => $paginator->url($paginator->lastPage()),
            ],
            'jsonapi' => ['version' => '1.1'],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(int $status, string $code, string $title, ?string $detail = null, array $extra = []): JsonResponse
    {
        return response()->json([
            'errors' => [array_filter([
                'status' => (string) $status,
                'code' => $code,
                'title' => $title,
                'detail' => $detail,
            ] + $extra, fn ($v) => $v !== null)],
            'jsonapi' => ['version' => '1.1'],
        ], $status, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function validation(MessageBag $errors): JsonResponse
    {
        $list = [];
        foreach ($errors->messages() as $field => $messages) {
            foreach ($messages as $message) {
                $list[] = [
                    'status' => '422',
                    'code' => 'validation_failed',
                    'title' => 'بيانات غير صحيحة',
                    'detail' => $message,
                    'source' => ['pointer' => '/' . str_replace('.', '/', $field)],
                ];
            }
        }

        return response()->json(['errors' => $list, 'jsonapi' => ['version' => '1.1']], 422, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
