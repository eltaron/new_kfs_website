<?php

namespace Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\SecureUploads;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Authorized access to sensitive uploads (national-ID images, complaint and
 * emergency attachments, Estidama application documents).
 * Serves from the private disk, falling back to the public disk for records
 * uploaded before files were moved to private storage.
 */
class SecureFileController extends Controller
{
    public function show(string $type, string $id, string $field, ?int $index = null)
    {
        abort_unless(isset(SecureUploads::TYPES[$type]), 404);

        [$class, $fields] = SecureUploads::TYPES[$type];
        abort_unless(in_array($field, $fields, true), 404);

        $model = $class::findOrFail($id);

        abort_unless(Gate::allows('view-secure-file', $model), 403);

        $value = $model->getAttribute($field);
        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $value = json_decode($value, true) ?: [];
        }

        $path = is_array($value) ? (array_values($value)[$index ?? 0] ?? null) : $value;

        abort_if(blank($path) || ! is_string($path) || str_contains($path, '..'), 404);

        $disk = SecureUploads::diskFor($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, basename($path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
