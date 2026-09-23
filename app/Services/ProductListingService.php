<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductListingService
{
    /** Legacy + canonical in-stock values (inline SQL — ProxySQL-safe, no bindings). */
    public const IN_STOCK_SQL = "availability IN ('in_stock','1')";

    /** Legacy + canonical out-of-stock values. */
    public const OUT_OF_STOCK_SQL = "availability IN ('out_of_stock','2','0')";

    /**
     * Base query used everywhere (consistent columns + default "in_stock").
     */
    public function baseQuery(): Builder
    {
        return Product::query()
            ->select([
                'id',
                'name',
                'price',
                'discount',
                'image_path',
                'url',
                'articule',
                'availability',
                'is_wholesale',
                'wholesale_price',
                'wholesale_min_quantity',
                'units_per_box',
                'unit_name',
                'unit_name_plural',
            ])
            // Legacy data can contain 1/2 instead of in_stock/out_of_stock.
            ->whereRaw(self::IN_STOCK_SQL);
    }

    /**
     * Catalog listing query with optional availability scope.
     */
    public function catalogQuery(Request $request): Builder
    {
        $query = Product::query()->select([
            'id',
            'name',
            'price',
            'discount',
            'image_path',
            'url',
            'articule',
            'availability',
            'is_wholesale',
            'wholesale_price',
            'wholesale_min_quantity',
            'units_per_box',
            'unit_name',
            'unit_name_plural',
            'created_at',
        ]);

        $this->applyAvailability($query, $request);

        return $query;
    }

    /**
     * Availability: default shows all (in-stock first via sort); "1" = in stock only.
     */
    public function applyAvailability(Builder $query, Request $request): Builder
    {
        $availability = (string) $request->input('availability', '');

        // Default / "all": no availability filter — out-of-stock listed last by sort.
        if ($availability === '' || $availability === 'all') {
            return $query;
        }

        if ($availability === '1' || $availability === 'in' || $availability === 'in_stock') {
            return $query->whereRaw(self::IN_STOCK_SQL);
        }

        // Legacy URL support (?availability=out).
        if ($availability === 'out') {
            return $query->whereRaw(self::OUT_OF_STOCK_SQL);
        }

        return $query;
    }

    /**
     * Apply common filters from request (price, discount, wholesale, new).
     */
    public function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('price_min')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) >= ?', [(float) $request->input('price_min')]);
        }

        if ($request->filled('price_max')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [(float) $request->input('price_max')]);
        }

        if ($request->boolean('discount')) {
            $query->whereRaw('CAST(discount AS DECIMAL(10,2)) > 0');
        }

        if ($request->boolean('wholesale')) {
            $query->where(function (Builder $q) {
                $q->where('is_wholesale', 1)
                    ->orWhere('is_wholesale', true)
                    ->orWhere('is_wholesale', '1');
            });
        }

        if ($request->boolean('new')) {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        return $query;
    }

    public function applyCategorySlug(Builder $query, string $slug): Builder
    {
        $category = Category::query()->where('url', $slug)->first();
        if (!$category) {
            return $query;
        }

        $ids = $this->categoryIdsWithDescendants($category);

        return $query->whereHas('categories', function (Builder $q) use ($ids) {
            $q->whereIn('categories.id', $ids);
        });
    }

    /**
     * Apply sorting. In-stock first, then out-of-stock. Default secondary sort is newest.
     */
    public function applySort(Builder $query, string $sort): Builder
    {
        $query->orderByRaw(
            'CASE WHEN '.self::IN_STOCK_SQL.' THEN 0 ELSE 1 END ASC'
        );

        return match ($sort) {
            'price_asc' => $query->orderByRaw('CAST(price AS DECIMAL(10,2)) ASC'),
            'price_desc' => $query->orderByRaw('CAST(price AS DECIMAL(10,2)) DESC'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'random' => $query->inRandomOrder(),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    /**
     * Products for a category, including all descendants (any depth).
     */
    public function queryForCategory(Category $category): Builder
    {
        $ids = $this->categoryIdsWithDescendants($category);

        return Product::query()
            ->select([
                'id',
                'name',
                'price',
                'discount',
                'image_path',
                'url',
                'articule',
                'availability',
                'is_wholesale',
                'wholesale_price',
                'wholesale_min_quantity',
                'units_per_box',
                'unit_name',
                'unit_name_plural',
                'created_at',
            ])
            ->whereHas('categories', function (Builder $q) use ($ids) {
                $q->whereIn('categories.id', $ids);
            });
    }

    /**
     * Fast descendant-id collection for adjacency-list categories.
     */
    public function categoryIdsWithDescendants(Category $category): array
    {
        $ids = [$category->id];
        $frontier = [$category->id];

        while (!empty($frontier)) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $children = array_values(array_diff($children, $ids));
            if (empty($children)) {
                break;
            }

            $ids = array_merge($ids, $children);
            $frontier = $children;
        }

        return $ids;
    }

    /**
     * Autocomplete search (AJAX).
     */
    public function autocomplete(string $q, int $limit = 10)
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return collect();
        }

        return $this->baseQuery()
            ->where(function (Builder $b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('articule', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            })
            ->select(['id', 'name', 'price', 'image_path', 'discount', 'url', 'articule', 'availability'])
            ->orderByRaw(
                "CASE
                    WHEN name LIKE ? THEN 1
                    WHEN name LIKE ? THEN 2
                    WHEN articule LIKE ? THEN 3
                    ELSE 4
                END",
                [$q . '%', '%' . $q . '%', '%' . $q . '%'],
            )
            ->limit($limit)
            ->get();
    }

    /**
     * Full search results (page).
     */
    public function queryForSearch(string $q): Builder
    {
        $q = trim($q);

        return $this->baseQuery()
            ->where(function (Builder $b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('articule', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
    }

    /**
     * Recommended products (API).
     *
     * Не используем ORDER BY RAND() — на удалённой MySQL это легко
     * укладывает запрос в десятки секунд и провоцирует таймауты/HY000 2014.
     */
    public function recommended(int $limit = 12)
    {
        $products = $this->baseQuery()
            ->whereHas('categories')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $this->attachPrimaryCategory($products);

        return $products;
    }

    public function attachPrimaryCategory($products): void
    {
        $collection = $products instanceof \Illuminate\Database\Eloquent\Collection
            ? $products
            : new \Illuminate\Database\Eloquent\Collection(
                $products instanceof \Illuminate\Support\Collection
                    ? $products->all()
                    : (is_array($products) ? $products : [$products])
            );

        if ($collection->isEmpty()) {
            return;
        }

        $collection->loadMissing([
            'categories' => fn ($query) => $query
                ->select(['categories.id', 'categories.name', 'categories.url'])
                ->orderBy('categories.id'),
        ]);

        $collection->each(function ($product) {
            $category = $product->categories->first();
            $product->category_name = $category?->name ?? 'Без категории';
            $product->category_url = $category?->url ?? 'catalog';
        });
    }
}

