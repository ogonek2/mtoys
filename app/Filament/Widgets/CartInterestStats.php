<?php

namespace App\Filament\Widgets;

use App\Services\ShopAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CartInterestStats extends StatsOverviewWidget
{
    protected static ?int $sort = -14;

    protected int|string|array $columnSpan = 1;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Корзины / интерес';

    protected ?string $description = 'По брошенным корзинам и составу заказов';

    protected function getStats(): array
    {
        $abandoned = ShopAnalytics::abandonedCarts();

        return [
            Stat::make('Брошенных корзин', (string) $abandoned['count'])
                ->description('Всего записей')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color($abandoned['count'] > 0 ? 'danger' : 'gray'),

            Stat::make('За 7 дней', (string) $abandoned['recent'])
                ->description('Недавняя активность')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Уникальных телефонов', (string) $abandoned['phones'])
                ->description('С указанным номером')
                ->descriptionIcon('heroicon-m-phone')
                ->color('info'),
        ];
    }
}
