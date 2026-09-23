<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Actions\ProductCriticalExportAction;
use App\Filament\Resources\Products\Actions\ProductExportAction;
use App\Filament\Resources\Products\Actions\ProductImportAction;
use App\Filament\Resources\Products\Actions\ProductTemplateAction;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\ProductDataQualityOverview;
use App\Services\Products\ProductDataQuality;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    #[Url]
    public ?string $activeTab = 'all';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ProductImportAction::make(),
                ProductExportAction::make(),
                ProductTemplateAction::make(),
            ])
                ->label('Импорт и экспорт')
                ->icon(Heroicon::OutlinedArrowsUpDown)
                ->button()
                ->color('gray'),

            ProductCriticalExportAction::make()
                ->button(),

            CreateAction::make(),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            ProductDataQualityOverview::class,
        ];
    }

    public function getTabs(): array
    {
        $counts = ProductDataQuality::counts();

        return [
            'all' => Tab::make('Все')
                ->badge($counts['total'])
                ->icon('heroicon-m-cube'),

            'incomplete' => Tab::make('Критичные')
                ->badge($counts['incomplete'])
                ->badgeColor($counts['incomplete'] > 0 ? 'danger' : 'gray')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::incomplete($query)),

            'without_price' => Tab::make('Без цены')
                ->badge($counts['without_price'])
                ->badgeColor($counts['without_price'] > 0 ? 'danger' : 'gray')
                ->icon('heroicon-m-banknotes')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::withoutPrice($query)),

            'without_image' => Tab::make('Без фото')
                ->badge($counts['without_image'])
                ->badgeColor($counts['without_image'] > 0 ? 'warning' : 'gray')
                ->icon('heroicon-m-photo')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::withoutImage($query)),

            'without_category' => Tab::make('Без категории')
                ->badge($counts['without_category'])
                ->badgeColor($counts['without_category'] > 0 ? 'warning' : 'gray')
                ->icon('heroicon-m-tag')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::withoutCategory($query)),

            'without_description' => Tab::make('Без описания')
                ->badge($counts['without_description'])
                ->icon('heroicon-m-document-text')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::withoutDescription($query)),

            'without_articule' => Tab::make('Без артикула')
                ->badge($counts['without_articule'])
                ->icon('heroicon-m-hashtag')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::withoutArticule($query)),

            'without_brand' => Tab::make('Без бренда')
                ->badge($counts['without_brand'])
                ->icon('heroicon-m-building-storefront')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::withoutBrand($query)),

            'without_characteristics' => Tab::make('Без характеристик')
                ->badge($counts['without_characteristics'])
                ->icon('heroicon-m-list-bullet')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::withoutCharacteristics($query)),

            'without_catalog' => Tab::make('Без каталога')
                ->badge($counts['without_catalog'])
                ->icon('heroicon-m-rectangle-stack')
                ->modifyQueryUsing(fn (Builder $query): Builder => ProductDataQuality::withoutCatalog($query)),
        ];
    }
}
