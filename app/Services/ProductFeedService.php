<?php

namespace App\Services;

use App\Services\Feeds\FeedBuilder;

/**
 * Фасад для совместимости со старыми вызовами (Product, Import, ShopSettings).
 */
class ProductFeedService
{
    public const DIRTY_CACHE_KEY = FeedBuilder::DIRTY_KEY;

    public static function schedule(): void
    {
        app(FeedBuilder::class)->markDirty();
    }

    public static function flushDirty(): bool
    {
        if (! cache()->pull(FeedBuilder::DIRTY_KEY)) {
            return false;
        }

        self::generate();

        return true;
    }

    /**
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
