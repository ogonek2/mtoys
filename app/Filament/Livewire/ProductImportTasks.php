<?php

namespace App\Filament\Livewire;

use App\Models\ProductImport;
use App\Services\Products\ProductImportWorker;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Правый сайдбар админки: ход импорта товаров по порциям очереди.
 */
class ProductImportTasks extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public bool $autoProcess = true;

    #[On('product-import-queued')]
    public function importQueued(): void
    {
        $this->autoProcess = true;
        $this->dispatch('open-modal', id: 'product-import-tasks');
    }

    public function tick(): void
    {
        if ($this->autoProcess) {
            app(ProductImportWorker::class)->processNext();
        }
    }

    public function processNow(): void
    {
        $this->autoProcess = true;
        app(ProductImportWorker::class)->processNext();
    }

    public function cancelImport(int $id): void
    {
        $import = ProductImport::find($id);

        if ($import === null || ! $import->isActive()) {
            return;
        }

        $import->cancel();

        Notification::make()
            ->title('Импорт остановлен')
            ->body($import->file_name)
            ->warning()
            ->send();
    }

    public function dismiss(int $id): void
    {
        ProductImport::query()
            ->whereKey($id)
            ->whereNotIn('status', [ProductImport::STATUS_QUEUED, ProductImport::STATUS_PROCESSING])
            ->delete();
    }

    /**
     * @return Collection<int, ProductImport>
     */
    public function imports(): Collection
    {
        return ProductImport::query()
            ->latest('id')
            ->limit((int) config('admin.import.history', 15))
            ->get();
    }

    public function activeCount(): int
    {
        return ProductImport::query()
            ->whereIn('status', [ProductImport::STATUS_QUEUED, ProductImport::STATUS_PROCESSING])
            ->count();
    }

    public function render()
    {
        $active = $this->activeCount();

        return view('filament.livewire.product-import-tasks', [
            'imports' => $this->imports(),
            'activeCount' => $active,
            'pendingJobs' => app(ProductImportWorker::class)->pendingJobs(),
            'pollingInterval' => $active > 0 ? '3s' : '15s',
        ]);
    }
}
