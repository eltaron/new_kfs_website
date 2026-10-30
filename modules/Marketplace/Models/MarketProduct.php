<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Marketplace\Support\Slug;

/**
 * @property int $id
 * @property int $vendor_id
 * @property string $name
 * @property string $price
 * @property int $stock
 * @property int $reserved_stock
 * @property string $status draft|pending_review|published|rejected
 */
class MarketProduct extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_DRAFT => 'مسودة',
        self::STATUS_PENDING => 'بانتظار المراجعة',
        self::STATUS_PUBLISHED => 'منشور',
        self::STATUS_REJECTED => 'مرفوض',
    ];

    protected $table = 'market_products';

    protected $fillable = [
        'vendor_id', 'category_id', 'name', 'slug', 'description', 'price', 'compare_at_price',
        'stock', 'variants', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'stock' => 'integer',
        'reserved_stock' => 'integer',
        'variants' => 'array',
        'is_featured' => 'boolean',
        'rating_avg' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (MarketProduct $product) {
            if (blank($product->slug) || $product->isDirty('slug')) {
                $product->slug = Slug::unique(static::class, $product->slug ?: $product->name, $product->getKey(), 'product');
            }
            if ($product->isDirty('status') && $product->status === self::STATUS_PUBLISHED && ! $product->published_at) {
                $product->published_at = now();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(MarketVendor::class, 'vendor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(MarketProductImage::class, 'product_id')->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(MarketProductImage::class, 'product_id')->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketReview::class, 'product_id');
    }

    /** Published products from approved vendors: what the public storefront may show / sell. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('market_products.status', self::STATUS_PUBLISHED)
            ->whereHas('vendor', fn (Builder $q) => $q->where('status', MarketVendor::STATUS_APPROVED));
    }

    public function isPurchasable(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->vendor?->isApproved()
            && $this->availableStock() > 0
            && ! $this->trashed();
    }

    public function availableStock(): int
    {
        return max(0, (int) $this->stock - (int) $this->reserved_stock);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** @return array<int, array{name:string, options: array<int,string>}> */
    public function variantGroups(): array
    {
        return collect((array) ($this->variants ?? []))
            ->filter(fn ($g) => is_array($g) && filled($g['name'] ?? null) && ! empty($g['options']))
            ->map(fn ($g) => [
                'name' => (string) $g['name'],
                'options' => array_values(array_filter(array_map('strval', (array) $g['options']), 'strlen')),
            ])
            ->values()
            ->all();
    }

    /** Validates a submitted variant selection and returns its canonical label ("المقاس: M، اللون: أزرق"). */
    public function normalizeVariant(array $selection): ?string
    {
        $parts = [];
        foreach ($this->variantGroups() as $i => $group) {
            $value = $selection[$i] ?? null;
            if (! is_string($value) || ! in_array($value, $group['options'], true)) {
                return null;
            }
            $parts[] = $group['name'].': '.$value;
        }

        return implode('، ', $parts);
    }

    public function url(): string
    {
        return route('market.products.show', $this->slug);
    }
}
