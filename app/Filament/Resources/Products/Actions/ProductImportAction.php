<?php

namespace App\Filament\Resources\Products\Actions;

use App\Filament\Support\ShopOptions;
use App\Models\ProductImport;
use App\Services\Products\ProductImportQueue;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Импорт товаров из CSV/XLSX: понимает и выгрузку Prom.ua, и наш шаблон.
 *
 * Файл не обрабатывается в запросе — он режется на порции, которые уходят
 * в очередь отдельными задачами. Ход выполнения виден в панели задач админки.
 */
class ProductImportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'importProducts';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Импорт')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('Импорт товаров из файла')
            ->modalDescription('Подойдёт выгрузка с Prom.ua или наш шаблон — колонки определяются по заголовкам. Колонок, которых нет в файле, импорт не касается.')
            ->modalSubmitActionLabel('Поставить в очередь')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Radio::make('source')
                    ->label('Откуда берём файл')
                    ->options([
                        ProductImportQueue::SOURCE_UPLOAD => 'Загрузить с компьютера',
                        ProductImportQueue::SOURCE_SERVER => 'Файл уже лежит на сервере',
                    ])
                    ->default(ProductImportQueue::SOURCE_UPLOAD)
                    ->live()
                    ->required(),

                FileUpload::make('file')
                    ->label('Файл CSV или Excel')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(self::uploadLimitInKilobytes())
                    ->storeFiles(false)
                    ->visibility('private')
                    ->visible(fn (Get $get): bool => $get('source') !== ProductImportQueue::SOURCE_SERVER)
                    ->required(fn (Get $get): bool => $get('source') !== ProductImportQueue::SOURCE_SERVER)
                    ->helperText(new HtmlString(e(self::uploadLimitHint()))),

                Select::make('server_path')
                    ->label('Файл на сервере')
                    ->options(fn (): array => ProductImportQueue::serverFiles())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => $get('source') === ProductImportQueue::SOURCE_SERVER)
                    ->required(fn (Get $get): bool => $get('source') === ProductImportQueue::SOURCE_SERVER)
                    ->helperText(new HtmlString(e(self::serverHint()))),

                Toggle::make('update_existing')
                    ->label('Обновлять товары, которые уже есть')
                    ->default(true)
                    ->helperText('Товар ищется по ID, внешнему ID и артикулу. Если выключить, такие строки будут пропущены.'),

                Toggle::make('import_images')
                    ->label('Забирать ссылки на изображения')
                    ->default(true),

                Toggle::make('create_missing_categories')
                    ->label('Создавать категории, которых нет')
                    ->live()
                    ->helperText('Названия групп из файла станут новыми категориями каталога.'),

                Select::make('default_category_id')
                    ->label('Категория по умолчанию')
                    ->options(fn (): array => ShopOptions::categories())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => ! $get('create_missing_categories'))
                    ->helperText('Сюда попадут товары, для которых категория из файла не нашлась.'),

                TextInput::make('chunk_size')
                    ->label('Строк в одной задаче')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->default((int) config('admin.import.chunk_size', 50))
                    ->helperText('Файл разбивается на порции по этому числу строк, каждая порция уходит в очередь отдельной задачей.'),
            ])
            ->action(function (array $data, Component $livewire): void {
                $source = $data['source'] ?? ProductImportQueue::SOURCE_UPLOAD;

                try {
                    [$path, $fileName] = self::resolveFile($data, $source);

                    $import = (new ProductImportQueue)->dispatch(
                        sourcePath: $path,
                        fileName: $fileName,
                        options: [
                            'update_existing' => (bool) ($data['update_existing'] ?? true),
                            'import_images' => (bool) ($data['import_images'] ?? true),
                            'create_missing_categories' => (bool) ($data['create_missing_categories'] ?? false),
                            'default_category_id' => $data['default_category_id'] ?? null,
                        ],
                        userId: Auth::id(),
                        source: $source,
                        chunkSize: (int) ($data['chunk_size'] ?? 0),
                    );
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Импорт не запущен')
                        ->body($exception->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $livewire->dispatch('product-import-queued', importId: $import->id);

                self::notifyQueued($import);
            });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string} Путь к файлу и его имя
     */
    private static function resolveFile(array $data, string $source): array
    {
        if ($source === ProductImportQueue::SOURCE_SERVER) {
            $path = (string) ($data['server_path'] ?? '');

            // Путь принимаем только из списка просканированных каталогов.
            if (! array_key_exists($path, ProductImportQueue::serverFiles())) {
                throw new \RuntimeException('Файл не найден в каталогах импорта. Обновите страницу и выберите файл заново.');
            }

            return [$path, basename($path)];
        }

        /** @var TemporaryUploadedFile|null $file */
        $file = $data['file'] ?? null;

        if ($file === null) {
            throw new \RuntimeException('Файл не выбран.');
        }

        return [
            $file->getRealPath(),
            $file->getClientOriginalName() ?: 'import.csv',
        ];
    }

    /**
     * Лимит берём у PHP: обещать в форме больше, чем сервер примет, — значит
     * получить обрыв загрузки без внятного сообщения.
     */
    private static function uploadLimitInKilobytes(): int
    {
        $phpLimit = UploadedFile::getMaxFilesize();

        return $phpLimit > 0
            ? (int) min(floor($phpLimit / 1024), 51200)
            : 51200;
    }

    private static function uploadLimitHint(): string
    {
        $megabytes = round(self::uploadLimitInKilobytes() / 1024, 1);

        return "Максимальный размер загрузки — {$megabytes} МБ (ограничение PHP). "
            .'Файл крупнее положите на сервер и выберите вариант «Файл уже лежит на сервере» — количество строк там не ограничено.';
    }

    private static function serverHint(): string
    {
        $directories = array_map(
            static fn (string $directory): string => str_replace(base_path().DIRECTORY_SEPARATOR, '', $directory),
            (array) config('admin.import.directories', []),
        );

        return 'Ищем файлы CSV и XLSX в каталогах: '.implode(', ', $directories).'.';
    }

    private static function notifyQueued(ProductImport $import): void
    {
        $lines = [
            "Файл разобран: {$import->total_rows} строк, {$import->chunks_total} порций по {$import->chunk_size}.",
            'Следите за выполнением в панели задач — значок в правом верхнем углу.',
        ];

        if ($import->unknown_headers !== null && $import->unknown_headers !== []) {
            $lines[] = 'Незнакомые колонки пропущены: '.implode(', ', array_slice($import->unknown_headers, 0, 10))
                .(count($import->unknown_headers) > 10 ? ' и ещё '.(count($import->unknown_headers) - 10) : '').'.';
        }

        if (config('queue.default') === 'sync') {
            $lines[] = 'Очередь работает в режиме sync: задачи выполняются сразу, без воркера.';
        }

        Notification::make()
            ->title($import->isFinished() ? 'Импорт выполнен' : 'Импорт поставлен в очередь')
            ->body(new HtmlString(implode('<br>', array_map('e', $lines))))
            ->success()
            ->send();
    }
}
