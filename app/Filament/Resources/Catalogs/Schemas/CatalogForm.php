<?php

namespace App\Filament\Resources\Catalogs\Schemas;

use App\Filament\Forms\Components\ShopImageUpload;
use App\Filament\Support\ShopOptions;
use App\Models\Catalog;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CatalogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Каталог')
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
                            ->label('Родительский каталог')
                            ->options(fn (?Catalog $record): array => ShopOptions::catalogs($record?->id))
                            ->searchable()
                            ->placeholder('Корневой каталог')
                            ->native(false),

                        Select::make('type')
                            ->label('Тип')
                            ->options(ShopOptions::CATALOG_TYPE)
                            ->default('group')
                            ->required()
                            ->native(false),

                        Select::make('template_id')
                            ->label('Шаблон')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Без шаблона')
                            ->native(false),

                        Toggle::make('is_active')
                            ->label('Активен')
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
                            ->label('Изображение каталога')
                            ->directory('catalogs')
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
