<?php

namespace App\Services\Products;

/**
 * Единый справочник полей товара для импорта, экспорта и шаблона-заготовки.
 *
 * Псевдонимы (`aliases`) — это заголовки, под которыми поле встречается во
 * внешних файлах: колонки выгрузки Prom.ua и заголовки нашего же экспорта.
 * Благодаря им один импортёр читает и «родной» шаблон, и файл с Prom.ua.
 */
class ProductFieldMap
{
    public const GROUP_MAIN = 'Основное';
    public const GROUP_PRICES = 'Цены';
    public const GROUP_IMAGES = 'Изображения';
    public const GROUP_CHARACTERISTICS = 'Характеристики';
    public const GROUP_RELATIONS = 'Связи';
    public const GROUP_SEO = 'SEO';
    public const GROUP_SERVICE = 'Служебное';

    public const TYPE_STRING = 'string';
    public const TYPE_TEXT = 'text';
    public const TYPE_INT = 'int';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_BOOL = 'bool';
    public const TYPE_DATE = 'date';
    public const TYPE_AVAILABILITY = 'availability';
    public const TYPE_CONDITION = 'condition';
    public const TYPE_IMAGES = 'images';
    public const TYPE_CATEGORIES = 'categories';
    public const TYPE_CATALOGS = 'catalogs';
    public const TYPE_CHARACTERISTICS = 'characteristics';

    /** Колонки Prom.ua, из которых собираются тройки характеристик. */
    public const PROM_CHARACTERISTIC_NAME = 'Назва_Характеристики';
    public const PROM_CHARACTERISTIC_UNIT = 'Одиниця_виміру_Характеристики';
    public const PROM_CHARACTERISTIC_VALUE = 'Значення_Характеристики';

    /**
     * @return array<string, array{label: string, group: string, type: string, aliases: array<int, string>, example: string, importable?: bool}>
     */
    public static function fields(): array
    {
        return [
            'id' => [
                'label' => 'ID',
                'group' => self::GROUP_SERVICE,
                'type' => self::TYPE_INT,
                'aliases' => [],
                'example' => '1024',
                'importable' => false,
            ],
            'name' => [
                'label' => 'Название',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_STRING,
                'aliases' => ['Назва_позиції_укр', 'Назва позиції укр', 'Название укр'],
                'example' => 'Ліхтарик акумуляторний Almina DL-2424',
            ],
            'name_ru' => [
                'label' => 'Название (рус.)',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_STRING,
                'aliases' => ['Назва_позиції', 'Назва позиції'],
                'example' => 'Фонарик аккумуляторный Almina DL-2424',
            ],
            'articule' => [
                'label' => 'Артикул',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_STRING,
                'aliases' => ['Код_товару', 'Код товару', 'Артикул', 'SKU'],
                'example' => '4024',
            ],
            'external_id' => [
                'label' => 'Внешний ID',
                'group' => self::GROUP_SERVICE,
                'type' => self::TYPE_STRING,
                'aliases' => ['Унікальний_ідентифікатор', 'Унікальний ідентифікатор'],
                'example' => '1960198430',
            ],
            'brand' => [
                'label' => 'Бренд',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_STRING,
                'aliases' => ['Виробник', 'Производитель'],
                'example' => 'Almina',
            ],
            'country' => [
                'label' => 'Страна производитель',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_STRING,
                'aliases' => ['Країна_виробник', 'Країна виробник', 'Страна'],
                'example' => 'Китай',
            ],
            'availability' => [
                'label' => 'Наличие',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_AVAILABILITY,
                'aliases' => ['Наявність', 'Наличие'],
                'example' => 'В наличии',
            ],
            'condition_item' => [
                'label' => 'Состояние',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_CONDITION,
                'aliases' => ['Стан', 'Состояние'],
                'example' => 'Новый',
            ],
            'weight' => [
                'label' => 'Вес, кг',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_DECIMAL,
                'aliases' => ['Вага,кг', 'Вага, кг', 'Вес'],
                'example' => '1.5',
            ],
            'description' => [
                'label' => 'Описание',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_TEXT,
                'aliases' => ['Опис_укр', 'Опис укр', 'Описание укр'],
                'example' => '<p>Акумуляторний ліхтар для дому та туризму.</p>',
            ],
            'description_ru' => [
                'label' => 'Описание (рус.)',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_TEXT,
                'aliases' => ['Опис', 'Описание'],
                'example' => '<p>Аккумуляторный фонарь для дома и туризма.</p>',
            ],
            'complectation' => [
                'label' => 'Комплектация',
                'group' => self::GROUP_MAIN,
                'type' => self::TYPE_TEXT,
                'aliases' => ['Комплектація', 'Комплектация'],
                'example' => 'Ліхтар, кабель USB, інструкція',
            ],
            'admin_notes' => [
                'label' => 'Заметки (не видны на сайте)',
                'group' => self::GROUP_SERVICE,
                'type' => self::TYPE_TEXT,
                'aliases' => ['Особисті_нотатки', 'Особисті нотатки', 'Заметки'],
                'example' => 'Уточнить остаток у поставщика',
            ],

            'price' => [
                'label' => 'Цена',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_DECIMAL,
                'aliases' => ['Ціна', 'Цена'],
                'example' => '650',
            ],
            'discount' => [
                'label' => 'Скидка, %',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_INT,
                'aliases' => ['Знижка', 'Скидка'],
                'example' => '25',
            ],
            'discount_starts_at' => [
                'label' => 'Скидка действует с',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_DATE,
                'aliases' => ['Термін_дії_знижки_від', 'Термін дії знижки від'],
                'example' => '01.09.2026',
            ],
            'discount_ends_at' => [
                'label' => 'Скидка действует до',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_DATE,
                'aliases' => ['Термін_дії_знижки_до', 'Термін дії знижки до'],
                'example' => '30.09.2026',
            ],
            'unit_name' => [
                'label' => 'Единица измерения',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_STRING,
                'aliases' => ['Одиниця_виміру', 'Одиниця виміру'],
                'example' => 'шт.',
            ],
            'unit_name_plural' => [
                'label' => 'Единица измерения (мн.)',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_STRING,
                'aliases' => [],
                'example' => 'шт.',
            ],
            'units_per_box' => [
                'label' => 'Единиц в ящике',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_INT,
                'aliases' => [],
                'example' => '12',
            ],
            'min_order_quantity' => [
                'label' => 'Минимальный заказ',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_INT,
                'aliases' => ['Мінімальний_обсяг_замовлення', 'Мінімальний обсяг замовлення'],
                'example' => '1',
            ],
            'is_wholesale' => [
                'label' => 'Оптовый товар',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_BOOL,
                'aliases' => [],
                'example' => 'да',
            ],
            'wholesale_price' => [
                'label' => 'Оптовая цена',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_DECIMAL,
                'aliases' => ['Оптова_ціна', 'Оптова ціна'],
                'example' => '480',
            ],
            'wholesale_min_quantity' => [
                'label' => 'Минимальный опт. заказ',
                'group' => self::GROUP_PRICES,
                'type' => self::TYPE_INT,
                'aliases' => ['Мінімальне_замовлення_опт', 'Мінімальне замовлення опт'],
                'example' => '12',
            ],

            'image_path' => [
                'label' => 'Главное изображение',
                'group' => self::GROUP_IMAGES,
                'type' => self::TYPE_STRING,
                'aliases' => ['Изображение', 'Фото', 'Картинка'],
                'example' => 'https://images.prom.ua/7661102500_fonarik.jpg',
            ],
            'images' => [
                'label' => 'Все изображения',
                'group' => self::GROUP_IMAGES,
                'type' => self::TYPE_IMAGES,
                'aliases' => ['Посилання_зображення', 'Посилання зображення'],
                'example' => 'https://images.prom.ua/1.jpg, https://images.prom.ua/2.jpg',
            ],

            'characteristics' => [
                'label' => 'Характеристики',
                'group' => self::GROUP_CHARACTERISTICS,
                'type' => self::TYPE_CHARACTERISTICS,
                'aliases' => [],
                'example' => 'Колір=Білий; Вага=2 г; Тип заряду=Від мережі|USB',
            ],

            'categories' => [
                'label' => 'Категории',
                'group' => self::GROUP_RELATIONS,
                'type' => self::TYPE_CATEGORIES,
                'aliases' => ['Назва_групи', 'Назва групи', 'Категория', 'Группа'],
                'example' => 'Ручні та налобні фонарі',
            ],
            'catalogs' => [
                'label' => 'Каталоги',
                'group' => self::GROUP_RELATIONS,
                'type' => self::TYPE_CATALOGS,
                'aliases' => [],
                'example' => 'Туризм',
            ],

            'seo_title' => [
                'label' => 'SEO заголовок',
                'group' => self::GROUP_SEO,
                'type' => self::TYPE_STRING,
                'aliases' => ['HTML_заголовок_укр', 'HTML заголовок укр', 'HTML_заголовок'],
                'example' => 'Ліхтарик Almina DL-2424 — купити в Україні',
            ],
            'seo_description' => [
                'label' => 'SEO описание',
                'group' => self::GROUP_SEO,
                'type' => self::TYPE_TEXT,
                'aliases' => ['HTML_опис_укр', 'HTML опис укр', 'HTML_опис'],
                'example' => 'Акумуляторний ліхтар Almina DL-2424 з доставкою.',
            ],
            'seo_keywords' => [
                'label' => 'Поисковые запросы',
                'group' => self::GROUP_SEO,
                'type' => self::TYPE_TEXT,
                'aliases' => ['Пошукові_запити_укр', 'Пошукові запити укр', 'Пошукові_запити', 'Ключові слова'],
                'example' => 'ліхтарик, фонарик almina, ліхтар акумуляторний',
            ],

            'url' => [
                'label' => 'URL (ЧПУ)',
                'group' => self::GROUP_SERVICE,
                'type' => self::TYPE_STRING,
                'aliases' => [],
                'example' => 'likhtarik-almina-dl-2424',
                'importable' => false,
            ],
        ];
    }

    /**
     * Поля для правки пробелов после импорта: ID обязателен для обратной загрузки.
     *
     * @return array<int, string>
     */
    public static function criticalFixFields(): array
    {
        return [
            'id',
            'name',
            'articule',
            'price',
            'categories',
            'image_path',
            'images',
            'description',
            'brand',
            'characteristics',
        ];
    }

    /**
     * Поля, которые предлагаются в экспорте по умолчанию: то, с чем обычно
     * работают в таблице, без тяжёлых описаний и служебных колонок.
     *
     * @return array<int, string>
     */
    public static function defaultExportFields(): array
    {
        return [
            'id',
            'name',
            'articule',
            'external_id',
            'price',
            'discount',
            'availability',
            'brand',
            'categories',
            'image_path',
            'characteristics',
        ];
    }

    /**
     * Поля шаблона для заполнения: минимум, которого достаточно, чтобы
     * создать товар с картинками и характеристиками.
     *
     * @return array<int, string>
     */
    public static function defaultTemplateFields(): array
    {
        return [
            'name',
            'articule',
            'price',
            'discount',
            'availability',
            'brand',
            'categories',
            'images',
            'characteristics',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::fields());
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(
            static fn (array $field): string => $field['label'],
            self::fields(),
        );
    }

    /**
     * @return array<string, array{label: string, group: string, type: string, aliases: array<int, string>, example: string, importable?: bool}>
     */
    public static function importableFields(): array
    {
        return array_filter(
            self::fields(),
            static fn (array $field): bool => $field['importable'] ?? true,
        );
    }

    /**
     * Поля, сгруппированные для чекбоксов выбора колонок: ['Основное' => ['name' => 'Название']].
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedLabels(): array
    {
        $grouped = [];

        foreach (self::fields() as $key => $field) {
            $grouped[$field['group']][$key] = $field['label'];
        }

        return $grouped;
    }

    public static function label(string $key): string
    {
        return self::fields()[$key]['label'] ?? $key;
    }

    public static function type(string $key): string
    {
        return self::fields()[$key]['type'] ?? self::TYPE_STRING;
    }

    public static function example(string $key): string
    {
        return self::fields()[$key]['example'] ?? '';
    }

    /**
     * Ищет поле по заголовку колонки во внешнем файле: сравнивает и с
     * названием поля, и с человеческой подписью, и со псевдонимами.
     */
    public static function resolveField(string $header): ?string
    {
        $needle = self::normalizeHeader($header);

        if ($needle === '') {
            return null;
        }

        foreach (self::fields() as $key => $field) {
            $candidates = array_merge([$key, $field['label']], $field['aliases']);

            foreach ($candidates as $candidate) {
                if (self::normalizeHeader($candidate) === $needle) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Приводит заголовок к сравнимому виду: без регистра, подчёркиваний и
     * лишних пробелов, чтобы «Назва_позиції_укр» и «Назва позиції укр» совпали.
     */
    public static function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = mb_strtolower(trim($header));
        $header = str_replace(['_', '.', ','], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header) ?? $header;

        return trim($header);
    }
}
