<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'meta_image',
        'characteristics',
        'modifications',
        'additional_fields',
        'is_active',
    ];

    protected $casts = [
        'characteristics' => 'array',
        'modifications' => 'array',
        'additional_fields' => 'array',
        'is_active' => 'boolean',
    ];

    // Отношения
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function catalogs()
    {
        return $this->hasMany(Catalog::class);
    }

    // Автоматическая генерация slug
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($template) {
            if (empty($template->slug)) {
                $template->slug = self::generateSlug($template->name);
            }
        });
    }

    public static function generateSlug($text)
    {
        $text = mb_strtolower($text);

        $text = str_replace(
            ['а', 'б', 'в', 'г', 'ґ', 'д', 'е', 'є', 'ж', 'з', 'и', 'і', 'ї', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ь', 'ю', 'я'],
            ['a', 'b', 'v', 'g', 'g', 'd', 'e', 'ye', 'zh', 'z', 'y', 'i', 'yi', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'ts', 'ch', 'sh', 'shch', '', 'yu', 'ya'],
            $text
        );

        $text = preg_replace('/[^\w\-]+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }
}
