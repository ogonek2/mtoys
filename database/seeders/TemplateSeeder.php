<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        Template::query()->updateOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default template',
                'description' => 'Базовый шаблон для категорий/каталогов.',
                'seo_title' => 'Интернет-магазин',
                'seo_description' => 'Демо-контент для разработки и наполнения.',
                'seo_keywords' => 'shop, demo, filament, laravel',
                'characteristics' => ['Цвет', 'Материал', 'Размер'],
                'modifications' => [
                    ['name' => 'Вариант', 'options' => ['A', 'B', 'C']],
                ],
                'additional_fields' => [
                    ['key' => 'warranty', 'label' => 'Гарантия', 'type' => 'text'],
                ],
                'is_active' => true,
            ],
        );

        Template::query()->updateOrCreate(
            ['slug' => 'electronics'],
            [
                'name' => 'Electronics template',
                'description' => 'Шаблон для электроники.',
                'seo_title' => 'Электроника',
                'seo_description' => 'Смартфоны, наушники, аксессуары.',
                'seo_keywords' => 'electronics, gadgets',
                'characteristics' => ['Бренд', 'Модель', 'Гарантия'],
                'modifications' => [
                    ['name' => 'Память', 'options' => ['64GB', '128GB', '256GB']],
                ],
                'additional_fields' => [
                    ['key' => 'country', 'label' => 'Страна производства', 'type' => 'text'],
                ],
                'is_active' => true,
            ],
        );
        Template::query()->updateOrCreate(
            ['slug' => 'home-kitchen'],
            [
                'name' => 'Home & Kitchen template',
                'description' => 'Шаблон для товарів дому, кухні та ванної.',
                'seo_title' => 'Товари для дому — DOMEXO',
                'seo_description' => 'Посуд, текстиль, декор та організація простору.',
                'seo_keywords' => 'dim, kukhnya, vanna, dekor',
                'characteristics' => ['Матеріал', 'Колір', 'Розмір', 'Бренд'],
                'modifications' => [
                    ['name' => 'Колір', 'options' => ['Білий', 'Бежевий', 'Сірий', 'Чорний', 'Золотий']],
                ],
                'additional_fields' => [
                    ['key' => 'care', 'label' => 'Догляд', 'type' => 'text'],
                ],
                'is_active' => true,
            ],
        );
    }
}

