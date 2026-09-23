<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ProductImport;
use App\Services\Products\ProductFieldMap;
use App\Services\Products\ProductImportQueue;
use App\Services\Products\ProductImportWorker;
use Illuminate\Console\Command;
use Throwable;

/**
 * Ставит выгрузку товаров в очередь порциями.
 *
 * Крупный файл не обрабатывается целиком: его режут на куски, каждый кусок
 * уходит отдельной задачей. Ход виден в правом сайдбаре админки.
 */
class ImportProductsCommand extends Command
{
    protected $signature = 'products:import
        {path : Путь к файлу CSV или XLSX}
        {--skip-existing : Не обновлять товары, которые уже есть в базе}
        {--create-categories : Создавать категории, которых ещё нет}
        {--default-category= : ID категории для товаров без группы}
        {--without-images : Не забирать ссылки на изображения}
        {--chunk= : Строк в одной задаче очереди}
        {--sync : Обработать сразу в этом процессе, не оставляя задачи в очереди}
        {--wait : Дождаться окончания очереди в этом процессе}
        {--force : Не спрашивать подтверждение}';

    protected $description = 'Импорт товаров из выгрузки CSV/XLSX порциями через очередь';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->components->error("Файл не найден: {$path}");

            return self::FAILURE;
        }

        $defaultCategoryId = $this->option('default-category');

        if ($defaultCategoryId !== null && ! Category::query()->whereKey($defaultCategoryId)->exists()) {
            $this->components->error("Категория с ID {$defaultCategoryId} не найдена.");

            return self::FAILURE;
        }

        $chunkSize = $this->option('chunk') !== null
            ? (int) $this->option('chunk')
            : (int) config('admin.import.chunk_size', 50);

        $this->components->twoColumnDetail('Файл', $path);
        $this->components->twoColumnDetail('Размер', ProductImportQueue::humanSize((int) filesize($path)));
        $this->components->twoColumnDetail('База данных', (string) config('database.connections.'.config('database.default').'.database'));
        $this->components->twoColumnDetail('Существующие товары', $this->option('skip-existing') ? 'пропускать' : 'обновлять');
        $this->components->twoColumnDetail('Новые категории', $this->option('create-categories') ? 'создавать' : 'не создавать');
        $this->components->twoColumnDetail('Изображения', $this->option('without-images') ? 'не забирать' : 'забирать ссылки');
        $this->components->twoColumnDetail('Строк в порции', (string) $chunkSize);

        if (! $this->option('force') && ! $this->confirm('Поставить импорт в очередь?', true)) {
            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            config(['queue.default' => 'sync']);
        }

        try {
            $import = (new ProductImportQueue)->dispatch(
                sourcePath: $path,
                fileName: basename($path),
                options: [
                    'update_existing' => ! $this->option('skip-existing'),
                    'create_missing_categories' => (bool) $this->option('create-categories'),
                    'default_category_id' => $defaultCategoryId,
                    'import_images' => ! $this->option('without-images'),
                ],
                source: ProductImportQueue::SOURCE_CONSOLE,
                chunkSize: $chunkSize,
            );
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info("Задача #{$import->id}: {$import->total_rows} строк, {$import->chunks_total} порций.");

        if ($import->recognized_fields) {
            $this->line('  Колонки: '.implode(', ', array_map(
                static fn (string $field): string => ProductFieldMap::label($field),
                $import->recognized_fields,
            )));
        }

        if ($this->option('wait') && $import->isActive()) {
            $this->waitForImport($import);
        }

        $import->refresh();

        if ($import->isFinished()) {
            $this->components->info($import->statusLabel().': '.$import->summary());
        } else {
            $this->components->info('Следите за выполнением в правом сайдбаре админки (значок списка задач).');
            $this->line('  Либо запустите: php artisan queue:work --stop-when-empty');
        }

        return $import->status === ProductImport::STATUS_FAILED ? self::FAILURE : self::SUCCESS;
    }

    private function waitForImport(ProductImport $import): void
    {
        $this->components->info('Обрабатываю порции очереди...');

        $worker = app(ProductImportWorker::class);
        $started = time();

        while ($import->refresh()->isActive() && (time() - $started) < 1800) {
            if (! $worker->processNext()) {
                usleep(250_000);
            }
        }
    }
}
