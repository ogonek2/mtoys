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
 * Сбор Google/Facebook фидов с нуля:
 * — только Query Builder (без Eloquent-связей);
 * — короткие поля;
 * — пишем файлы потоком, пока не кончатся строки в БД.
 */
final class FeedBuilder
{
    public const DIR = 'feeds';

    public const GOOGLE = 'google-merchant.xml';

    public const FACEBOOK = 'facebook-catalog.csv';

    public const META_KEY = 'product-feeds.meta';

    public const DIRTY_KEY = 'product-feeds.dirty';

    private const BATCH = 100;

    private const TITLE_MAX = 100;

    private const DESC_MAX = 300;

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '256M');
        ignore_user_abort(true);

        $t0 = microtime(true);
        Log::info('feeds: build start');

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
                    $afterId = (int) $row->id;
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

                // Явно двигаем курсор вперёд по последнему id батча.
                $afterId = (int) $rows[array_key_last($rows)]->id;

                unset($rows);
                if ($scanned % 300 === 0) {
                    Log::info('feeds: progress', compact('scanned', 'written', 'afterId'));
                }
            }
        } catch (Throwable $e) {
            fclose($g);
            fclose($f);
            @unlink($googleTmp);
            @unlink($facebookTmp);
            Log::error('feeds: build failed', [
                'message' => $e->getMessage(),
                'scanned' => $scanned,
                'afterId' => $afterId,
            ]);
            throw $e;
        }

        fwrite($g, "  </channel>\n</rss>\n");
        fclose($g);
        fclose($f);

        $this->publish(self::DIR.'/'.self::GOOGLE, $googleTmp);
        $this->publish(self::DIR.'/'.self::FACEBOOK, $facebookTmp);

        Cache::forget(self::DIRTY_KEY);

        $meta = [
            'google' => route('feeds.google'),
            'facebook' => route('feeds.facebook'),
            'count' => $written,
            'catalog_total' => $scanned,
            'excluded' => $skipped,
            'generated_at' => now()->toIso8601String(),
            'google_bytes' => (int) filesize($this->abs(self::DIR.'/'.self::GOOGLE)),
            'facebook_bytes' => (int) filesize($this->abs(self::DIR.'/'.self::FACEBOOK)),
        ];

        Cache::forever(self::META_KEY, $meta);

        Log::info('feeds: build done', [
            'written' => $written,
            'scanned' => $scanned,
            'skipped' => $skipped,
            'sec' => round(microtime(true) - $t0, 2),
        ]);

        return $meta;
    }

    /**
     * @return list<object>
     */
    private function fetchBatch(int $afterId): array
    {
        return Database::retry(function () use ($afterId) {
            // reconnect только при ошибке внутри Database::retry
            return DB::table('products')
                ->select([
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

    /**
     * @param  resource  $g
     */
    private function writeGoogleHeader($g, string $store, string $site): void
    {
        fwrite($g, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        fwrite($g, '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'."\n");
        fwrite($g, "  <channel>\n");
        fwrite($g, '    <title>'.$this->xml($store)."</title>\n");
        fwrite($g, '    <link>'.$this->xml($site)."</link>\n");
        fwrite($g, '    <description>'.$this->xml($store.' product feed')."</description>\n");
    }

    /**
     * @param  resource  $f
     */
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
        fwrite($g, "    <item>\n");
        $this->g($g, 'id', $item['id']);
        $this->g($g, 'title', $item['title']);
        $this->g($g, 'description', $item['description']);
        $this->g($g, 'link', $item['link']);
        $this->g($g, 'image_link', $item['image_link']);
        $this->g($g, 'availability', $item['availability']);
        $this->g($g, 'condition', $item['condition']);
        $this->g($g, 'price', $item['price']);
        if ($item['sale_price'] !== '') {
            $this->g($g, 'sale_price', $item['sale_price']);
        }
        $this->g($g, 'brand', $item['brand']);
        if ($item['mpn'] !== '') {
            $this->g($g, 'mpn', $item['mpn']);
        }
        $this->g($g, 'identifier_exists', $item['mpn'] !== '' ? 'yes' : 'no');
        fwrite($g, "      <g:shipping>\n");
        $this->g($g, 'country', 'UA', 8);
        $this->g($g, 'service', 'Standard', 8);
        $this->g($g, 'price', '0.00 UAH', 8);
        fwrite($g, "      </g:shipping>\n");
        fwrite($g, "    </item>\n");
    }

    /**
     * @param  resource  $f
     * @param  array<string, mixed>  $item
     */
    private function writeFacebookItem($f, array $item): void
    {
        fputcsv($f, [
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
        ]);
    }

    /**
     * @param  resource  $g
     */
    private function g($g, string $name, string $value, int $pad = 6): void
    {
        fwrite($g, str_repeat(' ', $pad).'<g:'.$name.'>'.$this->xml($value).'</g:'.$name.">\n");
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

        if ($googleExists) {
            $xml = (string) Storage::disk('public')->get($googlePath);
            $n = substr_count($xml, '<item>');
            if ($n > 0) {
                $meta['count'] = $n;
                $meta['google_bytes'] = strlen($xml);
            }
        }

        return [
            'google' => route('feeds.google'),
            'facebook' => route('feeds.facebook'),
            'count' => (int) ($meta['count'] ?? 0),
            'catalog_total' => (int) ($meta['catalog_total'] ?? 0),
            'excluded' => (int) ($meta['excluded'] ?? 0),
            'generated_at' => $meta['generated_at'] ?? null,
            'google_bytes' => (int) ($meta['google_bytes'] ?? 0),
            'facebook_bytes' => (int) ($meta['facebook_bytes'] ?? 0),
            'dirty' => (bool) Cache::get(self::DIRTY_KEY),
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
        $file = $type === 'facebook' ? self::FACEBOOK : self::GOOGLE;
        $path = self::DIR.'/'.$file;

        if (! Storage::disk('public')->exists($path)) {
            try {
                $this->build();
            } catch (Throwable) {
                return null;
            }
        }

        return Storage::disk('public')->get($path);
    }

    public function markDirty(): void
    {
        Cache::put(self::DIRTY_KEY, true, now()->addHours(6));
    }
}
