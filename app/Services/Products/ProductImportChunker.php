<?php

namespace App\Services\Products;

use RuntimeException;

/**
 * Режет выгрузку товаров на небольшие CSV-порции.
 *
 * Каждая порция — самостоятельный файл: строка заголовков плюс не больше
 * chunkSize строк товаров. Так очередь обрабатывает большой файл частями,
 * не держа его целиком в памяти и не упираясь в таймаут воркера.
 */
class ProductImportChunker
{
    public const DELIMITER = ',';

    private int $chunkSize;

    public function __construct(int $chunkSize = 50)
    {
        $this->chunkSize = max(1, $chunkSize);
    }

    /**
     * @param  string|null  $extension  Расширение исходного файла: у временных
     *                                  файлов загрузки его в пути может не быть.
     * @return array{
     *     rows: int,
     *     chunks: array<int, string>,
     *     recognizedFields: array<int, string>,
     *     characteristicSlots: int,
     *     unknownHeaders: array<int, string>
     * }
     */
    public function split(string $sourcePath, string $targetDirectory, ?string $extension = null): array
    {
        $rows = (new ProductFileReader)->rows($sourcePath, $extension);

        if (! $rows->valid()) {
            throw new RuntimeException('Файл пустой: не удалось прочитать строку заголовков.');
        }

        $headerRow = $rows->current();
        $header = ProductImporter::analyseHeader($headerRow);

        if ($header['columns'] === [] && $header['characteristicSlots'] === []) {
            throw new RuntimeException('Не удалось распознать ни одной колонки. Скачайте шаблон и заполните его.');
        }

        if (! is_dir($targetDirectory) && ! @mkdir($targetDirectory, 0755, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException("Не удалось создать каталог для порций: {$targetDirectory}");
        }

        $chunks = [];
        $totalRows = 0;
        $rowsInChunk = 0;
        $handle = null;

        try {
            for ($rows->next(); $rows->valid(); $rows->next()) {
                $row = $rows->current();

                if (ProductFileReader::isEmptyRow($row)) {
                    continue;
                }

                if ($handle === null) {
                    $path = $targetDirectory.DIRECTORY_SEPARATOR.sprintf('chunk-%04d.csv', count($chunks) + 1);
                    $handle = $this->openChunk($path, $headerRow);
                    $chunks[] = $path;
                    $rowsInChunk = 0;
                }

                fputcsv($handle, $row, self::DELIMITER);
                $totalRows++;
                $rowsInChunk++;

                if ($rowsInChunk >= $this->chunkSize) {
                    fclose($handle);
                    $handle = null;
                }
            }
        } finally {
            if ($handle !== null) {
                fclose($handle);
            }
        }

        if ($totalRows === 0) {
            throw new RuntimeException('В файле нет ни одной строки с товаром.');
        }

        return [
            'rows' => $totalRows,
            'chunks' => $chunks,
            'recognizedFields' => array_keys($header['columns']),
            'characteristicSlots' => count($header['characteristicSlots']),
            'unknownHeaders' => $header['unknownHeaders'],
        ];
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return resource
     */
    private function openChunk(string $path, array $headerRow)
    {
        $handle = @fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Не удалось создать файл порции: {$path}");
        }

        fputcsv($handle, $headerRow, self::DELIMITER);

        return $handle;
    }
}
