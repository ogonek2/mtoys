<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('value')
                    ->label('Значение')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
