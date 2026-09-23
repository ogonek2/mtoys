<?php

namespace App\Services\Products;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Счётчики и скоупы «дыр» в карточках после импорта.
 */
class ProductDataQuality
{
    public const CACHE_TTL = 60;

    public const CACHE_KEY = 'product.data_quality.counts';

    /**
     * @return array{
     *     total: int,
     *     without_price: int,
     *     without_image: int,
     *     without_category: int,
     *     without_description: int,
     *     without_articule: int,
     *     without_brand: int,
     *     without_characteristics: int,
     *     without_catalog: int,
     *     incomplete: int
     * }
     */
    public static function counts(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            return [
                'total' => Product::query()->count(),
                'without_price' => self::withoutPrice()->count(),
                'without_image' => self::withoutImage()->count(),
                'without_category' => self::withoutCategory()->count(),
                'without_description' => self::withoutDescription()->count(),
                'without_articule' => self::withoutArticule()->count(),
                'without_brand' => self::withoutBrand()->count(),
                'without_characteristics' => self::withoutCharacteristics()->count(),
                'without_catalog' => self::withoutCatalog()->count(),
                'incomplete' => self::incomplete()->count(),
            ];
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public static function apply(Builder $query, string $scope): Builder
    {
        return match ($scope) {
            'without_price' => self::withoutPrice($query),
            'without_image' => self::withoutImage($query),
            'without_category' => self::withoutCategory($query),
            'without_description' => self::withoutDescription($query),
            'without_articule' => self::withoutArticule($query),
            'without_brand' => self::withoutBrand($query),
            'without_characteristics' => self::withoutCharacteristics($query),
            'without_catalog' => self::withoutCatalog($query),
            'incomplete' => self::incomplete($query),
            default => $query,
        };
    }

    /**
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function withoutPrice(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNull('price')
                ->orWhere('price', '')
                ->orWhereRaw('CAST(price AS DECIMAL(14,2)) <= 0');
        });
    }

    /**
     * Нет главного фото или стоит заглушка no-image.
     *
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function withoutImage(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNull('image_path')
                ->orWhere('image_path', '')
                ->orWhere('image_path', 'like', '%no-image%');
        });
    }

    /**
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function withoutCategory(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->whereDoesntHave('categories');
    }

    /**
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function withoutDescription(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNull('description')
                ->orWhere('description', '')
                ->orWhereRaw('TRIM(description) = ?', ['']);
        });
    }

    /**
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function withoutArticule(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->where(function (Builder $builder): void {
            $builder->whereNull('articule')->orWhere('articule', '');
        });
    }

    /**
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function withoutBrand(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->where(function (Builder $builder): void {
            $builder->whereNull('brand')->orWhere('brand', '');
        });
    }

    /**
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function withoutCharacteristics(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNull('characteristics')
                ->orWhere('characteristics', '')
                ->orWhere('characteristics', '[]')
                ->orWhere('characteristics', '{}')
                ->orWhereJsonLength('characteristics', 0);
        });
    }

    /**
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function withoutCatalog(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->whereDoesntHave('catalogs');
    }

    /**
     * Критичные пробелы: нет цены, фото или категории.
     *
     * @param  Builder<Product>|null  $query
     * @return Builder<Product>
     */
    public static function incomplete(?Builder $query = null): Builder
    {
        $query ??= Product::query();

        return $query->where(function (Builder $builder): void {
            $builder
                ->where(function (Builder $inner): void {
                    self::withoutPrice($inner);
                })
                ->orWhere(function (Builder $inner): void {
                    self::withoutImage($inner);
                })
                ->orWhere(function (Builder $inner): void {
                    self::withoutCategory($inner);
                });
        });
    }
}
