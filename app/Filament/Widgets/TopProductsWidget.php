<?php

namespace App\Filament\Widgets;

use App\Services\ShopAnalytics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class TopProductsWidget extends TableWidget
{
    protected static ?int $sort = -16;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Популярные товары')
            ->description('Топ по количеству проданных единиц в заказах')
            ->records(fn (): Collection => ShopAnalytics::topProducts(12))
            ->paginated(false)
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->alignCenter()
                    ->width('3rem'),

                TextColumn::make('name')
                    ->label('Товар')
                    ->wrap()
                    ->limit(80),

                TextColumn::make('articule')
                    ->label('Артикул')
                    ->color('gray'),

                TextColumn::make('qty')
                    ->label('Продано')
                    ->alignCenter()
                    ->weight('bold'),

                TextColumn::make('orders')
                    ->label('В заказах')
                    ->alignCenter(),

                TextColumn::make('revenue')
                    ->label('Выручка')
                    ->alignEnd()
                    ->weight('bold')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, '.', ' ').' ₴'),
            ]);
    }
}
