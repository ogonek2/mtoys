<?php

namespace App\Filament\Support;

use App\Models\Catalog;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ShopOptions
{
    /**
     * Значения совпадают с теми, по которым фильтрует витрина
     * (ProductListingService, SpaPageService).
     */
    public const AVAILABILITY = [
        'in_stock' => 'В наличии',
        'out_of_stock' => 'Нет в наличии',
    ];

    public const CONDITION = [
        'new' => 'Новый',
        'used' => 'Б/у',
        'refurbished' => 'Восстановленный',
    ];

    public const CATALOG_TYPE = [
        'group' => 'Группа',
        'subgroup' => 'Подгруппа',
    ];

    /** @var array<class-string, Collection<int, Model>> */
    protected static array $records = [];

    /** @var array<class-string, array<int, string>> */
    protected static array $labels = [];

    /**
     * Дерево категорий как плоский список меток «Дом › Кухня › Тарелки».
     *
     * @return array<int, string>
     */
    public static function categories(?int $excludeBranchId = null): array
    {
        return self::options(Category::class, $excludeBranchId);
    }

    /**
     * @return array<int, string>
     */
    public static function catalogs(?int $excludeBranchId = null): array
    {
        return self::options(Catalog::class, $excludeBranchId);
    }

    public static function categoryLabel(int $id, string $fallback = ''): string
    {
        return self::labels(Category::class)[$id] ?? $fallback;
    }

    public static function catalogLabel(int $id, string $fallback = ''): string
    {
        return self::labels(Catalog::class)[$id] ?? $fallback;
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<int, string>
     */
    protected static function options(string $model, ?int $excludeBranchId): array
    {
        $labels = self::labels($model);

        if ($excludeBranchId) {
            foreach (self::branchIds($model, $excludeBranchId) as $id) {
                unset($labels[$id]);
            }
        }

        return $labels;
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<int, string>
     */
    protected static function labels(string $model): array
    {
        if (isset(self::$labels[$model])) {
            return self::$labels[$model];
        }

        $records = self::records($model);
        $byId = $records->keyBy('id');
        $labels = [];

        foreach ($records as $record) {
            $parts = [];
            $current = $record;
            $depth = 0;

            while ($current && $depth < 10) {
                array_unshift($parts, $current->name);
                $current = $current->parent_id ? $byId->get($current->parent_id) : null;
                $depth++;
            }

            $labels[$record->id] = implode(' › ', $parts);
        }

        asort($labels);

        return self::$labels[$model] = $labels;
    }

    /**
     * Узел вместе со всеми потомками: такую ветку нельзя выбрать
     * в качестве родителя, иначе получится цикл.
     *
     * @param  class-string<Model>  $model
     * @return array<int, int>
     */
    protected static function branchIds(string $model, int $rootId): array
    {
        $children = self::records($model)->groupBy('parent_id');
        $ids = [$rootId];
        $queue = [$rootId];

        while ($queue) {
            $parentId = array_shift($queue);

            foreach ($children->get($parentId, collect()) as $child) {
                if (in_array($child->id, $ids, true)) {
                    continue;
                }

                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * @param  class-string<Model>  $model
     * @return Collection<int, Model>
     */
    protected static function records(string $model): Collection
    {
        return self::$records[$model] ??= $model::query()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'parent_id']);
    }
}
