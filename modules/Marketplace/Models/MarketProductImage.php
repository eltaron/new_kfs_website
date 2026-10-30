<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MarketProductImage extends Model
{
    protected $table = 'market_product_images';

    protected $fillable = ['product_id', 'path', 'alt_text', 'sort_order'];

    protected static function booted(): void
    {
        // Accessibility rule: every product image must describe itself.
        static::saving(function (MarketProductImage $image) {
            $alt = trim((string) $image->alt_text);
            if (mb_strlen($alt) < 3) {
                throw ValidationException::withMessages([
                    'alt_text' => 'يجب كتابة وصف نصي للصورة (3 أحرف على الأقل) ليتمكن قارئ الشاشة من قراءته.',
                ]);
            }
            $image->alt_text = $alt;
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'product_id');
    }

    public function url(): string
    {
        if (str_starts_with($this->path, 'http') || str_starts_with($this->path, '/')) {
            return $this->path;
        }

        return Storage::disk(config('marketplace.media_disk', 'public'))->url($this->path);
    }
}
