<?php

namespace App\Filament\Forms\Components;

use App\Helpers\FileUploadHelper;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Загрузка картинок в том же формате, в котором их хранит витрина:
 * BunnyCDN отдаёт полный URL, локальное хранилище — путь относительно
 * диска `public`. Оба варианта понимают Product::getImagePath()
 * и productImage::getImagePath().
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
            // В базе уже лежат внешние URL (CDN, picsum), для них нельзя
            // спрашивать размер/mime у локального диска.
            ->fetchFileInformation(false)
            ->getUploadedFileUsing(static function (string $file): ?array {
                return [
                    'name' => basename(parse_url($file, PHP_URL_PATH) ?: $file),
                    'size' => 0,
                    'type' => null,
                    'url' => self::resolveUrl($file),
                ];
            })
            ->saveUploadedFileUsing(static function (ShopImageUpload $component, TemporaryUploadedFile $file): ?string {
                return $component->storeImage($file);
            })
            ->deleteUploadedFileUsing(static function (ShopImageUpload $component, string $file): void {
                $component->forgetImage($file);
            });
    }

    public static function resolveUrl(string $file): string
    {
        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            return $file;
        }

        if ($cdnUrl = config('app.cdn_url')) {
            if (! Storage::disk('public')->exists($file)) {
                return rtrim($cdnUrl, '/') . '/' . ltrim($file, '/');
            }
        }

        return Storage::disk('public')->url($file);
    }

    protected function storeImage(TemporaryUploadedFile $file): ?string
    {
        $folder = trim($this->getDirectory() ?: 'products', '/');

        if ($cdnUrl = FileUploadHelper::uploadToBunnyCDN($file, $folder)) {
            return $cdnUrl;
        }

        return $file->storeAs(
            $folder,
            $this->getUploadedFileNameForStorage($file),
            $this->getDiskName(),
        ) ?: null;
    }

    /**
     * Удаляем только то, что залито через админку: локальные файлы и объекты
     * на нашем CDN. Сторонние URL просто отвязываются от записи.
     */
    protected function forgetImage(string $file): void
    {
        $bunnyCdnUrl = env('BUNNY_CDN_URL');

        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            if ($bunnyCdnUrl && str_starts_with($file, rtrim($bunnyCdnUrl, '/'))) {
                FileUploadHelper::deleteFromBunnyCDN($file);
            }

            return;
        }

        $this->getDisk()->delete($file);
    }
}
