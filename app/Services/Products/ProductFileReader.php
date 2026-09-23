<?php

namespace App\Services\Products;

use DateTimeInterface;
use Generator;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Чтение строк выгрузки товаров из CSV или XLSX.
 *
 * Заголовки в файлах Prom.ua дублируются (24 тройки колонок характеристик),
 * поэтому строки отдаются массивами по позициям колонок, без ключей.
 */
class ProductFileReader
{
    /**
     * @param  string|null  $extension  Расширение исходного файла: у временных
     *                                  файлов загрузки его в пути может не быть.
     * @param  string|null  $delimiter  Разделитель CSV; если не задан — определяем сами.
     * @return Generator<int, array<int, string>>
     */
    public function rows(string $path, ?string $extension = null, ?string $delimiter = null): Generator
    {
        if (strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION)) === 'xlsx') {
            yield from $this->xlsxRows($path);

            return;
        }

        yield from $this->csvRows($path, $delimiter);
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    private function csvRows(string $path, ?string $delimiter = null): Generator
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Не удалось открыть файл: {$path}");
        }

        $delimiter ??= $this->detectDelimiter($path);

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $row !== null) {
                yield array_map(function ($value): string {
                    $value = (string) $value;

                    // Файлы, сохранённые Excel на Windows, приходят в CP1251.
                    if (! mb_check_encoding($value, 'UTF-8')) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1251');
                    }

                    return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
                }, $row);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    private function xlsxRows(string $path): Generator
    {
        $reader = new XlsxReader;
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    yield array_map(
                        fn ($value): string => $this->stringifyCell($value),
                        $row->toArray(),
                    );
                }

                // Данные берём только с первого листа.
                break;
            }
        } finally {
            $reader->close();
        }
    }

    private function stringifyCell(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d.m.Y');
        }

        if (is_float($value) && $value === floor($value) && abs($value) < PHP_INT_MAX) {
            return (string) (int) $value;
        }

        if (is_bool($value)) {
            return $value ? 'да' : 'нет';
        }

        return (string) $value;
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'rb');
        $line = $handle === false ? '' : (string) fgets($handle, 8192);

        if ($handle !== false) {
            fclose($handle);
        }

        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    /**
     * @param  array<int, string>  $row
     */
    public static function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
