<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Support\ShopOptions;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
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
                    ->description(fn (Category $record): string => ShopOptions::categoryLabel($record->id, $record->name)),

                TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parentCategory.name')
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

                TextColumn::make('child_categories_count')
                    ->counts('childCategories')
                    ->label('Подкатегорий')
                    ->badge()
                    ->color('gray'),

                ToggleColumn::make('is_active')
                    ->label('Активна'),

                TextColumn::make('sort_order')
                    ->label('Сортировка')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность'),

                SelectFilter::make('template_id')
                    ->label('Шаблон')
                    ->relationship('template', 'name')
                    ->preload(),

                SelectFilter::make('parent_id')
                    ->label('Родитель')
                    ->options(fn (): array => ShopOptions::categories())
                    ->searchable(),

                Filter::make('roots_only')
                    ->label('Только корневые')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),

                Filter::make('empty')
                    ->label('Без товаров')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('products')),
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
