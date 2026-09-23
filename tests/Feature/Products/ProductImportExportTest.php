<?php

namespace Tests\Feature\Products;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Product;
use App\Models\productImage;
use App\Services\Products\ProductDataQuality;
use App\Services\Products\ProductExporter;
use App\Services\Products\ProductFieldMap;
use App\Services\Products\ProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportExportTest extends TestCase
{
    use RefreshDatabase;

    /** Выгрузка Prom.ua: 4 показательные строки из реального файла. */
    private const FIXTURE = 'prom-products.csv';

    private function fixture(string $name): string
    {
        return base_path("tests/Fixtures/{$name}");
    }

    private function tempFile(string $extension): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'products-test-');

        return $path.'.'.$extension;
    }

    private function importFixture(array $options = []): \App\Services\Products\ProductImportResult
    {
        return (new ProductImporter($options))->import($this->fixture(self::FIXTURE));
    }

    private function writeCsv(string $path, array $rows): string
    {
        $handle = fopen($path, 'wb');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        fclose($handle);

        return $path;
    }

    public function test_prom_csv_import_fills_product_fields(): void
    {
        $result = $this->importFixture();

        $this->assertSame(4, $result->rows);
        $this->assertSame(4, $result->created);
        $this->assertSame(0, $result->updated);
        $this->assertSame(24, $result->characteristicSlots);

        $product = Product::query()->where('external_id', '1960198430')->firstOrFail();

        $this->assertSame('Ліхтарики  almina dl2424', $product->name);
        $this->assertSame('Фонарики almina dl2424', $product->name_ru);
        $this->assertSame('4024', $product->articule);
        $this->assertSame('650', (string) $product->price);
        $this->assertSame('Китай', $product->country);
        $this->assertSame('шт.', $product->unit_name);
        $this->assertSame('in_stock', $product->availability);
        $this->assertStringContainsString('Акумуляторний ліхтар', $product->description);
        $this->assertStringContainsString('ліхтар almina', $product->seo_keywords);
    }

    public function test_wholesale_flag_is_derived_from_wholesale_price(): void
    {
        $this->importFixture();

        $product = Product::query()->where('external_id', '1960198430')->firstOrFail();

        $this->assertTrue($product->is_wholesale);
        $this->assertSame('480.00', (string) $product->wholesale_price);
        $this->assertSame(120, $product->wholesale_min_quantity);
    }

    public function test_multi_tier_wholesale_price_takes_the_first_tier(): void
    {
        $this->importFixture();

        // В файле «150,00000;120» и «10.000;240.000» — несколько оптовых ступеней.
        $product = Product::query()->where('wholesale_min_quantity', 10)->firstOrFail();

        $this->assertSame('150.00', (string) $product->wholesale_price);
    }

    public function test_out_of_stock_marker_is_recognised(): void
    {
        $this->importFixture();

        $this->assertSame(2, Product::query()->where('availability', 'out_of_stock')->count());
        $this->assertSame(2, Product::query()->where('availability', 'in_stock')->count());
    }

    public function test_discount_and_its_dates_are_parsed(): void
    {
        $this->importFixture();

        $product = Product::query()->where('discount', '>', 0)->firstOrFail();

        $this->assertSame(40, $product->discount);
        $this->assertNotNull($product->discount_ends_at);
        $this->assertSame('2026', $product->discount_ends_at->format('Y'));
    }

    public function test_characteristics_keep_units_and_multiple_values(): void
    {
        $this->importFixture();

        $product = Product::query()->where('external_id', '1960198430')->firstOrFail();
        $characteristics = collect($product->characteristicsList())->keyBy('name');

        $this->assertSame(
            ['name' => 'Вага', 'value' => '2', 'unit' => 'г'],
            $characteristics['Вага'],
        );

        $this->assertSame('Від мережі|USB', $characteristics['Тип заряду']['value']);
        $this->assertNull($characteristics['Колір']['unit']);
    }

    public function test_storefront_glues_unit_to_value_and_joins_multiple_values(): void
    {
        $this->importFixture();

        $product = Product::query()->where('external_id', '1960198430')->firstOrFail();
        $display = collect($product->characteristicsForDisplay())->keyBy('name');

        $this->assertSame('2 г', $display['Вага']['value']);
        $this->assertSame('Від мережі, USB', $display['Тип заряду']['value']);
        $this->assertSame('Білий', $display['Колір']['value']);
    }

    public function test_legacy_flat_characteristics_are_still_displayed(): void
    {
        $product = Product::query()->create([
            'name' => 'Старый товар',
            'price' => '100',
            'characteristics' => ['Бренд' => 'DOMEXO', 'Колір' => 'Білий', 'Розмір' => '-'],
        ]);

        $display = collect($product->characteristicsForDisplay())->pluck('value', 'name');

        $this->assertSame('DOMEXO', $display['Бренд']);
        $this->assertSame('Білий', $display['Колір']);
        $this->assertArrayNotHasKey('Розмір', $display->all(), 'Заглушка «-» не должна попадать на витрину.');
    }

    public function test_images_are_split_into_main_and_gallery(): void
    {
        $this->importFixture();

        $product = Product::query()->where('external_id', '1960198430')->firstOrFail();

        $this->assertStringStartsWith('https://images.prom.ua/', $product->image_path);

        $gallery = productImage::query()->where('product_id', $product->id)->pluck('src');

        $this->assertGreaterThan(1, $gallery->count());
        $this->assertFalse($gallery->contains($product->image_path), 'Главное фото не должно дублироваться в галерее.');
    }

    public function test_existing_category_is_matched_by_name_ignoring_case(): void
    {
        Category::query()->create(['name' => 'РУЧНЫЕ И НАЛОБНЫЕ ФОНАРИ']);

        $this->importFixture();

        $product = Product::query()->where('external_id', '1960198430')->firstOrFail();

        $this->assertSame('РУЧНЫЕ И НАЛОБНЫЕ ФОНАРИ', $product->categories->first()?->name);
        $this->assertSame(1, Category::query()->count(), 'Существующая категория не должна дублироваться.');
    }

    public function test_missing_categories_are_created_only_when_asked(): void
    {
        $withoutCreating = $this->importFixture();

        $this->assertSame(0, Category::query()->count());
        $this->assertSame(0, $withoutCreating->categoriesCreated);
        $this->assertNotEmpty($withoutCreating->problems);

        Product::query()->delete();

        $withCreating = $this->importFixture(['create_missing_categories' => true]);

        // В фикстуре две настоящие группы: одна встречается дважды, ещё одна
        // строка лежит в служебной «Корневой группе».
        $this->assertSame(2, $withCreating->categoriesCreated);
        $this->assertSame(2, Category::query()->count());
    }

    public function test_prom_root_group_is_not_turned_into_a_category(): void
    {
        $this->importFixture(['create_missing_categories' => true]);

        $this->assertSame(0, Category::query()->where('name', 'Корневая группа')->count());
    }

    public function test_default_category_catches_products_without_a_group(): void
    {
        $fallback = Category::query()->create(['name' => 'Без категории']);

        $this->importFixture(['default_category_id' => $fallback->id]);

        $this->assertSame(4, $fallback->products()->count());
    }

    public function test_repeated_import_updates_products_instead_of_duplicating(): void
    {
        $this->importFixture();
        $second = $this->importFixture();

        $this->assertSame(4, Product::query()->count());
        $this->assertSame(0, $second->created);
        $this->assertSame(4, $second->updated);
        $this->assertSame(0, $second->imagesCreated, 'Повторный импорт не должен плодить фото.');
    }

    public function test_existing_products_can_be_left_untouched(): void
    {
        $this->importFixture();
        Product::query()->update(['price' => '1']);

        $result = $this->importFixture(['update_existing' => false]);

        $this->assertSame(4, $result->skipped);
        $this->assertSame(0, $result->updated);
        $this->assertSame('1', (string) Product::query()->first()->price);
    }

    public function test_import_touches_only_columns_present_in_the_file(): void
    {
        $this->importFixture();
        $before = Product::query()->where('articule', '4024')->firstOrFail();

        $path = $this->writeCsv($this->tempFile('csv'), [
            ['Артикул', 'Цена'],
            ['4024', '999'],
        ]);

        $result = (new ProductImporter)->import($path);

        $after = $before->fresh();

        $this->assertSame(1, $result->updated);
        $this->assertSame('999', (string) $after->price);
        $this->assertSame($before->name, $after->name);
        $this->assertSame($before->brand, $after->brand);
        $this->assertCount(
            count($before->characteristicsList()),
            $after->characteristicsList(),
            'Файл без колонки характеристик не должен их стирать.',
        );
    }

    public function test_export_contains_only_selected_columns(): void
    {
        $this->importFixture();

        $exporter = new ProductExporter(['name', 'price', 'availability']);
        $path = $exporter->write($this->tempFile('csv'), Product::query());

        $rows = array_map(
            static fn (string $line): array => str_getcsv($line, ';'),
            array_filter(explode("\n", str_replace("\r", '', file_get_contents($path)))),
        );

        // Порядок колонок задаёт справочник полей, а не порядок выбора.
        $this->assertSame(['Название', 'Наличие', 'Цена'], array_map(
            static fn (string $value): string => preg_replace('/^\xEF\xBB\xBF/', '', $value),
            $rows[0],
        ));

        $this->assertCount(5, $rows, 'Заголовок и четыре товара.');
        $this->assertContains($rows[1][1], ['В наличии', 'Нет в наличии']);
    }

    public function test_export_respects_the_query_it_is_given(): void
    {
        $this->importFixture();

        $exporter = new ProductExporter(['name']);
        $path = $exporter->write(
            $this->tempFile('csv'),
            Product::query()->where('availability', 'out_of_stock'),
        );

        $this->assertCount(3, array_filter(explode("\n", str_replace("\r", '', file_get_contents($path)))));
    }

    public function test_exported_file_can_be_imported_back(): void
    {
        $this->importFixture(['create_missing_categories' => true]);

        $fields = ['id', 'name', 'price', 'availability', 'brand', 'categories', 'images', 'characteristics'];
        $path = (new ProductExporter($fields))->write($this->tempFile('csv'), Product::query());

        $original = Product::query()->where('external_id', '1960198430')->firstOrFail();
        Product::query()->update(['price' => '1', 'brand' => null]);

        $result = (new ProductImporter)->import($path);

        $restored = $original->fresh();

        $this->assertSame(0, $result->created, 'Экспорт содержит ID — товары должны обновиться.');
        $this->assertSame(4, $result->updated);
        $this->assertSame((string) $original->price, (string) $restored->price);
        $this->assertSame($original->brand, $restored->brand);
        $this->assertSame($original->categories->pluck('name')->all(), $restored->categories->pluck('name')->all());
        $this->assertCount(count($original->characteristicsList()), $restored->characteristicsList());
    }

    public function test_template_has_selected_headers_and_one_example_row(): void
    {
        $exporter = new ProductExporter(['name', 'price', 'characteristics']);
        $path = $exporter->writeTemplate($this->tempFile('csv'));

        $rows = array_map(
            static fn (string $line): array => str_getcsv($line, ';'),
            array_filter(explode("\n", str_replace("\r", '', file_get_contents($path)))),
        );

        $this->assertCount(2, $rows);
        $this->assertSame('Характеристики', $rows[0][2]);
        $this->assertSame(ProductFieldMap::example('characteristics'), $rows[1][2]);
    }

    public function test_xlsx_template_can_be_filled_and_imported_back(): void
    {
        $exporter = new ProductExporter(
            ['name', 'price', 'availability', 'characteristics'],
            ProductExporter::FORMAT_XLSX,
        );

        $path = $exporter->writeTemplate($this->tempFile('xlsx'));

        $result = (new ProductImporter)->import($path, 'xlsx');

        $this->assertSame(1, $result->created);

        $product = Product::query()->firstOrFail();

        $this->assertSame(ProductFieldMap::example('name'), $product->name);
        $this->assertSame('in_stock', $product->availability);
        $this->assertSame('Білий', collect($product->characteristicsList())->firstWhere('name', 'Колір')['value']);
    }

    public function test_import_reports_unknown_columns(): void
    {
        $path = $this->writeCsv($this->tempFile('csv'), [
            ['Название', 'Цена', 'Товар_в_ProSale'],
            ['Тестовый товар', '10', 'Так'],
        ]);

        $result = (new ProductImporter)->import($path);

        $this->assertSame(1, $result->created);
        $this->assertSame(['Товар_в_ProSale'], $result->unknownHeaders);
    }

    public function test_import_skips_rows_without_a_name(): void
    {
        $path = $this->writeCsv($this->tempFile('csv'), [
            ['Название', 'Цена'],
            ['', '10'],
            ['Хороший товар', '20'],
        ]);

        $result = (new ProductImporter)->import($path);

        $this->assertSame(1, $result->created);
        $this->assertSame(1, $result->skipped);
        $this->assertNotEmpty($result->problems);
    }

    public function test_rows_with_the_same_articule_but_different_external_id_stay_separate(): void
    {
        $path = $this->writeCsv($this->tempFile('csv'), [
            ['Внешний ID', 'Артикул', 'Название', 'Цена'],
            ['1001', '23', 'Плита газовая на 3 конфорки', '900'],
            ['1002', '23', 'Плита газовая на 2 конфорки', '700'],
        ]);

        $result = (new ProductImporter)->import($path);

        $this->assertSame(2, $result->created);
        $this->assertSame(2, Product::query()->where('articule', '23')->count());
        $this->assertNotEmpty($result->problems, 'О повторяющемся артикуле нужно предупредить.');
    }

    public function test_rows_without_external_id_are_matched_by_articule(): void
    {
        Product::query()->create(['name' => 'Старое название', 'articule' => 'K-1', 'price' => '10']);

        $path = $this->writeCsv($this->tempFile('csv'), [
            ['Артикул', 'Цена'],
            ['K-1', '25'],
        ]);

        $result = (new ProductImporter)->import($path);

        $this->assertSame(1, $result->updated);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame('25', (string) Product::query()->first()->price);
    }

    public function test_catalogs_are_linked_when_they_exist(): void
    {
        $catalog = Catalog::query()->create(['name' => 'Туризм', 'url' => 'turizm']);

        $path = $this->writeCsv($this->tempFile('csv'), [
            ['Название', 'Цена', 'Каталоги'],
            ['Палатка', '1500', 'Туризм | Неизвестный каталог'],
        ]);

        $result = (new ProductImporter)->import($path);
        $product = Product::query()->firstOrFail();

        $this->assertSame([$catalog->id], $product->catalogs->pluck('id')->all());
        $this->assertNotEmpty($result->problems);
    }

    public function test_critical_fix_file_updates_product_by_id(): void
    {
        $category = Category::query()->create(['name' => 'Коврики']);
        $product = Product::query()->create([
            'name' => 'Малый коврик',
            'price' => 0,
            'image_path' => null,
            'articule' => 'MK-1',
        ]);

        $this->assertTrue(ProductDataQuality::incomplete()->whereKey($product->id)->exists());

        $exportPath = (new ProductExporter(ProductFieldMap::criticalFixFields()))
            ->write($this->tempFile('csv'), ProductDataQuality::incomplete());

        $rows = array_map(
            static fn (string $line): array => str_getcsv($line, ';'),
            array_values(array_filter(explode("\n", str_replace("\r", '', file_get_contents($exportPath))))),
        );

        $this->assertSame('ID', preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]));
        $this->assertContains('Цена', $rows[0]);
        $this->assertContains('Категории', $rows[0]);

        $fixPath = $this->writeCsv($this->tempFile('csv'), [
            ['ID', 'Название', 'Цена', 'Категории', 'Главное изображение'],
            [(string) $product->id, 'Малый коврик', '450', 'Коврики', 'https://cdn.example.com/rug.jpg'],
        ]);

        $result = (new ProductImporter(['update_existing' => true]))->import($fixPath);
        $product->refresh();

        $this->assertSame(1, $result->updated);
        $this->assertSame(0, $result->created);
        $this->assertSame('450', (string) $product->price);
        $this->assertSame('https://cdn.example.com/rug.jpg', $product->image_path);
        $this->assertSame([$category->id], $product->categories->pluck('id')->all());
        $this->assertFalse(ProductDataQuality::incomplete()->whereKey($product->id)->exists());
    }
}
