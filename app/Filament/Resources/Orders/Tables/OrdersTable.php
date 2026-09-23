<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Orders;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            // Персональные данные заказа зашифрованы в базе, поэтому фильтровать
            // и сортировать по ним на стороне SQL нельзя — только выводить.
            ->columns([
                TextColumn::make('id')
                    ->label('№')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Orders::STATUSES[$state ?? Orders::STATUS_NEW] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        Orders::STATUS_SHIPPED, Orders::STATUS_POSTED => 'info',
                        Orders::STATUS_DELIVERED => 'success',
                        Orders::STATUS_CANCELLED => 'danger',
                        Orders::STATUS_ASSEMBLED => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Покупатель')
                    ->getStateUsing(function (Orders $record): string {
                        $name = $record->full_name;
                        if ($name === '' || $name === '—' || Orders::looksLikeEncryptedPayload($name)) {
                            return 'Заказ #'.$record->id.' (не удалось расшифровать)';
                        }

                        return $name;
                    }),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->getStateUsing(fn (Orders $record): string => $record->displayValue('phone'))
                    ->copyable(fn (Orders $record): bool => $record->displayValue('phone') !== '—'),

                TextColumn::make('email')
                    ->label('Email')
                    ->getStateUsing(fn (Orders $record): string => $record->displayValue('email'))
                    ->toggleable()
                    ->copyable(fn (Orders $record): bool => $record->displayValue('email') !== '—'),

                TextColumn::make('tracking_number')
                    ->label('Накладная')
                    ->placeholder('—')
                    ->toggleable()
                    ->copyable(),

                TextColumn::make('delivery_service')
                    ->label('Доставка')
                    ->getStateUsing(fn (Orders $record): string => $record->displayValue('delivery_service'))
                    ->badge()
                    ->color('info'),

                TextColumn::make('city')
                    ->label('Город')
                    ->getStateUsing(fn (Orders $record): string => $record->displayValue('city'))
                    ->toggleable(),

                TextColumn::make('payment')
                    ->label('Оплата')
                    ->getStateUsing(fn (Orders $record): string => $record->displayValue('payment'))
                    ->toggleable(),

                TextColumn::make('positions')
                    ->label('Позиций')
                    ->badge()
                    ->getStateUsing(fn (Orders $record): int => count($record->cart_items)),

                TextColumn::make('total_price')
                    ->label('Сумма')
                    ->getStateUsing(fn (Orders $record): string => $record->formatted_total_price)
                    ->weight('bold'),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
