<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Forms\Components\ShopImageUpload;
use App\Models\productImage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Дополнительные фото';

    protected static ?string $modelLabel = 'фото';

    protected static ?string $pluralModelLabel = 'Фото';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ShopImageUpload::make('src')
                    ->label('Изображение')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('src')
            ->columns([
                ImageColumn::make('src')
                    ->label('Превью')
                    ->getStateUsing(fn (productImage $record): string => $record->getImagePath())
                    ->checkFileExistence(false)
                    ->square()
                    ->size(80),

                TextColumn::make('src')
                    ->label('Путь / URL')
                    ->wrap()
                    ->limit(90)
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('Добавлено')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить фото'),
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
