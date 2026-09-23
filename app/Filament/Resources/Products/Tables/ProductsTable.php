<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\ShopOptions;
use App\Models\Product;
use App\Services\Products\ProductDataQuality;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Фото')
                    ->getStateUsing(fn (Product $record): string => $record->getImagePath())
                    ->checkFileExistence(false)
                    ->square()
                    ->size(56),

                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(70)
                    ->description(fn (Product $record): ?string => $record->articule ? "Арт. {$record->articule}" : 'Без артикула'),

                TextColumn::make('price')
                    ->label('Цена')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn (Product $record): string => self::hasPrice($record) ? 'gray' : 'danger')
                    ->weight(fn (Product $record): string => self::hasPrice($record) ? 'normal' : 'bold')
                    ->formatStateUsing(function (Product $record): string {
                        if (! self::hasPrice($record)) {
                            return 'Уточняется';
                        }

                        return number_format((float) $record->price, 2, '.', ' ').' ₴';
                    }),

                TextColumn::make('discount')
                    ->label('Скидка')
                    ->badge()
                    ->sortable()
                    ->color(fn (?int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => $state > 0 ? "{$state}%" : '—'),

                TextColumn::make('availability')
                    ->label('Наличие')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state): string => $state === 'in_stock' ? 'success' : 'danger')
                    ->formatStateUsing(fn (?string $state): string => ShopOptions::AVAILABILITY[$state] ?? (string) $state),

                IconColumn::make('is_wholesale')
                    ->label('Опт')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('categories_count')
                    ->counts('categories')
                    ->label('Категорий')
                    ->badge()
                    ->color(fn (?int $state): string => ($state ?? 0) > 0 ? 'info' : 'danger')
                    ->formatStateUsing(fn (?int $state): string => ($state ?? 0) > 0 ? (string) $state : 'Нет'),

                TextColumn::make('data_flags')
                    ->label('Пробелы')
                    ->badge()
                    ->separator(',')
                    ->getStateUsing(fn (Product $record): array => self::missingFlags($record))
                    ->color('warning')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('images_count')
                    ->counts('images')
                    ->label('Галерея')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('brand')
                    ->label('Бренд')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('articule')
                    ->label('Артикул')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('external_id')
                    ->label('Внешний ID')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('country')
                    ->label('Страна')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('weight')
                    ->label('Вес, кг')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('characteristics')
                    ->label('Характеристик')
                    ->badge()
                    ->color(fn (Product $record): string => count($record->characteristicsList()) > 0 ? 'info' : 'warning')
                    ->state(fn (Product $record): int => count($record->characteristicsList()))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('without_price')
                    ->label('Без цены')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ProductDataQuality::withoutPrice($query)),

                Filter::make('without_image')
                    ->label('Без фото / заглушка')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ProductDataQuality::withoutImage($query)),

                Filter::make('without_category')
                    ->label('Без категории')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ProductDataQuality::withoutCategory($query)),

                Filter::make('without_description')
                    ->label('Без описания')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ProductDataQuality::withoutDescription($query)),

                Filter::make('without_articule')
                    ->label('Без артикула')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ProductDataQuality::withoutArticule($query)),

                Filter::make('without_brand')
                    ->label('Без бренда')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ProductDataQuality::withoutBrand($query)),

                Filter::make('without_characteristics')
                    ->label('Без характеристик')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ProductDataQuality::withoutCharacteristics($query)),

                Filter::make('without_catalog')
                    ->label('Без каталога')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => ProductDataQuality::withoutCatalog($query)),

                Filter::make('without_seo')
                    ->label('Без SEO')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $builder): void {
                        $builder
                            ->where(fn (Builder $q) => $q->whereNull('seo_title')->orWhere('seo_title', ''))
                            ->orWhere(fn (Builder $q) => $q->whereNull('seo_description')->orWhere('seo_description', ''));
                    })),

                Filter::make('without_external_id')
                    ->label('Без внешнего ID')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $builder): void {
                        $builder->whereNull('external_id')->orWhere('external_id', '');
                    })),

                SelectFilter::make('availability')
                    ->label('Наличие')
                    ->options(ShopOptions::AVAILABILITY),

                TernaryFilter::make('is_wholesale')
                    ->label('Оптовый товар'),

                SelectFilter::make('categories')
                    ->label('Категория')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('catalogs')
                    ->label('Каталог')
                    ->relationship('catalogs', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('brand')
                    ->label('Бренд')
                    ->options(fn (): array => Product::query()
                        ->whereNotNull('brand')
                        ->where('brand', '!=', '')
                        ->distinct()
                        ->orderBy('brand')
                        ->pluck('brand', 'brand')
                        ->all())
                    ->searchable(),

                Filter::make('has_discount')
                    ->label('Со скидкой')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('discount', '>', 0)),

                SelectFilter::make('country')
                    ->label('Страна')
                    ->options(fn (): array => Product::query()
                        ->whereNotNull('country')
                        ->where('country', '!=', '')
                        ->distinct()
                        ->orderBy('country')
                        ->pluck('country', 'country')
                        ->all())
                    ->searchable(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->persistFiltersInSession()
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Дублировать')
                    ->excludeAttributes(['url'])
                    ->beforeReplicaSaved(function (Product $replica): void {
                        $replica->name = $replica->name.' (копия)';
                        $replica->articule = $replica->articule ? $replica->articule.'-copy' : null;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('setAvailability')
                        ->label('Изменить наличие')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Select::make('availability')
                                ->label('Наличие')
                                ->options(ShopOptions::AVAILABILITY)
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            self::updateWithoutFeedRebuild($records, ['availability' => $data['availability']]);
                            ProductDataQuality::forgetCache();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('setDiscount')
                        ->label('Изменить скидку')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            TextInput::make('discount')
                                ->label('Скидка, %')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            self::updateWithoutFeedRebuild($records, ['discount' => (int) $data['discount']]);
                            ProductDataQuality::forgetCache();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->after(fn () => ProductDataQuality::forgetCache()),
                ]),
            ]);
    }

    protected static function hasPrice(Product $record): bool
    {
        if ($record->price === null || $record->price === '') {
            return false;
        }

        return (float) $record->price > 0;
    }

    /**
     * @return array<int, string>
     */
    protected static function missingFlags(Product $record): array
    {
        $flags = [];

        if (! self::hasPrice($record)) {
            $flags[] = 'цена';
        }

        $image = trim((string) ($record->image_path ?? ''));
        if ($image === '' || str_contains(mb_strtolower($image), 'no-image')) {
            $flags[] = 'фото';
        }

        if ((int) ($record->categories_count ?? 0) === 0) {
            $flags[] = 'категория';
        }

        if (trim((string) ($record->description ?? '')) === '') {
            $flags[] = 'описание';
        }

        if (trim((string) ($record->articule ?? '')) === '') {
            $flags[] = 'артикул';
        }

        return $flags;
    }

    /**
     * Массовое обновление без событий модели: иначе каждая запись
     * заново собирает URL и фид товаров.
     *
     * @param  Collection<int, Product>  $records
     * @param  array<string, mixed>  $attributes
     */
    protected static function updateWithoutFeedRebuild(Collection $records, array $attributes): void
    {
        Product::withoutEvents(function () use ($records, $attributes): void {
            Product::query()->whereKey($records->modelKeys())->update($attributes);
        });
    }
}
