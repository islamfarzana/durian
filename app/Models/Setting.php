<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * Marketplace settings an admin can change, with the value used until they do.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'store_name' => 'Durian',
        'tagline' => 'Groceries, fashion, gadgets and more, delivered to your door',
        'hotline' => '',
        'support_email' => '',
        'free_delivery_threshold' => '999',
        'default_delivery_fee' => '60',
        'cod_enabled' => '1',
        'delivery_days_ahead' => '3',
        'slot_lead_minutes' => '60',
        'currency_symbol' => '৳',
        'min_payout_amount' => '500',
    ];

    private const CACHE_KEY = 'marketplace.settings';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetLoaded());
        static::deleted(fn () => static::forgetLoaded());
    }

    /**
     * Settings are read many times per request, so they are kept on the
     * container for the rest of it as well as in the cache between requests.
     */
    public static function get(string $key): string
    {
        if (! app()->bound(self::CACHE_KEY)) {
            app()->instance(self::CACHE_KEY, Cache::rememberForever(self::CACHE_KEY, fn (): array => static::query()->pluck('value', 'key')->all()));
        }

        $stored = app(self::CACHE_KEY);

        return (string) ($stored[$key] ?? self::DEFAULTS[$key] ?? '');
    }

    private static function forgetLoaded(): void
    {
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::CACHE_KEY);
    }

    public static function number(string $key): float
    {
        return (float) static::get($key);
    }

    public static function enabled(string $key): bool
    {
        return static::get($key) === '1';
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function store(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
    }
}
