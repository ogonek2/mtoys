<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Колонки для лістингів. SELECT * + ORDER BY sort_order, name
     * на хостингу mysql.tools дає SQLSTATE[HY093].
     */
    public const LISTING_COLUMNS = [
        'id',
        'name',
        'url',
        'parent_id',
        'template_id',
        'is_active',
        'sort_order',
        'meta_image',
        'description',
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
    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function childCategories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Рекурсивное получение всех потомков
    public function allChildren()
    {
        return $this->childCategories()->with('allChildren');
    }

    // Получить всех родителей
    public function getParents()
    {
        $parents = collect();
        $parent = $this->parentCategory;
        
        while ($parent) {
            $parents->prepend($parent);
            $parent = $parent->parentCategory;
        }
        
        return $parents;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($category) {
            if (empty($category->url)) {
                $category->url = self::generateHref($category->name);
            }
        });

        static::saved(fn () => forget_mega_menu_cache());
        static::deleted(fn () => forget_mega_menu_cache());
    }

    // Метод для генерации href
    public static function generateHref($text)
    {
        $text = mb_strtolower($text);

        $text = str_replace(
            ['а','б','в','г','ґ','д','е','є','ж','з','и','і','ї','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ь','ю','я'],
            ['a','b','v','g','g','d','e','ye','zh','z','y','i','yi','y','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','shch','','yu','ya'],
            $text
        );

        $text = preg_replace('/[^\w\-]+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }
}
