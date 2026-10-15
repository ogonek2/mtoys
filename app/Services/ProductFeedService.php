<?php

namespace App\Services;

use App\Helpers\FileUploadHelper;
use App\Models\Product;
use App\Models\productImage;
use App\Support\Database;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductFeedService
{
    public const DIR = 'feeds';

    public const GOOGLE_FILE = 'google-merchant.xml';

    public const FACEBOOK_FILE = 'facebook-catalog.csv';

    public const META_CACHE_KEY = 'product-feeds.meta';

    public const DIRTY_CACHE_KEY = 'product-feeds.dirty';

    public const BUILD_CACHE_KEY = 'product-feeds.build';

    /** Размер страницы для веб-генерации (короткий HTTP-запрос). */
    public const PAGE_SIZE = 40;

    /**
     * Пометить фиды устаревшими. Полная пересборка — кроном / вручную
     * (постранично в админке), чтобы не ронять PHP по таймауту.
     */
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
     * Полная генерация (CLI / крон): крутит страницы до конца.
     *
     * @return array<string, mixed>
     */
    public static function generate(): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        self::startBuild();

        $guard = 0;
        while ($guard < 5000) {
            $guard++;
            $state = self::processNextPages(app()->runningInConsole() ? 10 : 2);
            if (($state['status'] ?? '') !== 'running') {
                break;
            }
        }

        $state = self::buildStatus();
        if (($state['status'] ?? '') === 'failed') {
            throw new RuntimeException((string) ($state['error'] ?? 'Ошибка генерации фидов'));
        }

        return self::meta();
    }

    /**
     * Старт постраничной сборки (админка / CLI).
     *
     * @return array<string, mixed>
     */
    public static function startBuild(): array
    {
        self::cancelBuild();

        $total = 0;
        try {
            $total = (int) Database::retry(static function (): int {
                Database::reconnect();

                return Product::query()->count();
            }, 5);
        } catch (Throwable $e) {
            Log::channel('single')->warning('product-feeds: count failed, build without total', [
                'message' => $e->getMessage(),
            ]);
        }

        $token = Str::lower(Str::random(12));
        $buildDir = self::DIR.'/build/'.$token;

        Storage::disk('public')->makeDirectory($buildDir);
        Storage::disk('public')->put($buildDir.'/items.jsonl', '');

        $state = [
            'status' => 'running',
            'token' => $token,
            'build_dir' => $buildDir,
            'after_id' => 0,
            'page' => 0,
            'pages_total' => $total > 0 ? max(1, (int) ceil($total / self::PAGE_SIZE)) : 1,
            'total' => $total,
            'processed' => 0,
            'included' => 0,
            'excluded' => 0,
            'reasons' => [
                'no_name' => 0,
                'no_price' => 0,
                'no_image' => 0,
                'no_url' => 0,
                'duplicate_id' => 0,
            ],
            'seen_ids' => [],
            'error' => null,
            'started_at' => now()->toIso8601String(),
            'percent' => 0,
        ];

        self::saveBuildState($state);
        Log::channel('single')->info('product-feeds: paginated build started', [
            'token' => $token,
            'total' => $total,
            'pages' => $state['pages_total'],
        ]);

        return $state;
    }

    /**
     * Обработать N следующих страниц каталога.
     *
     * @return array<string, mixed>
     */
    public static function processNextPages(int $pages = 1): array
    {
        $pages = max(1, min(20, $pages));
        $state = self::buildStatus();

        if ($state === null || ($state['status'] ?? '') !== 'running') {
            return $state ?? ['status' => 'idle', 'percent' => 0];
        }

        @set_time_limit(120);

        try {
            for ($i = 0; $i < $pages; $i++) {
                $state = self::processOnePage($state);
                if (($state['status'] ?? '') !== 'running') {
                    break;
                }
            }
        } catch (Throwable $e) {
            Log::channel('single')->error('product-feeds: page failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
            $state['status'] = 'failed';
            $state['error'] = $e->getMessage() !== '' ? $e->getMessage() : $e::class;
            self::saveBuildState($state);
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected static function processOnePage(array $state): array
    {
        $afterId = (int) ($state['after_id'] ?? 0);

        $products = Database::retry(function () use ($afterId) {
            Database::reconnect();

            return Product::query()
                ->with([
                    'categories:id,name,url,parent_id',
                    'categories.parentCategory:id,name,parent_id',
                    'images:id,product_id,src',
                ])
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit(self::PAGE_SIZE)
                ->get();
        }, 5);

        if ($products->isEmpty()) {
            if ((int) ($state['total'] ?? 0) <= 0) {
                $state['total'] = (int) ($state['processed'] ?? 0);
            }

            return self::finalizeBuild($state);
        }

        $lines = [];
        $seenIds = $state['seen_ids'] ?? [];
        if (! is_array($seenIds)) {
            $seenIds = [];
        }

        foreach ($products as $product) {
            $state['after_id'] = (int) $product->id;
            $state['processed'] = (int) ($state['processed'] ?? 0) + 1;

            $row = self::mapProduct($product);
            if ($row === null) {
                $state['excluded'] = (int) ($state['excluded'] ?? 0) + 1;
                $reason = self::$lastSkipReason ?? 'no_image';
                if (isset($state['reasons'][$reason])) {
                    $state['reasons'][$reason]++;
                }

                continue;
            }

            if (isset($seenIds[$row['id']])) {
                $state['excluded'] = (int) ($state['excluded'] ?? 0) + 1;
                $state['reasons']['duplicate_id'] = (int) ($state['reasons']['duplicate_id'] ?? 0) + 1;

                continue;
            }

            $seenIds[$row['id']] = 1;
            $state['included'] = (int) ($state['included'] ?? 0) + 1;
            $lines[] = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (count($seenIds) > 5000) {
            $seenIds = array_slice($seenIds, -2000, preserve_keys: true);
        }
        $state['seen_ids'] = $seenIds;
        $state['page'] = (int) ($state['page'] ?? 0) + 1;
        $state['pages_total'] = max(
            (int) ($state['pages_total'] ?? 1),
            (int) $state['page'] + 1,
        );

        $total = (int) ($state['total'] ?? 0);
        if ($total > 0) {
            $state['percent'] = min(99, (int) floor(((int) $state['processed'] / $total) * 100));
        } else {
            $state['percent'] = min(95, (int) $state['page'] * 4);
        }

        if ($lines !== []) {
            $path = Storage::disk('public')->path($state['build_dir'].'/items.jsonl');
            file_put_contents($path, implode("\n", $lines)."\n", FILE_APPEND | LOCK_EX);
        }

        self::saveBuildState($state);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected static function finalizeBuild(array $state): array
    {
        $state['status'] = 'finalizing';
        $state['percent'] = 99;
        self::saveBuildState($state);

        $items = self::readBuildItems((string) $state['build_dir']);
        $storeName = (string) (ShopSettings::get('store_name') ?: 'Mtoys');
        $siteUrl = self::siteUrl();

        Storage::disk('public')->makeDirectory(self::DIR);

        $googleXml = self::buildGoogleXml($items, $storeName, $siteUrl);
        $facebookCsv = self::buildFacebookCsv($items);

        self::putAtomically(self::DIR.'/'.self::GOOGLE_FILE, $googleXml);
        self::putAtomically(self::DIR.'/'.self::FACEBOOK_FILE, $facebookCsv);

        Cache::forget(self::DIRTY_CACHE_KEY);

        $meta = [
            'google' => self::publicUrl('google'),
            'facebook' => self::publicUrl('facebook'),
            'count' => count($items),
            'catalog_total' => (int) ($state['total'] ?? count($items)),
            'excluded' => (int) ($state['excluded'] ?? 0),
            'excluded_reasons' => $state['reasons'] ?? [],
            'generated_at' => now()->toIso8601String(),
            'google_bytes' => strlen($googleXml),
            'facebook_bytes' => strlen($facebookCsv),
        ];

        Cache::forever(self::META_CACHE_KEY, $meta);

        self::cleanupBuildDir((string) ($state['build_dir'] ?? ''));

        $state['status'] = 'done';
        $state['percent'] = 100;
        $state['included'] = count($items);
        $state['seen_ids'] = [];
        $state['finished_at'] = now()->toIso8601String();
        self::saveBuildState($state);

        Log::channel('single')->info('product-feeds: paginated build done', [
            'count' => $meta['count'],
            'total' => $meta['catalog_total'],
        ]);

        // Коротко живёт для UI, потом можно очистить.
        Cache::put(self::BUILD_CACHE_KEY, $state, now()->addMinutes(30));

        return $state;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function readBuildItems(string $buildDir): array
    {
        $path = Storage::disk('public')->path($buildDir.'/items.jsonl');
        if (! is_file($path)) {
            return [];
        }

        $items = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = json_decode($line, true);
            if (is_array($row) && isset($row['id'])) {
                $items[] = $row;
            }
        }

        fclose($handle);

        return $items;
    }

    protected static function cleanupBuildDir(string $buildDir): void
    {
        if ($buildDir === '' || ! str_starts_with($buildDir, self::DIR.'/build/')) {
            return;
        }

        Storage::disk('public')->deleteDirectory($buildDir);
    }

    public static function cancelBuild(): void
    {
        $state = Cache::get(self::BUILD_CACHE_KEY);
        if (is_array($state) && ! empty($state['build_dir'])) {
            self::cleanupBuildDir((string) $state['build_dir']);
        }
        Cache::forget(self::BUILD_CACHE_KEY);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buildStatus(): ?array
    {
        $state = Cache::get(self::BUILD_CACHE_KEY);

        return is_array($state) ? $state : null;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function saveBuildState(array $state): void
    {
        Cache::put(self::BUILD_CACHE_KEY, $state, now()->addHours(2));
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

        // Сверяем count с реальным XML — cache мог остаться от сорванной генерации.
        if ($googleExists) {
            $xml = (string) Storage::disk('public')->get(self::relativePath('google'));
            $fileCount = substr_count($xml, '<item>');
            if ($fileCount > 0) {
                $meta['count'] = $fileCount;
                $meta['google_bytes'] = strlen($xml);
            }
        }

        if ($facebookExists) {
            $csv = (string) Storage::disk('public')->get(self::relativePath('facebook'));
            $meta['facebook_bytes'] = strlen($csv);
        }

        if (empty($meta['catalog_total'])) {
            try {
                Database::reconnect();
                $meta['catalog_total'] = (int) Database::retry(
                    static fn (): int => Product::query()->count()
                );
            } catch (Throwable) {
                $meta['catalog_total'] = (int) ($meta['count'] ?? 0);
            }
        }

        return [
            'google' => self::publicUrl('google'),
            'facebook' => self::publicUrl('facebook'),
            'count' => (int) ($meta['count'] ?? 0),
            'catalog_total' => (int) ($meta['catalog_total'] ?? 0),
            'excluded' => (int) ($meta['excluded'] ?? max(0, (int) ($meta['catalog_total'] ?? 0) - (int) ($meta['count'] ?? 0))),
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

    protected static function putAtomically(string $relativePath, string $contents): void
    {
        $tmp = $relativePath.'.tmp';
        Storage::disk('public')->put($tmp, $contents);

        $diskRoot = Storage::disk('public')->path('');
        $from = $diskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tmp);
        $to = $diskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (is_file($to)) {
            @unlink($to);
        }

        if (! @rename($from, $to)) {
            Storage::disk('public')->put($relativePath, $contents);
            Storage::disk('public')->delete($tmp);
        }
    }

    protected static function siteUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    protected static ?string $lastSkipReason = null;

    /**
     * @return array<string, mixed>|null
     */
    protected static function mapProduct(Product $product): ?array
    {
        self::$lastSkipReason = null;

        $title = self::cleanText((string) $product->name, 150);
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

        $availabilityRaw = (string) ($product->availability ?? '');
        $inStock = in_array($availabilityRaw, ['in_stock', '1'], true);

        $condition = match ((string) ($product->condition_item ?? 'new')) {
            'used' => 'used',
            'refurbished' => 'refurbished',
            default => 'new',
        };

        $brand = self::cleanText((string) ($product->brand ?: ShopSettings::get('store_name') ?: 'Mtoys'), 70);
        $mpn = self::cleanText((string) ($product->articule ?? ''), 70);
        // Стабильный уникальный id для Merchant/Meta (артикул может дублироваться).
        $id = (string) $product->id;

        $additionalImages = [];
        foreach ($product->images as $imageRow) {
            /** @var productImage $imageRow */
            $url = self::toFeedImageUrl($imageRow->src);
            if ($url !== null && $url !== $image && ! in_array($url, $additionalImages, true)) {
                $additionalImages[] = $url;
            }
            if (count($additionalImages) >= 10) {
                break;
            }
        }

        $productType = self::categoryPath($category);
        $description = self::plainDescription($product, $title, $brand, $productType);

        return [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'image_link' => $image,
            'additional_image_links' => $additionalImages,
            'availability' => $inStock ? 'in_stock' : 'out_of_stock',
            'availability_facebook' => $inStock ? 'in stock' : 'out of stock',
            'condition' => $condition,
            'price' => self::money($price),
            'sale_price' => $salePrice !== null ? self::money($salePrice) : null,
            'brand' => $brand !== '' ? $brand : 'Mtoys',
            'mpn' => $mpn !== '' ? $mpn : null,
            'identifier_exists' => $mpn !== '',
            'product_type' => $productType,
        ];
    }

    protected static function resolveImageUrl(Product $product): ?string
    {
        $candidates = [];

        $main = trim((string) $product->image_path);
        if ($main !== '') {
            $candidates[] = $main;
        }

        foreach ($product->images as $imageRow) {
            $src = trim((string) $imageRow->src);
            if ($src !== '') {
                $candidates[] = $src;
            }
        }

        foreach ($candidates as $candidate) {
            $url = self::toFeedImageUrl($candidate);
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

        // Без fallback-заглушки: для Merchant/Meta она недопустима.
        if (str_starts_with($path, '//')) {
            return 'https:'.$path;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::ensureHttps($path);
        }

        $relative = ltrim($path, '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        $cdn = FileUploadHelper::cdnBaseUrl();
        if ($cdn !== '') {
            return $cdn.'/'.$relative;
        }

        return self::absoluteUrl('/storage/'.$relative);
    }

    protected static function ensureHttps(string $url): string
    {
        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, strlen('http://'));
        }

        return $url;
    }

    protected static function absoluteUrl(string $pathOrUrl): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return self::ensureHttps($pathOrUrl);
        }

        if (str_starts_with($pathOrUrl, '//')) {
            return 'https:'.$pathOrUrl;
        }

        return self::siteUrl().'/'.ltrim($pathOrUrl, '/');
    }

    protected static function money(float $amount): string
    {
        return number_format($amount, 2, '.', '').' UAH';
    }

    protected static function cleanText(string $value, int $limit): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return $value === '' ? '' : Str::limit($value, $limit, '');
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

    protected static function plainDescription(Product $product, string $title, string $brand, ?string $productType): string
    {
        $raw = trim((string) ($product->description ?: $product->seo_description ?: ''));
        $text = self::cleanText($raw, 5000);

        if (mb_strlen($text) < 30) {
            $parts = array_filter([$title, $brand !== '' ? 'Бренд: '.$brand : null, $productType]);
            $text = trim(implode('. ', $parts));
        }

        if ($text === '') {
            $text = $title;
        }

        // Google рекомендует осмысленное описание; минимум не жёсткий, но пустое нельзя.
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
            $name = trim((string) $current->name);
            if ($name !== '') {
                array_unshift($parts, $name);
            }
            $current = $current->parentCategory ?? null;
            $guard++;
        }

        return $parts === [] ? null : implode(' > ', $parts);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected static function buildGoogleXml(array $items, string $storeName, string $siteUrl): string
    {
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

        $xml->startElement('channel');
        $xml->writeElement('title', $storeName);
        $xml->writeElement('link', $siteUrl);
        $xml->writeElement('description', $storeName.' — товарный фид Google Merchant');

        foreach ($items as $item) {
            $xml->startElement('item');
            self::writeG($xml, 'id', $item['id']);
            self::writeG($xml, 'title', $item['title']);
            self::writeG($xml, 'description', $item['description']);
            self::writeG($xml, 'link', $item['link']);
            self::writeG($xml, 'image_link', $item['image_link']);
            foreach ($item['additional_image_links'] as $extra) {
                self::writeG($xml, 'additional_image_link', $extra);
            }
            self::writeG($xml, 'availability', $item['availability']);
            self::writeG($xml, 'condition', $item['condition']);
            self::writeG($xml, 'price', $item['price']);
            if (! empty($item['sale_price'])) {
                self::writeG($xml, 'sale_price', $item['sale_price']);
            }
            self::writeG($xml, 'brand', $item['brand']);
            if (! empty($item['mpn'])) {
                self::writeG($xml, 'mpn', $item['mpn']);
            }
            self::writeG($xml, 'identifier_exists', $item['identifier_exists'] ? 'yes' : 'no');
            if (! empty($item['product_type'])) {
                self::writeG($xml, 'product_type', $item['product_type']);
            }
            // Украина — основной рынок.
            self::writeG($xml, 'shipping', null, function (\XMLWriter $xml): void {
                self::writeG($xml, 'country', 'UA');
                self::writeG($xml, 'service', 'Standard');
                self::writeG($xml, 'price', '0.00 UAH');
            });
            $xml->endElement(); // item
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss

        return $xml->outputMemory();
    }

    /**
     * @param  (callable(\XMLWriter): void)|null  $children
     */
    protected static function writeG(\XMLWriter $xml, string $name, ?string $value, ?callable $children = null): void
    {
        $xml->startElement('g:'.$name);
        if ($children !== null) {
            $children($xml);
        } elseif ($value !== null) {
            $xml->text($value);
        }
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

        // UTF-8 BOM — Meta/Excel корректно читают кириллицу.
        fwrite($handle, "\xEF\xBB\xBF");

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
            'status',
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
                'active',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}
