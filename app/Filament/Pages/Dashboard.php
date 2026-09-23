<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CartInterestStats;
use App\Filament\Widgets\CategorySalesChart;
use App\Filament\Widgets\LatestOrdersWidget;
use App\Filament\Widgets\OrdersMonthlyChart;
use App\Filament\Widgets\OrdersRevenueChart;
use App\Filament\Widgets\ShopStatsOverview;
use App\Filament\Widgets\TopCategoriesWidget;
use App\Filament\Widgets\TopProductsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            ShopStatsOverview::class,
            OrdersRevenueChart::class,
            OrdersMonthlyChart::class,
            CategorySalesChart::class,
            TopProductsWidget::class,
            TopCategoriesWidget::class,
            CartInterestStats::class,
            LatestOrdersWidget::class,
            AccountWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }
}
