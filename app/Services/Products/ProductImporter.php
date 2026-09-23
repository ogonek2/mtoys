<?php

namespace App\Services\Products;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Product;
use App\Models\productImage;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Импорт товаров из CSV или XLSX.
 *
 * Читает как выгрузку Prom.ua (где характеристики лежат в 24 повторяющихся
 * тройках колонок с одинаковыми заголовками), так и наш шаблон, где выбранные
 * поля идут по одной колонке. Колонки, которых в файле нет, не трогаются —
 * поэтому можно загрузить файл только с ценами и не потерять остальное.
 */
class ProductImporter
{
    /** Служебные группы Prom.ua, которые не являются настоящей категорией. */
    private const ROOT_GROUPS = ['корневая группа', 'коренева група'];

    private const AVAILABILITY_OUT_OF_STOCK = ['-', '0', 'нет', 'немає', 'нема', 'ні', 'no', 'false', 'out_of_stock', 'нет в наличии', 'немає в наявності'];

    private const TRUE_VALUES = ['1', 'да', 'так', 'yes', 'true', '+', 'y'];

    /** @var array<string, mixed> */
    private array $options;

    private ProductImportResult $result;

    /** @var array<string, int|null>|null */
    private ?array $categoryCache = null;

    /** @var array<string, int>|null */
    private ?array $catalogCache = null;

    /** @var array<int, string> */
    private array $seenArticules = [];

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'update_existing' => true,
            'create_missing_categories' => false,
            'default_category_id' => null,
            'import_images' => true,
            'csv_delimiter' => null,
        ], $options);
    }

    /**
     * @param  string|null  $extension  Расширение исходного файла: у временных
     *                                  файлов загрузки его в пути может не быть.
     */
    public function import(string $path, ?string $extension = null): ProductImportResult
    {
        $this->result = new ProductImportResult;

        $rows = (new ProductFileReader)->rows($path, $extension, $this->options['csv_delimiter']);

        if (! $rows->valid()) {
            throw new RuntimeException('Файл пустой: не удалось прочитать строку заголовков.');
        }

        $header = self::analyseHeader($rows->current());

        if ($header['columns'] === [] && $header['characteristicSlots'] === []) {
            throw new RuntimeException('Не удалось распознать ни одной колонки. Скачайте шаблон и заполните его.');
        }

        $this->result->recognizedFields = array_keys($header['columns']);
        $this->result->characteristicSlots = count($header['characteristicSlots']);
        $this->result->unknownHeaders = $header['unknownHeaders'];

        for ($rows->next(); $rows->valid(); $rows->next()) {
            $row = $rows->current();

            if (ProductFileReader::isEmptyRow($row)) {
                continue;
            }

            $this->result->rows++;
            $this->importRow($row, $header['columns'], $header['characteristicSlots']);
        }

        return $this->result;
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<string, int>  $columns
     * @param  array<int, array<string, int>>  $characteristicSlots
     */
    private function importRow(array $row, array $columns, array $characteristicSlots): void
    {
        $values = [];

        foreach ($columns as $field => $position) {
            $values[$field] = trim((string) ($row[$position] ?? ''));
        }

        $product = $this->findProduct($values);

        if ($product !== null && ! $this->options['update_existing']) {
            $this->result->skipped++;

            return;
        }

        $isNew = $product === null;
        $name = ($values['name'] ?? '') !== '' ? $values['name'] : ($values['name_ru'] ?? '');

        if ($isNew && $name === '') {
            $this->result->skipped++;
            $this->result->addProblem('Строки без названия пропущены — товар нельзя создать без названия.');

            return;
        }

        $product ??= new Product;

        $this->fillAttributes($product, $values, $columns);

        if ($isNew && ($product->name === null || $product->name === '')) {
            $product->name = $name;
        }

        $characteristics = $this->resolveCharacteristics($row, $columns, $characteristicSlots);

        if ($characteristics !== null) {
            $product->characteristics = $characteristics;
        }

        $product->save();

        $isNew ? $this->result->created++ : $this->result->updated++;

        $this->noteDuplicateArticule($values['articule'] ?? '');

        if (array_key_exists('categories', $columns)) {
            $this->syncCategories($product, $values['categories'] ?? '');
        } elseif ($isNew && $this->options['default_category_id']) {
            $product->categories()->syncWithoutDetaching([$this->options['default_category_id']]);
        }

        if (array_key_exists('catalogs', $columns)) {
            $this->syncCatalogs($product, $values['catalogs'] ?? '');
        }

        if ($this->options['import_images'] && array_key_exists('images', $columns)) {
            $this->syncImages($product, $values['images'] ?? '');
        }
    }

    /**
     * Товар ищем по ID из нашего экспорта, затем по внешнему идентификатору и
     * артикулу — так повторный импорт того же файла обновляет товары, а не
     * плодит дубликаты.
     *
     * @param  array<string, string>  $values
     */
    private function findProduct(array $values): ?Product
    {
        if (($values['id'] ?? '') !== '') {
            $product = Product::query()->find((int) $values['id']);

            if ($product !== null) {
                return $product;
            }
        }

        if (($values['external_id'] ?? '') !== '') {
            // Внешний идентификатор уникален в системе-источнике: если он не
            // совпал, это новый товар, даже когда артикул повторяется.
            return Product::query()->where('external_id', $values['external_id'])->first();
        }

        if (($values['articule'] ?? '') !== '') {
            return Product::query()->where('articule', $values['articule'])->first();
        }

        return null;
    }

    /**
     * В выгрузках Prom.ua артикул иногда повторяется у разных товаров. Это не
     * ошибка импорта, но об этом стоит сказать: по такому артикулу товар потом
     * не найти однозначно.
     */
    private function noteDuplicateArticule(string $articule): void
    {
        if ($articule === '') {
            return;
        }

        if (in_array($articule, $this->seenArticules, true)) {
            $this->result->addProblem("Артикул «{$articule}» в файле встречается у нескольких товаров — они загружены как отдельные позиции.");

            return;
        }

        $this->seenArticules[] = $articule;
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, int>  $columns
     */
    private function fillAttributes(Product $product, array $values, array $columns): void
    {
        foreach ($values as $field => $raw) {
            $type = ProductFieldMap::type($field);

            if (in_array($type, [ProductFieldMap::TYPE_IMAGES, ProductFieldMap::TYPE_CATEGORIES, ProductFieldMap::TYPE_CATALOGS, ProductFieldMap::TYPE_CHARACTERISTICS], true)) {
                continue;
            }

            if (! in_array($field, $product->getFillable(), true)) {
                continue;
            }

            $value = $this->castValue($type, $raw);

            // Пустая ячейка ничего не затирает: файл может содержать только часть полей.
            if ($value === null) {
                continue;
            }

            $product->{$field} = $value;
        }

        if (array_key_exists('images', $columns) && ! array_key_exists('image_path', $columns)) {
            $main = $this->splitImageUrls($values['images'] ?? '')[0] ?? null;

            if ($main !== null) {
                $product->image_path = $main;
            }
        }

        // В выгрузках Prom.ua нет флага «оптовый товар» — выводим его из цены.
        if (! array_key_exists('is_wholesale', $columns) && (float) ($product->wholesale_price ?? 0) > 0) {
            $product->is_wholesale = true;
        }
    }

    private function castValue(string $type, string $raw): string|int|float|bool|null
    {
        if ($raw === '') {
            return null;
        }

        return match ($type) {
            ProductFieldMap::TYPE_INT => $this->parseInt($raw),
            ProductFieldMap::TYPE_DECIMAL => $this->parseDecimal($raw),
            ProductFieldMap::TYPE_BOOL => in_array(mb_strtolower($raw), self::TRUE_VALUES, true),
            ProductFieldMap::TYPE_DATE => $this->parseDate($raw),
            ProductFieldMap::TYPE_AVAILABILITY => in_array(mb_strtolower($raw), self::AVAILABILITY_OUT_OF_STOCK, true)
                ? 'out_of_stock'
                : 'in_stock',
            ProductFieldMap::TYPE_CONDITION => $this->parseCondition($raw),
            default => $raw,
        };
    }

    /**
     * Prom.ua умеет несколько оптовых ступеней в одной ячейке («150,00000;120»)
     * и запятую как десятичный разделитель. Берём первую ступень.
     */
    private function parseDecimal(string $raw): ?float
    {
        $first = trim(explode(';', $raw)[0]);
        $first = str_replace([' ', "\u{a0}", '%'], '', $first);
        $first = str_replace(',', '.', $first);

        return is_numeric($first) ? (float) $first : null;
    }

    private function parseInt(string $raw): ?int
    {
        $value = $this->parseDecimal($raw);

        return $value === null ? null : (int) round($value);
    }

    private function parseDate(string $raw): ?string
    {
        foreach (['d.m.Y', 'Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($raw));
            } catch (InvalidFormatException) {
                continue;
            }

            if ($date !== false && $date->format($format) === trim($raw)) {
                return $date->format('Y-m-d');
            }
        }

        $this->result->addProblem("Не удалось разобрать дату «{$raw}» — ожидается формат 31.12.2026.");

        return null;
    }

    private function parseCondition(string $raw): string
    {
        $value = mb_strtolower($raw);

        return match (true) {
            str_contains($value, 'б/у') || str_contains($value, 'вжив') || str_contains($value, 'used') => 'used',
            str_contains($value, 'віднов') || str_contains($value, 'восстан') || str_contains($value, 'refurb') => 'refurbished',
            default => 'new',
        };
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<string, int>  $columns
     * @param  array<int, array<string, int>>  $slots
     * @return array<int, array{name: string, value: string, unit: string|null}>|null
     */
    private function resolveCharacteristics(array $row, array $columns, array $slots): ?array
    {
        if ($slots !== []) {
            $characteristics = [];

            foreach ($slots as $slot) {
                $name = trim((string) ($row[$slot['name']] ?? ''));
                $value = trim((string) ($row[$slot['value'] ?? -1] ?? ''));
                $unit = trim((string) ($row[$slot['unit'] ?? -1] ?? ''));

                if ($name === '' || $value === '') {
                    continue;
                }

                $characteristics[] = [
                    'name' => $name,
                    'value' => $value,
                    'unit' => $unit === '' ? null : $unit,
                ];
            }

            return $characteristics;
        }

        if (! array_key_exists('characteristics', $columns)) {
            return null;
        }

        $raw = trim((string) ($row[$columns['characteristics']] ?? ''));

        return $raw === '' ? [] : $this->parseCharacteristicsString($raw);
    }

    /**
     * Разбирает строку вида «Колір=Білий; Вага=2 г; Тип=Від мережі|USB».
     *
     * @return array<int, array{name: string, value: string, unit: string|null}>
     */
    private function parseCharacteristicsString(string $raw): array
    {
        $characteristics = [];

        foreach (preg_split('/\s*;\s*/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $pair) {
            $parts = explode('=', $pair, 2);

            if (count($parts) !== 2) {
                $this->result->addProblem("Характеристика «{$pair}» пропущена: ожидается формат «Название=Значение».");

                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);

            if ($name === '' || $value === '') {
                continue;
            }

            $characteristics[] = ['name' => $name, 'value' => $value, 'unit' => null];
        }

        return $characteristics;
    }

    private function syncCategories(Product $product, string $raw): void
    {
        $ids = [];

        foreach ($this->splitList($raw) as $name) {
            if (in_array(mb_strtolower($name), self::ROOT_GROUPS, true)) {
                continue;
            }

            $id = $this->resolveCategoryId($name);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        if ($ids === [] && $this->options['default_category_id']) {
            $ids[] = (int) $this->options['default_category_id'];
        }

        if ($ids !== []) {
            $product->categories()->sync($ids);
        }
    }

    private function resolveCategoryId(string $name): ?int
    {
        $this->categoryCache ??= $this->loadNameMap(Category::query());
        $key = mb_strtolower($name);

        if (array_key_exists($key, $this->categoryCache)) {
            return $this->categoryCache[$key];
        }

        if ($this->options['create_missing_categories']) {
            $category = Category::query()->create(['name' => $name]);
            $this->result->categoriesCreated++;

            return $this->categoryCache[$key] = $category->id;
        }

        $this->result->addProblem("Категория «{$name}» не найдена — товар привязан к категории по умолчанию или остался без категории.");

        return $this->categoryCache[$key] = null;
    }

    private function syncCatalogs(Product $product, string $raw): void
    {
        $this->catalogCache ??= $this->loadNameMap(Catalog::query());
        $ids = [];

        foreach ($this->splitList($raw) as $name) {
            $id = $this->catalogCache[mb_strtolower($name)] ?? null;

            if ($id === null) {
                $this->result->addProblem("Каталог «{$name}» не найден и не был создан.");

                continue;
            }

            $ids[] = $id;
        }

        if ($ids !== []) {
            $product->catalogs()->sync($ids);
        }
    }

    /**
     * Названия сопоставляем в PHP, а не через LOWER() в SQL: у разных СУБД
     * разное поведение с регистром кириллицы.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array<string, int>
     */
    private function loadNameMap($query): array
    {
        $map = [];

        foreach ($query->get(['id', 'name']) as $record) {
            $map[mb_strtolower(trim((string) $record->name))] = (int) $record->id;
        }

        return $map;
    }

    /**
     * Главное изображение уже выбрано при заполнении полей, здесь остаётся
     * галерея. Повторный импорт того же файла не плодит дубликаты.
     */
    private function syncImages(Product $product, string $raw): void
    {
        $existing = productImage::query()->where('product_id', $product->id)->pluck('src')->all();

        foreach ($this->splitImageUrls($raw) as $url) {
            if ($url === $product->image_path || in_array($url, $existing, true)) {
                continue;
            }

            productImage::query()->create(['src' => $url, 'product_id' => $product->id]);
            $existing[] = $url;
            $this->result->imagesCreated++;
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitImageUrls(string $raw): array
    {
        $urls = preg_split('/[\s,]+/u', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $urls,
            static fn (string $url): bool => str_starts_with($url, 'http://') || str_starts_with($url, 'https://'),
        )));
    }

    /**
     * @return array<int, string>
     */
    private function splitList(string $raw): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/\s*[|;]\s*/u', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [],
        )));
    }

    /**
     * Сопоставляет заголовки файла с полями товара и собирает тройки
     * характеристик Prom.ua (название, единица измерения, значение).
     *
     * @param  array<int, string>  $header
     * @return array{columns: array<string, int>, characteristicSlots: array<int, array<string, int>>, unknownHeaders: array<int, string>}
     */
    public static function analyseHeader(array $header): array
    {
        $unknownHeaders = [];
        $columns = [];
        $slots = [];

        $promRoles = [
            ProductFieldMap::normalizeHeader(ProductFieldMap::PROM_CHARACTERISTIC_NAME) => 'name',
            ProductFieldMap::normalizeHeader(ProductFieldMap::PROM_CHARACTERISTIC_UNIT) => 'unit',
            ProductFieldMap::normalizeHeader(ProductFieldMap::PROM_CHARACTERISTIC_VALUE) => 'value',
        ];

        foreach ($header as $position => $title) {
            $title = trim((string) $title);

            if ($title === '') {
                continue;
            }

            $promRole = $promRoles[ProductFieldMap::normalizeHeader($title)] ?? null;

            if ($promRole !== null) {
                if ($promRole === 'name') {
                    $slots[] = ['name' => $position];

                    continue;
                }

                for ($i = count($slots) - 1; $i >= 0; $i--) {
                    if (! array_key_exists($promRole, $slots[$i])) {
                        $slots[$i][$promRole] = $position;

                        break;
                    }
                }

                continue;
            }

            $field = ProductFieldMap::resolveField($title);

            if ($field === null) {
                $unknownHeaders[] = $title;

                continue;
            }

            // При дублях заголовков берём первую колонку.
            $columns[$field] ??= $position;
        }

        $slots = array_values(array_filter(
            $slots,
            static fn (array $slot): bool => array_key_exists('value', $slot),
        ));

        return [
            'columns' => $columns,
            'characteristicSlots' => $slots,
            'unknownHeaders' => array_values(array_unique($unknownHeaders)),
        ];
    }
}
