<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Catalogs\CatalogResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Orders\OrdersResource;
use App\Filament\Resources\Packages\PackageResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Templates\Pages\EditTemplate;
use App\Filament\Resources\Templates\TemplateResource;
use App\Filament\Widgets\LatestOrdersWidget;
use App\Filament\Widgets\ShopStatsOverview;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Orders;
use App\Models\package;
use App\Models\Product;
use App\Models\productImage;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected Template $template;

    protected Category $parentCategory;

    protected Category $category;

    protected Catalog $catalog;

    protected package $package;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $this->template = Template::create([
            'name' => 'Электроника',
            'characteristics' => ['Цвет', 'Материал'],
            'modifications' => [['name' => 'Память', 'options' => ['64GB', '128GB']]],
            'additional_fields' => [['key' => 'warranty', 'label' => 'Гарантия', 'type' => 'text']],
            'is_active' => true,
        ]);

        $this->parentCategory = Category::create([
            'name' => 'Дом',
            'template_id' => $this->template->id,
        ]);

        $this->category = Category::create([
            'name' => 'Тарелки',
            'parent_id' => $this->parentCategory->id,
            'template_id' => $this->template->id,
        ]);

        $this->catalog = Catalog::create([
            'name' => 'Акции',
            'type' => 'group',
            'template_id' => $this->template->id,
        ]);

        $this->package = package::create(['name' => 'Комплектация', 'value' => 'Коробка']);

        $this->product = Product::create([
            'name' => 'Тарелка белая',
            'articule' => 'ART-1',
            'price' => '199',
            'discount' => 10,
            'availability' => 'in_stock',
            'condition_item' => 'new',
            'brand' => 'DOMEXO',
            'image_path' => 'https://example.test/image.jpg',
            'characteristics' => ['Цвет' => 'Белый'],
        ]);

        $this->product->categories()->attach([$this->category->id, $this->parentCategory->id]);
        $this->product->catalogs()->attach($this->catalog->id);
        $this->product->packages()->attach($this->package->id);

        productImage::create([
            'product_id' => (string) $this->product->id,
            'src' => 'https://example.test/extra.jpg',
        ]);

        Orders::create([
            'delivery_service' => 'Нова Пошта',
            'city' => 'Київ',
            'warehouse' => 'Відділення 1',
            'name' => 'Иван',
            'lastname' => 'Петров',
            'phone' => '+380001112233',
            'payment' => 'card',
            'total_price' => '199',
            'cart' => json_encode([
                ['id' => 1, 'name' => 'Тарелка белая', 'price' => 199, 'quantity' => 1],
            ]),
        ]);
    }

    public function test_dashboard_renders(): void
    {
        $this->get('/admin')->assertSuccessful();
    }

    public function test_stats_widget_counts_products_and_orders(): void
    {
        Livewire::test(ShopStatsOverview::class)
            ->assertSuccessful()
            ->assertSee('Товаров')
            ->assertSee('Заказов')
            ->assertSee('Без фото');
    }

    public function test_latest_orders_widget_lists_orders(): void
    {
        Livewire::test(LatestOrdersWidget::class)
            ->assertSuccessful()
            ->loadTable()
            ->assertCanSeeTableRecords(Orders::query()->get())
            ->assertSee('Иван');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function resourceListPages(): array
    {
        return [
            'products' => [ProductResource::class],
            'categories' => [CategoryResource::class],
            'catalogs' => [CatalogResource::class],
            'templates' => [TemplateResource::class],
            'packages' => [PackageResource::class],
            'orders' => [OrdersResource::class],
        ];
    }

    #[DataProvider('resourceListPages')]
    public function test_resource_list_page_renders(string $resource): void
    {
        $this->get($resource::getUrl('index'))->assertSuccessful();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function resourceCreatePages(): array
    {
        return [
            'products' => [ProductResource::class],
            'categories' => [CategoryResource::class],
            'catalogs' => [CatalogResource::class],
            'templates' => [TemplateResource::class],
            'packages' => [PackageResource::class],
        ];
    }

    #[DataProvider('resourceCreatePages')]
    public function test_resource_create_page_renders(string $resource): void
    {
        $this->get($resource::getUrl('create'))->assertSuccessful();
    }

    public function test_product_view_and_edit_pages_render(): void
    {
        $this->get(ProductResource::getUrl('view', ['record' => $this->product]))
            ->assertSuccessful()
            ->assertSee('Тарелка белая');

        $this->get(ProductResource::getUrl('edit', ['record' => $this->product]))
            ->assertSuccessful();
    }

    public function test_category_catalog_template_package_edit_pages_render(): void
    {
        $this->get(CategoryResource::getUrl('edit', ['record' => $this->category]))->assertSuccessful();
        $this->get(CatalogResource::getUrl('edit', ['record' => $this->catalog]))->assertSuccessful();
        $this->get(TemplateResource::getUrl('edit', ['record' => $this->template]))->assertSuccessful();
        $this->get(PackageResource::getUrl('edit', ['record' => $this->package]))->assertSuccessful();
    }

    public function test_order_view_page_shows_decrypted_data(): void
    {
        $order = Orders::query()->firstOrFail();

        $this->get(OrdersResource::getUrl('view', ['record' => $order]))
            ->assertSuccessful()
            ->assertSee('Иван')
            ->assertSee('Нова Пошта');
    }

    public function test_product_can_be_created_through_form(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Новый товар',
                'articule' => 'ART-NEW',
                'price' => '350',
                'discount' => 5,
                'availability' => 'in_stock',
                'condition_item' => 'new',
                'characteristics' => [['name' => 'Цвет', 'value' => 'Черный', 'unit' => null]],
                'categories' => [$this->category->id],
                'catalogs' => [$this->catalog->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Product::query()->where('articule', 'ART-NEW')->firstOrFail();

        $this->assertSame('Новый товар', $created->name);
        $this->assertSame(
            [['name' => 'Цвет', 'value' => 'Черный', 'unit' => null]],
            $created->characteristicsList(),
        );
        $this->assertNotEmpty($created->url);
        $this->assertEqualsCanonicalizing(
            [$this->category->id],
            $created->categories->pluck('id')->all(),
        );
    }

    public function test_wholesale_price_is_required_when_wholesale_enabled(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Оптовый товар',
                'price' => '100',
                'availability' => 'in_stock',
                'is_wholesale' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['wholesale_price']);
    }

    public function test_product_can_be_edited_through_form(): void
    {
        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->fillForm([
                'name' => 'Тарелка серая',
                'availability' => 'out_of_stock',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->product->refresh();

        $this->assertSame('Тарелка серая', $this->product->name);
        $this->assertSame('out_of_stock', $this->product->availability);
    }

    public function test_bulk_availability_action_updates_products(): void
    {
        Livewire::test(ListProducts::class)
            ->callTableBulkAction('setAvailability', [$this->product], [
                'availability' => 'out_of_stock',
            ]);

        $this->assertSame('out_of_stock', $this->product->refresh()->availability);
    }

    public function test_bulk_discount_action_updates_products(): void
    {
        Livewire::test(ListProducts::class)
            ->callTableBulkAction('setDiscount', [$this->product], ['discount' => 25]);

        $this->assertSame(25, $this->product->refresh()->discount);
    }

    public function test_product_form_saves_characteristics_with_units_and_multiple_values(): void
    {
        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->fillForm([
                'characteristics' => [
                    ['name' => 'Вага', 'value' => '2', 'unit' => 'г'],
                    ['name' => 'Тип заряду', 'value' => 'Від мережі|USB', 'unit' => null],
                    ['name' => '', 'value' => '', 'unit' => null],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            [
                ['name' => 'Вага', 'value' => '2', 'unit' => 'г'],
                ['name' => 'Тип заряду', 'value' => 'Від мережі|USB', 'unit' => null],
            ],
            $this->product->refresh()->characteristicsList(),
            'Пустые строки репитера не должны сохраняться.',
        );
    }

    public function test_legacy_characteristics_are_shown_in_the_form_as_rows(): void
    {
        // В базе остались товары со старым плоским форматом.
        Product::withoutEvents(fn () => Product::query()
            ->whereKey($this->product->getKey())
            ->update(['characteristics' => json_encode(['Матеріал' => 'Пластик'])]));

        Livewire::test(EditProduct::class, ['record' => $this->product->getKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'characteristics' => [
                    ['name' => 'Матеріал', 'value' => 'Пластик', 'unit' => null],
                ],
            ]);
    }

    public function test_export_action_downloads_a_file(): void
    {
        Livewire::test(ListProducts::class)
            ->callAction('exportProducts', [
                'format' => 'csv',
                'only_filtered' => true,
                'fields' => ['name', 'price', 'characteristics'],
            ])
            ->assertFileDownloaded();
    }

    public function test_template_action_downloads_a_file(): void
    {
        Livewire::test(ListProducts::class)
            ->callAction('downloadProductTemplate', [
                'format' => 'xlsx',
                'fields' => ['name', 'price'],
            ])
            ->assertFileDownloaded();
    }

    public function test_import_and_export_actions_are_available_on_the_list_page(): void
    {
        Livewire::test(ListProducts::class)
            ->assertActionVisible('importProducts')
            ->assertActionVisible('exportProducts')
            ->assertActionVisible('downloadProductTemplate');
    }

    public function test_template_characteristics_are_normalized_for_the_form(): void
    {
        Livewire::test(EditTemplate::class, ['record' => $this->template->getKey()])
            ->assertSuccessful()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(
            [
                ['name' => 'Цвет', 'key' => 'tsvet', 'default_value' => null],
                ['name' => 'Материал', 'key' => 'materyal', 'default_value' => null],
            ],
            array_values($this->template->refresh()->characteristics),
        );
    }

    public function test_panel_uses_russian_locale_without_touching_storefront(): void
    {
        $this->get(ProductResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Поиск');

        $this->assertSame(config('app.locale'), app()->getLocale());
    }

    public function test_panel_access_is_restricted_by_admin_emails(): void
    {
        config()->set('admin.emails', ['owner@example.test']);

        $outsider = User::factory()->create(['email' => 'someone@example.test']);
        $this->assertFalse($outsider->canAccessPanel(filament()->getPanel('admin')));

        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $this->assertTrue($owner->canAccessPanel(filament()->getPanel('admin')));
    }
}
