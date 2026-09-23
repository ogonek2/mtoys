<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductInfolist;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openOnSite')
                ->label('Открыть на сайте')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): ?string => ProductInfolist::storefrontUrl($this->getRecord()))
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled(ProductInfolist::storefrontUrl($this->getRecord()))),

            EditAction::make(),
        ];
    }

    public function getRecord(): Product
    {
        /** @var Product $record */
        $record = parent::getRecord();

        return $record;
    }
}
