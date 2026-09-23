<?php

namespace App\Filament\Pages;

use App\Services\ShopSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageShopSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Настройки магазина';

    protected static ?string $title = 'Настройки магазина';

    protected static ?int $navigationSort = 90;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(ShopSettings::all());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Заказы')
                    ->description('Минимальная сумма и правила оформления заказа')
                    ->columns(2)
                    ->schema([
                        Toggle::make('min_order_enabled')
                            ->label('Включить минимальную сумму заказа')
                            ->helperText('Если выключено — проверка минимальной суммы на сайте отключается')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('min_order_total')
                            ->label('Минимальная сумма заказа')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('грн')
                            ->required()
                            ->default(1000)
                            ->helperText('По умолчанию 1000 грн. Используется в корзине, checkout и на карточке товара.')
                            ->disabled(fn (Get $get): bool => ! $get('min_order_enabled')),
                        Textarea::make('checkout_notice')
                            ->label('Доп. уведомление на оформлении')
                            ->rows(2)
                            ->placeholder('Например: Самовывоз в выходные по предварительной записи')
                            ->columnSpanFull(),
                    ]),

                Section::make('Доставка')
                    ->description('Бесплатная доставка и текст в шапке сайта')
                    ->columns(2)
                    ->schema([
                        Toggle::make('free_delivery_enabled')
                            ->label('Показывать бесплатную доставку')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('free_delivery_from')
                            ->label('Бесплатная доставка от')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('грн')
                            ->required()
                            ->default(10000)
                            ->disabled(fn (Get $get): bool => ! $get('free_delivery_enabled')),
                        Textarea::make('announcement_text')
                            ->label('Текст объявления в шапке')
                            ->rows(2)
                            ->helperText('Плейсхолдеры: {free_delivery_from}, {min_order_total}, {store_name}, {currency_symbol}, {usd_rate}')
                            ->columnSpanFull(),
                    ]),

                Section::make('Курс USD')
                    ->description('Цены в админке можно задавать в $, на сайте всегда показываются в грн. При смене курса UAH-цены пересчитаются автоматически.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('usd_rate')
                            ->label('Курс доллара (UAH за 1 USD)')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->required()
                            ->suffix('₴')
                            ->helperText('Например 41.50. После сохранения товары с заполненной ценой в $ будут пересчитаны.'),
                    ]),

                Section::make('Магазин')
                    ->columns(2)
                    ->schema([
                        TextInput::make('store_name')
                            ->label('Название магазина')
                            ->maxLength(120),
                        TextInput::make('currency_symbol')
                            ->label('Символ валюты')
                            ->maxLength(8)
                            ->placeholder('₴'),
                        TextInput::make('currency_label')
                            ->label('Подпись валюты')
                            ->maxLength(16)
                            ->placeholder('грн'),
                        TextInput::make('contact_phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(64),
                        TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(120),
                        Textarea::make('contact_address')
                            ->label('Адрес')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $oldRate = ShopSettings::usdRate();
        $newRate = (float) ($state['usd_rate'] ?? $oldRate);

        ShopSettings::setMany([
            'min_order_enabled' => (bool) ($state['min_order_enabled'] ?? true),
            'min_order_total' => (int) ($state['min_order_total'] ?? 1000),
            'free_delivery_enabled' => (bool) ($state['free_delivery_enabled'] ?? true),
            'free_delivery_from' => (int) ($state['free_delivery_from'] ?? 10000),
            'announcement_text' => (string) ($state['announcement_text'] ?? ''),
            'store_name' => (string) ($state['store_name'] ?? 'Mtoys'),
            'currency_symbol' => (string) ($state['currency_symbol'] ?? '₴'),
            'currency_label' => (string) ($state['currency_label'] ?? 'грн'),
            'usd_rate' => $newRate,
            'contact_phone' => (string) ($state['contact_phone'] ?? ''),
            'contact_email' => (string) ($state['contact_email'] ?? ''),
            'contact_address' => (string) ($state['contact_address'] ?? ''),
            'checkout_notice' => (string) ($state['checkout_notice'] ?? ''),
        ]);

        $recalculated = 0;
        if (abs($newRate - $oldRate) > 0.0001) {
            $recalculated = ShopSettings::recalculateProductPricesFromUsd($newRate);
        }

        $this->form->fill(ShopSettings::all());

        $notification = Notification::make()
            ->title('Настройки сохранены')
            ->success();

        if ($recalculated > 0) {
            $notification->body("Пересчитано товаров по курсу: {$recalculated}");
        }

        $notification->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Сохранить')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }
}
