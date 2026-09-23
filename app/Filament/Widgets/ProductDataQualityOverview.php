<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Services\Products\ProductDataQuality;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductDataQualityOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Контроль данных после импорта';

    protected ?string $description = 'Быстрый обзор пробелов в карточках. Ниже — вкладки и фильтры для правки.';

    protected function getStats(): array
    {
        $c = ProductDataQuality::counts();
        $base = ProductResource::getUrl('index');

        return [
            Stat::make('Всего товаров', (string) $c['total'])
                ->description('В каталоге')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary')
                ->url($base.'?activeTab=all'),

            Stat::make('Без цены', (string) $c['without_price'])
                ->description('0 или пусто — «цена уточняется»')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($c['without_price'] > 0 ? 'danger' : 'success')
                ->url($base.'?activeTab=without_price'),

            Stat::make('Без фото / заглушка', (string) $c['without_image'])
                ->description('Нет image или no-image')
                ->descriptionIcon('heroicon-m-photo')
                ->color($c['without_image'] > 0 ? 'warning' : 'success')
                ->url($base.'?activeTab=without_image'),

            Stat::make('Без категории', (string) $c['without_category'])
                ->description('Не привязаны к дереву')
                ->descriptionIcon('heroicon-m-tag')
                ->color($c['without_category'] > 0 ? 'warning' : 'success')
                ->url($base.'?activeTab=without_category'),

            Stat::make('Без описания', (string) $c['without_description'])
                ->description('Пустой description')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($c['without_description'] > 0 ? 'gray' : 'success')
                ->url($base.'?activeTab=without_description'),

            Stat::make('Без артикула', (string) $c['without_articule'])
                ->description('Пустой articule')
                ->descriptionIcon('heroicon-m-hashtag')
                ->color($c['without_articule'] > 0 ? 'gray' : 'success')
                ->url($base.'?activeTab=without_articule'),

            Stat::make('Без характеристик', (string) $c['without_characteristics'])
                ->description('Пустой JSON характеристик')
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color($c['without_characteristics'] > 0 ? 'gray' : 'success')
                ->url($base.'?activeTab=without_characteristics'),

            Stat::make('Критичные пробелы', (string) $c['incomplete'])
                ->description('Нет цены, фото или категории')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($c['incomplete'] > 0 ? 'danger' : 'success')
                ->url($base.'?activeTab=incomplete'),
        ];
    }
}
