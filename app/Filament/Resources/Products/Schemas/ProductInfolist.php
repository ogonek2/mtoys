<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\ShopOptions;
use App\Models\Product;
use App\Models\productImage;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Товар')
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('image_path')
                            ->label('Главное фото')
                            ->getStateUsing(fn (Product $record): string => $record->getImagePath())
                            ->checkFileExistence(false)
                            ->height(180),

                        Grid::make(2)
                            ->columnSpan(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Название')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->columnSpanFull(),

                                TextEntry::make('url')
                                    ->label('URL')
                                    ->url(fn (Product $record): ?string => self::storefrontUrl($record))
                                    ->openUrlInNewTab()
                                    ->copyable()
                                    ->columnSpanFull(),

                                TextEntry::make('articule')
                                    ->label('Артикул')
                                    ->placeholder('—'),

                                TextEntry::make('external_id')
                                    ->label('Внешний ID')
                                    ->placeholder('—'),

                                TextEntry::make('brand')
                                    ->label('Бренд')
                                    ->placeholder('—'),

                                TextEntry::make('country')
                                    ->label('Страна')
                                    ->placeholder('—'),

                                TextEntry::make('weight')
                                    ->label('Вес')
                                    ->suffix(' кг')
                                    ->placeholder('—'),
                            ]),
                    ]),

                Section::make('Цены и наличие')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('price')
                            ->label('Цена')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' ₴'),

                        TextEntry::make('discount')
                            ->label('Скидка')
                            ->formatStateUsing(fn (?int $state): string => $state > 0 ? "{$state}%" : '—')
                            ->helperText(fn (Product $record): ?string => self::discountPeriod($record)),

                        TextEntry::make('availability')
                            ->label('Наличие')
                            ->badge()
                            ->color(fn (?string $state): string => $state === 'in_stock' ? 'success' : 'danger')
                            ->formatStateUsing(fn (?string $state): string => ShopOptions::AVAILABILITY[$state] ?? (string) $state),

                        TextEntry::make('condition_item')
                            ->label('Состояние')
                            ->formatStateUsing(fn (?string $state): string => ShopOptions::CONDITION[$state] ?? ($state ?? '—')),

                        TextEntry::make('is_wholesale')
                            ->label('Опт')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Да' : 'Нет'),

                        TextEntry::make('wholesale_price')
                            ->label('Оптовая цена')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' ₴')
                            ->placeholder('—')
                            ->visible(fn (Product $record): bool => (bool) $record->is_wholesale),

                        TextEntry::make('wholesale_min_quantity')
                            ->label('Мин. опт. заказ')
                            ->placeholder('—')
                            ->visible(fn (Product $record): bool => (bool) $record->is_wholesale),

                        TextEntry::make('units_per_box')
                            ->label('В упаковке')
                            ->placeholder('—')
                            ->visible(fn (Product $record): bool => (bool) $record->is_wholesale),
                    ]),

                Section::make('Описание')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->html()
                            ->placeholder('—'),

                        TextEntry::make('complectation')
                            ->label('Комплектация')
                            ->placeholder('—'),

                        TextEntry::make('admin_notes')
                            ->label('Заметки (только в админке)')
                            ->placeholder('—'),
                    ]),

                Section::make('Характеристики')
                    ->collapsible()
                    ->schema([
                        // Значение показываем так же, как его увидит покупатель:
                        // с единицей измерения и перечисленными вариантами.
                        KeyValueEntry::make('characteristics')
                            ->hiddenLabel()
                            ->keyLabel('Название')
                            ->valueLabel('Значение')
                            ->state(fn (Product $record): array => collect($record->characteristicsForDisplay())
                                ->mapWithKeys(fn (array $characteristic): array => [
                                    $characteristic['name'] => $characteristic['value'],
                                ])
                                ->all())
                            ->placeholder('Характеристики не заданы'),
                    ]),

                Section::make('Дополнительные фото')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('images')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                ImageEntry::make('src')
                                    ->hiddenLabel()
                                    ->getStateUsing(fn (productImage $record): string => $record->getImagePath())
                                    ->checkFileExistence(false)
                                    ->height(120),
                            ])
                            ->placeholder('Дополнительных фото нет'),
                    ]),

                Section::make('Связи')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('categories.name')
                            ->label('Категории')
                            ->badge()
                            ->placeholder('—'),

                        TextEntry::make('catalogs.name')
                            ->label('Каталоги')
                            ->badge()
                            ->placeholder('—'),

                        TextEntry::make('packages.name')
                            ->label('Упаковки')
                            ->badge()
                            ->placeholder('—'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('seo_title')->label('SEO заголовок')->placeholder('—'),
                        TextEntry::make('seo_description')->label('SEO описание')->placeholder('—'),
                        TextEntry::make('seo_keywords')->label('SEO ключевые слова')->placeholder('—'),
                    ]),
            ]);
    }

    /**
     * Срок действия скидки из выгрузки — справочная информация.
     */
    private static function discountPeriod(Product $record): ?string
    {
        if ($record->discount_starts_at === null && $record->discount_ends_at === null) {
            return null;
        }

        $from = $record->discount_starts_at?->format('d.m.Y');
        $to = $record->discount_ends_at?->format('d.m.Y');

        return match (true) {
            $from !== null && $to !== null => "с {$from} по {$to}",
            $from !== null => "с {$from}",
            default => "до {$to}",
        };
    }

    /**
     * Ссылка на карточку товара на витрине: маршрут требует и slug категории.
     */
    public static function storefrontUrl(Product $record): ?string
    {
        $categoryUrl = $record->categories->first()?->url;

        if (! $record->url || ! $categoryUrl) {
            return null;
        }

        return route('catalog_product_page', [
            'category' => $categoryUrl,
            'product' => $record->url,
        ]);
    }
}
