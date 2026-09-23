<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'parent_id',
        'template_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'meta_image',
        'description',
        'is_active',
        'sort_order',
        'type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Отношения
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    // Иерархические отношения (родитель-потомок)
    public function parentCatalog()
    {
        return $this->belongsTo(Catalog::class, 'parent_id');
    }

    public function childCatalogs()
    {
        return $this->hasMany(Catalog::class, 'parent_id');
    }

    // Рекурсивное получение всех потомков
    public function allChildren()
    {
        return $this->childCatalogs()->with('allChildren');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($catalog) {
            if (empty($catalog->url)) {
                $catalog->url = self::generateHref($catalog->name);
            }
        });
    }

    // Метод для генерации href
    public static function generateHref($text)
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
