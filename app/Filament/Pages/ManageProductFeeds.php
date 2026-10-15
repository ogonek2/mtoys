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

    protected static ?string $title = 'Товарные фиды';

    protected static ?int $navigationSort = 95;

    public function getSubheading(): ?string
    {
        return 'Google Merchant Center и Facebook / Meta Catalog';
    }

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
            Action::make('openGoogle')
                ->label('Google XML')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => ProductFeedService::publicUrl('google'), shouldOpenInNewTab: true),
            Action::make('openFacebook')
                ->label('Facebook CSV')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => ProductFeedService::publicUrl('facebook'), shouldOpenInNewTab: true),
            Action::make('generate')
                ->label('Сгенерировать сейчас')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Пересобрать фиды?')
                ->modalDescription('Будут заново собраны Google Merchant XML и Facebook Catalog CSV. Это может занять до минуты.')
                ->successNotification(null)
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
                        ->body("В фиде: {$meta['count']} из {$meta['catalog_total']} товаров")
                        ->success()
                        ->send();
                }),
        ];
    }
}
