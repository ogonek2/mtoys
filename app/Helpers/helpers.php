<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

function get_all_category() {
    // На хостингу mysql.tools (ProxySQL) запит
    // `SELECT * … ORDER BY sort_order, name` падає з HY093.
    // Явний select + той самий порядок колонок — стабільний.
    return Category::query()
        ->select(Category::LISTING_COLUMNS)
        ->where('is_active', true)
        ->orderBy('sort_order', 'asc')
        ->orderBy('name', 'asc')
        ->get();
}

if (!function_exists('get_category_total_products')) {
    /**
     * Подсчитывает количество товаров для категории с учетом всех дочерних категорий.
     *
     * Использует кеширование в рамках одного запроса, чтобы избежать лишних SQL-запросов.
     */
    function get_category_total_products(Category $category): int
    {
        static $categoryChildrenMap = null;
        static $categoryDirectCounts = null;
        static $calculatedTotals = [];

        if ($categoryChildrenMap === null || $categoryDirectCounts === null) {
            $categories = Category::query()
                ->select(['id', 'parent_id'])
                ->where('is_active', true)
                ->withCount('products')
                ->get();

            $categoryChildrenMap = $categories->groupBy('parent_id');
            $categoryDirectCounts = $categories->pluck('products_count', 'id')->toArray();
            $calculatedTotals = [];
        }

        return calculate_total_products_for_category($category->id, $categoryChildrenMap, $categoryDirectCounts, $calculatedTotals);
    }
}

if (!function_exists('calculate_total_products_for_category')) {
    /**
     * Рекурсивно подсчитывает количество товаров для категории и всех ее потомков.
     *
     * @param int $categoryId
     * @param \Illuminate\Support\Collection $childrenMap
     * @param array<int,int> $directCounts
     * @param array<int,int> $cache
     */
    function calculate_total_products_for_category(
        int $categoryId,
        $childrenMap,
        array $directCounts,
        array &$cache
    ): int {
        if (isset($cache[$categoryId])) {
            return $cache[$categoryId];
        }

        $total = $directCounts[$categoryId] ?? 0;

        if ($childrenMap->has($categoryId)) {
            foreach ($childrenMap->get($categoryId) as $childCategory) {
                $total += calculate_total_products_for_category(
                    $childCategory->id,
                    $childrenMap,
                    $directCounts,
                    $cache
                );
            }
        }

        return $cache[$categoryId] = $total;
    }
}

if (!function_exists('get_category_card_data')) {
    function get_category_card_data(Category $category, ?string $previewImage = null): array
    {
        $categoryImage = $previewImage;

        if ($categoryImage === null) {
            $categoryImage = $category->products()
                ->whereNotNull('products.image_path')
                ->where('products.image_path', '!=', '')
                ->orderByDesc('products.id')
                ->value('products.image_path');
        }

        if (!$categoryImage) {
            foreach ($category->childCategories()->where('is_active', true)->get() as $childCategory) {
                $childImage = $childCategory->products()
                    ->whereNotNull('products.image_path')
                    ->where('products.image_path', '!=', '')
                    ->orderByDesc('products.id')
                    ->value('products.image_path');
                if ($childImage) {
                    $categoryImage = $childImage;
                    break;
                }
            }
        }

        $count = get_category_total_products($category);

        return [
            'name' => $category->name,
            'url' => $category->url,
            'href' => route('catalog_category_page', $category->url),
            'image' => $categoryImage,
            'count' => $count,
            'description' => $category->description
                ? \Illuminate\Support\Str::limit(strip_tags($category->description), 70)
                : null,
        ];
    }
}

if (!function_exists('get_category_filter_tree')) {
    /**
     * Дерево категорій для фільтрів (корінь → діти → онуки).
     *
     * @param  \Illuminate\Support\Collection<int, Category>|null  $roots
     */
    function get_category_filter_tree($roots = null): array
    {
        $roots = $roots ?? Category::query()
            ->select(Category::LISTING_COLUMNS)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->with(['childCategories' => function ($q) {
                $q->select(Category::LISTING_COLUMNS)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->with(['childCategories' => function ($q2) {
                        $q2->select(Category::LISTING_COLUMNS)
                            ->where('is_active', true)
                            ->orderBy('name');
                    }]);
            }])
            ->get();

        $mapNode = function (Category $category) use (&$mapNode) {
            $children = $category->relationLoaded('childCategories')
                ? $category->childCategories
                : $category->childCategories()->where('is_active', true)->orderBy('name')->get();

            return [
                'name' => $category->name,
                'url' => $category->url,
                'href' => route('catalog_category_page', $category->url),
                'count' => get_category_total_products($category),
                'children' => $children
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->map(fn (Category $child) => $mapNode($child))
                    ->all(),
            ];
        };

        return $roots
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn (Category $c) => $mapNode($c))
            ->all();
    }
}

if (!function_exists('build_category_breadcrumbs')) {
    /**
     * Повний ланцюжок крихт: Каталог → батьки → поточна категорія.
     */
    function build_category_breadcrumbs(Category $category, bool $currentAsLink = false): array
    {
        $crumbs = [
            ['label' => 'Каталог', 'url' => route('catalog')],
        ];

        foreach ($category->getParents() as $parent) {
            $crumbs[] = [
                'label' => $parent->name,
                'url' => route('catalog_category_page', $parent->url),
            ];
        }

        $crumbs[] = $currentAsLink
            ? ['label' => $category->name, 'url' => route('catalog_category_page', $category->url)]
            : ['label' => $category->name];

        return $crumbs;
    }
}

if (!function_exists('forget_mega_menu_cache')) {
    function forget_mega_menu_cache(): void
    {
        app(\App\Services\MegaMenuService::class)->forget();
    }
}

if (!function_exists('get_mega_menu_data')) {
    /**
     * Дерево категорий и образцы товаров для мега-меню.
     *
     * @return array<int, array<string, mixed>>
     */
    function get_mega_menu_data(): array
    {
        return app(\App\Services\MegaMenuService::class)->items();
    }
}

function uploadToBunnyCDN($localFilePath, $destinationPath)
{
    // Legacy wrapper: FileUploadHelper сам генерирует имя; для явного пути используем прямой API.
    $helper = \App\Helpers\FileUploadHelper::class;
    if (! is_readable($localFilePath)) {
        throw new \Exception('File not found: '.$localFilePath);
    }

    $folder = trim(dirname($destinationPath), '/.');
    $uploaded = $helper::uploadToBunnyCDN($localFilePath, $folder !== '' ? $folder : 'products');
    if (! $uploaded) {
        throw new \Exception('BunnyCDN upload failed');
    }

    return $uploaded;
}

if (! function_exists('shop_image_url')) {
    function shop_image_url(?string $path, ?string $fallback = null): string
    {
        return \App\Helpers\FileUploadHelper::publicUrl($path, $fallback);
    }
}

/**
 * Генерирует уникальное название файла для изображения
 */
function generateUniqueImageName($originalName = null, $prefix = 'img') {
    $extension = 'jpg'; // по умолчанию
    
    if ($originalName) {
        $pathInfo = pathinfo($originalName);
        $extension = $pathInfo['extension'] ?? 'jpg';
    }
    
    // Генерируем уникальное название: префикс + uniqid + случайное число + расширение
    $fileName = $prefix . '_' . uniqid('', true) . '_' . random_int(10000, 99999) . '.' . $extension;
    
    return $fileName;
}

if (! function_exists('shop_settings')) {
    /**
     * @return array<string, mixed>
     */
    function shop_settings(): array
    {
        return \App\Services\ShopSettings::all();
    }
}

if (! function_exists('shop_settings_public')) {
    /**
     * @return array<string, mixed>
     */
    function shop_settings_public(): array
    {
        return \App\Services\ShopSettings::public();
    }
}

if (! function_exists('shop_min_order_total')) {
    function shop_min_order_total(): int
    {
        return \App\Services\ShopSettings::minOrderTotal();
    }
}