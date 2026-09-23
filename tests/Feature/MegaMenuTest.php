<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\MegaMenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MegaMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_mega_menu_lists_root_categories(): void
    {
        Category::create([
            'name' => 'Ножи',
            'is_active' => true,
        ]);

        $items = get_mega_menu_data();

        $this->assertCount(1, $items);
        $this->assertSame('Ножи', $items[0]['category']->name);
    }

    public function test_homepage_renders_root_categories_in_mega_menu(): void
    {
        Category::create([
            'name' => 'ОрганайзериДляМегаМеню',
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ОрганайзериДляМегаМеню', false)
            ->assertSee('mega-menu__cat', false);
    }

    public function test_mega_menu_shows_products_from_category(): void
    {
        $category = Category::create([
            'name' => 'Газове обладнання',
            'url' => 'gazove-obladnannya',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Газовий балон Tourist 5л',
            'url' => 'gazovyi-balon-tourist-5l',
            'price' => 890,
            'availability' => 'in_stock',
        ]);
        $product->categories()->attach($category->id);

        app(MegaMenuService::class)->forget();
        $items = get_mega_menu_data();

        $this->assertCount(1, $items);
        $this->assertCount(1, $items[0]['products']);
        $this->assertSame('Газовий балон Tourist 5л', $items[0]['products']->first()->name);
        $this->assertSame('gazove-obladnannya', $items[0]['products']->first()->category_url);

        $this->get('/')
            ->assertOk()
            ->assertSee('Газовий балон Tourist 5л', false)
            ->assertSee('mega-card', false)
            ->assertSee('890', false);
    }

    public function test_mega_menu_shows_products_in_subcategories(): void
    {
        $root = Category::create([
            'name' => 'Туризм',
            'url' => 'turyzm',
            'is_active' => true,
        ]);
        $child = Category::create([
            'name' => 'Газове обладнання',
            'url' => 'gazove',
            'parent_id' => $root->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Пальник газовий Compact',
            'url' => 'palnyk-compact',
            'price' => 450,
            'availability' => 'in_stock',
        ]);
        $product->categories()->attach($child->id);

        app(MegaMenuService::class)->forget();
        $items = get_mega_menu_data();

        $this->assertCount(1, $items[0]['children']);
        $this->assertCount(1, $items[0]['children'][0]['products']);
        $this->assertSame('Пальник газовий Compact', $items[0]['children'][0]['products']->first()->name);
    }
}
