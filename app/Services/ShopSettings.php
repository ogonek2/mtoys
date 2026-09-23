<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShopSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ShopSettings
{
    public const CACHE_KEY = 'shop.settings.v1';

    /**
     * Defaults used when DB is empty / unavailable.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'min_order_total' => 1000,
        'min_order_enabled' => true,
        'free_delivery_from' => 10000,
        'free_delivery_enabled' => true,
        'announcement_text' => 'Безкоштовна доставка від {free_delivery_from}₴',
        'store_name' => 'Mtoys',
        'currency_symbol' => '₴',
        'currency_label' => 'грн',
        'usd_rate' => 41.5,
        'contact_phone' => '063 63 100 41',
        'contact_email' => 'office@mtoys.com.ua',
        'contact_address' => 'вул. Фабрична, номер контейнер 2177, 7 км, Одеса',
        'checkout_notice' => '',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        try {
            $stored = Cache::remember(self::CACHE_KEY, 3600, function () {
                return ShopSetting::query()
                    ->select(['key', 'value'])
                    ->orderBy('key')
                    ->get()
                    ->pluck('value', 'key')
                    ->all();
            });
        } catch (Throwable) {
            $stored = [];
        }

        $merged = self::DEFAULTS;
        foreach ($stored as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                $merged[$key] = $value;
                continue;
            }

            // Empty strings in DB should not wipe non-empty defaults (contacts, store name, etc.).
            if (is_string(self::DEFAULTS[$key]) && self::DEFAULTS[$key] !== '' && trim((string) $value) === '') {
                continue;
            }

            $merged[$key] = self::castValue($key, $value);
        }

        return $merged;
    }

    /**
     * Public subset for storefront JS / Blade.
     *
     * @return array<string, mixed>
     */
    public static function public(): array
    {
        $all = self::all();

        return [
            'min_order_total' => (int) $all['min_order_total'],
            'min_order_enabled' => (bool) $all['min_order_enabled'],
            'free_delivery_from' => (int) $all['free_delivery_from'],
            'free_delivery_enabled' => (bool) $all['free_delivery_enabled'],
            'announcement_text' => self::announcementText(),
            'store_name' => (string) $all['store_name'],
            'currency_symbol' => (string) $all['currency_symbol'],
            'currency_label' => (string) $all['currency_label'],
            'contact_phone' => (string) $all['contact_phone'],
            'contact_email' => (string) $all['contact_email'],
            'contact_address' => (string) $all['contact_address'],
            'checkout_notice' => (string) $all['checkout_notice'],
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        return (float) self::get($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        return (bool) self::get($key, $default);
    }

    public static function usdRate(): float
    {
        return max(0.0, self::getFloat('usd_rate', 41.5));
    }

    public static function minOrderTotal(): int
    {
        if (! self::getBool('min_order_enabled', true)) {
            return 0;
        }

        return max(0, self::getInt('min_order_total', 1000));
    }

    public static function freeDeliveryFrom(): int
    {
        if (! self::getBool('free_delivery_enabled', true)) {
            return 0;
        }

        return max(0, self::getInt('free_delivery_from', 10000));
    }

    public static function announcementText(): string
    {
        $all = self::all();
        $template = trim((string) ($all['announcement_text'] ?? ''));
        if ($template === '') {
            $template = (string) self::DEFAULTS['announcement_text'];
        }

        return strtr($template, [
            '{free_delivery_from}' => number_format((int) $all['free_delivery_from'], 0, '.', ' '),
            '{min_order_total}' => number_format((int) $all['min_order_total'], 0, '.', ' '),
            '{store_name}' => (string) $all['store_name'],
            '{currency_symbol}' => (string) $all['currency_symbol'],
            '{currency_label}' => (string) $all['currency_label'],
            '{usd_rate}' => number_format((float) $all['usd_rate'], 2, '.', ' '),
        ]);
    }

    /**
     * Recalculate UAH prices from USD for products that have USD set.
     */
    public static function recalculateProductPricesFromUsd(?float $rate = null): int
    {
        $rate = $rate ?? self::usdRate();
        if ($rate <= 0) {
            return 0;
        }

        $updated = 0;

        Product::query()
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('price_usd')->where('price_usd', '>', 0);
                })->orWhere(function ($q) {
                    $q->whereNotNull('wholesale_price_usd')->where('wholesale_price_usd', '>', 0);
                });
            })
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($rate, &$updated) {
                foreach ($products as $product) {
                    $dirty = false;

                    if ($product->price_usd !== null && (float) $product->price_usd > 0) {
                        $product->price = round((float) $product->price_usd * $rate, 2);
                        $dirty = true;
                    }

                    if ($product->wholesale_price_usd !== null && (float) $product->wholesale_price_usd > 0) {
                        $product->wholesale_price = round((float) $product->wholesale_price_usd * $rate, 2);
                        $dirty = true;
                    }

                    if ($dirty) {
                        $product->saveQuietly();
                        $updated++;
                    }
                }
            });

        if ($updated > 0 && class_exists(ProductFeedService::class)) {
            try {
                ProductFeedService::generate();
            } catch (Throwable) {
                // ignore feed errors
            }
        }

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $stored = self::serializeValue($key, $value);

            ShopSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $stored],
            );
        }

        self::forgetCache();
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function castValue(string $key, mixed $value): mixed
    {
        $default = self::DEFAULTS[$key] ?? null;

        if (is_bool($default)) {
            return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
        }

        if (is_float($default)) {
            return (float) $value;
        }

        if (is_int($default)) {
            return (int) $value;
        }

        return $value === null ? '' : (string) $value;
    }

    private static function serializeValue(string $key, mixed $value): string
    {
        $default = self::DEFAULTS[$key] ?? null;

        if (is_bool($default) || is_bool($value)) {
            return $value || $value === 1 || $value === '1' || $value === 'true' ? '1' : '0';
        }

        if (is_float($default)) {
            return (string) round((float) $value, 4);
        }

        if (is_int($default) || is_numeric($value)) {
            return (string) ((int) $value);
        }

        return (string) ($value ?? '');
    }
}
