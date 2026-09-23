<?php

namespace App\Services\Products;

use App\Models\ProductImport;
use Illuminate\Bus\Batch;
use Throwable;

/**
 * Колбэки пакета задач импорта.
 *
 * Регистрируются как callable-массивы (а не замыкания), чтобы пакет
 * сериализовался без SerializableClosure; задачу находим по batch_id.
 */
class ProductImportFinalizer
{
    public static function failed(Batch $batch, Throwable $exception): void
    {
        $import = self::findImport($batch);

        if ($import === null) {
            return;
        }

        $import->addProblem($exception->getMessage());
    }

    public static function finished(Batch $batch): void
    {
        $import = self::findImport($batch);

        if ($import === null || $import->isFinished()) {
            return;
        }

        $status = match (true) {
            $batch->cancelled() => ProductImport::STATUS_CANCELLED,
            $batch->hasFailures() => ProductImport::STATUS_FAILED,
            default => ProductImport::STATUS_COMPLETED,
        };

        $error = $batch->hasFailures()
            ? sprintf('Не обработано порций: %d из %d.', $batch->failedJobs, $batch->totalJobs)
            : null;

        $import->finish($status, $error);
        ProductDataQuality::forgetCache();
    }

    private static function findImport(Batch $batch): ?ProductImport
    {
        $importId = ProductImportQueue::importIdFromBatchName($batch->name);

        if ($importId !== null) {
            return ProductImport::find($importId);
        }

        return ProductImport::query()->where('batch_id', $batch->id)->first();
    }
}
