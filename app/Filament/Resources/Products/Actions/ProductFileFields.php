<?php

namespace App\Filament\Resources\Products\Actions;

use App\Services\Products\ProductExporter;
use App\Services\Products\ProductFieldMap;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;

/**
 * Общие поля формы для экспорта и шаблона: выбор колонок и формата файла.
 */
class ProductFileFields
{
    public static function formatSelect(): Select
    {
        return Select::make('format')
            ->label('Формат файла')
            ->options([
                ProductExporter::FORMAT_XLSX => 'Excel (.xlsx)',
                ProductExporter::FORMAT_CSV => 'CSV (.csv)',
            ])
            ->default(ProductExporter::FORMAT_XLSX)
            ->native(false)
            ->required();
    }

    /**
     * @param  array<int, string>  $default
     */
    public static function fieldSelector(array $default): CheckboxList
    {
        return CheckboxList::make('fields')
            ->label('Колонки файла')
            ->options(ProductFieldMap::labels())
            ->default($default)
            ->columns(3)
            ->bulkToggleable()
            ->required()
            ->helperText(
                'Характеристики выгружаются одной колонкой в виде «Колір=Білий; Вага=2 г», '
                .'изображения — ссылками через запятую, категории и каталоги — названиями через «|».',
            );
    }
}
