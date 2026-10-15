<?php

namespace App\Services;

use App\Services\Feeds\FeedBuilder;
use Throwable;

/**
 * Фасад: точечное обновление фидов при изменении товаров.
 * Полный rebuild — только если файлов ещё нет или через CLI.
 */
class ProductFeedService
{
    /**
     * Обновить один товар в фидах (create/update). При невалидных данных — удалить из фида.
     */
    public static function sync(int $productId): void
    {
        try {
            app(FeedBuilder::class)->upsert($productId);
        } catch (Throwable) {
            // не блокируем CRUD из‑за фида
        }
    }

    /**
     * @param  list<int>|iterable<int>  $productIds
     */
    public static function syncMany(iterable $productIds): void
    {
        try {
            $ids = [];
            foreach ($productIds as $id) {
                $ids[] = (int) $id;
            }
            app(FeedBuilder::class)->upsertMany($ids);
        } catch (Throwable) {
        }
    }

    public static function remove(int $productId): void
    {
        try {
            app(FeedBuilder::class)->remove($productId);
        } catch (Throwable) {
        }
    }

    /**
     * @param  list<int>|iterable<int>  $productIds
     */
    public static function removeMany(iterable $productIds): void
    {
        try {
            $ids = [];
            foreach ($productIds as $id) {
                $ids[] = (int) $id;
            }
            app(FeedBuilder::class)->removeMany($ids);
        } catch (Throwable) {
        }
    }

    /**
     * @deprecated Используйте sync() — оставлено для старых вызовов.
     */
    public static function schedule(?int $productId = null): void
    {
        if ($productId !== null) {
            self::sync($productId);
        }
    }

    /**
     * Полная сборка (CLI / первичная). Не для админ-кнопки.
     *
     * @return array<string, mixed>
     */
    public static function generate(): array
    {
        return app(FeedBuilder::class)->build();
    }

    /**
     * @return array<string, mixed>
     */
    public static function meta(): array
    {
        return app(FeedBuilder::class)->meta();
    }

    public static function exists(string $type): bool
    {
        return app(FeedBuilder::class)->exists($type);
    }

    public static function publicUrl(string $type): string
    {
        return $type === 'facebook'
            ? route('feeds.facebook')
            : route('feeds.google');
    }

    public static function content(string $type): ?string
    {
        return app(FeedBuilder::class)->fileContents($type);
    }
}
