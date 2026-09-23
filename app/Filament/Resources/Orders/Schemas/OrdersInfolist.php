<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Orders;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class OrdersInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Заказ')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('id')->label('Номер'),
                        TextEntry::make('created_at')->label('Создан')->dateTime('d.m.Y H:i'),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => Orders::STATUSES[$state ?? Orders::STATUS_NEW] ?? (string) $state)
                            ->color(fn (?string $state): string => match ($state) {
                                Orders::STATUS_SHIPPED, Orders::STATUS_POSTED => 'info',
                                Orders::STATUS_DELIVERED => 'success',
                                Orders::STATUS_CANCELLED => 'danger',
                                Orders::STATUS_ASSEMBLED => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('tracking_number')
                            ->label('Накладная')
                            ->placeholder('—')
                            ->copyable(),
                        TextEntry::make('total_price')
                            ->label('Сумма')
                            ->getStateUsing(fn (Orders $record): string => $record->formatted_total_price)
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->columnSpan(2),
                    ]),

                Section::make('Покупатель')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Имя')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('name')),
                        TextEntry::make('lastname')
                            ->label('Фамилия')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('lastname')),
                        TextEntry::make('fathername')
                            ->label('Отчество')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('fathername')),
                        TextEntry::make('phone')
                            ->label('Телефон')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('phone'))
                            ->copyable(fn (Orders $record): bool => $record->displayValue('phone') !== '—'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('email'))
                            ->copyable(fn (Orders $record): bool => $record->displayValue('email') !== '—'),
                        TextEntry::make('comment')
                            ->label('Комментарий')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('comment'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Доставка и оплата')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('delivery_service')
                            ->label('Служба доставки')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('delivery_service')),
                        TextEntry::make('payment')
                            ->label('Способ оплаты')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('payment')),
                        TextEntry::make('city')
                            ->label('Город')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('city')),
                        TextEntry::make('warehouse')
                            ->label('Отделение')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('warehouse')),
                        TextEntry::make('manual_address')
                            ->label('Адрес вручную')
                            ->getStateUsing(fn (Orders $record): string => $record->displayValue('manual_address'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Состав заказа (для сборки)')
                    ->description('Полный список позиций для склада')
                    ->schema([
                        // Лёгкий HTML вместо RepeatableEntry+ImageEntry (на хостинге давало 503).
                        TextEntry::make('cart_html')
                            ->hiddenLabel()
                            ->html()
                            ->getStateUsing(fn (Orders $record): string => view('filament.orders.cart-items', [
                                'order' => $record,
                            ])->render()),
                    ]),
            ]);
    }
}
