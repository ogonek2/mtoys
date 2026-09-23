<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
use App\Services\Products\ProductExporter;
use App\Services\Products\ProductFieldMap;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Экспорт товаров в файл: пользователь сам отмечает нужные колонки, чтобы не
 * разбираться потом в таблице на сотню столбцов.
 */
class ProductExportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportProducts';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Экспорт')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->modalHeading('Экспорт товаров')
            ->modalSubmitActionLabel('Скачать')
            ->modalWidth(Width::FourExtraLarge)
            ->schema([
                ProductFileFields::formatSelect(),

                Toggle::make('only_filtered')
                    ->label('Только товары из текущего фильтра и поиска')
                    ->default(true)
                    ->helperText('Выключите, чтобы выгрузить весь каталог целиком.'),

                ProductFileFields::fieldSelector(ProductFieldMap::defaultExportFields()),
            ])
            ->action(function (array $data, Component $livewire): BinaryFileResponse {
                $exporter = new ProductExporter($data['fields'], $data['format']);

                $path = $exporter->write(
                    (string) tempnam(sys_get_temp_dir(), 'products-export-'),
                    self::resolveQuery($livewire, (bool) ($data['only_filtered'] ?? false)),
                );

                return response()
                    ->download($path, 'tovary-'.now()->format('Y-m-d-Hi').'.'.$exporter->extension())
                    ->deleteFileAfterSend();
            });
    }

    private static function resolveQuery(Component $livewire, bool $onlyFiltered): Builder
    {
        if ($onlyFiltered && method_exists($livewire, 'getTableQueryForExport')) {
            return $livewire->getTableQueryForExport();
        }

        return Product::query();
    }
}
