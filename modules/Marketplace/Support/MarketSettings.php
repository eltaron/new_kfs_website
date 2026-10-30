<?php

namespace Modules\Marketplace\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Marketplace\Models\MarketSetting;

/**
 * Runtime-editable marketplace settings (from /store-admin), falling back to
 * config/marketplace.php.
 */
final class MarketSettings
{
    private const CACHE_KEY = 'marketplace.settings';

    /** @return array<string, string|null> */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            try {
                return Schema::hasTable('market_settings')
                    ? MarketSetting::query()->pluck('value', 'key')->all()
                    : [];
            } catch (\Throwable) {
                return [];
            }
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : config('marketplace.'.$key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        MarketSetting::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function commissionRate(): float
    {
        return max(0.0, min(100.0, (float) self::get('default_commission_rate', 0)));
    }

    public static function shippingFeePerVendor(): float
    {
        return max(0.0, (float) self::get('shipping_fee_per_vendor', 0));
    }

    public static function codEnabled(): bool
    {
        return filter_var(self::get('cod_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }
}
