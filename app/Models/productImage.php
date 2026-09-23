<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'src', 'product_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Получить путь к изображению (с поддержкой CDN)
    public function getImagePath()
    {
        if (!$this->src) {
            return asset('dist/img/no-image.png'); // Fallback изображение
        }

        // Если путь уже содержит полный URL (например, из CDN)
        if (str_starts_with($this->src, 'http://') || str_starts_with($this->src, 'https://')) {
            return $this->src;
        }

        // Проверяем, есть ли изображение в локальном хранилище
        $localPath = storage_path('app/public/' . $this->src);
        if (file_exists($localPath)) {
            return asset('storage/' . $this->src);
        }

        // Если изображение на CDN (BunnyCDN)
        if (config('app.cdn_url')) {
            return config('app.cdn_url') . '/' . $this->src;
        }

        // Fallback на storage
        return asset('storage/' . $this->src);
    }
}
