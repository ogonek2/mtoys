<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\ProductFeedService;

class Product extends Model
{
    use HasFactory;

    /** Разделитель нескольких значений одной характеристики (формат Prom.ua). */
    public const CHARACTERISTIC_VALUE_SEPARATOR = '|';

    /** Значения-заглушки, которые не нужно показывать на витрине. */
    public const CHARACTERISTIC_PLACEHOLDERS = ['', '-', '—', 'Не вказано', 'не вказано', 'Не указано'];

    protected $fillable = [
        'name',
        'name_ru',
        'articule',
        'external_id',
        'description',
        'description_ru',
        'url',
        'discount',
        'discount_starts_at',
        'discount_ends_at',
        'price',
        'price_usd',
        'is_wholesale',
        'wholesale_price',
        'wholesale_price_usd',
        'wholesale_min_quantity',
        'units_per_box',
        'min_order_quantity',
        'unit_name',
        'unit_name_plural',
        'image_path',
        'complectation',
        'brand',
        'country',
        'weight',
        'condition_item',
        'availability',
        'admin_notes',
        'seo_title',
        'seo_keywords',
        'seo_description',
        'characteristics',
        'modifications',
        'additional_fields',
    ];

    protected $casts = [
        'characteristics' => 'array',
        'modifications' => 'array',
        'additional_fields' => 'array',
        'is_wholesale' => 'boolean',
        'wholesale_price' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'wholesale_price_usd' => 'decimal:2',
        'wholesale_min_quantity' => 'integer',
        'units_per_box' => 'integer',
        'min_order_quantity' => 'integer',
        'weight' => 'decimal:3',
        'discount_starts_at' => 'date',
        'discount_ends_at' => 'date',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
    
    public function catalogs()
    {
        return $this->belongsToMany(Catalog::class);
    }
    
    public function packages()
    {
        return $this->belongsToMany(\App\Models\package::class);
    }

    public function relations()
    {
        return $this->hasMany(ProductCategoryCatalogRelation::class);
    }
    
    public function images()
    {
        return $this->hasMany(\App\Models\productImage::class, 'product_id');
    }

    // Получить шаблон из связанной категории
    public function getCategoryTemplate()
    {
        $category = $this->categories()->with('template')->first();
        return $category?->template;
    }

    // Получить шаблон из связанного каталога
    public function getCatalogTemplate()
    {
        $catalog = $this->catalogs()->with('template')->first();
        return $catalog?->template;
    }

    // Получить активный шаблон (приоритет: каталог > категория)
    public function getActiveTemplate()
    {
        return $this->getCatalogTemplate() ?? $this->getCategoryTemplate();
    }

    // Получить характеристики из шаблона
    public function getTemplateCharacteristics()
    {
        $template = $this->getActiveTemplate();
        return $template?->characteristics ?? [];
    }

    // Получить модификации из шаблона
    public function getTemplateModifications()
    {
        $template = $this->getActiveTemplate();
        return $template?->modifications ?? [];
    }

    // Получить дополнительные поля из шаблона
    public function getTemplateAdditionalFields()
    {
        $template = $this->getActiveTemplate();
        return $template?->additional_fields ?? [];
    }

    /**
     * Характеристики товара в едином виде. Понимает и новый формат (список
     * строк с названием, значением и единицей измерения), и старый плоский
     * «название => значение», который остался в части товаров.
     *
     * @return array<int, array{name: string, value: string, unit: string|null}>
     */
    public static function normalizeCharacteristics(mixed $characteristics): array
    {
        if (is_string($characteristics)) {
            $characteristics = json_decode($characteristics, true);
        }

        if (! is_array($characteristics)) {
            return [];
        }

        $normalized = [];

        foreach ($characteristics as $key => $item) {
            if (is_array($item)) {
                $name = (string) ($item['name'] ?? $item['key'] ?? (is_string($key) ? $key : ''));
                $value = $item['value'] ?? $item['values'] ?? null;
                $unit = (string) ($item['unit'] ?? '');
            } else {
                $name = is_string($key) ? ucwords(str_replace(['_', '-'], ' ', $key)) : '';
                $value = $item;
                $unit = '';
            }

            if (is_array($value)) {
                $value = implode(self::CHARACTERISTIC_VALUE_SEPARATOR, array_map('strval', $value));
            }

            $name = trim($name);
            $value = trim((string) $value);

            if ($name === '' || in_array($value, self::CHARACTERISTIC_PLACEHOLDERS, true)) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'value' => $value,
                'unit' => trim($unit) === '' ? null : trim($unit),
            ];
        }

        return $normalized;
    }

    /**
     * Любая запись характеристик приводится к новому формату: и форма админки,
     * и импорт, и сидеры сохраняют данные одинаково.
     */
    public function setCharacteristicsAttribute(mixed $value): void
    {
        $this->attributes['characteristics'] = json_encode(
            self::normalizeCharacteristics($value),
            JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * @return array<int, array{name: string, value: string, unit: string|null}>
     */
    public function characteristicsList(): array
    {
        return self::normalizeCharacteristics($this->characteristics);
    }

    /**
     * Характеристики для витрины: единица измерения приклеена к значению,
     * многозначные варианты перечислены через запятую.
     *
     * @return array<int, array{name: string, value: string}>
     */
    public function characteristicsForDisplay(): array
    {
        return array_map(static function (array $characteristic): array {
            $values = array_filter(array_map(
                'trim',
                explode(self::CHARACTERISTIC_VALUE_SEPARATOR, $characteristic['value']),
            ), static fn (string $value): bool => $value !== '');

            $value = implode(', ', $values);

            return [
                'name' => $characteristic['name'],
                'value' => $characteristic['unit'] === null ? $value : "{$value} {$characteristic['unit']}",
            ];
        }, $this->characteristicsList());
    }

    // Получить путь к изображению (с поддержкой CDN)
    public function getImagePath()
    {
        if (!$this->image_path) {
            return asset('dist/img/no-image.png'); // Fallback изображение
        }

        // Если путь уже содержит полный URL (например, из CDN)
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        // Проверяем, есть ли изображение в локальном хранилище
        $localPath = storage_path('app/public/' . $this->image_path);
        if (file_exists($localPath)) {
            return asset('storage/' . $this->image_path);
        }

        // Если изображение на CDN (BunnyCDN)
        if (config('app.cdn_url')) {
            return config('app.cdn_url') . '/' . $this->image_path;
        }

        // Fallback на storage
        return asset('storage/' . $this->image_path);
    }

    protected static function booted()
    {
        static::saved(fn () => forget_mega_menu_cache());
        static::deleted(fn () => forget_mega_menu_cache());

        static::deleting(function ($product) {
            // Удаляем связи many-to-many
            $product->catalogs()->detach();
            $product->categories()->detach();
            
            // Удаляем характеристики товара
            $product->packages()->delete();
            
            // Удаляем связанные изображения
            $productImages = \App\Models\productImage::where('product_id', $product->id)->get();
            foreach ($productImages as $image) {
                // Удаляем файл с CDN
                if (class_exists('\App\Helpers\FileUploadHelper')) {
                    \App\Helpers\FileUploadHelper::deleteFromBunnyCDN($image->src);
                }
                // Удаляем запись из базы
                $image->delete();
            }
        });
    }


    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {
            // Скидка никогда не null — иначе SQLSTATE 23000 на NOT NULL / старых схемах.
            $product->discount = max(0, min(100, (int) ($product->discount ?? 0)));

            // Пустые USD-поля → null (не пустая строка), чтобы decimal-cast не падал.
            foreach (['price_usd', 'wholesale_price_usd'] as $usdField) {
                $raw = $product->{$usdField};
                if ($raw === '' || $raw === null) {
                    $product->{$usdField} = null;
                }
            }

            foreach (['description', 'description_ru'] as $textField) {
                if ($product->{$textField} === null) {
                    $product->{$textField} = '';
                }
            }

            // USD → UAH по текущему курсу магазина (витрина всегда в грн)
            $rate = \App\Services\ShopSettings::usdRate();
            if ($rate > 0) {
                if ($product->price_usd !== null && (float) $product->price_usd > 0) {
                    $product->price = round((float) $product->price_usd * $rate, 2);
                }
                if ($product->wholesale_price_usd !== null && (float) $product->wholesale_price_usd > 0) {
                    $product->wholesale_price = round((float) $product->wholesale_price_usd * $rate, 2);
                }
            }

            // Генерируем базовый URL, если пустой
            $baseUrl = self::generateHref($product->name);

            // Проверяем есть ли уже такой URL у других продуктов
            $exists = self::where('url', $baseUrl)
                ->when($product->id, fn($query) => $query->where('id', '!=', $product->id)) // исключаем текущий продукт при обновлении
                ->exists();

            if (!$exists) {
                $product->url = $baseUrl;
            } else {
                // Если такой URL уже есть, добавляем ID к URL
                // Для новых продуктов $product->id еще нет, поэтому можно сгенерировать уникальный вариант через временный суффикс
                if ($product->id) {
                    $product->url = $baseUrl . '-' . $product->id;
                } else {
                    // Если id еще нет (новый продукт), то генерируем уникальный суффикс, например, временную метку или случайное число
                    $product->url = $baseUrl . '-' . uniqid();
                }
            }
        });

        static::saved(function ($product) {
            self::regenerateFeed();
        });

        static::deleted(function ($product) {
            self::regenerateFeed();
        });
    }

    protected static function regenerateFeed()
    {
        if (class_exists(ProductFeedService::class)) {
            ProductFeedService::generate();
        }
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
