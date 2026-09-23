<?php

namespace App\Filament\Widgets;

use App\Services\ShopAnalytics;
use Filament\Widgets\ChartWidget;

class OrdersMonthlyChart extends ChartWidget
{
    protected static ?int $sort = -18;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Заказы по месяцам';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 1;

    public function isEmpty(): bool
    {
        return false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $data = ShopAnalytics::monthlySales(6);

        return [
            'datasets' => [
                [
                    'label' => 'Заказы',
                    'data' => $data['orders'],
                    'backgroundColor' => '#0B1F3B',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }
}
