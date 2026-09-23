<?php

namespace App\Jobs;

use App\Models\ProductImport;
use App\Services\Products\ProductImportChunker;
use App\Services\Products\ProductImporter;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Импортирует одну порцию товаров и добавляет её итоги к задаче импорта.
 *
 * Порция самодостаточна (заголовки + до chunk_size строк), поэтому задачи
 * можно обрабатывать в любом порядке и несколькими воркерами.
 */
class ImportProductChunkJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public int $timeout = 900;

    /** @var array<int, int> */
    public array $backoff = [10, 30];

    public function __construct(
        public int $importId,
        public string $chunkPath,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $import = ProductImport::find($this->importId);

        if ($import === null || $import->isFinished()) {
            return;
        }

        // Порции нет — значит её уже обработали (например, при повторном запуске).
        if (! is_file($this->chunkPath)) {
            return;
        }

        $import->markProcessing();

        $result = (new ProductImporter(array_merge($import->options ?? [], [
            'csv_delimiter' => ProductImportChunker::DELIMITER,
        ])))->import($this->chunkPath, 'csv');

        $import->applyChunkResult($result);

        @unlink($this->chunkPath);
    }

    public function failed(?Throwable $exception): void
    {
        ProductImport::find($this->importId)?->addProblem(sprintf(
            'Порция %s не обработана: %s',
            basename($this->chunkPath),
            $exception?->getMessage() ?? 'неизвестная ошибка',
        ));
    }
}
