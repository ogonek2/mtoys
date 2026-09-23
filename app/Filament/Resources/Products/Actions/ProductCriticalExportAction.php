<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
use App\Services\Products\ProductDataQuality;
use App\Services\Products\ProductExporter;
use App\Services\Products\ProductFieldMap;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Выгрузка товаров с пробелами для правки в Excel и обратного импорта по ID.
 */
class ProductCriticalExportAction extends Action
{
    private const CSV_DELIMITER = ';';

    /** @var array<int, string> */
    private const CRITICAL_BLANKABLE = [
        'price',
        'categories',
        'image_path',
        'images',
        'description',
        'articule',
        'brand',
        'characteristics',
    ];

    public static function getDefaultName(): ?string
    {
        return 'exportCriticalProducts';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Экспорт пробелов')
            ->icon(Heroicon::OutlinedWrenchScrewdriver)
            ->color('warning')
            ->modalHeading('Экспорт товаров для правки')
            ->modalDescription(new HtmlString(
                'Скачается таблица с <strong>ID</strong> и полями для заполнения. '
                .'Заполните пустые ячейки в Excel и снова загрузите файл через «Импорт» — товары обновятся по ID. '
                .'Пустые ячейки при импорте <em>не затирают</em> уже заполненные данные. '
                .'Для новых названий категорий включите «Создавать недостающие категории».'
            ))
            ->modalSubmitActionLabel('Скачать')
            ->modalWidth(Width::Large)
            ->schema([
                Select::make('scope')
                    ->label('Какие товары выгрузить')
                    ->options([
                        'incomplete' => 'Критичные (нет цены, фото или категории)',
                        'without_price' => 'Без цены',
                        'without_image' => 'Без фото / заглушка',
                        'without_category' => 'Без категории',
                        'without_description' => 'Без описания',
                        'without_articule' => 'Без артикула',
                        'without_brand' => 'Без бренда',
                        'without_characteristics' => 'Без характеристик',
                        'without_catalog' => 'Без каталога',
                        'filtered' => 'Текущая вкладка и фильтры списка',
                    ])
                    ->default('incomplete')
                    ->native(false)
                    ->required(),

                ProductFileFields::formatSelect(),

                Toggle::make('blank_filled_critical')
                    ->label('В критичных колонках оставлять пустым то, что уже заполнено')
                    ->default(false)
                    ->helperText('В файле останутся пустыми только дыры; ID и название всегда заполнены.'),

                ProductFileFields::fieldSelector(ProductFieldMap::criticalFixFields()),
            ])
            ->action(function (array $data, Component $livewire): BinaryFileResponse {
                $format = $data['format'] ?? ProductExporter::FORMAT_XLSX;
                $blankFilled = (bool) ($data['blank_filled_critical'] ?? false);
                $query = self::resolveQuery($livewire, (string) ($data['scope'] ?? 'incomplete'));

                $fields = array_values(array_unique(array_merge(['id', 'name'], $data['fields'] ?? [])));
                $exporter = new ProductExporter($fields, $format);

                $count = (clone $query)->count();
                $path = self::write($exporter, $query, $blankFilled);
                $name = 'probely-'.$count.'-'.now()->format('Y-m-d-Hi').'.'.$exporter->extension();

                return response()
                    ->download($path, $name)
                    ->deleteFileAfterSend();
            });
    }

    private static function resolveQuery(Component $livewire, string $scope): Builder
    {
        if ($scope === 'filtered' && method_exists($livewire, 'getTableQueryForExport')) {
            return $livewire->getTableQueryForExport();
        }

        return ProductDataQuality::apply(Product::query(), $scope);
    }

    private static function write(ProductExporter $exporter, Builder $query, bool $blankFilledCritical): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'products-critical-');
        $fields = $exporter->fieldKeys();
        $headers = array_merge($exporter->headers(), ['Пробелы']);
        $rows = self::rows($exporter, $query, $fields, $blankFilledCritical);

        return $exporter->extension() === 'xlsx'
            ? self::writeXlsx($path, $headers, $rows)
            : self::writeCsv($path, $headers, $rows);
    }

    /**
     * @param  array<int, string>  $fields
     * @return iterable<int, array<int, string>>
     */
    private static function rows(
        ProductExporter $exporter,
        Builder $query,
        array $fields,
        bool $blankFilledCritical,
    ): iterable {
        foreach ($query->clone()->with(['categories', 'images', 'catalogs'])->orderBy('id')->lazyById(200) as $product) {
            $cells = [];

            foreach ($fields as $field) {
                $cells[] = self::cellValue($exporter, $product, $field, $blankFilledCritical);
            }

            $cells[] = implode(', ', self::gapLabels($product));

            yield $cells;
        }
    }

    private static function cellValue(
        ProductExporter $exporter,
        Product $product,
        string $field,
        bool $blankFilledCritical,
    ): string {
        if (
            $blankFilledCritical
            && in_array($field, self::CRITICAL_BLANKABLE, true)
            && ! self::fieldIsMissing($product, $field)
        ) {
            return '';
        }

        if ($field === 'price' && self::fieldIsMissing($product, 'price')) {
            return '';
        }

        if (in_array($field, ['image_path', 'images'], true) && self::fieldIsMissing($product, 'image_path')) {
            return '';
        }

        return $exporter->value($product, $field);
    }

    private static function fieldIsMissing(Product $product, string $field): bool
    {
        return match ($field) {
            'price' => $product->price === null || $product->price === '' || (float) $product->price <= 0,
            'image_path', 'images' => self::imageMissing($product),
            'categories' => $product->categories->isEmpty(),
            'description' => trim((string) ($product->description ?? '')) === '',
            'articule' => trim((string) ($product->articule ?? '')) === '',
            'brand' => trim((string) ($product->brand ?? '')) === '',
            'characteristics' => $product->characteristicsList() === [],
            default => false,
        };
    }

    private static function imageMissing(Product $product): bool
    {
        $image = trim((string) ($product->image_path ?? ''));

        return $image === '' || str_contains(mb_strtolower($image), 'no-image');
    }

    /**
     * @return array<int, string>
     */
    private static function gapLabels(Product $product): array
    {
        $map = [
            'price' => 'цена',
            'image_path' => 'фото',
            'categories' => 'категория',
            'description' => 'описание',
            'articule' => 'артикул',
            'brand' => 'бренд',
            'characteristics' => 'характеристики',
        ];

        $labels = [];
        foreach ($map as $field => $label) {
            if (self::fieldIsMissing($product, $field)) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string>>  $rows
     */
    private static function writeCsv(string $path, array $headers, iterable $rows): string
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Не удалось создать файл экспорта.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, self::CSV_DELIMITER);
        foreach ($rows as $row) {
            fputcsv($handle, $row, self::CSV_DELIMITER);
        }
        fclose($handle);

        return $path;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string>>  $rows
     */
    private static function writeXlsx(string $path, array $headers, iterable $rows): string
    {
        $writer = new XlsxWriter;
        $writer->openToFile($path);
        $headerStyle = (new Style)->setFontBold();
        $writer->addRow(Row::fromValues($headers, $headerStyle));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }
}
