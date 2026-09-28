<?php

namespace App\Filament\Forms\Components;

use App\Helpers\FileUploadHelper;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Один источник фото: либо файл на BunnyCDN, либо внешняя ссылка.
 */
final class ShopImageSourceFields
{
    /**
     * @return array<int, mixed>
     */
    public static function make(string $target, string $label = 'Фото'): array
    {
        $modeKey = $target.'_source';
        $fileKey = $target.'_cdn';
        $urlKey = $target.'_link';

        return [
            Radio::make($modeKey)
                ->label($label)
                ->options([
                    'cdn' => 'Загрузить новое фото на CDN',
                    'link' => 'Вставить ссылку на фото',
                ])
                ->default('cdn')
                ->inline()
                ->live()
                ->dehydrated(false)
                ->afterStateHydrated(function (Radio $component, mixed $state, Get $get) use ($target): void {
                    $current = trim((string) $get($target));
                    if ($current === '') {
                        $component->state('cdn');

                        return;
                    }

                    $component->state(FileUploadHelper::isExternalUrl($current) ? 'link' : 'cdn');
                })
                ->columnSpanFull(),

            ShopImageUpload::make($fileKey)
                ->label('Файл на CDN')
                ->visible(fn (Get $get): bool => ($get($modeKey) ?? 'cdn') === 'cdn')
                ->required(fn (Get $get): bool => ($get($modeKey) ?? 'cdn') === 'cdn')
                ->dehydrated(false)
                ->afterStateHydrated(function (ShopImageUpload $component, mixed $state, Get $get) use ($modeKey, $target): void {
                    $current = trim((string) $get($target));
                    if ($current === '' || FileUploadHelper::isExternalUrl($current)) {
                        return;
                    }
                    if (($get($modeKey) ?? 'cdn') === 'cdn') {
                        $component->state($current);
                    }
                })
                ->afterStateUpdated(function (mixed $state, Set $set) use ($target, $fileKey): void {
                    $file = is_array($state) ? ($state[0] ?? null) : $state;

                    if ($file instanceof TemporaryUploadedFile) {
                        $url = FileUploadHelper::uploadFile($file, 'products');
                        if (is_string($url) && $url !== '') {
                            $set($fileKey, $url);
                            $set($target, $url);
                        }

                        return;
                    }

                    $value = self::normalizeUploadState($state);
                    $set($target, $value);
                })
                ->helperText('Выберите этот вариант, чтобы загрузить новый файл на BunnyCDN.')
                ->columnSpanFull(),

            TextInput::make($urlKey)
                ->label('Ссылка на фото')
                ->placeholder('https://example.com/photo.jpg')
                ->url()
                ->visible(fn (Get $get): bool => ($get($modeKey) ?? 'cdn') === 'link')
                ->required(fn (Get $get): bool => ($get($modeKey) ?? 'cdn') === 'link')
                ->dehydrated(false)
                ->afterStateHydrated(function (TextInput $component, mixed $state, Get $get) use ($modeKey, $target): void {
                    $current = trim((string) $get($target));
                    if ($current !== '' && ($get($modeKey) ?? '') === 'link') {
                        $component->state($current);
                    }
                })
                ->afterStateUpdated(function (mixed $state, Set $set) use ($target): void {
                    $value = trim((string) $state);
                    $set($target, $value !== '' ? $value : null);
                })
                ->helperText('Внешний URL (импорт и т.п.) — на CDN не загружается.')
                ->columnSpanFull(),

            Hidden::make($target)
                ->dehydrated(true)
                ->dehydrateStateUsing(function (mixed $state, Get $get) use ($modeKey, $fileKey, $urlKey): ?string {
                    $mode = $get($modeKey) ?? 'cdn';

                    if ($mode === 'link') {
                        $url = trim((string) $get($urlKey));

                        return $url !== '' ? $url : null;
                    }

                    return self::normalizeUploadState($get($fileKey))
                        ?? (is_string($state) && trim($state) !== '' ? trim($state) : null);
                })
                ->rules([
                    fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get, $modeKey, $fileKey, $urlKey): void {
                        $mode = $get($modeKey) ?? 'cdn';

                        if ($mode === 'link') {
                            if (trim((string) $get($urlKey)) === '') {
                                $fail('Вставьте ссылку на фото.');
                            }

                            return;
                        }

                        if (self::normalizeUploadState($get($fileKey)) === null && trim((string) $value) === '') {
                            $fail('Загрузите фото на CDN.');
                        }
                    },
                ]),
        ];
    }

    public static function normalizeUploadState(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = $state[0] ?? null;
        }

        if (! is_string($state)) {
            return null;
        }

        $state = trim($state);

        return $state !== '' ? $state : null;
    }
}
