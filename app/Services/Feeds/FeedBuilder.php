<?php

namespace App\Services\Feeds;

use App\Helpers\FileUploadHelper;
use App\Services\ShopSettings;
use App\Support\Database;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Товарные фиды без «общего сбора» из админки:
 * — первичная сборка (если файлов ещё нет) или CLI;
 * — дальше только точечный upsert/remove по id товара.
 */
final class FeedBuilder
{
    public const DIR = 'feeds';

    public const GOOGLE = 'google-merchant.xml';

    public const FACEBOOK = 'facebook-catalog.csv';

    public const META_KEY = 'product-feeds.meta';

    private const BATCH = 100;

    private const TITLE_MAX = 100;

    private const DESC_MAX = 300;

    /**
     * Полная первичная сборка (или аварийный CLI). Не для UI.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '256M');

        $t0 = microtime(true);
        Log::info('feeds: full build start');

        Storage::disk('public')->makeDirectory(self::DIR);

        $googleTmp = $this->abs(self::DIR.'/'.self::GOOGLE.'.tmp');
        $facebookTmp = $this->abs(self::DIR.'/'.self::FACEBOOK.'.tmp');
        @unlink($googleTmp);
        @unlink($facebookTmp);

        $g = fopen($googleTmp, 'wb');
        $f = fopen($facebookTmp, 'wb');
        if ($g === false || $f === false) {
            throw new RuntimeException('Cannot open feed temp files');
        }

        $store = (string) (ShopSettings::get('store_name') ?: 'Mtoys');
        $site = rtrim((string) config('app.url'), '/');

        $this->writeGoogleHeader($g, $store, $site);
        $this->writeFacebookHeader($f);

        $scanned = 0;
        $written = 0;
        $skipped = 0;
        $afterId = 0;

        try {
            while (true) {
                $rows = $this->fetchBatch($afterId);
                if ($rows === []) {
                    break;
                }

                $categoryByProduct = $this->categoriesForIds(array_map(
                    static fn (object $row): int => (int) $row->id,
                    $rows
                ));

                foreach ($rows as $row) {
                    $scanned++;
                    $item = $this->mapRow($row, $store, $site, $categoryByProduct[(int) $row->id] ?? null);
                    if ($item === null) {
                        $skipped++;

                        continue;
                    }

                    $this->writeGoogleItem($g, $item);
                    $this->writeFacebookItem($f, $item);
                    $written++;
                }

                $afterId = (int) $rows[array_key_last($rows)]->id;
                unset($rows);
            }
        } catch (Throwable $e) {
            fclose($g);
            fclose($f);
            @unlink($googleTmp);
            @unlink($facebookTmp);
            Log::error('feeds: full build failed', ['message' => $e->getMessage()]);
            throw $e;
        }

        fwrite($g, "  </channel>\n</rss>\n");
        fclose($g);
        fclose($f);

        $this->withLock(function () use ($googleTmp, $facebookTmp): void {
            $this->publish(self::DIR.'/'.self::GOOGLE, $googleTmp);
            $this->publish(self::DIR.'/'.self::FACEBOOK, $facebookTmp);
        });

        $meta = $this->storeMeta($written, $scanned, $skipped);
        Log::info('feeds: full build done', [
            'written' => $written,
            'scanned' => $scanned,
            'sec' => round(microtime(true) - $t0, 2),
        ]);

        return $meta;
    }

    /**
     * Создать/обновить товар в фидах. Если товар невалиден — удалить из фидов.
     */
    public function upsert(int $productId): void
    {
        $this->ensureFiles();

        $item = $this->itemForId($productId);
        if ($item === null) {
            $this->remove($productId);

            return;
        }

        $googleBlock = $this->googleItemXml($item);
        $facebookRow = $this->facebookCsvRow($item);

        $this->withLock(function () use ($productId, $googleBlock, $facebookRow): void {
            $this->patchGoogleMany([$productId => ['_raw' => $googleBlock]]);
            $this->patchFacebookMany([$productId => ['_raw' => $facebookRow]]);
            $this->refreshMetaFromFiles();
        });
    }

    /**
     * @param  list<int>  $productIds
     */
    public function upsertMany(array $productIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($ids === []) {
            return;
        }

        $this->ensureFiles();

        /** @var array<int, array<string, mixed>|null> $items */
        $items = [];
        foreach (array_chunk($ids, self::BATCH) as $chunk) {
            foreach ($chunk as $id) {
                $items[$id] = $this->itemForId($id);
            }
        }

        $this->withLock(function () use ($items): void {
            $this->patchGoogleMany($items);
            $this->patchFacebookMany($items);
            $this->refreshMetaFromFiles();
        });
    }

    public function remove(int $productId): void
    {
        if (! $this->exists('google') && ! $this->exists('facebook')) {
            return;
        }

        $this->withLock(function () use ($productId): void {
            if ($this->exists('google')) {
                $this->patchGoogle($productId, null);
            }
            if ($this->exists('facebook')) {
                $this->patchFacebook($productId, null);
            }
            $this->refreshMetaFromFiles();
        });
    }

    /**
     * @param  list<int>  $productIds
     */
    public function removeMany(array $productIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($ids === [] || (! $this->exists('google') && ! $this->exists('facebook'))) {
            return;
        }

        $items = [];
        foreach ($ids as $id) {
            $items[$id] = null;
        }

        $this->withLock(function () use ($items): void {
            if ($this->exists('google')) {
                $this->patchGoogleMany($items);
            }
            if ($this->exists('facebook')) {
                $this->patchFacebookMany($items);
            }
            $this->refreshMetaFromFiles();
        });
    }

    public function ensureFiles(): void
    {
        if ($this->exists('google') && $this->exists('facebook')) {
            return;
        }

        $this->build();
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        $meta = Cache::get(self::META_KEY);
        if (! is_array($meta)) {
            $meta = [];
        }

        $googlePath = self::DIR.'/'.self::GOOGLE;
        $facebookPath = self::DIR.'/'.self::FACEBOOK;
        $googleExists = Storage::disk('public')->exists($googlePath);
        $facebookExists = Storage::disk('public')->exists($facebookPath);

        if ($googleExists && empty($meta['count'])) {
            $this->refreshMetaFromFiles();
            $meta = Cache::get(self::META_KEY) ?: [];
        }

        return [
            'google' => route('feeds.google'),
            'facebook' => route('feeds.facebook'),
            'count' => (int) ($meta['count'] ?? 0),
            'catalog_total' => (int) ($meta['catalog_total'] ?? ($meta['count'] ?? 0)),
            'excluded' => (int) ($meta['excluded'] ?? 0),
            'generated_at' => $meta['generated_at'] ?? null,
            'google_bytes' => (int) ($meta['google_bytes'] ?? 0),
            'facebook_bytes' => (int) ($meta['facebook_bytes'] ?? 0),
            'google_exists' => $googleExists,
            'facebook_exists' => $facebookExists,
        ];
    }

    public function exists(string $type): bool
    {
        $file = $type === 'facebook' ? self::FACEBOOK : self::GOOGLE;

        return Storage::disk('public')->exists(self::DIR.'/'.$file);
    }

    public function fileContents(string $type): ?string
    {
        $this->ensureFiles();
        $file = $type === 'facebook' ? self::FACEBOOK : self::GOOGLE;
        $path = self::DIR.'/'.$file;

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->get($path);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function itemForId(int $productId): ?array
    {
        $row = Database::retry(static function () use ($productId) {
            return DB::table('products')
                ->select([
                    'id', 'name', 'articule', 'price', 'discount',
                    'discount_starts_at', 'discount_ends_at', 'image_path',
                    'url', 'availability', 'condition_item', 'brand', 'seo_description',
                ])
                ->where('id', $productId)
                ->first();
        }, 3);

        if ($row === null) {
            return null;
        }

        $store = (string) (ShopSettings::get('store_name') ?: 'Mtoys');
        $site = rtrim((string) config('app.url'), '/');
        $cats = $this->categoriesForIds([$productId]);

        return $this->mapRow($row, $store, $site, $cats[$productId] ?? null);
    }

    /**
     * @return list<object>
     */
    private function fetchBatch(int $afterId): array
    {
        return Database::retry(function () use ($afterId) {
            return DB::table('products')
                ->select([
                    'id', 'name', 'articule', 'price', 'discount',
                    'discount_starts_at', 'discount_ends_at', 'image_path',
                    'url', 'availability', 'condition_item', 'brand', 'seo_description',
                ])
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit(self::BATCH)
                ->get()
                ->all();
        }, 5);
    }

    /**
     * @param  list<int>  $productIds
     * @return array<int, array{url: string, name: string}>
     */
    private function categoriesForIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = Database::retry(static function () use ($productIds) {
            return DB::table('category_product as cp')
                ->join('categories as c', 'c.id', '=', 'cp.category_id')
                ->whereIn('cp.product_id', $productIds)
                ->orderBy('cp.category_id')
                ->get(['cp.product_id', 'c.url', 'c.name']);
        }, 3);

        $map = [];
        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            if (isset($map[$pid])) {
                continue;
            }
            $map[$pid] = [
                'url' => trim((string) $row->url),
                'name' => trim((string) $row->name),
            ];
        }

        return $map;
    }

    /**
     * @param  array{url: string, name: string}|null  $category
     * @return array<string, mixed>|null
     */
    private function mapRow(object $row, string $store, string $site, ?array $category): ?array
    {
        $title = $this->text((string) ($row->name ?? ''), self::TITLE_MAX);
        if ($title === '') {
            return null;
        }

        $price = round((float) ($row->price ?? 0), 2);
        if ($price <= 0) {
            return null;
        }

        $image = $this->imageUrl((string) ($row->image_path ?? ''));
        if ($image === null) {
            return null;
        }

        $slug = trim((string) ($row->url ?? ''));
        if ($slug === '') {
            $slug = $this->slug($title);
        }
        if ($slug === '') {
            return null;
        }

        $categoryUrl = ($category['url'] ?? '') !== '' ? $category['url'] : 'catalog';
        $link = $site.'/catalog/categoriya/'.$categoryUrl.'/'.$slug;

        $discount = max(0, min(100, (int) ($row->discount ?? 0)));
        $sale = null;
        if ($discount > 0 && $this->discountActive($row)) {
            $tmp = round($price * (1 - $discount / 100), 2);
            if ($tmp > 0 && $tmp < $price) {
                $sale = $tmp;
            }
        }

        $inStock = in_array((string) ($row->availability ?? ''), ['in_stock', '1'], true);
        $condition = match ((string) ($row->condition_item ?? 'new')) {
            'used' => 'used',
            'refurbished' => 'refurbished',
            default => 'new',
        };

        $brand = $this->text((string) ($row->brand ?: $store), 50) ?: 'Mtoys';
        $mpn = $this->text((string) ($row->articule ?? ''), 40);
        $desc = $this->text((string) ($row->seo_description ?? ''), self::DESC_MAX);
        if ($desc === '') {
            $catName = $category['name'] ?? '';
            $desc = $this->text(trim($title.'. '.$brand.($catName !== '' ? '. '.$catName : '')), self::DESC_MAX);
        }

        return [
            'id' => (string) $row->id,
            'title' => $title,
            'description' => $desc,
            'link' => $link,
            'image_link' => $image,
            'availability' => $inStock ? 'in_stock' : 'out_of_stock',
            'availability_fb' => $inStock ? 'in stock' : 'out of stock',
            'condition' => $condition,
            'price' => number_format($price, 2, '.', '').' UAH',
            'sale_price' => $sale !== null ? number_format($sale, 2, '.', '').' UAH' : '',
            'brand' => $brand,
            'mpn' => $mpn,
        ];
    }

    private function discountActive(object $row): bool
    {
        $today = now()->startOfDay();
        $from = $row->discount_starts_at ?? null;
        $to = $row->discount_ends_at ?? null;

        if ($from && strtotime((string) $from) > $today->getTimestamp()) {
            return false;
        }
        if ($to && strtotime((string) $to) < $today->getTimestamp()) {
            return false;
        }

        return true;
    }

    private function imageUrl(string $path): ?string
    {
        $path = trim($path);
        if ($path === '' || str_contains(mb_strtolower($path), 'no-image')) {
            return null;
        }
        if (str_starts_with($path, '//')) {
            return 'https:'.$path;
        }
        if (str_starts_with($path, 'http://')) {
            return 'https://'.substr($path, 7);
        }
        if (str_starts_with($path, 'https://')) {
            return $path;
        }

        $relative = ltrim($path, '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, 8);
        }

        $cdn = FileUploadHelper::cdnBaseUrl();
        if ($cdn !== '') {
            return $cdn.'/'.$relative;
        }

        return rtrim((string) config('app.url'), '/').'/storage/'.$relative;
    }

    private function text(string $value, int $max): string
    {
        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/\S{120,}/u', $value)) {
            $value = preg_replace('/\S{120,}/u', ' ', $value) ?? $value;
            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        }

        return Str::limit($value, $max, '');
    }

    private function slug(string $title): string
    {
        $text = mb_strtolower($title);
        $text = str_replace(
            ['а', 'б', 'в', 'г', 'ґ', 'д', 'е', 'є', 'ж', 'з', 'и', 'і', 'ї', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ь', 'ю', 'я'],
            ['a', 'b', 'v', 'g', 'g', 'd', 'e', 'ye', 'zh', 'z', 'y', 'i', 'yi', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'ts', 'ch', 'sh', 'shch', '', 'yu', 'ya'],
            $text
        );
        $text = preg_replace('/[^\w\-]+/', '-', $text) ?? $text;

        return trim($text, '-');
    }

    /** @param  resource  $g */
    private function writeGoogleHeader($g, string $store, string $site): void
    {
        fwrite($g, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        fwrite($g, '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'."\n");
        fwrite($g, "  <channel>\n");
        fwrite($g, '    <title>'.$this->xml($store)."</title>\n");
        fwrite($g, '    <link>'.$this->xml($site)."</link>\n");
        fwrite($g, '    <description>'.$this->xml($store.' product feed')."</description>\n");
    }

    /** @param  resource  $f */
    private function writeFacebookHeader($f): void
    {
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, [
            'id', 'title', 'description', 'availability', 'condition', 'price', 'link',
            'image_link', 'brand', 'sale_price', 'mpn', 'status',
        ]);
    }

    /**
     * @param  resource  $g
     * @param  array<string, mixed>  $item
     */
    private function writeGoogleItem($g, array $item): void
    {
        fwrite($g, $this->googleItemXml($item));
    }

    /**
     * @param  resource  $f
     * @param  array<string, mixed>  $item
     */
    private function writeFacebookItem($f, array $item): void
    {
        fputcsv($f, $this->facebookCsvFields($item));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function googleItemXml(array $item): string
    {
        $pad = str_repeat(' ', 6);
        $pad8 = str_repeat(' ', 8);
        $out = "    <item>\n";
        $out .= $pad.'<g:id>'.$this->xml($item['id'])."</g:id>\n";
        $out .= $pad.'<g:title>'.$this->xml($item['title'])."</g:title>\n";
        $out .= $pad.'<g:description>'.$this->xml($item['description'])."</g:description>\n";
        $out .= $pad.'<g:link>'.$this->xml($item['link'])."</g:link>\n";
        $out .= $pad.'<g:image_link>'.$this->xml($item['image_link'])."</g:image_link>\n";
        $out .= $pad.'<g:availability>'.$this->xml($item['availability'])."</g:availability>\n";
        $out .= $pad.'<g:condition>'.$this->xml($item['condition'])."</g:condition>\n";
        $out .= $pad.'<g:price>'.$this->xml($item['price'])."</g:price>\n";
        if ($item['sale_price'] !== '') {
            $out .= $pad.'<g:sale_price>'.$this->xml($item['sale_price'])."</g:sale_price>\n";
        }
        $out .= $pad.'<g:brand>'.$this->xml($item['brand'])."</g:brand>\n";
        if ($item['mpn'] !== '') {
            $out .= $pad.'<g:mpn>'.$this->xml($item['mpn'])."</g:mpn>\n";
        }
        $out .= $pad.'<g:identifier_exists>'.$this->xml($item['mpn'] !== '' ? 'yes' : 'no')."</g:identifier_exists>\n";
        $out .= "      <g:shipping>\n";
        $out .= $pad8.'<g:country>UA</g:country>'."\n";
        $out .= $pad8.'<g:service>Standard</g:service>'."\n";
        $out .= $pad8.'<g:price>0.00 UAH</g:price>'."\n";
        $out .= "      </g:shipping>\n";
        $out .= "    </item>\n";

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<string>
     */
    private function facebookCsvFields(array $item): array
    {
        return [
            $item['id'],
            $item['title'],
            $item['description'],
            $item['availability_fb'],
            $item['condition'],
            $item['price'],
            $item['link'],
            $item['image_link'],
            $item['brand'],
            $item['sale_price'],
            $item['mpn'],
            'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function facebookCsvRow(array $item): string
    {
        $fh = fopen('php://temp', 'r+b');
        if ($fh === false) {
            throw new RuntimeException('Cannot open temp stream');
        }
        fputcsv($fh, $this->facebookCsvFields($item));
        rewind($fh);
        $line = stream_get_contents($fh);
        fclose($fh);

        return rtrim((string) $line, "\r\n");
    }

    /** @param  ?string  $block  null = удалить */
    private function patchGoogle(int $productId, ?string $block): void
    {
        $this->patchGoogleMany([$productId => $block === null ? null : ['_raw' => $block]]);
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $items  null = удалить; массив item или ['_raw'=>xml]
     */
    private function patchGoogleMany(array $items): void
    {
        if ($items === []) {
            return;
        }

        $path = $this->abs(self::DIR.'/'.self::GOOGLE);
        if (! is_file($path)) {
            return;
        }

        $xml = (string) file_get_contents($path);
        $append = '';

        foreach ($items as $productId => $item) {
            $block = null;
            if (is_array($item)) {
                $block = isset($item['_raw']) ? (string) $item['_raw'] : $this->googleItemXml($item);
            }

            $pattern = '/\s*<item>\s*<g:id>'.preg_quote((string) $productId, '/').'<\/g:id>.*?<\/item>/s';

            if (preg_match($pattern, $xml) === 1) {
                $xml = $block === null
                    ? (string) preg_replace($pattern, '', $xml, 1)
                    : (string) preg_replace($pattern, "\n".$block, $xml, 1);
            } elseif ($block !== null) {
                $append .= $block;
            }
        }

        if ($append !== '') {
            if (! str_contains($xml, '</channel>')) {
                throw new RuntimeException('Invalid google feed: missing </channel>');
            }
            $xml = str_replace('  </channel>', $append.'  </channel>', $xml);
        }

        $tmp = $path.'.patch';
        if (file_put_contents($tmp, $xml) === false || ! @rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException('Cannot write google feed patch');
        }
    }

    /** @param  ?string  $row  null = удалить */
    private function patchFacebook(int $productId, ?string $row): void
    {
        $this->patchFacebookMany([$productId => $row === null ? null : ['_raw' => $row]]);
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $items
     */
    private function patchFacebookMany(array $items): void
    {
        if ($items === []) {
            return;
        }

        $path = $this->abs(self::DIR.'/'.self::FACEBOOK);
        if (! is_file($path)) {
            return;
        }

        $rowsById = [];
        foreach ($items as $productId => $item) {
            if ($item === null) {
                $rowsById[(string) $productId] = null;
            } elseif (isset($item['_raw'])) {
                $rowsById[(string) $productId] = (string) $item['_raw'];
            } else {
                $rowsById[(string) $productId] = $this->facebookCsvRow($item);
            }
        }

        $raw = (string) file_get_contents($path);
        $bom = str_starts_with($raw, "\xEF\xBB\xBF") ? "\xEF\xBB\xBF" : '';
        $body = $bom !== '' ? substr($raw, 3) : $raw;
        $lines = preg_split("/\r\n|\n|\r/", $body) ?: [];
        if ($lines === []) {
            return;
        }

        $header = array_shift($lines);
        $seen = [];
        $out = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line);
            $lineId = (string) ($cols[0] ?? '');
            if (array_key_exists($lineId, $rowsById)) {
                $seen[$lineId] = true;
                if ($rowsById[$lineId] !== null) {
                    $out[] = $rowsById[$lineId];
                }

                continue;
            }
            $out[] = $line;
        }

        foreach ($rowsById as $id => $row) {
            if ($row !== null && ! isset($seen[$id])) {
                $out[] = $row;
            }
        }

        $content = $bom.$header."\n";
        if ($out !== []) {
            $content .= implode("\n", $out)."\n";
        }

        $tmp = $path.'.patch';
        if (file_put_contents($tmp, $content) === false || ! @rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException('Cannot write facebook feed patch');
        }
    }

    private function withLock(callable $callback): void
    {
        Storage::disk('public')->makeDirectory(self::DIR);
        $lockPath = $this->abs(self::DIR.'/.lock');
        $fh = fopen($lockPath, 'c+');
        if ($fh === false) {
            throw new RuntimeException('Cannot open feed lock');
        }

        try {
            if (! flock($fh, LOCK_EX)) {
                throw new RuntimeException('Cannot lock feeds');
            }
            $callback();
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    private function refreshMetaFromFiles(): void
    {
        $googlePath = $this->abs(self::DIR.'/'.self::GOOGLE);
        $facebookPath = $this->abs(self::DIR.'/'.self::FACEBOOK);
        $count = 0;
        $gBytes = 0;
        $fBytes = 0;

        if (is_file($googlePath)) {
            $xml = (string) file_get_contents($googlePath);
            $count = substr_count($xml, '<item>');
            $gBytes = strlen($xml);
        }
        if (is_file($facebookPath)) {
            $fBytes = (int) filesize($facebookPath);
        }

        $this->storeMeta($count, $count, 0, $gBytes, $fBytes);
    }

    /**
     * @return array<string, mixed>
     */
    private function storeMeta(int $written, int $scanned, int $skipped, ?int $gBytes = null, ?int $fBytes = null): array
    {
        $meta = [
            'google' => route('feeds.google'),
            'facebook' => route('feeds.facebook'),
            'count' => $written,
            'catalog_total' => $scanned,
            'excluded' => $skipped,
            'generated_at' => now()->toIso8601String(),
            'google_bytes' => $gBytes ?? (int) (@filesize($this->abs(self::DIR.'/'.self::GOOGLE)) ?: 0),
            'facebook_bytes' => $fBytes ?? (int) (@filesize($this->abs(self::DIR.'/'.self::FACEBOOK)) ?: 0),
        ];
        Cache::forever(self::META_KEY, $meta);

        return $meta;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function abs(string $relative): string
    {
        return Storage::disk('public')->path($relative);
    }

    private function publish(string $relative, string $tmpAbs): void
    {
        $final = $this->abs($relative);
        if (is_file($final)) {
            @unlink($final);
        }
        if (! @rename($tmpAbs, $final) && ! @copy($tmpAbs, $final)) {
            throw new RuntimeException('Cannot publish '.$relative);
        }
        @unlink($tmpAbs);
    }
}
