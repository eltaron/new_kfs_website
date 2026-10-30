<?php

namespace Modules\Marketplace\Support;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Slug
{
    /**
     * URL-safe unique slug. Arabic text is kept (Unicode slug) so links stay
     * readable; falls back to a random token if nothing usable remains.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public static function unique(string $model, string $text, mixed $ignoreId = null, string $fallback = 'item'): string
    {
        $base = self::make($text) ?: $fallback.'-'.Str::lower(Str::random(6));
        $base = Str::limit($base, 110, '');

        $slug = $base;
        $i = 2;
        while (self::exists($model, $slug, $ignoreId)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public static function make(string $text): string
    {
        $text = mb_strtolower(trim($text));
        // Keep Arabic letters, Latin letters and digits; everything else becomes "-".
        $text = preg_replace('/[^\p{Arabic}\p{L}\p{N}]+/u', '-', $text) ?? '';
        // Strip Arabic diacritics / tatweel.
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $text) ?? '';

        return trim($text, '-');
    }

    private static function exists(string $model, string $slug, mixed $ignoreId): bool
    {
        $query = in_array(SoftDeletes::class, class_uses_recursive($model), true)
            ? $model::withTrashed()
            : $model::query();

        return $query->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }
}
