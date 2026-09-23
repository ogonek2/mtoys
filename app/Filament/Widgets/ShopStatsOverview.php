<?php

namespace App\Filament\Widgets;

use App\Services\ShopAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -20;

    protected ?string $heading = 'Сводка магазина';

    protected function getStats(): array
    {
        $o = ShopAnalytics::overview();
        $abandoned = ShopAnalytics::abandonedCarts();

        return [
            Stat::make('Заказы за месяц', (string) $o['orders'])
                ->description('Всего: '.$o['orders_total'])
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('Выручка за месяц', number_format($o['revenue'], 0, '.', ' ').' ₴')
                ->description('Средний чек: '.number_format($o['avg_check'], 0, '.', ' ').' ₴')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Выручка всего', number_format($o['revenue_total'], 0, '.', ' ').' ₴')
                ->description('По всем расшифрованным заказам')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Товары', (string) $o['products'])
                ->description('В наличии: '.$o['in_stock'])
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Брошенные корзины', (string) $abandoned['count'])
                ->description('За 7 дней: '.$abandoned['recent'].' · телефонов: '.$abandoned['phones'])
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color($abandoned['count'] > 0 ? 'danger' : 'gray'),
        ];
    }
}
