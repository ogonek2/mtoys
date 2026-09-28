<?php

namespace App\Filament\Forms\Components;

use App\Helpers\FileUploadHelper;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Загрузка картинок товаров: BunnyCDN (полный URL) с fallback на disk public.
 */
class ShopImageUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->image()
            ->disk('public')
            ->directory('products')
            ->visibility('public')
            ->maxSize(8192)
            ->imageEditor()
            ->fetchFileInformation(false)
            ->getUploadedFileUsing(static function (string $file): ?array {
                return [
                    'name' => basename(parse_url($file, PHP_URL_PATH) ?: $file),
                    'size' => 0,
                    'type' => null,
                    'url' => FileUploadHelper::publicUrl($file),
                ];
            })
            ->saveUploadedFileUsing(static function (ShopImageUpload $component, TemporaryUploadedFile $file): ?string {
                return $component->storeImage($file);
            })
            ->deleteUploadedFileUsing(static function (ShopImageUpload $component, string $file): void {
                $component->forgetImage($file);
            });
    }

    protected function storeImage(TemporaryUploadedFile $file): ?string
    {
        $folder = trim($this->getDirectory() ?: 'products', '/');

        return FileUploadHelper::uploadFile($file, $folder);
    }

    protected function forgetImage(string $file): void
    {
        $cdn = FileUploadHelper::cdnBaseUrl();

        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            if ($cdn !== '' && str_starts_with($file, $cdn)) {
                FileUploadHelper::deleteFromBunnyCDN($file);
            }

            return;
        }

        $this->getDisk()->delete($file);
    }
}
