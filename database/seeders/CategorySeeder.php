<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Template;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaultTemplateId = Template::query()->where('slug', 'default')->value('id');
        $homeTemplateId = Template::query()->where('slug', 'home-kitchen')->value('id') ?? $defaultTemplateId;

        /**
         * Кореневі категорії (parent_id = null) для карток / мега-меню.
         * Діти та онуки — для фільтрів і вкладеної навігації.
         */
        $tree = [
            [
                'url' => 'kukhnya',
                'name' => 'Кухня',
                'template_id' => $homeTemplateId,
                'sort_order' => 10,
                'description' => 'Посуд, аксесуари та все необхідне для приготування їжі.',
                'children' => [
                    [
                        'url' => 'posud',
                        'name' => 'Посуд',
                        'sort_order' => 0,
                        'children' => [
                            ['url' => 'tarelky', 'name' => 'Тарілки', 'sort_order' => 0],
                            ['url' => 'chashky', 'name' => 'Чашки та кружки', 'sort_order' => 1],
                            ['url' => 'mysky', 'name' => 'Миски', 'sort_order' => 2],
                            ['url' => 'sklyanky', 'name' => 'Склянки', 'sort_order' => 3],
                        ],
                    ],
                    [
                        'url' => 'prygotuvannya',
                        'name' => 'Приготування',
                        'sort_order' => 1,
                        'children' => [
                            ['url' => 'skovorody', 'name' => 'Сковороди', 'sort_order' => 0],
                            ['url' => 'kastryuli', 'name' => 'Каструлі', 'sort_order' => 1],
                            ['url' => 'nozhi-kukhni', 'name' => 'Ножі', 'sort_order' => 2],
                            ['url' => 'dosky', 'name' => 'Дошки для нарізання', 'sort_order' => 3],
                        ],
                    ],
                    [
                        'url' => 'tekstyl-kukhni',
                        'name' => 'Текстиль для кухні',
                        'sort_order' => 2,
                        'children' => [
                            ['url' => 'rushnyky-kukhni', 'name' => 'Рушники', 'sort_order' => 0],
                            ['url' => 'fartukhy', 'name' => 'Фартухи', 'sort_order' => 1],
                            ['url' => 'skaterty', 'name' => 'Скатертини', 'sort_order' => 2],
                        ],
                    ],
                    [
                        'url' => 'zberigannya-yizhi',
                        'name' => 'Зберігання їжі',
                        'sort_order' => 3,
                        'children' => [
                            ['url' => 'kontaynery', 'name' => 'Контейнери', 'sort_order' => 0],
                            ['url' => 'banky', 'name' => 'Банки для круп', 'sort_order' => 1],
                        ],
                    ],
                ],
            ],
            [
                'url' => 'vanna',
                'name' => 'Ванна кімната',
                'template_id' => $homeTemplateId,
                'sort_order' => 20,
                'description' => 'Аксесуари, текстиль і організація простору у ванній.',
                'children' => [
                    [
                        'url' => 'aksessuary-vanny',
                        'name' => 'Аксесуари',
                        'sort_order' => 0,
                        'children' => [
                            ['url' => 'dyspensery', 'name' => 'Диспенсери', 'sort_order' => 0],
                            ['url' => 'mylnytsi', 'name' => 'Мильниці', 'sort_order' => 1],
                            ['url' => 'shchitky', 'name' => 'Щітки та йоржики', 'sort_order' => 2],
                        ],
                    ],
                    [
                        'url' => 'tekstyl-vanny',
                        'name' => 'Текстиль',
                        'sort_order' => 1,
                        'children' => [
                            ['url' => 'rushnyky-vanny', 'name' => 'Рушники для ванни', 'sort_order' => 0],
                            ['url' => 'khalaty', 'name' => 'Халати', 'sort_order' => 1],
                            ['url' => 'kylvymky-vanny', 'name' => 'Килимки', 'sort_order' => 2],
                        ],
                    ],
                    [
                        'url' => 'organizatsiya-vanny',
                        'name' => 'Організація',
                        'sort_order' => 2,
                        'children' => [
                            ['url' => 'polytsi-vanny', 'name' => 'Полиці', 'sort_order' => 0],
                            ['url' => 'koshyky-vanny', 'name' => 'Кошики', 'sort_order' => 1],
                        ],
                    ],
                ],
            ],
            [
                'url' => 'dim',
                'name' => 'Дім та декор',
                'template_id' => $homeTemplateId,
                'sort_order' => 30,
                'description' => 'Декор, освітлення та атмосфера вашого дому.',
                'children' => [
                    [
                        'url' => 'dekor',
                        'name' => 'Декор',
                        'sort_order' => 0,
                        'children' => [
                            ['url' => 'vazy', 'name' => 'Вази', 'sort_order' => 0],
                            ['url' => 'svichky', 'name' => 'Свічки', 'sort_order' => 1],
                            ['url' => 'ramky', 'name' => 'Рамки', 'sort_order' => 2],
                        ],
                    ],
                    [
                        'url' => 'osvitlennya',
                        'name' => 'Освітлення',
                        'sort_order' => 1,
                        'children' => [
                            ['url' => 'nastilni-lampy', 'name' => 'Настільні лампи', 'sort_order' => 0],
                            ['url' => 'girlyandy', 'name' => 'Гірлянди', 'sort_order' => 1],
                        ],
                    ],
                    [
                        'url' => 'tekstyl-domu',
                        'name' => 'Текстиль для дому',
                        'sort_order' => 2,
                        'children' => [
                            ['url' => 'podushky', 'name' => 'Декоративні подушки', 'sort_order' => 0],
                            ['url' => 'pledy', 'name' => 'Пледи', 'sort_order' => 1],
                        ],
                    ],
                ],
            ],
            [
                'url' => 'prybyrannya',
                'name' => 'Прибирання',
                'template_id' => $defaultTemplateId,
                'sort_order' => 40,
                'description' => 'Засоби і інструменти для чистоти в домі.',
                'children' => [
                    [
                        'url' => 'instrumenty-prybyrannya',
                        'name' => 'Інструменти',
                        'sort_order' => 0,
                        'children' => [
                            ['url' => 'shvabry', 'name' => 'Швабри', 'sort_order' => 0],
                            ['url' => 'vidra', 'name' => 'Відра', 'sort_order' => 1],
                            ['url' => 'shchitky-prybyrannya', 'name' => 'Щітки', 'sort_order' => 2],
                        ],
                    ],
                    [
                        'url' => 'zberigannya-prybyrannya',
                        'name' => 'Зберігання',
                        'sort_order' => 1,
                        'children' => [
                            ['url' => 'konteynery-prybyrannya', 'name' => 'Контейнери для засобів', 'sort_order' => 0],
                            ['url' => 'organayzery', 'name' => 'Органайзери', 'sort_order' => 1],
                        ],
                    ],
                ],
            ],
            [
                'url' => 'organizatsiya',
                'name' => 'Організація простору',
                'template_id' => $defaultTemplateId,
                'sort_order' => 50,
                'description' => 'Коробки, вішалки та системи зберігання.',
                'children' => [
                    [
                        'url' => 'zberigannya',
                        'name' => 'Зберігання',
                        'sort_order' => 0,
                        'children' => [
                            ['url' => 'korobky', 'name' => 'Коробки', 'sort_order' => 0],
                            ['url' => 'koshyky-zberigannya', 'name' => 'Кошики', 'sort_order' => 1],
                            ['url' => 'vyshalky', 'name' => 'Вішалки', 'sort_order' => 2],
                        ],
                    ],
                    [
                        'url' => 'ofis-vdoma',
                        'name' => 'Офіс вдома',
                        'sort_order' => 1,
                        'children' => [
                            ['url' => 'pidstavky', 'name' => 'Підставки', 'sort_order' => 0],
                            ['url' => 'lotrky', 'name' => 'Лотки', 'sort_order' => 1],
                        ],
                    ],
                ],
            ],
            [
                'url' => 'sad-ta-balkon',
                'name' => 'Сад і балкон',
                'template_id' => $defaultTemplateId,
                'sort_order' => 60,
                'description' => 'Горщики, інструменти та декор для зеленого куточка.',
                'children' => [
                    [
                        'url' => 'roslyny',
                        'name' => 'Для рослин',
                        'sort_order' => 0,
                        'children' => [
                            ['url' => 'gorshky', 'name' => 'Горщики', 'sort_order' => 0],
                            ['url' => 'leiky', 'name' => 'Лійки', 'sort_order' => 1],
                        ],
                    ],
                    [
                        'url' => 'mebli-balkon',
                        'name' => 'Меблі для балкона',
                        'sort_order' => 1,
                        'children' => [
                            ['url' => 'stolyky', 'name' => 'Столики', 'sort_order' => 0],
                            ['url' => 'stiltsi', 'name' => 'Стільці', 'sort_order' => 1],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($tree as $sectionIndex => $section) {
            $sectionCategory = Category::query()->updateOrCreate(
                ['url' => $section['url']],
                [
                    'name' => $section['name'],
                    'template_id' => $section['template_id'] ?? $defaultTemplateId,
                    'seo_title' => $section['name'] . ' — DOMEXO',
                    'seo_description' => $section['description'] ?? null,
                    'seo_keywords' => null,
                    'description' => $section['description'] ?? null,
                    'is_active' => true,
                    'sort_order' => $section['sort_order'] ?? ($sectionIndex * 10),
                    'parent_id' => null,
                ],
            );

            foreach (($section['children'] ?? []) as $childIndex => $child) {
                $childCategory = Category::query()->updateOrCreate(
                    ['url' => $child['url']],
                    [
                        'name' => $child['name'],
                        'template_id' => $section['template_id'] ?? $defaultTemplateId,
                        'seo_title' => $child['name'] . ' — DOMEXO',
                        'seo_description' => $child['description'] ?? ($section['name'] . ': ' . $child['name']),
                        'description' => $child['description'] ?? null,
                        'is_active' => true,
                        'sort_order' => $child['sort_order'] ?? $childIndex,
                        'parent_id' => $sectionCategory->id,
                    ],
                );

                foreach (($child['children'] ?? []) as $grandIndex => $grand) {
                    Category::query()->updateOrCreate(
                        ['url' => $grand['url']],
                        [
                            'name' => $grand['name'],
                            'template_id' => $section['template_id'] ?? $defaultTemplateId,
                            'seo_title' => $grand['name'] . ' — DOMEXO',
                            'seo_description' => $section['name'] . ' / ' . $child['name'] . ' / ' . $grand['name'],
                            'description' => $grand['description'] ?? null,
                            'is_active' => true,
                            'sort_order' => $grand['sort_order'] ?? $grandIndex,
                            'parent_id' => $childCategory->id,
                        ],
                    );
                }
            }
        }

        $keepUrls = $this->collectUrls($tree);
        $obsolete = Category::query()->whereNotIn('url', $keepUrls)->get();
        if ($obsolete->isNotEmpty()) {
            $ids = $obsolete->pluck('id');
            \Illuminate\Support\Facades\DB::table('category_product')->whereIn('category_id', $ids)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('product_category_catalog_relations')) {
                \Illuminate\Support\Facades\DB::table('product_category_catalog_relations')->whereIn('category_id', $ids)->delete();
            }
            Category::query()->whereIn('id', $ids)->delete();
            $this->command?->info('Removed obsolete categories: ' . $obsolete->count());
        }

        $this->command?->info('Categories seeded: ' . Category::query()->count());
    }

    private function collectUrls(array $tree): array
    {
        $urls = [];
        foreach ($tree as $section) {
            $urls[] = $section['url'];
            foreach (($section['children'] ?? []) as $child) {
                $urls[] = $child['url'];
                foreach (($child['children'] ?? []) as $grand) {
                    $urls[] = $grand['url'];
                }
            }
        }

        return $urls;
    }
}
