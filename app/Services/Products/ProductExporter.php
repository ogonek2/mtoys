<?php

namespace App\Services\Products;

use App\Filament\Support\ShopOptions;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;

/**
 * Экспорт товаров и генерация шаблона для заполнения.
 *
 * Набор колонок задаёт пользователь, поэтому один и тот же класс отдаёт и
 * полный экспорт, и пустой шаблон под нужные поля. Файл, выгруженный отсюда,
 * читается обратно через ProductCsvImporter.
 */
class ProductExporter
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';

    /** Excel в русской локали ждёт точку с запятой, значения при этом закавычиваются. */
    private const CSV_DELIMITER = ';';

    /** @var array<int, string> */
    private array $fields;

    /**
     * @param  array<int, string>  $fields
     */
    public function __construct(array $fields, private string $format = self::FORMAT_CSV)
    {
        // Порядок колонок всегда как в справочнике, а не как пришло из формы.
        $this->fields = array_values(array_filter(
            ProductFieldMap::keys(),
            static fn (string $key): bool => in_array($key, $fields, true),
        ));

        if ($this->fields === []) {
            throw new RuntimeException('Не выбрано ни одного поля для экспорта.');
        }
    }

    /**
     * @return array<int, string>
     */
    public function fieldKeys(): array
    {
        return $this->fields;
    }

    public function extension(): string
    {
        return $this->format === self::FORMAT_XLSX ? 'xlsx' : 'csv';
    }

    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return array_map(
            static fn (string $field): string => ProductFieldMap::label($field),
            $this->fields,
        );
    }

    /**
     * Пишет файл на диск и возвращает путь к нему.
     */
    public function write(string $path, Builder $query): string
    {
        return $this->writeRows($path, $this->rows($query));
    }

    /**
     * Шаблон для заполнения: заголовки и одна строка-пример, которую видно
     * как образец формата и которую не жалко удалить.
     */
    public function writeTemplate(string $path): string
    {
        return $this->writeRows($path, [array_map(
            static fn (string $field): string => ProductFieldMap::example($field),
            $this->fields,
        )]);
    }

    /**
     * @return iterable<int, array<int, string>>
     */
    private function rows(Builder $query): iterable
    {
        // lazyById (а не cursor): cursor держит unbuffered SELECT открытым,
        // и любые доп. запросы/sessions UPDATE падают с SQLSTATE 2014.
        foreach ($this->prepareQuery($query)->lazyById(200) as $product) {
            yield array_map(
                fn (string $field): string => $this->value($product, $field),
                $this->fields,
            );
        }
    }

    private function prepareQuery(Builder $query): Builder
    {
        $relations = array_values(array_filter([
            in_array('categories', $this->fields, true) ? 'categories' : null,
            in_array('catalogs', $this->fields, true) ? 'catalogs' : null,
            in_array('images', $this->fields, true) ? 'images' : null,
        ]));

        return $query->clone()->with($relations)->orderBy('id');
    }

    public function value(Product $product, string $field): string
    {
        return match ($field) {
            'availability' => ShopOptions::AVAILABILITY[$product->availability] ?? (string) $product->availability,
            'condition_item' => ShopOptions::CONDITION[$product->condition_item] ?? (string) $product->condition_item,
            'is_wholesale' => $product->is_wholesale ? 'да' : 'нет',
            'discount_starts_at', 'discount_ends_at' => $product->{$field}?->format('d.m.Y') ?? '',
            'images' => $this->imageList($product),
            'categories' => $product->categories->pluck('name')->implode(' | '),
            'catalogs' => $product->catalogs->pluck('name')->implode(' | '),
            'characteristics' => $this->characteristicsList($product),
            default => (string) ($product->{$field} ?? ''),
        };
    }

    private function imageList(Product $product): string
    {
        $urls = collect([$product->image_path])
            ->concat($product->images->pluck('src'))
            ->filter()
            ->unique();

        return $urls->implode(', ');
    }

    /**
     * «Колір=Білий; Вага=2 г» — читаемо в Excel и разбирается импортёром обратно.
     */
    private function characteristicsList(Product $product): string
    {
        return collect($product->characteristicsList())
            ->map(static fn (array $characteristic): string => $characteristic['unit'] === null
                ? "{$characteristic['name']}={$characteristic['value']}"
                : "{$characteristic['name']}={$characteristic['value']} {$characteristic['unit']}")
            ->implode('; ');
    }

    /**
     * @param  iterable<int, array<int, string>>  $rows
     */
    private function writeRows(string $path, iterable $rows): string
    {
        return $this->format === self::FORMAT_XLSX
            ? $this->writeXlsx($path, $rows)
            : $this->writeCsv($path, $rows);
    }

    /**
     * @param  iterable<int, array<int, string>>  $rows
     */
    private function writeCsv(string $path, iterable $rows): string
    {
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Не удалось создать файл: {$path}");
        }

        // BOM, иначе Excel на Windows покажет кириллицу как «РџСЂРёРІРµС‚».
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $this->headers(), self::CSV_DELIMITER);

        foreach ($rows as $row) {
            fputcsv($handle, $row, self::CSV_DELIMITER);
        }

        fclose($handle);

        return $path;
    }

    /**
     * @param  iterable<int, array<int, string>>  $rows
     */
    private function writeXlsx(string $path, iterable $rows): string
    {
        $writer = new XlsxWriter;
        $writer->openToFile($path);

        $header = new Style;
        $header->setFontBold();

        $writer->addRow(Row::fromValues($this->headers(), $header));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }
}
