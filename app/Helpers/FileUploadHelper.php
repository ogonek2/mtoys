<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class FileUploadHelper
{
    /**
     * Публичный URL картинки для витрины / админки.
     *
     * - http(s) / //… — внешние ссылки импорта, отдаём как есть (не через Bunny);
     * - products/… — локально или BunnyCDN;
     * - пусто — fallback.
     */
    public static function publicUrl(?string $path, ?string $fallback = null): string
    {
        $fallback ??= asset('dist/img/no-image.png');
        $path = trim((string) $path);

        if ($path === '') {
            return $fallback;
        }

        // Protocol-relative CDN/host from imports
        if (str_starts_with($path, '//')) {
            return 'https:'.$path;
        }

        // Absolute external or own CDN URL — never rewrite foreign hosts
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $relative = ltrim($path, '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        $localRelative = $relative;
        if (Storage::disk('public')->exists($localRelative)) {
            return Storage::disk('public')->url($localRelative);
        }

        $cdn = self::cdnBaseUrl();
        if ($cdn !== '') {
            return $cdn.'/'.$relative;
        }

        return asset('storage/'.$relative);
    }

    public static function isExternalUrl(?string $path): bool
    {
        $path = trim((string) $path);
        if ($path === '') {
            return false;
        }

        if (str_starts_with($path, '//')) {
            return true;
        }

        if (! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
            return false;
        }

        $cdn = self::cdnBaseUrl();

        return $cdn === '' || ! str_starts_with($path, $cdn);
    }

    public static function cdnBaseUrl(): string
    {
        return rtrim((string) config('services.bunny.cdn_url', config('app.cdn_url', '')), '/');
    }

    public static function isConfigured(): bool
    {
        return self::cdnBaseUrl() !== ''
            && (string) config('services.bunny.storage_name', '') !== ''
            && (string) config('services.bunny.storage_password', '') !== '';
    }

    public static function storageApiBaseUrl(): string
    {
        $region = strtolower(trim((string) config('services.bunny.region', '')));

        $hosts = [
            '' => 'https://storage.bunnycdn.com',
            'de' => 'https://storage.bunnycdn.com',
            'fs' => 'https://storage.bunnycdn.com',
            'falkenstein' => 'https://storage.bunnycdn.com',
            'uk' => 'https://uk.storage.bunnycdn.com',
            'ny' => 'https://ny.storage.bunnycdn.com',
            'la' => 'https://la.storage.bunnycdn.com',
            'sg' => 'https://sg.storage.bunnycdn.com',
            'se' => 'https://se.storage.bunnycdn.com',
            'br' => 'https://br.storage.bunnycdn.com',
            'jh' => 'https://jh.storage.bunnycdn.com',
            'syd' => 'https://syd.storage.bunnycdn.com',
        ];

        if (isset($hosts[$region])) {
            return $hosts[$region];
        }

        return 'https://'.$region.'.storage.bunnycdn.com';
    }

    /**
     * @param  UploadedFile|string  $file  UploadedFile или абсолютный путь к файлу
     */
    public static function uploadToBunnyCDN($file, string $folder = 'products'): ?string
    {
        try {
            if (! self::isConfigured()) {
                Log::error('BunnyCDN: отсутствуют BUNNY_* настройки в .env / config');

                return null;
            }

            if ($file instanceof UploadedFile) {
                $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
                $realPath = $file->getRealPath();
            } else {
                $realPath = (string) $file;
                $extension = pathinfo($realPath, PATHINFO_EXTENSION) ?: 'jpg';
            }

            if (! $realPath || ! is_readable($realPath)) {
                Log::error('BunnyCDN: файл недоступен для чтения', ['path' => $realPath]);

                return null;
            }

            $fileName = Str::random(20).'.'.strtolower($extension);
            $destinationPath = trim($folder, '/').'/'.$fileName;
            $fileContents = file_get_contents($realPath);
            if ($fileContents === false) {
                return null;
            }

            $url = self::storageApiBaseUrl().'/'.config('services.bunny.storage_name').'/'.$destinationPath;

            $response = Http::timeout(60)
                ->withHeaders([
                    'AccessKey' => (string) config('services.bunny.storage_password'),
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($fileContents, 'application/octet-stream')
                ->put($url);

            if (! $response->successful()) {
                Log::error('BunnyCDN upload failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $url,
                ]);

                return null;
            }

            $publicUrl = self::cdnBaseUrl().'/'.$destinationPath;
            Log::info('BunnyCDN upload ok', ['publicUrl' => $publicUrl]);

            return $publicUrl;
        } catch (Throwable $e) {
            Log::error('BunnyCDN upload exception: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return null;
        }
    }

    public static function deleteFromBunnyCDN(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '' || ! self::isConfigured()) {
            return false;
        }

        $cdn = self::cdnBaseUrl();
        $path = $url;

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            if ($cdn === '' || ! str_starts_with($url, $cdn)) {
                return false;
            }
            $parsed = parse_url($url);
            $path = ltrim((string) ($parsed['path'] ?? ''), '/');
        } else {
            $path = ltrim($url, '/');
        }

        if ($path === '') {
            return false;
        }

        try {
            return Http::timeout(30)
                ->withHeaders([
                    'AccessKey' => (string) config('services.bunny.storage_password'),
                ])
                ->delete(self::storageApiBaseUrl().'/'.config('services.bunny.storage_name').'/'.$path)
                ->successful();
        } catch (Throwable $e) {
            Log::error('BunnyCDN delete exception: '.$e->getMessage());

            return false;
        }
    }

    public static function uploadLocally(UploadedFile $file, string $folder = 'products'): ?string
    {
        try {
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = Str::random(20).'.'.strtolower($extension);
            $stored = $file->storeAs(trim($folder, '/'), $fileName, 'public');

            return $stored ? self::publicUrl($stored) : null;
        } catch (Throwable $e) {
            Log::error('Local upload error: '.$e->getMessage());

            return null;
        }
    }

    public static function uploadFile($file, string $folder = 'products'): ?string
    {
        $cdnUrl = self::uploadToBunnyCDN($file, $folder);
        if ($cdnUrl) {
            return $cdnUrl;
        }

        if ($file instanceof UploadedFile) {
            Log::warning('BunnyCDN недоступен, fallback на локальное хранилище');

            return self::uploadLocally($file, $folder);
        }

        return null;
    }
}
