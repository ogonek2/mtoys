<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrdersResource;
use App\Models\Orders;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrdersWidget extends TableWidget
{
    protected static ?int $sort = -13;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Последние заказы')
            ->query(Orders::query()->latest('id')->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('id')
                    ->label('№'),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i'),

                TextColumn::make('full_name')
                    ->label('Покупатель')
                    ->getStateUsing(fn (Orders $record): string => $record->full_name ?: '—'),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->getStateUsing(fn (Orders $record): string => $record->displayValue('phone')),

                TextColumn::make('total_price')
                    ->label('Сумма')
                    ->getStateUsing(fn (Orders $record): string => $record->formatted_total_price)
                    ->weight('bold'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Открыть')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Orders $record): string => OrdersResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
