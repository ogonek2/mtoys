<?php

namespace App\Filament\Pages;

use App\Services\ProductFeedService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

class ManageProductFeeds extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRss;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Фиды товаров';

    protected static ?string $title = 'Google и Facebook фиды';

    protected static ?int $navigationSort = 95;

    protected string $view = 'filament.pages.manage-product-feeds';

    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array
    {
        return ProductFeedService::meta();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Сгенерировать сейчас')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Пересобрать фиды?')
                ->modalDescription('Будут заново собраны Google Merchant XML и Facebook Catalog CSV по текущим товарам.')
                ->action(function (): void {
                    try {
                        $meta = ProductFeedService::generate();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Не удалось сгенерировать фиды')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Фиды обновлены')
                        ->body("Товаров в фиде: {$meta['count']}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
