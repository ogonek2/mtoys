<?php

namespace App\Filament\Resources\Catalogs\Tables;

use App\Filament\Support\ShopOptions;
use App\Models\Catalog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CatalogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Catalog $record): string => ShopOptions::catalogLabel($record->id, $record->name)),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ShopOptions::CATALOG_TYPE[$state] ?? (string) $state)
                    ->sortable(),

                TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parentCatalog.name')
                    ->label('Родитель')
                    ->placeholder('Корень')
                    ->sortable(),

                TextColumn::make('template.name')
                    ->label('Шаблон')
                    ->placeholder('—')
                    ->badge()
                    ->color('info'),

                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Товаров')
                    ->badge()
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Активен'),

                TextColumn::make('sort_order')
                    ->label('Сортировка')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность'),

                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(ShopOptions::CATALOG_TYPE),

                SelectFilter::make('template_id')
                    ->label('Шаблон')
                    ->relationship('template', 'name')
                    ->preload(),

                Filter::make('roots_only')
                    ->label('Только корневые')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
