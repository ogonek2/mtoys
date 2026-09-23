<?php

namespace App\Filament\Widgets;

use App\Services\ShopAnalytics;
use Filament\Widgets\ChartWidget;

class CategorySalesChart extends ChartWidget
{
    protected static ?int $sort = -17;

    protected static bool $isLazy = true;

    protected ?string $heading = 'Рейтинг категорий (график)';

    protected ?string $description = 'По количеству проданных единиц';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 1;

    public function isEmpty(): bool
    {
        return false;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $rows = ShopAnalytics::topCategories(8);
        if ($rows->isEmpty()) {
            return [
                'datasets' => [[
                    'label' => 'Продано шт.',
                    'data' => [1],
                    'backgroundColor' => ['#E5E7EB'],
                ]],
                'labels' => ['Нет данных'],
            ];
        }

        $palette = ['#0B1F3B', '#D4AF5A', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#64748B'];

        return [
            'datasets' => [
                [
                    'label' => 'Продано шт.',
                    'data' => $rows->pluck('qty')->all(),
                    'backgroundColor' => array_slice($palette, 0, max(1, $rows->count())),
                ],
            ],
            'labels' => $rows->pluck('name')->all(),
        ];
    }
}
