<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Forms\Components\ShopImageUpload;
use App\Filament\Support\ShopOptions;
use App\Models\Category;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Категория')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('url')
                            ->label('URL (ЧПУ)')
                            ->maxLength(255)
                            ->helperText('Если оставить пустым — сгенерируется из названия.')
                            ->columnSpanFull(),

                        Select::make('parent_id')
                            ->label('Родительская категория')
                            ->options(fn (?Category $record): array => ShopOptions::categories($record?->id))
                            ->searchable()
                            ->placeholder('Корневая категория')
                            ->native(false),

                        Select::make('template_id')
                            ->label('Шаблон')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Без шаблона')
                            ->native(false)
                            ->helperText('Задаёт набор характеристик для товаров категории.'),

                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0),
                    ]),

                Section::make('Контент')
                    ->collapsible()
                    ->schema([
                        RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),

                        ShopImageUpload::make('meta_image')
                            ->label('Изображение категории')
                            ->directory('categories')
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO')
                    ->collapsed()
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
                    ]),
            ]);
    }
}
