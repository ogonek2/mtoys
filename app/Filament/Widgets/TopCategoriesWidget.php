<?php

namespace App\Filament\Widgets;

use App\Services\ShopAnalytics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class TopCategoriesWidget extends TableWidget
{
    protected static ?int $sort = -15;

    protected int|string|array $columnSpan = 1;

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Рейтинг категорий')
            ->description('По количеству проданных единиц')
            ->records(fn (): Collection => ShopAnalytics::topCategories(8)
                ->values()
                ->map(function (array $row, int $index): array {
                    $row['id'] = $index + 1;

                    return $row;
                }))
            ->paginated(false)
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->alignCenter()
                    ->width('3rem'),

                TextColumn::make('name')
                    ->label('Категория')
                    ->wrap(),

                TextColumn::make('qty')
                    ->label('Продано')
                    ->alignCenter()
                    ->suffix(' шт')
                    ->weight('bold'),

                TextColumn::make('revenue')
                    ->label('Выручка')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, '.', ' ').' ₴'),
            ]);
    }
}
