<?php

namespace App\Filament\Resources\Templates\Schemas;

use App\Filament\Forms\Components\ShopImageUpload;
use App\Models\Template;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Шаблон')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Основное')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Название')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Если оставить пустым — сгенерируется из названия.'),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true),

                                Textarea::make('description')
                                    ->label('Описание')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make('Характеристики')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Repeater::make('characteristics')
                                    ->label('Характеристики')
                                    ->helperText('Список полей, которые витрина предлагает заполнить у товаров этой категории.')
                                    ->afterStateHydrated(fn (Repeater $component, mixed $state) => $component->state(self::normalizeCharacteristics($state)))
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Название')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                                                if (blank($get('key')) && filled($state)) {
                                                    $set('key', Template::generateSlug($state));
                                                }
                                            }),

                                        TextInput::make('key')
                                            ->label('Ключ')
                                            ->helperText('Латиницей, используется в данных товара.'),

                                        TextInput::make('default_value')
                                            ->label('Значение по умолчанию'),
                                    ])
                                    ->columns(3)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Модификации')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Repeater::make('modifications')
                                    ->label('Модификации')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Название')
                                            ->required(),

                                        TagsInput::make('options')
                                            ->label('Варианты')
                                            ->placeholder('Добавить вариант'),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Доп. поля')
                            ->icon('heroicon-o-queue-list')
                            ->schema([
                                Repeater::make('additional_fields')
                                    ->label('Дополнительные поля')
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Подпись')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                                                if (blank($get('key')) && filled($state)) {
                                                    $set('key', Template::generateSlug($state));
                                                }
                                            }),

                                        TextInput::make('key')
                                            ->label('Ключ'),

                                        Select::make('type')
                                            ->label('Тип')
                                            ->options([
                                                'text' => 'Текст',
                                                'number' => 'Число',
                                                'boolean' => 'Да / Нет',
                                            ])
                                            ->default('text')
                                            ->native(false),
                                    ])
                                    ->columns(3)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('SEO заголовок')
                                            ->maxLength(255),

                                        Textarea::make('seo_description')
                                            ->label('SEO описание')
                                            ->rows(3),

                                        Textarea::make('seo_keywords')
                                            ->label('SEO ключевые слова')
                                            ->rows(2),

                                        ShopImageUpload::make('meta_image')
                                            ->label('OG-изображение')
                                            ->directory('templates'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * В базе характеристики шаблонов лежат и плоским списком строк,
     * и структурой {key, name, default_value}. Приводим к структуре —
     * её ожидает SpaPageService при сборке карточки товара.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function normalizeCharacteristics(mixed $state): array
    {
        return collect(Arr::wrap($state))
            ->map(function (mixed $item): array {
                if (is_array($item)) {
                    $name = $item['name'] ?? $item['key'] ?? '';

                    return [
                        'name' => $name,
                        'key' => $item['key'] ?? Template::generateSlug((string) $name),
                        'default_value' => $item['default_value'] ?? null,
                    ];
                }

                return [
                    'name' => (string) $item,
                    'key' => Template::generateSlug((string) $item),
                    'default_value' => null,
                ];
            })
            ->values()
            ->all();
    }
}
