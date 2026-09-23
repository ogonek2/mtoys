<?php

namespace App\Services\Products;

use App\Models\ProductImport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Подхватывает одну порцию импорта из очереди.
 *
 * На локальном OpenServer отдельный queue:work обычно не запущен — тогда
 * панель задач сама разбирает порции по одной, пока открыта админка.
 * Если воркер уже крутится, он заберёт задачу раньше: --once безопасен.
 */
class ProductImportWorker
{
    public function processNext(): bool
    {
        if (! $this->shouldProcess()) {
            return false;
        }

        Artisan::call('queue:work', [
            '--once' => true,
            '--tries' => 3,
            '--timeout' => 180,
            '--sleep' => 0,
        ]);

        return true;
    }

    public function shouldProcess(): bool
    {
        if (config('queue.default') === 'sync') {
            return false;
        }

        if (! ProductImport::query()->whereIn('status', [
            ProductImport::STATUS_QUEUED,
            ProductImport::STATUS_PROCESSING,
        ])->exists()) {
            return false;
        }

        return DB::table('jobs')->exists();
    }

    public function pendingJobs(): int
    {
        if (config('queue.default') === 'sync') {
            return 0;
        }

        return (int) DB::table('jobs')->count();
    }
}
