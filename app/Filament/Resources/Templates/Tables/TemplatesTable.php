<?php

namespace App\Filament\Resources\Templates\Tables;

use App\Models\Template;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('characteristics')
                    ->label('Характеристик')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (Template $record): int => count($record->characteristics ?? [])),

                TextColumn::make('modifications')
                    ->label('Модификаций')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (Template $record): int => count($record->modifications ?? [])),

                TextColumn::make('categories_count')
                    ->counts('categories')
                    ->label('Категорий')
                    ->badge(),

                TextColumn::make('catalogs_count')
                    ->counts('catalogs')
                    ->label('Каталогов')
                    ->badge(),

                ToggleColumn::make('is_active')
                    ->label('Активен'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Дублировать')
                    ->excludeAttributes(['slug'])
                    ->beforeReplicaSaved(function (Template $replica): void {
                        $replica->name = $replica->name . ' (копия)';
                        $replica->slug = null;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
