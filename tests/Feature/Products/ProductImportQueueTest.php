<?php

namespace Tests\Feature\Products;

use App\Filament\Livewire\ProductImportTasks;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\ProductImport;
use App\Models\User;
use App\Services\Products\ProductImportChunker;
use App\Services\Products\ProductImportQueue;
use App\Services\Products\ProductImportWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImportQueueTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $path, array $rows): string
    {
        $handle = fopen($path, 'wb');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        fclose($handle);

        return $path;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function sampleRows(int $count): array
    {
        $rows = [['Название', 'Артикул', 'Цена']];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = ["Товар {$i}", "ART-{$i}", (string) (100 + $i)];
        }

        return $rows;
    }

    private function sampleFile(int $count): string
    {
        return $this->writeCsv((string) tempnam(sys_get_temp_dir(), 'queue-import-').'.csv', $this->sampleRows($count));
    }

    public function test_chunker_splits_file_into_self_contained_csv_parts(): void
    {
        $source = $this->sampleFile(5);
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'chunks-'.uniqid();

        $split = (new ProductImportChunker(2))->split($source, $directory);

        $this->assertSame(5, $split['rows']);
        $this->assertCount(3, $split['chunks']);
        $this->assertContains('name', $split['recognizedFields']);

        $first = array_map(
            static fn (string $line): array => str_getcsv($line, ProductImportChunker::DELIMITER),
            array_values(array_filter(explode("\n", str_replace("\r", '', file_get_contents($split['chunks'][0]))))),
        );

        $this->assertSame('Название', $first[0][0]);
        $this->assertCount(3, $first, 'Заголовок и две строки товара.');

        array_map('unlink', $split['chunks']);
        @rmdir($directory);
        @unlink($source);
    }

    public function test_queue_dispatch_creates_import_and_imports_products_when_queue_is_sync(): void
    {
        $source = $this->sampleFile(4);

        $import = (new ProductImportQueue)->dispatch(
            sourcePath: $source,
            fileName: 'tovary.csv',
            options: ['create_missing_categories' => false],
            chunkSize: 2,
        );

        $this->assertSame(ProductImport::STATUS_COMPLETED, $import->fresh()->status);
        $this->assertSame(4, $import->fresh()->total_rows);
        $this->assertSame(2, $import->fresh()->chunks_total);
        $this->assertSame(2, $import->fresh()->chunks_done);
        $this->assertSame(4, Product::query()->count());
        $this->assertSame(4, $import->fresh()->created_count);
        $this->assertFalse(is_dir($import->directory ?? ''));
    }

    public function test_queue_dispatch_puts_one_job_per_chunk_on_database_queue(): void
    {
        config(['queue.default' => 'database']);

        $source = $this->sampleFile(5);

        $import = (new ProductImportQueue)->dispatch(
            sourcePath: $source,
            fileName: 'tovary.csv',
            chunkSize: 2,
        );

        $this->assertSame(ProductImport::STATUS_QUEUED, $import->status);
        $this->assertSame(3, $import->chunks_total);
        $this->assertSame(3, DB::table('jobs')->count());
        $this->assertSame(0, Product::query()->count());
    }

    public function test_sidebar_processes_queued_chunks_one_by_one(): void
    {
        config(['queue.default' => 'database']);

        $this->actingAs(User::factory()->create());

        $source = $this->sampleFile(3);

        $import = (new ProductImportQueue)->dispatch(
            sourcePath: $source,
            fileName: 'ochred.csv',
            chunkSize: 1,
        );

        $this->assertSame(3, DB::table('jobs')->count());

        $component = Livewire::test(ProductImportTasks::class)
            ->assertSee('ochred.csv')
            ->assertSee('В очереди');

        $component->call('tick');
        $this->assertSame(2, DB::table('jobs')->count());
        $this->assertSame(1, Product::query()->count());

        $component->call('tick')->call('tick');

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(3, Product::query()->count());
        $this->assertSame(ProductImport::STATUS_COMPLETED, $import->fresh()->status);
        $component->assertSee('Готово');
    }

    public function test_sidebar_can_cancel_an_active_import(): void
    {
        config(['queue.default' => 'database']);

        $this->actingAs(User::factory()->create());

        $source = $this->sampleFile(4);

        $import = (new ProductImportQueue)->dispatch(
            sourcePath: $source,
            fileName: 'stop.csv',
            chunkSize: 2,
        );

        Livewire::test(ProductImportTasks::class)
            ->call('cancelImport', $import->id)
            ->assertSee('Отменено');

        $this->assertSame(ProductImport::STATUS_CANCELLED, $import->fresh()->status);
    }

    public function test_import_action_queues_file_and_opens_sidebar_event(): void
    {
        $this->actingAs(User::factory()->create());

        $source = $this->sampleFile(2);
        config(['admin.import.directories' => [dirname($source)]]);

        Livewire::test(ListProducts::class)
            ->callAction('importProducts', [
                'source' => ProductImportQueue::SOURCE_SERVER,
                'server_path' => $source,
                'update_existing' => true,
                'import_images' => false,
                'create_missing_categories' => false,
                'chunk_size' => 50,
            ])
            ->assertHasNoActionErrors()
            ->assertDispatched('product-import-queued');

        $this->assertSame(1, ProductImport::query()->count());
        $this->assertSame(2, Product::query()->count());
    }

    public function test_worker_does_not_run_when_queue_is_sync(): void
    {
        $this->assertFalse(app(ProductImportWorker::class)->shouldProcess());
    }

    public function test_admin_topbar_contains_import_tasks_component(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(\App\Filament\Resources\Products\ProductResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Задачи импорта', false);
    }
}
