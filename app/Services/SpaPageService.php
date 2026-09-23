<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\productImage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SpaPageService
{
    public function __construct(private readonly ProductListingService $listing)
    {
    }

    public function homeMeta(): array
    {
        return [
            'title' => 'Mtoys — Інтернет-магазин іграшок',
            'description' => 'Яскраві іграшки оптом і в роздріб. Широкий асортимент, перевірена якість та швидка доставка по Україні.',
        ];
    }

    public function homePayload(): array
    {
        $popularProducts = \App\Support\Database::retry(
            fn () => $this->listing->recommended(10)
        );

        $newProducts = \App\Support\Database::retry(function () {
            $items = $this->homeRibbonQuery()
                ->where('created_at', '>=', now()->subDays(60))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
            $this->listing->attachPrimaryCategory($items);

            return $items;
        });

        $saleProducts = \App\Support\Database::retry(function () {
            $items = $this->homeRibbonQuery()
                ->whereRaw('CAST(discount AS DECIMAL(10,2)) > 0')
                ->orderByRaw('CAST(discount AS DECIMAL(10,2)) DESC')
                ->limit(10)
                ->get();
            $this->listing->attachPrimaryCategory($items);

            return $items;
        });

        $wholesaleProducts = \App\Support\Database::retry(function () {
            $items = $this->homeRibbonQuery()
                ->where('is_wholesale', 1)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
            $this->listing->attachPrimaryCategory($items);

            return $items;
        });

        $categories = \App\Support\Database::retry(function () {
            $categories = get_all_category()
                ->whereNull('parent_id')
                ->take(12)
                ->values();

            $categoryImages = $this->categoryPreviewImages($categories->pluck('id')->all());

            return $categories
                ->map(fn (Category $c) => get_category_card_data($c, $categoryImages[$c->id] ?? null))
                ->values()
                ->all();
        });

        return [
            'popularProducts' => $popularProducts instanceof Collection ? $popularProducts->values()->all() : [],
            'newProducts' => $newProducts->values()->all(),
            'saleProducts' => $saleProducts->values()->all(),
            'wholesaleProducts' => $wholesaleProducts->values()->all(),
            'categories' => $categories,
            'routes' => [
                'catalog' => route('catalog'),
            ],
        ];
    }

    private function homeRibbonQuery()
    {
        return Product::query()
            ->select([
                'id', 'name', 'price', 'discount', 'image_path', 'url', 'articule',
                'availability', 'is_wholesale', 'wholesale_price', 'wholesale_min_quantity',
                'units_per_box', 'unit_name', 'unit_name_plural', 'created_at',
            ])
            ->whereRaw(ProductListingService::IN_STOCK_SQL);
    }

    /**
     * Одно превью на категорию одним запросом вместо N+1 в get_category_card_data.
     *
     * @param  array<int, int>  $categoryIds
     * @return array<int, string>
     */
    private function categoryPreviewImages(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $rows = Product::query()
            ->select(['products.image_path', 'category_product.category_id'])
            ->join('category_product', 'category_product.product_id', '=', 'products.id')
            ->whereIn('category_product.category_id', $categoryIds)
            ->whereNotNull('products.image_path')
            ->where('products.image_path', '!=', '')
            ->orderByDesc('products.id')
            ->get();

        $images = [];
        foreach ($rows as $row) {
            $categoryId = (int) $row->category_id;
            if (! isset($images[$categoryId])) {
                $images[$categoryId] = $row->image_path;
            }
        }

        return $images;
    }

    public function catalogMeta(): array
    {
        return [
            'title' => 'Каталог іграшок — Mtoys',
            'description' => 'Широкий вибір товарів для дому, кухні та ванної. Якісна продукція, вигідні ціни та швидка доставка по Україні.',
        ];
    }

    public function catalogPayload(Request $request): array
    {
        $query = $this->listing->catalogQuery($request);
        // Category is navigated via URL, not query filter.
        $this->listing->applyFilters($query, $request);
        $this->listing->applySort($query, (string) $request->get('sort', 'default'));

        $paginator = $query->paginate(45);
        $this->listing->attachPrimaryCategory($paginator->getCollection());
        $paginator->appends($request->query());

        $categories = get_all_category()->whereNull('parent_id')->values();
        $categoryImages = $this->categoryPreviewImages($categories->pluck('id')->all());
        $popularProducts = $this->listing->recommended(8);
        $categoryTree = get_category_filter_tree();

        return $this->catalogConfigFromPaginator(
            $paginator,
            $categories
                ->map(fn ($c) => get_category_card_data($c, $categoryImages[$c->id] ?? null))
                ->values()
                ->all(),
            $popularProducts instanceof Collection ? $popularProducts->values()->all() : [],
            route('catalog'),
            '/api/spa/catalog',
            true,
            true,
            [['label' => 'Каталог']],
            [
                'eyebrow' => 'Mtoys',
                'title' => 'Каталог товарів',
                'subtitle' => 'Товари для дому, кухні та ванної — оптом і в роздріб',
                'stat' => $paginator->total(),
            ],
            'Розділи',
            'Категорії',
            $categoryTree,
            null,
        );
    }

    public function categoryMeta(Category $category): array
    {
        return [
            'title' => $category->name . ' — Mtoys',
            'description' => $category->description
                ?? 'Купити ' . $category->name . ' в інтернет-магазині Mtoys. Яскраві іграшки з доставкою по Україні.',
        ];
    }

    public function categoryPayload(Request $request, string $categorySlug): array
    {
        $category = Category::where('url', $categorySlug)->firstOrFail();

        $query = $this->listing->queryForCategory($category);
        $this->listing->applyAvailability($query, $request);
        $this->listing->applyFilters($query, $request);
        $this->listing->applySort($query, (string) ($request->get('sort') ?: 'default'));

        $paginator = $query->paginate(45);
        $this->listing->attachPrimaryCategory($paginator->getCollection());
        $paginator->appends($request->query());

        $childCategories = $category->childCategories()
            ->select(Category::LISTING_COLUMNS)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Full tree so the picker can jump to any category.
        $categoryTree = get_category_filter_tree();

        $parents = $category->getParents();
        $root = $parents->first() ?: $category;

        return $this->catalogConfigFromPaginator(
            $paginator,
            $childCategories->map(fn ($c) => get_category_card_data($c))->values()->all(),
            [],
            route('catalog_category_page', $category->url),
            '/api/spa/category/' . $category->url,
            false,
            false,
            build_category_breadcrumbs($category),
            [
                'eyebrow' => $root->id === $category->id ? 'Категорія' : $root->name,
                'title' => $category->name,
                'subtitle' => $category->description,
                'stat' => $paginator->total(),
            ],
            'Підрозділи',
            'Підкатегорії',
            $categoryTree,
            [
                'name' => $category->name,
                'url' => $category->url,
                'href' => route('catalog_category_page', $category->url),
                'parent_url' => $category->parentCategory?->url,
            ],
        );
    }

    public function productMeta(Product $product): array
    {
        return [
            'title' => $product->name . ' — Mtoys',
            'description' => $product->description
                ? strip_tags(mb_substr($product->description, 0, 160))
                : $product->name,
        ];
    }

    public function productPayload(string $categorySlug, string $productSlug): array
    {
        $product = Product::with('categories.parentCategory')
            ->where('url', $productSlug)
            ->firstOrFail();

        $images = productImage::where('product_id', $product->id)->get();

        $this->listing->attachPrimaryCategory(collect([$product]));

        $category = $product->categories->first();
        $categoryUrl = $product->category_url ?? ($category?->url ?? 'catalog');

        $allImages = [];
        if ($product->image_path) {
            $allImages[] = $product->image_path;
        }
        foreach ($images as $image) {
            if (! in_array($image->src, $allImages, true)) {
                $allImages[] = $image->src;
            }
        }

        $validCharacteristics = $this->buildCharacteristics($product, []);

        $recommendedProducts = collect();
        if ($product->categories->isNotEmpty()) {
            $categoryIds = $product->categories->pluck('id')->all();
            $recommendedProducts = Product::query()
                ->whereExists(function ($sub) use ($categoryIds) {
                    $sub->selectRaw('1')
                        ->from('category_product')
                        ->whereColumn('category_product.product_id', 'products.id')
                        ->whereIn('category_product.category_id', $categoryIds);
                })
                ->where('id', '!=', $product->id)
                ->whereRaw(ProductListingService::IN_STOCK_SQL)
                ->select([
                    'id', 'name', 'price', 'discount', 'image_path', 'url',
                    'articule', 'availability', 'is_wholesale', 'wholesale_price', 'wholesale_min_quantity',
                    'units_per_box', 'unit_name', 'unit_name_plural',
                ])
                ->orderByDesc('id')
                ->limit(8)
                ->get();
            $this->listing->attachPrimaryCategory($recommendedProducts);
        }

        $unitName = $product->unit_name ?? 'шт';
        $unitPlural = $product->unit_name_plural ?? $unitName;
        $finalPrice = $product->discount > 0
            ? $product->price * (1 - $product->discount / 100)
            : $product->price;
        $inStock = ! in_array($product->availability, [2, '2', 'out_of_stock', 0], true);
        $conditionLabel = match ($product->condition_item) {
            'used' => 'Вживаний',
            'refurbished' => 'Відновлений',
            'new' => 'Новий',
            default => null,
        };

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'articule' => $product->articule,
                'brand' => $product->brand,
                'country' => $product->country,
                'weight' => $product->weight,
                'complectation' => $product->complectation,
                'min_order_quantity' => $product->min_order_quantity,
                'condition_item' => $product->condition_item,
                'condition_label' => $conditionLabel,
                'description' => $product->description,
                'price' => $product->price,
                'discount' => $product->discount,
                'finalPrice' => $finalPrice,
                'image_path' => $product->image_path,
                'availability' => $product->availability,
                'inStock' => $inStock,
                'is_wholesale' => $product->is_wholesale,
                'wholesale_price' => $product->wholesale_price,
                'wholesale_min_quantity' => $product->wholesale_min_quantity,
                'units_per_box' => $product->units_per_box,
                'unit_name' => $unitName,
                'unit_name_plural' => $unitPlural,
                'url' => $product->url,
                'category_url' => $categoryUrl,
            ],
            'images' => $allImages,
            'characteristics' => $validCharacteristics,
            'recommendedProducts' => $recommendedProducts->values()->all(),
            'breadcrumbs' => $category
                ? array_merge(build_category_breadcrumbs($category, true), [['label' => $product->name]])
                : [
                    ['label' => 'Каталог', 'url' => route('catalog')],
                    ['label' => $product->name],
                ],
            'buyBoxProduct' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'discount' => $product->discount,
                'image_path' => $product->image_path,
                'articule' => $product->articule,
                'availability' => $product->availability,
                'is_wholesale' => $product->is_wholesale,
                'wholesale_price' => $product->wholesale_price,
                'wholesale_min_quantity' => $product->wholesale_min_quantity,
                'url' => $product->url,
                'category_url' => $categoryUrl,
                'unit_name' => $unitName,
                'unit_name_plural' => $unitPlural,
            ],
        ];
    }

    private function catalogConfigFromPaginator(
        $paginator,
        array $categories,
        array $popularProducts,
        string $filterBaseUrl,
        string $spaApiUrl,
        bool $showNewest,
        bool $showCta,
        array $breadcrumbs,
        array $hero,
        string $categoriesTitle = 'Розділи',
        string $categoriesHeading = 'Категорії',
        array $categoryTree = [],
        ?array $currentCategory = null,
    ): array {
        return [
            'filterBaseUrl' => $filterBaseUrl,
            'spaApiUrl' => $spaApiUrl,
            'showNewest' => $showNewest,
            'showCta' => $showCta,
            'breadcrumbs' => $breadcrumbs,
            'hero' => $hero,
            'categories' => $categories,
            'categoryTree' => $categoryTree,
            'currentCategory' => $currentCategory,
            'categoriesTitle' => $categoriesTitle,
            'categoriesHeading' => $categoriesHeading,
            'products' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'popularProducts' => $popularProducts,
            'routes' => [
                'home' => url('/'),
                'catalog' => route('catalog'),
                'contacts' => route('kontaktna_informatsiia'),
            ],
        ];
    }

    private function buildCharacteristics(Product $product, array $templateCharacteristics): array
    {
        // Модель приводит к одному виду и новый формат (название, значение и
        // единица измерения), и старый плоский «название => значение».
        $validCharacteristics = $product->characteristicsForDisplay();

        if (empty($validCharacteristics) && !empty($templateCharacteristics)) {
            foreach ($templateCharacteristics as $char) {
                $value = (is_array($product->characteristics) ? ($product->characteristics[$char['key']] ?? null) : null)
                    ?? ($char['default_value'] ?? '-');
                if ($value !== '-' && $value !== '' && $value !== 'Не вказано') {
                    $validCharacteristics[] = [
                        'name' => $char['name'] ?? 'Не вказано',
                        'value' => is_array($value) ? implode(', ', $value) : $value,
                    ];
                }
            }
        }

        return $validCharacteristics;
    }
}
