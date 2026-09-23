<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategoryCatalogRelation;
use App\Models\productImage;
use App\Models\package;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private function uniqueProductUrl(string $name, ?string $articule = null, ?int $excludeProductId = null): string
    {
        $base = Product::generateHref($name);
        if ($base === '') {
            $base = 'product';
        }

        $candidate = $base;
        $suffix = 2;

        while (Product::query()
            ->when($excludeProductId, fn ($q) => $q->where('id', '!=', $excludeProductId))
            ->where('url', $candidate)
            ->exists()
        ) {
            if ($articule) {
                $candidate = $base . '-' . Product::generateHref((string) $articule);
                if (!Product::query()
                    ->when($excludeProductId, fn ($q) => $q->where('id', '!=', $excludeProductId))
                    ->where('url', $candidate)
                    ->exists()
                ) {
                    break;
                }
            }

            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Тематичні товари для листових категорій (url => список товарів).
     */
    private function catalogMap(): array
    {
        return [
            'tarelky' => [
                ['name' => 'Тарілка біла матова 27 см', 'price' => 189, 'discount' => 0],
                ['name' => 'Набір глибоких тарілок 4 шт', 'price' => 649, 'discount' => 10],
                ['name' => 'Тарілка десертна з золотим кантом', 'price' => 249, 'discount' => 5],
            ],
            'chashky' => [
                ['name' => 'Кружка керамічна 350 мл', 'price' => 159, 'discount' => 0],
                ['name' => 'Чашка з блюдцем Classic', 'price' => 299, 'discount' => 15],
                ['name' => 'Набір кружок 6 шт', 'price' => 899, 'discount' => 0, 'wholesale' => true],
            ],
            'mysky' => [
                ['name' => 'Миска для салату 24 см', 'price' => 219, 'discount' => 0],
                ['name' => 'Набір мисок 3 шт', 'price' => 549, 'discount' => 8],
            ],
            'sklyanky' => [
                ['name' => 'Склянка highball 400 мл', 'price' => 99, 'discount' => 0],
                ['name' => 'Набір склянок 6 шт', 'price' => 499, 'discount' => 12],
            ],
            'skovorody' => [
                ['name' => 'Сковорода антипригарна 28 см', 'price' => 799, 'discount' => 10],
                ['name' => 'Сковорода-гриль чавунна', 'price' => 1299, 'discount' => 0, 'wholesale' => true],
                ['name' => 'Сотейник з кришкою 24 см', 'price' => 1099, 'discount' => 5],
            ],
            'kastryuli' => [
                ['name' => 'Каструля нержавіюча 5 л', 'price' => 1499, 'discount' => 0],
                ['name' => 'Набір каструль 3 шт', 'price' => 3299, 'discount' => 15],
            ],
            'nozhi-kukhni' => [
                ['name' => 'Ніж шеф-кухаря 20 см', 'price' => 899, 'discount' => 0],
                ['name' => 'Набір кухонних ножів 5 шт', 'price' => 1599, 'discount' => 10],
            ],
            'dosky' => [
                ['name' => 'Дошка дубова 40×30 см', 'price' => 649, 'discount' => 0],
                ['name' => 'Дошка бамбукова складана', 'price' => 349, 'discount' => 5],
            ],
            'rushnyky-kukhni' => [
                ['name' => 'Рушник кухонний лляний', 'price' => 179, 'discount' => 0],
                ['name' => 'Набір рушників 3 шт', 'price' => 449, 'discount' => 10],
            ],
            'fartukhy' => [
                ['name' => 'Фартух кухонний з кишенями', 'price' => 399, 'discount' => 0],
            ],
            'skaterty' => [
                ['name' => 'Скатертина водостійка 140×180', 'price' => 599, 'discount' => 8],
            ],
            'kontaynery' => [
                ['name' => 'Контейнер hermetic 1 л', 'price' => 129, 'discount' => 0],
                ['name' => 'Набір контейнерів 5 шт', 'price' => 549, 'discount' => 12, 'wholesale' => true],
            ],
            'banky' => [
                ['name' => 'Банка для круп 1.5 л', 'price' => 199, 'discount' => 0],
                ['name' => 'Набір банок з етикетками', 'price' => 699, 'discount' => 5],
            ],
            'dyspensery' => [
                ['name' => 'Диспенсер для рідкого мила', 'price' => 349, 'discount' => 0],
                ['name' => 'Диспенсер подвійний', 'price' => 549, 'discount' => 10],
            ],
            'mylnytsi' => [
                ['name' => 'Мильниця керамічна', 'price' => 149, 'discount' => 0],
            ],
            'shchitky' => [
                ['name' => 'Йоржик для унітазу з підставкою', 'price' => 299, 'discount' => 0],
                ['name' => 'Щітка для душу', 'price' => 189, 'discount' => 5],
            ],
            'rushnyky-vanny' => [
                ['name' => 'Рушник банний 70×140', 'price' => 449, 'discount' => 0],
                ['name' => 'Набір рушників 4 шт', 'price' => 1199, 'discount' => 15],
            ],
            'khalaty' => [
                ['name' => 'Халат махровий унісекс', 'price' => 999, 'discount' => 10],
            ],
            'kylvymky-vanny' => [
                ['name' => 'Килимок для ванни антиковзкий', 'price' => 349, 'discount' => 0],
            ],
            'polytsi-vanny' => [
                ['name' => 'Полиця кутова для душу', 'price' => 399, 'discount' => 0],
                ['name' => 'Полиця настінна двоярусна', 'price' => 549, 'discount' => 8],
            ],
            'koshyky-vanny' => [
                ['name' => 'Кошик для білизни 60 л', 'price' => 699, 'discount' => 0],
            ],
            'vazy' => [
                ['name' => 'Ваза керамічна мінімалізм', 'price' => 549, 'discount' => 0],
                ['name' => 'Ваза скляна прозора 30 см', 'price' => 399, 'discount' => 5],
            ],
            'svichky' => [
                ['name' => 'Свічка ароматична Vanilla', 'price' => 249, 'discount' => 0],
                ['name' => 'Набір свічок 3 шт', 'price' => 599, 'discount' => 10],
            ],
            'ramky' => [
                ['name' => 'Рамка для фото 20×30', 'price' => 199, 'discount' => 0],
                ['name' => 'Набір рамок 5 шт', 'price' => 749, 'discount' => 12],
            ],
            'nastilni-lampy' => [
                ['name' => 'Лампа настільна з абажуром', 'price' => 1299, 'discount' => 0],
                ['name' => 'Лампа LED з USB', 'price' => 899, 'discount' => 10],
            ],
            'girlyandy' => [
                ['name' => 'Гірлянда тепле світло 5 м', 'price' => 349, 'discount' => 0],
            ],
            'podushky' => [
                ['name' => 'Подушка декоративна 45×45', 'price' => 399, 'discount' => 0],
                ['name' => 'Набір подушок 2 шт', 'price' => 699, 'discount' => 8],
            ],
            'pledy' => [
                ['name' => 'Плед м\'який 150×200', 'price' => 899, 'discount' => 10],
            ],
            'shvabry' => [
                ['name' => 'Швабра з віджимом', 'price' => 549, 'discount' => 0],
                ['name' => 'Швабра плоска мікрофібра', 'price' => 399, 'discount' => 5],
            ],
            'vidra' => [
                ['name' => 'Відро з віджимом 10 л', 'price' => 449, 'discount' => 0],
            ],
            'shchitky-prybyrannya' => [
                ['name' => 'Набір щіток для прибирання', 'price' => 299, 'discount' => 0],
            ],
            'konteynery-prybyrannya' => [
                ['name' => 'Контейнер для миючих засобів', 'price' => 349, 'discount' => 0],
            ],
            'organayzery' => [
                ['name' => 'Органайзер під мийку', 'price' => 499, 'discount' => 8],
            ],
            'korobky' => [
                ['name' => 'Коробка для зберігання з кришкою', 'price' => 249, 'discount' => 0],
                ['name' => 'Набір коробок 3 шт', 'price' => 649, 'discount' => 10, 'wholesale' => true],
            ],
            'koshyky-zberigannya' => [
                ['name' => 'Кошик плетений середній', 'price' => 399, 'discount' => 0],
            ],
            'vyshalky' => [
                ['name' => 'Вішалки дерев\'яні 10 шт', 'price' => 349, 'discount' => 0, 'wholesale' => true],
                ['name' => 'Вішалка-плічка металева', 'price' => 29, 'discount' => 0],
            ],
            'pidstavky' => [
                ['name' => 'Підставка під ноутбук', 'price' => 599, 'discount' => 5],
            ],
            'lotrky' => [
                ['name' => 'Лоток для канцелярії', 'price' => 199, 'discount' => 0],
            ],
            'gorshky' => [
                ['name' => 'Горщик керамічний 15 см', 'price' => 279, 'discount' => 0],
                ['name' => 'Набір горщиків 3 шт', 'price' => 699, 'discount' => 10],
            ],
            'leiky' => [
                ['name' => 'Лійка садова 2 л', 'price' => 249, 'discount' => 0],
            ],
            'stolyky' => [
                ['name' => 'Столик складаний для балкона', 'price' => 1199, 'discount' => 8],
            ],
            'stiltsi' => [
                ['name' => 'Стілець складаний сад/балкон', 'price' => 799, 'discount' => 0],
            ],
        ];
    }

    public function run(): void
    {
        $categoriesByUrl = Category::query()->get()->keyBy('url');
        $catalogs = Catalog::query()->orderBy('id')->get();
        $packages = package::query()->orderBy('id')->get();
        $productColumns = collect(Schema::getColumnListing('products'))->flip();

        if ($categoriesByUrl->isEmpty()) {
            $this->command?->warn('No categories found; skipping ProductSeeder.');
            return;
        }

        $brands = ['DOMEXO', 'HomeLine', 'NordicHome', 'CasaMia', 'PureLiving'];
        $colors = ['Білий', 'Бежевий', 'Сірий', 'Чорний', 'Оливковий', 'Терракота'];
        $materials = ['Кераміка', 'Скло', 'Метал', 'Дерево', 'Текстиль', 'Пластик', 'Бамбук'];

        $productsData = [];
        $index = 1;

        foreach ($this->catalogMap() as $categoryUrl => $items) {
            if (!$categoriesByUrl->has($categoryUrl)) {
                continue;
            }

            foreach ($items as $item) {
                $isWholesale = !empty($item['wholesale']);
                $price = (int) $item['price'];
                $discount = (int) ($item['discount'] ?? 0);
                $articule = 'DMK-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);

                $productsData[] = [
                    'name' => $item['name'],
                    'articule' => $articule,
                    'description' => $item['name'] . ' — якісний товар для дому від DOMEXO. Ідеально підходить для щоденного використання.',
                    'price' => (string) $price,
                    'discount' => $discount,
                    'brand' => Arr::random($brands),
                    'condition_item' => 'new',
                    'availability' => Arr::random(['in_stock', 'in_stock', 'in_stock', 'in_stock', 'out_of_stock']),
                    'is_wholesale' => $isWholesale,
                    'wholesale_price' => $isWholesale ? round($price * 0.85, 2) : null,
                    'wholesale_min_quantity' => $isWholesale ? random_int(5, 20) : null,
                    'units_per_box' => $isWholesale ? random_int(4, 24) : null,
                    'unit_name' => 'шт',
                    'unit_name_plural' => 'шт',
                    'image_path' => 'https://picsum.photos/seed/domexo-' . $articule . '/800/800',
                    'category_url' => $categoryUrl,
                    'characteristics' => [
                        'Бренд' => Arr::random($brands),
                        'Колір' => Arr::random($colors),
                        'Матеріал' => Arr::random($materials),
                    ],
                ];

                $index++;
            }
        }

        // Extra fillers for leaf categories that have fewer than 2 products
        $leafCategories = Category::query()
            ->whereDoesntHave('childCategories')
            ->whereNotNull('parent_id')
            ->get();

        foreach ($leafCategories as $leaf) {
            $already = collect($productsData)->where('category_url', $leaf->url)->count();
            for ($n = $already; $n < 2; $n++) {
                $articule = 'DMK-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);
                $price = random_int(99, 2499);
                $productsData[] = [
                    'name' => $leaf->name . ' — модель ' . chr(65 + $n),
                    'articule' => $articule,
                    'description' => 'Демо-товар категорії «' . $leaf->name . '».',
                    'price' => (string) $price,
                    'discount' => Arr::random([0, 0, 5, 10, 15]),
                    'brand' => Arr::random($brands),
                    'condition_item' => 'new',
                    'availability' => 'in_stock',
                    'is_wholesale' => false,
                    'unit_name' => 'шт',
                    'unit_name_plural' => 'шт',
                    'image_path' => 'https://picsum.photos/seed/domexo-fill-' . $articule . '/800/800',
                    'category_url' => $leaf->url,
                    'characteristics' => [
                        'Колір' => Arr::random($colors),
                        'Матеріал' => Arr::random($materials),
                    ],
                ];
                $index++;
            }
        }

        Model::withoutEvents(function () use ($productsData, $categoriesByUrl, $catalogs, $packages, $productColumns) {
            foreach ($productsData as $data) {
                $categoryUrl = $data['category_url'];
                unset($data['category_url']);

                $payload = array_merge(
                    [
                        'seo_title' => ($data['seo_title'] ?? $data['name']) . ' — DOMEXO',
                        'seo_description' => $data['seo_description'] ?? Str::limit(strip_tags($data['description'] ?? ''), 160),
                        'seo_keywords' => $data['seo_keywords'] ?? null,
                        'modifications' => $data['modifications'] ?? null,
                        'additional_fields' => $data['additional_fields'] ?? null,
                    ],
                    $data,
                );

                if (!array_key_exists('availability', $payload)) {
                    $payload['availability'] = 'in_stock';
                }

                if (array_key_exists('url', $productColumns->all())) {
                    $existingId = Product::query()->where('articule', $data['articule'])->value('id');
                    $payload['url'] = $this->uniqueProductUrl(
                        (string) ($payload['name'] ?? ''),
                        (string) ($payload['articule'] ?? ''),
                        $existingId ? (int) $existingId : null,
                    );
                }

                $payload = array_intersect_key($payload, $productColumns->all());

                $product = Product::query()->updateOrCreate(
                    ['articule' => $data['articule']],
                    $payload,
                );

                $category = $categoriesByUrl->get($categoryUrl);
                $categoryIds = $category ? [$category->id] : [];

                // Also attach parent so mid-level pages have products
                if ($category?->parent_id) {
                    $categoryIds[] = $category->parent_id;
                    $parent = $categoriesByUrl->firstWhere('id', $category->parent_id);
                    if ($parent?->parent_id) {
                        $categoryIds[] = $parent->parent_id;
                    }
                }

                $categoryIds = array_values(array_unique($categoryIds));
                if ($categoryIds) {
                    $product->categories()->sync($categoryIds);
                }

                if ($catalogs->isNotEmpty()) {
                    $catalogIds = $catalogs->random(min(2, $catalogs->count()))->pluck('id')->all();
                    $product->catalogs()->syncWithoutDetaching($catalogIds);
                }

                $primaryCategoryId = $categoryIds[0] ?? null;
                $primaryCatalogId = $catalogs->isNotEmpty() ? $catalogs->first()->id : null;

                if ($primaryCategoryId || $primaryCatalogId) {
                    ProductCategoryCatalogRelation::query()->updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'category_id' => $primaryCategoryId,
                            'catalog_id' => $primaryCatalogId,
                        ],
                        [
                            'sort_order' => 0,
                            'is_primary' => true,
                        ],
                    );
                }

                for ($k = 1; $k <= 2; $k++) {
                    productImage::query()->updateOrCreate(
                        [
                            'product_id' => (string) $product->id,
                            'src' => "https://picsum.photos/seed/domexo-{$product->articule}-{$k}/900/900",
                        ],
                        [
                            'product_id' => (string) $product->id,
                            'src' => "https://picsum.photos/seed/domexo-{$product->articule}-{$k}/900/900",
                        ],
                    );
                }

                if ($packages->isNotEmpty() && random_int(0, 1)) {
                    $pkgIds = $packages->random(min(1, $packages->count()))->pluck('id')->all();
                    $product->packages()->syncWithoutDetaching($pkgIds);
                }
            }
        });

        $this->command?->info('Products seeded: ' . Product::query()->count());
    }
}
