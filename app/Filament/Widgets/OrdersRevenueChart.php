<?php

namespace App\Filament\Widgets;

use App\Services\ShopAnalytics;
use Filament\Widgets\ChartWidget;

class OrdersRevenueChart extends ChartWidget
{
    protected static ?int $sort = -19;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Заказы и выручка за 30 дней';

    protected ?string $description = 'Количество заказов и сумма продаж по дням';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    public function isEmpty(): bool
    {
        return false;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $data = ShopAnalytics::dailySales(30);

        return [
            'datasets' => [
                [
                    'label' => 'Заказы',
                    'data' => $data['orders'],
                    'borderColor' => '#0B1F3B',
                    'backgroundColor' => 'rgba(11, 31, 59, 0.12)',
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Выручка, ₴',
                    'data' => $data['revenue'],
                    'borderColor' => '#D4AF5A',
                    'backgroundColor' => 'rgba(212, 175, 90, 0.15)',
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'ticks' => ['precision' => 0],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => true],
            ],
        ];
    }
}
