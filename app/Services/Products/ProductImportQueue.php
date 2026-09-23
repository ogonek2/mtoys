<?php

namespace App\Services\Products;

use App\Jobs\ImportProductChunkJob;
use App\Models\ProductImport;
use Illuminate\Support\Facades\Bus;
use Throwable;

/**
 * Ставит импорт товаров в очередь: режет файл на порции и раздаёт их
 * задачам пакета. Прогресс собирается в модели ProductImport, поэтому
 * ход выполнения виден в панели задач админки.
 */
class ProductImportQueue
{
    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_SERVER = 'server';

    public const SOURCE_CONSOLE = 'console';

    /**
     * @param  array<string, mixed>  $options  Настройки для ProductImporter.
     */
    public function dispatch(
        string $sourcePath,
        string $fileName,
        array $options = [],
        ?int $userId = null,
        string $source = self::SOURCE_UPLOAD,
        ?int $chunkSize = null,
    ): ProductImport {
        $chunkSize = max(1, $chunkSize ?: (int) config('admin.import.chunk_size', 50));

        $import = ProductImport::create([
            'user_id' => $userId,
            'file_name' => $fileName,
            'source' => $source,
            'status' => ProductImport::STATUS_QUEUED,
            'chunk_size' => $chunkSize,
            'options' => $options,
        ]);

        $directory = self::directoryFor($import);

        try {
            $split = (new ProductImportChunker($chunkSize))->split(
                $sourcePath,
                $directory,
                pathinfo($fileName, PATHINFO_EXTENSION) ?: null,
            );
        } catch (Throwable $exception) {
            $import->update(['directory' => $directory]);
            $import->finish(ProductImport::STATUS_FAILED, $exception->getMessage());

            throw $exception;
        }

        $import->update([
            'directory' => $directory,
            'chunks_total' => count($split['chunks']),
            'total_rows' => $split['rows'],
            'recognized_fields' => $split['recognizedFields'],
            'unknown_headers' => $split['unknownHeaders'],
        ]);

        $batch = Bus::batch(array_map(
            fn (string $chunkPath): ImportProductChunkJob => new ImportProductChunkJob($import->id, $chunkPath),
            $split['chunks'],
        ))
            // Имя несёт id задачи: колбэки пакета получают только Batch,
            // а batch_id к моменту их вызова может быть ещё не записан.
            ->name(self::batchName($import->id))
            ->allowFailures()
            ->catch([ProductImportFinalizer::class, 'failed'])
            ->finally([ProductImportFinalizer::class, 'finished']);

        if (($queue = config('admin.import.queue')) !== null) {
            $batch->onQueue($queue);
        }

        $dispatched = $batch->dispatch();

        $import->newQuery()->whereKey($import->getKey())->update(['batch_id' => $dispatched->id]);

        return $import->refresh();
    }

    public static function batchName(int $importId): string
    {
        return "product-import:{$importId}";
    }

    public static function importIdFromBatchName(?string $name): ?int
    {
        if ($name === null || ! str_starts_with($name, 'product-import:')) {
            return null;
        }

        return (int) substr($name, strlen('product-import:')) ?: null;
    }

    public static function directoryFor(ProductImport $import): string
    {
        return storage_path('app/private/product-imports/'.$import->getKey());
    }

    /**
     * Файлы выгрузок, уже лежащие на сервере: их можно импортировать,
     * не упираясь в лимит загрузки PHP.
     *
     * @return array<string, string> Путь => подпись для выбора
     */
    public static function serverFiles(): array
    {
        $files = [];

        foreach ((array) config('admin.import.directories', []) as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $pattern = rtrim($directory, '\\/').DIRECTORY_SEPARATOR;

            foreach (['*.csv', '*.CSV', '*.xlsx', '*.XLSX'] as $mask) {
                foreach (glob($pattern.$mask) ?: [] as $path) {
                    if (! is_file($path)) {
                        continue;
                    }

                    $files[$path] = sprintf(
                        '%s (%s, %s)',
                        basename($path),
                        self::humanSize((int) filesize($path)),
                        date('d.m.Y H:i', (int) filemtime($path)),
                    );
                }
            }
        }

        return $files;
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' МБ';
        }

        return max(1, (int) round($bytes / 1024)).' КБ';
    }
}
