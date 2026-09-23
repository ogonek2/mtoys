<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Template;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $defaultTemplateId = Template::query()->where('slug', 'default')->value('id');

        $root = Catalog::query()->updateOrCreate(
            ['url' => 'main'],
            [
                'name' => 'Основной каталог',
                'template_id' => $defaultTemplateId,
                'description' => 'Каталог витрин и подборок (для главной/меню).',
                'is_active' => true,
                'sort_order' => 0,
                'type' => 'group',
                'parent_id' => null,
            ],
        );

        $tree = [
            [
                'url' => 'promo',
                'name' => 'Акции',
                'sort_order' => 10,
                'children' => [
                    ['url' => 'new', 'name' => 'Новинки', 'sort_order' => 0],
                    ['url' => 'hit', 'name' => 'Хиты продаж', 'sort_order' => 1],
                    ['url' => 'sale', 'name' => 'Скидки до -30%', 'sort_order' => 2],
                    ['url' => 'outlet', 'name' => 'Outlet', 'sort_order' => 3],
                ],
            ],
            [
                'url' => 'brands',
                'name' => 'Бренды',
                'sort_order' => 20,
                'children' => [
                    ['url' => 'demobrand', 'name' => 'DemoBrand', 'sort_order' => 0],
                    ['url' => 'demosound', 'name' => 'DemoSound', 'sort_order' => 1],
                    ['url' => 'demohome', 'name' => 'DemoHome', 'sort_order' => 2],
                    ['url' => 'noname', 'name' => 'NoName', 'sort_order' => 3],
                ],
            ],
            [
                'url' => 'collections',
                'name' => 'Подборки',
                'sort_order' => 30,
                'children' => [
                    ['url' => 'for-home', 'name' => 'Для дома', 'sort_order' => 0],
                    ['url' => 'for-kitchen', 'name' => 'Для кухни', 'sort_order' => 1],
                    ['url' => 'for-gifts', 'name' => 'Идеи подарков', 'sort_order' => 2],
                    ['url' => 'budget', 'name' => 'До 1000 ₴', 'sort_order' => 3],
                ],
            ],
            [
                'url' => 'seasonal',
                'name' => 'Сезонное',
                'sort_order' => 40,
                'children' => [
                    ['url' => 'summer', 'name' => 'Лето', 'sort_order' => 0],
                    ['url' => 'winter', 'name' => 'Зима', 'sort_order' => 1],
                    ['url' => 'back-to-school', 'name' => 'К школе', 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($tree as $groupIndex => $group) {
            $groupCatalog = Catalog::query()->updateOrCreate(
                ['url' => $group['url']],
                [
                    'name' => $group['name'],
                    'template_id' => $defaultTemplateId,
                    'description' => $group['description'] ?? null,
                    'is_active' => true,
                    'sort_order' => $group['sort_order'] ?? ($groupIndex * 10),
                    'type' => 'group',
                    'parent_id' => $root->id,
                ],
            );

            foreach (($group['children'] ?? []) as $childIndex => $child) {
                Catalog::query()->updateOrCreate(
                    ['url' => $child['url']],
                    [
                        'name' => $child['name'],
                        'template_id' => $defaultTemplateId,
                        'description' => $child['description'] ?? null,
                        'is_active' => true,
                        'sort_order' => $child['sort_order'] ?? $childIndex,
                        'type' => 'subgroup',
                        'parent_id' => $groupCatalog->id,
                    ],
                );
            }
        }
    }
}

