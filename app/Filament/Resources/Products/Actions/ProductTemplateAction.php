<?php

namespace App\Filament\Resources\Products\Actions;

use App\Services\Products\ProductExporter;
use App\Services\Products\ProductFieldMap;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Пустой файл с выбранными колонками и строкой-примером: заполнить и загрузить
 * обратно через импорт.
 */
class ProductTemplateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'downloadProductTemplate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Шаблон для заполнения')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->modalHeading('Шаблон для заполнения')
            ->modalDescription('В файле будет строка-пример — замените её своими товарами и загрузите файл через «Импорт».')
            ->modalSubmitActionLabel('Скачать')
            ->modalWidth(Width::FourExtraLarge)
            ->schema([
                ProductFileFields::formatSelect(),
                ProductFileFields::fieldSelector(ProductFieldMap::defaultTemplateFields()),
            ])
            ->action(function (array $data): BinaryFileResponse {
                $exporter = new ProductExporter($data['fields'], $data['format']);

                $path = $exporter->writeTemplate(
                    (string) tempnam(sys_get_temp_dir(), 'products-template-'),
                );

                return response()
                    ->download($path, 'shablon-tovarov.'.$exporter->extension())
                    ->deleteFileAfterSend();
            });
    }
}
