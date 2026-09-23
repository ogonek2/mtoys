<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrdersResource;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Orders;
use App\Services\TelegramNotifier;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ViewOrders extends ViewRecord
{
    protected static string $resource = OrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateStatus')
                ->label('Изменить статус')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->fillForm(fn (Orders $record): array => [
                    'status' => $record->status ?: Orders::STATUS_NEW,
                    'tracking_number' => $record->tracking_number,
                    'notify_customer' => true,
                    'notify_telegram' => false,
                ])
                ->form([
                    Select::make('status')
                        ->label('Статус')
                        ->options(Orders::STATUSES)
                        ->required()
                        ->live(),
                    TextInput::make('tracking_number')
                        ->label('Номер накладной')
                        ->maxLength(120)
                        ->helperText('Обязателен для статусов «Отправлено» и «Создан на почте»')
                        ->required(fn (Get $get): bool => in_array($get('status'), Orders::STATUSES_WITH_TRACKING, true))
                        ->visible(fn (Get $get): bool => in_array($get('status'), Orders::STATUSES_WITH_TRACKING, true)
                            || filled($get('tracking_number'))),
                    Toggle::make('notify_customer')
                        ->label('Отправить письмо клиенту')
                        ->default(true)
                        ->helperText('Для статусов с отправкой письмо содержит номер накладной'),
                    Toggle::make('notify_telegram')
                        ->label('Уведомить в Telegram о смене статуса')
                        ->default(false),
                ])
                ->action(function (array $data, Orders $record): void {
                    $oldStatus = $record->status;
                    $newStatus = $data['status'];
                    $tracking = trim((string) ($data['tracking_number'] ?? ''));

                    if (in_array($newStatus, Orders::STATUSES_WITH_TRACKING, true) && $tracking === '') {
                        Notification::make()
                            ->title('Укажите номер накладной')
                            ->danger()
                            ->send();

                        return;
                    }

                    $record->forceFill([
                        'status' => $newStatus,
                        'tracking_number' => $tracking !== '' ? $tracking : null,
                    ])->save();

                    $record = $record->fresh();
                    $statusLabel = Orders::STATUSES[$newStatus] ?? $newStatus;
                    $mailOk = null;
                    $tgOk = null;

                    $shouldNotify = (bool) ($data['notify_customer'] ?? false);
                    $email = trim((string) $record->email);

                    if ($shouldNotify && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            Mail::to($email)->send(new OrderStatusUpdatedMail($record, $statusLabel));
                            $mailOk = true;
                        } catch (Throwable $e) {
                            $mailOk = false;
                            Log::error('Order status mail failed: '.$e->getMessage());
                        }
                    }

                    if (! empty($data['notify_telegram'])) {
                        $tgOk = TelegramNotifier::send(
                            implode("\n", array_filter([
                                '📦 Статус заказа #'.$record->id.' изменён',
                                'Новый статус: '.$statusLabel,
                                $tracking !== '' ? 'Накладная: '.$tracking : null,
                                'Клиент: '.trim(($record->lastname ?? '').' '.($record->name ?? '')),
                                'Тел: '.($record->phone ?? '—'),
                            ]))
                        );
                    }

                    if ($mailOk === true && $tgOk === true) {
                        Notification::make()->title('Статус обновлён, email и Telegram отправлены')->success()->send();
                    } elseif ($mailOk === true) {
                        Notification::make()->title('Статус обновлён, письмо отправлено')->success()->send();
                    } elseif ($mailOk === false) {
                        Notification::make()->title('Статус обновлён, но письмо не отправилось')->warning()->send();
                    } elseif ($tgOk === true) {
                        Notification::make()->title('Статус обновлён, Telegram отправлен')->success()->send();
                    } elseif ($tgOk === false) {
                        Notification::make()->title('Статус обновлён, но Telegram не отправился')->warning()->send();
                    } else {
                        Notification::make()
                            ->title($oldStatus === $newStatus ? 'Данные сохранены' : 'Статус обновлён')
                            ->success()
                            ->send();
                    }
                }),

            Action::make('sendTelegram')
                ->label('В Telegram')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Отправить заказ в Telegram?')
                ->modalDescription('В чат магазина уйдёт полное сообщение с составом заказа и контактами клиента.')
                ->action(function (Orders $record): void {
                    $ok = TelegramNotifier::sendNewOrder(
                        [
                            'id' => $record->id,
                            'name' => $record->name,
                            'lastname' => $record->lastname,
                            'fathername' => $record->fathername,
                            'phone' => $record->phone,
                            'email' => $record->email,
                            'delivery_service' => $record->delivery_service,
                            'payment' => $record->payment,
                            'city' => $record->city,
                            'warehouse' => $record->warehouse,
                            'manual_address' => $record->manual_address,
                            'comment' => $record->comment,
                        ],
                        $record->cart_items,
                        $record->numeric_total_price
                    );

                    if ($ok) {
                        Notification::make()
                            ->title('Заказ отправлен в Telegram')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Не удалось отправить в Telegram')
                            ->body('Проверьте TG_BOT_TOKEN / TG_CHAT_ID и логи.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('printOrder')
                ->label('Печатать заказ')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Orders $record): string => route('admin.orders.print', $record))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
