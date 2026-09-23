<?php

namespace App\Services\Products;

/**
 * Итог импорта: что создано, что обновлено и на что стоит посмотреть глазами.
 */
class ProductImportResult
{
    public int $rows = 0;

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $categoriesCreated = 0;

    public int $imagesCreated = 0;

    /** @var array<int, string> */
    public array $recognizedFields = [];

    /** @var array<int, string> */
    public array $unknownHeaders = [];

    /** @var array<int, string> */
    public array $problems = [];

    public int $characteristicSlots = 0;

    public function addProblem(string $message): void
    {
        // Одна и та же проблема на 200 строках не должна превращаться в простыню.
        if (count($this->problems) < 50 && ! in_array($message, $this->problems, true)) {
            $this->problems[] = $message;
        }
    }

    public function summary(): string
    {
        $parts = [
            "строк обработано: {$this->rows}",
            "создано: {$this->created}",
            "обновлено: {$this->updated}",
        ];

        if ($this->skipped > 0) {
            $parts[] = "пропущено: {$this->skipped}";
        }

        if ($this->categoriesCreated > 0) {
            $parts[] = "новых категорий: {$this->categoriesCreated}";
        }

        if ($this->imagesCreated > 0) {
            $parts[] = "изображений добавлено: {$this->imagesCreated}";
        }

        return implode(', ', $parts);
    }
}
