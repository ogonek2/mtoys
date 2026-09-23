<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Forms\Components\ShopImageUpload;
use App\Filament\Support\ShopOptions;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Товар')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Основное')
                            ->icon('heroicon-o-cube')
                            ->schema(self::mainFields()),

                        Tab::make('Цены')
                            ->icon('heroicon-o-banknotes')
                            ->schema(self::priceFields()),

                        Tab::make('Изображения')
                            ->icon('heroicon-o-photo')
                            ->schema(self::imageFields()),

                        Tab::make('Характеристики')
                            ->icon('heroicon-o-list-bullet')
                            ->schema(self::characteristicsFields()),

                        Tab::make('Связи')
                            ->icon('heroicon-o-rectangle-group')
                            ->schema(self::relationFields()),

                        Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema(self::seoFields()),
                    ]),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    protected static function mainFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            TextInput::make('articule')
                ->label('Артикул')
                ->maxLength(255),

            TextInput::make('external_id')
                ->label('Внешний ID')
                ->maxLength(64)
                ->helperText('Идентификатор из Prom.ua — по нему импорт находит этот товар.'),

            TextInput::make('brand')
                ->label('Бренд')
                ->maxLength(255)
                ->datalist(fn () => self::distinctValues('brand')),

            TextInput::make('country')
                ->label('Страна производитель')
                ->maxLength(100)
                ->datalist(fn () => self::distinctValues('country')),

            Select::make('availability')
                ->label('Наличие')
                ->options(ShopOptions::AVAILABILITY)
                ->default('in_stock')
                ->required()
                ->native(false)
                ->helperText('Товары не «в наличии» скрыты из каталога витрины.'),

            Select::make('condition_item')
                ->label('Состояние')
                ->options(ShopOptions::CONDITION)
                ->default('new')
                ->native(false),

            TextInput::make('weight')
                ->label('Вес')
                ->numeric()
                ->minValue(0)
                ->step(0.001)
                ->suffix('кг'),

            TextInput::make('url')
                ->label('URL (ЧПУ)')
                ->disabled()
                ->dehydrated(false)
                ->columnSpanFull()
                ->helperText('Генерируется автоматически из названия при каждом сохранении.'),

            Textarea::make('description')
                ->label('Описание')
                ->rows(8)
                ->columnSpanFull()
                ->nullable()
                ->formatStateUsing(fn ($state): string => is_string($state) ? $state : '')
                ->dehydrateStateUsing(fn ($state): string => is_string($state) ? $state : '')
                ->helperText('Можно HTML. Rich-editor отключён: TipTap падал на описаниях из Prom.ua.'),

            Textarea::make('complectation')
                ->label('Комплектация')
                ->rows(3)
                ->columnSpanFull(),

            Textarea::make('admin_notes')
                ->label('Заметки')
                ->rows(2)
                ->columnSpanFull()
                ->helperText('Видны только в админке, на витрину не попадают.'),

            Section::make('Русская версия')
                ->description('Приходит из выгрузки Prom.ua. Витрина её не показывает, но экспорт сохраняет.')
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name_ru')
                        ->label('Название (рус.)')
                        ->maxLength(255),

                    Textarea::make('description_ru')
                        ->label('Описание (рус.)')
                        ->rows(6)
                        ->nullable()
                        ->formatStateUsing(fn ($state): string => is_string($state) ? $state : '')
                        ->dehydrateStateUsing(fn ($state): string => is_string($state) ? $state : ''),
                ]),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected static function distinctValues(string $column): array
    {
        return Product::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    protected static function priceFields(): array
    {
        $rate = \App\Services\ShopSettings::usdRate();

        return [
            TextInput::make('price_usd')
                ->label('Цена (USD)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->suffix('$')
                ->nullable()
                ->helperText('Если указана — цена в грн пересчитается по курсу '.$rate.' ₴/$'),

            TextInput::make('price')
                ->label('Цена (UAH, на сайте)')
                ->numeric()
                ->minValue(0)
                ->required()
                ->suffix('₴')
                ->helperText('На витрине всегда показывается эта цена в гривнах'),

            TextInput::make('discount')
                ->label('Скидка')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(0)
                ->required()
                ->suffix('%')
                ->live(onBlur: true)
                ->dehydrateStateUsing(fn ($state): int => max(0, min(100, (int) ($state ?? 0)))),

            DatePicker::make('discount_starts_at')
                ->label('Скидка действует с')
                ->native(false)
                ->displayFormat('d.m.Y')
                ->visible(fn (Get $get): bool => (int) ($get('discount') ?? 0) > 0),

            DatePicker::make('discount_ends_at')
                ->label('Скидка действует до')
                ->native(false)
                ->displayFormat('d.m.Y')
                ->afterOrEqual('discount_starts_at')
                ->visible(fn (Get $get): bool => (int) ($get('discount') ?? 0) > 0)
                ->helperText('Справочные даты: витрина считает скидку постоянной, пока её не убрать вручную.'),

            TextInput::make('unit_name')
                ->label('Единица измерения')
                ->default('шт')
                ->maxLength(50),

            TextInput::make('unit_name_plural')
                ->label('Единица (мн. число)')
                ->maxLength(50),

            TextInput::make('min_order_quantity')
                ->label('Минимальный заказ')
                ->numeric()
                ->minValue(1)
                ->suffix(fn (Get $get): string => (string) ($get('unit_name') ?: 'шт')),

            Toggle::make('is_wholesale')
                ->label('Оптовый товар')
                ->live()
                ->columnSpanFull(),

            TextInput::make('wholesale_price_usd')
                ->label('Оптовая цена (USD)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->suffix('$')
                ->nullable()
                ->visible(fn (Get $get): bool => (bool) $get('is_wholesale')),

            TextInput::make('wholesale_price')
                ->label('Оптовая цена (UAH)')
                ->numeric()
                ->minValue(0)
                ->suffix('₴')
                ->visible(fn (Get $get): bool => (bool) $get('is_wholesale'))
                ->requiredIf('is_wholesale', true),

            TextInput::make('wholesale_min_quantity')
                ->label('Минимальный опт. заказ')
                ->numeric()
                ->minValue(1)
                ->visible(fn (Get $get): bool => (bool) $get('is_wholesale')),

            TextInput::make('units_per_box')
                ->label('Единиц в упаковке')
                ->numeric()
                ->minValue(1)
                ->visible(fn (Get $get): bool => (bool) $get('is_wholesale')),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function imageFields(): array
    {
        return [
            ShopImageUpload::make('image_path')
                ->label('Главное изображение')
                ->columnSpanFull()
                ->helperText('Загружается на BunnyCDN, если он настроен, иначе в локальное хранилище.'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function characteristicsFields(): array
    {
        return [
            Repeater::make('characteristics')
                ->label('Характеристики')
                ->table([
                    TableColumn::make('Название'),
                    TableColumn::make('Значение'),
                    TableColumn::make('Ед. измерения'),
                ])
                ->schema([
                    // Обязательности нет намеренно: кнопка «Добавить из шаблона»
                    // подставляет строки без значений, как список для заполнения.
                    TextInput::make('name')
                        ->placeholder('Колір')
                        ->datalist(fn (?Product $record): array => self::templateCharacteristicNames($record)),

                    TextInput::make('value')
                        ->placeholder('Білий'),

                    TextInput::make('unit')
                        ->placeholder('г'),
                ])
                ->addActionLabel('Добавить характеристику')
                ->reorderable()
                ->columnSpanFull()
                ->afterStateHydrated(fn (Repeater $component, mixed $state) => $component->state(
                    Product::normalizeCharacteristics($state),
                ))
                ->helperText(fn (?Product $record): string => 'Несколько значений одной характеристики разделяйте знаком «|»: «Від мережі|USB». '
                    .'Строки без названия или значения не сохраняются. '
                    .self::templateHint($record))
                ->hintAction(
                    Action::make('fillFromTemplate')
                        ->label('Добавить из шаблона')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->visible(fn (?Product $record): bool => self::templateCharacteristicNames($record) !== [])
                        ->action(function (Repeater $component, ?Product $record): void {
                            $component->state(self::mergeTemplateCharacteristics(
                                $component->getState(),
                                self::templateCharacteristicNames($record),
                            ));
                        }),
                ),

            KeyValue::make('modifications')
                ->label('Модификации')
                ->keyLabel('Название')
                ->valueLabel('Значение')
                ->columnSpanFull(),

            KeyValue::make('additional_fields')
                ->label('Дополнительные поля')
                ->keyLabel('Название')
                ->valueLabel('Значение')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function relationFields(): array
    {
        return [
            Select::make('categories')
                ->label('Категории')
                ->relationship('categories', 'name')
                ->getOptionLabelFromRecordUsing(fn (Category $record): string => ShopOptions::categoryLabel($record->id, $record->name))
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),

            Select::make('catalogs')
                ->label('Каталоги')
                ->relationship('catalogs', 'name')
                ->getOptionLabelFromRecordUsing(fn (Catalog $record): string => ShopOptions::catalogLabel($record->id, $record->name))
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),

            Select::make('packages')
                ->label('Упаковки / доп. блоки')
                ->relationship('packages', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function seoFields(): array
    {
        return [
            TextInput::make('seo_title')
                ->label('SEO заголовок')
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('seo_description')
                ->label('SEO описание')
                ->rows(3)
                ->columnSpanFull(),

            Textarea::make('seo_keywords')
                ->label('SEO ключевые слова')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * Названия характеристик из шаблона категории или каталога — витрина
     * показывает их, если у товара нет своих.
     *
     * @return array<int, string>
     */
    protected static function templateCharacteristicNames(?Product $record): array
    {
        if (! $record) {
            return [];
        }

        return collect($record->getTemplateCharacteristics())
            ->map(fn ($characteristic) => is_array($characteristic)
                ? ($characteristic['name'] ?? $characteristic['key'] ?? null)
                : $characteristic)
            ->filter()
            ->map(fn ($name): string => (string) $name)
            ->unique()
            ->values()
            ->all();
    }

    protected static function templateHint(?Product $record): string
    {
        $names = self::templateCharacteristicNames($record);

        return $names === []
            ? 'Характеристики попадают в карточку товара как есть.'
            : 'Шаблон предлагает: '.implode(', ', $names).'.';
    }

    /**
     * Добавляет к уже заполненным характеристикам пустые строки для тех
     * названий из шаблона, которых ещё нет.
     *
     * @param  array<mixed>  $state
     * @param  array<int, string>  $templateNames
     * @return array<int, array{name: string, value: string, unit: string|null}>
     */
    protected static function mergeTemplateCharacteristics(array $state, array $templateNames): array
    {
        $characteristics = collect($state)
            ->map(fn ($row): array => [
                'name' => trim((string) ($row['name'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
                'unit' => ($row['unit'] ?? null) === '' ? null : $row['unit'] ?? null,
            ])
            ->filter(fn (array $row): bool => $row['name'] !== '')
            ->values();

        $existing = $characteristics->map(fn (array $row): string => mb_strtolower($row['name']))->all();

        foreach ($templateNames as $name) {
            if (! in_array(mb_strtolower($name), $existing, true)) {
                $characteristics->push(['name' => $name, 'value' => '', 'unit' => null]);
            }
        }

        return $characteristics->all();
    }
}
