<?php

namespace App\Services;

use App\Helpers\FileUploadHelper;
use App\Models\Product;
use App\Models\productImage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductFeedService
{
    public const DIR = 'feeds';

    public const GOOGLE_FILE = 'google-merchant.xml';

    public const FACEBOOK_FILE = 'facebook-catalog.csv';

    public const META_CACHE_KEY = 'product-feeds.meta';

    public const DIRTY_CACHE_KEY = 'product-feeds.dirty';

    /**
     * Пометить фиды устаревшими и пересобрать после ответа (HTTP)
     * или позже через artisan / финализатор импорта (CLI).
     */
    public static function schedule(): void
    {
        Cache::put(self::DIRTY_CACHE_KEY, true, now()->addHours(6));

        if (app()->runningInConsole()) {
            return;
        }

        dispatch(static function (): void {
            self::flushDirty();
        })->afterResponse();
    }

    public static function flushDirty(): bool
    {
        if (! Cache::pull(self::DIRTY_CACHE_KEY)) {
            return false;
        }

        self::generate();

        return true;
    }

    /**
     * @return array{google: string, facebook: string, count: int, generated_at: string}
     */
    public static function generate(): array
    {
        $lock = Cache::lock('product-feeds.generate', 180);

        return $lock->block(10, function (): array {
            Storage::disk('public')->makeDirectory(self::DIR);

            $items = self::collectItems();
            $storeName = (string) (ShopSettings::get('store_name') ?: 'Mtoys');
            $siteUrl = rtrim((string) config('app.url'), '/');

            $googleXml = self::buildGoogleXml($items, $storeName, $siteUrl);
            $facebookCsv = self::buildFacebookCsv($items);

            Storage::disk('public')->put(self::DIR.'/'.self::GOOGLE_FILE, $googleXml);
            Storage::disk('public')->put(self::DIR.'/'.self::FACEBOOK_FILE, $facebookCsv);

            Cache::forget(self::DIRTY_CACHE_KEY);

            $meta = [
                'google' => self::publicUrl('google'),
                'facebook' => self::publicUrl('facebook'),
                'count' => count($items),
                'generated_at' => now()->toIso8601String(),
                'google_bytes' => strlen($googleXml),
                'facebook_bytes' => strlen($facebookCsv),
            ];

            Cache::forever(self::META_CACHE_KEY, $meta);

            return $meta;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function meta(): array
    {
        $meta = Cache::get(self::META_CACHE_KEY);

        if (is_array($meta)) {
            $meta['google'] = self::publicUrl('google');
            $meta['facebook'] = self::publicUrl('facebook');
            $meta['dirty'] = (bool) Cache::get(self::DIRTY_CACHE_KEY);
            $meta['google_exists'] = self::exists('google');
            $meta['facebook_exists'] = self::exists('facebook');

            return $meta;
        }

        return [
            'google' => self::publicUrl('google'),
            'facebook' => self::publicUrl('facebook'),
            'count' => 0,
            'generated_at' => null,
            'dirty' => (bool) Cache::get(self::DIRTY_CACHE_KEY),
            'google_exists' => self::exists('google'),
            'facebook_exists' => self::exists('facebook'),
        ];
    }

    public static function exists(string $type): bool
    {
        return Storage::disk('public')->exists(self::relativePath($type));
    }

    public static function absolutePath(string $type): string
    {
        return Storage::disk('public')->path(self::relativePath($type));
    }

    public static function relativePath(string $type): string
    {
        return self::DIR.'/'.($type === 'facebook' ? self::FACEBOOK_FILE : self::GOOGLE_FILE);
    }

    public static function publicUrl(string $type): string
    {
        return $type === 'facebook'
            ? route('feeds.facebook')
            : route('feeds.google');
    }

    public static function content(string $type): ?string
    {
        $path = self::relativePath($type);

        if (! Storage::disk('public')->exists($path)) {
            try {
                self::generate();
            } catch (Throwable) {
                return null;
            }
        }

        return Storage::disk('public')->get($path);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function collectItems(): array
    {
        $items = [];

        Product::query()
            ->with([
                'categories.parentCategory.parentCategory.parentCategory',
                'images',
            ])
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($products) use (&$items): void {
                foreach ($products as $product) {
                    $row = self::mapProduct($product);
                    if ($row !== null) {
                        $items[] = $row;
                    }
                }
            });

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function mapProduct(Product $product): ?array
    {
        $title = trim(strip_tags((string) $product->name));
        if ($title === '') {
            return null;
        }

        $image = FileUploadHelper::publicUrl($product->image_path);
        if ($image === '' || str_contains(mb_strtolower($image), 'no-image')) {
            return null;
        }

        $category = $product->categories->first();
        $categoryUrl = $category?->url ?? 'catalog';
        $productUrl = trim((string) $product->url);
        if ($productUrl === '') {
            return null;
        }

        $link = route('catalog_product_page', [
            'category' => $categoryUrl,
            'product' => $productUrl,
        ]);

        $price = round((float) $product->price, 2);
        $discount = max(0, min(100, (int) ($product->discount ?? 0)));
        $salePrice = null;
        if ($discount > 0 && self::isDiscountActive($product)) {
            $salePrice = round($price * (1 - $discount / 100), 2);
        }

        $availabilityRaw = (string) ($product->availability ?? '');
        $inStock = in_array($availabilityRaw, ['in_stock', '1'], true);

        $condition = match ((string) ($product->condition_item ?? 'new')) {
            'used' => 'used',
            'refurbished' => 'refurbished',
            default => 'new',
        };

        $description = self::plainDescription($product);
        $brand = trim((string) ($product->brand ?? '')) ?: (string) (ShopSettings::get('store_name') ?: 'Mtoys');
        $id = trim((string) ($product->articule ?? '')) ?: (string) $product->id;

        $additionalImages = [];
        foreach ($product->images as $imageRow) {
            /** @var productImage $imageRow */
            $url = FileUploadHelper::publicUrl($imageRow->src);
            if ($url !== '' && $url !== $image && ! in_array($url, $additionalImages, true)) {
                $additionalImages[] = $url;
            }
            if (count($additionalImages) >= 10) {
                break;
            }
        }

        $productType = self::categoryPath($category);

        return [
            'id' => $id,
            'title' => Str::limit($title, 150, ''),
            'description' => $description,
            'link' => $link,
            'image_link' => $image,
            'additional_image_links' => $additionalImages,
            'availability' => $inStock ? 'in_stock' : 'out_of_stock',
            'availability_facebook' => $inStock ? 'in stock' : 'out of stock',
            'condition' => $condition,
            'price' => number_format($price, 2, '.', '').' UAH',
            'sale_price' => $salePrice !== null
                ? number_format($salePrice, 2, '.', '').' UAH'
                : null,
            'brand' => Str::limit($brand, 70, ''),
            'mpn' => trim((string) ($product->articule ?? '')) ?: null,
            'product_type' => $productType,
        ];
    }

    protected static function isDiscountActive(Product $product): bool
    {
        $today = now()->startOfDay();

        if ($product->discount_starts_at !== null && $product->discount_starts_at->gt($today)) {
            return false;
        }

        if ($product->discount_ends_at !== null && $product->discount_ends_at->lt($today)) {
            return false;
        }

        return true;
    }

    protected static function plainDescription(Product $product): string
    {
        $raw = trim((string) ($product->description ?: $product->seo_description ?: $product->name));
        $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            $text = trim((string) $product->name);
        }

        return Str::limit($text, 5000, '');
    }

    protected static function categoryPath(mixed $category): ?string
    {
        if ($category === null) {
            return null;
        }

        $parts = [];
        $current = $category;
        $guard = 0;

        while ($current && $guard < 8) {
            array_unshift($parts, trim((string) $current->name));
            $current = $current->parentCategory ?? null;
            $guard++;
        }

        $parts = array_values(array_filter($parts));

        return $parts === [] ? null : implode(' > ', $parts);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected static function buildGoogleXml(array $items, string $storeName, string $siteUrl): string
    {
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

        $xml->startElement('channel');
        $xml->writeElement('title', $storeName);
        $xml->writeElement('link', $siteUrl);
        $xml->writeElement('description', $storeName.' product feed');

        foreach ($items as $item) {
            $xml->startElement('item');
            self::writeGoogleTag($xml, 'g:id', $item['id']);
            self::writeGoogleTag($xml, 'g:title', $item['title']);
            self::writeGoogleTag($xml, 'g:description', $item['description']);
            self::writeGoogleTag($xml, 'g:link', $item['link']);
            self::writeGoogleTag($xml, 'g:image_link', $item['image_link']);
            foreach ($item['additional_image_links'] as $extra) {
                self::writeGoogleTag($xml, 'g:additional_image_link', $extra);
            }
            self::writeGoogleTag($xml, 'g:availability', $item['availability']);
            self::writeGoogleTag($xml, 'g:condition', $item['condition']);
            self::writeGoogleTag($xml, 'g:price', $item['price']);
            if (! empty($item['sale_price'])) {
                self::writeGoogleTag($xml, 'g:sale_price', $item['sale_price']);
            }
            self::writeGoogleTag($xml, 'g:brand', $item['brand']);
            if (! empty($item['mpn'])) {
                self::writeGoogleTag($xml, 'g:mpn', $item['mpn']);
            }
            if (! empty($item['product_type'])) {
                self::writeGoogleTag($xml, 'g:product_type', $item['product_type']);
            }
            $xml->endElement(); // item
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss

        return $xml->outputMemory();
    }

    protected static function writeGoogleTag(\XMLWriter $xml, string $name, string $value): void
    {
        $xml->startElement($name);
        $xml->writeCdata($value);
        $xml->endElement();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected static function buildFacebookCsv(array $items): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        $headers = [
            'id',
            'title',
            'description',
            'availability',
            'condition',
            'price',
            'link',
            'image_link',
            'brand',
            'sale_price',
            'additional_image_link',
            'product_type',
            'mpn',
        ];

        fputcsv($handle, $headers);

        foreach ($items as $item) {
            fputcsv($handle, [
                $item['id'],
                $item['title'],
                $item['description'],
                $item['availability_facebook'],
                $item['condition'],
                $item['price'],
                $item['link'],
                $item['image_link'],
                $item['brand'],
                $item['sale_price'] ?? '',
                implode(',', $item['additional_image_links']),
                $item['product_type'] ?? '',
                $item['mpn'] ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}
