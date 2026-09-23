<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class FileUploadHelper
{
    /**
     * Загружает файл на BunnyCDN и возвращает публичный URL.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder - папка для хранения, например 'products'
     * @return string|null - публичный URL или null при ошибке
     */
    public static function uploadToBunnyCDN($file, $folder = 'products')
    {
        try {
            // Проверяем наличие необходимых переменных окружения
            if (!env('BUNNY_STORAGE_NAME') || !env('BUNNY_STORAGE_PASSWORD') || !env('BUNNY_CDN_URL')) {
                \Log::error('Отсутствуют настройки BunnyCDN в .env файле');
                return null;
            }

            $extension = $file->getClientOriginalExtension();
            $fileName = Str::random(20) . '.' . $extension;
            $destinationPath = $folder . '/' . $fileName;

            $fileContents = file_get_contents($file->getRealPath());

            $url = "https://storage.bunnycdn.com/" . env('BUNNY_STORAGE_NAME') . "/$destinationPath";

            \Log::info('Попытка загрузки на BunnyCDN', [
                'url' => $url,
                'folder' => $folder,
                'fileName' => $fileName,
                'fileSize' => strlen($fileContents)
            ]);

            $response = Http::withHeaders([
                'AccessKey' => env('BUNNY_STORAGE_PASSWORD'),
                'Content-Type' => 'application/octet-stream',
            ])->withBody($fileContents, 'application/octet-stream')
                ->put($url);

            \Log::info('Ответ от BunnyCDN', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $publicUrl = rtrim(env('BUNNY_CDN_URL'), '/') . '/' . $destinationPath;
                \Log::info('Файл успешно загружен на BunnyCDN', ['publicUrl' => $publicUrl]);
                return $publicUrl;
            } else {
                \Log::error('Ошибка загрузки на BunnyCDN', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers()
                ]);
                return null;
            }

        } catch (\Exception $e) {
            \Log::error('Исключение при загрузке на BunnyCDN: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public static function deleteFromBunnyCDN($url)
    {
        $parsed = parse_url($url);
        $path = ltrim($parsed['path'], '/');

        return Http::withHeaders([
            'AccessKey' => env('BUNNY_STORAGE_PASSWORD'),
            'Content-Type' => 'application/octet-stream',
        ])->delete("https://storage.bunnycdn.com/" . env('BUNNY_STORAGE_NAME') . "/" . $path)->successful();
    }

    /**
     * Альтернативный метод загрузки файлов локально
     */
    public static function uploadLocally($file, $folder = 'products')
    {
        try {
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::random(20) . '.' . $extension;
            $destinationPath = "storage/app/public/$folder";
            
            // Создаем директорию если её нет
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $fullPath = $destinationPath . '/' . $fileName;
            
            if (move_uploaded_file($file->getRealPath(), $fullPath)) {
                $publicUrl = asset("storage/$folder/$fileName");
                \Log::info('Файл успешно загружен локально', ['publicUrl' => $publicUrl]);
                return $publicUrl;
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Ошибка локальной загрузки: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Универсальный метод загрузки файлов
     */
    public static function uploadFile($file, $folder = 'products')
    {
        // Сначала пробуем загрузить на CDN
        $cdnUrl = self::uploadToBunnyCDN($file, $folder);
        
        if ($cdnUrl) {
            return $cdnUrl;
        }
        
        // Если CDN недоступен, загружаем локально
        \Log::warning('CDN недоступен, используем локальную загрузку');
        return self::uploadLocally($file, $folder);
    }
}
