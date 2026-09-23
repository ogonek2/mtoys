<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Дерево категорий + образцы товаров для мега-меню.
 *
 * Товары подгружаются одним JOIN (не N запросов на категорию), чтобы не
 * ломать сессии на ProxySQL/mysql.tools (HY000 2014).
 */
class MegaMenuService
{
    public const CACHE_KEY = 'shop.mega_menu.v5';

    public const CACHE_TTL = 300;

    public const PRODUCTS_PER_CATEGORY = 12;

    /**
     * @return array<int, array{
     *     category: Category,
     *     count: int,
     *     children: array<int, array{category: Category, count: int, products: Collection, children: array}>,
     *     products: Collection<int, Product>
     * }>
     */
    public function items(): array
    {
        return Cache::store($this->cacheStore())->remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn (): array => $this->build(),
        );
    }

    public function forget(): void
    {
        Cache::store($this->cacheStore())->forget(self::CACHE_KEY);
    }

    private function cacheStore(): string
    {
        // Database cache + тяжёлые SELECT на том же соединении иногда дают HY000 2014.
        return config('cache.default') === 'database' ? 'file' : config('cache.default');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function build(): array
    {
        $roots = Category::query()
            ->select(Category::LISTING_COLUMNS)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->with(['childCategories' => function ($q) {
                $q->select(Category::LISTING_COLUMNS)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->withCount('products')
                    ->with(['childCategories' => function ($q2) {
                        $q2->select(Category::LISTING_COLUMNS)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->withCount('products');
                    }]);
            }])
            ->withCount('products')
            ->get();

        $items = [];
        $categoryIds = [];
        $urlById = [];

        foreach ($roots as $root) {
            $categoryIds[] = $root->id;
            $urlById[$root->id] = (string) $root->url;

            $childBlocks = [];

            foreach ($root->childCategories as $child) {
                $categoryIds[] = $child->id;
                $urlById[$child->id] = (string) $child->url;

                $subBlocks = [];

                foreach ($child->childCategories as $grand) {
                    $categoryIds[] = $grand->id;
                    $urlById[$grand->id] = (string) $grand->url;

                    $subBlocks[] = [
                        'category' => $grand,
                        'count' => (int) $grand->products_count,
                        'products' => collect(),
                    ];
                }

                $childBlocks[] = [
                    'category' => $child,
                    'count' => (int) $child->products_count,
                    'products' => collect(),
                    'children' => $subBlocks,
                ];
            }

            $items[] = [
                'category' => $root,
                'count' => (int) $root->products_count,
                'children' => $childBlocks,
                'products' => collect(),
            ];
        }

        $productsByCategory = $this->loadProductsByCategory(array_values(array_unique($categoryIds)), $urlById);

        foreach ($items as &$item) {
            $rootId = $item['category']->id;
            $item['products'] = $productsByCategory[$rootId] ?? collect();

            foreach ($item['children'] as &$child) {
                $childId = $child['category']->id;
                $child['products'] = $productsByCategory[$childId] ?? collect();

                foreach ($child['children'] as &$sub) {
                    $subId = $sub['category']->id;
                    $sub['products'] = $productsByCategory[$subId] ?? collect();
                }
                unset($sub);
            }
            unset($child);
        }
        unset($item);

        return $items;
    }

    /**
     * @param  array<int, int>  $categoryIds
     * @param  array<int, string>  $urlById
     * @return array<int, Collection<int, Product>>
     */
    private function loadProductsByCategory(array $categoryIds, array $urlById): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $inStockSql = "p.availability IN ('in_stock','1')";

        $rows = DB::table('category_product as cp')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->whereIn('cp.category_id', $categoryIds)
            ->orderByRaw("CASE WHEN {$inStockSql} THEN 0 ELSE 1 END")
            ->orderBy('p.id')
            ->get([
                'cp.category_id',
                'p.id',
                'p.name',
                'p.url',
                'p.image_path',
                'p.price',
                'p.availability',
                'p.articule',
            ]);

        $grouped = [];
        $seen = [];

        foreach ($rows as $row) {
            $categoryId = (int) $row->category_id;
            $productId = (int) $row->id;

            if (! isset($grouped[$categoryId])) {
                $grouped[$categoryId] = [];
                $seen[$categoryId] = [];
            }

            if (isset($seen[$categoryId][$productId])) {
                continue;
            }

            if (count($grouped[$categoryId]) >= self::PRODUCTS_PER_CATEGORY) {
                continue;
            }

            $seen[$categoryId][$productId] = true;

            $product = new Product;
            $product->forceFill([
                'id' => $productId,
                'name' => (string) $row->name,
                'url' => (string) $row->url,
                'image_path' => $row->image_path,
                'price' => $row->price,
                'availability' => $row->availability,
                'articule' => $row->articule,
            ]);
            $product->exists = true;
            $product->category_url = $urlById[$categoryId] ?? 'catalog';

            $grouped[$categoryId][] = $product;
        }

        return array_map(
            static fn (array $list): Collection => collect($list),
            $grouped,
        );
    }
}
