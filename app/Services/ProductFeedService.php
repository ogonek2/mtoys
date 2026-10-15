<?php

namespace App\Services;

use App\Helpers\FileUploadHelper;
use App\Models\Product;
use App\Support\Database;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Лёгкая генерация фидов: только нужные колонки, короткие тексты, без «простыней» description.
 */
class ProductFeedService
{
    public const DIR = 'feeds';

    public const GOOGLE_FILE = 'google-merchant.xml';

    public const FACEBOOK_FILE = 'facebook-catalog.csv';

    public const META_CACHE_KEY = 'product-feeds.meta';

    public const DIRTY_CACHE_KEY = 'product-feeds.dirty';

    /** Лимиты под Merchant/Meta и чтобы не раздувать XML. */
    private const TITLE_MAX = 100;

    private const DESCRIPTION_MAX = 500;

    private const BRAND_MAX = 50;

    private const EXTRA_IMAGES_MAX = 2;

    private const CHUNK = 50;

    public static function schedule(): void
    {
        Cache::put(self::DIRTY_CACHE_KEY, true, now()->addHours(6));
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
     * @return array<string, mixed>
     */
    public static function generate(): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '256M');

        $started = microtime(true);
        Log::channel('single')->info('product-feeds: generate start');

        Storage::disk('public')->makeDirectory(self::DIR);

        $googleTmp = Storage::disk('public')->path(self::DIR.'/'.self::GOOGLE_FILE.'.tmp');
        $facebookTmp = Storage::disk('public')->path(self::DIR.'/'.self::FACEBOOK_FILE.'.tmp');
        @unlink($googleTmp);
        @unlink($facebookTmp);

        $g = fopen($googleTmp, 'wb');
        $f = fopen($facebookTmp, 'wb');
        if ($g === false || $f === false) {
            throw new RuntimeException('Не удалось создать временные файлы фидов');
        }

        $storeName = (string) (ShopSettings::get('store_name') ?: 'Mtoys');
        $siteUrl = rtrim((string) config('app.url'), '/');

        fwrite($g, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        fwrite($g, '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'."\n");
        fwrite($g, "  <channel>\n");
        fwrite($g, '    <title>'.self::xmlEscape($storeName)."</title>\n");
        fwrite($g, '    <link>'.self::xmlEscape($siteUrl)."</link>\n");
        fwrite($g, '    <description>'.self::xmlEscape($storeName.' product feed')."</description>\n");

        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, [
            'id', 'title', 'description', 'availability', 'condition', 'price', 'link',
            'image_link', 'brand', 'sale_price', 'additional_image_link', 'product_type', 'mpn', 'status',
        ]);

        $included = 0;
        $excluded = 0;
        $total = 0;
        $reasons = [
            'no_name' => 0,
            'no_price' => 0,
            'no_image' => 0,
            'no_url' => 0,
        ];
        $lastId = 0;

        // Не тянем description / characteristics / JSON — только короткие поля.
        $columns = [
            'id',
            'name',
            'articule',
            'price',
            'discount',
            'discount_starts_at',
            'discount_ends_at',
            'image_path',
            'url',
            'availability',
            'condition_item',
            'brand',
            'seo_description',
        ];

        try {
            while (true) {
                $products = Database::retry(function () use ($lastId, $columns) {
                    Database::reconnect();

                    return Product::query()
                        ->select($columns)
                        ->with([
                            'categories:id,name,url',
                            'images:id,product_id,src',
                        ])
                        ->where('id', '>', $lastId)
                        ->orderBy('id')
                        ->limit(self::CHUNK)
                        ->get();
                }, 5);

                if ($products->isEmpty()) {
                    break;
                }

                foreach ($products as $product) {
                    $lastId = (int) $product->id;
                    $total++;

                    $row = self::mapProduct($product, $storeName);
                    if ($row === null) {
                        $excluded++;
                        $reason = self::$lastSkipReason ?? 'no_image';
                        if (isset($reasons[$reason])) {
                            $reasons[$reason]++;
                        }

                        continue;
                    }

                    $included++;
                    self::writeGoogleItem($g, $row);
                    self::writeFacebookRow($f, $row);
                }

                // Сброс памяти между порциями.
                unset($products);
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }

                if ($total % 200 === 0) {
                    Log::channel('single')->info('product-feeds: progress', [
                        'processed' => $total,
                        'included' => $included,
                    ]);
                }
            }
        } catch (Throwable $e) {
            fclose($g);
            fclose($f);
            @unlink($googleTmp);
            @unlink($facebookTmp);
            Log::channel('single')->error('product-feeds: generate failed', [
                'message' => $e->getMessage(),
                'processed' => $total,
            ]);
            throw $e;
        }

        fwrite($g, "  </channel>\n</rss>\n");
        fclose($g);
        fclose($f);

        self::replaceFile(self::DIR.'/'.self::GOOGLE_FILE, $googleTmp);
        self::replaceFile(self::DIR.'/'.self::FACEBOOK_FILE, $facebookTmp);

        Cache::forget(self::DIRTY_CACHE_KEY);
        Cache::forget('product-feeds.build');
        Cache::forget('product-feeds.cancel');

        $meta = [
            'google' => self::publicUrl('google'),
            'facebook' => self::publicUrl('facebook'),
            'count' => $included,
            'catalog_total' => $total,
            'excluded' => $excluded,
            'excluded_reasons' => $reasons,
            'generated_at' => now()->toIso8601String(),
            'google_bytes' => (int) (Storage::disk('public')->size(self::DIR.'/'.self::GOOGLE_FILE) ?: 0),
            'facebook_bytes' => (int) (Storage::disk('public')->size(self::DIR.'/'.self::FACEBOOK_FILE) ?: 0),
        ];

        Cache::forever(self::META_CACHE_KEY, $meta);

        Log::channel('single')->info('product-feeds: generate done', [
            'count' => $included,
            'total' => $total,
            'excluded' => $excluded,
            'seconds' => round(microtime(true) - $started, 2),
            'google_kb' => round($meta['google_bytes'] / 1024, 1),
        ]);

        return $meta;
    }

    /**
     * @return array<string, mixed>
     */
    public static function meta(): array
    {
        $meta = Cache::get(self::META_CACHE_KEY);
        if (! is_array($meta)) {
            $meta = [];
        }

        $googleExists = self::exists('google');
        $facebookExists = self::exists('facebook');

        if ($googleExists) {
            $xml = (string) Storage::disk('public')->get(self::relativePath('google'));
            $fileCount = substr_count($xml, '<item>');
            if ($fileCount > 0) {
                $meta['count'] = $fileCount;
                $meta['google_bytes'] = strlen($xml);
            }
        }

        if ($facebookExists) {
            $meta['facebook_bytes'] = strlen((string) Storage::disk('public')->get(self::relativePath('facebook')));
        }

        return [
            'google' => self::publicUrl('google'),
            'facebook' => self::publicUrl('facebook'),
            'count' => (int) ($meta['count'] ?? 0),
            'catalog_total' => (int) ($meta['catalog_total'] ?? 0),
            'excluded' => (int) ($meta['excluded'] ?? 0),
            'excluded_reasons' => is_array($meta['excluded_reasons'] ?? null) ? $meta['excluded_reasons'] : [],
            'generated_at' => $meta['generated_at'] ?? null,
            'google_bytes' => (int) ($meta['google_bytes'] ?? 0),
            'facebook_bytes' => (int) ($meta['facebook_bytes'] ?? 0),
            'dirty' => (bool) Cache::get(self::DIRTY_CACHE_KEY),
            'google_exists' => $googleExists,
            'facebook_exists' => $facebookExists,
        ];
    }

    public static function exists(string $type): bool
    {
        return Storage::disk('public')->exists(self::relativePath($type));
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

    protected static function replaceFile(string $relative, string $absoluteTmp): void
    {
        $final = Storage::disk('public')->path($relative);
        if (is_file($final)) {
            @unlink($final);
        }
        if (! @rename($absoluteTmp, $final)) {
            if (! @copy($absoluteTmp, $final)) {
                throw new RuntimeException('Не удалось сохранить '.$relative);
            }
            @unlink($absoluteTmp);
        }
    }

    protected static ?string $lastSkipReason = null;

    /**
     * @return array<string, mixed>|null
     */
    protected static function mapProduct(Product $product, string $storeName): ?array
    {
        self::$lastSkipReason = null;

        $title = self::cleanText((string) $product->name, self::TITLE_MAX);
        if ($title === '') {
            self::$lastSkipReason = 'no_name';

            return null;
        }

        $price = round((float) ($product->price ?? 0), 2);
        if ($price <= 0) {
            self::$lastSkipReason = 'no_price';

            return null;
        }

        $image = self::resolveImageUrl($product);
        if ($image === null) {
            self::$lastSkipReason = 'no_image';

            return null;
        }

        $category = $product->categories->first();
        $productUrl = trim((string) $product->url);
        if ($productUrl === '') {
            $productUrl = Product::generateHref($title);
        }
        if ($productUrl === '') {
            self::$lastSkipReason = 'no_url';

            return null;
        }

        $categoryUrl = trim((string) ($category?->url ?? '')) ?: 'catalog';
        $link = self::absoluteUrl(route('catalog_product_page', [
            'category' => $categoryUrl,
            'product' => $productUrl,
        ], false));

        $discount = max(0, min(100, (int) ($product->discount ?? 0)));
        $salePrice = null;
        if ($discount > 0 && self::isDiscountActive($product)) {
            $sale = round($price * (1 - $discount / 100), 2);
            if ($sale > 0 && $sale < $price) {
                $salePrice = $sale;
            }
        }

        $inStock = in_array((string) ($product->availability ?? ''), ['in_stock', '1'], true);
        $condition = match ((string) ($product->condition_item ?? 'new')) {
            'used' => 'used',
            'refurbished' => 'refurbished',
            default => 'new',
        };

        $brand = self::cleanText((string) ($product->brand ?: $storeName), self::BRAND_MAX);
        if ($brand === '') {
            $brand = 'Mtoys';
        }

        $mpn = self::cleanText((string) ($product->articule ?? ''), 40);
        // Только seo_description или название — полный description из БД НЕ читаем.
        $description = self::feedDescription($product, $title, $brand, $category?->name);

        $additionalImages = [];
        foreach ($product->images as $imageRow) {
            $url = self::toFeedImageUrl($imageRow->src ?? null);
            if ($url !== null && $url !== $image && ! in_array($url, $additionalImages, true)) {
                $additionalImages[] = $url;
            }
            if (count($additionalImages) >= self::EXTRA_IMAGES_MAX) {
                break;
            }
        }

        $productType = self::cleanText((string) ($category?->name ?? ''), 100);

        return [
            'id' => (string) $product->id,
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'image_link' => $image,
            'additional_image_links' => $additionalImages,
            'availability' => $inStock ? 'in_stock' : 'out_of_stock',
            'availability_facebook' => $inStock ? 'in stock' : 'out of stock',
            'condition' => $condition,
            'price' => number_format($price, 2, '.', '').' UAH',
            'sale_price' => $salePrice !== null ? number_format($salePrice, 2, '.', '').' UAH' : null,
            'brand' => $brand,
            'mpn' => $mpn !== '' ? $mpn : null,
            'identifier_exists' => $mpn !== '',
            'product_type' => $productType !== '' ? $productType : null,
        ];
    }

    protected static function feedDescription(Product $product, string $title, string $brand, ?string $categoryName): string
    {
        $seo = self::cleanText((string) ($product->seo_description ?? ''), self::DESCRIPTION_MAX);
        if ($seo !== '') {
            return $seo;
        }

        $parts = array_filter([$title, $brand !== '' ? $brand : null, $categoryName]);

        return self::cleanText(implode('. ', $parts), self::DESCRIPTION_MAX);
    }

    protected static function resolveImageUrl(Product $product): ?string
    {
        $main = trim((string) $product->image_path);
        if ($main !== '') {
            $url = self::toFeedImageUrl($main);
            if ($url !== null) {
                return $url;
            }
        }

        foreach ($product->images as $imageRow) {
            $url = self::toFeedImageUrl($imageRow->src ?? null);
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    protected static function toFeedImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || str_contains(mb_strtolower($path), 'no-image')) {
            return null;
        }
        if (str_starts_with($path, '//')) {
            return 'https:'.$path;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return str_starts_with($path, 'http://') ? 'https://'.substr($path, 7) : $path;
        }

        $relative = ltrim($path, '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, 8);
        }

        $cdn = FileUploadHelper::cdnBaseUrl();
        if ($cdn !== '') {
            return $cdn.'/'.$relative;
        }

        return self::absoluteUrl('/storage/'.$relative);
    }

    protected static function absoluteUrl(string $pathOrUrl): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return str_starts_with($pathOrUrl, 'http://')
                ? 'https://'.substr($pathOrUrl, 7)
                : $pathOrUrl;
        }
        if (str_starts_with($pathOrUrl, '//')) {
            return 'https:'.$pathOrUrl;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($pathOrUrl, '/');
    }

    protected static function cleanText(string $value, int $limit): string
    {
        // Убираем HTML, скрипты, лишние пробелы; режем жёстко.
        $value = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $value) ?? $value;
        $value = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $value) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        // Обрезаем очень длинные «слова» (base64 и т.п.).
        if (preg_match('/\S{200,}/u', $value)) {
            $value = preg_replace('/\S{200,}/u', ' ', $value) ?? $value;
            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        }

        return Str::limit($value, $limit, '');
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

    protected static function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $item
     */
    protected static function writeGoogleItem($handle, array $item): void
    {
        $extra = is_array($item['additional_image_links'] ?? null) ? $item['additional_image_links'] : [];

        fwrite($handle, "    <item>\n");
        self::writeG($handle, 'id', (string) $item['id']);
        self::writeG($handle, 'title', (string) $item['title']);
        self::writeG($handle, 'description', (string) $item['description']);
        self::writeG($handle, 'link', (string) $item['link']);
        self::writeG($handle, 'image_link', (string) $item['image_link']);
        foreach ($extra as $url) {
            self::writeG($handle, 'additional_image_link', (string) $url);
        }
        self::writeG($handle, 'availability', (string) $item['availability']);
        self::writeG($handle, 'condition', (string) $item['condition']);
        self::writeG($handle, 'price', (string) $item['price']);
        if (! empty($item['sale_price'])) {
            self::writeG($handle, 'sale_price', (string) $item['sale_price']);
        }
        self::writeG($handle, 'brand', (string) $item['brand']);
        if (! empty($item['mpn'])) {
            self::writeG($handle, 'mpn', (string) $item['mpn']);
        }
        self::writeG($handle, 'identifier_exists', ! empty($item['identifier_exists']) ? 'yes' : 'no');
        if (! empty($item['product_type'])) {
            self::writeG($handle, 'product_type', (string) $item['product_type']);
        }
        fwrite($handle, "      <g:shipping>\n");
        self::writeG($handle, 'country', 'UA', 8);
        self::writeG($handle, 'service', 'Standard', 8);
        self::writeG($handle, 'price', '0.00 UAH', 8);
        fwrite($handle, "      </g:shipping>\n");
        fwrite($handle, "    </item>\n");
    }

    /**
     * @param  resource  $handle
     */
    protected static function writeG($handle, string $name, string $value, int $indent = 6): void
    {
        fwrite($handle, str_repeat(' ', $indent).'<g:'.$name.'>'.self::xmlEscape($value).'</g:'.$name.">\n");
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $item
     */
    protected static function writeFacebookRow($handle, array $item): void
    {
        $extra = is_array($item['additional_image_links'] ?? null) ? $item['additional_image_links'] : [];
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
            implode(',', $extra),
            $item['product_type'] ?? '',
            $item['mpn'] ?? '',
            'active',
        ]);
    }
}
